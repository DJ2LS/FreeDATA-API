<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

include_once("explorer_config.php");

date_default_timezone_set('UTC');
$date = date('y-m-d h:i:s');
$conn = new mysqli($dbserver, $dbuser, $dbpwd, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

if ($conn->query($dbsetup) !== TRUE) {
    die(json_encode(["error" => "Error creating table: " . $conn->error]));
}

$post_data = file_get_contents('php://input');
// Attempt to decode the JSON
$post_data = json_decode($post_data, true);

if (!empty($post_data)) {
    $json = json_decode($post_data, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $callsign = $conn->real_escape_string($json['callsign']);
        $band = $conn->real_escape_string($json['band']);
        $frequency = (int)$json['frequency'];
        $strength = $conn->real_escape_string($json['strength']);
        $gridsquare = $conn->real_escape_string($json['gridsquare']);
        $version = $conn->real_escape_string($json['version']);
        $bandwidth = $conn->real_escape_string($json['bandwidth']);
        $beacon = $conn->real_escape_string($json['beacon']);
        $lastheard = json_encode($json['lastheard']);

        $sql = "SELECT frequency FROM explorer WHERE callsign = '$callsign'";
        $result = $conn->query($sql);

        if ($result->num_rows === 0) {
            $sql = "INSERT INTO explorer (timestamp, callsign, gridsquare, version, frequency, strength, band, bandwidth, beacon, lastheard)
                    VALUES ('$date', '$callsign', '$gridsquare', '$version', '$frequency', '$strength', '$band', '$bandwidth', '$beacon', '$lastheard')";
        } else {
            $row = $result->fetch_assoc();
            if ($frequency === 0) {
                $frequency = (int)$row['frequency'];
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

        if (!$conn->query($sql)) {
            die(json_encode(["error" => "Database query failed: " . $conn->error]));
        }
    } else {
        die(json_encode(["error" => "Invalid JSON data received."]));
    }
}

$conn->query("DELETE FROM explorer WHERE timestamp < NOW() - INTERVAL 1 DAY");

$result = $conn->query("SELECT * FROM explorer");
$rows = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['lastheard'] = json_decode($row['lastheard']);
        $rows[] = $row;
    }
    $json_string = json_encode($rows);
} else {
    $json_string = json_encode(["message" => "0 results"]);
}

if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
    echo $callback . '(' . $json_string . ')';
} else {
    echo $json_string;
}

$conn->close();
?>
