<?php

namespace DomBase\DomApiBundle\Controller;

use App\Entity\CategoryTree;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use DomBase\DomApiBundle\Event\ApiEvents;
use DomBase\DomApiBundle\Exception\EntityRemoveException;
use DomBase\DomApiBundle\Exception\ForbiddenActionException;
use DomBase\DomApiBundle\Exception\NoEntitiesConfiguredException;
use DomBase\DomApiBundle\Exception\UndefinedEntityException;
use DomBase\DomApiBundle\Form\Filter\FilterRegistry;
use DomBase\DomApiBundle\Form\Type\EasyAdminBatchFormType;
use DomBase\DomApiBundle\Form\Type\EasyAdminFiltersFormType;
use DomBase\DomApiBundle\Form\Type\EasyAdminFormType;
use http\Exception\RuntimeException;
use Lle\EasyAdminPlusBundle\Translator\Event\EasyAdminPlusTranslatorEvents;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Common features needed in admin controllers.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait ApiControllerTrait
{
    /** @var array The full configuration of the entire backend */
    protected $config;
    /** @var array The full configuration of the current entity */
    protected $entity = [];
    /** @var Request The instance of the current Symfony request */
    protected $request;
    /** @var EntityManager|null The Doctrine entity manager for the current entity */
    protected $em;

    /**
     * @Route("/", name="domapi")
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @throws ForbiddenActionException
     */
    public function indexAction(Request $request)
    {
        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            return $this->redirectToBackendHomepage();
        }

        $action = $request->query->get('action', 'list');
        if (!$this->isActionAllowed($action)) {
            throw new ForbiddenActionException(['action' => $action, 'entity_name' => $this->entity['name']]);
        }

        return $this->executeDynamicMethod($action.'<EntityName>Action');
    }

    /**
     * Utility method which initializes the configuration of the entity on which
     * the user is performing the action.
     *
     * @param Request $request
     *
     * @throws NoEntitiesConfiguredException
     * @throws UndefinedEntityException
     */
    protected function initialize(Request $request)
    {
        $this->dispatch(ApiEvents::PRE_INITIALIZE);

        $this->config = $this->get('domapi.config.manager')->getBackendConfig();

        if (0 === \count($this->config['entities'])) {
            throw new NoEntitiesConfiguredException();
        }

        // this condition happens when accessing the backend homepage and before
        // redirecting to the default page set as the homepage
        if (null === $entityName = $request->query->get('entity')) {
            return;
        }

        if (!\array_key_exists($entityName, $this->config['entities'])) {
            throw new UndefinedEntityException(['entity_name' => $entityName]);
        }

        $this->entity = $this->get('domapi.config.manager')->getEntityConfig($entityName);

        $action = $request->query->get('action', 'list');
        if (!$request->query->has('sortField')) {
            $sortField = $this->entity[$action]['sort']['field'] ?? $this->entity['primary_key_field_name'];
            $request->query->set('sortField', $sortField);
        }
        if (!$request->query->has('sortDirection')) {
            $sortDirection = $this->entity[$action]['sort']['direction'] ?? 'DESC';
            $request->query->set('sortDirection', $sortDirection);
        }

        $this->em = $this->getDoctrine()->getManagerForClass($this->entity['class']);
        $this->request = $request;

        $this->dispatch(ApiEvents::POST_INITIALIZE);
    }

    protected function dispatch($eventName, array $arguments = [])
    {
        $arguments = \array_replace([
            'config' => $this->config,
            'em' => $this->em,
            'entity' => $this->entity,
            'request' => $this->request,
        ], $arguments);

        $subject = $arguments['paginator'] ?? $arguments['entity'];
        $event = new GenericEvent($subject, $arguments);

        if (Kernel::VERSION_ID >= 40300) {
            $this->get('event_dispatcher')->dispatch($event, $eventName);
        } else {
            $this->get('event_dispatcher')->dispatch($eventName, $event);
        }
    }

    /**
     * The method that returns the values displayed by an autocomplete field
     * based on the user's input.
     *
     * @return JsonResponse
     */
    protected function autocompleteAction()
    {
        $results = $this->get('domapi.autocomplete')->find(
            $this->request->query->get('entity'),
            $this->request->query->get('query'),
            $this->request->query->get('page', 1)
        );

        return new JsonResponse($results);
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        $this->dispatch(ApiEvents::PRE_LIST);

        if(isset($this->entity['tree']) && $this->entity['tree'] == true) {
            $fields = $this->entity['list']['fields'];

            $repo = $this->getDoctrine()->getRepository($this->entity['class']);
            $repo->setChildrenIndex('children');
            $tree = $repo->childrenHierarchy();

            $parameters = [
                'tree' => $tree,
                'fields' => $fields,
            ];

            return $this->executeDynamicMethod('render<EntityName>Template', ['tree', $this->entity['templates']['tree'], $parameters]);
        } else {
            $fields = $this->entity['list']['fields'];
            $paginator = $this->findAll(
                $this->entity['class'],
                $this->request->query->get('page', 1),
                $this->entity['list']['max_results'],
                $this->request->query->get('sortField'),
                $this->request->query->get('sortDirection'),
                $this->entity['list']['dql_filter']
            );

            $this->dispatch(ApiEvents::POST_LIST, ['paginator' => $paginator]);

            $parameters = [
                'paginator' => $paginator->getCurrentPageResults(),
                'fields' => $fields,
            ];

            return $this->executeDynamicMethod('render<EntityName>Template', [$parameters]);
        }
    }

    /**
     * The method that is executed when the user performs a 'edit' action on an entity.
     *
     * @return Response|RedirectResponse
     *
     * @throws \RuntimeException
     */
    protected function editAction()
    {
        $this->dispatch(ApiEvents::PRE_EDIT);

        $id = $this->request->query->get('id');
        $domapi = $this->request->attributes->get('domapi');
        $entity = $domapi['item'];

        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === \mb_strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];

            if (!isset($fieldsMetadata[$property]) || 'toggle' !== $fieldsMetadata[$property]['dataType']) {
                throw new \RuntimeException(\sprintf('The type of the "%s" property is not "toggle".', $property));
            }

            $this->updateEntityProperty($entity, $property, $newValue);

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }

        $fields = $this->entity['edit']['fields'];

        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->dispatch(ApiEvents::PRE_UPDATE, ['entity' => $entity]);
            $this->executeDynamicMethod('update<EntityName>Entity', [$entity, $editForm]);
            $this->dispatch(ApiEvents::POST_UPDATE, ['entity' => $entity]);

            return $this->redirectToReferrer();
        }

        $this->dispatch(ApiEvents::POST_EDIT);

        $parameters = [
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['edit', $this->entity['templates']['edit'], $parameters]);
    }

    /**
     * The method that is executed when the user performs a 'show' action on an entity.
     *
     * @return Response
     */
    protected function showAction()
    {
        $this->dispatch(ApiEvents::PRE_SHOW);

        $domapi = $this->request->attributes->get('domapi');
        $entity = $domapi['item'];

        $fields = $this->entity['show']['fields'];

        $this->dispatch(ApiEvents::POST_SHOW, [
            'fields' => $fields,
            'entity' => $entity,
        ]);
        $serializedEntity = $this->get('domapi.serializer')->serialize($entity);
        dump($serializedEntity);exit;
        $parameters = [
            'entity' => $entity,
            'fields' => $fields,
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', $parameters);
    }

    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
     * @return Response|RedirectResponse
     */
    protected function newAction()
    {
        $this->dispatch(ApiEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');

        $domapi = $this->request->attributes->get('domapi');
        $domapi['item'] = $entity;
        $this->request->attributes->set('domapi', $domapi);

        $fields = $this->entity['new']['fields'];

        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', [$entity, $fields]);

        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->dispatch(ApiEvents::PRE_PERSIST, ['entity' => $entity]);
            $this->executeDynamicMethod('persist<EntityName>Entity', [$entity, $newForm]);
            $this->dispatch(ApiEvents::POST_PERSIST, ['entity' => $entity]);

            return $this->redirectToReferrer();
        }

        $this->dispatch(ApiEvents::POST_NEW, [
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ]);

        $parameters = [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['new', $this->entity['templates']['new'], $parameters]);
    }

    /**
     * The method that is executed when the user performs a 'delete' action to
     * remove any entity.
     *
     * @return RedirectResponse
     *
     * @throws EntityRemoveException
     */
    protected function deleteAction()
    {
        $this->dispatch(ApiEvents::PRE_DELETE);

        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('domapi', ['action' => 'list', 'entity' => $this->entity['name']]));
        }

        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domapi = $this->request->attributes->get('domapi');
            $entity = $domapi['item'];

            $this->dispatch(ApiEvents::PRE_REMOVE, ['entity' => $entity]);

            try {
                $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => $this->entity['name'], 'message' => $e->getMessage()]);
            }

            $this->dispatch(ApiEvents::POST_REMOVE, ['entity' => $entity]);
        }

        $this->dispatch(ApiEvents::POST_DELETE);

        return $this->redirectToReferrer();
    }

    /**
     * The method that is executed when the user performs a query on an entity.
     *
     * @return Response
     */
    protected function searchAction()
    {
        $this->dispatch(ApiEvents::PRE_SEARCH);

        $query = \trim($this->request->query->get('query'));
        // if the search query is empty, redirect to the 'list' action
        if ('' === $query) {
            $queryParameters = \array_replace($this->request->query->all(), ['action' => 'list']);
            unset($queryParameters['query']);

            return $this->redirect($this->get('router')->generate('domapi', $queryParameters));
        }

        $searchableFields = $this->entity['search']['fields'];
        $paginator = $this->findBy(
            $this->entity['class'],
            $query,
            $searchableFields,
            $this->request->query->get('page', 1),
            $this->entity['list']['max_results'],
            $this->request->query->get('sortField'),
            $this->request->query->get('sortDirection'),
            $this->entity['search']['dql_filter']
        );
        $fields = $this->entity['list']['fields'];

        $this->dispatch(ApiEvents::POST_SEARCH, [
            'fields' => $fields,
            'paginator' => $paginator,
        ]);

        $parameters = [
            'paginator' => $paginator,
            'fields' => $fields,
            'batch_form' => $this->createBatchForm($this->entity['name'])->createView(),
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['search', $this->entity['templates']['list'], $parameters]);
    }


    /**
     * export action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction()
    {
        $entityName = $this->entity['name'];
        $user = $this->getUser();

        $this->dispatch(ApiEvents::PRE_EXPORT, [
            'user' => [
                'username' => $user ? $user->getUsername() : null,
                'roles' => $user ? $user->getRoles() : [],
            ],
        ]);

        // no export configuration? > take all the entity fields
        if (!array_key_exists('export', $this->config['entities'][$entityName]) ||
            empty($this->config['entities'][$entityName]['export']) ||
            !array_key_exists('fields', $this->config['entities'][$entityName]['export']) ||
            empty($this->config['entities'][$entityName]['export']['fields'])) {
            $this->config['entities'][$entityName]['export']['fields'] = $this->config['entities'][$entityName]['properties'];
        }

        $this->dispatch(ApiEvents::PRE_LIST);
        $paginator = $this->findFiltered(
            $this->entity, $this->entity['class'],
            1,
            PHP_INT_MAX, $this->request->query->get('sortField'),
            $this->request->query->get('sortDirection'),
            $this->entity['list']['dql_filter']);

        $fields = $this->entity['list']['fields'];
        $this->dispatch(ApiEvents::POST_LIST, [
            'fields' => $fields,
            'paginator' => $paginator,
        ]);

        $this->dispatch(ApiEvents::POST_EXPORT, [
            'user' => [
                'username' => $user ? $user->getUsername() : null,
                'roles' => $user ? $user->getRoles() : [],
            ],
        ]);

        $exportManager = $this->get('domapi.export_service');
        $filename = sprintf('export-%s-%s', strtolower($this->entity['name']), date('Ymd_His'));
        return $exportManager->generateResponse($paginator, $this->config['entities'][$entityName]['export']['fields'], $filename, $this->request->get('format'));
    }

    protected function createBatchForm(string $entityName): FormInterface
    {
        return $this->get('form.factory')->createNamed('batch_form', EasyAdminBatchFormType::class, null, [
            'action' => $this->generateUrl('domapi', ['action' => 'batch', 'entity' => $entityName]),
            'entity' => $entityName,
        ]);
    }

    protected function deleteBatchAction(array $ids): void
    {
        $class = $this->entity['class'];

        $this->getDoctrine()->getManagerForClass($class)
            ->createQueryBuilder()
            ->delete()
            ->from($class, 'entity')
            ->where(\sprintf('entity.%s IN (:ids)', $this->entity['primary_key_field_name']))
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    public function getChildrenAction()
    {
        $id = $this->request->query->get('id');
        $domapi = $this->request->attributes->get('domapi');

        $repo = $this->getDoctrine()->getRepository($this->entity['class']);
        $repo->setChildrenIndex('children');
        $node = $repo->find($id);

        $entity = $domapi['item'];

        $children = $repo->childrenHierarchy($node, false);

        return $this->render('@domapi/default/includes/_child.html.twig', ['children' => $children]);
    }

    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
     * @return Response|RedirectResponse
     */
    protected function newChildAction()
    {
        if (null == $parent_id = $this->request->query->get('parent_id', null)) {
            throw new \RuntimeException('"parent_id" is required to add new resource.');
        }

        $repo = $this->getDoctrine()->getRepository($this->entity['class']);
        $parent = $repo->find($parent_id);

        $this->dispatch(ApiEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');
        $entity->setParent($parent);

        $domapi = $this->request->attributes->get('domapi');
        $domapi['item'] = $entity;
        $this->request->attributes->set('domapi', $domapi);

        $fields = $this->entity['new']['fields'];

        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', [$entity, $fields]);

        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->dispatch(ApiEvents::PRE_PERSIST, ['entity' => $entity]);
            $this->executeDynamicMethod('persist<EntityName>Entity', [$entity, $newForm]);
            $this->dispatch(ApiEvents::POST_PERSIST, ['entity' => $entity]);

            return $this->redirectToReferrer();
        }

        $this->dispatch(ApiEvents::POST_NEW, [
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ]);

        $parameters = [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['new', $this->entity['templates']['new'], $parameters]);
    }

    /**
     * The method that is executed when the user open the filters modal on an entity.
     *
     * @return Response
     */
    protected function filtersAction()
    {
        $filtersForm = $this->createFiltersForm($this->entity['name']);
        $filtersForm->handleRequest($this->request);

        $domapi = $this->request->attributes->get('domapi');
        $domapi['filters']['applied'] = \array_keys($this->request->get('filters', []));
        $this->request->attributes->set('domapi', $domapi);

        $parameters = [
            'filters_form' => $filtersForm->createView(),
            'referer_action' => $this->request->get('referer_action', 'list'),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['filters', $this->entity['templates']['filters'], $parameters]);
    }

    /**
     * The method that apply all configured filter to the list QueryBuilder.
     */
    protected function filterQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if (!$requestData = $this->request->get('filters')) {
            // Don't create the filters form if there is no filter applied
            return;
        }

        /** @var Form $filtersForm */
        $filtersForm = $this->createFiltersForm($this->entity['name']);
        $filtersForm->handleRequest($this->request);
        if (!$filtersForm->isSubmitted()) {
            return;
        }

        /** @var FilterRegistry $filterRegistry */
        $filterRegistry = $this->get('domapi.filter.registry');

        $appliedFilters = [];
        foreach ($filtersForm as $filterForm) {
            $name = $filterForm->getName();
            if (!isset($requestData[$name])) {
                // this filter is not applied
                continue;
            }

            // resolve the filter type related to this form field
            $filterType = $filterRegistry->resolveType($filterForm);

            $metadata = $this->entity['list']['filters'][$name] ?? [];
            if (false !== $filterType->filter($queryBuilder, $filterForm, $metadata)) {
                $appliedFilters[] = $name;
            }
        }

        $domapi = $this->request->attributes->get('domapi');
        $domapi['filters']['applied'] = $appliedFilters;
        $this->request->attributes->set('domapi', $domapi);
    }

    protected function createFiltersForm(string $entityName): FormInterface
    {
        return $this->get('form.factory')->createNamed('filters', EasyAdminFiltersFormType::class, null, [
            'method' => 'GET',
            'entity' => $entityName,
        ]);
    }

    /**
     * It updates the value of some property of some entity to the new given value.
     *
     * @param mixed  $entity   The instance of the entity to modify
     * @param string $property The name of the property to change
     * @param bool   $value    The new value of the property
     *
     * @throws \RuntimeException
     */
    protected function updateEntityProperty($entity, $property, $value)
    {
        $entityConfig = $this->entity;

        if (!$this->get('domapi.property_accessor')->isWritable($entity, $property)) {
            throw new \RuntimeException(\sprintf('The "%s" property of the "%s" entity is not writable.', $property, $entityConfig['name']));
        }

        $this->get('domapi.property_accessor')->setValue($entity, $property, $value);

        $this->dispatch(ApiEvents::PRE_UPDATE, ['entity' => $entity, 'newValue' => $value]);
        $this->executeDynamicMethod('update<EntityName>Entity', [$entity]);
        $this->dispatch(ApiEvents::POST_UPDATE, ['entity' => $entity, 'newValue' => $value]);

        $this->dispatch(ApiEvents::POST_EDIT);
    }

    /**
     * Creates a new object of the current managed entity.
     * This method is mostly here for override convenience, because it allows
     * the user to use his own method to customize the entity instantiation.
     *
     * @return object
     */
    protected function createNewEntity()
    {
        $entityFullyQualifiedClassName = $this->entity['class'];

        return new $entityFullyQualifiedClassName();
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * created while persisting it.
     *
     * @param object $entity
     */
    protected function persistEntity($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * edited before updating it.
     *
     * @param object $entity
     */
    protected function updateEntity($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * deleted before removing it.
     *
     * @param object $entity
     */
    protected function removeEntity($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Performs a database query to get all the records related to the given
     * entity. It supports pagination and field sorting.
     *
     * @param string      $entityClass
     * @param int         $page
     * @param int         $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if (null === $sortDirection || !\in_array(\strtoupper($sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = 'DESC';
        }

        $queryBuilder = $this->executeDynamicMethod('create<EntityName>ListQueryBuilder', [$entityClass, $sortDirection, $sortField, $dqlFilter]);

        $this->filterQueryBuilder($queryBuilder);

        $this->dispatch(ApiEvents::POST_LIST_QUERY_BUILDER, [
            'query_builder' => $queryBuilder,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ]);

        return $this->get('domapi.paginator')->createArrayPaginator($queryBuilder, $page, $maxPerPage);
    }

    /**
     * Creates Query Builder instance for all the records.
     *
     * @param string      $entityClass
     * @param string      $sortDirection
     * @param string|null $sortField
     * @param string|null $dqlFilter
     *
     * @return QueryBuilder The Query Builder instance
     */
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        return $this->get('domapi.query_builder')->createListQueryBuilder($this->entity, $sortField, $sortDirection, $dqlFilter);
    }

    /**
     * Performs a database query based on the search query provided by the user.
     * It supports pagination and field sorting.
     *
     * @param string      $entityClass
     * @param string      $searchQuery
     * @param array       $searchableFields
     * @param int         $page
     * @param int         $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if (empty($sortDirection) || !\in_array(\strtoupper($sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = 'DESC';
        }

        $queryBuilder = $this->executeDynamicMethod('create<EntityName>SearchQueryBuilder', [$entityClass, $searchQuery, $searchableFields, $sortField, $sortDirection, $dqlFilter]);

        $this->filterQueryBuilder($queryBuilder);

        $this->dispatch(ApiEvents::POST_SEARCH_QUERY_BUILDER, [
            'query_builder' => $queryBuilder,
            'search_query' => $searchQuery,
            'searchable_fields' => $searchableFields,
        ]);

        return $this->get('domapi.paginator')->createOrmPaginator($queryBuilder, $page, $maxPerPage);
    }

    /**
     * Creates Query Builder instance for search query.
     *
     * @param string      $entityClass
     * @param string      $searchQuery
     * @param array       $searchableFields
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return QueryBuilder The Query Builder instance
     */
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        return $this->get('domapi.query_builder')->createSearchQueryBuilder($this->entity, $searchQuery, $sortField, $sortDirection, $dqlFilter);
    }

    /**
     * Creates the form used to edit an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form|FormInterface
     */
    protected function createEditForm($entity, array $entityProperties)
    {
        return $this->createEntityForm($entity, $entityProperties, 'edit');
    }

    /**
     * Creates the form used to create an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form|FormInterface
     */
    protected function createNewForm($entity, array $entityProperties)
    {
        return $this->createEntityForm($entity, $entityProperties, 'new');
    }

    /**
     * Creates the form builder of the form used to create or edit the given entity.
     *
     * @param object $entity
     * @param string $view   The name of the view where this form is used ('new' or 'edit')
     *
     * @return FormBuilder
     */
    protected function createEntityFormBuilder($entity, $view)
    {
        $formOptions = $this->executeDynamicMethod('get<EntityName>EntityFormOptions', [$entity, $view]);

        return $this->get('form.factory')->createNamedBuilder(\mb_strtolower($this->entity['name']), EasyAdminFormType::class, $entity, $formOptions);
    }

    /**
     * Retrieves the list of form options before sending them to the form builder.
     * This allows adding dynamic logic to the default form options.
     *
     * @param object $entity
     * @param string $view
     *
     * @return array
     */
    protected function getEntityFormOptions($entity, $view)
    {
        $formOptions = $this->entity[$view]['form_options'];
        $formOptions['entity'] = $this->entity['name'];
        $formOptions['view'] = $view;

        return $formOptions;
    }

    /**
     * Creates the form object used to create or edit the given entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     * @param string $view
     *
     * @return FormInterface
     *
     * @throws \Exception
     */
    protected function createEntityForm($entity, array $entityProperties, $view)
    {
        if (\method_exists($this, $customMethodName = 'create'.$this->entity['name'].'EntityForm')) {
            $form = $this->{$customMethodName}($entity, $entityProperties, $view);
            if (!$form instanceof FormInterface) {
                throw new \UnexpectedValueException(\sprintf(
                    'The "%s" method must return a FormInterface, "%s" given.',
                    $customMethodName, \is_object($form) ? \get_class($form) : \gettype($form)
                ));
            }

            return $form;
        }

        $formBuilder = $this->executeDynamicMethod('create<EntityName>EntityFormBuilder', [$entity, $view]);

        if (!$formBuilder instanceof FormBuilderInterface) {
            throw new \UnexpectedValueException(\sprintf(
                'The "%s" method must return a FormBuilderInterface, "%s" given.',
                'createEntityForm', \is_object($formBuilder) ? \get_class($formBuilder) : \gettype($formBuilder)
            ));
        }

        return $formBuilder->getForm();
    }

    /**
     * Creates the form used to delete an entity. It must be a form because
     * the deletion of the entity are always performed with the 'DELETE' HTTP method,
     * which requires a form to work in the current browsers.
     *
     * @param string     $entityName
     * @param int|string $entityId   When reusing the delete form for multiple entities, a pattern string is passed instead of an integer
     *
     * @return Form|FormInterface
     */
    protected function createDeleteForm($entityName, $entityId)
    {
        /** @var FormBuilder $formBuilder */
        $formBuilder = $this->get('form.factory')->createNamedBuilder('delete_form')
            ->setAction($this->generateUrl('domapi', ['action' => 'delete', 'entity' => $entityName, 'id' => $entityId]))
            ->setMethod('DELETE')
        ;
        $formBuilder->add('submit', SubmitType::class, ['label' => 'delete_modal.action', 'translation_domain' => 'DomApiBundle']);
        // needed to avoid submitting empty delete forms (see issue #1409)
        $formBuilder->add('_domapi_delete_flag', HiddenType::class, ['data' => '1']);

        return $formBuilder->getForm();
    }

    /**
     * Utility method that checks if the given action is allowed for
     * the current entity.
     *
     * @param string $actionName
     *
     * @return bool
     */
    protected function isActionAllowed($actionName)
    {
        // TODO
        return true;
//        return false === \in_array($actionName, $this->entity['disabled_actions'], true);
    }

    /**
     * Given a method name pattern, it looks for the customized version of that
     * method (based on the entity name) and executes it. If the custom method
     * does not exist, it executes the regular method.
     *
     * For example:
     *   executeDynamicMethod('create<EntityName>Entity') and the entity name is 'User'
     *   if 'createUserEntity()' exists, execute it; otherwise execute 'createEntity()'
     *
     * @param string $methodNamePattern The pattern of the method name (dynamic parts are enclosed with <> angle brackets)
     * @param array  $arguments         The arguments passed to the executed method
     *
     * @return mixed
     */
    protected function executeDynamicMethod($methodNamePattern, array $arguments = [])
    {
        $methodName = \str_replace('<EntityName>', $this->entity['name'], $methodNamePattern);

        if (!\is_callable([$this, $methodName])) {
            $methodName = \str_replace('<EntityName>', '', $methodNamePattern);
        }
        return \call_user_func_array([$this, $methodName], $arguments);
    }

    /**
     * Generates the backend homepage and redirects to it.
     */
    protected function redirectToBackendHomepage()
    {
        $homepageConfig = $this->config['homepage'];

        $url = $homepageConfig['url'] ?? $this->get('router')->generate($homepageConfig['route'], $homepageConfig['params']);

        return $this->redirect($url);
    }

    /**
     * @return RedirectResponse
     */
    protected function redirectToReferrer()
    {
        $refererUrl = $this->request->query->get('referer', '');
        $refererAction = $this->request->query->get('action');

        // 1. redirect to list if possible
        if ($this->isActionAllowed('list')) {
            if (!empty($refererUrl)) {
                return $this->redirect(\urldecode($refererUrl));
            }

            return $this->redirectToRoute('domapi', [
                'action' => 'list',
                'entity' => $this->entity['name'],
                'menuIndex' => $this->request->query->get('menuIndex'),
                'submenuIndex' => $this->request->query->get('submenuIndex'),
            ]);
        }

        // 2. from new|edit action, redirect to edit if possible
        if (\in_array($refererAction, ['new', 'edit']) && $this->isActionAllowed('edit')) {
            return $this->redirectToRoute('domapi', [
                'action' => 'edit',
                'entity' => $this->entity['name'],
                'menuIndex' => $this->request->query->get('menuIndex'),
                'submenuIndex' => $this->request->query->get('submenuIndex'),
                'id' => ('new' === $refererAction)
                    ? PropertyAccess::createPropertyAccessor()->getValue($this->request->attributes->get('domapi')['item'], $this->entity['primary_key_field_name'])
                    : $this->request->query->get('id'),
            ]);
        }

        // 3. from new action, redirect to new if possible
        if ('new' === $refererAction && $this->isActionAllowed('new')) {
            return $this->redirectToRoute('domapi', [
                'action' => 'new',
                'entity' => $this->entity['name'],
                'menuIndex' => $this->request->query->get('menuIndex'),
                'submenuIndex' => $this->request->query->get('submenuIndex'),
            ]);
        }

        return $this->redirectToBackendHomepage();
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     * Instead of defining a render method per action (list, show, search, etc.) use
     * the $actionName argument to d
        if ($entity['tree'] ?? false) {
            $queryBuilder->orderBy($queryBuilder->getRootAlias().'.root');
            $queryBuilder->addOrderBy($queryBuilder->getRootAlias().'.lft');
        }iscriminate between actions.
     *
     * @param string $actionName   The name of the current action (list, show, new, etc.)
     * @param string $templatePath The path of the Twig template to render
     * @param array  $parameters   The parameters passed to the template
     *
     * @return Response
     */
    protected function renderTemplate(array $parameters = [])
    {
        return new JsonResponse($parameters);
//        return $this->render($templatePath, $parameters);
    }

    /**
     * Performs a database query to get all the records related to the given
     * entity. It supports pagination and field sorting.
     *
     * @param string $entityConfig
     * @param string $entityClass
     * @param int $page
     * @param int $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findFiltered($entity, $entityClass, $page = 1, $maxPerPage = 50, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
            $sortDirection = 'DESC';
        }

        $queryBuilder = $this->executeDynamicMethod('create<EntityName>ListQueryBuilder', array($entityClass, $sortDirection, $sortField, $dqlFilter));

        if ($entity['tree'] ?? false) {
            $queryBuilder->orderBy($queryBuilder->getRootAlias().'.root');
            $queryBuilder->addOrderBy($queryBuilder->getRootAlias().'.lft');
        }

        $this->dispatch(ApiEvents::POST_LIST_QUERY_BUILDER, array(
            'query_builder' => $queryBuilder,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ));
        $page = ($this->request->request->has('filter'))? 1:$page;
        try {
            return $this->get('domapi.paginator')->createOrmPaginator($queryBuilder, $page, $maxPerPage);
        }catch(OutOfRangeCurrentPageException $e){
            return $this->get('domapi.paginator')->createOrmPaginator($queryBuilder, 1, $maxPerPage);
        }
    }

}
