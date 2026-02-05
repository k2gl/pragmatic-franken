<?php

declare(strict_types=1);

namespace App\User\UseCase\GitHubAuth\Action;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Authenticator\Passport\SelfValidatingPassport;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class GitHubAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private string $frontendUrl = 'http://localhost:5173'
    ) {}

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_github_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('github');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GithubResourceOwner $githubUser */
                $githubUser = $client->fetchUserFromToken($accessToken);

                $user = $this->entityManager->getRepository(User::class)->findOneBy([
                    'githubId' => $githubUser->getId()
                ]);

                if (!$user) {
                    $user = new User(
                        $githubUser->getEmail() ?? 'githubuser@example.com',
                        $githubUser->getId(),
                        $githubUser->getName() ?? 'GitHub User'
                    );
                    $user->setGithubId($githubUser->getId());
                    $user->setAvatarUrl($githubUser->getAvatarUrl());

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $jwt = $this->jwtManager->create($token->getUser());

        return new RedirectResponse(
            $this->frontendUrl . '/auth/callback?token=' . $jwt
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse(
            $this->frontendUrl . '/auth/error?message=' . urlencode($exception->getMessage())
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse(
            '/api/connect/github'
        );
    }
}
