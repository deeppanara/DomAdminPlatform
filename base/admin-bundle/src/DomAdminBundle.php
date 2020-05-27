<?php

namespace DomBase\DomAdminBundle;

use DomBase\DomAdminBundle\DependencyInjection\Compiler\AdminConfigPass;
use DomBase\DomAdminBundle\DependencyInjection\Compiler\AdminFormTypePass;
use DomBase\DomAdminBundle\DependencyInjection\Compiler\FilterTypePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class DomAdminBundle extends Bundle
{
    public const VERSION = '2.2.0';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdminFormTypePass(), PassConfig::TYPE_BEFORE_REMOVING);
        // this compiler pass must run earlier than FormPass to clear
        // the 'form.type_guesser' tag for 'domadmin.filter.type_guesser' services
        $container->addCompilerPass(new FilterTypePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $container->addCompilerPass(new AdminConfigPass());
    }
}
