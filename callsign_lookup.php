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
	$result = $conn->query("SELECT callsign, dxcallsign FROM summary");
	if ($result->num_rows > 0) {
	  // output data of each row

		while($row = $result->fetch_assoc()) {
			//print($row["callsign"]);
			$rows[] = $row["callsign"];
			$rows[] = $row["dxcallsign"];
	  }
			
		// Remove duplicate values
		$uniqueCallsignArray = array_unique($rows);


		foreach ($uniqueCallsignArray as $key => $value) {

			$crc24Value = crc24_openpgp($value);
			$crc24Value = sprintf("%08x", $crc24Value);
			$crc24HexTrimmed = substr($crc24Value, -6);

			$jsonArray[$value] = $crc24HexTrimmed; // Assign each value to the corresponding key in the JSON array
		}

		$jsonString = json_encode($jsonArray);
		print($jsonString);
		
		//$json_string = json_encode($rows);

		//exit("{$_GET['callback']}($json_string)");
		//print trim($json_string,'\\');	


	} else {
	  echo "0 results";
	}
		
}
	
// chatgpt generated crc24 checksum
function crc24_openpgp($data) {
    $crc = 0xB704CE;
    $len = strlen($data);

    for ($i = 0; $i < $len; $i++) {
        $crc ^= (ord($data[$i]) << 16);

        for ($j = 0; $j < 8; $j++) {
            $crc <<= 1;

            if ($crc & 0x1000000) {
                $crc ^= 0x864CFB;
            }
        }
    }




    return ($crc & 0xFFFFFF);
}


?>