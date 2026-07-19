#!/bin/sh
set -eu

TEST_DATABASE="${MYSQL_TEST_DATABASE:-retro_sphere_testing}"

mysql \
    --protocol=socket \
    -uroot \
    -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${TEST_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES
    ON \`${TEST_DATABASE}\`.*
    TO '${MYSQL_USER}'@'%';

FLUSH PRIVILEGES;
SQL