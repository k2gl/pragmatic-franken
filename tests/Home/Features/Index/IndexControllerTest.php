<?php

declare(strict_types=1);

namespace App\Tests\Home\Features\Index;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('e2e')]
final class IndexControllerTest extends ApiTestCase
{
    public function test_homepage_renders(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Pragmatic Franken');
    }
}
