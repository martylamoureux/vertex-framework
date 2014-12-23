<?php 

namespace Vertex\Framework;

use Vertex\Framework\Modeling\FormAdapter;
use Vertex\Framework\Modeling\Model;

class Controller {
    /**
     * @var Request
     */
	public $request;
    /**
     * @var Application
     */
	protected $app;

    /**
     * @param $request Request Current request
     */
	function __construct($request) {
		$this->request = $request;
		$this->app = $this->request->app;
	}

	public function __get($attr) {
		return $this->app->$attr;
	}

    public function getControllerName($withControllerSufix = true) {
        $refl = new \ReflectionClass(get_class($this));
        return $withControllerSufix ? $refl->getShortName() : str_replace('Controller', '', $refl->getShortName());
    }

    /**
     * @param String $name Name of the twig view
     * @param array $vars variables passed to the view
     * @return string Generated view
     */
	public function view($name, $vars = []) {
		return $this->app->twig->render($name, $vars);
	}

    /**
     * @param array $vars variables passed to the view
     * @return string Generated view
     */
	public function naturalView($vars = []) {
		$ctlName = $this->app->getControllerName();
		$actionName = $this->app->getRawActionName();
		return $this->view($ctlName.'/'.$actionName.'.html.twig', $vars);
	}

    /**
     * @param String $name The name of the repository to get
     * @return Repository
     */
	public function repo($name) {
		return $this->app->db->repository($name);
	}

    public function form($model) {
        if ($model instanceof Model)
            return new FormAdapter($model);
        else
            return new FormAdapter(Model::create($model));
    }

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action.
     * If the parameter is not passed and no fallback closure is passed, the request will send a 500 error.
     *
     * @param int $param The index of the parameter in the uri, following the action name
     * @param mixed $fallback The function that will be called if the param is not needed
     * @return mixed The value of the parameter
     */
	public function needs($param, $fallback = NULL) {
		if ($fallback === NULL || !is_callable($fallback)) {
			$fallback = function() use ($param) {
				global $app;
                /** @var Application $app */
                $app->raise(500, 'The parameter "'.$param.'" is needed.');
			};
		}

        $res = $this->app->getUriParameter($param);

		if ($res === NULL)
			return $fallback();
		return $res;
	}

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action.
     * If the parameter is not passed, the default value will be returned.
     *
     * @param $param int The name of the parameter that needs to be passed
     * @param $default String The default value used if the parameter is not given
     * @return string The value of the paramter of the default value
     */
	public function needsOrDefault($param, $default) {
		return $this->needs($param, function() use ($default) { return $default; });
	}

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action in order to find an entity.
     * If the entity is not found, a 404 error will be raised.
     *
     * @param $model String The name of the model of the entity to get
     * @param $param int The name of the request parameter that contains the primary key
     * @return Model
     */
	public function needsEntity($model, $param) {
		$id = $this->needs($param);
		$entity = $this->repo($model)->find($id);
		if ($entity === NULL)
			$this->app->raise(404, 'The requested '.$model." doesn't exists");
		return $entity;
	}

    /**
     *
     * Redirect the request
     *
     * @param $controller String Name of the controller
     * @param $action String Name of the action of the controller
     * @param array $params Parameters of the new request
     * @return string Empty response
     */
	public function redirect($controller, $action = NULL, $params = []) {
        if ($action === NULL) {
            $action = $controller;
            $controller = strtolower($this->getControllerName(false));
        }
		$path = $this->app->generateUrl($controller, $action, $params);
		header('Location: '.$path);
		return "";
	}
}