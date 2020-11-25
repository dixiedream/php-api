#!/bin/sh
set -e

composefile=${1:-docker-compose.yml}
db=${DB_DATABASE:-phpboilerplate}

echo "Dumping database..."
docker-compose -f "$composefile" exec database sh -c 'exec mysqldump -p $DB_DATABASE -uroot -p"$MYSQL_ROOT_PASSWORD"' > "./$DB_DATABASE.sql"

echo "Compressing..."
gzip -f "./$db.sql"