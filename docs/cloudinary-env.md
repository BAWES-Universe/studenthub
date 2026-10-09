# Cloudinary environment variables

Cloudinary upload credentials must be supplied by the runtime environment, not committed in application config.

Set these variables before using `cloudinaryManager`:

- `CLOUDINARY_CLOUD_NAME`
- `CLOUDINARY_API_KEY`
- `CLOUDINARY_API_SECRET`

If any Cloudinary variable is unset, the application leaves that credential as `null` so Cloudinary-dependent operations fail closed instead of using committed or partial credentials.

Rotate any Cloudinary API secret that was previously committed before using this deployment config.
