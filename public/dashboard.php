
<?php
session_start();

require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$uname = $_SESSION['user_name'] ?? 'Student';

$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCat = $_GET['category'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$transactions = [];
$totalIncome = 0;
$totalExpense = 0;
$byCat = [];
$balance = 0;
$errorMessage = '';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM transactions")
                ->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('date', $cols, true)) {
        $dateCol = 'date';
    } elseif (in_array('transaction_date', $cols, true)) {
        $dateCol = 'transaction_date';
    } elseif (in_array('created_at', $cols, true)) {
        $dateCol = 'created_at';
    } else {
        throw new RuntimeException('No transaction date column was found.');
    }

    $sql = "SELECT * FROM transactions WHERE user_id = ?";
    $params = [$uid];

    if ($filterMonth !== '') {
        $sql .= " AND DATE_FORMAT(`$dateCol`, '%Y-%m') = ?";
        $params[] = $filterMonth;
    }

    if ($filterCat !== 'All' && $filterCat !== '') {
        $sql .= " AND LOWER(category) = LOWER(?)";
        $params[] = $filterCat;
    }

    if ($search !== '') {
        $sql .= " AND (description LIKE ? OR category LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY `$dateCol` DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($transactions as $transaction) {
        $amount = (float)($transaction['amount'] ?? 0);
        $type = strtolower($transaction['type'] ?? 'expense');

        if ($type === 'income') {
            $totalIncome += $amount;
        } else {
            $totalExpense += $amount;

            $category = $transaction['category'] ?? 'Other';
            $byCat[$category] = ($byCat[$category] ?? 0) + $amount;
        }
    }

    $balance = $totalIncome - $totalExpense;

} catch (Throwable $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $errorMessage = 'Unable to load transactions. Please check your database configuration.';
}

$maxCat = $byCat ? max($byCat) : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | Student Expense Tracker</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Inter', system-ui, sans-serif;
}

body {
    background: #0a1413;
    color: #dbeae7;
    min-height: 100vh;
}

.top {
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(15,50,48,.95);
    border-bottom: 1px solid #1e3f3d;
    padding: 14px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.brand {
    font-size: 14px;
    font-weight: 800;
    display: flex;
    gap: 10px;
    align-items: center;
}

.brand-icon {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg,#2be5c8,#0f7a68);
    border-radius: 8px;
    display: grid;
    place-items: center;
    color: #04201c;
}

.nav {
    display: flex;
    gap: 22px;
    align-items: center;
    flex-wrap: wrap;
}

.nav a {
    color: #7fa9a4;
    text-decoration: none;
    font-size: 13px;
}

.nav a:hover,
.nav a.active {
    color: #5ee9d5;
}

.wrap {
    max-width: 1120px;
    margin: 0 auto;
    padding: 28px 24px;
}

.head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
    flex-wrap: wrap;
    gap: 14px;
}

.head h1 {
    font-size: 26px;
    font-weight: 800;
}

.head p {
    font-size: 12.5px;
    color: #7ea29d;
    margin-top: 6px;
}

.reset {
    font-size: 12px;
    padding: 9px 14px;
    border: 1px solid #704344;
    background: #2b1c1d;
    border-radius: 10px;
    color: #ffaaaa;
    cursor: pointer;
}

.reset:hover {
    background: #422526;
}

.filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1.5fr;
    gap: 14px;
    margin-bottom: 20px;
}

.f input,
.f select {
    width: 100%;
    padding: 13px 15px;
    background: #132625;
    border: 1.5px solid #1e3f3d;
    border-radius: 12px;
    color: #dbeae7;
    font-size: 13px;
    outline: none;
}

.f input:focus,
.f select:focus {
    border-color: #1a8a7a;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}

.stat {
    background: #122624;
    border: 1px solid #1e3f3d;
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.stat::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
}

.income::before { background: #5ee9d5; }
.expense::before { background: #ff8a8a; }
.balance::before { background: #f5d76e; }

.stat small {
    font-size: 10px;
    letter-spacing: 1px;
    color: #7fa9a4;
    font-weight: 700;
    text-transform: uppercase;
}

.stat h2 {
    font-size: 27px;
    margin-top: 10px;
    overflow-wrap: anywhere;
}

.income h2 { color: #5ee9d5; }
.expense h2 { color: #ff9f9f; }
.balance h2 { color: #fff0a8; }

.card {
    background: #112322;
    border: 1px solid #1e3f3d;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 20px;
}

.card h3 {
    font-size: 14px;
    margin-bottom: 18px;
}

.bar-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

.bar-label {
    width: 90px;
    font-size: 12px;
    color: #a9cbc6;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bar-track {
    flex: 1;
    height: 8px;
    background: #0e1f1e;
    border-radius: 10px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg,#f5b82e,#ffcf4d);
    border-radius: 10px;
}

.bar-val {
    width: 90px;
    text-align: right;
    font-size: 12px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

th {
    font-size: 10px;
    letter-spacing: 1px;
    color: #7fa9a4;
    text-transform: uppercase;
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #1e3f3d;
}

td {
    padding: 14px 12px;
    font-size: 13px;
    border-bottom: 1px solid #1b302e;
}

td:first-child {
    color: #8ab0ac;
}

.amt-income {
    color: #5ee9d5;
    font-weight: 700;
    white-space: nowrap;
}

.amt-expense {
    color: #ff9f9f;
    font-weight: 700;
    white-space: nowrap;
}

.action {
    display: flex;
    gap: 14px;
}

.action a {
    font-size: 12px;
    text-decoration: none;
    font-weight: 600;
}

.edit { color: #5ee9d5; }
.del { color: #ff8a8a; }

.empty {
    padding: 32px;
    text-align: center;
    color: #7fa9a4;
    font-size: 13px;
}

.message {
    padding: 12px 16px;
    margin-bottom: 16px;
    border-radius: 10px;
    font-size: 13px;
}

.success {
    border: 1px solid #1e8b78;
    color: #5ee9d5;
    background: #122624;
}

.error {
    border: 1px solid #704344;
    color: #ffaaaa;
    background: #2b1c1d;
}

@media (max-width: 800px) {
    .filters,
    .stats {
        grid-template-columns: 1fr;
    }

    .top {
        padding: 14px 18px;
        align-items: flex-start;
        flex-direction: column;
    }

    .nav {
        gap: 14px;
    }

    .wrap {
        padding: 20px 16px;
    }
}
</style>
</head>

<body>

<header class="top">
    <div class="brand">
        <span class="brand-icon">S</span>
        Student Expense Tracker
    </div>

    <nav class="nav">
        <a class="active" href="dashboard.php">Dashboard</a>
        <a href="add_transaction.php">Add transaction</a>
        <a href="monthly_report.php">Monthly report</a>
        <a href="logout.php">Log out</a>
    </nav>
</header>

<main class="wrap">

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="message success">
            Transactions reset successfully.
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error">
            <?= h($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="head">
        <div>
            <h1>Dashboard</h1>
            <p>
                <?= count($transactions) ?> transactions
                &bull; Welcome, <?= h($uname) ?>
            </p>
        </div>

        <form action="reset.php" method="POST"
              onsubmit="return confirm('Are you sure? This will delete all your transactions and cannot be undone.');">
            <button type="submit" class="reset">
                Reset demo data
            </button>
        </form>
    </section>

    <form class="filters" method="GET" action="dashboard.php">
        <div class="f">
            <input type="month" name="month"
                   value="<?= h($filterMonth) ?>"
                   aria-label="Filter by month">
        </div>

        <div class="f">
            <select name="category" aria-label="Filter by category">
                <?php
                $categories = [
                    'All', 'Food', 'Education', 'Travel',
                    'Books', 'Hostel', 'Entertainment',
                    'Other', 'Salary'
                ];
                foreach ($categories as $category):
                ?>
                    <option value="<?= h($category) ?>"
                        <?= $filterCat === $category ? 'selected' : '' ?>>
                        <?= $category === 'All'
                            ? 'All categories'
                            : h($category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f">
            <input type="search" name="search"
                   placeholder="Search description or category"
                   value="<?= h($search) ?>"
                   aria-label="Search transactions">
        </div>

        <div>
            <button type="submit" class="reset">Apply filters</button>
            <a href="dashboard.php"
               style="color:#5ee9d5;font-size:12px;margin-left:10px;">
                Clear filters
            </a>
        </div>
    </form>

    <section class="stats">
        <div class="stat income">
            <small>Total Income</small>
            <h2>₹<?= number_format($totalIncome, 2) ?></h2>
        </div>

        <div class="stat expense">
            <small>Total Expenses</small>
            <h2>₹<?= number_format($totalExpense, 2) ?></h2>
        </div>

        <div class="stat balance">
            <small>Available Balance</small>
            <h2>₹<?= number_format($balance, 2) ?></h2>
        </div>
    </section>

    <section class="card">
        <h3>Spending by category</h3>

        <?php if (empty($byCat)): ?>
            <div class="empty">No expenses found for these filters.</div>
        <?php else: ?>
            <?php foreach ($byCat as $category => $amount):
                $percentage = ($amount / $maxCat) * 100;
            ?>
                <div class="bar-row">
                    <div class="bar-label"><?= h($category) ?></div>

                    <div class="bar-track">
                        <div class="bar-fill"
                             style="width:<?= min(100, $percentage) ?>%">
                        </div>
                    </div>

                    <div class="bar-val">
                        ₹<?= number_format($amount, 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3>Recent Transactions</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No transactions found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($transactions, 0, 20) as $t):
                        $date = $t['date']
                            ?? $t['transaction_date']
                            ?? $t['created_at']
                            ?? '';

                        $isIncome = strtolower($t['type'] ?? 'expense') === 'income';
                        $amount = (float)($t['amount'] ?? 0);
                        $id = $t['id'] ?? null;
                    ?>
                        <tr>
                            <td><?= h(substr((string)$date, 0, 10)) ?></td>

                            <td><?= h($t['category'] ?? 'Other') ?></td>

                            <td>
                                <?= h($t['description'] ?? $t['note'] ?? '-') ?>
                            </td>

                            <td class="<?= $isIncome ? 'amt-income' : 'amt-expense' ?>">
                                <?= $isIncome ? '+' : '-' ?>
                                ₹<?= number_format($amount, 2) ?>
                            </td>

                            <td>
                                <?php if ($id !== null): ?>
                                    <div class="action">
                                        <a class="edit"
                                           href="edit_transaction.php?id=<?= urlencode((string)$id) ?>">
                                            Edit
                                        </a>

                                        <a class="del"
                                           href="delete_transaction.php?id=<?= urlencode((string)$id) ?>"
                                           onclick="return confirm('Delete this transaction?');">
                                            Delete
                                        </a>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>
