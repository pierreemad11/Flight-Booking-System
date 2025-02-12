<?php
include("db.php");
session_start(); // Start the session

// Check if the user is company
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "company") {
    header("Location: ../pages/login_page.html");
    exit();
}

// Retrieve session variables
$company_id   = $_SESSION["company_id"];
$company_name = $_SESSION["company_name"];

// Fetch company details, including logo image AND account_balance
$companyDetailsQuery = "
    SELECT logo_img, account_balance 
    FROM Company 
    WHERE company_id = '$company_id'
";
$companyDetailsResult = mysqli_query($conn, $companyDetailsQuery);
$companyData = mysqli_fetch_assoc($companyDetailsResult); // Fetching the company data

// Fetch flights associated with the company
$flightsQuery = "SELECT * FROM Flight WHERE company_id = '$company_id'";
$flightsResult = mysqli_query($conn, $flightsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Company Home</title>
  <link rel="stylesheet" type="text/css" href="../css/style.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  background-image: url('../images/Company_background.png'); 
  background-size: cover; 
  background-repeat: no-repeat; 
  background-position: center; 
  height: 100vh; 
}

    /* Make nav fixed (or sticky) so user can see it on scroll */
    nav {
      background-color: #2f86c5;
      padding: 10px 0;
      position: sticky; 
      top: 0;
      z-index: 999; 
      width: 100%;
    }

    nav ul {
      list-style: none;
      display: flex;
      justify-content: center;
    }

    nav ul li {
      margin: 0 15px;
    }

    nav a {
      text-decoration: none;
      color: #fff;
      padding: 8px 15px;
      border-radius: 4px;
      transition: 0.3s;
      font-weight: bold;
    }

    nav a:hover {
      background-color:rgb(72, 18, 209);
    }


    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      margin-top: 150px; 
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(227, 7, 7, 0.1);
      padding: 20px;
      box-sizing: border-box;
    }

    /* Header styling */
    header {
      text-align: center;
      margin-bottom: 20px;
    }

    header h1 {
      margin-top: 20px;
      color: #333;
    }

    .company-logo {
      max-width: 300px; 
      height: auto;
      margin: 20px auto; 
      display: block;
    }

    main h2 {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }

    table {
      margin: 0 auto 30px auto;
      border-collapse: collapse;
      width: 100%;
    }

    table, th, td {
      border: 1px solid #ddd;
      text-align: center;
      padding: 12px;
    }

    th {
      background-color: #2f86c5; 
      color: #fff;
      font-weight: bold;
    }

    tr:hover {
      background-color: #f9f9f9;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .container {
        width: 95%;
        margin-top: 70px; /* slightly smaller top margin on narrower screens */
        padding: 15px;
      }

      nav ul li {
        margin: 0 5px;
      }

      table, th, td {
        font-size: 14px;
        padding: 8px;
      }
    }
  </style>
</head>

<body>
  <nav>
    <ul>
      <li><a href="../pages/add_flight_page.html">Add Flightss</a></li>
      <li><a href="company_profile.php?profile_id=<?php echo $company_id; ?>">Profile</a></li>
      <li><a href="messages.php">Messages</a></li>
    </ul>
  </nav>

  <div class="container">
    <header>
      <h1>Welcome, <?php echo htmlspecialchars($company_name); ?>!</h1>
      <img 
        src="../images/<?php echo htmlspecialchars($companyData['logo_img']); ?>" 
        alt="Flight Booking Logo" 
        class="company-logo"
      >
      <!-- Display company account balance -->
      <p style="font-size: 18px; color: #333; margin-top: 10px;">
        <strong>Company Balance:</strong> 
        $<?php echo htmlspecialchars($companyData["account_balance"]); ?>
      </p>
    </header>

    <main>
      <h2>Your Flights</h2>

      <?php if (mysqli_num_rows($flightsResult) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Flight ID</th>
              <th>Name</th>
              <th>Itinerary</th>
              <th>Passengers</th>
              <th>Fees</th>
              <th>Seats left</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($flight = mysqli_fetch_assoc($flightsResult)): ?>
              <?php
                // Build itinerary using departure, stops, and destination
                $itinerary = $flight["departure"];
                if (!empty($flight["stops"])) {
                  $itinerary .= " => " . $flight["stops"];
                }
                $itinerary .= " => " . $flight["destination"];

                $flight_id = $flight['flight_id'];

                // Count how many have payment_status='completed'
                $completedQuery = "
                  SELECT COUNT(*) AS complete_count
                  FROM Passenger_Flight
                  WHERE flight_id = $flight_id
                    AND payment_status = 'completed'
                ";
                $completedResult = mysqli_query($conn, $completedQuery);
                $completedRow = mysqli_fetch_assoc($completedResult);
                $completedCount = $completedRow['complete_count'] ?? 0;

                // Count how many have payment_status='pending'
                $pendingQuery = "
                  SELECT COUNT(*) AS pending_count
                  FROM Passenger_Flight
                  WHERE flight_id = $flight_id
                    AND payment_status = 'pending'
                ";
                $pendingResult = mysqli_query($conn, $pendingQuery);
                $pendingRow = mysqli_fetch_assoc($pendingResult);
                $pendingCount = $pendingRow['pending_count'] ?? 0;

                // Determine flight-level status: canceled vs. active
                $statusLabel = $flight["is_completed"]
                  ? "<span style='color:red;font-weight:bold;'>Canceled</span>"
                  : "<span style='color:green;font-weight:bold;'>Active</span>";
              ?>
              <tr onclick="openFlightDetails(<?php echo $flight['flight_id']; ?>)">
                <td><?php echo htmlspecialchars($flight['flight_id']); ?></td>
                <td><?php echo htmlspecialchars($flight['name']); ?></td>
                <td><?php echo htmlspecialchars($itinerary); ?></td>
                <td>
                  completed: <?php echo (int)$completedCount; ?><br>
                  pending: <?php echo (int)$pendingCount; ?>
                </td>
                <td><?php echo htmlspecialchars($flight['fees']); ?></td>
                <td><?php echo (int)$flight["passengers_number"] . " seats left"; ?></td>
                <td><?php echo $statusLabel; ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No flights found. Add a new flight to get started!</p>
      <?php endif; ?>
    </main>
  </div>

  <script>
    function openFlightDetails(flightId) {
      window.location.href = "flight_details.php?flight_id=" + flightId;
    }
  </script>
</body>
</html>

<?php
$conn->close();
?>
  


  