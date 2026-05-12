<?php

/**
 * Copy to: /home/ACCOUNT/private/smtp.config.php (one level ABOVE public_html)
 * chmod 600 smtp.config.php
 *
 * Secrets: prefer environment variables (set by host or deploy) — they override
 * keys below when non-empty. Standard names:
 *
 *   SMTP_HOST, SMTP_PORT, SMTP_SECURE (tls|ssl|empty), SMTP_USER, SMTP_PASSWORD,
 *   MAIL_FROM_EMAIL, MAIL_FROM_NAME, MAIL_TO, MAIL_TO_NAME,
 *   TURNSTILE_SECRET, REDIRECT_SUCCESS_URL, DEFAULT_MAIL_SUBJECT
 *
 * InterWorx: create a mailbox (e.g. forms@yourdomain.com), SMTP to mail.domain
 * (or host docs), port 587 + tls or 465 + ssl.
 */

return [
    'smtp_host' => 'mail.example.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => 'forms@example.com',
    'smtp_pass' => 'CHANGE_ME',

    'mail_from_email' => 'forms@example.com',
    'mail_from_name' => 'Website contact form',

    'mail_to' => 'staff@example.com',
    'mail_to_name' => 'Inbox',

    /** After classic (non-AJAX) form POST */
    'redirect_success_url' => '/contact/?sent=1',

    /** If set, POST must include cf-turnstile-response (env: TURNSTILE_SECRET) */
    'turnstile_secret' => '',

    /**
     * Optional: field names (lowercase) treated as honeypots — if any non-empty,
     * the handler returns fake success (anti-bot). Default includes company, website, url.
     */
    'honeypot_fields' => ['company', 'website', 'url', 'fax', 'phone_ext'],

    /** If visitor email is not in a field named email, set the exact field name here */
    'reply_to_field' => '',

    /** When false, forms without a detectable visitor email still send (no Reply-To) */
    'require_reply_email' => true,

    /** Used when the form does not send a subject field */
    'default_mail_subject' => 'Website form submission',
];
