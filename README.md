# TLO Registry — Philippine Science High School, Caraga Region Campus

A PHP + MySQL dashboard for the Technology Licensing Office to log, filter,
chart, edit, delete, and export intellectual-property filings.

## What's included

```
tlo-registry/
├── schema.sql              ← import this into MySQL first
├── config.php               ← set your DB host/user/password here
├── index.php                 (redirects to login or dashboard)
├── login.php
├── signup.php
├── logout.php
├── dashboard.php             (the main app — requires login)
├── includes/
│   └── auth.php              (session helpers)
├── api/
│   ├── _filters.php          (shared filter-building logic)
│   ├── entries.php            (CRUD JSON API — list/add/edit/delete)
│   ├── chart_data.php         (aggregated JSON for the charts)
│   ├── export_csv.php         (CSV download, respects active filters)
│   └── export_pdf.php         (PDF download, respects active filters)
├── vendor/
│   ├── fpdf.php               (PDF generation library)
│   └── font/                  (core font metrics needed by fpdf.php)
└── assets/
    ├── css/style.css
    └── js/dashboard.js
```

## Requirements

- PHP 8.0+ with the **PDO** and **pdo_mysql** extensions enabled
- MySQL 5.7+ or MariaDB 10.3+
- A regular Apache/Nginx + PHP-FPM host, or `php -S` for local testing

## Setup

1. **Create the database.**
   ```bash
   mysql -u root -p < schema.sql
   ```
   This creates a `tlo_registry` database with a `users` table and an
   `ip_entries` table (see `schema.sql` for the exact columns).

2. **Point the app at your database.** Edit `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'tlo_registry');
   define('DB_USER', 'root');
   define('DB_PASS', 'your-password-here');
   ```

3. **Upload the whole folder** to your PHP host (e.g. `public_html/tlo-registry/`),
   or run it locally for testing:
   ```bash
   php -S localhost:8000
   ```
   then open `http://localhost:8000/login.php`.

4. **Create an account.** Click "Create one" on the login page, fill in the
   signup form, then sign in. There is no seeded admin account — the first
   person to sign up simply creates their own login.

## Using the dashboard

- **Add an entry** — fill in the "Add new entry" form (Employee Number,
  Month/Day/Year, IP Name, Application Number, Application Code, Status,
  Amount Paid, IP Type, Mode of Technology Transfer, Title of the IP) and
  click **Save entry**.
- **Edit an entry** — click **Edit** on any table row; the form switches into
  edit mode (title changes, a "Cancel edit" button appears). Click
  **Update entry** to save, or **Cancel edit** to discard.
- **Delete an entry** — click **Delete** on a row; you'll be asked to confirm.
- **Filter** — every variable (Employee Number, Month, Day, Year, IP Name,
  Application Number, Application Code, Status, Amount Paid range, IP Type,
  Mode of Transfer, Title of IP) can be filtered from the filter bar at the
  top. Filters apply to the table, both charts, the summary cards, and the
  CSV/PDF exports at the same time. Click **Clear all** to reset.
- **Charts** —
  - *Line graph*: number of filings per month/year, honoring active filters.
  - *Bar graph (IP Type)*: entry count for each of the five IP types.
  - *Bar graph (Mode of Transfer)*: entry count for each of the four transfer
    modes.
- **Download CSV / Download PDF** — exports exactly the rows that match your
  current filters (or everything, if no filters are set).

## Security notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt).
- All SQL uses parameterized PDO queries — no raw string concatenation.
- API endpoints require an active login session (`require_login_api()`).
- Consider serving the app over HTTPS in production and setting
  `session.cookie_secure` / `session.cookie_httponly` in `php.ini`.

## Customizing dropdown values

`config.php` defines three constants used to build every dropdown/filter
option, and to validate incoming data on the server:

```php
const IP_TYPES = ['Trademark', 'Copyright', 'Industrial Design', 'Utility Model', 'Patent'];
const TRANSFER_MODES = ['Commercialization', 'Deployment', 'Extension', 'No Transfer'];
const STATUS_OPTIONS = ['Filled', 'Registered', 'Formality examination', 'Substantive examination'];
```

Edit these arrays if your office uses different labels. If you change
`IP_TYPES` or `TRANSFER_MODES`, also update the matching `ENUM(...)` values
in `schema.sql` (and run an `ALTER TABLE` on an existing database).

## Tested

This build was tested end-to-end against a live MySQL instance: signup,
login, session-protected dashboard access, creating/editing/deleting
entries, filtering by every field, chart aggregation, and both CSV and PDF
downloads all verified working.
