<?php
header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors',1);

include_once("explorer_config.php");


// set default time UTC
date_default_timezone_set('UTC');

$date = date('y-m-d h:i:s');
//echo $date;



// set up database connection
$conn = new mysqli($dbserver, $dbuser, $dbpwd, $dbname);

// check database connection
if($conn->connect_error){
	die("database connection failed: " . $conn->connect_error);
} else {
	if($conn->query($dbsetup) === TRUE){
		//echo "table created successfully<br>";
	} else {
		echo "error creating table: " . $conn->error;
	}

	// get post data and check if empty
	$post_data = file_get_contents('php://input');	
	if($post_data !== ''){
		$data = json_decode($post_data, true);
		$json = json_decode($data, true); // we need to run this twice for converting string to array


		$callsign = $json["callsign"];
		$band = $json["band"];
		$frequency = $json["frequency"];
		$strength = $json["strength"];
		$gridsquare = $json["gridsquare"];
		$version = $json["version"];
		$bandwidth = $json["bandwidth"];
		$beacon = $json["beacon"];
		$lastheard = json_encode($json["lastheard"]);

		$sql = "SELECT * FROM explorer WHERE callsign = '$callsign'";
		$result = $conn->query($sql);
		if ($result->num_rows == 0) {
			$sql = "INSERT INTO explorer (timestamp, callsign, gridsquare, version, frequency, strength, band, bandwidth, beacon, lastheard )
				VALUES ('$date', '$callsign', '$gridsquare', '$version', '$frequency','$strength', '$band', '$bandwidth', '$beacon', '$lastheard')";

		} else {

			// Check if valid new data arrived else use old database entry
		// This also avoids have a 0 frequency on map
		while($row = $result->fetch_assoc()) {
			$frequency = (int)$frequency;			
			//echo $frequency;
			//echo is_int($frequency);
			if($frequency == 0 || !is_int($frequency)){

				$frequency = $row['frequency'];	
			}
		}





		// 			timestamp = '$date',
		$sql = "UPDATE explorer SET
			callsign = '$callsign', 
			gridsquare = '$gridsquare', 
			version = '$version', 
			frequency = '$frequency', 
			strength = '$strength', 
			band = '$band', 
			bandwidth = '$bandwidth', 
			beacon = '$beacon',
			lastheard = '$lastheard',
			timestamp = current_timestamp
    		WHERE callsign = '$callsign'";
		}
	$result = $conn->query($sql);
	}


	// check if entry too old then delete it
	$result = $conn->query("DELETE FROM explorer WHERE timestamp < NOW() - INTERVAL 1 DAY");


	// finally display all data
	$result = $conn->query("SELECT * FROM explorer");
	if ($result->num_rows > 0) {
		// output data of each row
	  while($row = $result->fetch_assoc()) {
		  $rows[] = $row;	
	  }

		$json_string = json_encode($rows);


	  exit("{$_GET['callback']}($json_string)");


	  //print trim($json_string,'\\');	
	} else {
		echo "0 results";
	}

}




?>