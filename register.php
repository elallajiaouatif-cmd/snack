<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'يرجى إدخال بريد إلكتروني صحيح! ⚠️';
        } elseif ($password !== $confirm_password) {
            $error = 'كلمات السر غير متطابقة! ⚠️';
        } elseif (strlen($password) < 6) {
            $error = 'يجب أن تكون كلمة السر من 6 أحرف أو أكثر! ⚠️';
        } else {
            // التحقق مما إذا كان اسم المستخدم أو البريد الإلكتروني مستخدماً من قبل
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'اسم المستخدم أو البريد الإلكتروني مستعمل بالفعل! ❌';
            } else {
                // تشفير كلمة السر وإدخال الحساب
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول. ✅';
                } else {
                    $error = 'حدث خطأ أثناء إنشاء الحساب! ❌';
                }
            }
        }
    } else {
        $error = 'يرجى إدخال جميع البيانات المطلوبة! ⚠️';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - Snack Express</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .register-box { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #d96528; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 13px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .btn { background: #d96528; color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; }
        .btn:hover { background: #b84f18; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .link { text-align: center; margin-top: 15px; font-size: 13px; }
        .link a { color: #d96528; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>📝 إنشاء حساب جديد</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="username" placeholder="ادخل اسم المستخدم" required>
        </div>
        <div class="form-group">
            <label>البريد الإلكتروني:</label>
            <input type="email" name="email" placeholder="example@gmail.com" required>
        </div>
        <div class="form-group">
            <label>كلمة السر:</label>
            <input type="password" name="password" placeholder="6 أحرف على الأقل" required>
        </div>
        <div class="form-group">
            <label>تأكيد كلمة السر:</label>
            <input type="password" name="confirm_password" placeholder="أعد كتابة كلمة السر" required>
        </div>
        <button type="submit" class="btn">إنشاء الحساب</button>
    </form>

    <div class="link">
        لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
    </div>
</div>

</body>
</html>