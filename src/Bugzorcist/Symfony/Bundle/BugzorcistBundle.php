<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Symfony\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Bugzorcist\Symfony\Bundle\DependencyInjection\Compiler\DataSourceCompilerPass;

/**
 * Symfony Bundle
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class BugzorcistBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DataSourceCompilerPass());
    }
}
