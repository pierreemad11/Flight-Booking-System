<?php
include("db.php");
session_start();

// Must be company
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "company") {
    header("Location: ../pages/login_page.html");
    exit();
}

$company_id = $_SESSION["company_id"];

if (!isset($_GET["flight_id"])) {
    echo "No flight ID provided.";
    exit();
}

$flight_id = intval($_GET["flight_id"]);

// Handle flight cancellation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_flight"])) {
    // 1) Mark flight as completed/canceled
    $updateFlight = "
        UPDATE Flight
        SET is_completed = 1
        WHERE flight_id = $flight_id
          AND company_id = $company_id
    ";
    $conn->query($updateFlight);

    // 2) Retrieve flight fees & flight's company_id
    $feesQuery = "
        SELECT fees, company_id
        FROM Flight
        WHERE flight_id = $flight_id
    ";
    $feesResult = $conn->query($feesQuery);
    $feesRow    = $feesResult->fetch_assoc();
    $flightFees      = floatval($feesRow["fees"]);
    $flightCompanyId = (int)$feesRow["company_id"];

    // Refund any passenger_flight row with payment_status='completed'
    $refundQuery = "
        SELECT passenger_id
        FROM Passenger_Flight
        WHERE flight_id = $flight_id
          AND payment_status = 'completed'
    ";
    $refundResult = $conn->query($refundQuery);

    $refundCount = 0;
    while ($rfRow = $refundResult->fetch_assoc()) {
        $p_id = (int)$rfRow['passenger_id'];

        // Increase passenger's account_balance
        $refundPassenger = "
            UPDATE Passenger
            SET account_balance = account_balance + $flightFees
            WHERE passenger_id = $p_id
        ";
        $conn->query($refundPassenger);

        $refundCount++;

        // Mark passenger_flight as canceled
        $cancelPF = "
            UPDATE Passenger_Flight
            SET status = 'canceled'
            WHERE passenger_id = $p_id
              AND flight_id = $flight_id
        ";
        $conn->query($cancelPF);
    }

    // Deduct total refund from the company's balance
    $totalRefund = $flightFees * $refundCount;
    $updateCompany = "
        UPDATE Company
        SET account_balance = account_balance - $totalRefund
        WHERE company_id = $flightCompanyId
    ";
    $conn->query($updateCompany);

    // Mark all other passenger_flight rows as canceled => no refund if they didn't pay
    $cancelPendingQuery = "
        UPDATE Passenger_Flight
        SET status = 'canceled'
        WHERE flight_id = $flight_id
          AND status <> 'canceled'
    ";
    $conn->query($cancelPendingQuery);

    echo "<script>
            alert('Flight canceled. Refunds processed for passengers who paid only.');
            window.location.href='company_home.php';
          </script>";
    exit();
}

// Fetch flight details
$flightQuery = "
    SELECT *
    FROM Flight
    WHERE flight_id = $flight_id
      AND company_id = $company_id
";
$flightResult = $conn->query($flightQuery);

if ($flightResult->num_rows === 0) {
    echo "Flight not found or you do not have permission to view it.";
    exit();
}

$flightData = $flightResult->fetch_assoc();

// Fetch passengers
$passengerListQuery = "
    SELECT p.passenger_id, p.name, p.email, pf.status, pf.payment_status
    FROM Passenger p
    JOIN Passenger_Flight pf ON p.passenger_id = pf.passenger_id
    WHERE pf.flight_id = $flight_id
";
$passengerListResult = $conn->query($passengerListQuery);

$pendingPassengers   = [];
$completedPassengers = [];

// Classify by payment_status
while ($row = $passengerListResult->fetch_assoc()) {
    switch ($row["payment_status"]) {
        case "pending":
            $pendingPassengers[] = $row;
            break;
        case "completed":
            $completedPassengers[] = $row;
            break;
        default:
            // can ignore
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Flight Details</title>
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .details-container {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            box-sizing: border-box;
        }
        h1 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        p {
            color: #333;
            margin: 6px 0;
        }
        .btn-cancel {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .btn-cancel:hover {
            background: #c0392b;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 100%;
        }
        table, th, td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 10px;
        }
        th {
            background-color: #2f86c5;
            color: #fff;
            font-weight: bold;
        }
        tr:hover {
            background-color: #fafafa;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .details-container {
                margin: 15px auto;
                padding: 15px;
            }
            table, th, td {
                font-size: 14px;
                padding: 8px;
            }
        }

        /* ====== Chat Widget (WhatsApp-like) ====== */
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
          cursor: pointer;
          border-radius: 6px 6px 0 0;
          font-weight: bold;
          display: flex;
          justify-content: space-between;
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
        /* Chat bubble style */
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

<div class="details-container">
    <h1>Flight Details (ID: <?php echo htmlspecialchars($flightData["flight_id"]); ?>)</h1>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($flightData["name"]); ?></p>
    <p><strong>Departure:</strong> <?php echo htmlspecialchars($flightData["departure"]); ?></p>
    <p><strong>Destination:</strong> <?php echo htmlspecialchars($flightData["destination"]); ?></p>
    <p><strong>Stops:</strong> <?php echo htmlspecialchars($flightData["stops"]); ?></p>
    <p><strong>Fees:</strong> <?php echo htmlspecialchars($flightData["fees"]); ?></p>
    <p><strong>Seats left:</strong> <?php echo htmlspecialchars($flightData["passengers_number"]); ?></p>
    <p><strong>Start Time:</strong> <?php echo htmlspecialchars($flightData["start_time"]); ?></p>
    <p><strong>End Time:</strong> <?php echo htmlspecialchars($flightData["end_time"]); ?></p>
    <p><strong>Is Completed?</strong> <?php echo $flightData["is_completed"] ? "Yes" : "No"; ?></p>

    <!-- PENDING -->
    <h2>Pending Payment Passengers</h2>
    <?php if (!empty($pendingPassengers)): ?>
        <table>
            <tr>
                <th>Passenger ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
            <?php foreach ($pendingPassengers as $pp): ?>
            <tr>
                <td><?php echo htmlspecialchars($pp["passenger_id"]); ?></td>
                <td><?php echo htmlspecialchars($pp["name"]); ?></td>
                <td><?php echo htmlspecialchars($pp["email"]); ?></td>
                <td>
                  <!-- Chat button => openChatWidget(passengerId, flightId) -->
                  <button 
                    style="background: #3498db; color: #fff; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer;"
                    onclick="openChatWidget(<?php echo (int)$pp['passenger_id']; ?>, <?php echo (int)$flight_id; ?>)"
                  >
                    Chat
                  </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No pending passengers.</p>
    <?php endif; ?>

    <!-- COMPLETED -->
    <h2>Completed Payment Passengers</h2>
    <?php if (!empty($completedPassengers)): ?>
        <table>
            <tr>
                <th>Passenger ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
            <?php foreach ($completedPassengers as $cp): ?>
            <tr>
                <td><?php echo htmlspecialchars($cp["passenger_id"]); ?></td>
                <td><?php echo htmlspecialchars($cp["name"]); ?></td>
                <td><?php echo htmlspecialchars($cp["email"]); ?></td>
                <td>
                  <button 
                    style="background: #3498db; color: #fff; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer;"
                    onclick="openChatWidget(<?php echo (int)$cp['passenger_id']; ?>, <?php echo (int)$flight_id; ?>)"
                  >
                    Chat
                  </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No completed passengers.</p>
    <?php endif; ?>

    <!-- Cancel flight if not completed -->
    <?php if (!$flightData["is_completed"]): ?>
        <form method="post">
            <button type="submit" name="cancel_flight" class="btn-cancel">
                Cancel Flight (Refund Paid Only)
            </button>
        </form>
    <?php else: ?>
        <p>This flight is marked as completed or canceled.</p>
    <?php endif; ?>

    <a href="company_home.php" class="back-link">Back to Company Home</a>
</div>

<!-- ====== The Floating Chat Widget ====== -->
<div id="miniChatContainer">
  <div id="miniChatHeader" onclick="toggleMiniChat(event)">
    <span>Chat with Passenger</span>
    <span class="miniChat-close-btn" onclick="toggleMiniChat(event)">✕</span>
  </div>
  <div id="miniChatBody">
    <!-- We'll load messages here with loadConversation() -->
  </div>
  <div id="miniChatFooter">
    <!-- We store user_sending=company -->
    <input type="hidden" id="hiddenCompanyId" value="<?php echo (int)$company_id; ?>">
    <input type="hidden" id="receiverPassengerId" value="">
    <input type="hidden" id="flightIdHidden" value="">

    <textarea id="miniChatMessage" rows="2" placeholder="Type your message..."></textarea>
    <button id="miniChatSendBtn" onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
// We'll store the company ID in a variable
window.currentCompanyId = <?php echo (int)$company_id; ?>;

// Toggle chat
function toggleMiniChat(evt) {
  if (evt && evt.stopPropagation) evt.stopPropagation();
  const c = document.getElementById("miniChatContainer");
  if (c.style.display === "none" || c.style.display === "") {
    c.style.display = "flex";
  } else {
    c.style.display = "none";
  }
}

// Called when company clicks "Chat" button in the table
function openChatWidget(passengerId, flightId) {
  document.getElementById("receiverPassengerId").value = passengerId;
  document.getElementById("flightIdHidden").value      = flightId;
  const container = document.getElementById("miniChatContainer");
  container.style.display = "flex";
  // Load the conversation so we see old messages
  loadConversation();
}

// 1) loadConversation => AJAX GET to e.g. ajax_load_chat_company.php
async function loadConversation() {
  const passengerId = document.getElementById("receiverPassengerId").value;
  const flightVal   = document.getElementById("flightIdHidden").value;
  if (!passengerId || !flightVal) return;

  try {
    let resp = await fetch(`../php/ajax_load_chat.php?receiver_id=${passengerId}&flight_id=${flightVal}`);
    let data = await resp.json(); // array of messages
    renderMessages(data);
  } catch (err) {
    console.error("Error loading chat:", err);
  }
}

// 2) renderMessages => show them in #miniChatBody with left/right bubbles
function renderMessages(messages) {
  const chatBody = document.getElementById("miniChatBody");
  chatBody.innerHTML = ""; // clear old

  messages.forEach(msg => {
    // determine if me or them
    // if msg.sender_id == currentCompanyId => bubble-me, else bubble-other
    const isMine = (msg.sender_id == window.currentCompanyId);
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

// 3) sendMessage => AJAX POST to send_message.php with user_sending=company
async function sendMessage() {
  const messageBox  = document.getElementById("miniChatMessage");
  const passengerId = document.getElementById("receiverPassengerId").value;
  const flightVal   = document.getElementById("flightIdHidden").value;
  const content     = messageBox.value.trim();
  if (!content) return;

  let formData = new FormData();
  formData.append("user_sending", "company");
  formData.append("receiver_passenger_id", passengerId);
  formData.append("flight_id", flightVal); // we store flight_id
  formData.append("message", content);

  try {
    let resp = await fetch("../php/send_message.php", {
      method: "POST",
      body: formData
    });
    let text = await resp.text();
    console.log("Send message response:", text);

    // Clear the input
    messageBox.value = "";

    // Reload conversation to see the newly inserted message
    loadConversation();
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
