<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataSourceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition("bugzorcist.data_profiler_manager")) {
            return;
        }

        $definition     = $container->getDefinition("bugzorcist.data_profiler_manager");
        $taggedServices = $container->findTaggedServiceIds("bugzorcist.data-profiler");

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall("addProfiler", array(new Reference($id)));
        }
    }
}
