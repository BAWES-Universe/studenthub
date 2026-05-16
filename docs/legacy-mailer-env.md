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

Production mailer behavior
--------------------------

The production mailer reads all SMTP transport values from the environment.
With no overrides, the transport points at Resend's SMTP endpoint:

```php
'transport' => [
    'scheme' => getenv('MAIL_SCHEME') ?: 'smtp',
    'host' => getenv('MAIL_HOST') ?: 'smtp.resend.com',
    'username' => getenv('MAIL_USERNAME') ?: 'resend',
    'password' => getenv('MAIL_PASSWORD') ?: '',
    'port' => (int)(getenv('MAIL_PORT') ?: 587),
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
],
```

`MAIL_PASSWORD` intentionally has no usable default. A deployment can boot
without it, but real email delivery requires setting it through Railway,
server environment variables, or a secrets manager.

Provider examples
-----------------

AWS SES, eu-west-1:

```text
MAIL_SCHEME=smtp
MAIL_HOST=email-smtp.eu-west-1.amazonaws.com
MAIL_USERNAME=<AWS_SES_SMTP_USERNAME>
MAIL_PASSWORD=<AWS_SES_SMTP_PASSWORD>
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

ElasticEmail:

```text
MAIL_SCHEME=smtp
MAIL_HOST=smtp.elasticemail.com
MAIL_USERNAME=<elasticemail_username>
MAIL_PASSWORD=<elasticemail_api_key>
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
```

Mailgun:

```text
MAIL_SCHEME=smtp
MAIL_HOST=smtp.eu.mailgun.org
MAIL_USERNAME=postmaster@<domain>
MAIL_PASSWORD=<mailgun_smtp_password>
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

SendGrid:

```text
MAIL_SCHEME=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_USERNAME=apikey
MAIL_PASSWORD=<sendgrid_api_key>
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

Day-1 production checklist
--------------------------

- Confirm all `MAIL_*` variables are present in the production environment.
- Confirm `MAIL_PASSWORD` is set to a live provider secret, not a placeholder.
- Send one verification email from the target environment after deploy.
- Rotate any SMTP credential that was ever committed, even if the commit was
  later removed.
