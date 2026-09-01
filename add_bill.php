<?php
session_start();
require_once '../config/database.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

function calculateBillAmount($units, $db) {
    $query = "SELECT * FROM rate_slabs ORDER BY min_units ASC";
    $stmt = $db->query($query);
    $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_amount = 0;
    $remaining_units = $units;
    foreach ($slabs as $slab) {
        if ($remaining_units <= 0) break;
        $slab_range = $slab['max_units'] - $slab['min_units'] + 1;
        $slab_units = min($remaining_units, $slab_range);
        $total_amount += $slab_units * $slab['rate_per_unit'];
        $remaining_units -= $slab_units;
    }
    return $total_amount;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $current_reading = $_POST['current_reading'];
    $reading_date = $_POST['reading_date'];
    try {
        // Get previous reading
        $query = "SELECT current_reading FROM meter_readings WHERE user_id = :user_id ORDER BY reading_date DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $previous_reading = $stmt->fetch(PDO::FETCH_ASSOC);
        $previous_reading = $previous_reading ? $previous_reading['current_reading'] : 0;
        // Calculate units consumed
        $units_consumed = $current_reading - $previous_reading;
        if ($units_consumed < 0) {
            throw new Exception("Current reading cannot be less than previous reading");
        }
        // Calculate bill amount
        $amount = calculateBillAmount($units_consumed, $db);
        // Set due date (30 days from reading date)
        $due_date = date('Y-m-d', strtotime($reading_date . ' +30 days'));
        // Begin transaction
        $db->beginTransaction();
        // Insert meter reading
        $query = "INSERT INTO meter_readings (user_id, reading_date, current_reading, previous_reading, units_consumed) VALUES (:user_id, :reading_date, :current_reading, :previous_reading, :units_consumed)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":reading_date", $reading_date);
        $stmt->bindParam(":current_reading", $current_reading);
        $stmt->bindParam(":previous_reading", $previous_reading);
        $stmt->bindParam(":units_consumed", $units_consumed);
        $stmt->execute();
        $reading_id = $db->lastInsertId();
        // Fetch meter_id for the user
        $query_meter = "SELECT meter_id FROM users WHERE user_id = :user_id";
        $stmt_meter = $db->prepare($query_meter);
        $stmt_meter->bindParam(":user_id", $user_id);
        $stmt_meter->execute();
        $row_meter = $stmt_meter->fetch(PDO::FETCH_ASSOC);
        $meter_id = $row_meter ? $row_meter['meter_id'] : null;
        // Insert bill
        $query = "INSERT INTO bills (user_id, meter_id, reading_id, bill_date, amount, due_date) VALUES (:user_id, :meter_id, :reading_id, :bill_date, :amount, :due_date)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":meter_id", $meter_id);
        $stmt->bindParam(":reading_id", $reading_id);
        $stmt->bindParam(":bill_date", $reading_date);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":due_date", $due_date);
        $stmt->execute();
        $db->commit();
        $success = "Bill generated successfully!";
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
// Get all users for dropdown
$query = "SELECT user_id, full_name, meter_id FROM users ORDER BY full_name";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .center-card {
            max-width: 450px;
            margin: 60px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            padding: 35px 30px 30px 30px;
        }
        .center-card h2 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 25px;
        }
        .center-card form {
            margin-top: 10px;
        }
        .center-card .form-group {
            margin-bottom: 18px;
        }
        .center-card label {
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
            display: block;
        }
        .center-card select, .center-card input[type="number"], .center-card input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .center-card button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 10px;
        }
        .center-card button:hover {
            background: #388e3c;
        }
        .center-card .success, .center-card .error {
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="add_user.php">Add User</a>
        <a href="bills.php">Manage Bills</a>
        <a href="manage_rates.php">Manage Rates</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="center-card">
        <h2>Generate New Bill</h2>
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <div class="form-group">
            <label>Search by Meter ID:</label>
            <input type="text" id="meterSearch" placeholder="Enter meter ID to search..." onkeyup="filterUsers()">
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Select User:</label>
                <select name="user_id" id="userSelect" required>
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>" data-meter="<?php echo htmlspecialchars($user['meter_id']); ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?> (Meter ID: <?php echo htmlspecialchars($user['meter_id']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Current Reading:</label>
                <input type="number" name="current_reading" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Reading Date:</label>
                <input type="date" name="reading_date" required>
            </div>
            <button type="submit">Generate Bill</button>
        </form>
    </div>
    <script>
    function filterUsers() {
        var input = document.getElementById('meterSearch');
        var filter = input.value.toUpperCase();
        var select = document.getElementById('userSelect');
        var options = select.getElementsByTagName('option');

        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            var meterId = option.getAttribute('data-meter');
            if (meterId) {
                if (meterId.toUpperCase().indexOf(filter) > -1) {
                    option.style.display = "";
                } else {
                    option.style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html> 