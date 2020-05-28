<?php

namespace DomBase\DomApiBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extension that injects EasyAdmin related information in the view used to
 * render the form.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminExtension extends AbstractTypeExtension
{
    /** @var RequestStack|null */
    private $requestStack;

    /**
     * @param RequestStack|null $requestStack
     */
    public function __construct(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $request = null;
        if (null !== $this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (null === $request) {
            return;
        }

        if ($request->attributes->has('domapi')) {
            $domapi = $request->attributes->get('domapi');
            $entity = $domapi['entity'];
            $action = $domapi['view'];
            $fields = $entity[$action]['fields'] ?? [];
            $filters = $domapi['filters'] ?? [];
            $view->vars['domapi'] = [
                'entity' => $entity,
                'view' => $action,
                'item' => $domapi['item'],
                'field' => null,
                'form_group' => $form->getConfig()->getAttribute('domapi_form_group'),
                'form_tab' => $form->getConfig()->getAttribute('domapi_form_tab'),
                'filters' => $filters,
            ];

            /*
             * Checks if current form view is direct child on the topmost form
             * (ie. this form view`s field exists in domapi configuration)
             */
            if (null !== $view->parent && null === $view->parent->parent) {
                $view->vars['domapi']['field'] = $fields[$view->vars['name']] ?? null;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes()
    {
        return [FormType::class];
    }

    /**
     * This legacy method can be removed when the minimum supported version is Symfony 4.2.
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
