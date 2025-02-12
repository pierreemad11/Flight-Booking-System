<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    $usernameform = htmlspecialchars($_POST["username"] ?? "");
    $name         = htmlspecialchars($_POST["name"]);
    $passwordform = $_POST["password"];
    $telephone    = htmlspecialchars($_POST["telephone"]);
    $userType     = htmlspecialchars($_POST["userType"]);

    // Handle file uploads
    $uploadedFiles = [];
    foreach (['logo', 'photo', 'passportImage'] as $fileKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $targetDir  = "../images/";
            $targetFile = $targetDir . basename($_FILES[$fileKey]["name"]);
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Validate file type
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                die("Invalid file type for $fileKey. Only JPG, JPEG, PNG, or GIF allowed.");
            }

            // Move file to uploads directory
            if (!move_uploaded_file($_FILES[$fileKey]["tmp_name"], $targetFile)) {
                die("Error uploading $fileKey.");
            }

            $uploadedFiles[$fileKey] = $targetFile;
        }
    }

    // Check if the email is already used by ANY user (company or passenger)
    $generalEmailCheck = "
        SELECT email FROM passenger WHERE email = '$email'
        UNION
        SELECT email FROM Company WHERE email = '$email'
    ";
    $generalEmailResult = mysqli_query($conn, $generalEmailCheck);

    if (mysqli_num_rows($generalEmailResult) > 0) {
        // Email found in either table => can't register
        echo "This email is already registered. Please try logging in.";
        mysqli_close($conn);
        exit;
    }

    // For company users only, also check if the chosen username is already taken
    if ($userType === "company") {
        $usernameCheckQuery = "SELECT username FROM Company WHERE username = '$usernameform'";
        $usernameCheckResult = mysqli_query($conn, $usernameCheckQuery);

        if (mysqli_num_rows($usernameCheckResult) > 0) {
            echo "This username is already taken. Please choose another.";
            mysqli_close($conn);
            exit;
        }
    }

    // Hash the password for security
    $hashedPassword = password_hash($passwordform, PASSWORD_DEFAULT);

    // Prepare SQL query based on userType
    if ($userType === "company") {
        $companyName     = htmlspecialchars($_POST["company_name"]);
        $bio             = htmlspecialchars($_POST["bio"]);
        $address         = htmlspecialchars($_POST["address"]);
        $location        = htmlspecialchars($_POST["location"]);
        // Force company to start with 0 balance
        $account_balance = 0;

        $sql = "INSERT INTO Company (name, bio, address, location, username, password, email, tel, account_balance, logo_img)
                VALUES (
                    '$companyName',
                    '$bio',
                    '$address',
                    '$location',
                    '$usernameform',
                    '$hashedPassword',
                    '$email',
                    '$telephone',
                    '$account_balance',
                    '{$uploadedFiles['logo']}'
                )";
    } else {
        // userType is "passenger"
        $sql = "INSERT INTO passenger (name, email, password, tel, photo, passport_img)
                VALUES (
                    '$name',
                    '$email',
                    '$hashedPassword',
                    '$telephone',
                    '{$uploadedFiles['photo']}',
                    '{$uploadedFiles['passportImage']}'
                )";
    }

    if (mysqli_query($conn, $sql)) {
        echo "Registration successful!<br>";
        echo "You can now log in with your new account. <a href='../pages/login_page.html'>Go to Login</a>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Close the connection
    mysqli_close($conn);
}
?>
