<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch all comments with user details
$query = "SELECT c.*, u.username, u.full_name, u.meter_id 
          FROM comments c 
          JOIN users u ON c.user_id = u.user_id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Comments - Electricity Billing System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .comments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .comments-table th, .comments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .comments-table th {
            background-color: #4CAF50;
            color: white;
        }
        .comments-table tr:hover {
            background-color: #f5f5f5;
        }
        .comment-text {
            max-width: 400px;
            word-wrap: break-word;
        }
        .timestamp {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Comments</h2>
        
        <?php if(empty($comments)): ?>
            <p>No comments found.</p>
        <?php else: ?>
            <table class="comments-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Meter ID</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($comments as $comment): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($comment['username']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($comment['meter_id']); ?></td>
                            <td class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></td>
                            <td class="timestamp"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html> 