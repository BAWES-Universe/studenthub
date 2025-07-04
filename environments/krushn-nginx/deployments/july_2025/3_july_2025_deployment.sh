#!/bin/sh

MYSQL_HOST="mysql"
MYSQL_PORT=3306
MYSQL_USER="root"
MYSQL_PASSWORD="studenthub"

echo "Waiting for MySQL at $MYSQL_HOST:$MYSQL_PORT..."
for attempt in $(seq 1 60); do
  mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1" >/dev/null 2>&1 && break
  echo "Attempt $attempt: retrying in 1s..."
  sleep 1
  [ "$attempt" -eq 60 ] && echo "MySQL not responding." && exit 1
done

echo "Disabling ONLY_FULL_GROUP_BY..."
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "
  SET GLOBAL sql_mode = REPLACE(@@GLOBAL.sql_mode, 'ONLY_FULL_GROUP_BY', '');
  SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '');
"

echo "Converting 'candidate' table to utf8mb4 for emoji support..."
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "
  ALTER TABLE candidate
  MODIFY candidate_intro TEXT
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
"
