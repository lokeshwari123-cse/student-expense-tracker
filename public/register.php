<?php
session_start();

require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $pass === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check whether the email already exists
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Securely hash the password
                $hash = password_hash($pass, PASSWORD_DEFAULT);

                // Insert into the actual users table columns
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password)
                     VALUES (?, ?, ?)'
                );
                $stmt->execute([$name, $email, $hash]);

                // Redirect to login after successful registration
                header('Location: login.php?msg=registered');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Registration database error: ' . $e->getMessage());
            $error = 'Registration failed. Please check your database setup and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account - Student Expense Tracker</title>
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
            min-height: 100vh;
            display: flex;
            color: #e6f0ef;
        }

        .left {
            flex: 1.15;
            background: radial-gradient(120% 120% at 20% 20%, #1a4a46 0%, #0e2e2b 45%, #0a1413 100%);
            padding: 48px 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2be5c8, #0f7a68);
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-weight: 800;
            color: #04201c;
        }

        .hero {
            max-width: 460px;
        }

        .hero h1 {
            font-size: 52px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -2px;
            margin-bottom: 18px;
        }

        .hero h1 span {
            color: #5ee9d5;
        }

        .hero p {
            font-size: 15px;
            line-height: 1.6;
            color: #8ab0ac;
            margin-bottom: 28px;
        }

        .points {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .point {
            display: flex;
            gap: 12px;
            align-items: center;
            font-size: 13px;
            color: #a7c8c3;
        }

        .point i {
            width: 32px;
            height: 32px;
            background: #173834;
            border: 1px solid #244a47;
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-style: normal;
        }

        .right {
            flex: 0.85;
            background: #0e1c1b;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
        }

        .card {
            width: 100%;
            max-width: 400px;
            background: #172a29;
            border: 1px solid #223e3c;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .card h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .sub {
            font-size: 13px;
            color: #7ea29d;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .lab {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #a9cbc6;
            margin: 16px 0 8px;
        }

        .inp {
            width: 100%;
            padding: 14px 16px;
            background: #0f211f;
            border: 1.5px solid #2a4a47;
            border-radius: 12px;
            color: #eaf5f3;
            font-size: 14px;
            outline: none;
        }

        .inp:focus {
            border-color: #1a8a7a;
        }

        .btn {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            background: linear-gradient(180deg, #1a8a7a, #0f6d5f);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .div {
            margin: 22px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #5a7a77;
            font-size: 12px;
        }

        .div::before,
        .div::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #1f3a38;
        }

        .foot {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #7ea29d;
        }

        .foot a {
            color: #5ee9d5;
            text-decoration: none;
            font-weight: 600;
        }

        .error {
            background: #2b1c1c;
            border: 1px solid #4a2a2a;
            color: #ff9f9f;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .copy {
            font-size: 11px;
            color: #5a7a77;
        }

        @media (max-width: 900px) {
            body {
                flex-direction: column;
            }

            .left {
                padding: 30px 26px;
                gap: 40px;
            }

            .hero h1 {
                font-size: 38px;
            }

            .right {
                padding: 28px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="left">
        <div class="logo">
            <div class="logo-icon">S</div>
            <b>Student Expense Tracker</b>
        </div>

        <div class="hero">
            <h1>Join us,<br><span>start saving.</span></h1>
            <p>Create your account and take control of your student finances.</p>

            <div class="points">
                <div class="point"><i>₹</i> Free for students</div>
                <div class="point"><i>◧</i> Monthly reports and insights</div>
                <div class="point"><i>✓</i> Secure password storage</div>
            </div>
        </div>

        <div class="copy">© 2026 Student Expense Tracker</div>
    </div>

    <div class="right">
        <div class="card">
            <h2>Create your account</h2>
            <div class="sub">Enter your details to get started.</div>

            <?php if ($error !== ''): ?>
                <div class="error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label class="lab" for="name">Full Name</label>
                <input
                    class="inp"
                    id="name"
                    type="text"
                    name="name"
                    maxlength="100"
                    required
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <label class="lab" for="email">Email Address</label>
                <input
                    class="inp"
                    id="email"
                    type="email"
                    name="email"
                    maxlength="150"
                    required
                    placeholder="you@college.edu"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <label class="lab" for="password">Password (6+ characters)</label>
                <input
                    class="inp"
                    id="password"
                    type="password"
                    name="password"
                    minlength="6"
                    required
                    placeholder="Create a password"
                >

                <button class="btn" type="submit">Create account →</button>
            </form>

            <div class="div">or</div>

            <div class="foot">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>
</body>
</html>
