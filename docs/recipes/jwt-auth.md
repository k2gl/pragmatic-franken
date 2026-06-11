---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Add stateless JWT auth (lexik) with rotating refresh tokens (gesdinet) — configs proven in the production CRM grown from this skeleton. The skeleton ships no auth on purpose; this recipe is the fastest safe path."
---

# Recipe: JWT authentication

The skeleton ships **no** authentication (a product decision — see
`config/packages/security.yaml`). This recipe is the production-proven path:
`lexik/jwt-authentication-bundle` for access tokens +
`gesdinet/jwt-refresh-token-bundle` for seamless re-login.

## Install

```bash
composer require lexik/jwt-authentication-bundle gesdinet/jwt-refresh-token-bundle
bin/console lexik:jwt:generate-keypair    # writes config/jwt/{private,public}.pem
```

In prod, persist the keypair in a volume (`jwt:/app/config/jwt`) and set a
`JWT_PASSPHRASE` — see the env handling below.

## Configuration (CRM-proven)

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%kernel.project_dir%/config/jwt/private.pem'
    public_key: '%kernel.project_dir%/config/jwt/public.pem'
    # Empty when JWT_PASSPHRASE is unset (dev/test/CI) → unencrypted keys, no
    # extra wiring. In prod set the env and regenerate the keypair once.
    pass_phrase: '%env(default::JWT_PASSPHRASE)%'
    token_ttl: 3600
```

```yaml
# config/packages/gesdinet_jwt_refresh_token.yaml
gesdinet_jwt_refresh_token:
    refresh_token_class: App\Context\User\Entity\RefreshToken
    ttl: 2592000        # 30 days
    ttl_update: true    # sliding window: active users never drop out
    single_use: true    # rotation: each refresh issues a new token, kills the old
```

```yaml
# config/packages/security.yaml — firewalls (replace the bare `main`)
security:
    providers:
        app_user_provider:
            entity: { class: App\Context\User\Entity\User, property: email }
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern: ^/api/v1/auth/login$
            stateless: true
            provider: app_user_provider
            json_login:
                check_path: /api/v1/auth/login
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            login_throttling: { max_attempts: 5 }   # brute-force brake
        api_token_refresh:
            pattern: ^/api/v1/auth/token/refresh$
            stateless: true
            provider: app_user_provider
            refresh_jwt: { check_path: /api/v1/auth/token/refresh }
        api:
            pattern: ^/api
            stateless: true
            provider: app_user_provider
            jwt: ~
    access_control:
        - { path: ^/api/v1/auth, roles: PUBLIC_ACCESS }
        - { path: ^/(healthz|ready)$, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

Build the `User` context per ADR-0012: entity + repository at
`src/Context/User/{Entity,Repository}/`, Login/Register as feature slices.

## Tests

`tests/Support/TestCase/ApiTestCase.php` already has the hook — override it
once per project:

```php
protected function authHeaders(object $executor): array
{
    $manager = $this->container(\Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface::class);

    return ['HTTP_AUTHORIZATION' => 'Bearer '.$manager->create($executor)];
}
```

then `$this->sendJsonRequest('GET', '/api/v1/me', executor: UserFactory::createOne());`
works in every e2e test. The `SecurityExceptionListener` in SharedKernel
already renders `#[IsGranted]` denials as problem+json 403s.
