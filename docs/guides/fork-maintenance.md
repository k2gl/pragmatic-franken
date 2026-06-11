---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "How forks track the template: release-notes-driven manual porting (UPGRADE.md), optional cherry-picking via a skeleton remote, and the fresh-fork drill checklist used to validate every release."
---

# Fork maintenance

A project built from a template diverges immediately — `git merge` from
upstream stops being realistic after the first week. What works instead:

## 1. Release-notes-driven porting (default)

Watch the skeleton's releases (GitHub → Watch → Custom → Releases). Each
release ships a short [`UPGRADE.md`](../../UPGRADE.md) entry — typically "3
files changed, here's why". Apply by hand in minutes; skip what doesn't fit
your fork.

## 2. Cherry-pick via a skeleton remote (surgical)

```bash
git remote add skeleton https://github.com/k2gl/pragmatic-franken.git
git fetch skeleton
git log --oneline skeleton/main -- dev/ docker/   # see what moved
git cherry-pick <sha>                              # take exactly what you need
```

Works best for self-contained files (`dev/*.sh`, workflows, `docker/`), worst
for `src/` you've rewritten.

## 3. The fresh-fork drill (how releases are validated)

Every release of the skeleton itself must pass this checklist on a clean fork
with **zero secrets configured** — if you maintain an internal template fork,
adopt the same gate:

1. `gh repo create tmp-fork --template k2gl/pragmatic-franken --clone`
2. `make install` → `make smoke` → `make ci`
3. Push a trivial PR → **all** workflows green
4. `make init name=tmp-app prune=1` → `make ci` still green
5. Delete the fork.

Failures are release blockers, not known issues.
