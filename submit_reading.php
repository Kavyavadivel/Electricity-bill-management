<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $current_reading = $_POST['current_reading'];
    $comment = $_POST['comment'];
    
    try {
        // Get previous reading
        $query = "SELECT current_reading FROM meter_readings WHERE user_id = :user_id ORDER BY reading_date DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $previous_reading = $stmt->fetch(PDO::FETCH_ASSOC)['current_reading'] ?? 0;
        
        // Calculate units consumed
        $units_consumed = $current_reading - $previous_reading;
        
        // Insert meter reading
        $query = "INSERT INTO meter_readings (user_id, reading_date, current_reading, previous_reading, units_consumed) 
                  VALUES (:user_id, CURDATE(), :current_reading, :previous_reading, :units_consumed)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":current_reading", $current_reading);
        $stmt->bindParam(":previous_reading", $previous_reading);
        $stmt->bindParam(":units_consumed", $units_consumed);
        $stmt->execute();
        
        // Insert comment if provided
        if (!empty($comment)) {
            $query = "INSERT INTO comments (user_id, comment_text) VALUES (:user_id, :comment_text)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":comment_text", $comment);
            $stmt->execute();
        }
        
        $_SESSION['message'] = "Meter reading submitted successfully!";
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Submission failed: " . $e->getMessage();
    }
}

// Get user's meter ID
$query = "SELECT meter_id FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$meter_id = $stmt->fetch(PDO::FETCH_ASSOC)['meter_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Meter Reading - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Submit Meter Reading</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if(isset($_SESSION['message'])) { echo "<div class='success'>".$_SESSION['message']."</div>"; unset($_SESSION['message']); } ?>
        
        <div class="info-box">
            <p>Your Meter ID: <strong><?php echo htmlspecialchars($meter_id); ?></strong></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Current Meter Reading:</label>
                <input type="number" name="current_reading" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Comments (Optional):</label>
                <textarea name="comment" rows="4" placeholder="Add any comments or concerns about your meter reading..."></textarea>
            </div>
            
            <button type="submit">Submit Reading</button>
        </form>
        
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html> 