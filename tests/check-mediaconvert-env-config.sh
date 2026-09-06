#!/usr/bin/env sh
set -eu

if git grep -n -E 'WKGYOFLT|5STO55KF' -- environments docs; then
  echo "Committed MediaConvert access-key suffix still present." >&2
  exit 1
fi

if git grep -n -E 'AWS_MEDIACONVERT(_RAILWAY)?_(ACCESS_KEY_ID|SECRET_ACCESS_KEY).*(AKIA[0-9A-Z]{16}|[A-Za-z0-9+/=]{30,})' -- environments docs tests; then
  echo "MediaConvert env documentation or config appears to contain literal credential material." >&2
  exit 1
fi

if awk '
  /mediaConvert/ && /=>/ && /\[/ { in_media_convert = 1; next }
  in_media_convert && /^[[:space:]]*\]/ { in_media_convert = 0 }
  in_media_convert && /["\047](key|secret)["\047][[:space:]]*=>[[:space:]]*["\047][^"\047$][^"\047]*["\047]/ {
    print FILENAME ":" FNR ":" $0
    found = 1
  }
  END { exit found ? 0 : 1 }
' environments/*/common/config/main-local.php; then
  echo "MediaConvert config appears to contain a literal key/secret assignment." >&2
  exit 1
fi

for env in circle-ci docker krushn krushn-nginx; do
  config="environments/${env}/common/config/main-local.php"

  for var in AWS_MEDIACONVERT_ACCESS_KEY_ID AWS_MEDIACONVERT_SECRET_ACCESS_KEY; do
    if ! git grep -q -E "getenv\\(['\"]${var}['\"]\\)" -- "$config"; then
      echo "$var is not wired through getenv() in $config." >&2
      exit 1
    fi
  done
done

for env in dev-server-railway prod-railway; do
  config="environments/${env}/common/config/main-local.php"

  for var in AWS_MEDIACONVERT_RAILWAY_ACCESS_KEY_ID AWS_MEDIACONVERT_RAILWAY_SECRET_ACCESS_KEY; do
    if ! git grep -q -E "getenv\\(['\"]${var}['\"]\\)" -- "$config"; then
      echo "$var is not wired through getenv() in $config." >&2
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

for var in AWS_MEDIACONVERT_RAILWAY_ACCESS_KEY_ID AWS_MEDIACONVERT_RAILWAY_SECRET_ACCESS_KEY; do
  if ! git grep -q "$var" -- docs/mediaconvert-env.md; then
    echo "$var is not referenced in docs/mediaconvert-env.md." >&2
    exit 1
  fi
done
