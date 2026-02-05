#!/bin/bash

MODULE=$1
FEATURE=$2

if [ -z "$MODULE" ] || [ -z "$FEATURE" ]; then
    echo "Usage: make slice module=ModuleName feature=FeatureName"
    exit 1
fi

DIR="src/$MODULE/Features/$FEATURE"

mkdir -p "$DIR"
mkdir -p "src/$MODULE/Entity"
mkdir -p "src/$MODULE/Repository"

cat <<EOF > "$DIR/${FEATURE}Dto.php"
<?php

declare(strict_types=1);

namespace App\\$MODULE\\Features\\$FEATURE;

readonly class ${FEATURE}Dto
{
    public function __construct(
        // Add your properties here
    ) {}
}
EOF

cat <<EOF > "$DIR/${FEATURE}Action.php"
<?php

declare(strict_types=1);

namespace App\\$MODULE\\Features\\$FEATURE;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ${FEATURE}Action
{
    #[Route('/${MODULE,,}/${FEATURE,,}', methods: ['POST'])]
    public function __invoke(${FEATURE}Dto \$dto, ${FEATURE}Handler \$handler): JsonResponse
    {
        \$result = \$handler->handle(\$dto);

        return new JsonResponse(['status' => 'success', 'data' => \$result]);
    }
}
EOF

cat <<EOF > "$DIR/${FEATURE}Handler.php"
<?php

declare(strict_types=1);

namespace App\\$MODULE\\Features\\$FEATURE;

class ${FEATURE}Handler
{
    public function handle(${FEATURE}Dto \$dto): array
    {
        // Business logic starts here
        return [];
    }
}
EOF

# 4. Создаем Feature Test (прямо в папке фичи)
cat <<EOF > "$DIR/${FEATURE}Test.php"
<?php

declare(strict_types=1);

namespace App\\$MODULE\\Features\\$FEATURE;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ${FEATURE}Test extends WebTestCase
{
    public function testSuccess(): void
    {
        \$client = static::createClient();

        // Быстрый старт для теста твоего API
        \$client->request('POST', '/${MODULE,,}/${FEATURE,,}', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            // 'key' => 'value'
        ]));

        \$this->assertResponseIsSuccessful();
    }
}
EOF

echo "✅ Slice $FEATURE in Module $MODULE created at $DIR"
