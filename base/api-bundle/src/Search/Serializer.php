<?php

namespace DomBase\DomApiBundle\Search;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class Serializer
{
    private $serializer;

    public function __construct()
    {

    }

    /**
     */
    public function serialize($entity)
    {
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getName();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $normalizer->setCircularReferenceLimit(0);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $serializer->serialize($entity, 'json');
        dump($serializer->serialize($entity, 'json'));exit;
        return $this->serializer->toArray($entity);
    }
}
