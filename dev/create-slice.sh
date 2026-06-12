#!/usr/bin/env bash
# Generates a vertical slice scaffold for src/Context/{Context}/Features/{Feature}/.
# {Context} is a DDD Bounded Context (Eric Evans / Vaughn Vernon) — top-level
# directories like User/, Task/, Health/ each have their own ubiquitous
# language and consistency boundary.
#
# Layout follows ADR-0001 (DDD-layered slice). Inside Application/ only the
# *Handler sits at the root; the dispatched message goes in Message/, output
# DTOs in Dto/.
#
# Usage: make slice context=User feature=Register
#    or: ./dev/create-slice.sh User Register

set -euo pipefail

CONTEXT="${1:-}"
FEATURE="${2:-}"

if [[ -z "$CONTEXT" || -z "$FEATURE" ]]; then
    echo "Usage: make slice context=ContextName feature=FeatureName" >&2
    exit 1
fi

DIR="src/Context/$CONTEXT/Features/$FEATURE"
TEST_DIR="tests/Context/$CONTEXT/Features/$FEATURE"

if [[ -e "$DIR" ]]; then
    echo "Refusing to overwrite existing $DIR" >&2
    exit 1
fi

mkdir -p "$DIR/Application/Message" "$DIR/Application/Dto" "$DIR/Infrastructure" "$DIR/EntryPoint/Http" "$TEST_DIR"

LCC=$(echo "$CONTEXT" | tr '[:upper:]' '[:lower:]')
LCF=$(echo "$FEATURE" | tr '[:upper:]' '[:lower:]')

cat > "$DIR/Application/Message/${FEATURE}Command.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Message;

final readonly class ${FEATURE}Command
{
    public function __construct(
        // Add input fields here.
    ) {}
}
EOF

cat > "$DIR/Application/${FEATURE}Handler.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application;

use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Dto\\${FEATURE}Result;
use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Message\\${FEATURE}Command;
use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

#[AsMessageHandler]
final readonly class ${FEATURE}Handler
{
    public function __invoke(${FEATURE}Command \$command): ${FEATURE}Result
    {
        // Business logic.
        return new ${FEATURE}Result;
    }
}
EOF

cat > "$DIR/Application/Dto/${FEATURE}Result.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Dto;

final readonly class ${FEATURE}Result
{
    public function __construct(
        // Add output fields here.
    ) {}
}
EOF

cat > "$DIR/EntryPoint/Http/${FEATURE}Controller.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\EntryPoint\\Http;

use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Message\\${FEATURE}Command;
use Symfony\\Component\\HttpFoundation\\JsonResponse;
use Symfony\\Component\\Messenger\\HandleTrait;
use Symfony\\Component\\Messenger\\MessageBusInterface;
use Symfony\\Component\\Routing\\Attribute\\Route;

final class ${FEATURE}Controller
{
    use HandleTrait;

    public function __construct(MessageBusInterface \$messageBus)
    {
        \$this->messageBus = \$messageBus;
    }

    #[Route('/${LCC}/${LCF}', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        \$result = \$this->handle(new ${FEATURE}Command);

        return new JsonResponse(['data' => \$result]);
    }
}
EOF

cat > "$TEST_DIR/${FEATURE}HandlerTest.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Tests\\Context\\${CONTEXT}\\Features\\${FEATURE};

use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\${FEATURE}Handler;
use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Dto\\${FEATURE}Result;
use App\\Context\\${CONTEXT}\\Features\\${FEATURE}\\Application\\Message\\${FEATURE}Command;
use App\\Tests\\Support\\TestCase\\UnitTestCase;
use PHPUnit\\Framework\\Attributes\\Group;

#[Group('unit')]
final class ${FEATURE}HandlerTest extends UnitTestCase
{
    public function test_handler_returns_result(): void
    {
        \$handler = new ${FEATURE}Handler;
        \$result = \$handler(new ${FEATURE}Command);

        self::assertInstanceOf(${FEATURE}Result::class, \$result);
    }
}
EOF

echo "Created $DIR (Application: Handler at root + Message/, Dto/; Infrastructure, EntryPoint/Http) and $TEST_DIR"
