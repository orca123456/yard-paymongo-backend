<?php
session_start();

require_once 'db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
        $stmt->execute([
            ':username' => $username
        ]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Yard Handicraft</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #333;
        }

        h2 span {
            color: #e84393;
        }

        label {
            display: block;
            margin-top: 18px;
            font-size: 14px;
            color: #555;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-top: 7px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            border: none;
            border-radius: 30px;
            background: #333;
            color: white;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #e84393;
        }

        .error {
            background: #ffe5e5;
            color: #c0392b;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }

        .back {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #777;
            font-size: 14px;
            text-decoration: none;
        }

        .back:hover {
            color: #e84393;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Yard Handicraft<span>.</span> Admin</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <a href="index.php" class="back">Back to Website</a>
</div>

</body>
</html>
