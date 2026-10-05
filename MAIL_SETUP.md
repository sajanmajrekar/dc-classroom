# Transactional email setup

The API sends mail through the DigiChefs mail endpoint described in `Mail Api.pdf`.

- Default endpoint: `https://digichefs.in/sajan/mail.php`
- Request format: JSON `POST` with `to`, `subject`, and HTML `body`.
- Optional server environment variable: `MAIL_API_URL`, if the mail endpoint changes.

Deploy `api/mail.php` with the API files. Ensure PHP cURL is enabled in cPanel. Account creation and class assignment continue even if email delivery fails; the failure is logged using PHP's `error_log`, and the admin UI shows a notification warning.

New-user emails include the submitted password once. Passwords are still stored only as secure hashes in the database.
