# SQS EventManager Environment

`EventManager` SQS credentials must be supplied by the runtime environment, not
checked into environment config files.

Set these variables where SQS delivery is enabled:

- `AWS_SQS_REGION`
- `AWS_SQS_ACCESS_KEY_ID`
- `AWS_SQS_SECRET_ACCESS_KEY`
- `AWS_SQS_QUEUE`

For local relay setups that use the existing `sqsEndpoint` fallback path, set:

- `AWS_SQS_ENDPOINT`

The current config keeps the existing region and queue-name defaults so local
and CI environments do not need to know the secret values. If the access key or
secret is absent, `EventManager` already skips creating the AWS SQS client.
