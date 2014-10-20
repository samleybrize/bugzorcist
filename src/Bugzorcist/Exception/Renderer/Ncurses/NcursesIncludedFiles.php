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

class NcursesIncludedFiles extends NcursesAbstract
{
    /**
     * List of included files
     * @var array
     */
    private $includedFileList;

    /**
     * Constructor
     * @param array $includedFileList list of included files
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct(array $includedFileList, $padPositionX, $padPositionY)
    {
        $this->includedFileList = $includedFileList;
        parent::__construct($padPositionX, $padPositionY);
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $count  = count($this->includedFileList);
        $i      = 1;
        $digits = strlen($count);
        $this->printText("$count files\n\n");

        foreach ($this->includedFileList as $includedFile) {
            $index = str_pad($i, $digits, " ", STR_PAD_LEFT);
            $this->printText("  <<3>>$index  <<0>>$includedFile\n");
            $i++;
        }
    }
}
