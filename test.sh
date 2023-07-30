#!/bin/sh
#echo "In test script"
#check for root
passwrd = $0
echo "PASSWRD::$passwrd"
UID=$(id -u)
if [ x$UID != x0 ] 
then
    #Beware of how you compose the command
    printf -v cmd_str '%q ' "$0" "$@"
    exec sudo su -c "$cmd_str"
fi

#I am root
search_dir=/var/www/html/ocr/fax_documents
for entry in "$search_dir"/*.pdf
do
  echo "$entry"
done
#mkdir /opt/D3GO/
#and the rest of your commands