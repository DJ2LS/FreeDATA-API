<?php
header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors',1);


include_once("stats_config.php");


// set default time UTC
date_default_timezone_set('UTC');

$date = time();

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
	
	
	// finally display all data
	$result = $conn->query("SELECT callsign FROM summary");
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