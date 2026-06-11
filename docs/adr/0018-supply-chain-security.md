---
id: ADR-0018
title: Supply-Chain Security
status: Accepted
date: 2026-06-11
supersedes: []
superseded_by: []
audience: both
summary: "Release images carry SLSA build provenance (GitHub Artifact Attestations, keyless). Deploys can gate on verification: gh attestation verify for registry pulls, app:verify-attestation (k2gl/sigstore-verify, offline, fail-closed) for artifacts. Dependencies: composer audit hard gate; no Packagist attestation ecosystem yet."
---

# ADR-0018: Supply-Chain Security

**TL;DR:** Every pushed release image gets a SLSA build-provenance attestation
signed with the workflow's OIDC identity (no secrets to leak). Consumers can
verify *what was built, by which workflow, from which commit* before running
it. The skeleton dogfoods verification with `app:verify-attestation` on
`k2gl/sigstore-verify` — offline and fail-closed, proven by committed
fixtures including a tampered negative.

## Context

A template that tells forks to `docker pull` images and `composer install`
dependencies owes them an answer to "how do I know this is what CI built?".
The Sigstore ecosystem made the signing side free (GitHub Artifact
Attestations: pure YAML, keyless OIDC), and the k2gl package family provides a
pure-PHP, conformance-tested verifier — no cosign binary required.

## Decision

### Sign (CI, release.yml)

`actions/attest-build-provenance` attests the pushed image digest with
`permissions: id-token: write, attestations: write`. No keys are stored
anywhere; the signing identity IS the workflow run.

### Verify (three levels)

1. **Manual / CI**: `gh attestation verify oci://ghcr.io/<repo>:<tag> --repo <repo>`.
2. **Deploy gate (opt-in)**: `ops/rollout.sh` with `DEPLOY_REQUIRE_ATTESTATION=true`
   + `DEPLOY_IMAGE` + `DEPLOY_REPO` refuses to roll out a registry image whose
   provenance does not verify. Opt-in because the default `deploy.sh` flow
   builds images on the host (nothing in a registry to verify) — forks that
   pull release images flip it on.
3. **In-app (dogfood)**: `bin/console app:verify-attestation <artifact> <bundle.jsonl>`
   verifies any artifact's attestation offline against a pinned trusted root
   (`gh attestation trusted-root`), checking signature chain, transparency
   log, signer identity (repository + workflow + ref) and subject digest.
   Implementation: `src/Context/Platform/Features/VerifyAttestation/` on
   `k2gl/sigstore-verify` (passes the official sigstore-conformance suite).

### Fail-closed is proven, not claimed

The test suite commits a real signed artifact and asserts all three outcomes:
valid bundle verifies; a one-byte-tampered artifact is rejected; a wrong
signer identity is rejected. Offline — no network in tests.

### Dependencies (non-goal, for now)

`composer audit` is a hard CI gate. Verifying Packagist package signatures is
explicitly out of scope: there is no attestation ecosystem on Packagist yet —
verifying nothing against nothing is theater. Revisit when one exists.

## Consequences

**Positive:** anyone can answer "what produced this image" cryptographically;
the deploy gate turns provenance into an enforcement point; the verifier is
plain PHP (auditable, no binary downloads in CI).

**Negative:** one more dependency family (k2gl/sigstore-verify + phpseclib);
the pinned trusted root fixture needs a refresh if Sigstore rotates roots
(`gh attestation trusted-root > …`); local-build deploys remain trust-the-host.

## References

- `docs/guides/supply-chain.md` — step-by-step.
- [GitHub Artifact Attestations](https://docs.github.com/en/actions/security-for-github-actions/using-artifact-attestations)
- [k2gl/sigstore-verify](https://github.com/k2gl/sigstore-verify) — the verifier.
