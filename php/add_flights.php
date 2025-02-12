<?php
include ("db.php");

session_start(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $flightName = $_POST["flightName"];
    $flightDeparture = $_POST["flightDeparture"]; 
    $flightDestination = $_POST["flightDestination"]; 
    $flightStop = $_POST["flightStops"];
    $flightFees = $_POST["flightFees"];
    $flightStartTime = $_POST["flightStartTime"]; 
    $flightEndTime = $_POST["flightEndTime"]; 
    $passengersNumber  = $_POST["passengersNumber"];


    // Retrieve company_id from the session
    $company_id = $_SESSION["company_id"];

    // SQL query to insert flight information into the database
    $sql = "INSERT INTO `Flight` (`name`, `departure`, `destination`, `stops`, `fees`, `start_time`, `end_time`, `company_id`, `is_completed` ,  `passengers_number`) 
    VALUES ('$flightName', '$flightDeparture', '$flightDestination', '$flightStop', '$flightFees', '$flightStartTime', '$flightEndTime', '$company_id', '0' , $passengersNumber)";

    if ($conn->query($sql) === TRUE) {
        header("Location: company_home.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
