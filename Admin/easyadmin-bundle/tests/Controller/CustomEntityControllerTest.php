<?php

namespace EasyCorp\Bundle\DomAdminBundle\Tests\Controller;

use EasyCorp\Bundle\DomAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomEntityControllerTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'custom_entity_controller'];

    public function testListAction()
    {
        $this->requestListView();
        $this->assertContains('Overridden list action.', static::$client->getResponse()->getContent());
    }

    public function testShowAction()
    {
        $this->requestShowView();
        $this->assertContains('Overridden show action.', static::$client->getResponse()->getContent());
    }
}
