<?php
// app/helpers/functions.php

function formatPrice($price)
{
    return number_format($price, 0, ',', ' ') . ' FCFA';
}

function formatDate($date, $format = 'd/m/Y')
{
    return (new DateTime($date))->format($format);
}

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return trim(filter_var($data, FILTER_SANITIZE_STRING));
}

function escape($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function old($key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function asset($path)
{
    return '/' . ltrim($path, '/');
}

function url($path)
{
    return '/' . ltrim($path, '/');
}

function isActive($route)
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $uri === $route ? 'active' : '';
}

function generateSlug($string)
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

function truncate($text, $length = 100)
{
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function timeAgo($date)
{
    $dt = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($dt);
    // Implémentation simplifiée
    if ($diff->y > 0) return $diff->y . ' an(s)';
    if ($diff->m > 0) return $diff->m . ' mois';
    if ($diff->d > 0) return $diff->d . ' jour(s)';
    return 'Aujourd’hui';
}

function generateToken()
{
    return bin2hex(random_bytes(32));
}
