<?php

include("db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$passenger_edit_id = $_GET["edit_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle the form submission to update the passenger information
    $newName  = $_POST["new_name"];
    $newEmail = $_POST["new_email"];
    $newTel   = $_POST["new_tel"];

    // Update passenger info in the DB
    $updateQuery = "
        UPDATE `passenger` 
        SET
            `name`  = '$newName',
            `email` = '$newEmail',
            `tel`   = '$newTel'
        WHERE `passenger_id` = $passenger_edit_id
    ";

    if ($conn->query($updateQuery) === TRUE) {
        // For now, just echo success. You can add a redirect if desired.
        echo "Passenger information updated successfully";
    } else {
        echo "Error updating passenger information: " . $conn->error;
    }
}

// Retrieve current passenger information
$passengerQuery  = "SELECT * FROM `Passenger` WHERE `passenger_id` = $passenger_edit_id";
$passengerResult = $conn->query($passengerQuery);

if ($passengerResult->num_rows > 0) {
    $passengerData = $passengerResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?php echo htmlspecialchars($passengerData["name"]); ?> Profile</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .edit-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 400px;
            margin: 20px;
            padding: 20px;
            box-sizing: border-box;
        }

        .edit-container h1 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 10px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #2980b9;
        }

        .note {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        @media (max-width: 480px) {
            .edit-container {
                margin: 10px auto;
                padding: 15px;
            }
            label {
                margin-top: 8px;
                margin-bottom: 2px;
            }
        }
    </style>
</head>

<body>

<div class="edit-container">
    <h1>Edit your profile</h1>
    <form method="post" action="">
        <label for="new_name">Name:</label>
        <input 
            type="text" 
            id="new_name" 
            name="new_name" 
            value="<?php echo htmlspecialchars($passengerData["name"]); ?>"
        >

        <label for="new_email">Email:</label>
        <input 
            type="email" 
            id="new_email" 
            name="new_email" 
            value="<?php echo htmlspecialchars($passengerData["email"]); ?>"
        >

        <label for="new_tel">Tel:</label>
        <input 
            type="text" 
            id="new_tel" 
            name="new_tel" 
            value="<?php echo htmlspecialchars($passengerData["tel"]); ?>"
        >

        <button type="submit">Update</button>
    </form>
    <p class="note">Please ensure your information is accurate.</p>
</div>

</body>
</html>

<?php
} // end if ($passengerResult->num_rows > 0)
$conn->close();
?>
