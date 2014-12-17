<?php 

namespace Vertex\Vertex\Framework;

/**
 * Class Application
 * @package Vertex\Vertex\Framework
 */
class Application {

	private $config = [];
	private $envs = [];
	private $aliases = [];
	private $modules = [];
	private $loadedModules = [];

    private $commands = [];

    public $twig;

    /**
     * @var Database
     */
	public $db;

	public function __construct() {
		$this->loadConfig();
		$this->forceRequestParameters();
		$this->loadDatabase();
        $this->loadTwig();
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

    private function forceRequestParameters() {
		if (!array_key_exists(CONTROLLER_ACCESSOR, $_GET)) {
			header('Location: ?'.CONTROLLER_ACCESSOR.'='.$this->getConfig('default_controller'));
		}
		if (!array_key_exists(ACTION_ACCESSOR, $_GET)) {
			header('Location: ?'.CONTROLLER_ACCESSOR.'='.$this->getControllerName().'&'.ACTION_ACCESSOR.'='.$this->getConfig('default_action'));
		}
	}

    private function loadDatabase() {
		if ($this->hasConfig('database')) {
			$this->db = new Database($this);
		} else {
			$this->db = null;
		}
	}

    public function loadTwig() {

        $loader = new \Twig_Loader_Filesystem(APP_ROOT.'/App/Views');
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => false,
        ));

        $this->twig->addFunction(new \Twig_SimpleFunction('asset', function ($path) {
            return 'public/'.$path;
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('path', function ($ctl, $action, $params=[]) {
            $path = "?".CONTROLLER_ACCESSOR.'='.$ctl.'&'.ACTION_ACCESSOR.'='.$action;
            foreach ($params as $key => $val)
                $path .= '&'.$key.'='.$val;
            return $path;
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('app', function () {
            global $app;
            return $app;
        }));

        $this->twig->addFilter(new \Twig_SimpleFilter('dump', function ($var) {
            return var_dump($var);
        }));
    }

    private function loadCommands()
    {
        $this->registerCommand('\\Vertex\\Vertex\\Framework\\Commands\\Test');
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
        if ((array_key_exists(CONTROLLER_ACCESSOR, $_GET))) {
            return $_GET[CONTROLLER_ACCESSOR];
        } else {
            return $this->getConfig('default_controller');
        }
	}

	public function getActionName() {
		return strtolower($_SERVER['REQUEST_METHOD']).ucwords((array_key_exists(ACTION_ACCESSOR, $_GET)) ? $_GET[ACTION_ACCESSOR] : $this->getConfig('default_action'));
	}

	public function getRawActionName() {
		return strtolower((array_key_exists(ACTION_ACCESSOR, $_GET)) ? $_GET[ACTION_ACCESSOR] : $this->getConfig('default_action'));
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

	public static function getUri() {
		$uri = $_SERVER['REQUEST_URI'];
		$script = $_SERVER['SCRIPT_NAME'];
		$script = str_replace('index.php', '', $script);
		$uri = substr($uri, strlen($script));
		return '/'.$uri;
	}

	public static function getUriPart($i) {
		return explode('/', static::getUri())[$i];
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
			exit('Error '.$code.'<br/>'.$message);
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
            /** @var Command $commandClass */
            $commandClass = new $commandClassName;
            if ($commandClass->commandName() == $cmd)
                $commandClass->run();
        }
    }

    public function isCLI() {
        return (php_sapi_name() == "cli");
    }

	public function close() {
		$this->db = null;
	}

}