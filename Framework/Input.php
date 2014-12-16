<?php

namespace Vertex\Vertex\Framework;

/**
 * Class Input
 * @package Vertex\Vertex\Framework
 */
class Input {

	private static $stored = [];

    /**
     *
     * Check if an input is passed
     *
     * @param $name String Name of the input
     * @return bool true if the input exists
     */
    public static function has($name) {
		return array_key_exists($name, $_REQUEST) || array_key_exists($name, static::$stored);
	}

    /**
     *
     * Return the value of the input, or call the fallback
     *
     * @param $name String Name of the input
     * @param string $fallback Fallback called if the input doesn't exists
     * @return string the requested input
     */
    public static function get($name, $fallback = '') {
		if (array_key_exists($name, $_REQUEST))
			return $_REQUEST[$name];
		elseif (array_key_exists($name, static::$stored))
			return static::$stored[$name];
		else
			return $fallback;

	}


    /**
     *
     * Push a variable in the input for the current request
     *
     * @param $name Name of the variable
     * @param $value Value to store
     */
    public static function push($name, $value) {
		static::$stored[$name] = $value;
	}
}