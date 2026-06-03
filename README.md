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
| `FTP_REMOTE_PATH` | Remote directory **relative to the FTP account home** — **must end with `/`** (see [InterWorx paths](#interworx--crservers-ftp-paths) below; e.g. `example.com/html/`) |

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

## New site checklist (customer repo)

Use this when onboarding a new static or Next.js customer repository.

1. **Next.js static export** — in `next.config` (or `next.config.mjs`):
   - `output: 'export'`
   - `images: { unoptimized: true }` if the app uses `next/image`
2. **`package.json`** — `packageManager` (matches `pnpm-lock.yaml`), `verify:static-out` (e.g. `test -f out/index.html`)
3. **`public/.htaccess`** — Apache rules for shared hosting (see [below](#recommended-publichtaccess-next-static-export))
4. **Caller workflow** — `.github/workflows/deploy-static-site.yml` calling `edenia/crservers-static-deploy/.../deploy-static-site.yml@v1` with FTP secrets mapped explicitly
5. **GitHub Actions** — secrets `FTP_*`; optional variable `SITE_URL` (public URL for deploy summaries only)
6. **First deploy** — run workflow with **dry_run** once, then production; confirm the live site (not only a green Actions run)
7. **Optional contact form** — copy **`static-site-contact/`** into `public/` (or pre-provision on the server), configure SMTP per **`USERS-EASY-START.md`**

## InterWorx / crservers FTP paths

On **InterWorx** shared hosting, the FTP user is usually **chrooted to the account home** (e.g. `/home/ACCOUNT/`). [FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action) treats `FTP_REMOTE_PATH` as **relative to that FTP root**, not as an absolute path on the server.

### Use a relative path (required)

| `FTP_REMOTE_PATH` value | Result |
|-------------------------|--------|
| `example.com/html/` | Correct — files land in `/home/ACCOUNT/example.com/html/` |
| `/home/ACCOUNT/example.com/html/` | Wrong — uploads often go outside the tree you see over SSH; Actions may still succeed |
| `/home/ACCOUNT/html/` | Wrong for the primary domain — often the account default “Test Page”, not the domain vhost |

**Rule:** Set `FTP_REMOTE_PATH` to the domain’s web root **relative to the account home**, with a trailing slash. Confirm in **SiteWorx** (domain → home / document root) or on the server:

```bash
# SSH (paths on disk)
ls /home/ACCOUNT/DOMAIN/html/

# After a successful deploy you should see at least:
#   index.html  _next/  .htaccess
```

### Typical layout (primary domain)

```text
/home/ACCOUNT/                 ← FTP login root
├── html/                      ← account default page (crservers “Test Page”) — usually NOT the live domain
└── DOMAIN/                    ← e.g. example.com/
    ├── html/                  ← document root for https://DOMAIN/  ← deploy here
    └── iworx-backup/
```

Some accounts use `domains/DOMAIN/html/` instead of `DOMAIN/html/`. Always take the path from SiteWorx or `ls` on the server, then express it **relative to `/home/ACCOUNT/`** in the secret.

### Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Actions deploy succeeds; live URL still shows crservers “Test Page” | Wrong `FTP_REMOTE_PATH` (wrong folder or absolute path) |
| SSH into `…/DOMAIN/html/` shows only placeholder files (`crservers-logo.*`, old `index.html`) | Deploy never hit that directory — fix path and redeploy |
| `find /home/ACCOUNT -name '_next' -type d` finds `_next` under `html/` or a nested `home/…` path | Stray upload from an absolute path — safe to delete after fixing the secret |

**Verify the correct folder:** after deploy, `index.html` in the domain `html/` should be small (static export) and include `_next/`. Check the public URL `Last-Modified` or page title changes.

**Verify the public site:** `curl -sI https://DOMAIN/ | grep -i last-modified` and confirm content matches the app (not the default hosting page).

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

### Contact forms (SMTP mail for static sites)

Static exports cannot send mail from the browser alone. For **contact / inquiry forms**, crservers hosts a small **PHP + PHPMailer** endpoint that authenticates to an **InterWorx mailbox** over SMTP.

| Piece | Location |
|-------|----------|
| Canonical bundle | **`static-site-contact/`** in this repo |
| Edenia ops (rsync / pre-provision) | **`static-site-contact/README-EDENIA-OPS.md`** |
| Customer setup | **`static-site-contact/USERS-EASY-START.md`** → `install-on-server.sh`, then **`../private/smtp.config.php`** (or env vars) |
| Front-end contract / v0 prompt | **`static-site-contact/V0-FORM-PROMPT.md`**, **`OPERATOR.txt`** |

**Deploy with the static site:** copy `contact.php`, `composer.json`, and `composer.lock` into the app’s **`public/`** so `pnpm build` places them in **`out/`** and the [FTP workflow](#usage-customer-repository) uploads them to the domain `html/` (see [InterWorx paths](#interworx--crservers-ftp-paths)). On the server, run **`composer install --no-dev`** once in that directory (or use **`install-on-server.sh`** after Edenia pre-copies the bundle).

**Secrets:** never commit SMTP passwords. Use **`~/private/smtp.config.php`** and/or hosting env vars (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASSWORD`, `MAIL_TO`, optional `TURNSTILE_SECRET`). Optional **Cloudflare Turnstile** for public forms.

Customer repos may mirror the bundle under **`utils/contact-form/`**; treat **`static-site-contact/`** here as the source of truth (`CANONICAL-SOURCE.txt`).

### Recommended `public/.htaccess` (Next static export)

Commit this next to your app as **`public/.htaccess`** so it is copied into **`out/.htaccess`** on build. Directives are wrapped in **`IfModule`** so missing modules do not break the site.

```apache
# Static Next export on Apache (crservers / shared hosting)
DirectoryIndex index.html
Options -Indexes

# Compression when the host has mod_deflate (no-op if missing)
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml
  AddOutputFilterByType DEFLATE text/css application/javascript application/json
  AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>

# Long cache for Next.js fingerprinted assets under /_next/static/
<IfModule mod_headers.c>
  <LocationMatch "^/_next/static/">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </LocationMatch>
</IfModule>

# Expires for common image/font types (complements Cache-Control above)
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/avif "access plus 30 days"
  ExpiresByType image/webp "access plus 30 days"
  ExpiresByType image/jpeg "access plus 30 days"
  ExpiresByType image/png "access plus 30 days"
  ExpiresByType image/gif "access plus 30 days"
  ExpiresByType image/svg+xml "access plus 30 days"
  ExpiresByType font/woff2 "access plus 180 days"
  ExpiresByType font/woff "access plus 180 days"
</IfModule>
```

## Versioning

Tag stable commits (for example `v1`, `v1.0.0`) and pin customer workflows to that tag instead of `@main`.

## Security

- Do not pass untrusted user input into `install_command`, `build_command`, or `verify_command`.
- `clean_deploy` deletes the entire remote `FTP_REMOTE_PATH`; use only for intentional full resets.

## License

MIT (see `LICENSE`).
