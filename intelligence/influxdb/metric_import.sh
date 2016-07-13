#!/bin/bash

for i in `ls influxImport`; do 

 echo $i;
 curl -i -XPOST 'http://localhost:8086/write?db=yams' --data-binary @"influxImport/$i"; 

done