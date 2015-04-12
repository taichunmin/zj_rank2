#!/bin/bash

# 0 */3 * * * /home/taichunmin/zj_rank2/cron.sh 1>/home/taichunmin/zj_rank2/last.log 2>&1
# */10 18-23 * * 2,4 /home/taichunmin/zj_rank2/cron.sh 1>/home/taichunmin/zj_rank2/last.log 2>&1

PWD=$( cd "$( dirname "$0" )" && pwd )
LOCKFILE="$PWD/cron.lock"

if [ -e ${LOCKFILE} ] && kill -0 `cat ${LOCKFILE}`; then
    echo "already running"
    exit
fi

# make sure the lockfile is removed when we exit and then claim it
trap "rm -f ${LOCKFILE}; exit" INT TERM EXIT
echo $$ > ${LOCKFILE}

# do stuff
/usr/bin/php "$PWD/updateGoogleSheetRank.php"

rm -f ${LOCKFILE}
