<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && $password === 'admin123') {
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
<div class="container">
    <div class="login-card">
        <h1>Dealership Creative Tool</h1>
        <p>Sign in to continue</p>

        <?php if ($error): ?>
        <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text" id="username" name="username" placeholder="Enter username" 
                   style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:1rem">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password"
                   style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:1rem">
        </div>
        <button onclick="doLogin()" style="width:100%;padding:14px;background:#4361ee;color:white;border:none;border-radius:8px;font-size:1.1rem;font-weight:600;cursor:pointer">
            Login
        </button>
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