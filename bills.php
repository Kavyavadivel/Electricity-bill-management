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

// Handle bill deletion
if (isset($_POST['delete_bill'])) {
    $bill_id = $_POST['delete_bill'];
    try {
        $stmt = $db->prepare("DELETE FROM bills WHERE bill_id = :bill_id");
        $stmt->bindParam(":bill_id", $bill_id);
        $stmt->execute();
        $_SESSION['message'] = "Bill deleted successfully!";
        header("Location: bills.php");
        exit();
    } catch(PDOException $e) {
        $error = "Deletion failed: " . $e->getMessage();
    }
}

// Fetch all bills with user info
$query = "SELECT b.*, u.full_name, u.meter_id, mr.units_consumed
          FROM bills b
          JOIN users u ON b.user_id = u.user_id
          JOIN meter_readings mr ON b.reading_id = mr.reading_id
          ORDER BY b.bill_date DESC";
$stmt = $db->query($query);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bills - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .bills-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .bills-table th, .bills-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .bills-table th {
            background-color: #4CAF50;
            color: white;
        }
        .bills-table tr:hover {
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
        .delete-btn {
            background: #f44336;
            color: white;
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
        <h2>Manage Bills</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if(isset($_SESSION['message'])) { echo "<div class='success'>".$_SESSION['message']."</div>"; unset($_SESSION['message']); } ?>

        <div class="search-box">
            <input type="text" id="billSearch" placeholder="Search bills..." onkeyup="filterBills()">
        </div>

        <table class="bills-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Meter ID</th>
                    <th>Bill Date</th>
                    <th>Units</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bill['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($bill['meter_id']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($bill['bill_date'])); ?></td>
                        <td><?php echo $bill['units_consumed']; ?></td>
                        <td>₹<?php echo number_format($bill['amount'], 2); ?></td>
                        <td><?php echo ucfirst($bill['status']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($bill['due_date'])); ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <button type="submit" name="delete_bill" value="<?php echo $bill['bill_id']; ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this bill?');">
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
    function filterBills() {
        var input = document.getElementById('billSearch');
        var filter = input.value.toUpperCase();
        var table = document.querySelector('.bills-table');
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