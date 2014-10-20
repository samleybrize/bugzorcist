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

            // array
            case "null":
            case "NULL":
                $level["value"] = null;
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
                $reflection             = new \ReflectionObject($var);
                $propertyList           = $reflection->getProperties();
                $level["class"]         = get_class($var);
                $level["count"]         = count($propertyList);
                $level["properties"]    = array();
                $hasStaticProperty      = false;

                foreach ($propertyList as $property) {
                    $property->setAccessible(true);
                    $level["properties"][] = array(
                        "static"    => $property->isStatic(),
                        "access"    => $property->isPrivate() ? "private" : $property->isProtected() ? "protected" : "public",
                        "name"      => $property->getName(),
                        "value"     => $this->makeTree($property->getValue($var)),
                    );

                    // whether this object has static property or not
                    if (!$hasStaticProperty) {
                        $hasStaticProperty = $property->isStatic();
                    }
                }

                // if this object has a static property, properties are sorted (static properties first)
                if ($hasStaticProperty) {
                    usort($level["properties"], array($this, "sortClassProperties"));
                }
                break;

            // unknown type
            default:
                throw new \UnexpectedValueException("Unknown var type '{$level["type"]}'");
        }

        return $level;
    }

    /**
     * Callback function for usort() to sort class properties
     * @param array $property1
     * @param array $property2
     * @return number
     */
    protected function sortClassProperties(array $property1, array $property2)
    {
        return $property1["static"] && !$property2["static"] ? -1 : 1;
    }
}
