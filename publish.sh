#!/bin/bash
NOW=$(date +%Y%m%d%H%M%S)
NEWFILE="/srv/www/wordpress/xcopen_script/index_$NOW.html"
OLDFILE="/srv/www/wordpress/xcopen/index.html"
CHECK_STRING="XC Open"
/usr/bin/php /srv/www/wordpress/xcopen_script/calculate.php > "$NEWFILE"

if grep -q "$CHECK_STRING" "$NEWFILE"; then
    echo "New file contains check string. Overwriting old file."
    cp --force "$NEWFILE" "$OLDFILE"
fi

rm --force "$NEWFILE"
