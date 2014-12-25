<?php 

namespace Vertex\Framework;

/**
 * Class Application
 * @package Vertex\Framework
 */
class Application {

	private $config = [];
	private $envs = [];
	private $aliases = [];
	private $modules = [];
	private $loadedModules = [];

    private $commands = [];

    public $uri = "";
    private $controllerName = "";
    private $actionName = "";
    private $uriParams = [];

    public $twig;
    public $frameworkTwig;

    public $whoops;

    /**
     * @var Database
     */
	public $db;

	public function __construct() {
		$this->loadConfig();
        $this->loadUri();
		$this->loadDatabase();
        $this->loadTwig();
        $this->loadWhoops();
        $this->loadCommands();
	}

	private function loadConfig() {
		$this->config = require APP_ROOT."/config/config.php";
		$this->envs = require APP_ROOT."/config/envs.php";

		if (file_exists(APP_ROOT."/config/".$this->currentEnvironment().".php")) {
			$envConfig = require APP_ROOT."/config/".$this->currentEnvironment().".php";
			$this->config = array_merge_recursive($this->config, $envConfig);
		}
	}

    private function loadDatabase() {
		if ($this->hasConfig('database')) {
			$this->db = new Database($this);
		} else {
			$this->db = null;
		}
	}

    public function loadUri() {
        if ($this->isCLI())
            return;
        $this->uri = array_key_exists('uri', $_GET) ? $_GET['uri'] : '';
        $this->uri = rtrim($this->uri, '/');

        // Controller
        if ($this->uri == '') {
            $this->controllerName = $this->getConfig('default_controller');
            $this->actionName = $this->getConfig('default_action');
            return;
        } else {
            $uri = $this->uri;
            $urlArray = explode("/", $uri);

            $controller = $urlArray[0];
            $this->controllerName = ucwords($controller);
        }

        array_shift($urlArray);

        if (count($urlArray) == 0)
            $this->actionName = $this->getConfig('default_action');
        else {
            $this->actionName = $urlArray[0];
            array_shift($urlArray);
        }

        while (count($urlArray) > 0) {
            $this->uriParams[] = $urlArray[0];
            array_shift($urlArray);
        }
    }

    public function loadTwig() {

        $loader = new \Twig_Loader_Filesystem(APP_ROOT . DS . 'App'. DS . 'Views');
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => false,
        ));

        $this->twig->addFunction(new \Twig_SimpleFunction('asset', function ($path) {
            return "http://".$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']))."/".$path;
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('path', function ($controller, $action, $params=[]) {
            return static::generateUrl($controller, $action, $params);
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('app', function () {
            return $this;
        }));

        $this->twig->addGlobal('app', $this);

        $this->twig->addFilter(new \Twig_SimpleFilter('dump', function ($var) {
            return var_dump($var);
        }));

        $loader = new \Twig_Loader_Filesystem(realpath(dirname(dirname(__FILE__))) . DS . 'Resources' . DS . 'twig');
        $this->frameworkTwig = new \Twig_Environment($loader, array(
            'cache' => false,
        ));

        $this->frameworkTwig->addFunction(new \Twig_SimpleFunction('path', function ($controller, $action, $params=[]) {
            return static::generateUrl($controller, $action, $params);
        }));

        $this->frameworkTwig->addGlobal('app', $this);
    }

    private function loadWhoops()
    {
        $this->whoops = new \Whoops\Run();
        $this->whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        $this->whoops->register();
    }

    private function loadCommands()
    {
        $this->registerCommand('\\Vertex\\Framework\\Commands\\DatabaseCurrent');
        $this->registerCommand('\\Vertex\\Framework\\Commands\\DatabaseUpdate');
        if ($this->hasConfig('commands')) {
            $cmds = $this->getConfig('commands');
            foreach ($cmds as $cmdClass)
                $this->registerCommand($cmdClass);
        }
    }

	public function __get($attr) {
		foreach ($this->modules as $name => $class) {
			if ($name == $attr) {
				if (!array_key_exists($attr, $this->loadedModules))
					$this->loadedModules[$attr] = new $class($this);
				return $this->loadedModules[$attr];
			}
		}
	}

	public function hasConfig($name, $sub = '') {
		if ($sub == '')
			return array_key_exists($name, $this->config);
		else
			return array_key_exists($name, $this->config) && array_key_exists($sub, $this->config[$name]);
	}

	public function getConfig($name, $sub = '') {
        return $sub == '' ? $this->config[$name] : $this->config[$name][$sub];
	}

	public function getControllerName() {
        return $this->controllerName;
	}

	public function getActionName() {
		return strtolower($_SERVER['REQUEST_METHOD']).ucwords($this->getRawActionName());
	}

    public function getUriParameter($i) {
        return array_key_exists($i, $this->uriParams) ? $this->uriParams[$i] : NULL;
    }

	public function getRawActionName() {
        return $this->actionName;
	}

	public function generateRequest() {
		$req = new Request($this);
		return $req;
	}

	public function render() {
		echo $this->generateRequest()->getResponse();
		$this->close();
	}

	public function currentEnvironment() {
		$res = "prod";
		foreach ($this->envs as $env => $hosts) {
			foreach ($hosts as $host)
				if ($host == gethostname())
					$res = $env;
		}
		return $res;
	}

	public function isDebug() {
		return $this->getConfig('debug');
	}

    public function getMemoryUsage($peak = false) {
        $unit=array('b','Kb','MB','GB','TB','PB');
        $size = $peak ? memory_get_peak_usage(true) : memory_get_usage(true);
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    /**
     *
     * Stops the execution of the request and raise an error.
     *
     * @param Integer $code Response status
     * @param String $message Error message
     */
	public function raise($code, $message) {
		http_response_code($code);
		if ($this->isDebug())
			throw new \RuntimeException($message);
		else
			exit('Error '.$code);
	}

	public function registerAlias($name, $obj) {
		$this->aliases[$name] = $obj;
	}

    public function registerCommand($className) {
        $this->commands[] = $className;
    }

    public function runCommand($cmd) {
        foreach ($this->commands as $commandClassName) {
            /** @var CommandInterface $commandClass */
            $commandClass = new $commandClassName;
            if ($commandClass->commandName() == $cmd) {
                $commandClass->setApp($this);
                $commandClass->run();
                $commandClass->resetColor();
                return;
            }
        }
        echo "Not found\r\n";
    }

    public function showCommandHelp($cmd) {
        foreach ($this->commands as $commandClassName) {
            /** @var CommandInterface $commandClass */
            $commandClass = new $commandClassName;
            if ($commandClass->commandName() == $cmd) {
                $commandClass->setApp($this);
                $commandClass->displayUsage();
                $commandClass->resetColor();
                return;
            }
        }
        echo "Not found\r\n";
    }

    public function isCLI() {
        return (php_sapi_name() == "cli");
    }

	public function close() {
		$this->db = null;
	}

    public static function generateUrl($controller, $action, $params = []) {
        $url = "http://".$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']))."/".$controller.'/'.$action;
        foreach ($params as $val)
            $url .= '/'.$val;
        return $url;
    }

}