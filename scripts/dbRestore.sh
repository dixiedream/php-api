#!/bin/sh
set -e

composefile=${1:-docker-compose.yml}
dbname=${DB_DATABASE:-phpboilerplate}

echo "Extracting..."
[[ -f "./$dbname.sql.gz" ]] && gunzip "./$dbname.sql.gz"

echo "Restoring..."
docker-compose -f "$composefile" exec -T database sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" $DB_DATABASE' < "./$DB_DATABASE.sql"