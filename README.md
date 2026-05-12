# crservers.com — static site deploy (GitHub Actions)

Official **crservers.com** reusable workflow for sites we host: build a static export (for example Next.js `output: "export"`) and publish over **FTP/FTPS** using [SamKirkland/FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action).

This repository is maintained by **Edenia** for the **crservers.com** hosting product. Customer application repos stay thin: they call this workflow and supply FTP secrets.

Customer repos should set **`packageManager`** in `package.json` (for example `"packageManager": "pnpm@9.15.9"`). `pnpm/action-setup` reads that field and no separate pnpm version is passed from the workflow (avoids mismatch with a pinned major in CI).

## Usage (customer repository)

Add a workflow that references this repo (pin a **tag** such as `@v1` in production instead of `@main`):

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
    uses: edenia/crservers-static-deploy/.github/workflows/deploy-static-site.yml@v1
    secrets:
      FTP_HOST: ${{ secrets.FTP_HOST }}
      FTP_USER: ${{ secrets.FTP_USER }}
      FTP_PASSWORD: ${{ secrets.FTP_PASSWORD }}
      FTP_REMOTE_PATH: ${{ secrets.FTP_REMOTE_PATH }}
    with:
      site_url: ${{ vars.SITE_URL }}
```

Configure **Settings → Secrets and variables → Actions**:

- **Secrets** (required): `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`, `FTP_REMOTE_PATH`
- **Variables** (optional): `SITE_URL` — public URL such as `https://dev.example.com/` (not sensitive; shown in the deploy job summary)

Because this reusable workflow lives in **another** repository, the caller **cannot** use `secrets: inherit`. Map each secret under `secrets:` as shown above (same names on both sides).

### Required repository secrets

| Secret | Description |
|--------|-------------|
| `FTP_HOST` | crservers FTP/FTPS hostname |
| `FTP_USER` | FTP username |
| `FTP_PASSWORD` | FTP password |
| `FTP_REMOTE_PATH` | Remote directory — **must end with `/`** (for example `public_html/yoursite/`) |

### Optional repository variable

| Variable | Description |
|----------|-------------|
| `SITE_URL` | Pass as `site_url` input (`vars.SITE_URL` in the caller). Used only for the workflow summary link text — **FTP deploy does not require it**. |

### Manual runs (dry run / clean deploy)

Add `workflow_dispatch` and forward booleans using string comparisons (`github.event.inputs` values are strings):

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
    uses: edenia/crservers-static-deploy/.github/workflows/deploy-static-site.yml@v1
    secrets:
      FTP_HOST: ${{ secrets.FTP_HOST }}
      FTP_USER: ${{ secrets.FTP_USER }}
      FTP_PASSWORD: ${{ secrets.FTP_PASSWORD }}
      FTP_REMOTE_PATH: ${{ secrets.FTP_REMOTE_PATH }}
    with:
      dry_run: ${{ github.event.inputs.dry_run == 'true' }}
      clean_deploy: ${{ github.event.inputs.clean_deploy == 'true' }}
      site_url: ${{ vars.SITE_URL }}
```

On `push`, those comparisons are false because the inputs are absent.

### Callable workflow inputs (defaults)

| Input | Default | Notes |
|-------|---------|--------|
| `node_version` | `22` | Node used for `pnpm install` / `pnpm build` on the runner |
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
| `dry_run` | `false` | FTP no-op |
| `clean_deploy` | `false` | Wipes remote `FTP_REMOTE_PATH` |
| `site_url` | *(empty)* | Pass `vars.SITE_URL` from caller for summary |

## Publishing (Edenia / crservers.com)

Canonical remote:

`git@github.com:edenia/crservers-static-deploy.git`

### Repo already exists on GitHub (empty or with a README)

From your local clone of this directory:

```bash
cd crservers-static-deploy
git remote add origin git@github.com:edenia/crservers-static-deploy.git   # skip if origin already set
git branch -M main
git add -A && git status
git commit -am "crservers.com static site deploy reusable workflow"   # if you have local changes
git push -u origin main
git tag v1 && git push origin v1
```

If GitHub created a first commit (for example a default `README.md`) and `git push` is rejected, run `git fetch origin` and either merge with `git pull origin main --allow-unrelated-histories` and resolve conflicts, or coordinate with your team before any force push.

### Create the repo from scratch with GitHub CLI

```bash
cd crservers-static-deploy
git init
git add .
git commit -m "crservers.com static site deploy reusable workflow"
gh repo create edenia/crservers-static-deploy --public --source=. --remote=origin --push
git tag v1 && git push origin v1
```

Use a **public** repo if customer sites live in other GitHub orgs or accounts; otherwise callers cannot resolve `uses: edenia/crservers-static-deploy/...` unless you rely on Enterprise or org access you already control.

## What runs where

- **GitHub Actions:** Node runs only on the **runner** to install dependencies, run `next build`, and upload `out/` over FTP. Official actions are pinned to **v5+** / **FTP-Deploy v4.4+** so they use the **Node 24** action runtime (avoids the Node 20 deprecation on GitHub-hosted runners).
- **crservers (production):** Only the **static files** under your `FTP_REMOTE_PATH` are needed — typically **Apache** serves `index.html`, assets, and `.htaccess`. **You do not need Node.js on the hosting account** for this setup.

## Versioning

Tag stable commits (for example `v1`, `v1.0.0`) and pin customer workflows to that tag instead of `@main`.

## Security

- Do not pass untrusted user input into `install_command`, `build_command`, or `verify_command`.
- `clean_deploy` deletes the entire remote `FTP_REMOTE_PATH`; use only for intentional full resets.

## License

MIT (see `LICENSE`).
