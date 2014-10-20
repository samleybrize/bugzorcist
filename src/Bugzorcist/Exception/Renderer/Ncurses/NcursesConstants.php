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

class NcursesConstants extends NcursesVerticalCursorAbstract
{
    /**
     * Defined constant list by category
     * @var array
     */
    private $constants;

    /**
     * Constructor
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->constants = get_defined_constants(true);
        unset($this->constants["user"]);

        foreach ($this->constants as $name => $values) {
            $this->addExpandableElement($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        foreach ($this->constants as $cat => $values) {
            // retrieve current position
            $x = null;
            $y = null;
            ncurses_getyx($this->getPadResource(), $y, $x);

            // category name
            ncurses_wattron($this->getPadResource(), NCURSES_A_BOLD);
            $catText = $this->isElementExpanded($cat) ? "$cat ▾" : "$cat ▸";
            $this->printText("<<4>>$catText\n");
            ncurses_wattroff($this->getPadResource(), NCURSES_A_BOLD);

            $this->setExpandableElementPositionY($cat, $y);

            if (!$this->isElementExpanded($cat)) {
                continue;
            }

            // category constants
            if (!empty($values)) {
                $maxLength = 0;

                foreach ($values as $name => $value) {
                    $maxLength = max($maxLength, strlen($name));
                }

                foreach ($values as $name => $value) {
                    $name = str_pad($name, $maxLength, " ", STR_PAD_RIGHT);

                    switch (gettype($value)) {
                        case "string":
                            $value  = str_replace(
                                array("\n", "\r", "\t"),
                                array("\\n", "\\r", "\\t"),
                                $value
                            );
                            break;

                        case "bool":
                        case "boolean":
                            $value = $value ? "true" : "false";
                            $value = "<<2>>$value";
                            break;

                        case "null":
                        case "NULL":
                            $value = "<<2>>null";
                            break;
                    }

                    $this->printText("    <<3>>$name : <<0>>$value\n");
                }
            } else {
                // no constants
                $this->_firePhp->log("* empty *");
            }
        }
    }
}
