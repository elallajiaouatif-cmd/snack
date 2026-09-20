<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($username) && !empty($email) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error = 'كلمات السر غير متطابقة! ⚠️';
        } elseif (strlen($new_password) < 6) {
            $error = 'يجب أن تكون كلمة السر من 6 أحرف أو أكثر! ⚠️';
        } else {
            try {
                // البحث عن الحساب باسم المستخدم والبريد الإلكتروني معاً
                $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND LOWER(email) = LOWER(?)");
                $stmt->execute([$username, $email]);
                $user = $stmt->fetch();

                if ($user) {
                    // تشفير كلمة السر الجديدة وتحديثها
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    
                    if ($updateStmt->execute([$hashed_password, $user['id']])) {
                        $success = 'تم تغيير كلمة السر بنجاح! يمكنك الآن تسجيل الدخول. ✅';
                    } else {
                        $error = 'حدث خطأ أثناء تحديث كلمة السر! ❌';
                    }
                } else {
                    $error = 'لم يتم العثور على حساب يطابق اسم المستخدم والبريد الإلكتروني معاً! ❌';
                }
            } catch (PDOException $e) {
                $error = ' خطأ في قاعدة البيانات: تأكد من إضافة عمود email في phpMyAdmin. ⚠️';
            }
        }
    } else {
        $error = 'يرجى ملء كافة الحقول! ⚠️';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة السر - Snack Express</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .reset-box { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #d96528; margin-bottom: 20px; font-size: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 13px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        .btn { background: #d96528; color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; }
        .btn:hover { background: #b84f18; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .links { text-align: center; margin-top: 15px; font-size: 13px; display: flex; justify-content: space-between; }
        .links a { color: #d96528; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="reset-box">
    <h2>🔑 استعادة كلمة السر</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="username" placeholder="ادخل اسم المستخدم" required>
        </div>
        <div class="form-group">
            <label>البريد الإلكتروني المسجل:</label>
            <input type="email" name="email" placeholder="example@gmail.com" required>
        </div>
        <div class="form-group">
            <label>كلمة السر الجديدة:</label>
            <input type="password" name="new_password" placeholder="6 أحرف على الأقل" required>
        </div>
        <div class="form-group">
            <label>تأكيد كلمة السر الجديدة:</label>
            <input type="password" name="confirm_password" placeholder="أعد كتابة كلمة السر" required>
        </div>
        <button type="submit" class="btn">تغيير كلمة السر 🔄</button>
    </form>

    <div class="links">
        <a href="login.php">تسجيل الدخول</a>
        <a href="register.php">إنشاء حساب جديد</a>
    </div>
</div>

</body>
</html>