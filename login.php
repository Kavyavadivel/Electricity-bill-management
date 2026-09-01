<?php
session_start();
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if ($user_type == 'admin') {
        $query = "SELECT * FROM admin WHERE username = :username";
    } else {
        $query = "SELECT * FROM users WHERE username = :username";
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row[$user_type == 'admin' ? 'admin_id' : 'user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_type'] = $user_type;
                
                header("Location: " . ($user_type == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
                exit();
            }
        }
        $error = "Invalid username or password";
    } catch(PDOException $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Electricity Billing System</title>
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
    .container h2 {
        font-size: 2.2em;
        margin-bottom: 28px;
    }
    .form-group label,
    .form-group input,
    .form-group textarea,
    .form-group select {
        font-size: 1.15em;
    }
    button {
        font-size: 1.15em;
        padding: 12px;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php 
        if(isset($error)) echo "<div class='error'>$error</div>";
        if(isset($_SESSION['message'])) {
            echo "<div class='success'>" . $_SESSION['message'] . "</div>";
            unset($_SESSION['message']);
        }
        ?>
        
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
                <label>Login as:</label>
                <select name="user_type" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html> 