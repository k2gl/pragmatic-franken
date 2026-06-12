---
audience: both
tier: 2
last_reviewed: 2026-04-29
summary: "Step-by-step guide for adding real-time server-sent events to a vertical slice via Mercure and FrankenPHP's built-in hub."
---

# Mercure Integration Guide

FrankenPHP ships a Mercure hub as a built-in service — no separate process needed. Use it to push server-sent events (SSE) to browser clients without WebSocket complexity.

`symfony/mercure-bundle` is already included in this boilerplate (`composer.json`) and registered in `config/bundles.php`. The config file `config/packages/mercure.yaml` is also pre-configured. The only step you need is to set the env vars below.

## Configuration

### `config/packages/mercure.yaml`

```yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: ['*']
```

### `.env.local` (local development)

```dotenv
# Internal URL reachable from the PHP worker (same container as FrankenPHP)
MERCURE_URL=https://pragmatic-franken.localhost:4750/.well-known/mercure
# Public URL sent to browser clients
MERCURE_PUBLIC_URL=https://pragmatic-franken.localhost:4750/.well-known/mercure
# Must match the `hubOptions.jwt_key` in your Caddyfile
MERCURE_JWT_SECRET=changeme-in-production
```

### `.env.test` (test / CI stubs)

```dotenv
# Stub values — no real hub needed for unit or smoke tests
MERCURE_URL=http://localhost/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost/.well-known/mercure
MERCURE_JWT_SECRET=test-secret
```

These stubs are already committed to `.env.test`. MercureBundle resolves the hub URL eagerly at container compile time (via `EnvVarProcessor`), so stubs are required even when no real Mercure hub is present in CI.

## Publishing from a slice

The reference implementation lives at `src/Context/Notification/Features/LiveUpdates/`.

### Command + Handler

```php
// Application/PublishLiveUpdateCommand.php
final readonly class PublishLiveUpdateCommand
{
    public function __construct(
        public string $topic,
        public array  $data,
        public bool   $private = false,
    ) {}
}

// Application/PublishLiveUpdateHandler.php
#[AsMessageHandler]
final readonly class PublishLiveUpdateHandler
{
    public function __construct(private HubInterface $hub) {}

    public function __invoke(PublishLiveUpdateCommand $command): LiveUpdateResult
    {
        $update = new Update(
            topics: $command->topic,
            data: json_encode($command->data, \JSON_THROW_ON_ERROR),
            private: $command->private,
        );

        return new LiveUpdateResult($this->hub->publish($update));
    }
}
```

Dispatch from any other handler:

```php
$this->commandBus->dispatch(new PublishLiveUpdateCommand(
    topic: '/board/42',
    data: ['event' => 'task_created', 'taskId' => $id],
));
```

### HTTP entry point

`POST /notification/live-update` accepts JSON:

```json
{ "topic": "/board/42", "data": { "event": "task_created" }, "private": false }
```

Returns `201` with `{ "messageId": "..." }`.

## Subscribing (browser)

```javascript
const url = new URL('https://pragmatic-franken.localhost:4750/.well-known/mercure');
url.searchParams.append('topic', '/board/42');

const es = new EventSource(url.toString(), { withCredentials: true });
es.onmessage = (e) => console.log(JSON.parse(e.data));
```

For private updates, the hub checks the subscriber's JWT cookie — see [Mercure docs](https://mercure.rocks/spec).

## Topic conventions

| Pattern | Use |
|---|---|
| `/board/{id}` | All events for a board |
| `/user/{userId}` | Per-user private notifications |
| `/tasks` | Task completion updates (shipped example) |

## Testing

Unit-test handlers by mocking `HubInterface` — see `tests/Context/Notification/Features/LiveUpdates/PublishLiveUpdateHandlerTest.php`.

E2E tests that need a live hub require the FrankenPHP container running (`make up`). Guard them with a `#[Group('e2e')]` attribute and skip in CI without Docker.

## Worker-mode note

FrankenPHP's worker reuses the kernel between requests. `HubInterface` is stateless (each `publish()` opens an HTTP connection to the hub and closes it), so it is safe in worker mode. See ADR-0004 and `docs/guides/worker-mode.md`.
