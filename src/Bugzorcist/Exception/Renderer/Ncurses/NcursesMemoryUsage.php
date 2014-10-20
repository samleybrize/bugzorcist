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

use Bugzorcist\Utils\UnitFormatter;

class NcursesMemoryUsage extends NcursesAbstract
{
    /**
     * Memory used
     * @var string
     */
    private $memoryUsage;

    /**
     * Memory peak
     * @var string
     */
    private $memoryPeak;

    /**
     * Memory limit
     * @var string
     */
    private $memoryLimit;

    /**
     * Constructor
     * @param int $memoryUsage memory used
     * @param int $memoryPeakUsage memory peak
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($memoryUsage, $memoryPeakUsage, $padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);

        $this->memoryUsage  = UnitFormatter::formatByte($memoryUsage);
        $this->memoryPeak   = UnitFormatter::formatByte($memoryPeakUsage);
        $this->memoryLimit  = ini_get("memory_limit");

        if (-1 == $this->memoryLimit) {
            $this->memoryLimit = "-1 (no limit)";
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $this->printText("<<3>>Memory used   : <<0>>$this->memoryUsage\n");
        $this->printText("<<3>>Memory peak   : <<0>>$this->memoryPeak\n");
        $this->printText("<<3>>Memory limit  : <<0>>$this->memoryLimit\n");
    }
}
