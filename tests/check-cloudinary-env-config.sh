#!/usr/bin/env sh
set -eu

config="common/config/main.php"
doc="docs/cloudinary-env.md"
status=0

for pattern in \
  "'cloud_name'[[:space:]]*=>[[:space:]]*getenv\\('CLOUDINARY_CLOUD_NAME'\\)[[:space:]]*\\?:[[:space:]]*null" \
  "'api_key'[[:space:]]*=>[[:space:]]*getenv\\('CLOUDINARY_API_KEY'\\)[[:space:]]*\\?:[[:space:]]*null" \
  "'api_secret'[[:space:]]*=>[[:space:]]*getenv\\('CLOUDINARY_API_SECRET'\\)[[:space:]]*\\?:[[:space:]]*null"
do
  if ! git grep -qE "$pattern" -- "$config"; then
    echo "Missing expected Cloudinary env wiring in $config: $pattern" >&2
    status=1
  fi
done

if git grep -nE "'api_(key|secret)'[[:space:]]*=>[[:space:]]*['\"][^'\"]+['\"]" -- "$config"; then
  echo "Hard-coded Cloudinary credential remains in $config" >&2
  status=1
fi

for var in CLOUDINARY_API_KEY CLOUDINARY_API_SECRET CLOUDINARY_CLOUD_NAME; do
  if ! grep -q "$var" "$doc"; then
    echo "$doc must document $var" >&2
    status=1
  fi
done

exit "$status"
