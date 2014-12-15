<?php 

namespace Vertex\Vertex\Framework;

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
     * @param $request Current request
     */
	function __construct($request) {
		$this->request = $request;
		$this->app = $this->request->app;
	}

	public function __get($attr) {
		return $this->app->$attr;
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

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action.
     * If the parameter is not passed and no fallback closure is passed, the request will send a 500 error.
     *
     * @param string $param The name of the parameter that needs to be passed
     * @param mixed $fallback The function that will be called if the param is not needed
     * @return mixed The value of the parameter
     */
	public function needs($param, $fallback = NULL) {
		if ($fallback === NULL) {
			$fallback = function() use ($param) {
				global $app;
				$app->raise(500, 'The parameter "'.$param.'" is needed.');
			};
		}

		if (!Input::has($param))
			$fallback();

		return Input::get($param);
	}

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action.
     * If the parameter is not passed, the default value will be returned.
     *
     * @param $param The name of the parameter that needs to be passed
     * @param $default The default value used if the parameter is not given
     * @return string The value of the paramter of the default value
     */
	public function needsOrDefault($param, $default) {
		if (!Input::has($param))
			Input::push($param, $default);
		return Input::get($param);
	}

    /**
     *
     * Tells the controller that a specific parameter is needed for the current action in order to find an entity.
     * If the entity is not found, a 404 error will be raised.
     *
     * @param $model The name of the model of the entity to get
     * @param $param The name of the request parameter that contains the primary key
     * @return Model
     */
	public function needsEntity($model, $param) {
		$this->needs($param);
		$id = $this->request->$param;
		$entity = $this->repo($model)->find($id);
		if ($entity === NULL)
			$this->app->raise(404, 'The requested '.$model." doesn't exists");
		return $entity;
	}

    /**
     *
     * Redirect the request
     *
     * @param $controller Name of the controller
     * @param $action Name of the action of the controller
     * @param array $params Parameters of the new request
     * @return string Empty response
     */
	public function redirect($controller, $action, $params = []) {
		$path = "?".CONTROLLER_ACCESSOR.'='.$controller.'&'.ACTION_ACCESSOR.'='.$action;
		foreach ($params as $key => $val)
			$path .= '&'.$key.'='.$val;
		header('Location: '.$path);
		return "";
	}
}