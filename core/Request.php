<?php
// core/Request.php

class Request
{
    private $get;
    private $post;
    private $files;
    private $server;
    private $method;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->method = strtoupper($this->server['REQUEST_METHOD']);
    }

    public function get($key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function post($key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function input($key, $default = null)
    {
        return $this->get($key, $this->post($key, $default));
    }

    public function all()
    {
        return array_merge($this->get, $this->post);
    }

    public function has($key)
    {
        return isset($this->get[$key]) || isset($this->post[$key]);
    }

    public function method()
    {
        return $this->method;
    }

    public function isPost()
    {
        return $this->method === 'POST';
    }

    public function isGet()
    {
        return $this->method === 'GET';
    }

    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    public function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return filter_var(trim($data), FILTER_SANITIZE_STRING);
    }

    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
