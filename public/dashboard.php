<?php
session_start();
require __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['user_name'] ?? 'Jackson';

$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCat = $_GET['category'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$transactions = [];
try{
  global $pdo;
  if(isset($pdo)){
    $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
    $dateCol = in_array('date',$cols)?'date':(in_array('transaction_date',$cols)?'transaction_date':'created_at');
    
    $sql = "SELECT * FROM transactions WHERE user_id=? ";
    $params = [$uid];
    if($filterMonth){
      $sql .= " AND DATE_FORMAT($dateCol, '%Y-%m') = ? ";
      $params[] = $filterMonth;
    }
    $sql .= " ORDER BY $dateCol DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}

// Filter by category & search in PHP
if($filterCat!=='All' && $filterCat!==''){
  $transactions = array_filter($transactions, fn($t)=>strtolower($t['category']??'')===strtolower($filterCat));
}
if($search!==''){
  $transactions = array_filter($transactions, fn($t)=>stripos(($t['description']??'').($t['category']??''), $search)!==false);
}

$totalIncome = 0; $totalExpense = 0;
$byCat = [];
foreach($transactions as $t){
  $amt = floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $totalIncome += $amt;
  else { $totalExpense += $amt; $byCat[$t['category']??'Other'] = ($byCat[$t['category']??'Other']??0)+$amt; }
}
$balance = $totalIncome - $totalExpense;
$maxCat = $byCat ? max($byCat) : 1;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:#0a1413;color:#dbeae7;min-height:100vh}
.top{position:sticky;top:0;z-index:10;background:rgba(15,50,48,0.85);backdrop-filter:blur(12px);border-bottom:1px solid #1e3f3d;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:14px;letter-spacing:-0.2px;display:flex;gap:10px;align-items:center}
.top b i{width:30px;height:30px;background:linear-gradient(135deg,#2be5c8,#0f7a68);border-radius:8px;display:grid;place-items:center;color:#04201c;font-weight:800;font-style:normal;font-size:14px}
.nav{display:flex;gap:24px;align-items:center}
.nav a{color:#7fa9a4;text-decoration:none;font-size:13px;font-weight:500;transition:0.2s;position:relative;padding-bottom:3px}
.nav a.active,.nav a:hover{color:#5ee9d5}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:#5ee9d5;border-radius:2px}
.wrap{max-width:1120px;margin:0 auto;padding:28px 24px}
.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.head h1{font-size:24px;font-weight:800;letter-spacing:-0.6px}
.head p{font-size:12.5px;color:#7ea29d;margin-top:4px}
.head p b{color:#a9cbc6;font-weight:600}
.reset{font-size:11.5px;padding:7px 12px;border:1px solid #2a4a47;background:#132625;border-radius:20px;color:#7ea29d;cursor:pointer}
.filters{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px}
.f{position:relative}
.f input,.f select{width:100%;padding:13px 16px;background:#132625;border:1.5px solid #1e3f3d;border-radius:12px;color:#dbeae7;font-size:13.5px;outline:none;transition:0.2s;appearance:none}
.f input:focus,.f select:focus{border-color:#1a8a7a;background:#112a28}
.f select{cursor:pointer}
.stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px}
.stat{background:#122624;border:1px solid #1e3f3d;border-radius:16px;padding:18px 20px;position:relative;overflow:hidden}
.stat::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px}
.stat.income::before{background:#5ee9d5}
.stat.expense::before{background:#ff8a8a}
.stat.balance::before{background:#f5d76e}
.stat small{font-size:10px;letter-spacing:1px;color:#6f9a95;font-weight:600;text-transform:uppercase}
.stat h2{font-size:28px;font-weight:800;letter-spacing:-1px;margin-top:8px}
.stat.income h2{color:#e8fffd}
.stat.expense h2{color:#ff9f9f}
.stat.balance h2{color:#fff7cc}
.card{background:#112322;border:1px solid #1e3f3d;border-radius:16px;padding:22px;margin-bottom:20px}
.card h3{font-size:14px;font-weight:700;margin-bottom:16px;letter-spacing:-0.2px}
.bar-row{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.bar-row:last-child{margin-bottom:0}
.bar-label{width:70px;font-size:12.5px;color:#a9cbc6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{flex:1;height:8px;background:#0e1f1e;border-radius:10px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,#f5b82e,#ffcf4d);border-radius:10px;transition:1s}
.bar-val{width:60px;text-align:right;font-size:12px;font-weight:600;color:#cbd5d5}
.table-wrap{overflow:auto}
table{width:100%;border-collapse:collapse}
th{font-size:10px;letter-spacing:1px;color:#5e8a86;text-transform:uppercase;font-weight:600;text-align:left;padding:10px 12px;border-bottom:1px solid #1e3f3d}
td{padding:14px 12px;font-size:13px;border-bottom:1px solid #142a28}
td:first-child{color:#8ab0ac}
.amt-income{color:#5ee9d5;font-weight:700}
.amt-expense{color:#ff9f9f;font-weight:700}
.action{display:flex;gap:14px}
.action a{font-size:12px;text-decoration:none;font-weight:600}
.action .edit{color:#5ee9d5}
.action .del{color:#ff8a8a}
.empty{padding:40px;text-align:center;color:#5a7a77;font-size:13px}
@media(max-width:800px){.filters,.stats{grid-template-columns:1fr}.top{padding:12px 18px}.wrap{padding:20px 16px}}
</style>
</head><body>
<div class="top">
  <b><i>S</i> Student Expense Tracker</b>
  <div class="nav">
    <a class="active" href="dashboard.php">Dashboard</a>
    <a href="add_transaction.php">Add transaction</a>
    <a href="monthly_report.php">Monthly report</a>
    <a href="logout.php">Log out</a>
  </div>
</div>

<div class="wrap">
  <div class="head">
    <div>
      <h1>Dashboard</h1>
      <p><?=count($transactions)?> Transactions • User ID: <?=h($uid)?> • <?=h($uname)?></p>
    </div>
    <button class="reset" onclick="if(confirm('Reset demo data?')) location.href='reset.php'">Reset demo data</button>
  </div>

  <form class="filters" method="GET">
    <div class="f"><input type="month" name="month" value="<?=h($filterMonth)?>" onchange="this.form.submit()"></div>
    <div class="f">
      <select name="category" onchange="this.form.submit()">
        <option value="All" <?= $filterCat==='All'?'selected':''?>>All categories</option>
        <option value="Food" <?= $filterCat==='Food'?'selected':''?>>Food</option>
        <option value="Education" <?= $filterCat==='Education'?'selected':''?>>Education</option>
        <option value="Travel" <?= $filterCat==='Travel'?'selected':''?>>Travel</option>
        <option value="Other" <?= $filterCat==='Other'?'selected':''?>>Other</option>
        <option value="Salary" <?= $filterCat==='Salary'?'selected':''?>>Salary</option>
      </select>
    </div>
    <div class="f"><input type="text" name="search" placeholder="Search by description or category" value="<?=h($search)?>"></div>
  </form>

  <div class="stats">
    <div class="stat income"><small>Total Income</small><h2>₹<?=number_format($totalIncome)?></h2></div>
    <div class="stat expense"><small>Total Expenses</small><h2>₹<?=number_format($totalExpense)?></h2></div>
    <div class="stat balance"><small>Available Balance</small><h2>₹<?=number_format($balance)?></h2></div>
  </div>

  <div class="card">
    <h3>Spending by category</h3>
    <?php if(!$byCat):?><div class="empty">No expenses yet</div>
    <?php else: foreach($byCat as $cat=>$amt): $pct=($maxCat?($amt/$maxCat*100):0); ?>
      <div class="bar-row">
        <div class="bar-label"><?=h($cat)?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%"></div></div>
        <div class="bar-val">₹<?=number_format($amt)?></div>
      </div>
    <?php endforeach; endif;?>
  </div>

  <div class="card">
    <h3>Recent Transactions</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(!$transactions):?><tr><td colspan="5" class="empty">No transactions found</td></tr>
        <?php else: foreach(array_slice($transactions,0,20) as $t):
          $d = $t['date']??$t['transaction_date']??$t['created_at']??'';
          $isInc = ($t['type']??'expense')==='income';
        ?>
          <tr>
            <td><?=h(substr($d,0,10))?></td>
            <td><?=h($t['category']??'Other')?></td>
            <td><?=h($t['description']??$t['note']??'-')?></td>
            <td class="<?=$isInc?'amt-income':'amt-expense'?>"><?=$isInc?'+':'-'?> ₹<?=number_format(floatval($t['amount']??0))?></td>
            <td><div class="action"><a class="edit" href="edit_transaction.php?id=<?=$t['id']?>">Edit</a><a class="del" href="delete_transaction.php?id=<?=$t['id']?>" onclick="return confirm('Delete?')">Delete</a></div></td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body></html>