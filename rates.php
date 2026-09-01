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

// Handle add new rate slab
if (isset($_POST['add_slab'])) {
    $min_units = $_POST['min_units'];
    $max_units = $_POST['max_units'];
    $rate_per_unit = $_POST['rate_per_unit'];
    try {
        $stmt = $db->prepare("INSERT INTO rate_slabs (min_units, max_units, rate_per_unit) VALUES (:min_units, :max_units, :rate_per_unit)");
        $stmt->bindParam(":min_units", $min_units);
        $stmt->bindParam(":max_units", $max_units);
        $stmt->bindParam(":rate_per_unit", $rate_per_unit);
        $stmt->execute();
        $_SESSION['message'] = "Rate slab added successfully!";
        header("Location: rates.php");
        exit();
    } catch(PDOException $e) {
        $error = "Add failed: " . $e->getMessage();
    }
}

// Handle delete slab
if (isset($_POST['delete_slab'])) {
    $slab_id = $_POST['delete_slab'];
    try {
        $stmt = $db->prepare("DELETE FROM rate_slabs WHERE slab_id = :slab_id");
        $stmt->bindParam(":slab_id", $slab_id);
        $stmt->execute();
        $_SESSION['message'] = "Rate slab deleted successfully!";
        header("Location: rates.php");
        exit();
    } catch(PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

// Handle edit slab
if (isset($_POST['edit_slab'])) {
    $slab_id = $_POST['slab_id'];
    $min_units = $_POST['min_units'];
    $max_units = $_POST['max_units'];
    $rate_per_unit = $_POST['rate_per_unit'];
    try {
        $stmt = $db->prepare("UPDATE rate_slabs SET min_units = :min_units, max_units = :max_units, rate_per_unit = :rate_per_unit WHERE slab_id = :slab_id");
        $stmt->bindParam(":min_units", $min_units);
        $stmt->bindParam(":max_units", $max_units);
        $stmt->bindParam(":rate_per_unit", $rate_per_unit);
        $stmt->bindParam(":slab_id", $slab_id);
        $stmt->execute();
        $_SESSION['message'] = "Rate slab updated successfully!";
        header("Location: rates.php");
        exit();
    } catch(PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Fetch all rate slabs
$query = "SELECT * FROM rate_slabs ORDER BY min_units ASC";
$stmt = $db->query($query);
$slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If editing, fetch the slab
$edit_slab = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM rate_slabs WHERE slab_id = :slab_id");
    $stmt->bindParam(":slab_id", $edit_id);
    $stmt->execute();
    $edit_slab = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rate Slabs - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .rates-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .rates-table th, .rates-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .rates-table th {
            background-color: #4CAF50;
            color: white;
        }
        .rates-table tr:hover {
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
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .form-inline input {
            width: 90px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Manage Users</a>
        <a href="bills.php">Manage Bills</a>
        <a href="rates.php">Manage Rates</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="container">
        <h2>Manage Rate Slabs</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if(isset($_SESSION['message'])) { echo "<div class='success'>".$_SESSION['message']."</div>"; unset($_SESSION['message']); } ?>
        <table class="rates-table">
            <thead>
                <tr>
                    <th>Min Units</th>
                    <th>Max Units</th>
                    <th>Rate per Unit (₹)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slabs as $slab): ?>
                    <tr>
                        <?php if ($edit_slab && $edit_slab['slab_id'] == $slab['slab_id']): ?>
                        <form method="POST" action="" class="form-inline">
                            <input type="hidden" name="slab_id" value="<?php echo $slab['slab_id']; ?>">
                            <td><input type="number" name="min_units" value="<?php echo $slab['min_units']; ?>" required></td>
                            <td><input type="number" name="max_units" value="<?php echo $slab['max_units']; ?>" required></td>
                            <td><input type="number" step="0.01" name="rate_per_unit" value="<?php echo $slab['rate_per_unit']; ?>" required></td>
                            <td>
                                <button type="submit" name="edit_slab" class="action-btn edit-btn">Save</button>
                                <a href="rates.php" class="action-btn delete-btn">Cancel</a>
                            </td>
                        </form>
                        <?php else: ?>
                        <td><?php echo $slab['min_units']; ?></td>
                        <td><?php echo $slab['max_units']; ?></td>
                        <td>₹<?php echo number_format($slab['rate_per_unit'], 2); ?></td>
                        <td>
                            <a href="rates.php?edit=<?php echo $slab['slab_id']; ?>" class="action-btn edit-btn">Edit</a>
                            <form method="POST" action="" style="display:inline;">
                                <button type="submit" name="delete_slab" value="<?php echo $slab['slab_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this slab?');">Delete</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3 style="margin-top:30px;">Add New Rate Slab</h3>
        <form method="POST" action="" class="form-inline">
            <input type="number" name="min_units" placeholder="Min Units" required>
            <input type="number" name="max_units" placeholder="Max Units" required>
            <input type="number" step="0.01" name="rate_per_unit" placeholder="Rate per Unit (₹)" required>
            <button type="submit" name="add_slab" class="action-btn edit-btn">Add</button>
        </form>
    </div>
</body>
</html> 