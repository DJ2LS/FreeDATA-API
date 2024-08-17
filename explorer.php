<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

include_once("explorer_config.php");

// Set default time to UTC
date_default_timezone_set('UTC');

// Get the current date and time
$date = date('Y-m-d H:i:s');

// Set up database connection
$conn = new mysqli($dbserver, $dbuser, $dbpwd, $dbname);

// Check database connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Create the table if it doesn't exist
if ($conn->query($dbsetup) !== TRUE) {
    die(json_encode(["error" => "Error creating table: " . $conn->error]));
}

// Get POST data and check if it is not empty
$post_data = file_get_contents('php://input');
if (!empty($post_data)) {
    $json = json_decode($post_data, true);

    // Ensure JSON decoding was successful
    if (json_last_error() === JSON_ERROR_NONE) {
        // Extract data from JSON payload
        $callsign = $conn->real_escape_string($json['callsign']);
        $band = $conn->real_escape_string($json['band']);
        $frequency = (int) $json['frequency'];
        $strength = $conn->real_escape_string($json['strength']);
        $gridsquare = $conn->real_escape_string($json['gridsquare']);
        $version = $conn->real_escape_string($json['version']);
        $bandwidth = $conn->real_escape_string($json['bandwidth']);
        $beacon = $conn->real_escape_string($json['beacon']);
        $lastheard = $conn->real_escape_string(json_encode($json['lastheard']));

        // Check if the callsign already exists
        $sql = "SELECT frequency FROM explorer WHERE callsign = '$callsign'";
        $result = $conn->query($sql);

        if ($result->num_rows === 0) {
            // Insert new record
            $sql = "INSERT INTO explorer (timestamp, callsign, gridsquare, version, frequency, strength, band, bandwidth, beacon, lastheard)
                    VALUES ('$date', '$callsign', '$gridsquare', '$version', '$frequency', '$strength', '$band', '$bandwidth', '$beacon', '$lastheard')";
        } else {
            // Update existing record
            $row = $result->fetch_assoc();
            if ($frequency === 0) {
                $frequency = (int) $row['frequency'];
            }
            $sql = "UPDATE explorer SET
                    timestamp = CURRENT_TIMESTAMP,
                    gridsquare = '$gridsquare',
                    version = '$version',
                    frequency = '$frequency',
                    strength = '$strength',
                    band = '$band',
                    bandwidth = '$bandwidth',
                    beacon = '$beacon',
                    lastheard = '$lastheard'
                    WHERE callsign = '$callsign'";
        }

        // Execute the query
        if (!$conn->query($sql)) {
            die(json_encode(["error" => "Database query failed: " . $conn->error]));
        }
    } else {
        die(json_encode(["error" => "Invalid JSON data received."]));
    }
}

// Delete entries older than 1 day
$conn->query("DELETE FROM explorer WHERE timestamp < NOW() - INTERVAL 1 DAY");

// Retrieve and display all data
$result = $conn->query("SELECT * FROM explorer");
if ($result->num_rows > 0) {
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $json_string = json_encode($rows);
    echo "{$_GET['callback']}($json_string)";
} else {
    echo json_encode(["message" => "0 results"]);
}

$conn->close();
?>
