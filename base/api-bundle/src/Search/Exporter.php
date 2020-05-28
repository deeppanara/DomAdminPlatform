<?php

namespace DomBase\DomApiBundle\Search;

use DomBase\DomApiBundle\Configuration\ConfigManager;
use DomBase\DomApiBundle\Search\Exporter\ExporterInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class Exporter
{

    /** @var PropertyAccessor */
    private $propertyAccessor;
    private $configManager;
    private $translator;
    private $exporters = [];


    public function __construct(
        ConfigManager $configManager,
        PropertyAccessor $propertyAccessor,
        TranslatorInterface $translator,
        iterable $exporters)
    {
        $this->configManager = $configManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->translator = $translator;
        foreach($exporters as $exporter){
            if($exporter instanceof ExporterInterface) {
                if (array_key_exists($exporter->getFormat(), $this->exporters)) {
                    throw new \Exception('The exporter format ' . $exporter->getFormat() . ' already exist');
                }
                $this->exporters[$exporter->getFormat()] = $exporter;
                $this->exporters[get_class($exporter)] = $exporter;
            }
        }
    }

    private function getExportableValue($entity, array $field): ?string{
        try {
            $value = $this->propertyAccessor->getValue($entity, $field['property']);
            if ($value instanceOf \DateTime){
                return $value->format($field['format']);
            } elseif (is_array($value)){
                return implode(',', $value);
            } elseif (is_object($value)){
                return (string)$value;
            }
            return $value;
        } catch(\Exception $e) {
            return "";
        }
    }

    private function generateData(iterable $paginator, array $fields): array{
        $data= [];
        $keys = array_keys($fields);
        for ($i = 0, $count = count($keys); $i < $count; $i++) {
            $data[0][$i] = $this->translator->trans($fields[$keys[$i]]['label'] ?? $keys[$i]);
        }
        $i = 1;
        foreach ($paginator as $entity) {
            foreach ($fields as $field) {
                $data[$i][] = $this->getExportableValue($entity, $field);
            }
            $i++;
        }
        return $data;
    }

    public function getExporter($format): ExporterInterface
    {
        if(array_key_exists($format, $this->exporters)){
            return $this->exporters[$format];
        }else{
            throw new \Exception('The format '. $format .' is not found');
        }
    }

    public function generateResponse(iterable $paginator, array $fields, string $filename,  string $format = 'csv'): Response{
        $data = $this->generateData($paginator, $fields);
        return $this->getExporter($format)->generateResponse($data, $filename);
    }


}
