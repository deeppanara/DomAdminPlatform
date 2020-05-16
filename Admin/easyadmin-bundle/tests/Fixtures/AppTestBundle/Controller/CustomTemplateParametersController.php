<?php

namespace EasyCorp\Bundle\DomAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use EasyCorp\Bundle\DomAdminBundle\Controller\EasyAdminController;

class CustomTemplateParametersController extends EasyAdminController
{
    protected function renderTemplate($actionName, $templatePath, array $parameters = [])
    {
        $parameters['custom_parameter'] = $actionName;

        return parent::renderTemplate($actionName, $templatePath, $parameters);
    }
}
