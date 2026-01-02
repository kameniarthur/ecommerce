<?php
// core/Router.php

class Router
{
    private $routes = [];
    private $notFound;

    public function get($uri, $callback)
    {
        $this->routes['GET'][$uri] = $callback;
    }

    public function post($uri, $callback)
    {
        $this->routes['POST'][$uri] = $callback;
    }

    public function put($uri, $callback)
    {
        $this->routes['PUT'][$uri] = $callback;
    }

    public function delete($uri, $callback)
    {
        $this->routes['DELETE'][$uri] = $callback;
    }

    public function any($uri, $callback)
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->routes[$method][$uri] = $callback;
        }
    }

    public function notFound($callback)
    {
        $this->notFound = $callback;
    }

    public function dispatch($request)
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes[$method] as $route => $callback) {
            if ($this->match($route, $uri, $params)) {
                return is_callable($callback) ? $callback($params) : $this->call($callback, $params);
            }
        }

        // 404
        if ($this->notFound) {
            return $this->notFound($request);
        }
        http_response_code(404);
        echo "404 Not Found";
    }

    private function match($route, $uri, &$params)
    {
        $pattern = '#^' . preg_replace('/\\\{(\w+)\\\}/', '(?P<$1>[^/]+)', preg_quote($route)) . '$#';
        if (preg_match($pattern, $uri, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }
        return false;
    }

    private function call($callback, $params)
    {
        list($controller, $action) = explode('@', $callback);
        $controller = "App\\Controller\\{$controller}";
        $controller = new $controller();
        return $controller->$action(...array_values($params));
    }
}
