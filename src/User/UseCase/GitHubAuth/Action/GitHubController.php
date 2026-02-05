<?php

declare(strict_types=1);

namespace App\User\UseCase\GitHubAuth\Action;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

final class GitHubController extends AbstractController
{
    #[Route('/api/connect/github', name: 'connect_github_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('github')->redirect(['user:email'], []);
    }

    #[Route('/api/connect/github/check', name: 'connect_github_check')]
    public function check(): void
    {
    }
}
