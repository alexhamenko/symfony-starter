<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StyleguideTest extends WebTestCase
{
    public function testStyleguideRendersWithCompiledStylesheet(): void
    {
        $client = self::createClient();
        $client->request('GET', '/_dev/styleguide');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Styleguide');
        self::assertSelectorExists('link[rel="stylesheet"][href^="/assets/styles/app-"]');
        self::assertSelectorExists('meta[name="viewport"]');
    }
}
