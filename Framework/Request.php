<?php

namespace Vertex\Framework;

/**
 * Class Request
 * @package Vertex\Framework
 * @property Application app
 */
class Request {

    /**
     * @var Application
     */
	private $app;

	private $flashbag = [];

	public function __construct($app) {
		$this->app = $app;
	}

    /**
     * @return Application
     */
	public function getApplication() {
		return $this->app;
	}

    /**
     * @return Controller
     */
	public function getController() {
		if (!file_exists(APP_ROOT.'/App/Controllers/'.$this->app->getControllerName().'Controller.php')) {
			$this->app->raise(404, 	"The controller '".$this->app->getControllerName()."' does not exists !");
		}

		$name = 'App\\Controllers\\'.$this->app->getControllerName().'Controller';
		$ctl = new $name($this);
		return $ctl;
	}

    /**
     * @return String
     */
	public function getResponse() {
		$ctl = $this->getController();
		$action = strtolower($this->app->getActionName());
		if (!method_exists($ctl, $action))
			exit("The action '".$this->app->getActionName()."' of controller '".$this->app->getControllerName()."' does not exists !");
		$ctl->request = $this;
		return $ctl->$action();
	}

	public function __get($name) {
		if ($name == 'app')
			return $this->getApplication();
		if (Input::has($name))
			return Input::get($name);
		return null;
	}
}