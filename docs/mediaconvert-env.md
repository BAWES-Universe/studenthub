# MediaConvert Environment

MediaConvert access credentials must be supplied by the runtime environment,
not checked into environment config files.

Set these variables anywhere MediaConvert still uses key-and-secret auth:

- `AWS_MEDIACONVERT_ACCESS_KEY_ID`
- `AWS_MEDIACONVERT_SECRET_ACCESS_KEY`

For Railway-backed environments, use the Railway-scoped variables already
expected by the deployment configs:

- `AWS_MEDIACONVERT_RAILWAY_ACCESS_KEY_ID`
- `AWS_MEDIACONVERT_RAILWAY_SECRET_ACCESS_KEY`

The existing IAM-role based MediaConvert environments do not need these values.
The key-based environments keep their existing region, endpoint, role, and job
queue configuration, but now fail closed if the runtime credentials are not
provided.
