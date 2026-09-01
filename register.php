<?php
session_start();
require_once 'config/database.php';

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
            $_SESSION['message'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Electricity Billing System</title>
    <link rel="stylesheet" href="css/style.css">
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
    .container {
        width: 100%;
        max-width: 600px;
        margin: 60px auto;
        padding: 40px 40px 30px 40px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.12);
        position: relative;
        z-index: 1;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" action="">
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
            
            <div class="form-group">
                <label>Meter ID:</label>
                <input type="text" name="meter_id" required pattern="[A-Za-z0-9-]+" title="Please enter a valid meter ID (letters, numbers, and hyphens only)">
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html> 