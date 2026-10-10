<?php
session_start();
require __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$id=$_GET['id']??0;
if(!$id){ header('Location: dashboard.php'); exit; }

$txn=null;
try{
  global $pdo;
  if(isset($pdo)){
    $stmt=$pdo->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
    $stmt->execute([$id,$uid]);
    $txn=$stmt->fetch(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}
if(!$txn){ header('Location: dashboard.php'); exit; }

$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type=trim($_POST['type']??'expense');
  $cat=trim($_POST['category']??'Other');
  $amt=floatval($_POST['amount']??0);
  $date=$_POST['date']??date('Y-m-d');
  $desc=trim($_POST['description']??'');

  if($amt<=0) $err="Please enter a valid amount.";
  else{
    try{
      global $pdo;
      $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol = 'date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $sql = "UPDATE transactions SET type=?, category=?, amount=?, $dateCol=?, description=? WHERE id=? AND user_id=?";
      $stmt=$pdo->prepare($sql);
      $stmt->execute([$type,$cat,$amt,$date,$desc,$id,$uid]);
      header('Location: dashboard.php'); exit;
    }catch(Exception $e){ $err="Database error: ".$e->getMessage(); }
  }
}
function h($s){return htmlspecialchars($s??'');}
function sel($a,$b){return strtolower(trim($a))===strtolower(trim($b))?'selected':'';}
$dateVal = $txn['date']??$txn['transaction_date']??$txn['created_at']??date('Y-m-d');
$currCat = $txn['category']??'Other';
$currType = $txn['type']??'expense';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Transaction - Student Expense Tracker</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:Inter,system-ui,sans-serif}
body{background:#0e1414;color:#dbeae7;min-height:100vh}
.top{position:sticky;top:0;background:#16413e;border-bottom:1px solid #1f4a47;padding:14px 24px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:15px}
.nav a{color:#8fb8b3;text-decoration:none;margin-left:22px;font-size:13.5px}
.wrap{max-width:560px;margin:0 auto;padding:40px 22px}
h1{font-size:36px;font-weight:800;letter-spacing:-0.5px;margin-bottom:28px;text-align:center}
.card{background:#1b2c2b;border:1px solid #233e3c;border-radius:16px;padding:26px}
.tog{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px}
.tog label{background:#142524;border:1.5px solid #2a4a47;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600}
.tog input{accent-color:#0f6d5f}
.tog label:has(input:checked){border-color:#0f6d5f;background:#173834}
.lab{display:block;font-size:13px;font-weight:500;margin:18px 0 8px;color:#a8c4c0}
.inp, .sel{width:100%;padding:13px 16px;background:#101f1e;border:1.5px solid #2a4a47;border-radius:10px;color:#e8f0ef;font-size:14px;outline:none}
.btns{display:flex;align-items:center;gap:14px;margin-top:22px}
.save{padding:11px 22px;background:#0f6d5f;color:white;border:none;border-radius:10px;font-weight:700;cursor:pointer}
.cancel{color:#9bbfb9;text-decoration:underline;font-size:14px}
.err{background:#2a1a1a;border:1px solid #5a2a2a;color:#ff9a9a;padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px}
</style></head><body>
<div class="top"><b>Student Expense Tracker</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="#">Edit transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Edit transaction</h1>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" <?= $currType!=='income'?'checked':''?> onchange="updateCats()"> Expense</label>
<label><input type="radio" name="type" value="income" <?= $currType==='income'?'checked':''?> onchange="updateCats()"> Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs">
    <option <?=sel($currCat,'Food')?>>Food</option>
    <option <?=sel($currCat,'Mess / Canteen')?>>Mess / Canteen</option>
    <option <?=sel($currCat,'Transport / Bus')?>>Transport / Bus</option>
    <option <?=sel($currCat,'Accommodation / Rent')?>>Accommodation / Rent</option>
  </optgroup>
  <optgroup label="Study">
    <option <?=sel($currCat,'Education')?>>Education</option>
    <option <?=sel($currCat,'Books / Stationery')?>>Books / Stationery</option>
    <option <?=sel($currCat,'Internet / Recharge')?>>Internet / Recharge</option>
    <option <?=sel($currCat,'Course Fees')?>>Course Fees</option>
  </optgroup>
  <optgroup label="Lifestyle">
    <option <?=sel($currCat,'Travel')?>>Travel</option>
    <option <?=sel($currCat,'Entertainment / Movies')?>>Entertainment / Movies</option>
    <option <?=sel($currCat,'Shopping / Clothes')?>>Shopping / Clothes</option>
    <option <?=sel($currCat,'Health / Medical')?>>Health / Medical</option>
    <option <?=sel($currCat,'Gym / Sports')?>>Gym / Sports</option>
  </optgroup>
  <optgroup label="Others">
    <option <?=sel($currCat,'Bills / Electricity')?>>Bills / Electricity</option>
    <option <?=sel($currCat,'Personal Care')?>>Personal Care</option>
    <option <?=sel($currCat,'Other')?>>Other</option>
  </optgroup>
  <optgroup label="Income Sources">
    <option <?=sel($currCat,'Salary')?>>Salary</option>
    <option <?=sel($currCat,'Pocket Money')?>>Pocket Money</option>
    <option <?=sel($currCat,'Scholarship')?>>Scholarship</option>
    <option <?=sel($currCat,'Freelance / Part-time')?>>Freelance / Part-time</option>
    <option <?=sel($currCat,'From Parents')?>>From Parents</option>
    <option <?=sel($currCat,'Internship')?>>Internship</option>
    <option <?=sel($currCat,'Gift')?>>Gift</option>
  </optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" value="<?=h($txn['amount']??'')?>" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=h(substr($dateVal,0,10))?>" required>

<label class="lab">Description <span style="color:#7aa39e;font-weight:400">(optional)</span></label>
<input type="text" name="description" class="inp" value="<?=h($txn['description']??$txn['note']??'')?>" placeholder="e.g. Travel to college">

<div class="btns"><button type="submit" class="save">Update</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>
<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 const curr="<?=h($currCat)?>";
 if(type==='income'){
  sel.innerHTML=`<optgroup label="Income Sources">
    <option>Salary</option><option>Pocket Money</option><option>Scholarship</option>
    <option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option>
    <option>Gift</option><option>Other</option></optgroup>`;
 } else {
  sel.innerHTML=`<optgroup label="Daily Needs">
    <option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>`;
 }
 // keep current if possible
 for(let o of sel.options){ if(o.text.toLowerCase()===curr.toLowerCase()){ o.selected=true; break; } }
}
// init based on current type
window.onload=()=>{ if("<?=$currType?>"==="income") updateCats(); };
</script>
</body></html>