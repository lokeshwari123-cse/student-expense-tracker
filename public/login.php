<?php
session_start();
require_once __DIR__.'/config/db.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=trim($_POST['email']??'');
 $pass=$_POST['password']??'';
 if($email && $pass){
  $u = function_exists('getUserByEmail')? getUserByEmail($email) : null;
  if(!$u){
    try{
      global $pdo;
      if(isset($pdo)){
        $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u=$stmt->fetch(PDO::FETCH_ASSOC);
      }
    }catch(Exception $e){}
  }
  if($u && password_verify($pass,$u['password'])){
   session_regenerate_id(true);
   $_SESSION['user_id']=$u['id'];
   $_SESSION['user_name']=$u['name']??$u['username']??explode('@',$u['email'])[0];
   header('Location: dashboard.php'); exit;
  } else {
    $error="Invalid email or password. Please try again.";
  }
 } else {
   $error="Please fill in all fields.";
 }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:#0a1413;min-height:100vh;display:flex;color:#e6f0ef}
.left{flex:1.15;background:radial-gradient(120% 120% at 20% 20%,#1a4a46 0%,#0e2e2b 45%,#0a1413 100%);padding:48px 56px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}
.left::before{content:'';position:absolute;width:600px;height:600px;background:radial-gradient(circle,#1e6b65 0%,transparent 70%);top:-150px;left:-150px;opacity:0.4;filter:blur(40px)}
.logo{display:flex;align-items:center;gap:10px;position:relative;z-index:1}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,#2be5c8,#0f7a68);border-radius:10px;display:grid;place-items:center;font-weight:800;color:#04201c;font-size:16px}
.logo b{font-size:15px}
.hero{position:relative;z-index:1;max-width:460px}
.hero h1{font-size:52px;line-height:0.95;font-weight:800;letter-spacing:-2px;margin-bottom:18px}
.hero h1 span{background:linear-gradient(90deg,#5ee9d5,#8ef0e2);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:15.5px;line-height:1.6;color:#8ab0ac;margin-bottom:28px}
.points{display:flex;flex-direction:column;gap:12px}
.point{display:flex;gap:12px;align-items:center;font-size:13.5px;color:#a7c8c3}
.point i{width:32px;height:32px;background:#173834;border:1px solid #244a47;border-radius:9px;display:grid;place-items:center;font-style:normal}
.right{flex:0.85;background:#0e1c1b;display:flex;align-items:center;justify-content:center;padding:40px 30px}
.card{width:100%;max-width:400px;background:#172a29;border:1px solid #223e3c;border-radius:20px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.4),inset 0 1px 0 rgba(255,255,255,0.05)}
.card h2{font-size:28px;font-weight:800;letter-spacing:-0.8px;margin-bottom:6px}
.sub{font-size:13px;color:#7ea29d;margin-bottom:22px;line-height:1.5}
.lab{display:block;font-size:12px;font-weight:600;color:#a9cbc6;margin:16px 0 8px;letter-spacing:0.3px}
.inp{width:100%;padding:14px 16px;background:#0f211f;border:1.5px solid #2a4a47;border-radius:12px;color:#eaf5f3;font-size:14px;outline:none;transition:0.2s}
.inp:focus{border-color:#1a8a7a;background:#102523;box-shadow:0 0 0 4px rgba(30,138,122,0.15)}
.pass-wrap{position:relative}
.pass-wrap.inp{padding-right:46px}
.eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7ea29d;font-size:18px;width:32px;height:32px;display:grid;place-items:center;border-radius:8px;transition:0.2s}
.eye:hover{background:#173834;color:#5ee9d5}
.btn{width:100%;margin-top:22px;padding:14px;background:linear-gradient(180deg,#1a8a7a,#0f6d5f);color:white;border:none;border-radius:12px;font-weight:700;font-size:14.5px;cursor:pointer;transition:0.2s;box-shadow:0 8px 20px rgba(15,109,95,0.35)}
.btn:hover{transform:translateY(-1px);box-shadow:0 12px 28px rgba(15,109,95,0.45)}
.div{margin:22px 0;display:flex;align-items:center;gap:12px;color:#5a7a77;font-size:12px}
.div::before,.div::after{content:'';flex:1;height:1px;background:#1f3a38}
.foot{margin-top:20px;text-align:center;font-size:13px;color:#7ea29d}
.foot a{color:#5ee9d5;text-decoration:none;font-weight:600;border-bottom:1px solid rgba(94,233,213,0.3);padding-bottom:2px}
.error{background:#2b1c1c;border:1px solid #4a2a2a;color:#ff9f9f;padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
.copy{position:relative;z-index:1;font-size:11px;color:#5a7a77}
@media(max-width:900px){body{flex-direction:column}.left{padding:30px 26px}.hero h1{font-size:38px}.right{padding:28px 20px}}
</style>
</head><body>
<div class="left">
  <div class="logo"><div class="logo-icon">S</div><b>Student Expense Tracker</b></div>
  <div class="hero">
    <h1>Track smarter,<br><span>spend better.</span></h1>
    <p>Designed for students. Manage income, expenses, and monthly reports in one clean dashboard.</p>
    <div class="points">
      <div class="point"><i>₹</i> Real-time balance & category breakdown</div>
      <div class="point"><i>◧</i> Monthly reports & spending insights</div>
      <div class="point"><i>✓</i> Secure and student-friendly</div>
    </div>
  </div>
  <div class="copy">© 2026 Student Expense Tracker • Built for Students</div>
</div>

<div class="right">
  <div class="card">
    <h2>Welcome back</h2>
    <div class="sub">Sign in to continue to your expense dashboard.</div>
    <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
    <form method="POST" autocomplete="off">
      <label class="lab">Email Address</label>
      <input type="email" name="email" class="inp" required placeholder="you@college.edu" value="" autocomplete="off">

      <label class="lab">Password</label>
      <div class="pass-wrap">
        <input type="password" id="password" name="password" class="inp" required placeholder="Enter your password" value="" autocomplete="new-password">
        <button type="button" class="eye" onclick="togglePass()" id="eyeBtn">👁</button>
      </div>

      <button type="submit" class="btn">Log in →</button>
    </form>
    <div class="div">or</div>
    <div class="foot">New here? <a href="register.php">Create an account</a></div>
  </div>
</div>

<script>
function togglePass(){
  const inp = document.getElementById('password');
  const btn = document.getElementById('eyeBtn');
  if(inp.type === 'password'){
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body></html>