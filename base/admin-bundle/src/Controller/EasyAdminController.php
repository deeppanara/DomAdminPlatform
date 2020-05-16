<?php

namespace DomBase\DomAdminBundle\Controller;

use DomBase\DomAdminBundle\Configuration\ConfigManager;
use DomBase\DomAdminBundle\Form\Filter\FilterRegistry;
use DomBase\DomAdminBundle\Search\Autocomplete;
use DomBase\DomAdminBundle\Search\Paginator;
use DomBase\DomAdminBundle\Search\QueryBuilder;
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
