<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT c.complaint_id, u.username, c.complaint_text, c.status, c.created_at 
          FROM complaints c
          JOIN users u ON c.user_id = u.user_id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Complaints</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>User Complaints</h2>
        <table border="1" cellpadding="10" style="width:100%;">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Complaint</th>
                <th>Date</th>
            </tr>
            <?php foreach ($complaints as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['complaint_id']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['complaint_text'])) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html> 