<?php
if (!defined('BASE_URL')) {
    $h = $_SERVER['HTTP_HOST'] ?? '';
    define('BASE_URL', str_contains($h, '5300.') ? '/proxy/' . ($_SERVER['SERVER_PORT'] ?? '8080') : '');
}

$DB_HOST = 'db01.dbhost.dev';
$DB_PORT = '5051';
$DB_NAME = 'db_4559wx7c3';
$DB_USER = 'user_4559wx7c3';
$DB_PASS = 'p4559wx7c3';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function getUsers(){
    global $pdo;
    return $pdo->query("SELECT * FROM expense_users")->fetchAll();
}
function getUserByEmail($email){
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM expense_users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}
function getTxns($uid=1){
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$uid]);
    return $stmt->fetchAll();
}
function saveUsers($data){ return true; }
function addTxn($uid, $type, $category, $amount, $date, $note){
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, date, note) VALUES (?,?,?,?,?,?)");
    return $stmt->execute([$uid, $type, $category, $amount, $date, $note]);
}
?>