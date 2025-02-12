<?php
// passenger_messages.php
session_start();
include("db.php");

// Must be passenger
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "passenger") {
    header("Location: ../pages/login_page.html");
    exit();
}

$passenger_id = $_SESSION["passenger_id"];

// Optionally fetch passenger name for display:
$passengerNameQuery = "SELECT name FROM Passenger WHERE passenger_id = $passenger_id";
$passengerNameResult= $conn->query($passengerNameQuery);
$passengerName      = "Unknown Passenger";
if ($passengerNameResult && $passengerNameResult->num_rows > 0) {
    $row          = $passengerNameResult->fetch_assoc();
    $passengerName= $row["name"];
}


$sql = "
    SELECT
        m.*,
        p.passenger_id AS pID,
        p.name         AS pName,
        c.company_id   AS cID,
        c.name         AS cName,
        f.name         AS flightName,
        f.flight_id    AS realFlightId
    FROM Message m
    LEFT JOIN Passenger p ON m.sender_id = p.passenger_id
    LEFT JOIN Company   c ON m.sender_id = c.company_id
    LEFT JOIN Flight    f ON m.flight_id = f.flight_id
    WHERE m.receiver_id = $passenger_id
    ORDER BY m.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Passenger Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .message {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fdfdfd;
        }
        .message strong {
            color: #555;
        }
        .message small {
            display: block;
            margin-top: 10px;
            color: #777;
        }
        .message p {
            color: #444;
            margin: 5px 0;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .back-link:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($passengerName); ?> - Messages</h1>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="message">
                    <p>
                        <strong>From:</strong>
                        <?php
                          if ($row["pID"]) {
                            // Sender is a passenger
                            echo "Passenger " . htmlspecialchars($row["pName"]) . " (ID: " . (int)$row["pID"] . ")";
                          } else if ($row["cID"]) {
                            // Sender is a company
                            echo "Company " . htmlspecialchars($row["cName"]) . " (ID: " . (int)$row["cID"] . ")";
                          } else {
                            echo "Unknown Sender (ID: " . (int)$row["sender_id"] . ")";
                          }
                        ?>
                    </p>
                    <p>
                        <strong>Regarding Flight:</strong> 
                        <?php
                            if (!empty($row["flightName"])) {
                                echo htmlspecialchars($row["flightName"]) . " (ID: " . (int)$row["realFlightId"] . ")";
                            } else {
                                echo "N/A";
                            }
                        ?>
                    </p>
                    <p><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                    <small><em>Sent at: <?php echo htmlspecialchars($row["created_at"]); ?></em></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #888;">No messages yet.</p>
        <?php endif; ?>

        <a href="passenger_home.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
