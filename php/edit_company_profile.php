<?php
include ("db.php");


$company_edit_id = $_GET["edit_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $newName           = $_POST["new_name"];
    $newBio            = $_POST["new_bio"];
    $newAddress        = $_POST["new_address"];
    $newLocation       = $_POST["new_location"];
    $newUsername       = $_POST["new_username"];
    $newEmail          = $_POST["new_email"];
    $newTel            = $_POST["new_tel"];
    $newLogoImg        = $_POST["new_logo_img"];
    $newAccountBalance = $_POST["new_account_balance"];

    $updateQuery = "
        UPDATE `Company` 
        SET
          `name`            = '$newName',
          `bio`             = '$newBio',
          `address`         = '$newAddress',
          `location`        = '$newLocation',
          `username`        = '$newUsername',
          `email`           = '$newEmail',
          `tel`             = '$newTel',
          `logo_img`        = '$newLogoImg',
          `account_balance` = '$newAccountBalance'
        WHERE `company_id` = $company_edit_id
    ";

    if ($conn->query($updateQuery) === TRUE) {
        // Redirect after successful update
        echo "Company information updated successfully";
    } else {
        echo "Error updating company information: " . $conn->error;
    }
}

// Retrieve the current company information for pre-filling the form
$companyQuery = "SELECT * FROM `Company` WHERE `company_id` = $company_edit_id";
$companyResult = $conn->query($companyQuery);

if ($companyResult->num_rows > 0) {
    $companyData = $companyResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?php echo htmlspecialchars($companyData["name"]); ?> Profile</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        .edit-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .edit-container h1 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }

        form {
            display: block;
            width: 100%;
        }

        label {
            display: block;
            margin: 12px 0 5px 0;
            font-weight: bold;
            color: #333;
        }

        input, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #2980b9;
        }

        .note {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        @media (max-width: 480px) {
            .edit-container {
                margin: 20px auto;
                padding: 15px;
            }
            label, input, textarea, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>Edit <?php echo htmlspecialchars($companyData["name"]); ?> Profile</h1>

        <!-- Edit Company Profile Form -->
        <form method="post" action="">
            <label for="new_name">Name:</label>
            <input 
                type="text" 
                id="new_name" 
                name="new_name" 
                value="<?php echo htmlspecialchars($companyData["name"]); ?>" 
                required
            >

            <label for="new_bio">Bio:</label>
            <textarea 
                id="new_bio" 
                name="new_bio" 
                rows="4"
            ><?php echo htmlspecialchars($companyData["bio"]); ?></textarea>

            <label for="new_address">Address:</label>
            <input 
                type="text" 
                id="new_address" 
                name="new_address" 
                value="<?php echo htmlspecialchars($companyData["address"]); ?>" 
                required
            >

            <label for="new_location">Location:</label>
            <input 
                type="text" 
                id="new_location" 
                name="new_location" 
                value="<?php echo htmlspecialchars($companyData["location"]); ?>"
            >

            <label for="new_username">Username:</label>
            <input 
                type="text" 
                id="new_username" 
                name="new_username" 
                value="<?php echo htmlspecialchars($companyData["username"]); ?>" 
                required
            >

            <label for="new_email">Email:</label>
            <input 
                type="email" 
                id="new_email" 
                name="new_email" 
                value="<?php echo htmlspecialchars($companyData["email"]); ?>" 
                required
            >

            <label for="new_tel">Tel:</label>
            <input 
                type="text" 
                id="new_tel" 
                name="new_tel" 
                value="<?php echo htmlspecialchars($companyData["tel"]); ?>" 
                required
            >

            <label for="new_logo_img">Logo Image (URL):</label>
            <input 
                type="text" 
                id="new_logo_img" 
                name="new_logo_img" 
                value="<?php echo htmlspecialchars($companyData["logo_img"]); ?>"
            >

            <label for="new_account_balance">Account Balance:</label>
            <input 
                type="number" 
                id="new_account_balance" 
                name="new_account_balance" 
                value="<?php echo htmlspecialchars($companyData["account_balance"]); ?>" 
                step="0.01"
            >

            <button type="submit">Update</button>
        </form>

        <p class="note">
            Make sure to fill all required fields accurately.
        </p>
    </div>
</body>
</html>

<?php
}
$conn->close();
?>
