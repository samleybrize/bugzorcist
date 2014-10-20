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

/**
 * Ncurses execution time viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesExecutionTime extends NcursesAbstract
{
    /**
     * Execution time
     * @var float
     */
    private $executionTime;

    /**
     * Constructor
     * @param float $executionTime execution time
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($executionTime, $padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->executionTime = (float) $executionTime;
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $this->printText("$this->executionTime s");
    }
}
