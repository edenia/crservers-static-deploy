# v0 prompt — contact form → `/contact.php` (crservers static sites)

**How to use:** Copy everything **from “You are building…”** through the end of this file into **v0** (or your AI editor) when you want a contact / inquiry form that posts to our **PHP mail endpoint** on the live static host. Do **not** ask v0 to add SMTP secrets or Turnstile **secret** — those live only on the server.

---

## You are building…

A **Next.js (App Router)** page section with a **client-side contact form** for a site that is **exported as static HTML** (`output: "export"`) and hosted on **Apache / InterWorx (crservers)**. The backend is **`/contact.php`** in the site root (same origin as the site). There is **no** Next.js API route — the browser talks **only** to `/contact.php`.

### Hard requirements

1. **Endpoint:** `POST` to **`/contact.php`** (same origin — use relative URL `/contact.php`).

2. **AJAX contract:** Use `fetch` with:
   - `method: "POST"`
   - Headers: **`X-Requested-With: XMLHttpRequest`** and **`Accept: application/json`**
   - Body: **`FormData`** built from the form element (preferred), **or** `JSON.stringify` with `Content-Type: application/json` if the payload is flat key/value only.

3. **Response:** The server returns **JSON** `{ "ok": boolean, "error": string }`. On `ok === false` or non-2xx, show `error` to the user. On success, show a thank-you state and reset the form.

4. **Visitor email (Reply-To):** Include a visible, required field **`name="email"`** with `type="email"` so the server can set Reply-To. If the product copy uses another label, keep **`name="email"`** on the input.

5. **Optional subject line:** If the design has a subject, use **`name="subject"`** (single line, reasonable max length). Omit if not needed.

6. **Honeypot (anti-bot):** Add **exactly one** hidden decoy field the user never sees:
   - Wrapper: visually hidden (e.g. `absolute -left-[9999px]` or `sr-only` pattern), **`aria-hidden="true"`**
   - **`<input type="text" name="company" id="company" tabIndex={-1} autoComplete="off" />`**
   - **Do not** collect real “company” data in this field — pick another `name` like `organization` for real company name if needed.
   - The field must stay **empty** on submit.

7. **Secrets:** Never put **SMTP passwords**, **Turnstile secret**, or **mail API keys** in React code or the repo. Only **Turnstile site key** may appear as a public env var (e.g. `NEXT_PUBLIC_TURNSTILE_SITE_KEY`).

8. **Optional Cloudflare Turnstile:** If the project has a **site key** env var:
   - Render Turnstile (e.g. `@marsidev/react-turnstile` or Cloudflare’s embed) and obtain a token before submit.
   - Append to `FormData`: **`cf-turnstile-response`** = token string.
   - If there is no site key in env, **omit** Turnstile and do not send `cf-turnstile-response` (server must not require Turnstile until ops sets `TURNSTILE_SECRET` on the host).

9. **UX:** Disable submit while loading; show inline error region for server messages; accessible labels and `aria-invalid` / `role="alert"` where appropriate.

10. **Other fields:** Any additional names are fine (`message`, `phone`, `firstName`, `projectType`, etc.) — they are emailed as key/value lines. Avoid keys starting with **`__`** (reserved). Do not use honeypot reserved names for real data: **`company`**, **`website`**, **`url`**, **`fax`**, **`phone_ext`** unless they are the honeypot / empty traps per ops config.

### Reference (do not paste secrets into v0)

- Server behavior and env vars: repo file **`utils/contact-form/OPERATOR.txt`**
- First-time hosting setup: **`utils/contact-form/SETUP.md`**

### Out of scope for v0

- Creating or editing **`contact.php`**, **`vendor/`**, or **`private/smtp.config.php`** — that is server / repo ops after deploy.

---

## Minimal `fetch` shape (for your implementation)

```tsx
const res = await fetch("/contact.php", {
  method: "POST",
  body: fd, // FormData from form, including optional cf-turnstile-response
  headers: {
    "X-Requested-With": "XMLHttpRequest",
    Accept: "application/json",
  },
  credentials: "same-origin",
})
const data = (await res.json()) as { ok?: boolean; error?: string }
```

---

## Checklist before merging generated UI

- [ ] `action` is not needed if using `fetch` + `preventDefault` — do not double-submit.
- [ ] `name="email"` present and required.
- [ ] Honeypot `name="company"` present and hidden.
- [ ] Turnstile token added to `FormData` only when a site key exists.
- [ ] No secrets in source.
