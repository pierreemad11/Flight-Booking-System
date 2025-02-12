<?php
// passenger_home.php

session_start();
include("db.php");

// Check if user is passenger
if (
    isset($_SESSION["user_type"]) 
    && $_SESSION["user_type"] === "passenger" 
    && isset($_SESSION["passenger_id"])
) {
    $passenger_id = $_SESSION["passenger_id"];

    // Retrieve passenger information
    $passengerQuery = "SELECT * FROM `Passenger` WHERE `passenger_id` = $passenger_id";
    $passengerResult = $conn->query($passengerQuery);

    if ($passengerResult->num_rows > 0) {
        $passengerData = $passengerResult->fetch_assoc();
    } else {
        echo "Passenger not found.";
        exit();
    }
} else {
    // Redirect to login if not a passenger
    header("Location: ../pages/login_page.html");
    exit();
}

// 1) Completed Flights: status = 'completed'
$completedFlightsQuery = "
    SELECT * FROM `Flight`
    WHERE `flight_id` IN (
        SELECT `flight_id` 
        FROM `Passenger_Flight`
        WHERE `passenger_id` = $passenger_id
          AND `status` = 'completed'
          AND payment_status = 'completed' 
    )
";
$completedFlightsResult = $conn->query($completedFlightsQuery);

// 2) Current Flights: status = 'completed payment and pending flight status'
$currentFlightsQuery = "
    SELECT * FROM `Flight`
    WHERE `flight_id` IN (
        SELECT `flight_id` 
        FROM `Passenger_Flight`
        WHERE `passenger_id` = $passenger_id
          AND `status` = 'pending'
          AND payment_status = 'completed' 
    )
";
$currentFlightsResult = $conn->query($currentFlightsQuery);

// 3) Pending Flights: status = 'pending payment'
$pendingFlightsQuery = "
    SELECT * FROM `Flight`
    WHERE `flight_id` IN (
        SELECT `flight_id`
        FROM `Passenger_Flight`
        WHERE `passenger_id` = $passenger_id
          AND `payment_status` = 'pending'
         AND `status` = 'pending'


    )
";
$pendingFlightsResult = $conn->query($pendingFlightsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passenger Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Overall Page Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Container for main content */
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #333;
        }
        .header img {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            margin: 15px 0;
            object-fit: cover;
        }

        .details, .flights-list {
            margin-bottom: 20px;
        }
        .details p, .flights-list p {
            margin: 5px 0;
            color: #555;
        }

        .flights-list h3 {
            color: #444;
            margin-bottom: 10px;
        }

        .profile-link,
        .search-link {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
            text-align: center;
        }
        .profile-link:hover,
        .search-link:hover {
            background-color: #0056b3;
        }

        .flights {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .flights p {
            margin: 5px 0;
        }
        .flights .message {
            color: #777;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($passengerData["name"]); ?>!</h2>
            <img src="<?php echo htmlspecialchars("../images/" . $passengerData["photo"]); ?>" alt="Passenger Image">
        </div>

        <!-- Passenger Details -->
        <div class="details">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($passengerData["name"]); ?></p>
            <p><strong>Account Balance:</strong> $<?php echo htmlspecialchars($passengerData["account_balance"]); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($passengerData["email"]); ?></p>
            <p><strong>Tel:</strong> <?php echo htmlspecialchars($passengerData["tel"]); ?></p>
        </div>


        

        <!-- Completed Flights -->
        <div class="flights-list">
            <h3>Completed Flights</h3>
            <?php if ($completedFlightsResult->num_rows > 0): ?>
                <?php while ($completedFlightData = $completedFlightsResult->fetch_assoc()): ?>
                    <div class="flights">
                        <p>
                            <?php echo htmlspecialchars($completedFlightData['name']); ?>: 
                            <?php echo htmlspecialchars($completedFlightData['departure']); ?> 
                            → 
                            <?php echo htmlspecialchars($completedFlightData['destination']); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No completed flights.</p>
            <?php endif; ?>
        </div>

        <!-- Current Flights -->
        <div class="flights-list">
            <h3>Current Flights</h3>
            <?php if ($currentFlightsResult->num_rows > 0): ?>
                <?php while ($currentFlightData = $currentFlightsResult->fetch_assoc()): ?>
                    <div class="flights">
                        <p>
                            <?php echo htmlspecialchars($currentFlightData['name']); ?>: 
                            <?php echo htmlspecialchars($currentFlightData['departure']); ?> 
                            → 
                            <?php echo htmlspecialchars($currentFlightData['destination']); ?>
                        </p>
                        <p class="message">Enjoy your flight!</p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No current flights.</p>
            <?php endif; ?>
        </div>

        <!-- Pending Flights -->
        <div class="flights-list">
            <h3>Pending Flights</h3>
            <?php if ($pendingFlightsResult->num_rows > 0): ?>
                <?php while ($pendingFlightData = $pendingFlightsResult->fetch_assoc()): ?>
                    <div class="flights">
                        <p>
                            <?php echo htmlspecialchars($pendingFlightData['name']); ?>: 
                            <?php echo htmlspecialchars($pendingFlightData['departure']); ?> 
                            → 
                            <?php echo htmlspecialchars($pendingFlightData['destination']); ?>
                        </p>
                        <p class="message">Please complete your payment to confirm your flight.</p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending flights.</p>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <a href="passenger_profile.php?profile_id=<?php echo $passenger_id; ?>" class="profile-link">
            View Profile
        </a>
        <a href="search_flight.php" class="search-link">
            Search for a Flight
        </a>
        <a href="passenger_messages.php" 
          style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #007bff; 
          text-decoration: none; border-radius: 5px; margin-top: 10px; transition: 0.3s;">
          Inbox
</a>


    </div>


</body>

</html>

<?php
$conn->close();
?>





