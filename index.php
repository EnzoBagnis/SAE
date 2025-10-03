<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = str_replace('/SAE/', '', $request);
$request = str_replace('/SAE', '', $request);
$request = trim($request, '/');

if (!empty($request) && !isset($_GET['action'])) {
    $_GET['action'] = $request;
}
require_once 'config/router.php';

$router = new Router();
$router->route();
