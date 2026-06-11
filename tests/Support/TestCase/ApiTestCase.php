<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase;

use App\Tests\Support\Faker\FakerTrait;
use App\Tests\Support\TestCase\Mixins\ServicesContainerTrait;
use K2gl\ArrayReader\ArrayReader;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function K2gl\PHPUnitFluentAssertions\fact;

/**
 * Base for API / e2e tests — full HTTP stack via KernelBrowser.
 * Arrange data with factories (tests/Support/Factory) and read response
 * bodies through `responseReader()` (k2gl/array-reader). To authenticate a
 * request pass `executor:` and override `authHeaders()` for your auth scheme
 * (JWT recipe: docs/recipes/jwt-auth.md). Tag your test class #[Group('e2e')].
 */
abstract class ApiTestCase extends WebTestCase
{
    use Factories;
    use FakerTrait;
    use ResetDatabase;
    use ServicesContainerTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->setServicesFromContainer();
    }

    /**
     * Send a JSON request; with `executor` the request is authenticated via
     * {@see authHeaders()}.
     *
     * @param array<string, mixed> $query
     * @param array<array-key, mixed> $parameters
     * @param array<array-key, mixed> $files
     * @param array<array-key, mixed> $json
     */
    protected function sendJsonRequest(
        string $method,
        string $url,
        array $query = [],
        array $parameters = [],
        array $files = [],
        array $json = [],
        ?object $executor = null,
    ): void {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        if ($executor !== null) {
            $server += $this->authHeaders($executor);
        }

        $this->client->request(
            method: $method,
            uri: $url.($query !== [] ? '?'.http_build_query($query) : ''),
            parameters: $parameters,
            files: $files,
            server: $server,
            content: json_encode($json, \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Auth headers for `sendJsonRequest(..., executor:)`. The skeleton ships
     * no authentication (a product decision) — override per project, e.g.
     * `['HTTP_AUTHORIZATION' => 'Bearer '.$this->jwtFor($executor)]`.
     *
     * @return array<string, string>
     */
    protected function authHeaders(object $executor): array
    {
        throw new LogicException('Override authHeaders() for your auth scheme — see docs/recipes/jwt-auth.md.');
    }

    /** Body of the last response as ArrayReader (type-safe field/path reads). */
    protected function responseReader(): ArrayReader
    {
        return ArrayReader::fromJson((string) $this->client->getResponse()->getContent());
    }

    protected function responseStatusCode(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Assert the 422 problem+json envelope contains a violation for the given
     * property with the given stable code (`violations:[{propertyPath, message, code}]`).
     */
    protected function assertResponseContainsViolation(string $propertyPath, string $code): void
    {
        fact($this->responseStatusCode())->is(Response::HTTP_UNPROCESSABLE_ENTITY);

        $found = false;

        foreach ($this->responseReader()->list('violations') as $violation) {
            $violation = ArrayReader::of($violation);

            if ($violation->string('propertyPath') === $propertyPath && $violation->string('code') === $code) {
                $found = true;
            }
        }

        fact($found)->true(sprintf('Response has no violation "%s" with code "%s".', $propertyPath, $code));
    }

    /**
     * POST JSON and return the decoded body.
     *
     * @param array<array-key, mixed> $body
     *
     * @return array<array-key, mixed>
     */
    protected function postJson(string $uri, array $body): array
    {
        $this->sendJsonRequest('POST', $uri, json: $body);

        return $this->decodedResponse();
    }

    /**
     * GET and return the decoded body.
     *
     * @return array<array-key, mixed>
     */
    protected function getJson(string $uri): array
    {
        $this->client->request('GET', $uri, [], [], ['HTTP_ACCEPT' => 'application/json']);

        return $this->decodedResponse();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodedResponse(): array
    {
        $decoded = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
        \assert(\is_array($decoded));

        return $decoded;
    }
}
