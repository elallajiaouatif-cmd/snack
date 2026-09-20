<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';
$search_phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

// 1. إلغاء الطلبية من طرف الزبون
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);

    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $order['status'] === 'قيد الانتظار ⏳') {
        $delStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        if ($delStmt->execute([$order_id])) {
            $success = 'تم إلغاء الطلبية بنجاح! ❌';
        } else {
            $error = 'حدث خطأ أثناء إلغاء الطلب! ⚠️';
        }
    } else {
        $error = 'عذراً، لا يمكن إلغاء هذه الطلبية لأنها قيد التحضير أو تم توصيلها! ⚠️';
    }
}

// 2. تعديل بيانات التوصيل من طرف الزبون
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $name = trim($_POST['customer_name']);
    $phone = trim($_POST['customer_phone']);
    $address = trim($_POST['customer_address']);

    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $order['status'] === 'قيد الانتظار ⏳') {
        if (!empty($name) && !empty($phone) && !empty($address)) {
            $updStmt = $pdo->prepare("UPDATE orders SET customer_name = ?, customer_phone = ?, customer_address = ? WHERE id = ?");
            if ($updStmt->execute([$name, $phone, $address, $order_id])) {
                $success = 'تم تعديل بيانات التوصيل بنجاح! ✏️✅';
            } else {
                $error = 'حدث خطأ أثناء تعديل البيانات! ❌';
            }
        } else {
            $error = 'يرجى إدخال جميع البيانات! ⚠️';
        }
    } else {
        $error = 'عذراً، لا يمكن تعديل هذه الطلبية الآن! ⚠️';
    }
}

// 3. جلب الطلبيات المسجلة للزبون المسجل أو برقم الهاتف
$orders = [];
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($current_user_id > 0) {
    if (!empty($search_phone)) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? OR customer_phone = ? ORDER BY id DESC");
        $stmt->execute([$current_user_id, $search_phone]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$current_user_id]);
    }
    $orders = $stmt->fetchAll();
} elseif (!empty($search_phone)) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_phone = ? ORDER BY id DESC");
    $stmt->execute([$search_phone]);
    $orders = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متابعة وتعديل طلباتي - Snack Express</title>
    <style>
        :root { --primary: #d96528; --bg: #f8f9fa; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: #333; padding-bottom: 50px; }
        
        header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 15px 20px; }
        .nav-container { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 20px; font-weight: bold; color: var(--primary); text-decoration: none; }
        .btn-home { background: #f1f5f9; color: #333; text-decoration: none; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 13px; }

        .container { max-width: 800px; margin: 30px auto; padding: 0 15px; }
        
        .search-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; margin-bottom: 25px; }
        .search-card h2 { color: var(--primary); margin-bottom: 10px; font-size: 20px; }
        .search-form { display: flex; gap: 10px; max-width: 400px; margin: 15px auto 0 auto; }
        .search-form input { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .search-form button { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }

        .order-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .order-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; background: #fff3cd; color: #856404; }
        .status-badge.done { background: #d4edda; color: #155724; }

        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 14px; margin-bottom: 15px; }
        .items-box { background: #f8fafc; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; line-height: 1.6; }

        .actions-box { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; border-top: 1px dashed #eee; padding-top: 15px; }
        .btn-action { padding: 10px 16px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-cancel { background: #ffebee; color: #c62828; }
        .btn-edit { background: #e3f2fd; color: #1565c0; }

        .edit-form-box { display: none; background: #f8fafc; padding: 15px; border-radius: 10px; margin-top: 15px; border: 1px solid #e2e8f0; }
        .edit-form-box.show { display: block; }
        .form-group { margin-bottom: 10px; text-align: right; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: bold; }
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">🌮 Snack Express</a>
            <div>
                <?php if (isset($_SESSION['username'])): ?>
                    <span style="margin-left: 10px; font-size: 13px; font-weight: bold;">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="btn-home" style="background: #ffebee; color: #c62828;">خروج 🚪</a>
                <?php endif; ?>
                <a href="index.php" class="btn-home">🏠 الرئيسية</a>
            </div>
        </div>
    </header>

    <div class="container">

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="search-card">
            <h2>📦 متابعة وتعديل طلبياتي</h2>
            <p style="font-size: 13px; color: #666;">ادخل رقم الهاتف الذي استخدمته في الطلب لمشاهدة حالته أو تعديله/إلغائه:</p>
            <form method="GET" action="my_orders.php" class="search-form">
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($search_phone); ?>" placeholder="مثال: 06XXXXXXXX" required>
                <button type="submit">بحث 🔍</button>
            </form>
        </div>

        <?php if (empty($orders)): ?>
            <div class="order-card" style="text-align: center; color: #888;">
                لا توجد طلبيات مسجلة حالياً بهذا الحساب أو رقم الهاتف المكتوب. 🌮
            </div>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>طلبية رقم #<?php echo $ord['id']; ?></strong>
                            <div style="font-size: 11px; color: #888;"><?php echo $ord['created_at']; ?></div>
                        </div>
                        <span class="status-badge <?php echo $ord['status'] === 'تم التوصيل ✅' ? 'done' : ''; ?>">
                            <?php echo htmlspecialchars($ord['status']); ?>
                        </span>
                    </div>

                    <div class="details-grid">
                        <div>👤 <strong>الاسم:</strong> <?php echo htmlspecialchars($ord['customer_name']); ?></div>
                        <div>📞 <strong>الهاتف:</strong> <?php echo htmlspecialchars($ord['customer_phone']); ?></div>
                        <div>📍 <strong>العنوان:</strong> <?php echo htmlspecialchars($ord['customer_address']); ?></div>
                        <div>💳 <strong>الدفع:</strong> <?php echo htmlspecialchars($ord['payment_method']); ?></div>
                    </div>

                    <div class="items-box">
                        <strong>📦 الوجبات المطلوبة:</strong><br>
                        <?php echo nl2br(htmlspecialchars($ord['items_details'])); ?>
                        <div style="margin-top: 8px; font-weight: bold; color: var(--primary); font-size: 15px;">
                            المجموع: <?php echo number_format($ord['total_price'], 2); ?> DH
                        </div>
                    </div>

                    <?php if ($ord['status'] === 'قيد الانتظار ⏳'): ?>
                        <div class="actions-box">
                            <button type="button" class="btn-action btn-edit" onclick="toggleEditForm(<?php echo $ord['id']; ?>)">✏️ تعديل بيانات التوصيل</button>
                            
                            <form method="POST" action="my_orders.php" onsubmit="return confirm('هل أنت متأكد من إلغاء وحذف هذه الطلبية؟');" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                <button type="submit" name="cancel_order" class="btn-action btn-cancel">❌ إلغاء الطلبية</button>
                            </form>
                        </div>

                        <div class="edit-form-box" id="edit-box-<?php echo $ord['id']; ?>">
                            <h4 style="margin-bottom: 10px; font-size: 14px; color: #1565c0;">✏️ تعديل معلومات التوصيل:</h4>
                            <form method="POST" action="my_orders.php">
                                <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                <div class="form-group">
                                    <label>الاسم الكامل:</label>
                                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($ord['customer_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>رقم الهاتف:</label>
                                    <input type="tel" name="customer_phone" value="<?php echo htmlspecialchars($ord['customer_phone']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>عنوان التوصيل:</label>
                                    <input type="text" name="customer_address" value="<?php echo htmlspecialchars($ord['customer_address']); ?>" required>
                                </div>
                                <button type="submit" name="update_order" class="btn-action" style="background: #28a745; color: white; width: 100%; justify-content: center; margin-top: 5px;">حفظ التعديلات ✅</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script>
        function toggleEditForm(orderId) {
            let box = document.getElementById('edit-box-' + orderId);
            box.classList.toggle('show');
        }
    </script>
</body>
</html>