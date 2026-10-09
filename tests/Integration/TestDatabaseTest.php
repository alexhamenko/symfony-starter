<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TestDatabaseTest extends KernelTestCase
{
    public function testTestEnvironmentUsesSeparateDatabase(): void
    {
        $connection = self::getContainer()->get(Connection::class);

        // dbname_suffix from config/packages/doctrine.yaml (when@test): never touch the dev database
        self::assertSame('app_test', $connection->getDatabase());
        self::assertSame(1, $connection->fetchOne('SELECT 1'));
    }
}
