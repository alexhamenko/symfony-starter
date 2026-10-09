<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Called by KernelTrait (overrides its private method), PHPStan cannot see the call.
     *
     * @return list<string> An array of allowed values for APP_ENV
     */
    // @phpstan-ignore method.unused
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
