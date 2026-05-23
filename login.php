<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && password_verify($password, '$2y$10$EBJRMtKvGgN9xxlVZpx7buyDLnBI4hTKSZT6.YEJq6dqsuSmZj/Ka')) {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dealership Creative Tool</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <h1>Dealership Creative Tool</h1>
        <p>Sign in to continue</p>

        <?php if ($error): ?>
        <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text" id="username" name="username" placeholder="Enter username">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password">
        </div>
        <button onclick="doLogin()">Login</button>
    </div>
</div>
<script>
function doLogin() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'login.php';
    const u = document.createElement('input');
    u.type = 'hidden'; u.name = 'username'; u.value = username;
    const p = document.createElement('input');
    p.type = 'hidden'; p.name = 'password'; p.value = password;
    form.appendChild(u); form.appendChild(p);
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>