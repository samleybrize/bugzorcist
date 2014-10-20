<?php

namespace Bugzorcist\Exception\Renderer;

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
