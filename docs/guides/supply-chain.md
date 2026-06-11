---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Verify what you deploy: release images carry SLSA provenance; gh attestation verify or the offline app:verify-attestation command (k2gl/sigstore-verify) gate deploys. How to sign, verify, and refresh the trusted root."
---

# Supply chain: sign releases, verify before running

Policy: ADR-0018. This guide is the practice.

## What CI signs

`release.yml` pushes the prod image to GHCR and attaches a SLSA
build-provenance attestation via `actions/attest-build-provenance` — keyless,
signed by the workflow's OIDC identity. Nothing to configure on a fork: the
permissions block is already in the workflow.

## Verify an image (one-liner)

```bash
gh attestation verify oci://ghcr.io/k2gl/pragmatic-franken:latest \
    --repo k2gl/pragmatic-franken
```

## Gate a deploy on provenance

For pull-based deploys (registry images), `ops/rollout.sh` refuses unverified
images:

```bash
DEPLOY_REQUIRE_ATTESTATION=true \
DEPLOY_IMAGE=ghcr.io/k2gl/pragmatic-franken:v1.2.3 \
DEPLOY_REPO=k2gl/pragmatic-franken \
COMPOSE="docker compose -f docker/compose.prod.yml --env-file .env.prod" \
./ops/rollout.sh app
```

## Verify any artifact offline (PHP, no cosign)

```bash
gh attestation download <artifact-url-or-path> ...   # → artifact.sigstore.jsonl
gh attestation trusted-root > trusted_root.jsonl     # pin once, refresh rarely

bin/console app:verify-attestation artifact.tar.gz artifact.sigstore.jsonl \
    --repository=k2gl/pragmatic-franken --workflow=release.yml \
    --trusted-root=trusted_root.jsonl
```

Checks performed (all must pass — fail-closed): certificate chains to the
Sigstore trusted root · transparency-log inclusion · signer identity is the
named repository/workflow/ref · subject sha256 equals the local file. Without
`--trusted-root` the public-good root is fetched via TUF (the only network
call).

The implementation is the reference slice
`src/Context/Platform/Features/VerifyAttestation/` built on
[k2gl/sigstore-verify](https://github.com/k2gl/sigstore-verify) (pure PHP,
passes the official sigstore-conformance suite). Its tests commit a real
signed artifact and prove both directions: valid verifies, tampered and
wrong-identity are rejected.

## Maintenance

- Refresh the pinned root when Sigstore rotates keys:
  `gh attestation trusted-root > tests/.../fixtures/trusted_root.jsonl`.
- `composer audit` is a hard CI gate for dependency advisories. Packagist
  package signatures: out of scope until an ecosystem exists (ADR-0018).
