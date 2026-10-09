<?php

declare(strict_types=1);

// Loader for phpstan-symfony: gives PHPStan the real console commands (argument/option types)

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

$env = $_SERVER['APP_ENV'];
assert(is_string($env));

return new Application(new Kernel($env, (bool) $_SERVER['APP_DEBUG']));
