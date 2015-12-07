<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Disque\Console\Command\Worker;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Parser;

$error = function ($message) {
    error_log($message);
    exit(1);
};

if (!class_exists(Application::class)) {
    $error("symfony/console is missing. Please install it with: $ composer require symfony/console");
} elseif (!class_exists(Parser::class)) {
    $error("symfony/yaml is missing. Please install it with: $ composer require symfony/yaml");
}

$application = new Application();
$application->add(new Worker());
$application->run();