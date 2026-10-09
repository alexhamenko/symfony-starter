<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Visual check of the Tailwind/daisyUI theme and, later, of the UI-kit components.
 * The route does not exist in prod: "test" is listed only so the page can be covered by a functional test.
 */
final class StyleguideController extends AbstractController
{
    #[Route('/_dev/styleguide', name: 'dev_styleguide', methods: ['GET'], env: ['dev', 'test'])]
    public function __invoke(): Response
    {
        return $this->render('dev/styleguide.html.twig');
    }
}
