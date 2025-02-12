<?php
include ("db.php");

$company_temp_id = $_GET["profile_id"];

$companyQuery = "SELECT * FROM `Company` WHERE `company_id` = $company_temp_id";
$companyResult = $conn->query($companyQuery);

if ($companyResult->num_rows > 0) {
    $companyData = $companyResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyData["name"]); ?> Profile</title>
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
        }

        .company-profile {
            background-color:rgb(255, 255, 255);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            box-sizing: border-box;
            float: left;
            margin-left: 100px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }

        .company-logo {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 20px auto;
        }

        p {
            color: #333;
            margin: 8px 0;
            line-height: 1.4;
        }

        h3 {
            margin-top: 30px;
            color: #333;
        }

        .edit-link {
            display: inline-block;
            margin-top: 20px;
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

        .flight-item {
            margin: 4px 0;
        }

        @media (max-width: 480px) {
            .company-profile {
                width: 95%;
                margin: 15px auto;
                padding: 15px;
            }
            .company-logo {
                max-width: 150px;
            }
        }
    </style>
</head>

<body>

    <section class="company-profile">
        <h2><?php echo htmlspecialchars($companyData["name"]); ?> (Profile)</h2>

        <img 
            src="../images/<?php echo htmlspecialchars($companyData["logo_img"]); ?>" 
            alt="Company Logo" 
            class="company-logo"
        >

        <!-- Company Details -->
        <p><strong>Name:</strong> <?php echo htmlspecialchars($companyData["name"]); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($companyData["username"]); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($companyData["email"]); ?></p>
        <p><strong>Bio:</strong> <?php echo htmlspecialchars($companyData["bio"]); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($companyData["address"]); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($companyData["location"]); ?></p>
        <p><strong>Tel:</strong> <?php echo htmlspecialchars($companyData["tel"]); ?></p>

        <h3>Flights List</h3>
        <?php
        $flightsQuery = "SELECT * FROM `Flight` WHERE `company_id` = $company_temp_id";
        $flightsResult = $conn->query($flightsQuery);

        if ($flightsResult->num_rows > 0) {
            while ($flightData = $flightsResult->fetch_assoc()) {
                echo "<p class='flight-item'>" . htmlspecialchars($flightData['name']) . "</p>";
            }
        } else {
            echo "<p>No flights available</p>";
        }
        ?>
        <a 
            href="edit_company_profile.php?edit_id=<?php echo urlencode($company_temp_id); ?>" 
            class="edit-link"
        >
            Edit
        </a>
    </section>



</body>
</html>

<?php
}
$conn->close();
?>
