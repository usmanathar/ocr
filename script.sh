#!/bin/bash
sshpass -p the_proxy@OCR sftp -oBatchMode=no -b - ocr_lrwic@OCR23.122.104.252 << !
   cd /var/www/html/public_html/faxFiles/facility_21
   
   ls -l
   #get *.pdf
   bye
!