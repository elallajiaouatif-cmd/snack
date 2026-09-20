<?php
session_start();
require_once 'db.php';

// إذا كان مسجلاً بالفعل يتم توجيهه للرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password = trim($_POST['password']);

    if (!empty($login_input) && !empty($password)) {
        // دخول حساب الأدمن افتراضياً
        if ($login_input === 'admin' && $password === '123456') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = 'Admin';
            $_SESSION['role'] = 'admin';
            header('Location: admin.php');
            exit;
        }

        // دخول الزبون (باسم المستخدم أو البريد الإلكتروني)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = ($user['username'] === 'admin') ? 'admin' : 'client';

            if ($_SESSION['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'اسم المستخدم/البريد أو كلمة السر غير صحيحة! ❌';
        }
    } else {
        $error = 'يرجى إدخال جميع البيانات! ⚠️';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Snack Express</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 360px; }
        h2 { text-align: center; color: #d96528; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 13px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .btn { background: #d96528; color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .links { text-align: center; margin-top: 15px; font-size: 13px; display: flex; justify-content: space-between; }
        .links a { color: #d96528; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>🔐 تسجيل الدخول</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>اسم المستخدم أو البريد:</label>
            <input type="text" name="login_input" placeholder="اسم المستخدم أو الايميل" required>
        </div>
        <div class="form-group">
            <label>كلمة السر:</label>
            <input type="password" name="password" placeholder="ادخل كلمة السر" required>
        </div>
        <button type="submit" class="btn">دخول 🚀</button>
    </form>

    <div class="links">
        <a href="forgot_password.php">نسيت كلمة السر؟</a>
        <a href="register.php">إنشاء حساب جديد</a>
    </div>
</div>

</body>
</html>