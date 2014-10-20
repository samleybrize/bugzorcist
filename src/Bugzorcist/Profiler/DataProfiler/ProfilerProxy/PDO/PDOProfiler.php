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

class PDOProfiler extends \PDO
{
    /**
     * Data profiler
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfiler
     */
    private $profiler;

    /**
     * PDO object to profile
     * @var \PDO
     */
    private $pdo;

    /**
     * Constructor
     * @param \PDO $pdo PDO object to profile
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfiler $profiler data profiler
     */
    public function __construct(\PDO $pdo, DataProfiler $profiler)
    {
        $this->profiler = $profiler;
        $this->pdo      = $pdo;
    }

    /**
     * Call the corresponding PDO method
     * @param string $name method name
     * @param array $args args
     * @return mixed
     */
    public function __call($name, $args)
    {
        $result = call_user_func_array(array($this->pdo, $name), $args);

        if ($result instanceof \PDOStatement) {
            $result = new PDOProfilerStatement($result, $this->profiler);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->profiler->startQuery("START TRANSACTION;");
        $result = $this->pdo->beginTransaction();
        $this->profiler->stopQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->profiler->startQuery("COMMIT;");
        $result = $this->pdo->commit();
        $this->profiler->stopQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        $this->profiler->startQuery("ROLLBACK;");
        $result = $this->pdo->rollBack();
        $this->profiler->stopQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        $this->profiler->startQuery($statement);
        $result = $this->pdo->exec($statement);
        $this->profiler->stopQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function query($statement)
    {
        $this->profiler->startQuery($statement);
        $statement = $this->__call("query", func_get_args());
        $this->profiler->stopQuery();

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        return $this->__call("prepare", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute()
    {
        return $this->__call("getAttribute", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId()
    {
        return $this->__call("lastInsertId", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function quote()
    {
        return $this->__call("quote", func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute()
    {
        return $this->__call("setAttribute", func_get_args());
    }
}
