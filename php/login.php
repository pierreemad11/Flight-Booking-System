<?php
include("db.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Check if the user is a passenger
    $passengerSql = "SELECT * FROM `passenger` WHERE `email`='$email'";
    $passengerResult = $conn->query($passengerSql);

    if ($passengerResult->num_rows > 0) {
        $passengerData = $passengerResult->fetch_assoc();

        if (password_verify($password, $passengerData["password"])) {
            $_SESSION["user_type"] = "passenger";
            $_SESSION["passenger_id"] = $passengerData["passenger_id"];
            header("Location: passenger_home.php");
            exit();
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    } else {
        // Check if the user is a company
        $companySql = "SELECT * FROM `Company` WHERE `email`='$email'";
        $companyResult = $conn->query($companySql);

        if ($companyResult->num_rows > 0) {
            $companyData = $companyResult->fetch_assoc();

            if (password_verify($password, $companyData["password"])) {
                $_SESSION["user_type"] = "company";
                $_SESSION["company_id"] = $companyData["company_id"];
                $_SESSION["company_name"] = $companyData["name"];
                header("Location: company_home.php");
                exit();
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        form {
            width: 100%;
        }

        input[type="email"], input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: #fff;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        a {
            color: #3498db;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h1>Login</h1>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter your password" required>
        <button type="submit">Login</button>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
    </form>
    <a href="../pages/register_page.html">Don't have an account? Register</a>
</div>
</body>
</html>
