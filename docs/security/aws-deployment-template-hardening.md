# AWS Deployment Template Hardening

The legacy `aws-template*.sh` helpers are public repository bootstrap scripts. They must not contain inline private-key placeholders or account-specific ECR registry URLs.

## Required Inputs

Set these values in the operator shell before running a template:

```sh
export GITHUB_DEPLOY_KEY_PATH=/secure/path/to/deploy-key
export GITHUB_DEPLOY_PUBLIC_KEY_PATH=/secure/path/to/deploy-key.pub
```

For the optional ECR commands in `aws-template-docker.sh`, set the registry target explicitly:

```sh
export AWS_ECR_ACCOUNT_ID=123456789012
export AWS_ECR_REGION=eu-west-2
export AWS_ECR_IMAGE=studenthub/backend-prod
export AWS_ECR_TAG=latest
```

`GITHUB_DEPLOY_KEY_PATH` is copied with mode `600` into the temporary deploy location used by the script. Do not paste private key material into the script or commit generated key files.

## Local Regression Check

Run:

```sh
python3 scripts/check-aws-template-hardening.py
```

The check fails if the deployment templates reintroduce inline GitHub key writes or account-specific ECR registry literals.
