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
  SUBSTRING=$(echo $entry| cut -d'.' -f 1)
  echo "$entry"
  echo "${SUBSTRING}"
  file_name=$(echo $SUBSTRING| cut -d'/' -f 5)
  echo "${file_name}"
  #convert -density 300 $SUBSTRING.pdf -depth 8 -strip -background white -alpha off $SUBSTRING.tiff
  #tesseract 1.tiff - -l eng txt
done
#mkdir /opt/D3GO/
#and the rest of your commands