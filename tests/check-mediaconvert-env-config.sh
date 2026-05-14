#!/usr/bin/env sh
set -eu

if git grep -n -E 'WKGYOFLT|5STO55KF' -- environments docs; then
  echo "Committed MediaConvert access-key suffix still present." >&2
  exit 1
fi

if git grep -n -E 'AWS_MEDIACONVERT_(ACCESS_KEY_ID|SECRET_ACCESS_KEY).*(AKIA|[A-Za-z0-9+/]{30,})' -- environments docs tests; then
  echo "MediaConvert env documentation or config appears to contain literal credential material." >&2
  exit 1
fi

for env in circle-ci dev-server-railway docker krushn krushn-nginx prod-railway; do
  config="environments/${env}/common/config/main-local.php"

  for var in AWS_MEDIACONVERT_ACCESS_KEY_ID AWS_MEDIACONVERT_SECRET_ACCESS_KEY; do
    if ! git grep -q "$var" -- "$config"; then
      echo "$var is not referenced in $config." >&2
      exit 1
    fi
  done
done

for var in AWS_MEDIACONVERT_ACCESS_KEY_ID AWS_MEDIACONVERT_SECRET_ACCESS_KEY; do
  if ! git grep -q "$var" -- docs/mediaconvert-env.md; then
    echo "$var is not referenced in docs/mediaconvert-env.md." >&2
    exit 1
  fi
done
