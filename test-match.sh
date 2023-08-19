#!/bin/sh
#echo "In test script"
#check for root
passwrd = $0
#echo "PASSWRD::$passwrd"
UID=$(id -u)
if [ x$UID != x0 ] 
then
    #Beware of how you compose the command
    printf -v cmd_str '%q ' "$0" "$@"
    exec sudo su -c "$cmd_str"
fi

#I am root
search_dir=/var/www/html/ocr/test_fax_documents

for entry in "$search_dir"/*.pdf
do
  SUBSTRING=$(echo $entry| cut -d'.' -f 1)
  echo "$entry"
  echo "${SUBSTRING}"
  file_name=$(echo $SUBSTRING| cut -d'/' -f 7)
  echo "${file_name}"
  convert -density 300 $SUBSTRING.pdf -depth 8 -strip -background white -alpha off /var/www/html/ocr/test_engine_results/$file_name.tiff
  tesseract /var/www/html/ocr/test_engine_results/$file_name.tiff - -l eng txt > /var/www/html/ocr/test_engine_results/$file_name.txt
  #rm /var/www/html/ocr/engine_results/$file_name.tiff 
done
#mkdir /opt/D3GO/
#and the rest of your commands