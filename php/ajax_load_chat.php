<?php

// expects sender_id and receiver_id, It returns the messages in JSON (or HTML)

session_start();
include("db.php");

if (!isset($_SESSION["user_type"])) {
    echo "Not logged in.";
    exit();
}

// Grab request data
$receiver_id = isset($_GET["receiver_id"]) ? intval($_GET["receiver_id"]) : 0;
if ($receiver_id <= 0) {
    echo "Invalid or missing receiver_id.";
    exit();
}

// Determine our own user_id from session
if ($_SESSION["user_type"] === "passenger") {
    $sender_id = $_SESSION["passenger_id"];
} else if ($_SESSION["user_type"] === "company") {
    $sender_id = $_SESSION["company_id"];
} else {
    echo "Invalid user type in session.";
    exit();
}

// Fetch all messages between these two
$sql = "
    SELECT m.*, 
           p.name AS passengerName, 
           c.name AS companyName,
           m.created_at
    FROM Message m
    LEFT JOIN Passenger p ON p.passenger_id = m.sender_id
    LEFT JOIN Company c   ON c.company_id   = m.sender_id
    WHERE (m.sender_id   = $sender_id AND m.receiver_id = $receiver_id)
       OR (m.sender_id   = $receiver_id AND m.receiver_id = $sender_id)
    ORDER BY m.created_at ASC
";
$result = $conn->query($sql);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Return JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages);

$conn->close();
?>
