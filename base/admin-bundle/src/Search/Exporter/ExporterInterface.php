<?php
namespace DomBase\DomAdminBundle\Search\Exporter;

use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface{

    public function generateResponse(array $data, string $filename): Response;
    public function getFormat():string;

}