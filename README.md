# static-site-deploy

Reusable GitHub Actions workflow for Edenia / **crservers** customers: build a static site (e.g. Next.js `output: "export"`) and deploy with **FTP/FTPS** via [SamKirkland/FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action).

## Usage

In the **customer repository**, add a workflow that calls this repo (replace the ref with a **pinned tag** once you publish one, e.g. `@v1`):

```yaml
name: Deploy static site

on:
  push:
    branches: [main]

permissions:
  contents: read
  actions: write

jobs:
  deploy-site:
    uses: edenia/static-site-deploy/.github/workflows/deploy-static-site.yml@main
    secrets: inherit
    with:
      deployment_url: ${{ vars.SITE_URL }}
```

### Required secrets (repository or environment)

| Secret | Description |
|--------|-------------|
| `FTP_HOST` | FTP/FTPS hostname |
| `FTP_USER` | FTP username |
| `FTP_PASSWORD` | FTP password |
| `FTP_REMOTE_PATH` | Remote directory — **must end with `/`** (e.g. `public_html/site/`) |

Use `secrets: inherit` so the caller forwards these to the reusable workflow.

### Optional repository variables

| Variable | Description |
|----------|-------------|
| `SITE_URL` | Pass as `deployment_url` so the GitHub **environment** link points at the live site |

### Manual runs (dry run / clean deploy)

Add `workflow_dispatch` and forward booleans as strings (`github.event.inputs` is always string-valued):

```yaml
on:
  workflow_dispatch:
    inputs:
      dry_run:
        type: boolean
        default: false
      clean_deploy:
        type: boolean
        default: false

jobs:
  deploy-site:
    uses: edenia/static-site-deploy/.github/workflows/deploy-static-site.yml@main
    secrets: inherit
    with:
      dry_run: ${{ github.event.inputs.dry_run == 'true' }}
      clean_deploy: ${{ github.event.inputs.clean_deploy == 'true' }}
      deployment_url: ${{ vars.SITE_URL }}
```

On `push`, those comparisons are false because the inputs are absent.

### Callable workflow inputs (defaults)

| Input | Default | Notes |
|-------|---------|--------|
| `node_version` | `20` | |
| `pnpm_version` | `9` | |
| `install_command` | `pnpm install --frozen-lockfile` | Trusted maintainer input |
| `build_command` | `pnpm build` | Trusted maintainer input |
| `verify_command` | `pnpm run verify:static-out` | Skipped if `skip_verify: true` |
| `skip_verify` | `false` | |
| `artifact_name` | `static-out` | |
| `artifact_path` | `out/` | |
| `artifact_retention_days` | `7` | |
| `ftp_protocol` | `ftps` | |
| `ftp_local_dir` | `./out/` | Must end with `/` |
| `ftp_timeout_ms` | `1200000` | |
| `production_environment` | `production` | GitHub Environment name |
| `deployment_url` | *(empty)* | e.g. `vars.SITE_URL` from caller |
| `dry_run` | `false` | FTP no-op |
| `clean_deploy` | `false` | Wipes remote `FTP_REMOTE_PATH` |

## Publishing (Edenia)

Create `github.com/edenia/static-site-deploy` from this tree (paths shown match a local clone next to a client site):

```bash
cd edenia-static-site-deploy
git init
git add .
git commit -m "Initial reusable static site deploy workflow"
gh repo create edenia/static-site-deploy --public --source=. --remote=origin --push
git tag v1 && git push origin v1
```

Use a **public** repo if customer sites live in other GitHub orgs or accounts; otherwise callers cannot resolve `uses: edenia/static-site-deploy/...` unless you use Enterprise access controls you already trust.

## Versioning

Tag stable commits (e.g. `v1`, `v1.0.0`) and pin customer workflows to that tag instead of `@main`.

## Security

- Do not pass untrusted user input into `install_command`, `build_command`, or `verify_command` inputs.
- `clean_deploy` deletes the entire remote `FTP_REMOTE_PATH`; use only for intentional full resets.

## License

MIT
