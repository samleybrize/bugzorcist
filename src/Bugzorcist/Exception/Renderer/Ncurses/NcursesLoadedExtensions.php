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
 * Ncurses loaded extensions viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesLoadedExtensions extends NcursesAbstract
{
    /**
     * List of loaded extensions
     * @var array
     */
    private $loadedExtensionList;

    /**
     * Constructor
     * @param array $loadedExtensionList list of loaded extensions
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct(array $loadedExtensionList, $padPositionX, $padPositionY)
    {
        $this->loadedExtensionList = $loadedExtensionList;
        natcasesort($this->loadedExtensionList);
        parent::__construct($padPositionX, $padPositionY);
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $count  = count($this->loadedExtensionList);
        $i      = 1;
        $digits = strlen($count);
        $this->printText("$count extensions\n\n");

        foreach ($this->loadedExtensionList as $extension) {
            $index = str_pad($i, $digits, " ", STR_PAD_LEFT);
            $this->printText("  <<3>>$index  <<0>>$extension\n");
            $i++;
        }
    }
}
