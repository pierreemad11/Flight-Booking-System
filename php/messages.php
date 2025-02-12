<?php
// messages.php
include("db.php");
session_start();

// Must be company
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "company") {
    header("Location: ../pages/login_page.html");
    exit();
}

$company_id = $_SESSION["company_id"];

// fetch your company name for display
$companyNameQuery = "SELECT name FROM Company WHERE company_id = $company_id";
$companyNameResult= $conn->query($companyNameQuery);
$companyName      = "Your Company";
if ($companyNameResult && $companyNameResult->num_rows > 0) {
    $row          = $companyNameResult->fetch_assoc();
    $companyName  = $row["name"];
}

// Retrieve all messages for this company. 
$sql = "
    SELECT 
        m.*,
        p.name AS passengerName,
        p.passenger_id AS pID,
        c.name AS companySenderName,
        c.company_id AS cID,
        f.name AS flightName,
        f.flight_id AS realFlightId
    FROM Message m
    LEFT JOIN Passenger p ON m.sender_id = p.passenger_id
    LEFT JOIN Company   c ON m.sender_id = c.company_id
    LEFT JOIN Flight    f ON m.flight_id = f.flight_id
    WHERE m.receiver_id = $company_id
    ORDER BY m.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Company Messages</title>
    <style>
       body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-image: url('../images/Company_background.png'); 
        background-size: cover; 
        background-repeat: no-repeat; 
        background-position: center; 
        height: 100vh;
        display: flex; /* Enable flexbox */
        justify-content: center; /* Center horizontally */
        align-items: center; /* Center vertically */
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
        a.back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
        }
        a.back-link:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($companyName); ?> - Inbox</h1>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="message">
                    <p>
                        <strong>From:</strong>
                        <?php
                          if ($row["pID"]) {
                            echo "Passenger " . htmlspecialchars($row["passengerName"]) . " (ID: " . (int)$row["pID"] . ")";
                          } else if ($row["cID"]) {
                            echo "Company " . htmlspecialchars($row["companySenderName"]) . " (ID: " . (int)$row["cID"] . ")";
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

        <a href="company_home.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
