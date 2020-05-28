<?php

namespace DomBase\DomApiBundle\Controller;

use DomBase\DomApiBundle\Configuration\ConfigManager;
use DomBase\DomApiBundle\Form\Filter\FilterRegistry;
use DomBase\DomApiBundle\Search\Autocomplete;
use DomBase\DomApiBundle\Search\Exporter;
use DomBase\DomApiBundle\Search\Paginator;
use DomBase\DomApiBundle\Search\QueryBuilder;
use DomBase\DomApiBundle\Search\Serializer;
use DomBase\DomApiBundle\Search\Translator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DomApiController extends AbstractController
{
    use ApiControllerTrait;

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'domapi.autocomplete' => Autocomplete::class,
            'domapi.config.manager' => ConfigManager::class,
            'domapi.paginator' => Paginator::class,
            'domapi.query_builder' => QueryBuilder::class,
            'domapi.property_accessor' => PropertyAccessorInterface::class,
            'domapi.filter.registry' => FilterRegistry::class,
            'domapi.export_service' => Exporter::class,
            'domapi.translator' => Translator::class,
            'domapi.serializer' => Serializer::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }
}
