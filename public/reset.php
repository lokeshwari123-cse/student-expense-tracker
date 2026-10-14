
<?php
session_start();

require_once __DIR__ . '/config/db.php';

// Check whether the user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only allow POST requests to reset data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$uid = $_SESSION['user_id'];

try {
    // Delete transactions belonging only to the logged-in user
    $stmt = $pdo->prepare(
        'DELETE FROM transactions WHERE user_id = ?'
    );

    $stmt->execute([$uid]);

    // Return to dashboard after successful reset
    header('Location: dashboard.php?reset=success');
    exit;

} catch (PDOException $e) {
    // Log the technical error without displaying database details
    error_log('Reset failed: ' . $e->getMessage());

    http_response_code(500);
    echo 'Unable to reset transactions. Please check the database connection.';
    exit;
}
?>
