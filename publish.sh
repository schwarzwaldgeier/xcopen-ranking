#!/bin/bash
NOW=$(date +%Y%m%d%H%M%S)
NEWFILE="/srv/www/wordpress/xcopen_script/index_$NOW.html"
OLDFILE="/srv/www/wordpress/xcopen/index.html"
CHECK_STRING="XC Open 2023 Live Ranking"
/usr/bin/php /srv/www/wordpress/xcopen_script/calculate.php > "$NEWFILE"

# check if NEWFILE contains CHECK_STRING
if grep -q "$CHECK_STRING" "$NEWFILE"; then
    # if so, overwrite OLDFILE with NEWFILE
    echo "New file contains check string. Overwriting old file."
    cp --force "$NEWFILE" "$OLDFILE"
fi

rm --force "$NEWFILE"