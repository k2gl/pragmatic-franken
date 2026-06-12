---
audience: both
tier: 2
last_reviewed: 2026-06-12
summary: "Step-by-step guide for adding real-time server-sent events to a vertical slice via Mercure and FrankenPHP's built-in hub."
---

# Mercure Integration Guide

FrankenPHP ships a Mercure hub as a built-in service — no separate process needed. Use it to push server-sent events (SSE) to browser clients without WebSocket complexity.

`symfony/mercure-bundle` ships pre-wired: registered in `config/bundles.php`, configured in `config/packages/mercure.yaml` (hub URL, public URL and JWT secret all come from env vars). Working defaults are committed in `.env.dist` — local development needs no extra setup.

## Configuration

The hub runs as a private `:3000` site inside the FrankenPHP container; the public site reverse-proxies `/.well-known/mercure` to it (see `docker/frankenphp/Caddyfile`). Hence two URLs in `.env.dist`:

```dotenv
# Internal URL — the PHP worker talks to the hub in-container over plain HTTP
MERCURE_URL=http://localhost:3000/.well-known/mercure
# Public URL — sent to browser clients
MERCURE_PUBLIC_URL=https://pragmatic-franken.localhost:4750/.well-known/mercure
# Signs hub JWTs (publisher_jwt / subscriber_jwt in the Caddyfile)
MERCURE_JWT_SECRET=!ChangeMe!
```

Override in `.env.local` only when you deviate from these defaults; `make init name=my-app` rewrites the public hostname and rotates the secret. `.env.test` carries committed stubs — MercureBundle resolves the hub URL eagerly at container compile time, so the vars must exist even where no hub runs (CI).

## Publishing from a slice

The reference implementation lives at `src/Context/Notification/Features/LiveUpdates/`.

### Command + Handler

```php
// Application/Message/PublishLiveUpdateCommand.php
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
$this->messageBus->dispatch(new PublishLiveUpdateCommand(
    topic: '/board/42',
    data: ['event' => 'task_created', 'taskId' => $id],
));
```

### HTTP entry point

`POST /notification/live-update` accepts JSON:

```json
{ "topic": "/board/42", "data": { "event": "task_created" }, "private": false }
```

Returns `201` with `{ "data": { "messageId": "..." } }` — the response envelope from [ADR-0016](../adr/0016-http-response-contract.md).

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
