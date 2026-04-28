<?php

// Reference example for AssetMapper + Twig (ADR-0007).
// This slice is non-normative — remove if not using server-rendered HTML.

declare(strict_types=1);

namespace App\Home\Features\Index\EntryPoint\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    #[Route('/', name: 'app_home_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('@Home/index.html.twig');
    }
}
