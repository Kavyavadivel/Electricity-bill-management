<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['bill_id'])) {
    header("Location: dashboard.php");
    exit();
}

$bill_id = $_GET['bill_id'];

// Fetch bill details
$query = "SELECT b.*, mr.units_consumed FROM bills b JOIN meter_readings mr ON b.reading_id = mr.reading_id WHERE b.bill_id = :bill_id AND b.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":bill_id", $bill_id);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    header("Location: dashboard.php");
    exit();
}

if ($bill['status'] === 'paid') {
    $already_paid = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_pay'])) {
    // AJAX request to mark as paid
    $query = "UPDATE bills SET status = 'paid' WHERE bill_id = :bill_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":bill_id", $bill_id);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Bill - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .pay-card {
            max-width: 420px;
            margin: 60px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            padding: 35px 30px 30px 30px;
            text-align: center;
        }
        .pay-card h2 {
            color: #388e3c;
            margin-bottom: 18px;
        }
        .pay-details {
            text-align: left;
            margin-bottom: 18px;
        }
        .pay-details p {
            margin: 7px 0;
        }
        .qr-img {
            margin: 18px auto 10px auto;
            display: block;
            width: 180px;
            height: 180px;
            background: #eee;
            border-radius: 8px;
            object-fit: contain;
            cursor: pointer;
            transition: box-shadow 0.2s;
        }
        .qr-img:hover {
            box-shadow: 0 0 0 4px #4CAF50;
        }
        .paid-msg {
            color: #388e3c;
            font-weight: bold;
            margin-top: 18px;
        }
        .popup {
            display: none;
            position: fixed;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .popup-content {
            background: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            text-align: center;
        }
        .popup-content button {
            margin-top: 18px;
            background: #388e3c;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 24px;
            font-size: 1em;
            cursor: pointer;
        }
        .popup-content button:hover {
            background: #2e7031;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="pay-card">
        <h2>Pay Your Bill</h2>
        <div class="pay-details">
            <p><strong>Bill Date:</strong> <?php echo date('Y-m-d', strtotime($bill['bill_date'])); ?></p>
            <p><strong>Units:</strong> <?php echo $bill['units_consumed']; ?></p>
            <p><strong>Amount:</strong> ₹<?php echo number_format($bill['amount'], 2); ?></p>
            <p><strong>Status:</strong> <span id="status-text"><?php echo ucfirst($bill['status']); ?></span></p>
            <p><strong>Due Date:</strong> <?php echo date('Y-m-d', strtotime($bill['due_date'])); ?></p>
        </div>
        <div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=razorpay@upi&pn=ElectricityBill&am=<?php echo $bill['amount']; ?>&cu=INR" alt="Scan to Pay" class="qr-img" id="qr-img" />
            <div style="margin-top: 8px; color: #888; font-size: 0.98em;">Click the QR code after scanning to mark as paid.</div>
        </div>
        <div id="paid-msg" class="paid-msg" style="display:none;">Payment received. Thank you!</div>
    </div>
    <div class="popup" id="popup">
        <div class="popup-content">
            <div style="font-size:1.2em; color:#388e3c; font-weight:bold;">Payment received. Thank you!</div>
            <button onclick="closePopup()">OK</button>
        </div>
    </div>
    <script>
        const qrImg = document.getElementById('qr-img');
        const paidMsg = document.getElementById('paid-msg');
        const statusText = document.getElementById('status-text');
        const popup = document.getElementById('popup');
        <?php if ($bill['status'] === 'pending'): ?>
        qrImg.addEventListener('click', function() {
            // AJAX call to mark as paid
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Show popup and update UI
                    popup.style.display = 'flex';
                    paidMsg.style.display = 'block';
                    statusText.textContent = 'Paid';
                }
            };
            xhr.send('ajax_pay=1');
        });
        <?php else: ?>
        paidMsg.style.display = 'block';
        <?php endif; ?>
        function closePopup() {
            popup.style.display = 'none';
        }
    </script>
</body>
</html> 