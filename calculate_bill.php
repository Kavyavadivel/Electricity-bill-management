<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

function calculateBillAmount($units) {
    global $db;
    
    // Get all rate slabs
    $query = "SELECT * FROM rate_slabs ORDER BY min_units ASC";
    $stmt = $db->query($query);
    $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_amount = 0;
    $remaining_units = $units;
    
    foreach ($slabs as $slab) {
        if ($remaining_units <= 0) break;
        
        $slab_units = min(
            $remaining_units,
            $slab['max_units'] - $slab['min_units']
        );
        
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
        $query = "SELECT current_reading 
                 FROM meter_readings 
                 WHERE user_id = :user_id 
                 ORDER BY reading_date DESC 
                 LIMIT 1";
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
        $amount = calculateBillAmount($units_consumed);
        
        // Set due date (30 days from reading date)
        $due_date = date('Y-m-d', strtotime($reading_date . ' +30 days'));
        
        // Begin transaction
        $db->beginTransaction();
        
        // Insert meter reading
        $query = "INSERT INTO meter_readings (user_id, reading_date, current_reading, previous_reading, units_consumed) 
                 VALUES (:user_id, :reading_date, :current_reading, :previous_reading, :units_consumed)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":reading_date", $reading_date);
        $stmt->bindParam(":current_reading", $current_reading);
        $stmt->bindParam(":previous_reading", $previous_reading);
        $stmt->bindParam(":units_consumed", $units_consumed);
        $stmt->execute();
        
        $reading_id = $db->lastInsertId();
        
        // Insert bill
        $query = "INSERT INTO bills (user_id, reading_id, bill_date, amount, due_date) 
                 VALUES (:user_id, :reading_id, :bill_date, :amount, :due_date)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":reading_id", $reading_id);
        $stmt->bindParam(":bill_date", $reading_date);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":due_date", $due_date);
        $stmt->execute();
        
        $db->commit();
        $_SESSION['message'] = "Bill generated successfully!";
        header("Location: bills.php");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get all users for dropdown
$query = "SELECT user_id, full_name FROM users ORDER BY full_name";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="bills.php">Manage Bills</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Generate New Bill</h2>
        
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Select User:</label>
                <select name="user_id" required>
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
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
</body>
</html> 