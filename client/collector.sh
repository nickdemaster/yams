#!/bin/bash
# 
# The MIT License (MIT)
# Copyright (c) 2016 Nick DeMaster
# 
# Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
# 
# 


hostname="/tmp/"`hostname`
base64host=$(echo `hostname` | base64)
datacener="[string]"
apikey="[generate own API Key]"
consumerAddress="https://[ipaddress:port]/consume.php"

MYSQL_BIN=`which mysql`

COUNTER=0

echo "{"  > "$hostname.json"

#open document

echo "\"server_stats\": {" >> "$hostname.json"


# get distribution
echo `hostname` | awk '{ print "\"hostname\": \"" $1 "\"," }' >> "$hostname.json"

if hash dmidecode 2>/dev/null; then

# get chassis information
dmidecode -s system-manufacturer  | awk '{ print "\"system_manufacturer\": \"" $0 "\"," }' >> "$hostname.json"
dmidecode -s system-product-name  | awk '{ print "\"system_product_name\": \"" $0 "\"," }' >> "$hostname.json"
dmidecode -s system-serial-number  | awk '{ print "\"system_serial_number\": \"" $0 "\"," }' >> "$hostname.json"

# processor
dmidecode -s processor-version | uniq | sed 's/ \+/ /g' | awk '{ print "\"cpu_model\": \"" $0 "\"," }' >> "$hostname.json"
else
if [ -f /proc/cpuinfo ]; then
awk -F':' '/^model name/ { print "\"cpu_model\": \"" $2 "\"," }' /proc/cpuinfo | uniq |  sed -e  's/ \+/ /g' -e  's/\" /\"/g'  >> "$hostname.json"
fi
fi



# get platform
uname -p | awk '{ print "\"platform\": \"" $0 "\"," }'  >> "$hostname.json"

# get platform	
uname -r | awk '{ print "\"kernel\": \"" $0 "\"," }'  >> "$hostname.json"



if hash lsb_release 2>/dev/null; then

# get distribution
lsb_release -is | awk '{ print "\"distribution\": \"" $1 "\"," }' >> "$hostname.json"

# get description
lsb_release -ds | awk '{ print "\"description\": \"" $0 "\"," }' | sed -e 's/\"\"/\"/g' >> "$hostname.json"

# get release
lsb_release -rs | awk '{ print "\"release\": \"" $1 "\"," }' >> "$hostname.json"

# get codename
lsb_release -cs | awk '{ print "\"codename\": \"" $1 "\"," }' >> "$hostname.json"

else

# get distribution

for f in /etc/*-release; do

    [ -e "$f" ] && cat /etc/*-release | uniq | awk '{ print "\"distribution\": \"" $1 "\",\n"  "\"description\": \"" $0 "\",\n"  "\"release\": \"" $3 "\",\n"  "\"codename\": \"" $0 "\",\n" }' | sed '$ s/.$//' >> "$hostname.json" || echo ""

    break
done


fi

echo "\"poll_time\": \""`date +%s`"\"" >> "$hostname.json"

echo "}," >> "$hostname.json"


# get memory
echo "\"memory_usage\": {" >> "$hostname.json"
if hash free 2>/dev/null; then
free -m | grep Mem | awk '{ print "\"used\": \"" $3 "\",\n" "\"total\": \"" $2 "\"" }' >> "$hostname.json"
fi
echo "}," >> "$hostname.json"

# disk stats
echo "\"disk\": [{" >> "$hostname.json"
#df  | grep -v Filesystem | sed -e 's/ \+/ /g' | awk '{ print " \""$NF"\": { \"name\": \"" $1 "\", \"used_space\": \"" $3 "\", \"total_space\":\" "$4"\" }," }' | sed '$ s/.$//' >> "$hostname.json" 
df | awk  -v col1="Filesystem" -vcol2="Used" -vcol3="Available" 'NR==1{for(i=1;i<=NF;i++){ if($i==col1)c1=i; if ($i==col2)c2=i; if ($i==col3)c3=i;}} NR>1{print " \""$NF"\": { \"name\": \"" $c1 "\", \"used_space\": \"" $c2 "\", \"total_space\":\" "$c3"\" }," }' | sed '$ s/.$//' >> "$hostname.json" 

echo "}]," >> "$hostname.json"

# memory modules
echo "\"memory_modules\": [{" >> "$hostname.json"

if hash dmidecode 2>/dev/null; then
dmidecode -t 17 | awk -F: '/Size|Locator|Speed|Manufacturer|Serial Number|Part Number/{sub(/^ */,"",$2);s=sprintf("%s,\"%s\"",s,$2)}/^Memory/{print s;s=""}END{print s}' |sed -e 's/,//' |sed -e '1d'| tr -d ' ' | grep -v NoModuleInstalled |  awk -F"," ' { print $2 ": { \"manufacturer\": " $5 ", \"part_number\": " $7  ", \"size\": " $1 ", \"speed\": " $4 ", \"serial_number\": " $6  " }," } ' | sed -e 's/\:\ \,/\:\ \"\"\,/g' -e 's/\:\ \ \}/\: \"\"\ \}/g' -e '$ s/.$//' >> "$hostname.json"
fi

echo "}]," >> "$hostname.json"


# network stats
echo "\"network\": [{" >> "$hostname.json"

if hash ip 2>/dev/null; then
ip addr | grep -v  host | awk '$1 == "inet" {gsub(/\/.*$/, "", $2); print "\"" $2 "\": { \"priority\": \"" $(NF-1) "\", \"interface\" : \"" $(NF) "\" }," }' | sed '$ s/.$//' >> "$hostname.json"
fi


echo "}]," >> "$hostname.json"


# mysql stats
echo "\"mysql\": [" >> "$hostname.json"
echo "{" >> "$hostname.json"


# find mysql socks running
for i in `netstat -ln | awk '/mysql(.*)?\.sock/ { print $9 }' | sort`; 
  do
 
    if [ $COUNTER -gt 0 ];  then 
      echo "}," >> "$hostname.json"
    fi
    
    
    echo "" >>  "$hostname.json"
    echo "\"$COUNTER\": {" >> "$hostname.json"
 
 
    echo "\"mysql_variables\": [" >> "$hostname.json"
    echo "{" >> "$hostname.json"
    # get server variables  
    $MYSQL_BIN --socket=$i --skip-column-names -s  --execute "show global variables" | awk '{ print "\"" $1 "\": \"" $2 "\"," }' | sed '$ s/.$//'  >> "$hostname.json"
  
    
    echo "}" >> "$hostname.json"
    echo "]," >> "$hostname.json"
 
    echo "\"mysql_status\": [" >> "$hostname.json"
    echo "{" >> "$hostname.json"
    # get server variables  
    $MYSQL_BIN --socket=$i --skip-column-names -s  --execute "show global status" | awk '{ print "\"" $1 "\": \"" $2 "\"," }' | sed '$ s/.$//'  >> "$hostname.json"

    
    echo "}" >> "$hostname.json"
    echo "]," >> "$hostname.json"
   
    echo "\"mysql_replication\": [" >> "$hostname.json"
    echo "{" >> "$hostname.json"
    
    $MYSQL_BIN --socket=$i --table  --silent --execute="show slave status\G" | grep -v row |  awk  ' { print "\"" $1 "\"" ": "  "\"" $2 "\" ,"  }' | sed 's/:\":/\":/g' | sed '$ s/.$//' >> "$hostname.json" 
    
    echo "}" >> "$hostname.json"
    echo "]" >> "$hostname.json"
    
    COUNTER=$[COUNTER + 1]

done

echo "}" >> "$hostname.json"
echo "}" >> "$hostname.json"
echo "]" >> "$hostname.json"
echo "}" >> "$hostname.json"

curl -k --data-urlencode "file@$hostname.json" -H "xa1:$base64host" -H "xd1:$datacenter" -H "xk1:apikey" $consumerAddress