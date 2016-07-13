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


<?php

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_input_time', 300);
ini_set('max_execution_time', 300);

include '../internal/db.connect.php';

$posthost = $_SERVER['REMOTE_ADDR'];

error_log('getting headers');

$headers = array();
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
    }
}

error_log('headers_done');
//print_r($headers);
 
function is_json($string,$return_data = false) {
  $data = json_decode($string);
     return (json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $data : TRUE) : FALSE;
}

error_log('checking file');

error_log($_POST['file']);

if (!isset($_POST['file'])) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated - file not set'); exit(header("Status: 200 OK"));}

error_log('file set, moving on');


// checks if valid json, if not die
$response = is_json($_POST['file'], false);

error_log($response);

if ( $response != 1) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));} else {$json = json_decode($_POST['file'], true);}



$json_host_name = $json['server_stats']['hostname'];

// header legend priority
//  k1: explicit apikey
//  a1: base64 for hostname, check if decoded value matches $json['server_stats']['hostname'];
//  d1: location


error_log($headers['Xa1']);
error_log($headers['Xd1']);
error_log($headers['Xk1']);

if (!isset($headers['Xa1'])) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));}
if (!isset($headers['Xd1'])) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));}
if (!isset($headers['Xk1'])) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));}

// check if base64 hostheader

$base64_host_name = preg_replace('/[\r\n]+/', '', base64_decode($headers['Xa1']));

//error_log($base64_host_name;


//error_log('base64 length:' . strlen($base64_host_name) . PHP_EOL;
//error_log('json length: ' .strlen($json_host_name) . PHP_EOL;


error_log($apikey);

//error_log($json_host_name . PHP_EOL;

if ($headers['Xk1'] !== $apikey) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));}
if ($base64_host_name !== $json_host_name) {error_log('php-myinv: ('. $posthost. ') [ERROR] POST not sufficiently authenticated'); exit(header("Status: 200 OK"));}


$datacenter = $headers['Xd1'];

   

    $dc_select_sql = "select id from datacenter where name = ?;";
	
	$dc_select = $link->prepare($dc_select_sql);
	$dc_select->bind_param('s', $datacenter);
	$dc_select->execute();
	$result = $dc_select->get_result();
	$dc_select->free_result();
	
	while($row = $result->fetch_assoc()){
	  $datacenter_id=$row['id'];
    }

    if (!isset($datacenter_id)) {return;}


    // get base stats
    
	$json_poll_time = $json['server_stats']['poll_time'];
    if( isset($json['server_stats']['system_manufacturer']) ) { $json_system_manufacturer = $json['server_stats']['system_manufacturer']; }
    if( isset($json['server_stats']['system_product_name']) ) { $json_system_product_name = $json['server_stats']['system_product_name']; }
    if( isset($json['server_stats']['system_serial_number']) ) { $json_system_serial_number = $json['server_stats']['system_serial_number']; }
    if( isset($json['server_stats']['cpu_model']) ) { $json_cpu_model = $json['server_stats']['cpu_model']; }
    if( isset($json['server_stats']['platform']) ) { $json_platform = $json['server_stats']['platform']; }
    if( isset($json['server_stats']['distribution']) ) { $json_distribution = $json['server_stats']['distribution']; }
    if( isset($json['server_stats']['description']) ) { $json_description = $json['server_stats']['description']; }
    if( isset($json['server_stats']['release']) ) { $json_release = $json['server_stats']['release']; }
	if( isset($json['server_stats']['kernel']) ) { $json_kernel = $json['server_stats']['kernel']; }
    if( isset($json['server_stats']['codename']) ) { $json_codename = $json['server_stats']['codename']; }
	    
    //error_log("looking for host..." . PHP_EOL;
	
    $hostname_select_sql = "select `id` from `host` where `name` = ? and datacenter_id = ?;";
	
	// check if host exists, if so set host_id
	
	$hostname_select = $link->prepare($hostname_select_sql);
	$hostname_select->bind_param('si', $json_host_name, $datacenter_id);
	$hostname_select->execute();
	$result = $hostname_select->get_result();
	//$hostname_select->store_result();
	$numrows = $result->num_rows;
	$hostname_select->free_result();	

    ////error_log($numrows;

    while($row = $result->fetch_assoc()){
	  $host_id=$row['id'];
    }

// if host_id is not set, insert host information into database and get insert id
if ($numrows == 0) {
    
    //error_log("no host found... inserting host" . PHP_EOL;
    
    $hostname_insert_sql = "INSERT INTO host (`datacenter_id`, `name`, `system_manufacturer`, `system_product_name`, `system_serial_number`, `cpu_model`, `platform`, `distribution`, `description`, `release`, `kernel`, `codename`, `last_poll_dt`  ) VALUES (?, ?,?,?,?,?,?,?,?,?,?,?, FROM_UNIXTIME(?))";
	
	$hostname_insert = $link->prepare($hostname_insert_sql);
	$hostname_insert->bind_param('isssssssssssi', $datacenter_id, $json_host_name, $json_system_manufacturer, $json_system_product_name, $json_system_serial_number, $json_cpu_model, $json_platform, $json_distribution, $json_description, $json_release, $json_kernel, $json_codename, $json_poll_time);
	$hostname_insert->execute();
	$host_id = $hostname_insert->insert_id;
	$hostname_insert->free_result();

    //error_log('host inserted - new host id: '.$host_id. PHP_EOL;
	
	
} else {

    // if host exists, get current settings with this select.
	
	//error_log("host found... checking rows" . PHP_EOL;
    
    $host_variables_sql = "select `name`, `system_manufacturer`, `system_product_name`, `system_serial_number`, `cpu_model`, `platform`, `distribution`, `description`, `release`, `kernel`, `codename`, `last_poll_dt` from host where id = ?;";
	
	$host_variables = $link->prepare($host_variables_sql);
	$host_variables->bind_param('i', $host_id);
	$host_variables->execute();
	$result = $host_variables->get_result();
	$host_variables->free_result();
	
	while($row = $result->fetch_assoc()){

        $current_name = $row['name'];
        $current_system_manufacturer = $row['system_manufacturer'];
        $current_system_product_name = $row['system_product_name']; 
        $current_system_serial_number = $row['system_serial_number']; 
        $current_cpu_model = $row['cpu_model'];
        $current_platform = $row['platform'];
        $current_distribution = $row['distribution'];
        $current_description = $row['description'];
        $current_release = $row['release'];
		$current_kernel = $row['kernel'];
        $current_codename = $row['codename'];
		$current_poll_time = $row['last_poll_dt'];
        
        # for the row result, compare value.  If all values are equal, do nothing.  If any value differs, insert current values into history table, then update values
        
        if ( $json_system_manufacturer == $current_system_manufacturer &&
             $json_system_serial_number == $current_system_serial_number && 
             $json_cpu_model == $current_cpu_model &&
             $json_platform == $current_platform &&
             $json_distribution == $current_distribution && 
             $json_description == $current_description && 
             $json_release == $current_release && 
			 $json_kernel == $current_kernel &&
			 $json_codename == $current_codename
             
             ) { 
			 	$hostname_insert_sql = "UPDATE host SET `last_poll_dt` = FROM_UNIXTIME(?) WHERE id = ?";
	
				$hostname_insert = $link->prepare($hostname_insert_sql);
				$hostname_insert->bind_param('ii',  $json_poll_time, $host_id);
				$hostname_insert->execute();
				$hostname_insert->free_result();
			 
			 } else { 
               
			   //error_log("changes found... inserting host into history" . PHP_EOL;
             
			 
                $hostname_insert_sql = "INSERT INTO host_history (`host_id`, `datacenter_id`, `name`, `system_manufacturer`, `system_product_name`, `system_serial_number`, `cpu_model`, `platform`, `distribution`, `description`, `release`, `kernel`, `codename`, `last_poll_dt`  ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, ?)";
	
				$hostname_insert = $link->prepare($hostname_insert_sql);
				$hostname_insert->bind_param('iissssssssssss', $host_id, $datacenter_id, $current_host_name, $current_system_manufacturer, $current_system_product_name, $current_system_serial_number, $current_cpu_model, $current_platform, $current_distribution, $current_description, $current_release, $current_kernel, $current_codename, $current_poll_time);
				$hostname_insert->execute();
				$hostname_insert->free_result();

        
		 	//error_log("updating host entry" . PHP_EOL;
		 
		 
                $hostname_insert_sql = "UPDATE host SET `name` = ?, `system_manufacturer` = ?, `system_product_name` = ?, `system_serial_number` = ?, `cpu_model` = ?, `platform` = ?, `distribution` = ?, `description` = ?, `release` = ?, `kernel` = ?, `codename`  = ?, `modified_dt = now(), ``last_poll_dt` = FROM_UNIXTIME(?) WHERE id = ?";
	
				$hostname_insert = $link->prepare($hostname_insert_sql);
				$hostname_insert->bind_param('sssssssssssii', $json_host_name, $json_system_manufacturer, $json_system_product_name, $json_system_serial_number, $json_cpu_model, $json_platform, $json_distribution, $json_description, $json_release, $json_kernel, $json_codename, $json_poll_time, $host_id);
				$hostname_insert->execute();
				$hostname_insert->free_result();
			
			}

    } // end while 

}  // end else

//error_log("host done" . PHP_EOL;

//error_log("starting memory" . PHP_EOL;
	// get memory usage
	
    
	if ( isset($json['memory_usage']['used']) ) { $json_memory_used = $json['memory_usage']['used']; }
	if ( isset($json['memory_usage']['total']) ) { $json_memory_total = $json['memory_usage']['total']; }
    
	if (!isset($json_memory_used)) {} else {
    
	//error_log("getting memory values" . PHP_EOL;
	
    $ramusage_select_sql = "select used, total from host_memory where host_id = ?;";
	
	$ramusage_select = $link->prepare($ramusage_select_sql);
	$ramusage_select->bind_param('i', $host_id);
	$ramusage_select->execute();
	$result = $ramusage_select->get_result();
	$numrows = $result->num_rows;
	$ramusage_select->free_result();
	
	while($row = $result->fetch_assoc()){
	  $current_memory_used = $row['used'];
	  $current_memory_total = $row['total'];
    }
    
  
  
	if( $numrows > 0 ){
		
		//error_log("memory found" . PHP_EOL;
		
		if ( $current_memory_used == $json_memory_used && $current_memory_total == $json_memory_total ) {
			
			//error_log("no changes to memory" . PHP_EOL;
			
			}
	
		else {
			
			//error_log("changes to memory found... inserting into memory history" . PHP_EOL;
			
			////error_log("$current_value: $status_value" . PHP_EOL;
			
			
			$variables_insert_sql = "INSERT INTO host_memory_history (`host_id`, `used`, `total`) VALUES (?, ?, ?);";
			
			$variables_insert = $link->prepare($variables_insert_sql);
			$variables_insert->bind_param('iii', $host_id, $current_memory_used, $current_memory_total);
			$variables_insert->execute();
			$variables_insert->free_result();
			
			//error_log("updating memory entry" . PHP_EOL;
			
			$variables_update_sql = "UPDATE host_memory SET `used` = ?, `total` = ? WHERE host_id = ?"; 
			
			$variables_update = $link->prepare($variables_update_sql);
			$variables_update->bind_param('iii', $json_memory_used, $json_memory_total, $host_id);
			$variables_update->execute();
			$variables_update->free_result();
			}
		}
		else {
	
			 //error_log("no  memory found... inserting entry" . PHP_EOL;
			 
			$hostname_insert_sql = "INSERT INTO host_memory (`host_id`, `used`, `total`) VALUES (?, ?, ?)";
	
			$hostname_insert = $link->prepare($hostname_insert_sql);
			$hostname_insert->bind_param('iii', $host_id, $json_memory_used, $json_memory_total);
			$hostname_insert->execute();
			$hostname_insert->free_result();
	
		}
	   
	}

//error_log("memory done... " . PHP_EOL;
// disk information

//error_log("starting disks" . PHP_EOL;

foreach ($json['disk']  as $parent => $child) {
	foreach ($child as $k => $v) {
		
		$mountpoint = $k;
		
		if (isset($v['used_space'])) {
			$json_disk_used = $v['used_space'];
		}
		if (isset($v['total_space'])) {
			$json_disk_total = $v['total_space'];
		}		
		
		//error_log("checking disk information" . PHP_EOL;
		
		$disk_usage_sql = "select used_space, total_space from host_disk where host_id = ? AND mountpoint = ?;";
	
		$disk_usage = $link->prepare($disk_usage_sql);
		$disk_usage->bind_param('is', $host_id, $mountpoint);
		$disk_usage->execute();
		$result = $disk_usage->get_result();
		$numrows = $result->num_rows;
		$disk_usage->free_result();
	
		while($row = $result->fetch_assoc()){
		  $current_disk_used = $row['used_space'];
		  $current_disk_total = $row['total_space'];
   		 }
		 
		 
		if( $numrows > 0 ){
			
			//error_log("disk information found in db" . PHP_EOL;
		
			if ( $current_disk_used == $json_disk_used && $current_disk_total == $json_disk_total ) {
				
				//error_log("no changes to disk" . PHP_EOL;
				}
	
			else {
			
			 	 //error_log("changes to disks found" . PHP_EOL;
				////error_log("$current_value: $status_value" . PHP_EOL;
				
				//error_log("inserting to disks history" . PHP_EOL;
				$disk_insert_sql = "INSERT INTO host_disk_history (`host_id`, `mountpoint`, `used_space`, `total_space`) VALUES (?, ?, ?, ?);";
				
				$disk_insert = $link->prepare($disk_insert_sql);
				$disk_insert->bind_param('isii', $host_id, $mountpoint, $current_disk_used, $current_disk_total);
				$disk_insert->execute();
				$disk_insert->free_result();
				
				//error_log("updating to disks information" . PHP_EOL;
				$disk_update_sql = "UPDATE host_disk SET `used_space` = ?, `total_space` = ? WHERE host_id = ? AND mountpoint = ?"; 
				
				$disk_update = $link->prepare($disk_update_sql);
				$disk_update->bind_param('iiis', $json_disk_used, $json_disk_total, $host_id, $mountpoint);
				$disk_update->execute();
				$disk_update->free_result();
				}
			}
		else {
		
		   
		    //error_log("no disk found, inserting" . PHP_EOL;
			$disk_insert_sql = "INSERT INTO host_disk (`host_id`, `mountpoint`, `used_space`, `total_space`) VALUES (?, ?, ?, ?);";
				
			$disk_insert = $link->prepare($disk_insert_sql);
			$disk_insert->bind_param('isii', $host_id, $mountpoint, $json_disk_used, $json_disk_total);
			$disk_insert->execute();
			$disk_insert->free_result();
		
		}		 
		 
		 
		 
		 
		 
	     	
	}
}

//error_log("disks done" . PHP_EOL;

// end disk information


//

//error_log("memory modules start" . PHP_EOL;

foreach ($json['memory_modules']  as $parent => $child) {
	foreach ($child as $k => $v) {
	 $locator = $k;
		
		if (!isset($v['serial_number'])) {
			
			//error_log("memory serial number number not set... skipping" . PHP_EOL;
			// to clean success from entry
		} else
		{
			 //error_log("memory serial number found... starting " . PHP_EOL;
			 
			$json_manufacturer = $v['manufacturer'];
			$json_part_number = $v['part_number'];
			$json_size = $v['size'];
			$json_speed = $v['speed'];
			$json_serial_number = $v['serial_number'];
			
			
			//error_log("checking if modules exists in db " . PHP_EOL;
			
			$module_select_sql = "select manufacturer, part_number, size, speed, serial_number from host_memory_module where host_id = ? AND locator = ?;";
			$module_select = $link->prepare($module_select_sql);
			$module_select->bind_param('is', $host_id, $locator);
			$module_select->execute();
			$result = $module_select->get_result();
			$numrows = $result->num_rows;
			$module_select->free_result();
			
			
			while($row = $result->fetch_assoc()){
			  $current_manufacturer = $row['manufacturer'];
			  $current_part_number = $row['part_number'];
			  $current_size = $row['size'];
			  $current_speed = $row['speed'];
			  $current_serial_number = $row['serial_number'];
			 }
			
			if( $numrows > 0 ){
		    
			 //error_log("modules exists " . PHP_EOL;
				
				if ( $current_manufacturer == $json_manufacturer && 
					$current_part_number == $json_part_number &&
					$current_size == $json_size && 
					$current_speed == $json_speed &&
					$current_serial_number == $json_serial_number
					) { //error_log("no changes to memory modules... moving on" . PHP_EOL;
					}
				else	
				{
			
			//error_log("changes to memory modules exist...  " . PHP_EOL;
			
			////error_log("$current_value: $status_value" . PHP_EOL;
			
			//error_log("inserting memory modules to history...  " . PHP_EOL;
			
			$module_insert_sql = "INSERT INTO host_memory_module_history (`host_id`, `locator`, `manufacturer`, `part_number`, `size`, `speed`, `serial_number`) VALUES (?, ?, ?, ?, ?, ?, ?);";
			
			$module_insert = $link->prepare($module_insert_sql);
			$module_insert->bind_param('issssss', $host_id, $locator, $current_manufacturer, $current_part_number, $current_size, $current_speed, $current_serial_number);
			$module_insert->execute();
			$module_insert->free_result();
			
			 //error_log("update memory modules...  " . PHP_EOL;
			 
			$module_update_sql = "UPDATE host_memory_module SET  `manufacturer` = ?, `part_number` = ?, `size` = ?, `speed` = ?, `serial_number`= ? where host_id = ? and locator = ?"; 
			
			$module_update = $link->prepare($module_update_sql);
			$module_update->bind_param('sssssis', $json_manufacturer, $json_part_number, $json_size, $json_speed, $json_serial_number, $host_id, $locator);
			$module_update->execute();
			$module_update->free_result();
			}
		}
		else {
	
			//error_log("no memory modules found in db... inserting  " . PHP_EOL;
			 
			$module_insert_sql = "INSERT INTO host_memory_module (`host_id`, `locator`, `manufacturer`, `part_number`, `size`, `speed`, `serial_number`) VALUES (?, ?, ?, ?, ?, ?, ?);";
			
			$module_insert = $link->prepare($module_insert_sql);
			$module_insert->bind_param('issssss', $host_id, $locator, $json_manufacturer, $json_part_number, $json_size, $json_speed, $json_serial_number);
			$module_insert->execute();
			$module_insert->free_result();
					
					}
			
			}
			
			
			
		}
	 
	 
	}

 //error_log("memory modules done...  " . PHP_EOL;
//


//
//error_log("starting network...  " . PHP_EOL;

foreach ($json['network']  as $parent => $child) {
	foreach ($child as $k => $v) {
		
	
	$ipaddress = $k;
	 //error_log("starting network entry for $ipaddress  " . PHP_EOL;
		
		if (!isset($v['interface'])) {
			
			 //error_log("skipping invalid entry for network " . PHP_EOL;
			
			// to clean success from entry
		} else
		{
			
			
			$json_priority = $v['priority'];
			$json_interface = $v['interface'];
			
			//error_log("checking network in db" . PHP_EOL;
			
			$eth_select_sql = "select priority, interface from host_network where host_id = ? AND ipaddress = inet_aton(?);";
			$eth_select = $link->prepare($eth_select_sql);
			$eth_select->bind_param('is', $host_id, $ipaddress);
			$eth_select->execute();
			$result = $eth_select->get_result();
			$numrows = $result->num_rows;
			$eth_select->free_result();
			
			
			while($row = $result->fetch_assoc()){
			  $current_priority = $row['priority'];
			  $current_interface = $row['interface'];
			 }
			
			if( $numrows > 0 ){
		
		 	//error_log("network rows found" . PHP_EOL;
				
				if ( $current_priority == $json_priority && 
					$current_interface == $json_interface 
					) { //error_log("no network changes detected" . PHP_EOL;
					}
				else	
				{
		
				//error_log("network changes found" . PHP_EOL;
				
				////error_log("$current_value: $status_value" . PHP_EOL;
				
				//error_log("inserting network to history" . PHP_EOL;
				
				$eth_insert_sql = "INSERT INTO host_network_history (`host_id`, `ipaddress`, `priority`, `interface`) VALUES (?, inet_aton(?), ?, ?);";
				
				$eth_insert = $link->prepare($eth_insert_sql);
				$eth_insert->bind_param('isss', $host_id, $ipaddress, $current_priority, $current_interface);
				$eth_insert->execute();
				$eth_insert->free_result();
				
				//error_log("updating network" . PHP_EOL;
				
				$eth_update_sql = "UPDATE host_network SET `host_id` = ?, `ipaddress` = ?, `priority` = ?, `interface` = ? where host_id = ? and ipaddress = inet_aton(?)"; 
				
				$eth_update = $link->prepare($eth_update_sql);
				$eth_update->bind_param('isss', $host_id, $ipaddress, $json_priority, $json_interface);
				$eth_update->execute();
				$eth_update->free_result();
				}
		}
		else {
	        
			//error_log("no network rows found... inserting" . PHP_EOL;
			
			//error_log('hostid: ' . $host_id . ', ipaddress: ' . $ipaddress . ', priority: ' .  $json_priority.', interface: '.  $json_interface . PHP_EOL;
			
			
			$eth_insert_sql = "INSERT INTO host_network (`host_id`, `ipaddress`, `priority`, `interface`) VALUES (?, inet_aton(?), ?, ?);";
			
			
			
			$eth_insert = $link->prepare($eth_insert_sql);
			$eth_insert->bind_param('isss', $host_id, $ipaddress, $json_priority, $json_interface);
			$eth_insert->execute();
			$eth_insert->free_result();
					
			}
			
			}
			
			
			
		}
	 
	 
	}


//

  
foreach ($json['mysql']  as $parent => $child) {
	foreach ($child as $k => $v) {
	   
	   // set instance
	   //print_r ($host_id) .PHP_EOL ;
	   
	   if (isset($v['mysql_variables']['0']['bind_address'])) {
		  	 $mysql_bind_address = $v['mysql_variables']['0']['bind_address'];
		   } else
		   {
			  $mysql_bind_address = 0;
		   }
		   
	   
	   $mysql_socket = $v['mysql_variables']['0']['socket'];
	   $mysql_port = $v['mysql_variables']['0']['port'];
       
       $instance_select_sql = "select id from mysql_instance where host_id = ? AND bind_address = INET_ATON(?) AND port = ? AND socket = ?;";
       $instance_select = $link->prepare($instance_select_sql);
	   $instance_select->bind_param('iiis', $host_id, $mysql_bind_address, $mysql_port, $mysql_socket);
	   $instance_select->execute();
	   $result = $instance_select->get_result();
	   $instance_rows = $result->num_rows;
	   $instance_select->free_result();
	   
	   if ( $instance_rows == 0 )
	   // insert instance
	   { 
	    $instance_insert_sql = "INSERT INTO mysql_instance (`host_id`, `bind_address`, `port`, `socket` ) VALUES (?, INET_ATON(?), ?, ?)";
	
		$instance_insert = $link->prepare($instance_insert_sql);
		$instance_insert->bind_param('iiis', $host_id, $mysql_bind_address, $mysql_port, $mysql_socket);
		$instance_insert->execute();
		$instance_id = $instance_insert->insert_id;
		$instance_insert->free_result(); 
	   }
	   
	    else { 
		
		//set instance ID
	     while($row = $result->fetch_assoc()){
	    	$instance_id = $row['id'];
          }
	    
		//error_log('instance_id: ' . $instance_id);
		
	    $instance_insert_sql = "UPDATE mysql_instance SET last_poll_dt = FROM_UNIXTIME(?) WHERE id = ?";
	
		$instance_insert = $link->prepare($instance_insert_sql);
		$instance_insert->bind_param('ii', $json_poll_time, $instance_id);
		$instance_insert->execute();
		$instance_insert->free_result(); 
	   
	   }
       
       ////error_log($instance_id . PHP_EOL;
	   
	   foreach ($v as $key => $value) {
	   
	      //error_log('instance_id: ' . $instance_id);
	       
	       	   if ( $key == 'mysql_variables') { 

				foreach ($value as $a => $b) {
	       		  
	       		    foreach ($b as $c => $d) {
	       		         
	       		        $status_name = $c;
	       		        $status_value = $d; 
						 
	       		        $status_select_sql = "select id, `value` from mysql_variables where `instance_id` = ? AND `name` = ?;";
						
						//error_log("select `value` from mysql_status where `instance_id` = ".$instance_id." AND `name` = ".$status_name.";");
	
						$status_select = $link->prepare($status_select_sql);
						$status_select->bind_param('is', $instance_id, $status_name);
						$status_select->execute();
						$result = $status_select->get_result();
						$status_rows = $result->num_rows;
						$status_select->free_result();
						
						while($row = $result->fetch_assoc()){
							$current_id = $row['id'];
							$current_value = $row['value'];
							
						}
						
						//error_log('status_name: '.$status_name);
						//error_log('status rows: '.$status_rows);
						//error_log('current_value: '.$current_value);
						//error_log('status_value: '.$status_value);
						
						
					    ////error_log(($status_rows);
						
						if( $status_rows > 0 ){
							
							if ($current_value == $status_value) {}
						
							else {
							    
								//error_log($current_value.": ".$status_value . PHP_EOL);
							    
								
								$variables_insert_sql = "INSERT INTO mysql_variables_history (`mysql_variables_id`, `name`, `value`) VALUES (?,?,?)";
								
								//error_log("INSERT INTO mysql_status_history (`mysql_status_id`, `name`, `value`) VALUES (".$current_id.", '". $status_name."' , '".$current_value."' )");
								
								
								
								$variables_insert = $link->prepare($variables_insert_sql);
								$variables_insert->bind_param('iss', $current_id, $status_name, $current_value);
								$variables_insert->execute();
								$variables_insert->free_result();
								
								
								$variables_update_sql = "UPDATE mysql_variables SET `value` = ? WHERE id = ?";
								
								//error_log("UPDATE mysql_status SET `value` = '".$status_value."' WHERE id = $current_id");
								
								$variables_update = $link->prepare($variables_update_sql);
								$variables_update->bind_param('si', $status_value , $current_id);
								$variables_update->execute();
								$variables_update->free_result();
							}
						} else {
						
						    ////error_log('new status';
					
							$variables_insert_sql = "INSERT INTO mysql_variables (`instance_id`, `name`, `value`) VALUES (?,?,?)";
							
							$variables_insert = $link->prepare($variables_insert_sql);
							$variables_insert->bind_param('iss', $instance_id, $status_name, $status_value);
							$variables_insert->execute();
							$variables_insert->free_result();
						
						}
						
						
						
						}
						
						
						
						
						
					}
		   
		   
	       
	       
	       } else
	       
	       if ( $key == 'mysql_status') { 

				foreach ($value as $a => $b) {
	       		  
	       		    foreach ($b as $c => $d) {
	       		         
	       		        $status_name = $c;
	       		        $status_value = $d; 
						 
	       		        $status_select_sql = "select id, `value` from mysql_status where `instance_id` = ? AND `name` = ?;";
						
						//error_log("select `value` from mysql_status where `instance_id` = ".$instance_id." AND `name` = ".$status_name.";");
	
						$status_select = $link->prepare($status_select_sql);
						$status_select->bind_param('is', $instance_id, $status_name);
						$status_select->execute();
						$result = $status_select->get_result();
						$status_rows = $result->num_rows;
						$status_select->free_result();
						
						while($row = $result->fetch_assoc()){
							$current_id = $row['id'];
							$current_value = $row['value'];
							
						}
						
						//error_log('status_name: '.$status_name);
						//error_log('status rows: '.$status_rows);
						//error_log('current_value: '.$current_value);
						//error_log('status_value: '.$status_value);
						
						
					    ////error_log(($status_rows);
						
						if( $status_rows > 0 ){
							
							if ($current_value == $status_value) {}
						
							else {
							    
								//error_log($current_value.": ".$status_value . PHP_EOL);
							    
								
								$variables_insert_sql = "INSERT INTO mysql_status_history (`mysql_status_id`, `name`, `value`) VALUES (?,?,?)";
								
								//error_log("INSERT INTO mysql_status_history (`mysql_status_id`, `name`, `value`) VALUES (".$current_id.", '". $status_name."' , '".$current_value."' )");
								
								
								
								$variables_insert = $link->prepare($variables_insert_sql);
								$variables_insert->bind_param('iss', $current_id, $status_name, $current_value);
								$variables_insert->execute();
								$variables_insert->free_result();
								
								
								$variables_update_sql = "UPDATE mysql_status SET `value` = ? WHERE id = ?";
								
								//error_log("UPDATE mysql_status SET `value` = '".$status_value."' WHERE id = $current_id");
								
								$variables_update = $link->prepare($variables_update_sql);
								$variables_update->bind_param('si', $status_value , $current_id);
								$variables_update->execute();
								$variables_update->free_result();
							}
						} else {
						
						    ////error_log('new status';
					
							$variables_insert_sql = "INSERT INTO mysql_status (`instance_id`, `name`, `value`) VALUES (?,?,?)";
							
							$variables_insert = $link->prepare($variables_insert_sql);
							$variables_insert->bind_param('iss', $instance_id, $status_name, $status_value);
							$variables_insert->execute();
							$variables_insert->free_result();
						
						}
						
						
						
						}
						
						
						
						
						
					}
		   
		   
	       
	       
	       } else
	       
	       if ( $key == 'mysql_replication') { 

				foreach ($value as $a => $b) {
	       		  
	       		    foreach ($b as $c => $d) {
	       		         
	       		        $status_name = $c;
	       		        $status_value = $d; 
						 
	       		        $status_select_sql = "select id, `value` from mysql_replication where `instance_id` = ? AND `name` = ?;";
						
						//error_log("select `value` from mysql_status where `instance_id` = ".$instance_id." AND `name` = ".$status_name.";");
	
						$status_select = $link->prepare($status_select_sql);
						$status_select->bind_param('is', $instance_id, $status_name);
						$status_select->execute();
						$result = $status_select->get_result();
						$status_rows = $result->num_rows;
						$status_select->free_result();
						
						while($row = $result->fetch_assoc()){
							$current_id = $row['id'];
							$current_value = $row['value'];
							
						}
						
						//error_log('status_name: '.$status_name);
						//error_log('status rows: '.$status_rows);
						//error_log('current_value: '.$current_value);
						//error_log('status_value: '.$status_value);
						
						
					    ////error_log(($status_rows);
						
						if( $status_rows > 0 ){
							
							if ($current_value == $status_value) {}
						
							else {
							    
								//error_log($current_value.": ".$status_value . PHP_EOL);
							    
								
								$variables_insert_sql = "INSERT INTO mysql_replication_history (`mysql_replication_id`, `name`, `value`) VALUES (?,?,?)";
								
								//error_log("INSERT INTO mysql_status_history (`mysql_status_id`, `name`, `value`) VALUES (".$current_id.", '". $status_name."' , '".$current_value."' )");
								
								
								
								$variables_insert = $link->prepare($variables_insert_sql);
								$variables_insert->bind_param('iss', $current_id, $status_name, $current_value);
								$variables_insert->execute();
								$variables_insert->free_result();
								
								
								$variables_update_sql = "UPDATE mysql_replication SET `value` = ? WHERE id = ?";
								
								//error_log("UPDATE mysql_status SET `value` = '".$status_value."' WHERE id = $current_id");
								
								$variables_update = $link->prepare($variables_update_sql);
								$variables_update->bind_param('si', $status_value , $current_id);
								$variables_update->execute();
								$variables_update->free_result();
							}
						} else {
						
						    ////error_log('new status';
					
							$variables_insert_sql = "INSERT INTO mysql_replication (`instance_id`, `name`, `value`) VALUES (?,?,?)";
							
							$variables_insert = $link->prepare($variables_insert_sql);
							$variables_insert->bind_param('iss', $instance_id, $status_name, $status_value);
							$variables_insert->execute();
							$variables_insert->free_result();
						
						}
						
						
						
						}
						
						
						
						
						
					}
		   
		   
	       
	       
	       } else
			{}
	       
	   }

	}
}


mysqli_close($link);

echo "done";

?>