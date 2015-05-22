<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\DataProfiler\ProfilerProxy\PDO;

use Bugzorcist\Profiler\DataProfiler\DataProfiler;

/**
 * Extends PDOStatement to add profiling capabilities
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class PDOProfilerStatement extends \PDOStatement
{
    /**
     * Binded parameters
     * @var array
     */
    private $binds = array();

    /**
     * Data profiler
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfiler
     */
    private $profiler;

    /**
     * PDOStatement object to profile
     * @var \PDOStatement
     */
    private $statement;

    /**
     * Constructor
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfiler $profiler data profiler
     */
    public function __construct(\PDOStatement $statement, DataProfiler $profiler)
    {
        $this->profiler     = $profiler;
        $this->statement    = $statement;
    }

    /**
     * Call the corresponding PDOStatement method
     * @param string $name method name
     * @param array $args args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->statement, $name), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        $this->binds[$parameter] = $value;
        return $this->statement->bindValue($parameter, $value, $data_type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        $this->binds[$parameter] = &$variable;
        return $this->statement->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input_parameters = null)
    {
        $parameters = (null === $input_parameters) ? $this->binds : $input_parameters;

        $this->profiler->startQuery($this->statement->queryString, $this->binds);
        $result = $this->statement->execute($input_parameters);
        $this->profiler->stopQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        return $this->statement->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return $this->statement->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function debugDumpParams()
    {
        return $this->statement->debugDumpParams();
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->statement->errorCode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->statement->errorInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($how = null, $orientation = null, $offset = null)
    {
        return $this->__call("fetch", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($how = null, $class_name = null, $ctor_args = null)
    {
        return $this->__call("fetchAll", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($column_number = null)
    {
        return $this->__call("fetchColumn", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObject($class_name = null, $ctor_args = null)
    {
        return $this->__call("fetchObject", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute)
    {
        return $this->__call("getAttribute", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnMeta($column)
    {
        return $this->__call("getColumnMeta", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function nextRowset()
    {
        return $this->statement->nextRowset();
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($attribute, $value)
    {
        return $this->__call("setAttribute", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($mode, $params = null)
    {
        return $this->__call("setFetchMode", func_get_args());
    }
}
