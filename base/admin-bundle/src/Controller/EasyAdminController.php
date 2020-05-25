<?php

namespace DomBase\DomAdminBundle\Controller;

use DomBase\DomAdminBundle\Configuration\ConfigManager;
use DomBase\DomAdminBundle\Form\Filter\FilterRegistry;
use DomBase\DomAdminBundle\Search\Autocomplete;
use DomBase\DomAdminBundle\Search\Exporter;
use DomBase\DomAdminBundle\Search\Paginator;
use DomBase\DomAdminBundle\Search\QueryBuilder;
use DomBase\DomAdminBundle\Search\Translator;
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
            'domadmin.autocomplete' => Autocomplete::class,
            'domadmin.config.manager' => ConfigManager::class,
            'domadmin.paginator' => Paginator::class,
            'domadmin.query_builder' => QueryBuilder::class,
            'domadmin.property_accessor' => PropertyAccessorInterface::class,
            'domadmin.filter.registry' => FilterRegistry::class,
            'domadmin.export_service' => Exporter::class,
            'domadmin.translator' => Translator::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }
}
