<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user information
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's bills
$query = "SELECT b.*, mr.units_consumed 
          FROM bills b 
          JOIN meter_readings mr ON b.reading_id = mr.reading_id 
          WHERE b.user_id = :user_id 
          ORDER BY b.bill_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background: url('https://cdn.britannica.com/12/156712-131-8E29225D/transmission-lines-electricity-countryside-power-plants-homes.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.45);
            z-index: 0;
        }
        .dashboard-center, .container {
            position: relative;
            z-index: 1;
        }
        .dashboard-center {
            max-width: 800px;
            margin: 60px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            padding: 35px 30px 30px 30px;
        }
        .dashboard-center h2 {
            color: #388e3c;
            text-align: center;
            margin-bottom: 30px;
        }
        .dashboard-flex {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .dashboard-card {
            flex: 1 1 300px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.07);
            padding: 25px 20px;
            min-width: 270px;
        }
        .dashboard-card h3 {
            color: #222;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        /* Modern bills list style */
        .bills-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }
        .bill-item {
            background: #f9f9f9;
            border-left: 5px solid #4CAF50;
            border-radius: 6px;
            padding: 15px 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex;
            flex-wrap: wrap;
            gap: 18px 30px;
            align-items: center;
            justify-content: space-between;
        }
        .bill-item .status {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.95em;
            margin-left: 10px;
            color: #fff;
        }
        .bill-item.paid .status {
            background: #388e3c;
        }
        .bill-item.pending .status {
            background: #e67e22;
        }
        @media (max-width: 900px) {
            .dashboard-flex {
                flex-direction: column;
                gap: 20px;
            }
        }
        .pay-btn {
            display: inline-block;
            background: #388e3c;
            color: #fff;
            padding: 7px 18px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 6px;
            transition: background 0.2s;
        }
        .pay-btn:hover {
            background: #2e7031;
        }
        .nav {
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="dashboard-center">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h2>
        <div class="dashboard-flex">
            <div class="dashboard-card">
                <h3>Account Summary</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                <p><strong>Meter ID:</strong> <?php echo isset($user['meter_id']) ? htmlspecialchars($user['meter_id']) : 'Not assigned'; ?></p>
            </div>
            <div class="dashboard-card">
                <h3>Recent Bills</h3>
                <?php if (count($bills) > 0): ?>
                    <div class="bills-list">
                        <?php foreach ($bills as $bill): ?>
                            <div class="bill-item <?php echo $bill['status'] === 'paid' ? 'paid' : 'pending'; ?>">
                                <div>
                                    <strong>Date:</strong> <?php echo date('Y-m-d', strtotime($bill['bill_date'])); ?>
                                    <span class="status <?php echo $bill['status']; ?>">
                                        <?php echo ucfirst($bill['status']); ?>
                                    </span>
                                </div>
                                <div><strong>Units:</strong> <?php echo $bill['units_consumed']; ?></div>
                                <div><strong>Amount:</strong> ₹<?php echo number_format($bill['amount'], 2); ?></div>
                                <div><strong>Due:</strong> <?php echo date('Y-m-d', strtotime($bill['due_date'])); ?></div>
                                <?php if ($bill['status'] === 'pending'): ?>
                                    <div>
                                        <a href="pay_bill.php?bill_id=<?php echo $bill['bill_id']; ?>" class="pay-btn">Pay</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No bills found.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Add Complaint Button -->
        <a href="../add_complaint.php" class="btn" style="margin-bottom: 20px; display: inline-block;">Add Complaint</a>
    </div>
</body>
</html> 