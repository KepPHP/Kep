<?php

namespace Kep\Controller;

use Kep\Authentication\Auth;
use Kep\Config\Config;

class CallController extends Config
{
    /**
     * @acess private
     *
     * @var string Receives the controller name
     */
    private $controller;

    /**
     * @acess private
     *
     * @var string Receives the function to start
     */
    private $action;

    /**
     * @acess private
     *
     * @var array or json 	Parameters to be passed
     */
    private $parameters;

    /**
     * @acess private
     *
     * @var string Returns false or true
     */
    private $auth;

    /**
     * @acess private
     *
     * @var string Path
     */
    private $path;

    /**
     * @acess private
     *
     * @var string Folder
     */
    private $folder;

    /**
     * Mount the controller to perform the actions requested by the route.
     *
     * @acess public
     *
     * @return action returns the function called by route
     */
    public function createController()
    {
        $directory = $this->getConfig();
        $directory = $directory['directory'];

        $this->checkFolder($directory);
        $this->checkController();
    }

    /**
     * Checks whether there is even an organization folder, and set path.
     *
     * @acess private
     *
     * @return $path
     */
    private function checkFolder($directory)
    {
        if ($this->folder == false) {
            $this->path = "../{$directory}/controllers/{$this->controller}.php";
        } else {
            $this->path = "../{$directory}/controllers/{$this->folder}/{$this->controller}.php";
        }
    }

    /**
     * Check if the controller parameter exist or is empty.
     *
     * @acess private
     *
     * @param string $Path controller path
     */
    private function checkController()
    {
        if (! $this->controller) {
            $this->responseJson('Controller does not exist.'.$this->controller, 404);

            return;
        }

        $this->checkPatchController();
    }

    /**
     * Check if the controller of the way there.
     *
     * @acess private
     *
     * @param string $Path controller path
     */
    private function checkPatchController()
    {
        if (! file_exists($this->path)) {
            $this->responseJson('We did not find the driver: '.$this->path.' '.$this->params, 404);

            return;
        }

        $this->checkClassController();
    }

    /**
     * Check if the driver class exists.
     *
     * @acess private
     *
     * @param string $Path controller path
     */
    private function checkClassController()
    {
        require_once $this->path;

        if (! class_exists($this->controller)) {
            $this->responseJson('We did not find the driver class', 404);

            return;
        }

        $this->controller = new $this->controller($this->parameters);

        $this->checkMethodController();
    }

    /**
     * Check if the method exists.
     *
     * @acess private
     */
    private function checkMethodController()
    {
        if (method_exists($this->controller, $this->action)) {
            $this->controller->{$this->action}($this->parameters);

            return;
        }

        $this->checkActionController();
    }

    /**
     * Make sure that the class called function exist in the controller.
     *
     * @acess private
     */
    private function checkActionController()
    {
        if (! $this->action && method_exists($this->controlador, 'index')) {
            $this->controller->index($this->parameters);

            return;
        }

        $this->responseJson('We did not find the controller', 404);
    }

    /**
     * function to return a message in json.
     *
     * @acess private
     *
     * @param string $message error message
     * @param int    $code    Error code
     *
     * @return string Error message in JSON
     */
    private function responseJson($message, $code)
    {
        $array = [
            'status'  => 'error',
            'message' => $message,
            'code'    => $code,
        ];

        echo json_encode($array);
    }

    /**
     * checks for user authentication.
     *
     * @acess public
     *
     * @param string $name  Username
     * @param string $token Token Authentication
     *
     * @return int returns true or false
     */
    public function isAuth($name, $token)
    {
        $auth = new Auth();

        $check = $auth->checkToken($name, $token);

        if ($check == 'disabled') {
            return true;
        } elseif ($check == 'true') {
            return true;
        } elseif ($check == 'false') {
            return false;
        }
    }

    /**
     * Run function calls the function createController().
     *
     * @acess public
     *
     * @param string      $controller
     * @param string      $action
     * @param array||json $params
     * @param string      $folder
     */
    public function getController($controller, $action, $params, $folder)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->parameters = $params;
        $this->folder = $folder;

        if (isset($params->user) && isset($params->token)) {
            if ($this->isAuth($params->user, $params->token) == true) {
                $this->createController();
            } else {
                $this->responseJson('Authentication failed', 404);
            }
        } else {
            $this->createController();
        }
    }
}
