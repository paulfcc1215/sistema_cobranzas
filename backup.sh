#!/bin/sh
echo "Proceso Iniciado"
export PGUSER='postgres'
export PGPASSWORD='postgres'
# Seteamos el numero de backups a mantener
let NUMBACKUPS=20
FECHA=`date +%Y-%m-%d`
HORA_ACTUAL=`date +%H%M%S`
SERVIDOR=10.1.210.26

echo "Iniciando Backup de BDD"
cd /tmp/
pg_dump -h $SERVIDOR -F c cobranzas > cobranzas-$FECHA-$HORA_ACTUAL.backup
echo "Fin Backup de BDD"
#eLIMINAMOS
unset PGUSER
unset PGPASSWORD

#respaldo de app
echo "Iniciando respaldo de APP"
tar -czvf cobranzas-$FECHA-$HORA_ACTUAL.tar.gz /opt/www/cobranzas/
echo "Fin respaldo de APP"

echo "Moviendo respaldos a NASS"
cp cobranzas-$FECHA-$HORA_ACTUAL.tar.gz /mnt/cobranzas/
cp cobranzas-$FECHA-$HORA_ACTUAL.backup /mnt/cobranzas/

#echo "Limpiando temporales"
rm cobranzas-$FECHA-$HORA_ACTUAL.backup
rm cobranzas-$FECHA-$HORA_ACTUAL.tar.gz
#echo "Fichero temporal borrado"

#Nos movemos a la carpeta de red compartida para guardar los respaldos
cd /mnt/cobranzas/
#echo "Verificamos que cumplan el numero maximo de backups"
let CANT=`ls | wc -l`
let TOTAL=$CANT-$NUMBACKUPS

echo "Borramos los ficheros mas antiguos"
        for i in `ls -t| tail -$TOTAL`
        do
        rm -rf $i
        done
echo "Ficheros antiguos borrados"
echo "Proceso finalizado correctamente!!"
