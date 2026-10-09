<?php

declare(strict_types=1);

// Loader for phpstan-doctrine: gives PHPStan the real Doctrine mapping (no DB connection needed)

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

$env = $_SERVER['APP_ENV'];
assert(is_string($env));

$kernel = new Kernel($env, (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
