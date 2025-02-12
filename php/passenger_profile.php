<?php
include("db.php");

$passenger_temp_id = $_GET["profile_id"];

// Fetch passenger info
$passengerQuery = "SELECT * FROM `Passenger` WHERE `passenger_id` = $passenger_temp_id";
$passengerResult = $conn->query($passengerQuery);

if ($passengerResult->num_rows > 0) {
    $passengerData = $passengerResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($passengerData["name"]); ?> Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            flex-direction: column;
        }

        .passenger-profile {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            margin: 20px auto;
            box-sizing: border-box;
        }

        h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .passenger-passport-picture
        {
            width: 150px;
            height: 150px;
            margin: 15px 0;
            object-fit: cover;
        }

        .passenger-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 15px 0;
            object-fit: cover;
        }

        p {
            color: #333;
            margin: 6px 0;
        }

        .flights-list {
            margin-top: 20px;
            text-align: left;
        }

        .flights-list h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .flight-item {
            background-color: #f0f0f0;
            padding: 8px;
            border-radius: 5px;
            margin: 5px 0;
        }

        .edit-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #fff;
            background-color: #3498db;
            padding: 10px 16px;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .edit-link:hover {
            background-color: #2980b9;
        }

       

        @media (max-width: 480px) {
            .passenger-profile {
                width: 90%;
                padding: 15px;
            }
            .passenger-picture {
                max-width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body>
    <section class="passenger-profile">
        <h2><?php echo htmlspecialchars($passengerData["name"]); ?> (Profile)</h2>

        <!-- Passenger Picture -->
        <img 
            src="<?php echo htmlspecialchars($passengerData["photo"]); ?>" 
            alt="Passenger Picture" 
            class="passenger-picture"
        >
       

        <!-- Passenger Details -->
        <p><strong>Name:</strong> <?php echo htmlspecialchars($passengerData["name"]); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($passengerData["email"]); ?></p>
        <p><strong>Tel:</strong> <?php echo htmlspecialchars($passengerData["tel"]); ?></p>
        <p><strong>Account Balance:</strong> <?php echo htmlspecialchars($passengerData["account_balance"]); ?></p>
        <br>
        <p><strong>Passport picture ↓ </strong></p>
        <img 
            src="<?php echo htmlspecialchars($passengerData["passport_img"]); ?>" 
            alt="Passenger Picture" 
            class="passenger-passport-picture"
        >

        <!-- Flights List (registered or completed) -->
        <div class="flights-list">
            <h3>Flights List</h3>
            <?php
            // Only show flights with status IN ('completed','registered') for passenger
            $flightsQuery = "
                SELECT f.*
                FROM `Flight` AS f
                JOIN `Passenger_Flight` AS pf ON f.`flight_id` = pf.`flight_id`
                WHERE pf.`passenger_id` = $passenger_temp_id
                  AND pf.`status` IN ('completed', 'pending')
            ";
            $flightsResult = $conn->query($flightsQuery);

            if ($flightsResult->num_rows > 0) {
                while ($flightRow = $flightsResult->fetch_assoc()) {
                    echo '<div class="flight-item">';
                    echo htmlspecialchars($flightRow['name']);
                    echo '</div>';
                }
            } else {
                echo "<p>No flights booked</p>";
            }
            ?>
        </div>

        <!-- Edit Link -->
        <a 
            href="edit_passenger_profile.php?edit_id=<?php echo urlencode($passenger_temp_id); ?>" 
            class="edit-link"
        >
            Edit
        </a>
    </section>


</body>
</html>

<?php
} else {
    echo "Passenger not found.";
}

$conn->close();
?>
