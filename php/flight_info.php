<?php
include("db.php");
session_start();

// Must be passenger
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "passenger") {
    header("Location: ../pages/login_page.html");
    exit();
}

$passenger_id = $_SESSION["passenger_id"];

if (!isset($_GET["flight_id"])) {
    echo "No flight ID provided.";
    exit();
}

$flight_id = intval($_GET["flight_id"]);

// ========== HANDLE BOOKING FORM SUBMISSION ==========
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["take_flight"])) {
    $payType = $_POST["pay_type"];

    // 1) Retrieve flight info
    $feesQuery = "SELECT fees, is_completed, passengers_number
                  FROM Flight
                  WHERE flight_id = $flight_id";
    $feesResult = $conn->query($feesQuery);

    if ($feesResult->num_rows > 0) {
        $row = $feesResult->fetch_assoc();
        $flightFees      = floatval($row["fees"]);
        $flightCompleted = intval($row["is_completed"]);
        $currentSeats    = intval($row["passengers_number"]);

        if ($flightCompleted === 1) {
            echo "<script>alert('This flight is no longer available (already completed or canceled).');</script>";
        } else {
            if ($currentSeats <= 0) {
                echo "<script>
                        alert('No seats left on this flight!');
                        window.location.href='search_flight.php';
                      </script>";
                exit();
            }

            // Check if passenger already booked
            $checkBooking = "
                SELECT *
                FROM Passenger_Flight
                WHERE passenger_id = $passenger_id
                  AND flight_id = $flight_id
            ";
            $checkResult = $conn->query($checkBooking);

            if ($checkResult->num_rows > 0) {
                echo "<script>alert('You have already booked or have a pending request for this flight.');</script>";
            } else {
                // Paying from account
                if ($payType === 'account') {
                    $balanceQuery = "
                        SELECT account_balance
                        FROM Passenger
                        WHERE passenger_id = $passenger_id
                    ";
                    $balanceResult = $conn->query($balanceQuery);
                    $balanceRow    = $balanceResult->fetch_assoc();
                    $currentBalance= floatval($balanceRow["account_balance"]);

                    if ($currentBalance >= $flightFees) {
                        $newBalance = $currentBalance - $flightFees;
                        $updateBalance = "
                            UPDATE Passenger
                            SET account_balance = $newBalance
                            WHERE passenger_id = $passenger_id
                        ";
                        $conn->query($updateBalance);

                        $bookingQuery = "
                            INSERT INTO Passenger_Flight (passenger_id, flight_id, status, payment_status)
                            VALUES ($passenger_id, $flight_id, 'pending', 'completed')
                        ";
                        $conn->query($bookingQuery);

                        $updateSeats = "
                            UPDATE Flight
                            SET passengers_number = passengers_number - 1
                            WHERE flight_id = $flight_id
                        ";
                        $conn->query($updateSeats);

                        // Increase company's balance
                        $flightCompanyQuery = "SELECT company_id FROM Flight WHERE flight_id = $flight_id";
                        $flightCompanyResult= $conn->query($flightCompanyQuery);
                        if ($flightCompanyResult->num_rows > 0) {
                            $companyRow     = $flightCompanyResult->fetch_assoc();
                            $thisCompanyId  = $companyRow["company_id"];

                            $updateCompanyBalance = "
                                UPDATE Company
                                SET account_balance = account_balance + $flightFees
                                WHERE company_id = $thisCompanyId
                            ";
                            $conn->query($updateCompanyBalance);
                        }

                        echo "<script>
                                alert('Flight booked successfully using account balance!');
                                window.location.href='passenger_home.php';
                              </script>";
                    } else {
                        echo "<script>alert('Insufficient account balance. Choose cash or top up your account.');</script>";
                    }

                // Paying by cash
                } else {
                    $bookingQuery = "
                        INSERT INTO Passenger_Flight (passenger_id, flight_id, status, payment_status)
                        VALUES ($passenger_id, $flight_id, 'pending', 'pending')
                    ";
                    $conn->query($bookingQuery);

                    $updateSeats = "
                        UPDATE Flight
                        SET passengers_number = passengers_number - 1
                        WHERE flight_id = $flight_id
                    ";
                    $conn->query($updateSeats);

                    echo "<script>
                            alert('Flight booked in pending status. Please pay cash to the company.');
                            window.location.href='passenger_home.php';
                          </script>";
                }
            }
        }
    }
}

// ========== FETCH FLIGHT DETAILS ==========
$flightQuery = "
    SELECT f.*, c.name AS companyName
    FROM Flight f
    JOIN Company c ON f.company_id = c.company_id
    WHERE f.flight_id = $flight_id
";
$flightResult = $conn->query($flightQuery);

if ($flightResult->num_rows === 0) {
    echo "Flight not found.";
    exit();
}

$flightData = $flightResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Flight Info</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 90%;
      max-width: 800px;
      margin: 30px auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      padding: 20px;
    }
    h1 {
      color: #333;
      text-align: center;
    }
    p {
      color: #555;
      margin: 10px 0;
    }
    .btn-book, a {
      display: inline-block;
      text-align: center;
      text-decoration: none;
      color: #fff;
      background-color: #3498db;
      padding: 10px 20px;
      border-radius: 5px;
      margin: 10px 5px;
      transition: 0.3s ease;
    }
    .btn-book:hover, a:hover {
      background-color: #2980b9;
    }
    select {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 5px;
      border: 1px solid #ddd;
    }
    form {
      margin: 20px 0;
    }
    @media (max-width: 600px) {
      .container {
        padding: 15px;
      }
      .btn-book, a {
        width: 100%;
        padding: 12px;
      }
    }

    /* ===== Floating Chat Styles  ===== */
    #miniChatContainer {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 300px;
      max-height: 400px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 6px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
      display: none;
      flex-direction: column;
      z-index: 9999;
    }
    #miniChatHeader {
      background: #3498db;
      color: #fff;
      padding: 10px;
      border-radius: 6px 6px 0 0;
      font-weight: bold;
      display: flex;
      justify-content: space-between;
      cursor: pointer;
    }
    #miniChatBody {
      display: flex;
      flex-direction: column;
      padding: 10px;
      overflow-y: auto;
      flex: 1;
    }
    #miniChatFooter {
      padding: 10px;
      border-top: 1px solid #ddd;
    }
    #miniChatMessage {
      width: 100%;
      margin-bottom: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 6px;
      box-sizing: border-box;
    }
    #miniChatSendBtn {
      background-color: #3498db;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
    }
    #miniChatSendBtn:hover {
      background-color: #2980b9;
    }
    .miniChat-close-btn {
      color: #fff;
      font-weight: bold;
      text-decoration: none;
      cursor: pointer;
    }
    /* Bubbles */
    .chat-message-bubble {
      margin: 5px 0;
      padding: 6px 8px;
      border-radius: 5px;
      max-width: 80%;
    }
    .chat-bubble-me {
      background: #d1ecf1; 
      align-self: flex-end;
      text-align: right;
    }
    .chat-bubble-other {
      background: #f0f0f0;
      align-self: flex-start;
      text-align: left;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>Flight Details</h1>
  <p><strong>Flight ID:</strong> <?php echo htmlspecialchars($flightData["flight_id"]); ?></p>
  <p><strong>Name:</strong> <?php echo htmlspecialchars($flightData["name"]); ?></p>
  <p><strong>Company:</strong> <?php echo htmlspecialchars($flightData["companyName"]); ?></p>
  <p><strong>Departure:</strong> <?php echo htmlspecialchars($flightData["departure"]); ?></p>
  <p><strong>Destination:</strong> <?php echo htmlspecialchars($flightData["destination"]); ?></p>
  <p><strong>Stops:</strong> <?php echo htmlspecialchars($flightData["stops"]); ?></p>
  <p><strong>Fees:</strong> <?php echo htmlspecialchars($flightData["fees"]); ?></p>
  <p><strong>Start Time:</strong> <?php echo htmlspecialchars($flightData["start_time"]); ?></p>
  <p><strong>End Time:</strong> <?php echo htmlspecialchars($flightData["end_time"]); ?></p>
  <p><strong>Seats Left:</strong> <?php echo htmlspecialchars($flightData["passengers_number"]); ?></p>

  <?php if (!$flightData["is_completed"]): ?>
  <form method="post" action="">
    <label for="pay_type">Payment Method:</label>
    <select name="pay_type" id="pay_type" required>
      <option value="" disabled selected>Select Payment</option>
      <option value="account">From Account $</option>
      <option value="cash">Cash</option>
    </select>
    <button type="submit" name="take_flight" class="btn-book">Book Flight</button>
  </form>
  <?php else: ?>
    <p><strong>Status:</strong> This flight is no longer available (completed/canceled).</p>
  <?php endif; ?>

  <!-- Chat Button to open the floating chat -->
  <button 
  style="background:#3498db; color:#fff; padding:10px 20px; border:none; border-radius:4px; cursor:pointer;"
  onclick="openChatWidget(<?php echo (int)$flightData['company_id']; ?>, <?php echo (int)$flight_id; ?>)"
>
  Chat with Company
</button>


  <a href="search_flight.php">Back to Search</a>
</div>

<!-- The floating chat container -->
<div id="miniChatContainer">
  <div id="miniChatHeader" onclick="toggleMiniChat()">
    <span>Chat</span>
    <span class="miniChat-close-btn" onclick="toggleMiniChat()">✕</span>
  </div>

  <div id="miniChatBody">
    <!-- Bubbles will be appended here by renderMessages() -->
  </div>

  <div id="miniChatFooter">
    <!-- We'll store receiver_id = the company's ID -->
    <input type="hidden" id="receiver_id" value="">
    <input type="hidden" id="flightIdHidden" value="">

    <textarea id="miniChatMessage" rows="2" placeholder="Type your message..."></textarea>
    <button id="miniChatSendBtn" onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
// We'll store the passenger's ID in a JS variable
window.currentUserId = <?php echo (int)$passenger_id; ?>;

// Toggle the floating chat
function toggleMiniChat() {
  const c = document.getElementById("miniChatContainer");
  if (c.style.display === "none" || c.style.display === "") {
    c.style.display = "flex";
  } else {
    c.style.display = "none";
  }
}

// When user clicks "Chat with Company"
function openChatWidget(companyId, flightId) {
  document.getElementById("receiver_id").value = companyId;
  document.getElementById("flightIdHidden").value = flightId;

  toggleMiniChat();   // show chat widget
  loadConversation(); // load old messages if you want
}


// 1) loadConversation => AJAX GET to ajax_load_chat.php?receiver_id=...
async function loadConversation() {
  const receiverId = document.getElementById("receiver_id").value;
  if (!receiverId) return;

  try {
    const resp = await fetch(`../php/ajax_load_chat.php?receiver_id=${receiverId}`);
    const data = await resp.json(); // an array of messages
    renderMessages(data);
  } catch (err) {
    console.error("Error loading chat:", err);
  }
}

// 2) renderMessages => display the conversation in #miniChatBody
function renderMessages(messages) {
  const chatBody = document.getElementById("miniChatBody");
  chatBody.innerHTML = ""; // clear old content

  messages.forEach(msg => {
    // determine if the message is from me or from the other
    const isMine = (msg.sender_id == window.currentUserId);
    let bubbleClass = isMine ? 'chat-bubble-me' : 'chat-bubble-other';

    let bubble = document.createElement("div");
    bubble.classList.add("chat-message-bubble");
    bubble.classList.add(bubbleClass);

    bubble.innerText = msg.content;

    chatBody.appendChild(bubble);
  });

  // scroll to bottom
  chatBody.scrollTop = chatBody.scrollHeight;
}

// 3) sendMessage => AJAX POST to send_message.php with user_sending=passenger
async function sendMessage() {
  const messageBox = document.getElementById("miniChatMessage");
  const receiverId = document.getElementById("receiver_id").value;
  const flightVal  = document.getElementById("flightIdHidden").value; // flight ID

  const content    = messageBox.value.trim();
  if (!content) return;

  let formData = new FormData();
  formData.append("user_sending", "passenger");
  formData.append("receiver_company_id", receiverId);
  formData.append("flight_id", flightVal);       // <<--- CRUCIAL
  formData.append("message", content);

  try {
    let resp = await fetch("../php/send_message.php", {
      method: "POST",
      body: formData
    });
    let text = await resp.text();
    console.log("Send message response:", text);

    messageBox.value = "";
    loadConversation(); // optional
  } catch (err) {
    console.error("Error sending message:", err);
  }
}

</script>
</body>
</html>

<?php
$conn->close();
?>
