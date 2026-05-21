#!/usr/bin/env bash
#
# Runs once on first container start (via /docker-entrypoint-initdb.d).
# Creates the dedicated testing database and grants the app user access,
# so the PHPUnit suite (DatabaseTransactions, see tests/TestCase.php) runs
# against MySQL without touching local dev data.

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_testing\`;
    GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_testing\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
