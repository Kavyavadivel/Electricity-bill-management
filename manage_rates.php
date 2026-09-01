<?php
session_start();
require_once '../config/database.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle add new rate slab
if (isset($_POST['add'])) {
    $min_units = $_POST['min_units'];
    $max_units = $_POST['max_units'];
    $rate_per_unit = $_POST['rate_per_unit'];
    $query = "INSERT INTO rate_slabs (min_units, max_units, rate_per_unit) VALUES (:min_units, :max_units, :rate_per_unit)";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":min_units", $min_units);
        $stmt->bindParam(":max_units", $max_units);
        $stmt->bindParam(":rate_per_unit", $rate_per_unit);
        $stmt->execute();
        $success = "Rate slab added successfully!";
    } catch(PDOException $e) {
        $error = "Failed to add rate slab: " . $e->getMessage();
    }
}

// Handle update rate slab
if (isset($_POST['update'])) {
    $slab_id = $_POST['slab_id'];
    $min_units = $_POST['min_units'];
    $max_units = $_POST['max_units'];
    $rate_per_unit = $_POST['rate_per_unit'];
    $query = "UPDATE rate_slabs SET min_units = :min_units, max_units = :max_units, rate_per_unit = :rate_per_unit WHERE slab_id = :slab_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":min_units", $min_units);
        $stmt->bindParam(":max_units", $max_units);
        $stmt->bindParam(":rate_per_unit", $rate_per_unit);
        $stmt->bindParam(":slab_id", $slab_id);
        $stmt->execute();
        $success = "Rate slab updated successfully!";
    } catch(PDOException $e) {
        $error = "Failed to update rate slab: " . $e->getMessage();
    }
}

// Handle delete rate slab
if (isset($_GET['delete'])) {
    $slab_id = $_GET['delete'];
    $query = "DELETE FROM rate_slabs WHERE slab_id = :slab_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":slab_id", $slab_id);
        $stmt->execute();
        $success = "Rate slab deleted successfully!";
    } catch(PDOException $e) {
        $error = "Failed to delete rate slab: " . $e->getMessage();
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
    $query = "SELECT * FROM rate_slabs WHERE slab_id = :slab_id";
    $stmt = $db->prepare($query);
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
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="add_user.php">Add User</a>
        <a href="bills.php">Manage Bills</a>
        <a href="manage_rates.php">Manage Rates</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="container">
        <h2>Manage Rate Slabs</h2>
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <h3>Current Rate Slabs</h3>
        <table>
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
                        <td><?php echo $slab['min_units']; ?></td>
                        <td><?php echo $slab['max_units']; ?></td>
                        <td><?php echo $slab['rate_per_unit']; ?></td>
                        <td>
                            <a href="manage_rates.php?edit=<?php echo $slab['slab_id']; ?>">Edit</a> |
                            <a href="manage_rates.php?delete=<?php echo $slab['slab_id']; ?>" onclick="return confirm('Are you sure you want to delete this slab?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <?php if ($edit_slab): ?>
            <h3>Edit Rate Slab</h3>
            <form method="POST" action="">
                <input type="hidden" name="slab_id" value="<?php echo $edit_slab['slab_id']; ?>">
                <div class="form-group">
                    <label>Min Units:</label>
                    <input type="number" name="min_units" value="<?php echo $edit_slab['min_units']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Max Units:</label>
                    <input type="number" name="max_units" value="<?php echo $edit_slab['max_units']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Rate per Unit (₹):</label>
                    <input type="number" step="0.01" name="rate_per_unit" value="<?php echo $edit_slab['rate_per_unit']; ?>" required>
                </div>
                <button type="submit" name="update">Update Slab</button>
                <a href="manage_rates.php" style="margin-left:10px;">Cancel</a>
            </form>
        <?php else: ?>
            <h3>Add New Rate Slab</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Min Units:</label>
                    <input type="number" name="min_units" required>
                </div>
                <div class="form-group">
                    <label>Max Units:</label>
                    <input type="number" name="max_units" required>
                </div>
                <div class="form-group">
                    <label>Rate per Unit (₹):</label>
                    <input type="number" step="0.01" name="rate_per_unit" required>
                </div>
                <button type="submit" name="add">Add Slab</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 