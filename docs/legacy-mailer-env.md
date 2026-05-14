Legacy mailer environment configuration
=======================================

The legacy `prod` and `dev-server*` Yii environment configs no longer keep
SES or ElasticEmail SMTP credentials in source control.

Set these variables in the runtime environment for any deployment that should
send email:

- `MAIL_SCHEME` - defaults to `smtp`
- `MAIL_HOST` - defaults to `smtp.resend.com`
- `MAIL_USERNAME` - defaults to `resend`
- `MAIL_PASSWORD` - required for real delivery
- `MAIL_PORT` - defaults to `587`
- `MAIL_ENCRYPTION` - defaults to `tls`

Do not commit AWS SES SMTP usernames, SMTP passwords, or SMTP DSNs to
environment config files. Keep rotated values in Railway, server environment
variables, or the selected secrets manager.
