<?php
session_start();
require_once '../config/database.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $meter_id = $_POST['meter_id'];
    
    $query = "INSERT INTO users (username, password, full_name, email, address, phone, meter_id) 
              VALUES (:username, :password, :full_name, :email, :address, :phone, :meter_id)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":full_name", $full_name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":meter_id", $meter_id);
        
        if($stmt->execute()) {
            $success = "User added successfully!";
        }
    } catch(PDOException $e) {
        $error = "Failed to add user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="add_user.php">Add User</a>
        <a href="bills.php">Manage Bills</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="container">
        <h2>Add New User</h2>
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Meter ID:</label>
                <input type="text" name="meter_id" required pattern="[A-Za-z0-9-]+" title="Letters, numbers, and hyphens only">
            </div>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" required></textarea>
            </div>
            <div class="form-group">
                <label>Phone:</label>
                <input type="tel" name="phone" required>
            </div>
            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html> 