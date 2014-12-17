<?php

namespace Vertex\Framework;

use Vertex\Framework\Modeling\Repository;

/**
 * Class Database
 * @package Vertex\Framework
 */
class Database {

    /**
     * @var \PDO
     */
	private $pdo;
    /**
     * @var Application
     */
	private $app;
	private $trace = [];

    /**
     * @param $app Application
     */
	public function __construct($app) {
		$this->app = $app;
		if ($app->getConfig('database', 'driver') == 'sqlsrv')
			$connectionStr = 'sqlsrv:Server='.$app->getConfig('database', 'host').';Database='.$app->getConfig('database', 'dbname');
		else
			$connectionStr = $app->getConfig('database', 'driver').':host='.$app->getConfig('database', 'host').';dbname='.$app->getConfig('database', 'dbname');
		$this->pdo = new \PDO($connectionStr, $app->getConfig('database', 'username'), $app->getConfig('database', 'password'));
	}

	public function repository($name) {
		$repo = new Repository($name);
		return $repo;
	}

	public function execute($statement, $params = []) {
		$res = [];

		if ($this->app->getConfig('enable_db_caching', true) && array_key_exists($statement, $this->trace))
			return $this->trace[$statement]['res'];
		$query = $this->pdo->prepare($statement);

		foreach ($params as $name => $value) {
			$query->bindParam(':'.$name, $params[$name]);
		}

		if ($query->execute()) {
			$res = $query->fetchAll();
			foreach ($res as $i=>$row) {
				foreach ($row as $key=>$val) {
					if (is_numeric($key))
						unset($res[$i][$key]);
				}
			}
		}
		$this->trace[$statement] = compact('params', 'res');
		return $res;
	}

	public function success($query, $params = []) {
		$query = $this->pdo->prepare($query);

		foreach ($params as $name => $value) {
			$query->bindParam(':'.$name, $params[$name]);
		}

		return $query->execute();
	}

	public function first($query, $params = []) {
		$res = $this->execute($query, $params);
		return count($res) > 0 ? $res[0] : NULL;
	}

	public function count($query, $params = []) {
		return intval($this->first($query, $params)["COUNT(*)"]);
	}

	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}

    public function getSchema() {
        $rawTables = $this->execute("show tables");
        $schema = [];
        foreach ($rawTables as $key=>$t) {
            $table = array_values($t)[0];
            $structure = $this->execute("describe ".$table);
            $schema[$table] = $structure;
        }

        var_dump($schema);
    }

	public function getTrace() {
		return $this->trace;
	}

	public function close() {
		$this->pdo = null;
	}

	public function __destruct() {
		$this->close();
	}

}