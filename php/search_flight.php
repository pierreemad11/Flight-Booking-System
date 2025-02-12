<?php
include("db.php");
session_start();

 if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "passenger") {
     header("Location: ../pages/login_page.html");
     exit();
  }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Search Flight</title>
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .container {
            width: 80%;
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-top: 0;
            color: #333;
        }

        .search-form {
            margin-bottom: 20px;
            text-align: center;
        }

        .search-form label {
            font-weight: bold;
            color: #333;
        }

        .search-form input[type="text"] {
            width: 250px; 
            padding: 6px;
            margin-top: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .search-form button {
            margin-top: 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background: #28a745; 
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .search-form button:hover {
            background: #218838;
        }

        table.results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.results-table th,
        table.results-table td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }
        table.results-table th {
            background-color: #e9e9e9;
            color: #333;
        }

        .col-id { width: 10%; }
        .col-name { width: 20%; }
        .col-from { width: 20%; }
        .col-to { width: 20%; }
        .col-fees { width: 15%; }
        .col-seats { width: 15%; }
        .col-action { width: 15%; }

        .btn-view-details {
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s ease;
            font-weight: bold;
        }
        .btn-view-details:hover {
            background: #0056b3;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Search for a Flight</h1>

    <!-- Search Form -->
    <form class="search-form" method="get" action="search_flight.php">
        <label for="fromCity">From (Departure):</label><br>
        <input type="text" id="fromCity" name="fromCity" required><br><br>

        <label for="toCity">To (Destination):</label><br>
        <input type="text" id="toCity" name="toCity" required><br><br>

        <button type="submit">Search</button>
    </form>

    <?php
    if (isset($_GET["fromCity"]) && isset($_GET["toCity"])) {
        $fromCity = mysqli_real_escape_string($conn, $_GET["fromCity"]);
        $toCity   = mysqli_real_escape_string($conn, $_GET["toCity"]);

        // Simple search
        $sql = "
            SELECT * FROM Flight
            WHERE departure LIKE '%$fromCity%'
              AND destination LIKE '%$toCity%'
              AND is_completed = 0
        ";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "<table class='results-table'>";
            echo "<tr>
                    <th class='col-id'>Flight ID</th>
                    <th class='col-name'>Name</th>
                    <th class='col-from'>From</th>
                    <th class='col-to'>To</th>
                    <th class='col-fees'>Fees</th>
                    <th class='col-seats'>Seats Left</th>
                    <th class='col-action'>Action</th>
                  </tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".htmlspecialchars($row["flight_id"])."</td>";
                echo "<td>".htmlspecialchars($row["name"])."</td>";
                echo "<td>".htmlspecialchars($row["departure"])."</td>";
                echo "<td>".htmlspecialchars($row["destination"])."</td>";
                echo "<td>".htmlspecialchars($row["fees"])."</td>";
                echo "<td>".htmlspecialchars($row["passengers_number"])."</td>";

                // Link to flight_info.php for details/booking
                echo "<td>
                        <a href='flight_info.php?flight_id=".$row["flight_id"]."' 
                           class='btn-view-details'>
                           View Details
                        </a>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No flights found for your search criteria.</p>";
        }
    }
    ?>

    <br>
    <a href="passenger_home.php" class="back-link">Back to Passenger Home</a>
</div>

</body>
</html>
<?php
$conn->close();
?>
