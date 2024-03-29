<?php

namespace DomBase\DomAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class AdminConfigPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configPasses = $this->findAndSortTaggedServices('domadmin.config_pass', $container);
        $definition = $container->getDefinition('domadmin.config.manager');

        foreach ($configPasses as $service) {
            $definition->addMethodCall('addConfigPass', [$service]);
        }
    }
}
