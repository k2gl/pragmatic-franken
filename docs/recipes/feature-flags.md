---
audience: both
tier: 2
last_reviewed: 2026-06-12
summary: "Runtime feature flags in a Platform context: enum catalog + DB override + FeatureFlags::isEnabled() read service. UI-managed toggles never live in env vars. Distilled from a real production project's decision record."
---

# Recipe: feature flags & system settings

Distilled from a real production project grown out of this skeleton. The rule
that earns the recipe:

> Any **UI-manageable boolean behavior toggle** goes through a `FeatureFlag`
> enum + a `FeatureFlags` read service — never `%env(bool:*)%`. Env vars stay
> for infra, deploy and secrets (process-level concerns like
> `SCHEDULER_ENABLED`), because env changes need a redeploy and are invisible
> to the product owner.

## Shape

```
src/Context/Platform/
  Enum/FeatureFlag.php          # the catalog: cases + label/description/defaultEnabled
  Entity/FeatureFlagState.php   # DB override: flag (PK), enabled, updatedAt
  Repository/FeatureFlagStateRepository.php
  Shared/FeatureFlags.php       # isEnabled(FeatureFlag $flag): bool  → override ?? default
  Features/ListFeatures/        # GET  /api/v1/system/features  (admin role)
  Features/ToggleFeature/       # PUT  /api/v1/system/features  (admin role)
```

```php
enum FeatureFlag: string
{
    case Telephony = 'telephony';

    public function defaultEnabled(): bool
    {
        return match ($this) {
            self::Telephony => false,
        };
    }
}
```

`FeatureFlags::isEnabled()` reads the DB override first, falls back to the
enum default — so a fresh deploy with a new flag behaves sanely before anyone
touches the UI, and flipping a flag needs no redeploy.

## Why an enum and not a table of strings

The enum is the *catalog*: adding a flag is a code review, label and default
included; PHPStan sees every usage; deleting a case finds every dead toggle.
The DB stores only overrides, so there is no drift between environments about
*which flags exist*.

## When NOT to use a flag

Permanent configuration (URLs, credentials, limits) — env vars. One-off
migrations — migrations. Per-user preferences — that's a domain entity, not a
platform flag.
