#!/usr/bin/env bash
# Generates a vertical slice scaffold for src/{Module}/Features/{Feature}/.
# Layout follows ADR-0001 (DDD-layered slice).
#
# Usage: make slice module=User feature=Register
#    or: ./dev/create-slice.sh User Register

set -euo pipefail

MODULE="${1:-}"
FEATURE="${2:-}"

if [[ -z "$MODULE" || -z "$FEATURE" ]]; then
    echo "Usage: make slice module=ModuleName feature=FeatureName" >&2
    exit 1
fi

DIR="src/$MODULE/Features/$FEATURE"
TEST_DIR="tests/$MODULE/Features/$FEATURE"

if [[ -e "$DIR" ]]; then
    echo "Refusing to overwrite existing $DIR" >&2
    exit 1
fi

mkdir -p "$DIR/Application" "$DIR/Infrastructure" "$DIR/EntryPoint/Http" "$TEST_DIR"

LCM=$(echo "$MODULE" | tr '[:upper:]' '[:lower:]')
LCF=$(echo "$FEATURE" | tr '[:upper:]' '[:lower:]')

cat > "$DIR/Application/${FEATURE}Command.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\${MODULE}\\Features\\${FEATURE}\\Application;

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

namespace App\\${MODULE}\\Features\\${FEATURE}\\Application;

use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

#[AsMessageHandler]
final readonly class ${FEATURE}Handler
{
    public function __invoke(${FEATURE}Command \$command): ${FEATURE}Result
    {
        // Business logic.
        return new ${FEATURE}Result();
    }
}
EOF

cat > "$DIR/Application/${FEATURE}Result.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\${MODULE}\\Features\\${FEATURE}\\Application;

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

namespace App\\${MODULE}\\Features\\${FEATURE}\\EntryPoint\\Http;

use App\\${MODULE}\\Features\\${FEATURE}\\Application\\${FEATURE}Command;
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

    #[Route('/${LCM}/${LCF}', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        \$result = \$this->handle(new ${FEATURE}Command());

        return new JsonResponse(['data' => \$result]);
    }
}
EOF

cat > "$TEST_DIR/${FEATURE}HandlerTest.php" <<EOF
<?php

declare(strict_types=1);

namespace App\\Tests\\${MODULE}\\Features\\${FEATURE};

use App\\${MODULE}\\Features\\${FEATURE}\\Application\\${FEATURE}Command;
use App\\${MODULE}\\Features\\${FEATURE}\\Application\\${FEATURE}Handler;
use App\\${MODULE}\\Features\\${FEATURE}\\Application\\${FEATURE}Result;
use PHPUnit\\Framework\\TestCase;

final class ${FEATURE}HandlerTest extends TestCase
{
    public function test_handler_returns_result(): void
    {
        \$handler = new ${FEATURE}Handler();
        \$result = \$handler(new ${FEATURE}Command());

        self::assertInstanceOf(${FEATURE}Result::class, \$result);
    }
}
EOF

echo "Created $DIR (Application, Infrastructure, EntryPoint/Http) and $TEST_DIR"
