<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump;

/**
 * Construct the tree representation of a var
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class VarTree
{
    /**
     * Var tree
     * @var array
     */
    private $tree;

    /**
     * Temp var used to hold references to object instances
     * @var array
     */
    private $objectList = array();

    /**
     * Constructor
     * @param mixed $var var for which to build a tree
     */
    public function __construct($var)
    {
        $this->tree         = $this->makeTree($var);
        $this->objectList   = array();
    }

    /**
     * Returns the var tree
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Buils the var tree
     * @param mixed $var var for which to build a tree
     * @return array
     */
    protected function makeTree($var)
    {
        $level          = array();
        $level["type"]  = gettype($var);
        $level["uid"]   = uniqid("", true);

        switch ($level["type"]) {
            // string
            case "string":
                $level["length"]    = strlen($var);
                $level["value"]     = $var;
                break;

            // number
            case "integer":
            case "long":
            case "float":
            case "double":
                $level["value"] = $var;
                break;

            // boolean
            case "bool":
            case "boolean":
                $level["value"] = $var ? "true" : "false";
                break;

            // null
            case "null":
            case "NULL":
                $level["value"] = null;
                break;

            // resource
            case "resource":
                $level["value"] = (string) $var;
                break;

            // array
            case "array":
                $level["count"]     = count($var);
                $level["children"]  = array();

                foreach ($var as $k => $v) {
                    $level["children"][$k] = $this->makeTree($v);
                }
                break;

            // object
            case "object":
                // each instance cannot be processed twice
                $level["id"]            = spl_object_hash($var);

                if (in_array($var, $this->objectList, true)) {
                    return $level;
                }

                $this->objectList[]     = $var;
                $level["class"]         = get_class($var);
                $level["count"]         = 0;
                $level["properties"]    = array();
                $propertyHashList       = array();
                $isParentClass          = false;
                $reflection             = new \ReflectionObject($var);
                $classname              = $reflection->getName();

                do {
                    $propertyList = $reflection->getProperties();
                    usort($propertyList, array($this, "sortClassProperties"));

                    foreach ($propertyList as $property) {
                        // avoid getting duplicate properties
                        $hash = ((int) $property->isStatic()) . "#$classname#" . $property->getName();

                        if (in_array($hash, $propertyHashList)) {
                            continue;
                        }

                        $propertyHashList[] = $hash;

                        // add property definition
                        $property->setAccessible(true);
                        $level["count"]++;
                        $level["properties"][] = array(
                            "static"    => $property->isStatic(),
                            "access"    => $property->isPrivate() ? "private" : ($property->isProtected() ? "protected" : "public"),
                            "name"      => $property->getName(),
                            "class"     => $isParentClass ? $reflection->getName() : null,
                            "value"     => $this->makeTree($property->getValue($var)),
                        );
                    }

                    $isParentClass = true;
                } while ($reflection = $reflection->getParentClass());
                break;

            // unknown type
            default:
                throw new \UnexpectedValueException("Unknown var type '{$level["type"]}'");
        }

        return $level;
    }

    /**
     * Callback function for usort() to sort class properties
     * @param \ReflectionProperty $property1
     * @param \ReflectionProperty $property2
     * @return number
     */
    protected function sortClassProperties(\ReflectionProperty $property1, \ReflectionProperty $property2)
    {
        return $property1->isStatic() && !$property2->isStatic() ? -1 : 1;
    }
}
