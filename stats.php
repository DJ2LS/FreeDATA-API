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
    if($post_data !== ''){
        $data = json_decode($post_data, true);
        $json = json_decode($data, true); // we need to run this twice for converting string to array


		$callsign = $json["callsign"];
        $dxcallsign = $json["dxcallsign"];
        $gridsquare = $json["gridsquare"];
        $dxgridsquare = $json["dxgridsquare"];
        $frequency = $json["frequency"];
        $avgstrength = $json["avgstrength"];
        $avgsnr = $json["avgsnr"];
        $bytesperminute = $json["bytesperminute"];
        $filesize = $json["filesize"];
        $compressionfactor = $json["compressionfactor"];
        $nacks = $json["nacks"];
        $crcerror = $json["crcerror"];
        if($crcerror == true){
            $crcerror = "True";
        } else {
            $crcerror = "False";
        }

		$duration = $json["duration"];
        $status = $json["status"];
        $version = $json["version"];


        $sql = "INSERT INTO summary (timestamp, callsign, dxcallsign, gridsquare, dxgridsquare, frequency, avgstrength, avgsnr, bytesperminute, filesize, compressionfactor, nacks, crcerror, duration, status, version )
				VALUES ('$date', '$callsign', '$dxcallsign', '$gridsquare','$dxgridsquare', '$frequency','$avgstrength', '$avgsnr', '$bytesperminute', '$filesize', '$compressionfactor', '$nacks', '$crcerror', '$duration','$status', '$version')";


        $result = $conn->query($sql);
    }


	// finally display all data
	$result = $conn->query("SELECT * FROM summary");
    if ($result->num_rows > 0) {
        // output data of each row
	  while($row = $result->fetch_assoc()) {
          $rows[] = $row;	
      }

		$json_string = json_encode($rows);
      exit("{$_GET['callback']}($json_string)");
      //exit("$json_string");
		//exit("{$_GET['callback']}$json_string");
		//print trim($json_string,'\\');	
    } else {
        echo "0 results";
    }

}




?>