#!/bin/sh
#echo "In test script"
#check for root
passwrd = $0
UID=$(id -u)
if [ x$UID != x0 ] 
then
    #Beware of how you compose the command
    printf -v cmd_str '%q ' "$0" "$@"
    exec sudo su -c "$cmd_str"
fi

#I am root

#mkdir /opt/D3GO/
#and the rest of your commands