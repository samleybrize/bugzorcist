<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Render\CliNcurses;

class NcursesDescription extends NcursesAbstract
{
    /**
     * @var \Exception
     */
    private $exception;

    /**
     * Constructor
     * @param \Exception $exception
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct(\Exception $exception, $padPositionX, $padPositionY)
    {
        $this->exception = $exception;
        parent::__construct($padPositionX, $padPositionY);
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        global $argv;

        $message    = $this->exception->getMessage();
        $file       = $this->exception->getFile();
        $line       = $this->exception->getLine();
        $code       = $this->exception->getCode();
        $command    = implode(" ", $argv);
        $this->printText("<<6>>$message\n\n");
        $this->printText("<<3>>File    : <<0>>$file\n");
        $this->printText("<<3>>Line    : <<0>>$line\n");
        $this->printText("<<3>>Code    : <<0>>$code\n");
        $this->printText("<<3>>Command : <<0>>$command\n");
    }
}
