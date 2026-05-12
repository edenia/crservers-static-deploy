# Edenia / crservers — pre-copy `static-site-contact` onto InterWorx

This folder is the **canonical** mail-endpoint bundle for static sites on **crservers.com** (Apache + PHP-FPM + SMTP auth). Customer **Next.js** repos copy from here into `utils/contact-form/` when we change PHP behavior; **new hosting accounts** can receive this folder **as-is** so users only run one script and edit one config file.

## What to copy to a new account

From a clone of **edenia/crservers-static-deploy**, sync into the account’s **document root** (usually `~/public_html/`):

```bash
# Example: from your workstation (replace HOST and remote path)
rsync -avz --delete-excluded \
  --exclude '.git' \
  ./static-site-contact/ 'USER@HOST:~/public_html/'
```

Prefer **`--delete-excluded`** only if you intend to mirror exactly; otherwise omit `--delete` to avoid removing unrelated site files. **Safer** pattern — copy only bundle files:

```bash
rsync -avz \
  ./static-site-contact/contact.php \
  ./static-site-contact/composer.json \
  ./static-site-contact/composer.lock \
  ./static-site-contact/install-on-server.sh \
  ./static-site-contact/smtp.config.example.php \
  ./static-site-contact/USERS-EASY-START.md \
  'USER@HOST:~/public_html/'
```

Then SSH:

```bash
ssh USER@HOST 'cd ~/public_html && chmod +x install-on-server.sh && bash install-on-server.sh'
```

## After copy

- **`install-on-server.sh`** runs `composer install --no-dev` and creates **`../private/smtp.config.php`** from the example if missing.
- Tell the customer to **edit `../private/smtp.config.php`** (mailbox password, `mail_to`, etc.) or set **env vars** if you inject secrets at the panel level.

## Keeping in sync

When **`contact.php`** or Composer pins change:

1. Update **`static-site-contact/`** in this repo (PR + tag if needed).
2. Re-copy to accounts that need the fix **or** ship updates via customer site rebuilds that include `utils/contact-form/` from the same commit.

See **`CANONICAL-SOURCE.txt`** in the customer repo `utils/contact-form/` for the same pointer.
