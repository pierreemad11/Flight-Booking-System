<?php
session_start();
include("db.php");

// If no user type, not logged in
if (!isset($_SESSION["user_type"])) {
    header("Location: ../pages/login_page.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // If the user_sending field indicates passenger -> company
    if (isset($_POST["user_sending"]) && $_POST["user_sending"] === "passenger") {
        // Make sure session user_type also matches "passenger"
        if ($_SESSION["user_type"] !== "passenger") {
            echo "<script>
                    alert('Unauthorized action. You are not a passenger.');
                    window.location.href='../pages/login_page.html';
                  </script>";
            $conn->close();
            exit();
        }

        $sender_id           = $_SESSION["passenger_id"];
        $receiver_company_id = intval($_POST["receiver_company_id"] ?? 0);
        $messageContent      = mysqli_real_escape_string($conn, $_POST["message"] ?? '');
        $flight_id           = $_POST["flight_id"] ?? '';

        // Validate
        if ($receiver_company_id <= 0) {
            echo "<script>
                    alert('Invalid or missing company_id!');
                    window.location.href='passenger_home.php';
                  </script>";
            $conn->close();
            exit();
        }

        // Convert flight_id to either an int or NULL
        if (ctype_digit($flight_id) && (int)$flight_id > 0) {
            $flightValue = (int)$flight_id;
        } else {
            $flightValue = "NULL";
        }

        $sql = "INSERT INTO Message (sender_id, receiver_id, flight_id, content)
                VALUES ($sender_id, $receiver_company_id, $flightValue, '$messageContent')";

        if ($conn->query($sql) === TRUE) {
            // go back to flight_info.php with the same flight_id
            if ($flightValue !== "NULL") {
                // flightValue is an integer => redirect to flight_info
                echo "<script>
                        alert('Message sent successfully!');
                        window.location.href='../php/flight_info.php?flight_id=$flightValue';
                      </script>";
            } else {
                // If no flight_id, fallback
                echo "<script>
                        alert('Message sent successfully!');
                        window.location.href='passenger_home.php';
                      </script>";
            }
        } else {
            echo 'Error: ' . $sql . '<br>' . $conn->error;
        }
    }

    // Else if the user is company => company -> passenger
    else if (isset($_POST["user_sending"]) && $_POST["user_sending"] === "company") {
        if ($_SESSION["user_type"] !== "company") {
            echo "<script>
                    alert('Unauthorized action. You are not a company.');
                    window.location.href='../pages/login_page.html';
                  </script>";
            $conn->close();
            exit();
        }

        $sender_company_id    = $_SESSION["company_id"];
        $receiver_passenger_id= intval($_POST["receiver_passenger_id"] ?? 0);
        $messageContent       = mysqli_real_escape_string($conn, $_POST["message"] ?? '');
        $flight_id            = $_POST["flight_id"] ?? '';

        if ($receiver_passenger_id <= 0) {
            echo "<script>
                    alert('Invalid or missing passenger_id!');
                    window.location.href='../pages/company_home.php';
                  </script>";
            $conn->close();
            exit();
        }

        if (ctype_digit($flight_id) && (int)$flight_id > 0) {
            $flightValue = (int)$flight_id;
        } else {
            $flightValue = "NULL";
        }

        $sql = "INSERT INTO Message (sender_id, receiver_id, flight_id, content)
                VALUES ($sender_company_id, $receiver_passenger_id, $flightValue, '$messageContent')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    alert('Message sent successfully!');
                    // E.g. redirect to messages page for company 
                    window.location.href='messages.php';
                  </script>";
        } else {
            echo 'Error: ' . $sql . '<br>' . $conn->error;
        }
    }

} // end if POST

$conn->close();
?>
