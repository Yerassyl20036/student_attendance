<?php
// session_start();

require_once "../../config/db.php"; // Load the database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Connection error: Could not connect to the database.");
}

function isAdmin($conn, $user_id) {
    // Query the database to check if this user is an admin
    $query = "SELECT additional_info FROM teachers WHERE teacher_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if 'admin' is in the 'additional_info' column
    return ($result && strpos($result['additional_info'], 'admin') !== false);
}

// Call this function when you want to check if the user is an admin
if (isAdmin($conn, $_SESSION['user_id'])) {
    $_SESSION['role'] = 'admin';
} else {
    $_SESSION['role'] = 'teacher';
}
?>
