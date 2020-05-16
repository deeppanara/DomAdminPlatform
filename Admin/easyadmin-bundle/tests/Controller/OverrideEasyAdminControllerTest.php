<?php

namespace EasyCorp\Bundle\DomAdminBundle\Tests\Controller;

use EasyCorp\Bundle\DomAdminBundle\Tests\Fixtures\AbstractTestCase;

class OverrideEasyAdminControllerTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'override_controller'];

    public function testLayoutIsOverridden()
    {
        $crawler = static::$client->request('GET', '/override_layout');

        $this->assertSame(200, static::$client->getResponse()->getStatusCode());
        $this->assertSame('Layout is overridden.', \trim($crawler->filter('#main')->text()));
    }
}
