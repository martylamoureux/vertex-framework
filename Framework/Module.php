<?php

namespace Vertex\Framework;

class Module {

    /**
     * @var Application
     */
	protected static $app;

	public function __construct($app) {
		static::$app = $app;
	}
}