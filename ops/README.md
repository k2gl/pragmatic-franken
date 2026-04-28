# ops/

Deployment and SRE-facing scripts. Audience: CI/CD pipelines and operators.

| Script | Purpose |
|---|---|
| `deploy.sh` | Reference deployment pipeline (git pull, composer, docker build, migrations, health check loop). Adapt per environment before use. |
