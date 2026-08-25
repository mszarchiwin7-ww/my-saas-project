<?php
// 1. Session စတင်ခြင်း
session_start();

// 2. ဒေတာဘေ့စ် ချိတ်ဆက်ခြင်း
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($host, $user, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$raw_table = isset($_GET['table']) ? $_GET['table'] : '1';
$current_table = trim(str_ireplace('Table', '', $raw_table));

$order_success = false;

// 🌟 [အဓိက Backend Logic] JavaScript ကနေ AJAX နဲ့ ပို့လိုက်တဲ့ အော်ဒါတွေကို လက်ခံပြီး Database ထဲ ထည့်ခြင်း
if (isset($_POST['action']) && $_POST['action'] == 'place_order_ajax') {
    $cart_items = json_decode($_POST['cart_data'], true);
    $table_num = $_POST['table_number'];
    
    // 🌟 [UPDATE] AJAX က ပို့လိုက်သော ဝယ်သူ၏ Comment အား ဖတ်ယူခြင်း
    $order_comment = isset($_POST['order_comment']) ? trim($_POST['order_comment']) : "";
    $status = 'Pending';

    if (!empty($cart_items) && !$conn->connect_error) {
        // 🌟 [UPDATE] INSERT ထဲတွင် order_comment ကိုပါ သိမ်းဆည်းရန် ထည့်သွင်းခြင်း
        $stmt = $conn->prepare("INSERT INTO customer_orders (table_number, item_name, price, order_comment, status) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($cart_items as $item) {
            // အရေအတွက်အလိုက် ဒေတာဘေ့စ်ထဲ loop ပတ်ထည့်ခြင်း
            for ($i = 0; $i < $item['quantity']; $i++) {
                // "ssdss" -> table_num(string), name(string), price(double), order_comment(string), status(string)
                $stmt->bind_param("ssdss", $table_num, $item['name'], $item['price'], $order_comment, $status);
                $stmt->execute();
            }
        }
        $stmt->close();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Taste of Myanmar 🛒</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Pyidaungsu', sans-serif; }
        .cart-card { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .checkout-btn { background: linear-gradient(135deg, #ff9f43, #ff6b6b); color: white; border: none; border-radius: 12px; font-weight: bold; padding: 12px; }
        .checkout-btn:disabled { background: #cbd5e0 !important; }
    </style>
</head>
<body>

<div class="container my-5" style="max-width: 600px;">
    <div class="d-flex align-items-center mb-4">
        <a href="index.php?table=<?php echo urlencode($current_table); ?>" class="btn btn-outline-secondary btn-sm me-3" style="border-radius: 10px;">
            ⬅️ မီနူးသို့ ပြန်သွားရန်
        </a>
        <h4 class="fw-bold mb-0">🛒 သင်ရွေးချယ်ထားသော အော်ဒါများ</h4>
    </div>

    <div class="alert alert-warning text-center fw-bold mb-4" style="border-radius: 12px;">
        🪑 စားပွဲခုံနံပါတ်: <span class="text-danger fs-5"><?php echo htmlspecialchars($current_table); ?></span>
    </div>

    <div class="card p-3 cart-card mb-4">
        <div id="cart-items-container">
            <div class="text-center text-muted py-4">ခြင်းတောင်းထဲတွင် မည်သည့်ဟင်းလျာမျှ မရှိသေးပါဗျာ။</div>
        </div>
        
        <hr>
        <div class="d-flex justify-content-between align-items-center fw-bold fs-5 px-2">
            <span>စုစုပေါင်းကျသင့်ငွေ:</span>
            <span class="text-danger"><span id="total-price-display">0</span> MMK</span>
        </div>
    </div>

    <div class="mb-4 text-start bg-white p-3 rounded-3 shadow-sm border border-light-subtle">
        <label class="form-label fw-bold text-dark">📝 စိတ်ကြိုက်တောင်းဆိုချက်မှတ်ချက် (Comment)</label>
        <textarea id="order-comment-input" class="form-control" rows="2" placeholder="ဥပမာ - အစပ်လျှော့ပါ၊ အချိုမှုန့်မထည့်ပါနဲ့၊ အကြွပ်ကြော်ပေးပါ..."></textarea>
    </div>

    <button type="button" id="final-checkout-btn" class="btn checkout-btn w-100 fs-5" onclick="submitOrderViaAJAX()" disabled>
        🚀 စားပွဲခုံကနေ အော်ဒါတင်မည်
    </button>
</div>

<script>
// LocalStorage မှ ကတ်ဒေတာကို ယူခြင်း
let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];

function renderCart() {
    const container = document.getElementById('cart-items-container');
    const totalDisplay = document.getElementById('total-price-display');
    const checkoutBtn = document.getElementById('final-checkout-btn');
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">ခြင်းတောင်းထဲတွင် မည်သည့်ဟင်းလျာမျှ မရှိသေးပါဗျာ။</div>';
        totalDisplay.innerText = '0';
        checkoutBtn.disabled = true;
        return;
    }

    checkoutBtn.disabled = false;
    let html = '';
    let grandTotal = 0;

    cart.forEach((item, index) => {
        let itemTotal = item.price * item.quantity;
        grandTotal += itemTotal;
        html += `
            <div class="d-flex justify-content-between align-items-center py-3 ${index > 0 ? 'border-top' : ''}">
                <div>
                    <h6 class="fw-bold mb-1">${item.name}</h6>
                    <small class="text-muted">${new Intl.NumberFormat().format(item.price)} MMK</small>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-light border px-2 py-1" onclick="changeQty('${item.id}', -1)">-</button>
                    <span class="mx-3 fw-bold">${item.quantity}</span>
                    <button class="btn btn-sm btn-light border px-2 py-1" onclick="changeQty('${item.id}', 1)">+</button>
                    <span class="ms-4 fw-bold text-dark" style="min-width: 80px; text-align: right;">${new Intl.NumberFormat().format(itemTotal)}</span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    totalDisplay.innerText = new Intl.NumberFormat().format(grandTotal);
}

function changeQty(id, amount) {
    let item = cart.find(i => i.id === id);
    if (item) {
        item.quantity += amount;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        localStorage.setItem('restaurant_cart', JSON.stringify(cart));
        renderCart();
    }
}

// 🚀 [AJAX Function] ခလုတ်နှိပ်လိုက်လျှင် နောက်ကွယ်မှ Database (`customer_orders`) ထဲသို့ တိုက်ရိုက်သိမ်းဆည်းခြင်း
function submitOrderViaAJAX() {
    const checkoutBtn = document.getElementById('final-checkout-btn');
    // 🌟 စာသားရိုက်ကွင်းထဲမှ comment ကို လှမ်းဖတ်ခြင်း
    const commentInput = document.getElementById('order-comment-input').value;

    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = "⏳ အော်ဒါပို့နေပါသည်... ခေတ္တစောင့်ပါ...";

    const formData = new FormData();
    formData.append('action', 'place_order_ajax');
    formData.append('cart_data', JSON.stringify(cart));
    formData.append('table_number', 'Table ' + '<?php echo $current_table; ?>');
    
    // 🌟 AJAX Payload ထဲသို့ ဝယ်သူမှတ်ချက်ပါ တွဲထည့်ပေးလိုက်ခြင်း
    formData.append('order_comment', commentInput);

    fetch('shopping_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('🎉 အော်ဒါ တင်ခြင်း အောင်မြင်ပါသည်ဗျာ! Dashboard သို့ ပေးပို့ပြီးပါပြီ။');
            localStorage.removeItem('restaurant_cart'); // Cart ကို ရှင်းလင်းမည်
            window.location.href = 'index.php?table=' + encodeURIComponent('<?php echo $current_table; ?>');
        } else {
            alert('❌ တစ်စုံတစ်ခု မှားယွင်းနေပါသည်။ ပြန်လည် ကြိုးစားပေးပါ။');
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = "🚀 စားပွဲခုံကနေ အော်ဒါတင်မည်";
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network ချက်ဆက်မှု အခက်အခဲ ရှိနေပါသည်။');
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = "🚀 စားပွဲခုံကနေ အော်ဒါတင်မည်";
    });
}

// စတင်ပွင့်ချိန်တွင် ကတ်ကို ပြသခြင်း
renderCart();
</script>
<script>
function saveToLocalStorage(id, name, price) {
    let quantity = document.getElementById('qty-' + id).value;
    let comment = document.getElementById('comment-' + id).value;
    
    let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
    
    // ကတ်ထဲသို့ ထည့်ခြင်း
    cart.push({ id: id, name: name, price: price, quantity: parseInt(quantity), comment: comment });
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    
    alert('ခြင်းတောင်းထဲသို့ ထည့်ပြီးပါပြီ!');
    
    // Controls တွေကို ပြန်ပိတ်မယ်
    document.getElementById('controls-' + id).style.display = 'none';
    document.getElementById('btn-' + id).style.display = 'block';
}

function showControls(id) {
    document.getElementById('btn-' + id).style.display = 'none';
    document.getElementById('controls-' + id).style.display = 'block';
}
// (အရင်ပေးထားတဲ့ increment/decrement function များကိုလည်း ထည့်ထားပါ)
</script>
</body>
</html>