select 
 concat('mysql_status,class=', 
 ifnull(hc.name,'NotAssigned'),
 ',host=', 
 h.name,
 ',socket=', 
 mi.socket, 
 case when socket like '/var/mysql/%/mysql.sock' 
 then 
 concat(',instance=',replace(replace(socket,'/var/mysql/',''),'/mysql.sock','')) 
 else ''
 end,
 ' ',
 ifnull(msh.name,ms.name),
 '=',
 
 case when msh.value is null THEN 
 
 
 (
 case when ms.value REGEXP '^-?[0-9]+$' AND ms.value < 9023372036854775807
  then concat(ms.value,'i') 
when ms.value REGEXP '^-?[0-9]+$' AND ms.value < 9023372036854775807  THEN
  concat('"',ms.value,'"')
when ms.value REGEXP '([0-9]*[.])?[0-9]+'
  then ms.value 
 else 
  concat('"',ms.value,'"') end

)
 
 ELSE
 
 
 (
 case when msh.value REGEXP '^-?[0-9]+$' AND ms.value < 9023372036854775807
  then concat(msh.value,'i') 
when msh.value REGEXP '^-?[0-9]+$' AND ms.value < 9023372036854775807  THEN
  concat('"',msh.value,'"')
when msh.value REGEXP '([0-9]*[.])?[0-9]+'
  then msh.value 
 else 
  concat('"',msh.value,'"') end

)
 
 end
 
 , 
 
 ' ', 
 UNIX_TIMESTAMP(ifnull(msh.last_poll_dt,ms.last_poll_dt))*1000000000) as influxDB 
from 
 host h left outer join host_class hc on h.host_class_id = hc.id,
 mysql_instance mi, 
 mysql_status ms left outer join mysql_status_history msh on ms.id = msh.mysql_status_id
where
 h.id = mi.host_id AND 
 mi.id = ms.instance_id