<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer;

/**
 * Cli exception renderer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ExceptionRendererCli extends ExceptionRendererAbstract
{
    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $exception = $this->getException();

        echo get_class($exception) . "\n";
        echo $exception->getMessage() . "\n";
        echo "File : " . $exception->getFile() . "\n";
        echo "Line : " . $exception->getLine() . "\n";
        echo "Code : " . $exception->getCode() . "\n";
        echo $exception->getTraceAsString() . "\n";
    }
}
