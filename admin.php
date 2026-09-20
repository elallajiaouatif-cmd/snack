<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. تغيير حالة الطلب (تغيير إلى "تم التوصيل ✅")
if (isset($_GET['complete_order'])) {
    $order_id = intval($_GET['complete_order']);
    $stmt = $pdo->prepare("UPDATE orders SET status = 'تم التوصيل ✅' WHERE id = ?");
    $stmt->execute([$order_id]);
    header('Location: admin.php');
    exit;
}

// 2. حذف طلبية
if (isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    header('Location: admin.php');
    exit;
}

// 3. إضافة أو تعديل وجبة
$edit_mode = false;
$edit_product = [
    'id' => '', 'name' => '', 'price' => '', 'category_name' => '', 'image' => '', 'delivery_time' => 'min 15-20', 'rating' => '4.5 ⭐'
];

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$edit_id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $edit_mode = true;
        $edit_product = $fetched;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category_input = trim($_POST['category_name']);
    $delivery_time = trim($_POST['delivery_time']);
    $rating = trim($_POST['rating']);

    if (!empty($category_input) && !empty($name) && $price > 0) {
        $stmt_cat = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
        $stmt_cat->execute([$category_input]);
        $existing_cat = $stmt_cat->fetch();

        if ($existing_cat) {
            $category_id = $existing_cat['id'];
        } else {
            $stmt_ins_cat = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, '🍽️')");
            $stmt_ins_cat->execute([$category_input]);
            $category_id = $pdo->lastInsertId();
        }

        $image_path = isset($_POST['old_image']) ? $_POST['old_image'] : '';

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image_file']['tmp_name'];
            $file_name = $_FILES['image_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    if (!empty($image_path) && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $target_path;
                }
            }
        }

        if ($product_id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, category_id = ?, image = ?, delivery_time = ?, rating = ? WHERE id = ?");
            $stmt->execute([$name, $price, $category_id, $image_path, $delivery_time, $rating, $product_id]);
            $success = 'تم تعديل الوجبة بنجاح! ✏️';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, category_id, image, delivery_time, rating) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $category_id, $image_path, $delivery_time, $rating]);
            $success = 'تم إضافة الوجبة بنجاح! ✅';
        }
    }
}

// 4. حذف وجبة
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    header('Location: admin.php');
    exit;
}

// جلب الوجبات والطلبيات
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - Snack Express</title>
    <style>
        :root { --primary: #d96528; --bg: #f4f6f8; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, sans-serif; }
        body { background: var(--bg); padding: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .header h1 { color: var(--primary); font-size: 20px; }
        .btn-logout { background: #dc3545; color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-weight: bold; font-size: 13px; }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { margin-bottom: 15px; font-size: 16px; color: var(--primary); }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #555; }
        .prod-img { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: #fff3cd; color: #856404; }
        .status-badge.done { background: #d4edda; color: #155724; }
        .action-link { text-decoration: none; font-weight: bold; margin-left: 8px; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🛠️ لوحة تحكم Snack Express</h1>
        <div>
            <a href="index.php" style="margin-left: 10px; text-decoration: none; font-weight: bold; color: var(--primary);">الموقع 🌐</a>
            <a href="logout.php" class="btn-logout">خروج 🚪</a>
        </div>
    </div>

    <!-- قسم الطلبيات الواردة 📦 -->
    <div class="card">
        <h2>📦 الطلبيات الواردة من الزبناء</h2>
        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: #888; padding: 15px;">لا توجد طلبيات حالياً.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الزبون</th>
                            <th>الهاتف</th>
                            <th>العنوان</th>
                            <th>تفاصيل الوجبات</th>
                            <th>المجموع</th>
                            <th>طريقة الدفع</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td><strong>#<?php echo $ord['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                <td><a href="tel:<?php echo htmlspecialchars($ord['customer_phone']); ?>"><?php echo htmlspecialchars($ord['customer_phone']); ?></a></td>
                                <td><?php echo htmlspecialchars($ord['customer_address']); ?></td>
                                <td><small><?php echo nl2br(htmlspecialchars($ord['items_details'])); ?></small></td>
                                <td><strong><?php echo number_format($ord['total_price'], 2); ?> DH</strong></td>
                                <td><?php echo htmlspecialchars($ord['payment_method']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $ord['status'] === 'تم التوصيل ✅' ? 'done' : ''; ?>">
                                        <?php echo htmlspecialchars($ord['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ord['status'] !== 'تم التوصيل ✅'): ?>
                                        <a href="admin.php?complete_order=<?php echo $ord['id']; ?>" class="action-link" style="color: #28a745;">تأكيد التوصيل ✅</a>
                                    <?php endif; ?>
                                    <a href="admin.php?delete_order=<?php echo $ord['id']; ?>" class="action-link" style="color: #dc3545;" onclick="return confirm('حذف الطلبية؟')">حذف ❌</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- نموذج إضافة / تعديل وجبة -->
    <div class="card">
        <h2><?php echo $edit_mode ? '✏️ تعديل الوجبة' : '➕ إضافة وجبة جديدة'; ?></h2>
        <form method="POST" action="admin.php" enctype="multipart/form-data">
            <input type="hidden" name="save_product" value="1">
            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
            <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($edit_product['image']); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>اسم الوجبة:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>الثمن (DH):</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($edit_product['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label>التصنيف:</label>
                    <input type="text" name="category_name" value="<?php echo htmlspecialchars($edit_product['category_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>الصورة 📁:</label>
                    <input type="file" name="image_file" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?>>
                </div>
                <div class="form-group">
                    <label>وقت التحضير:</label>
                    <input type="text" name="delivery_time" value="<?php echo htmlspecialchars($edit_product['delivery_time']); ?>" required>
                </div>
                <div class="form-group">
                    <label>التقييم:</label>
                    <input type="text" name="rating" value="<?php echo htmlspecialchars($edit_product['rating']); ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit"><?php echo $edit_mode ? 'تحديث الوجبة ✏️' : 'حفظ الوجبة 💾'; ?></button>
        </form>
    </div>

    <!-- قائمة الوجبات الحالية -->
    <div class="card">
        <h2>📋 قائمة الوجبات الحالية</h2>
        <table>
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>الاسم</th>
                    <th>التصنيف</th>
                    <th>الثمن</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($p['image']); ?>" class="prod-img" alt=""></td>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                        <td><strong><?php echo number_format($p['price'], 2); ?> DH</strong></td>
                        <td>
                            <a href="admin.php?edit_id=<?php echo $p['id']; ?>" class="action-link" style="color: #28a745;">تعديل ✏️</a>
                            <a href="admin.php?delete_id=<?php echo $p['id']; ?>" class="action-link" style="color: #dc3545;" onclick="return confirm('حذف الوجبة؟')">حذف ❌</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>