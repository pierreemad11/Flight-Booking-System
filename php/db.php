<?php
$db_server = "localhost";
$db_username = 'root';
$db_password = '';
$db_name = 'businessdb';

// Using try-catch to handle potential exceptions
try {
    // Establish a connection to the database
    $conn = mysqli_connect($db_server, $db_username, $db_password, $db_name);

    // Check the connection
    if (!$conn) {
        // Throw an exception if the connection fails
        throw new Exception("Connection failed: " . mysqli_connect_error());
    } else {
        // Uncomment the following line to debug successful connections
        // echo "Connected successfully";
    }
} catch (Exception $e) {
    // Display the error message and terminate the script
    die("Error: " . $e->getMessage());
}
?>
