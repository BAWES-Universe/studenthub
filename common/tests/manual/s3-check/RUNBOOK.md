# Proposed service: company-docs-s3-check

This runbook describes a temporary Railway service. It does not create that service, deploy it, or run AWS. Production service `studenthub` stays on deployed commit `ad96583`. Staging isolation is still unverified. Do not point this runner at staging and do not give it a database.

Real AWS verification stays pending until an operator runs the confirmed command below and the expected lines appear.

## Source and build

- Repository: the backend repository used by the `studenthub` service.
- Branch: `fix/company-s3-documents`.
- Reviewed source commit: the head of backend pull request 131 named there as the S3 check runner commit. That commit contains this runbook, `Dockerfile.company-docs-s3-check`, and the cleanup change in `common/tests/manual/SyntheticCompanyStorageCheck.php`. Do not build `ad96583`, `master`, or any other commit.
- Builder: Dockerfile.
- Dockerfile path: `Dockerfile.company-docs-s3-check`.
- Root directory: the repository root, the directory that contains that Dockerfile.
- Do not use `Dockerfile-nginx-railway` or any other application Dockerfile.
- The image entrypoint is `php common/tests/manual/SyntheticCompanyStorageCheck.php` with no arguments. That is the dry-run. The image does not run init, migrations, `deployment.sh`, cron, Nginx, or PHP-FPM.
- No credential is baked into an image layer. The image has no AWS environment values of its own.

## Service shape

- Name: `company-docs-s3-check`.
- Replicas: 1.
- Restart policy: Never.
- Automatic deployments: disabled.
- Public domain: none. Do not generate a Railway domain.
- Volume: none.
- Scheduled job: none.
- Healthcheck: none. Do not use an application healthcheck path.
- Database variables: none. Do not reference `DATABASE_URL`, `MYSQL_*`, or `DB_*`.

## Variables

Reference only these four names from the existing `studenthub` service. Do not duplicate the rest of the backend variables.

```
AWS_TEMP_UPLOAD_SIGNER_KEY=${{studenthub.AWS_TEMP_UPLOAD_SIGNER_KEY}}
AWS_TEMP_UPLOAD_SIGNER_SECRET=${{studenthub.AWS_TEMP_UPLOAD_SIGNER_SECRET}}
AWS_S3_RAILWAY_ACCESS_KEY_ID=${{studenthub.AWS_S3_RAILWAY_ACCESS_KEY_ID}}
AWS_S3_RAILWAY_SECRET_ACCESS_KEY=${{studenthub.AWS_S3_RAILWAY_SECRET_ACCESS_KEY}}
```

Sealed-variable references are not verified. A name being present on `studenthub` does not prove this reference resolves, and it does not prove the value is sealed.

Runtime resolution must succeed without revealing values. Do not print a variable to check it. The dry-run exits before it reads the four names, so a successful dry-run does not prove they resolved. The confirmed command reads them. If any name is missing or empty, the process prints `Missing environment:` plus those names and exits 2. That happens before it builds an S3 client and before any upload.

## Commands

Dry-run, and the image default when the start command is left empty:

```
php common/tests/manual/SyntheticCompanyStorageCheck.php
```

Expected: the plan text, exit 0, and no AWS request.

Confirmed test. On Railway, set the start command to only:

```
--confirm-buckets
```

That argument is appended to the image entrypoint. The resulting process is:

```
php common/tests/manual/SyntheticCompanyStorageCheck.php --confirm-buckets
```

Do not set a start command that launches PHP again. That would run the script path as a PHP argument.

## Synthetic objects

The exact names are chosen when the process runs. The log prints each `key=` line. The shapes are:

| Object | Bucket | Name |
| --- | --- | --- |
| Temporary licence | `studenthub-public-anyone-can-upload-24hr-expiry` | `privatelicence-synthetic-licence-check-{unix}-{16 hex}.png` |
| Permanent licence | `studenthub-uploads` | `private/company-commercial-licences/` plus that licence basename |
| Temporary logo | `studenthub-public-anyone-can-upload-24hr-expiry` | `synthetic-logo-check-{unix}-{16 hex}.png` |
| Permanent logo | `studenthub-uploads` | `company-logos/` plus that logo basename |

The body is a 1x1 PNG. The temporary licence is registered, then uploaded with ACL `private`, before promotion. The temporary logo is registered, then uploaded with ACL `public-read`, before promotion. Promotion uses `Company::updateLicence` and `Company::updateCompanyLogo`. No company row is read or saved. If the process can see a database, it stops with category `database refused`.

## Expected confirmed result

This result is not yet observed. When an operator runs the confirmed command, the log should show:

- `presign put synthetic-licence-check.png status=200` with `acl=private`
- anonymous temporary licence HEAD and GET `status=403`
- permanent temporary licence read `match=yes`
- `promoted licence key=private/company-commercial-licences/privatelicence-synthetic-licence-check-...`
- anonymous permanent licence HEAD and GET `status=403`
- permanent licence read `match=yes`
- `presign put synthetic-logo-check.png status=200` with `acl=public-read`
- anonymous temporary logo HEAD `status=200` and GET `match=yes`
- `promoted logo key=company-logos/synthetic-logo-check-...`
- anonymous permanent logo HEAD `status=200` and GET `match=yes`
- permanent stored logo read `match=yes`
- one `deleted key=` line for each of the four keys, each after a HEAD 404
- exit 0

A line that would contain a URL, an access key, or a signature is replaced with `redacted output suppressed`. The log must not contain those values.

These lines are SDK and presigned-request results. They are not a browser CORS check, an image-decode check, or a Staff or Admin login check.

## Cleanup

Cleanup runs in `finally` and uses only the permanent principal (`AWS_S3_RAILWAY_ACCESS_KEY_ID` / `AWS_S3_RAILWAY_SECRET_ACCESS_KEY`). The signer is not used for delete and is not granted `DeleteObject`.

A validated temporary key and its permanent target are added to the cleanup list before that PUT starts. An upload that S3 accepts, whose HTTP response then times out, is still on the list. An invalid key is not added, and its PUT does not start. A key that does not match the names above is refused and is not deleted.

Each successful delete prints `deleted key=` only after a following HEAD returns 404. `cleanup remaining` means the object is still there. Do not remove the service while that line is unexplained. `cleanup created=0` means nothing was registered.

## Remove the service

After the confirmed log shows four `deleted key=` lines and exit 0, delete the `company-docs-s3-check` service. Do not leave it deployed. Do not change the `studenthub` service, its variables, or its deployed commit.

This runbook does not authorize creating the service, deploying it, or executing the confirmed command.
