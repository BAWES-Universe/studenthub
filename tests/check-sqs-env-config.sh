#!/usr/bin/env sh
set -eu

if git grep -n 'DOBNJ' -- environments common docs; then
  echo "Committed SQS access-key suffix still present." >&2
  exit 1
fi

if git grep -n 'AKIAWMITDJRKXNWDOBNJ' -- environments common docs; then
  echo "Committed SQS access key still present." >&2
  exit 1
fi

if git grep -n '"sqsKey" => "' -- environments; then
  echo "sqsKey must be read from AWS_SQS_ACCESS_KEY_ID, not a literal." >&2
  exit 1
fi

if git grep -n "'sqsKey' => '" -- environments; then
  echo "sqsKey must be read from AWS_SQS_ACCESS_KEY_ID, not a literal." >&2
  exit 1
fi

if git grep -n '"sqsSecret" => "' -- environments; then
  echo "sqsSecret must be read from AWS_SQS_SECRET_ACCESS_KEY, not a literal." >&2
  exit 1
fi

if git grep -n "'sqsSecret' => '" -- environments; then
  echo "sqsSecret must be read from AWS_SQS_SECRET_ACCESS_KEY, not a literal." >&2
  exit 1
fi

for var in AWS_SQS_REGION AWS_SQS_ACCESS_KEY_ID AWS_SQS_SECRET_ACCESS_KEY AWS_SQS_QUEUE; do
  if ! git grep -q "$var" -- environments docs; then
    echo "$var is not referenced in SQS config/docs." >&2
    exit 1
  fi
done
