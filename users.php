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

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['delete_user'];
    try {
        $db->beginTransaction();
        
        // Delete related records first
        $queries = [
            // "DELETE FROM comments WHERE user_id = :user_id", // Removed because comments table does not exist
            "DELETE FROM bills WHERE user_id = :user_id",
            "DELETE FROM meter_readings WHERE user_id = :user_id",
            "DELETE FROM users WHERE user_id = :user_id"
        ];
        
        foreach ($queries as $query) {
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
        }
        
        $db->commit();
        $_SESSION['message'] = "User deleted successfully!";
    } catch(PDOException $e) {
        $db->rollBack();
        $error = "Deletion failed: " . $e->getMessage();
    }
}

// Handle meter ID update
if (isset($_POST['update_meter_id'])) {
    $user_id = $_POST['user_id'];
    $meter_id = $_POST['meter_id'];
    error_log('Meter ID received: ' . $meter_id);
    
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
            $query = "UPDATE users SET meter_id = :meter_id WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":meter_id", $meter_id);
            $stmt->bindParam(":user_id", $user_id);
            
            if ($stmt->execute()) {
                error_log('Meter ID updated in DB for user ' . $user_id . ' to ' . $meter_id);
                $_SESSION['message'] = "Meter ID updated successfully!";
                header("Location: users.php");
                exit();
            }
        }
    } catch(PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Get all users with their details
$query = "SELECT u.*, 
          COUNT(DISTINCT b.bill_id) as total_bills,
          COUNT(DISTINCT mr.reading_id) as total_readings
          FROM users u 
          LEFT JOIN bills b ON u.user_id = b.user_id
          LEFT JOIN meter_readings mr ON u.user_id = mr.user_id
          GROUP BY u.user_id
          ORDER BY u.full_name";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background-color: #4CAF50;
            color: white;
        }
        .users-table tr:hover {
            background-color: #f5f5f5;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .edit-btn {
            background: #2196F3;
            color: white;
        }
        .delete-btn {
            background: #f44336;
            color: white;
        }
        .meter-id-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .meter-id-form input {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Manage Users</a>
        <a href="bills.php">Manage Bills</a>
        <a href="manage_rates.php">Manage Rates</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Manage Users</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if(isset($_SESSION['message'])) { echo "<div class='success'>".$_SESSION['message']."</div>"; unset($_SESSION['message']); } ?>
        
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Meter ID</th>
                    <th>Total Bills</th>
                    <th>Total Readings</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <?php echo ($user['meter_id'] !== null && $user['meter_id'] !== '' && strtolower($user['meter_id']) !== 'null') ? htmlspecialchars($user['meter_id']) : '-'; ?>
                        </td>
                        <td><?php echo $user['total_bills']; ?></td>
                        <td><?php echo $user['total_readings']; ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <button type="submit" name="delete_user" value="<?php echo $user['user_id']; ?>" 
                                        class="action-btn delete-btn" 
                                        onclick="return confirm('Are you sure you want to delete this user? This will also delete all their bills and readings.')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function filterUsers() {
        var input = document.getElementById('userSearch');
        var filter = input.value.toUpperCase();
        var table = document.querySelector('.users-table');
        var tr = table.getElementsByTagName('tr');

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName('td');
            var found = false;
            
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    var txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            tr[i].style.display = found ? "" : "none";
        }
    }
    </script>
</body>
</html> 