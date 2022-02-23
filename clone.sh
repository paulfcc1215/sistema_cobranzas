#!/bin/bash

exit
rm -rf ../cobranzas2
echo "DROP DATABASE cobranzas_clone;" | psql -Upostgres
echo "CREATE DATABASE cobranzas_clone WITH TEMPLATE template0;" | psql -Upostgres

pg_dump --no-owner --no-privileges --no-tablespaces -Upostgres cobranzas | psql -Upostgres cobranzas_clone

rsync -livro * ../cobranzas2
rm -f ../cobranzas2/config.php
cp config_clone.php ../cobranzas2/config.php
chmod 777 ../cobranzas2/tmp
chmod -R 777 ../cobranzas2/tmp/*
