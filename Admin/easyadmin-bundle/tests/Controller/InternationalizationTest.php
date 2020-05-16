<?php

namespace EasyCorp\Bundle\DomAdminBundle\Tests\Controller;

use EasyCorp\Bundle\DomAdminBundle\Tests\Fixtures\AbstractTestCase;

class InternationalizationTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'internationalization'];

    public function testLanguageDefinedByLayout()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame('fr', \trim($crawler->filter('html')->attr('lang')));
    }
}
