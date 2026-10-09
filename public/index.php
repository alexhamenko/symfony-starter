<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static fn (array $context): Kernel => new Kernel(
    // @phpstan-ignore argument.type (SymfonyRuntime always sets APP_ENV as a string)
    $context['APP_ENV'],
    (bool) $context['APP_DEBUG'],
);
