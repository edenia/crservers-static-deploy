#!/usr/bin/env bash
# crservers.com — one-time setup for contact.php (run from public_html, next to contact.php)
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

if [[ ! -f contact.php ]] || [[ ! -f composer.json ]]; then
  echo "Run this from the folder that contains contact.php and composer.json (usually ~/public_html)." >&2
  exit 1
fi

if command -v composer >/dev/null 2>&1; then
  echo "Installing PHPMailer into vendor/ …"
  composer install --no-dev --no-interaction
elif [[ -f composer.phar ]]; then
  echo "Installing PHPMailer via composer.phar …"
  php composer.phar install --no-dev --no-interaction
else
  echo "Composer not found on this account." >&2
  echo "Option A — on your Mac/Linux, from this same folder run:" >&2
  echo "  curl -sS https://getcomposer.org/installer | php" >&2
  echo "  php composer.phar install --no-dev --no-interaction" >&2
  echo "  Then upload the new vendor/ directory next to contact.php." >&2
  echo "Option B — ask your host to enable the Composer CLI for your SSH user." >&2
  exit 1
fi

PRIVATE="$(cd .. && pwd)/private"
mkdir -p "$PRIVATE"
CFG="$PRIVATE/smtp.config.php"
if [[ ! -f "$CFG" ]]; then
  cp smtp.config.example.php "$CFG"
  chmod 600 "$CFG" || true
  echo ""
  echo "Created $CFG — edit it with your mailbox password, mail_to, and SMTP host if needed."
else
  echo "Config already exists: $CFG (left unchanged)"
fi

echo ""
echo "Done. Test with your live contact form or curl (see SETUP.md §7)."
