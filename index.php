<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Billing System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .home-container {
            max-width: 500px;
            margin: 80px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            padding: 40px 30px 30px 30px;
            text-align: center;
        }
        .home-container h1 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .home-container p {
            color: #555;
            margin-bottom: 30px;
        }
        .home-btns {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .home-btns a {
            display: block;
            padding: 12px;
            background: #4CAF50;
            color: #fff;
            border-radius: 4px;
            font-size: 18px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .home-btns a:hover {
            background: #388e3c;
        }
        body {
            background: url('https://cdn.britannica.com/12/156712-131-8E29225D/transmission-lines-electricity-countryside-power-plants-homes.jpg') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
</head>
<body>
    <div class="home-container">
        <h1>Electricity Billing System</h1>
        <p>Welcome to the Electricity Billing System. Easily manage your electricity usage, view bills, and make payments. Admins can manage users, generate bills, and update rates.</p>
        <div class="home-btns">
            <a href="login.php">User Login</a>
            <a href="login.php" onclick="document.getElementsByName('user_type')[0].value='admin'">Admin Login</a>
            <a href="register.php">User Registration</a>
            <a href="admin/admin_register.php">Admin Registration</a>
        </div>
    </div>
</body>
</html>
