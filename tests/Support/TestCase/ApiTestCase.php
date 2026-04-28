<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Base for API / end-to-end tests — full HTTP stack via KernelBrowser.
 * Tag your test class with #[Group('e2e')].
 */
abstract class ApiTestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    /** POST JSON and return decoded body. */
    protected function postJson(string $uri, array $body): array
    {
        $this->client->request(
            'POST',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($body, \JSON_THROW_ON_ERROR),
        );

        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
    }

    /** GET and return decoded body. */
    protected function getJson(string $uri): array
    {
        $this->client->request(
            'GET',
            $uri,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
    }
}
