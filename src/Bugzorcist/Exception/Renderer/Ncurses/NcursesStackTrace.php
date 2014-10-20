<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer\Ncurses;

class NcursesStackTrace extends NcursesAbstract
{
    /**
     * Stack trace
     * @var array
     */
    private $stackTrace;

    /**
     * Constructor
     * @param array $stackTrace stack trace
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct(array $stackTrace, $padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->stackTrace = $stackTrace;
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $stackTrace = $this->stackTrace;

        if (empty($stackTrace)) {
            $stackTrace[] = array(
                "function" => "{main}"
            );
        }

        $num        = count($stackTrace) - 1;
        $numLength  = strlen($num);

        foreach ($stackTrace as $event) {
            $funcName   = array_key_exists("class", $event) ? "{$event['class']}{$event['type']}{$event['function']}" : "{$event['function']}";
            $file       = array_key_exists("file", $event)  ? $event['file'] : "-";
            $line       = array_key_exists("line", $event)  ? $event['line'] : "-";
            $args       = array_key_exists("args", $event)  ? $event['args'] : array();
            $argTypes   = array();

            foreach ($args as $k => $arg) {
                $argTypes[] = "object" == gettype($arg) ? get_class($arg) : gettype($arg);
            }

            $argTypes   = implode(", ", $argTypes);
            $numString  = str_pad($num, $numLength, "0", STR_PAD_LEFT);
            $funcName   = "{main}" == $funcName ? "<<1>>$funcName" : "<<1>>$funcName(<<3>>$argTypes<<1>>)";
            $render     = "<<0>>#$numString $funcName\n";
            $render    .= "    <<2>>File :<<0>> $file\n";
            $render    .= "    <<2>>Line :<<0>> $line\n";
            $render    .= "\n";
            $this->printText($render);
            $num--;
        }
    }
}
