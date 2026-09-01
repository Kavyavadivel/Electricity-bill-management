<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meter_id = $_POST['meter_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if meter ID is already taken
        $query = "SELECT COUNT(*) as count FROM users WHERE meter_id = :meter_id AND user_id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":meter_id", $meter_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = "This meter ID is already assigned to another user.";
        } else {
            // Update meter ID
            $query = "UPDATE users SET meter_id = :meter_id WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":meter_id", $meter_id);
            $stmt->bindParam(":user_id", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Meter ID updated successfully!";
                header("Location: dashboard.php");
                exit();
            }
        }
    } catch(PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Get current user data
$query = "SELECT meter_id FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Meter ID - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Update Meter ID</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Meter ID:</label>
                <input type="text" name="meter_id" value="<?php echo isset($user['meter_id']) ? htmlspecialchars($user['meter_id']) : ''; ?>" 
                       required pattern="[A-Za-z0-9-]+" 
                       title="Please enter a valid meter ID (letters, numbers, and hyphens only)">
            </div>
            
            <button type="submit">Update Meter ID</button>
        </form>
        
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html> 