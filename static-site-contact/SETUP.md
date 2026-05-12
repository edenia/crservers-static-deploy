# First-time setup (InterWorx / crservers static site)

Do this **once per SiteWorx account** (or whenever SMTP credentials change).  
Applies to **architect.cr** and **The Pork Shop** (same `utils/contact-form/` bundle).

## Fast path (most customers)

Read **`USERS-EASY-START.md`** — two short paths: **pre-provisioned files** on the account (run `install-on-server.sh` + edit one config file) **or** static export from GitHub then the same server steps.

**Canonical bundle to copy onto new accounts (Edenia ops):**  
[edenia/crservers-static-deploy — `static-site-contact/`](https://github.com/edenia/crservers-static-deploy/tree/main/static-site-contact)

---

## 1. SiteWorx: mailbox for sending

1. Log in to **SiteWorx** for the domain.
2. **Email → Add mailbox** (e.g. `forms@yourdomain.com`).
3. Note the password you set — this is the **SMTP password** (keep it secret).

Use your host’s SMTP hostname (often **`mail.yourdomain.com`** or the hostname InterWorx lists).  
Typical ports: **587** + **TLS (STARTTLS)**, or **465** + **SSL**.

---

## 2. Deploy the static site

Merge the GitHub PR and let Actions **FTP** the `out/` folder so the web root contains:

- `index.html`, assets, `.htaccess`, and **`contact.php`** (exported from `public/`).

---

## 3. SSH into the account (or terminal in the panel)

Replace `ACCOUNT` and paths with yours (`public_html` may differ).

```bash
cd ~/public_html
# Or: cd /home/ACCOUNT/public_html
```

Confirm **`contact.php`** is here (same directory as `index.html`).

---

## 4. Install PHPMailer (Composer)

Upload **`composer.json`** and **`composer.lock`** from the repo’s `utils/contact-form/` into this directory (if they are not already there from the deploy), then:

```bash
composer install --no-dev
```

You should see **`vendor/`** next to `contact.php`.  
If `composer` is not in PATH, use the full path your host documents (e.g. `/opt/cpanel/composer/bin/composer` — check InterWorx docs).

**Troubleshooting:** If Composer complains about PHP version, switch the domain to PHP 8.1+ in SiteWorx and retry.

---

## 5. Configure secrets (pick one pattern)

### Option A — Private PHP file (simple on shared hosting)

```bash
mkdir -p ~/private
cp /path/to/smtp.config.example.php ~/private/smtp.config.php
chmod 600 ~/private/smtp.config.php
nano ~/private/smtp.config.php   # set smtp_*, mail_from_*, mail_to, etc.
```

`contact.php` loads **`/home/ACCOUNT/private/smtp.config.php`** (one level above `public_html`).

### Option B — Environment variables (good if your host injects env into PHP)

Set in the panel or FPM pool, for example:

| Variable | Example |
|----------|---------|
| `SMTP_HOST` | `mail.yourdomain.com` |
| `SMTP_PORT` | `587` |
| `SMTP_SECURE` | `tls` |
| `SMTP_USER` | `forms@yourdomain.com` |
| `SMTP_PASSWORD` | *(mailbox password)* |
| `MAIL_FROM_EMAIL` | `forms@yourdomain.com` |
| `MAIL_FROM_NAME` | `Website form` |
| `MAIL_TO` | `you@yourdomain.com` |

Non-empty **env vars override** values from `smtp.config.php` when both exist.

### Option C — Hybrid

Put non-secrets in `smtp.config.php` and set **`SMTP_PASSWORD`** (and optionally `SMTP_USER`, `MAIL_TO`) only via env.

---

## 6. DNS / deliverability (same domain as From)

In **NodeWorx / DNS** for the domain:

- **SPF** should authorize the host that sends mail (your InterWorx server / PMG path — match what you already use for normal mail).
- Enable **DKIM** for the domain if the panel offers it.

This reduces spam-folder placement for form mail.

---

## 7. Smoke test

From your laptop (replace URL and use a real test body):

```bash
curl -sS -X POST 'https://yourdomain.com/contact.php' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H 'Accept: application/json' \
  -F 'email=test@example.com' \
  -F 'message=SMTP test from curl' \
  -F 'name=CLI'
```

Expect JSON: `{"ok":true,"error":""}`.  
Check the **`MAIL_TO`** inbox (and spam).

If **`require_reply_email`** is true (default), a valid **`email`** field (or `reply_to_field` in config) is required.

---

## 8. Front-end (already wired on architect.cr)

The site form must **POST** to **`/contact.php`** with a real **`email`** (and honeypots left empty).  
See **`OPERATOR.txt`** for JSON vs form, honeypots, and optional Turnstile.

**Generating forms in v0:** paste the prompt from **`utils/contact-form/V0-FORM-PROMPT.md`** (starts with “You are building…”) so the model matches headers, honeypot, and optional Turnstile.

The Pork Shop ships **`contact.php`** in the export; add a form when you want mail from that site.

---

## 9. Spam hardening (recommended)

Do **not** ship a public form with only a honeypot — bots will find it.

| Layer | What to do |
|-------|------------|
| **Turnstile** | Create a widget in Cloudflare Dashboard → Turnstile. Put **`site key`** in the front-end (e.g. `NEXT_PUBLIC_TURNSTILE_SITE_KEY`). Set **`TURNSTILE_SECRET`** (env) or **`turnstile_secret`** in `~/private/smtp.config.php`. Every successful POST must include field **`cf-turnstile-response`**. |
| **Honeypot** | Keep hidden trap fields (e.g. `company`) **empty** in real submissions. |
| **CDN / WAF** | If using Cloudflare, add managed / rate rules for **`POST /contact.php`** when possible. |
| **Watch mail** | If spam increases, enable Turnstile first, then tune hosting rules. |

**v0:** use the copy-paste prompt **`utils/contact-form/V0-FORM-PROMPT.md`** so generated React forms POST correctly to `/contact.php`.

---

## Checklist

- [ ] Mailbox created; SMTP host/port/TLS known  
- [ ] `contact.php` + `composer.json` + `composer.lock` in web root  
- [ ] `composer install --no-dev` → `vendor/` exists  
- [ ] `~/private/smtp.config.php` **or** env vars with SMTP + MAIL_TO  
- [ ] `chmod 600` on private config  
- [ ] curl test returns `ok: true`  
- [ ] Test message received  
- [ ] (Recommended) Turnstile site key + server secret if the form is public  
