<?php

namespace Vertex\Framework;

class Module {

    /**
     * @var Application
     */
	protected static $app;

    /** @var \Twig_Environment */
    protected $internalTwig;
    /** @var \Twig_Environment */
    protected $customTwig;

    protected $internalTwigDirectory;
    protected $customlTwigDirectory;

    public static $configName = "_module";

	public function __construct($app) {
		static::$app = $app;

        $this->internalTwigDirectory = './';
        $this->customlTwigDirectory = APP_ROOT . DIRECTORY_SEPARATOR . 'App' .DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . "_vertex";
        $this->loadTwig();
	}

    public static function path(array $parts) {
        $path = realpath(dirname(__FILE__));
        foreach ($parts as $filename)
            $path .= DIRECTORY_SEPARATOR . $filename;
        return $path;
    }

    public static function assetPath($filesystem = false) {
        $classname = __CLASS__;
        $classname = str_replace("\\", '.', $classname);
        if ($filesystem)
            return 'public/' . $classname;
        else
            return "http://".$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']))."/".$classname;
    }

    protected function loadTwig() {
        $loader = new \Twig_Loader_Filesystem($this->internalTwigDirectory);
        $this->internalTwig = new \Twig_Environment($loader, array(
            'cache' => false,
        ));


        $this->internalTwig->addFunction(new \Twig_SimpleFunction('asset', function ($path) {
            return static::assetPath().'/'.$path;
        }));

        $this->internalTwig->addGlobal('app', static::$app);
        $this->internalTwig->addGlobal('module', $this);

        if (is_dir($this->customlTwigDirectory)) {
            $customLoader = new \Twig_Loader_Filesystem($this->customlTwigDirectory);
            $this->customTwig = new \Twig_Environment($customLoader, array(
                'cache' => false,
            ));


            $this->customTwig->addFunction(new \Twig_SimpleFunction('asset', function ($path) {
                return "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . "/" . __CLASS__ . '/' . $path;
            }));

            $this->customTwig->addGlobal('app', static::$app);
            $this->customTwig->addGlobal('module', $this);
        }
    }

    public function render($view, $vars = []) {
        if (file_exists($this->customlTwigDirectory . DIRECTORY_SEPARATOR . $view)) {
            return $this->customTwig->render($view, $vars);
        }

        return $this->internalTwig->render($view, $vars);
    }

    public function getConfig($name, $default) {
        if (static::$app->hasConfig(static::$configName, $name))
            return static::$app->getConfig(static::$configName, $name);
        return $default;
    }

    public function getAssets() {
        return [];
    }
}