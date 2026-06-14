#!/bin/sh

MYSQL_HOST="${MYSQL_HOST:-mysql.railway.internal}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_DATABASE="${MYSQL_DATABASE:-railway}"
: "${MYSQL_PASSWORD:?MYSQL_PASSWORD must be set in Railway environment variables.}"

run_mysql() {
  MYSQL_PWD="$MYSQL_PASSWORD" mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" "$@"
}

echo "Waiting for MySQL at $MYSQL_HOST:$MYSQL_PORT..."
for attempt in $(seq 1 60); do
  run_mysql -e "SELECT 1" >/dev/null 2>&1 && break
  echo "Attempt $attempt: retrying in 1s..."
  sleep 1
  [ "$attempt" -eq 60 ] && echo "MySQL not responding." && exit 1
done

echo "Disabling ONLY_FULL_GROUP_BY..."
run_mysql -e "
  SET GLOBAL sql_mode = REPLACE(@@GLOBAL.sql_mode, 'ONLY_FULL_GROUP_BY', '');
  SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '');
"


echo "Converting 'candidate' table to utf8mb4 for emoji support..."
run_mysql "$MYSQL_DATABASE" -e "
  ALTER TABLE candidate
  MODIFY candidate_intro TEXT
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

  ALTER TABLE job_interest
  MODIFY notes TEXT
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

  ALTER TABLE candidate
  MODIFY candidate_objective VARCHAR(255)
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
"
