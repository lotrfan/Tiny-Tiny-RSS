#!/bin/bash

DIR=/mnt/ex/linux/backup/postgres.ttrss

dt=`date -I`

FILE=${DIR}/backup-${dt}.sql.gz

echo "Removing old backups"

find ${DIR} -name '*.sql.gz' | sort -r | tail -n+10 | grep '.*' | sed 's/^/rm /'
find ${DIR} -name '*.sql.gz' | sort -r | tail -n+10 | grep '.*' | xargs rm

echo "Backing up to ${FILE}"

pg_dump -o -U ttrss ttrss | gzip > ${FILE}
