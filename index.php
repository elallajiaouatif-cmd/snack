<?php
session_start();
require_once 'db.php';

// 1. جلب التصنيفات والوجبات من قاعدة البيانات
$categories = [];$products = [];

try {
    $categories =$pdo->query("SELECT * FROM categories")->fetchAll();
    $products =$pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
} catch (PDOException $e) {
    // في حالة عدم وجود بيانات يتم تعيين مصفوفات فارغة
}

// 2. رقم الواتساب الخاص بالمحل
$whatsapp_number = "212600000000"; 
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snack Express - أشهى المأكولات السريعة</title>
    <style>
        :root {
            --primary: #d96528;
            --primary-hover: #b84f18;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #2c3e50;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding-bottom: 80px; }

        /* Header & Navigation Bar */
        header { background: var(--card-bg); box-shadow: 0 2px 10px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 100; }
        .nav-container { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; flex-wrap: wrap; gap: 10px; }
        .logo { font-size: 22px; font-weight: 800; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        
        .nav-links { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .nav-link-btn { background: #f1f5f9; color: #333; text-decoration: none; padding: 8px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .nav-link-btn:hover { background: #e2e8f0; color: var(--primary); }
        .nav-link-btn.admin-link { background: #fff3ec; color: var(--primary); border: 1px solid var(--primary); }
        .nav-link-btn.orders-link { background: #e3f2fd; color: #0d47a1; }
        
        .cart-icon-btn { background: var(--primary); border: none; color: white; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .cart-icon-btn:hover { background: var(--primary-hover); }
        .cart-badge { background: white; color: var(--primary); border-radius: 50%; padding: 2px 7px; font-size: 11px; font-weight: 800; }

        /* Hero Banner */
        .hero { background: linear-gradient(135deg, #d96528 0%, #ff8c42 100%); color: white; text-align: center; padding: 35px 20px; border-radius: 0 0 24px 24px; }
        .hero h1 { font-size: 26px; margin-bottom: 10px; }
        .hero p { font-size: 14px; opacity: 0.95; max-width: 600px; margin: 0 auto 20px auto; line-height: 1.5; }
        
        .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; max-width: 900px; margin: 0 auto; }
        .service-card { background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3); padding: 12px 10px; border-radius: 12px; text-align: center; font-size: 13px; font-weight: 600; color: white; cursor: pointer; transition: 0.2s; }
        .service-card:hover { background: rgba(255,255,255,0.35); transform: translateY(-2px); }

        /* Container */
        .container { max-width: 1100px; margin: 25px auto; padding: 0 15px; }

        /* Categories Filter */
        .categories-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 25px; scrollbar-width: none; }
        .cat-btn { background: white; border: 1px solid #ddd; padding: 10px 20px; border-radius: 25px; cursor: pointer; white-space: nowrap; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .cat-btn.active, .cat-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }

        /* Products Grid */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; }
        .product-card { background: var(--card-bg); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .product-card:hover { transform: translateY(-4px); }
        .product-img { width: 100%; height: 160px; object-fit: cover; }
        .product-info { padding: 15px; }
        .product-title { font-size: 16px; font-weight: bold; margin-bottom: 6px; color: #222; }
        .product-meta { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #777; margin-bottom: 12px; }
        .product-price { font-size: 18px; font-weight: 800; color: var(--primary); }
        .add-btn { background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 14px; }
        .add-btn:hover { background: var(--primary-hover); }

        /* Cart Modal */
        .cart-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .cart-modal.active { display: flex; }
        .cart-content { background: white; width: 92%; max-width: 480px; max-height: 85vh; border-radius: 20px; padding: 20px; overflow-y: auto; position: relative; }
        .close-btn { position: absolute; top: 15px; left: 15px; background: #eee; border: none; width: 32px; height: 32px; border-radius: 50%; font-weight: bold; cursor: pointer; font-size: 16px; }

        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; }
        .qty-btns { display: flex; align-items: center; gap: 8px; }
        .qty-btn { background: #eee; border: none; width: 28px; height: 28px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }

        .checkout-form { margin-top: 15px; border-top: 2px dashed #eee; padding-top: 15px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; box-sizing: border-box; }

        .payment-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; }
        .pay-btn { padding: 12px; border-radius: 10px; border: none; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .pay-whatsapp { background: #25D366; color: white; }
        .pay-card { background: #007bff; color: white; }

        #card-form-box { display: none; background: #f8fafc; padding: 15px; border-radius: 10px; margin-top: 12px; border: 1px solid #e2e8f0; }
        #card-form-box.show { display: block !important; }
    </style>
</head>
<body>

    <!-- شريط التنقل العلوي -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">🌮 Snack Express</a>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link-btn">🏠 الرئيسية</a>
                <a href="my_orders.php" class="nav-link-btn orders-link">📦 طلباتي</a>
                
                <?php if (isset($_SESSION['user_id']) &&$_SESSION['user_id'] > 0): ?>
                    <?php if (isset($_SESSION['role']) &&$_SESSION['role'] === 'admin'): ?>
                        <a href="admin.php" class="nav-link-btn admin-link">🛠️ الأدمن</a>
                    <?php endif; ?>
                    <span style="font-size: 13px; font-weight: bold; color: var(--primary); background: #fff3ec; padding: 6px 12px; border-radius: 15px;">
                        👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="logout.php" class="nav-link-btn" style="background: #ffebee; color: #c62828;">🚪 خروج</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link-btn">🔐 دخول</a>
                    <a href="register.php" class="nav-link-btn">📝 حساب جديد</a>
                    <a href="admin.php" class="nav-link-btn admin-link">🛠️ الأدمن</a>
                <?php endif; ?>
                
                <button type="button" class="cart-icon-btn" id="openCartBtn">
                    🛒 السلة
                    <span class="cart-badge" id="cart-count">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- الواجهة الرئيسية -->
    <section class="hero">
        <h1>أسرع توصيل لألذ الوجبات 🚀</h1>
        <p>محل Snack Express يوفر لكم أجود أنواع الطاكوس، الباستيشو، والخبزة بالطريقة الأصلية والمكونات الطازجة يومياً.</p>
        
        <div class="services-grid">
            <div class="service-card" onclick="scrollToMenu()">⚡ توصيل سريع</div>
            <div class="service-card" onclick="scrollToMenu()">🌮 طاكوس و باستيشو</div>
            <div class="service-card" onclick="openCart()">💬 طلب مباشر عبر واتساب</div>
            <div class="service-card" onclick="openCardPayment()">💳 دفع بالبطاقة البنكية</div>
        </div>
    </section>

    <div class="container" id="menuSection">
        <!-- التصنيفات -->
        <div class="categories-scroll">
            <button type="button" class="cat-btn active" data-cat="all">الكل 🍽️</button>
            <?php foreach ($categories as$cat): ?>
                <button type="button" class="cat-btn" data-cat="cat-<?php echo $cat['id']; ?>">
                    <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- الوجبات -->
        <div class="products-grid">
            <?php foreach ($products as$p): ?>
                <div class="product-card product-item cat-<?php echo $p['category_id']; ?>">
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <div class="product-info">
                        <div class="product-title"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="product-meta">
                            <span>⏱️ <?php echo htmlspecialchars($p['delivery_time']); ?></span>
                            <span>⭐ <?php echo htmlspecialchars($p['rating']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <div class="product-price"><?php echo number_format($p['price'], 2); ?> DH</div>
                            <button type="button" class="add-btn add-to-cart-action" 
                                    data-id="<?php echo $p['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                    data-price="<?php echo $p['price']; ?>">
                                إضافة ➕
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- نافذة السلة -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-content">
            <button type="button" class="close-btn" id="closeCartBtn">✕</button>
            <h3 style="margin-bottom: 15px; color: var(--primary);">🛒 سلة الطلبات</h3>

            <div id="cart-items-container">
                <p style="text-align: center; color: #888; padding: 20px;">السلة فارغة حالياً 🌮</p>
            </div>

            <div style="margin-top: 15px; text-align: left; font-size: 18px; font-weight: bold; color: var(--primary);">
                المجموع الكلي: <span id="cart-total">0.00 DH</span>
            </div>

            <!-- نموذج التوصيل والدفع -->
            <div class="checkout-form" id="checkoutSection" style="display: none;">
                <h4 style="margin-bottom: 10px;">بيانات التوصيل:</h4>
                <div class="form-group">
                    <label>الاسم الكامل:</label>
                    <input type="text" id="cust-name" placeholder="مثال: محمد علي">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف:</label>
                    <input type="tel" id="cust-phone" placeholder="06XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>عنوان التوصيل:</label>
                    <textarea id="cust-address" rows="2" placeholder="المدينة، الحي، رقم المنزل..."></textarea>
                </div>

                <label style="font-size: 13px; font-weight: bold;">إتمام الطلب بواسطة:</label>
                <div class="payment-options">
                    <button type="button" class="pay-btn pay-whatsapp" id="payWhatsappBtn">💬 عبر الواتساب</button>
                    <button type="button" class="pay-btn pay-card" id="toggleCardBtn">💳 بالبطاقة البنكية</button>
                </div>

                <!-- نموذج الدفع بالبطاقة البنكية -->
                <div id="card-form-box">
                    <h5 style="margin-bottom: 10px; color: #333;">ادخل بيانات البطاقة البنكية:</h5>
                    <div class="form-group">
                        <input type="text" id="card-num" placeholder="رقم البطاقة (16 رقم)" maxlength="16">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="card-exp" placeholder="MM/YY" maxlength="5">
                        <input type="password" id="card-cvc" placeholder="CVC" maxlength="3">
                    </div>
                    <button type="button" class="add-btn" id="payCardSubmitBtn" style="width: 100%; margin-top: 10px; background: #28a745;">تأكيد الدفع والطلب الآن ✅</button>
                </div>
            </div>
        </div>
    </div>

    <!-- برمجة JavaScript -->
    <script>
        let cart = [];
        const waNumber = "<?php echo $whatsapp_number; ?>";

        function scrollToMenu() {
            document.getElementById('menuSection').scrollIntoView({ behavior: 'smooth' });
        }

        function openCardPayment() {
            openCart();
            let box = document.getElementById('card-form-box');
            box.classList.add('show');
        }

        function openCart() {
            document.getElementById('cartModal').classList.add('active');
            renderCartUI();
        }

        function closeCart() {
            document.getElementById('cartModal').classList.remove('active');
        }

        document.getElementById('openCartBtn').addEventListener('click', openCart);
        document.getElementById('closeCartBtn').addEventListener('click', closeCart);

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-to-cart-action');
            if (btn) {
                const id = parseInt(btn.getAttribute('data-id'));
                const name = btn.getAttribute('data-name');
                const price = parseFloat(btn.getAttribute('data-price'));

                let existing = cart.find(item => item.id === id);
                if (existing) {
                    existing.qty++;
                } else {
                    cart.push({ id, name, price, qty: 1 });
                }

                renderCartUI();
                openCart();
            }
        });

        function changeQty(id, delta) {
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            renderCartUI();
        }

        function renderCartUI() {
            let count = cart.reduce((sum, i) => sum + i.qty, 0);
            let total = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);

            document.getElementById('cart-count').innerText = count;
            document.getElementById('cart-total').innerText = total.toFixed(2) + ' DH';

            let container = document.getElementById('cart-items-container');
            let checkoutSec = document.getElementById('checkoutSection');

            if (cart.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">السلة فارغة حالياً 🌮</p>';
                checkoutSec.style.display = 'none';
            } else {
                let html = '';
                cart.forEach(item => {
                    html += `
                        <div class="cart-item">
                            <div>
                                <strong>${item.name}</strong>
                                <div style="font-size: 12px; color: #666;">${item.price.toFixed(2)} DH</div>
                            </div>
                            <div class="qty-btns">
                                <button type="button" class="qty-btn" onclick="changeQty(${item.id}, -1)">-</button>
                                <span>${item.qty}</span>
                                <button type="button" class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                checkoutSec.style.display = 'block';
            }
        }

        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                let cat = this.getAttribute('data-cat');
                document.querySelectorAll('.product-item').forEach(item => {
                    if (cat === 'all' || item.classList.contains(cat)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        document.getElementById('toggleCardBtn').addEventListener('click', function(e) {
            e.preventDefault();
            let box = document.getElementById('card-form-box');
            box.classList.toggle('show');
        });

        document.getElementById('payWhatsappBtn').addEventListener('click', function(e) {
            e.preventDefault();
            let name = document.getElementById('cust-name').value.trim();
            let phone = document.getElementById('cust-phone').value.trim();
            let address = document.getElementById('cust-address').value.trim();

            if (!name || !phone || !address) {
                alert('يرجى ملء جميع بيانات التوصيل أولاً!');
                return;
            }

            let total = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
            let itemsStr = cart.map(i => `${i.name} (${i.qty})`).join(', ');

            fetch('save_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    phone: phone,
                    address: address,
                    total: total,
                    payment_method: 'واتساب 💬',
                    items: itemsStr
                })
            });

            let text = `*طلب جديد من Snack Express 🌮*\n\n`;
            text += `👤 *الاسم:* ${name}\n`;
            text += `📞 *الهاتف:* ${phone}\n`;
            text += `📍 *العنوان:* ${address}\n\n`;
            text += `📦 *تفاصيل الوجبات:*\n`;
            cart.forEach(i => {
                text += `- ${i.name} (${i.qty}) : ${(i.price * i.qty).toFixed(2)} DH\n`;
            });
            text += `\n💰 *المجموع الكلي:* ${total.toFixed(2)} DH`;

            window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(text)}`, '_blank');
        });

        document.getElementById('payCardSubmitBtn').addEventListener('click', function(e) {
            e.preventDefault();
            let name = document.getElementById('cust-name').value.trim();
            let phone = document.getElementById('cust-phone').value.trim();
            let address = document.getElementById('cust-address').value.trim();

            let cardNum = document.getElementById('card-num').value.trim();
            let cardExp = document.getElementById('card-exp').value.trim();
            let cardCvc = document.getElementById('card-cvc').value.trim();

            if (!name || !phone || !address) {
                alert('يرجى ملء جميع بيانات التوصيل أولاً!');
                return;
            }

            if (!cardNum || !cardExp || !cardCvc || cardNum.length < 16) {
                alert('يرجى التأكد من إدخال كافة بيانات البطاقة البنكية (16 رقم)!');
                return;
            }

            let total = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
            let itemsStr = cart.map(i => `${i.name} (${i.qty})`).join(', ');

            fetch('save_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    phone: phone,
                    address: address,
                    total: total,
                    payment_method: 'بطاقة بنكية 💳',
                    items: itemsStr
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert('✅ تم قبول الدفع وتأكيد طلبك بنجاح! سيتم تجهيز طلبك فوراً.');
                    cart = [];
                    renderCartUI();
                    closeCart();
                } else {
                    alert('حدث خطأ أثناء إرسال الطلب، يرجى المحاولة لاحقاً.');
                }
            });
        });
    </script>
</body>
</html>