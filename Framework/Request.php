<?php

namespace Vertex\Framework;

/**
 * Class Request
 * @package Vertex\Framework
 * @property Application app
 */
class Request
{

    /**
     * @var Application
     */
    private $app;

    private $flashbag = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        if (!file_exists(APP_ROOT . '/App/Controllers/' . $this->app->getControllerName() . 'Controller.php')) {
            $ctl = $this->getLinkedController();
            if ($ctl === NULL)
                $this->app->raise(404, "The controller '" . $this->app->getControllerName() . "' does not exists !");
            return $ctl;
        }

        $name = 'App\\Controllers\\' . $this->app->getControllerName() . 'Controller';
        $ctl = new $name($this->app->getControllerName(), $this);
        return $ctl;
    }

    private function getLinkedController()
    {
        if (!$this->app->hasConfig('controllers'))
            return NULL;
        $ctls = $this->app->getConfig('controllers');
        if (!array_key_exists(strtolower($this->app->getControllerName()), $ctls))
            return NULL;

        $class = $ctls[strtolower($this->app->getControllerName())];
        $ctl = new $class(strtolower($this->app->getControllerName()), $this);
        return $ctl;
    }

    /**
     * @return String
     */
    public function getResponse()
    {
        $ctl = $this->getController();
        $action = strtolower($this->app->getActionName());
        if (!method_exists($ctl, $action))
            $this->app->raise(404, "The action '" . $this->app->getActionName() . "' of controller '" . $this->app->getControllerName() . "' does not exists !");
        $ctl->request = $this;
        return $ctl->$action();
    }

    public function __get($name)
    {
        if ($name == 'app')
            return $this->getApplication();
        if (Input::has($name))
            return Input::get($name);
        return null;
    }
}