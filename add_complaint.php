<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();

    $user_id = $_SESSION['user_id'];
    $complaint_text = $_POST['complaint_text'];

    $query = "INSERT INTO complaints (user_id, complaint_text) VALUES (:user_id, :complaint_text)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":complaint_text", $complaint_text);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Complaint submitted successfully!";
        header("Location: user/dashboard.php");
        exit();
    } else {
        $error = "Failed to submit complaint.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Complaint</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Submit a Complaint</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST" action="">
            <textarea name="complaint_text" required placeholder="Describe your complaint here..." rows="5" style="width:100%;"></textarea>
            <br>
            <button type="submit">Submit Complaint</button>
        </form>
    </div>
</body>
</html> 