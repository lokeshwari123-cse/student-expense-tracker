<?php
session_start();
require __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];

// Fetch transactions - supports getTxns(), getTransactions(), or PDO
$txns = function_exists('getTxns') ? getTxns($uid) : (function_exists('getTransactions') ? getTransactions($uid) : []);
if(empty($txns)){
  try{
    global $pdo;
    if(isset($pdo)){
      $cols=$pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol='date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $stmt=$pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY $dateCol DESC");
      $stmt->execute([$uid]);
      $txns=$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }catch(Exception $e){}
}

// Month by month aggregation
$byMonth = [];
foreach($txns as $t){
  $d = $t['date']??$t['transaction_date']??$t['txn_date']??$t['created_at']??date('Y-m-d');
  $m = substr($d,0,7);
  $mName = date('F Y', strtotime($d));
  if(!isset($byMonth[$m])) $byMonth[$m]=['name'=>$mName,'income'=>0,'expense'=>0,'count'=>0];
  $amt=floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $byMonth[$m]['income']+=$amt;
  else $byMonth[$m]['expense']+=$amt;
  $byMonth[$m]['count']++;
}
krsort($byMonth);

// All-time by category
$byCat = [];
foreach($txns as $t){
  if(($t['type']??'expense')==='income') continue;
  $cat=$t['category']??'Other';
  if(!isset($byCat[$cat])) $byCat[$cat]=['entries'=>0,'total'=>0];
  $byCat[$cat]['entries']++;
  $byCat[$cat]['total']+=floatval($t['amount']??0);
}
arsort($byCat);

$totalInc=array_sum(array_column($byMonth,'income'));
$totalExp=array_sum(array_column($byMonth,'expense'));
$totalSaved=$totalInc-$totalExp;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monthly report - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:#0a1413;color:#dbeae7;min-height:100vh}
.top{position:sticky;top:0;z-index:10;background:rgba(15,50,48,0.92);backdrop-filter:blur(14px);border-bottom:1px solid #1e3f3d;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:14px;display:flex;gap:10px;align-items:center;letter-spacing:-0.2px}
.top b i{width:30px;height:30px;background:linear-gradient(135deg,#2be5c8,#0f7a68);border-radius:8px;display:grid;place-items:center;color:#04201c;font-weight:800;font-style:normal}
.nav{display:flex;gap:24px;align-items:center}
.nav a{color:#7fa9a4;text-decoration:none;font-size:13px;font-weight:500;position:relative;padding-bottom:3px;transition:0.2s}
.nav a.active,.nav a:hover{color:#5ee9d5}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:#5ee9d5;border-radius:2px}
.wrap{max-width:800px;margin:0 auto;padding:40px 24px}
h1{font-size:44px;font-weight:800;letter-spacing:-1.5px;margin-bottom:8px}
.sub{font-size:13.5px;color:#7ea29d;margin-bottom:28px}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:24px}
.stat{background:#122624;border:1px solid #1e3f3d;border-radius:16px;padding:18px 20px;position:relative;overflow:hidden}
.stat::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px}
.stat:nth-child(1)::before{background:#5ee9d5}.stat:nth-child(2)::before{background:#ff8a8a}.stat:nth-child(3)::before{background:#f5d76e}
.stat small{font-size:10px;letter-spacing:1px;color:#6f9a95;font-weight:600;text-transform:uppercase}
.stat h2{font-size:26px;font-weight:800;letter-spacing:-1px;margin-top:8px}
.box{background:#112322;border:1px solid #1e3f3d;border-radius:20px;padding:26px;margin-bottom:22px;box-shadow:0 20px 60px rgba(0,0,0,0.3),inset 0 1px 0 rgba(255,255,255,0.05)}
.box h3{font-size:14px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.box h3 span{width:28px;height:28px;background:#173834;border:1px solid #244a47;border-radius:8px;display:grid;place-items:center;font-size:13px}
table{width:100%;border-collapse:collapse}
th{font-size:10px;letter-spacing:1px;color:#5e8a86;text-transform:uppercase;font-weight:600;text-align:left;padding:10px 8px;border-bottom:1px solid #1e3f3d}
th:nth-child(2),th:nth-child(3),th:nth-child(4){text-align:right}
td{padding:14px 8px;border-bottom:1px solid #142a28;font-size:13.5px}
td:nth-child(2),td:nth-child(3),td:nth-child(4){text-align:right}
.inc{color:#5ee9d5;font-weight:700}.exp{color:#ff8a8a;font-weight:700}
.saved{color:#f5d76e;font-weight:700;background:rgba(245,215,110,0.1);padding:4px 10px;border-radius:20px;font-size:12.5px}
.cat-bar{height:6px;background:#0e1f1e;border-radius:10px;margin-top:8px;overflow:hidden}
.cat-bar i{display:block;height:100%;background:linear-gradient(90deg,#1a8a7a,#5ee9d5);border-radius:10px}
.empty{padding:28px;text-align:center;color:#7aa39e;font-size:13px}
@media(max-width:700px){.grid{grid-template-columns:1fr}.wrap{padding:24px 16px}h1{font-size:34px}}
</style></head><body>
<div class="top"><b><i>S</i> Student Expense Tracker</b><div class="nav"><a href="dashboard.php">Dashboard</a><a href="add_transaction.php">Add transaction</a><a class="active" href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>

<div class="wrap">
<h1>Monthly report</h1>
<div class="sub">Analyze your spending trends and category breakdown.</div>

<div class="grid">
  <div class="stat"><small>Total Income</small><h2>₹<?=number_format($totalInc)?></h2></div>
  <div class="stat"><small>Total Expenses</small><h2>₹<?=number_format($totalExp)?></h2></div>
  <div class="stat"><small>Total Saved</small><h2>₹<?=number_format($totalSaved)?></h2></div>
</div>

<div class="box">
<h3><span>📅</span> Month by month</h3>
<table>
<tr><th>Month</th><th>Income</th><th>Expenses</th><th>Saved</th></tr>
<?php if(!$byMonth):?>
<tr><td colspan="4" class="empty">No transactions found. Start by adding your first transaction.</td></tr>
<?php else: foreach($byMonth as $data): $saved=$data['income']-$data['expense']; ?>
<tr>
<td style="font-weight:600"><?=h($data['name'])?></td>
<td class="inc">₹<?=number_format($data['income'])?></td>
<td class="exp">₹<?=number_format($data['expense'])?></td>
<td><span class="saved">₹<?=number_format($saved)?></span></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

<div class="box">
<h3><span>📊</span> All-time expenses by category</h3>
<table>
<tr><th>Category</th><th>Entries</th><th>Total</th></tr>
<?php if(!$byCat):?>
<tr><td colspan="3" class="empty">No expenses recorded yet.</td></tr>
<?php else: 
  $max = $byCat ? max(array_column($byCat,'total')) : 1;
  foreach($byCat as $cat=>$info): $pct=$max?($info['total']/$max*100):0;
?>
<tr>
<td>
  <div style="font-weight:600"><?=h($cat)?></div>
  <div class="cat-bar"><i style="width:<?=$pct?>%"></i></div>
</td>
<td style="text-align:right;color:#a9cbc6"><?=h($info['entries'])?></td>
<td style="text-align:right;font-weight:700">₹<?=number_format($info['total'])?></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

</div>
</body></html>