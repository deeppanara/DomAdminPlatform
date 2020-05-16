<?php

namespace DomBase\DomAdminBundle\Form\Extension;

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

        if ($request->attributes->has('domadmin')) {
            $domadmin = $request->attributes->get('domadmin');
            $entity = $domadmin['entity'];
            $action = $domadmin['view'];
            $fields = $entity[$action]['fields'] ?? [];
            $filters = $domadmin['filters'] ?? [];
            $view->vars['domadmin'] = [
                'entity' => $entity,
                'view' => $action,
                'item' => $domadmin['item'],
                'field' => null,
                'form_group' => $form->getConfig()->getAttribute('domadmin_form_group'),
                'form_tab' => $form->getConfig()->getAttribute('domadmin_form_tab'),
                'filters' => $filters,
            ];

            /*
             * Checks if current form view is direct child on the topmost form
             * (ie. this form view`s field exists in domadmin configuration)
             */
            if (null !== $view->parent && null === $view->parent->parent) {
                $view->vars['domadmin']['field'] = $fields[$view->vars['name']] ?? null;
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
