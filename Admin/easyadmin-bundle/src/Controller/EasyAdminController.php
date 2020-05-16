<?php

namespace EasyCorp\Bundle\DomAdminBundle\Controller;

use EasyCorp\Bundle\DomAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\DomAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\DomAdminBundle\Search\Autocomplete;
use EasyCorp\Bundle\DomAdminBundle\Search\Paginator;
use EasyCorp\Bundle\DomAdminBundle\Search\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminController extends AbstractController
{
    use AdminControllerTrait;

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'easyadmin.autocomplete' => Autocomplete::class,
            'easyadmin.config.manager' => ConfigManager::class,
            'easyadmin.paginator' => Paginator::class,
            'easyadmin.query_builder' => QueryBuilder::class,
            'easyadmin.property_accessor' => PropertyAccessorInterface::class,
            'easyadmin.filter.registry' => FilterRegistry::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }
}
