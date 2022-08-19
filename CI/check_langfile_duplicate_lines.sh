#!/bin/sh

. CI/Import/Functions.sh

if [ -z "${GHRUN}" ]
then
  langfiles=$(git diff --cached --name-only --diff-filter=ACM -- '*.lang')
else
  langfiles=$(get_changed_lang_files)
fi

for file in $langfiles
do
  duplicates=$(cat ${file} | php -r '$c=explode("\n",file_get_contents("php://stdin"));array_multisort($c);file_put_contents("php://stdout",join("\n",$c));' | uniq -d | grep -v "^/*\*")
  if [ ! -z "$duplicates" ]
  then
    echo "Duplicate entries in ${file}: ${duplicates}"
    exit 127
  fi
done
exit 0