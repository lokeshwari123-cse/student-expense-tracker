<?php
session_start();
require __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=trim($_POST['type']??'expense');
 $cat=trim($_POST['category']??'Food');
 $amt=floatval($_POST['amount']??0);
 $date=$_POST['date']??date('Y-m-d');
 $desc=trim($_POST['description']??'');

 if($amt<=0) $err="Please enter a valid amount greater than zero.";
 else{
  if(function_exists('addTransaction')) {
    try{
      addTransaction($uid,$type,$cat,$amt,$date,$desc);
    }catch(Exception $e){ $err="Failed to save: ".$e->getMessage(); }
  } else {
    try{
      global $pdo;
      if(isset($pdo)){
        $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
        $dateCol = 'date';
        if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
        elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
        elseif(in_array('trans_date',$cols)) $dateCol='trans_date';
        elseif(in_array('created_at',$cols) && !in_array('date',$cols)) $dateCol='created_at';
        
        $sql = "INSERT INTO transactions (user_id,type,category,amount,$dateCol,description) VALUES (?,?,?,?,?,?)";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$uid,$type,$cat,$amt,$date,$desc]);
      }
    }catch(Exception $e){ 
      $err="Database error: ".$e->getMessage(); 
    }
  }
  if(!$err){ header('Location: dashboard.php'); exit; }
 }
}
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add transaction - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:#0a1413;color:#dbeae7;min-height:100vh}
.top{position:sticky;top:0;z-index:10;background:rgba(15,50,48,0.92);backdrop-filter:blur(14px);border-bottom:1px solid #1e3f3d;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:14px;display:flex;align-items:center;gap:10px;letter-spacing:-0.2px}
.top b i{width:30px;height:30px;background:linear-gradient(135deg,#2be5c8,#0f7a68);border-radius:8px;display:grid;place-items:center;color:#04201c;font-weight:800;font-style:normal}
.nav{display:flex;gap:24px;align-items:center}
.nav a{color:#7fa9a4;text-decoration:none;font-size:13px;font-weight:500;position:relative;padding-bottom:3px;transition:0.2s}
.nav a.active,.nav a:hover{color:#5ee9d5}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:#5ee9d5;border-radius:2px}
.wrap{max-width:720px;margin:0 auto;padding:40px 24px}
h1{font-size:44px;font-weight:800;letter-spacing:-1.5px;text-align:center;margin-bottom:10px}
.sub-h{text-align:center;color:#7ea29d;font-size:13.5px;margin-bottom:28px}
.card{max-width:520px;margin:0 auto;background:#112322;border:1px solid #1e3f3d;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.4),inset 0 1px 0 rgba(255,255,255,0.05)}
.tog{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px}
.tog label{background:#0e201e;border:1.5px solid #1e3f3d;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#8fb8b3;transition:0.2s}
.tog input{accent-color:#0f6d5f;width:16px;height:16px}
.tog label:has(input:checked){border-color:#1a8a7a;background:#14302d;color:#dbeae7;box-shadow:0 0 0 4px rgba(26,138,122,0.12)}
.lab{display:block;font-size:12px;font-weight:600;margin:20px 0 8px;color:#a9cbc6;letter-spacing:0.2px}
.lab span{color:#5a7a77;font-weight:400}
.inp,.sel{width:100%;padding:14px 16px;background:#0f211f;border:1.5px solid #1e3f3d;border-radius:12px;color:#eaf5f3;font-size:14px;outline:none;transition:0.2s;appearance:none}
.inp:focus,.sel:focus{border-color:#1a8a7a;background:#112a28;box-shadow:0 0 0 4px rgba(26,138,122,0.15)}
.inp::placeholder{color:#5a7a77}
.btns{display:flex;align-items:center;gap:16px;margin-top:28px}
.save{padding:13px 24px;background:linear-gradient(180deg,#1a8a7a,#0f6d5f);color:white;border:none;border-radius:12px;font-weight:700;font-size:13.5px;cursor:pointer;box-shadow:0 8px 20px rgba(15,109,95,0.35);transition:0.2s}
.save:hover{transform:translateY(-1px);box-shadow:0 12px 28px rgba(15,109,95,0.45)}
.cancel{font-size:13px;color:#7ea29d;text-decoration:none;border-bottom:1px solid #2a4a47;padding-bottom:2px;transition:0.2s}
.cancel:hover{color:#5ee9d5;border-color:#5ee9d5}
.err{background:#2b1c1c;border:1px solid #4a2a2a;color:#ff9f9f;padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:13px}
</style></head><body>
<div class="top"><b><i>S</i> Student Expense Tracker</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="add_transaction.php">Add transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Add transaction</h1>
<div class="sub-h">Record your income or expense in seconds</div>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" checked onchange="updateCats()"> Expense</label>
<label><input type="radio" name="type" value="income" onchange="updateCats()"> Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" placeholder="e.g. 500" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=date('Y-m-d')?>" required>

<label class="lab">Description <span>(optional)</span></label>
<input type="text" name="description" class="inp" placeholder="e.g. Mess fees, bus pass">

<div class="btns"><button type="submit" class="save">Save transaction</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>

<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 if(type==='income'){
  sel.innerHTML='<optgroup label="Income Sources"><option>Salary</option><option>Pocket Money</option><option>Scholarship</option><option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option><option>Gift</option><option>Other</option></optgroup>';
 } else {
  sel.innerHTML='<optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup><optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup><optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup><optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>';
 }
}
</script>
</body></html>