<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HttpSmokeTest extends WebTestCase
{
    public function testUnknownUrlReturnsNotFound(): void
    {
        $client = self::createClient();
        $client->request('GET', '/this-page-does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }
}
