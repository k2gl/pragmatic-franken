<?php

declare(strict_types=1);

namespace App\Tests\Context\Notification\Features\LiveUpdates;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Group('e2e')]
final class PublishLiveUpdateTest extends ApiTestCase
{
    public function test_blank_topic_is_rejected_with_problem_json(): void
    {
        $this->sendJsonRequest('POST', '/notification/live-update', json: ['topic' => '', 'data' => []]);

        $this->assertResponseContainsViolation('topic', NotBlank::IS_BLANK_ERROR);
    }
}
