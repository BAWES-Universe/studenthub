# SES mailer environment variables

Production mailer credentials must be provided through runtime environment
variables. Do not commit SES SMTP usernames, SMTP passwords, or access key
suffixes to repository config files.

The Nginx production config reads:

- `MAIL_HOST` (defaults to `email-smtp.eu-west-1.amazonaws.com`)
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_PORT` (defaults to `587`)

The Railway production config already reads the same `MAIL_*` variables for
its mail transport. If SES SMTP is restored there, keep it env-backed and do
not reintroduce commented credential examples.

After changing mailer config, run:

```sh
sh tests/check-ses-mailer-env-config.sh
```
