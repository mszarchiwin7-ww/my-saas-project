<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Railway ရော Local မှာပါ ချိတ်ဆက်နိုင်မည့် Database Connection
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ? intval(getenv('MYSQLPORT')) : 3307;

$database_url = getenv('MYSQL_URL');
if ($database_url && strpos($database_url, '${') === false) {
    $db = parse_url($database_url);
    if (isset($db["host"])) $host = $db["host"];
    if (isset($db["user"])) $user = $db["user"];
    if (isset($db["pass"])) $pass = $db["pass"];
    if (isset($db["path"])) $dbname = ltrim($db["path"], "/");
    if (isset($db["port"])) $port = $db["port"];
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$current_table = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - U Myanmarfood</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center text-danger mb-4">🛒 မှာယူမည့်စာရင်း (စားပွဲခုံ - <?php echo $current_table; ?>)</h2>
        
        <div id="cart-items-container">
            <!-- JavaScript ဖြင့် Cart ထဲက ပစ္စည်းများကို ဤနေရာတွင် ပြမည် -->
        </div>

        <div id="checkout-section" style="display:none;" class="mt-4 text-center">
            <button onclick="submitOrder()" class="btn btn-success btn-lg w-100">✅ အော်ဒါအတည်ပြုမည်</button>
        </div>

        <div class="mt-3 text-center">
            <a href="index.php?table=<?php echo urlencode($current_table); ?>" class="btn btn-secondary">⬅️ မီနူးသို့ ပြန်သွားမည်</a>
        </div>
    </div>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
let container = document.getElementById('cart-items-container');
let checkoutSection = document.getElementById('checkout-section');

if (cart.length === 0) {
    container.innerHTML = "<p class='text-center text-muted'>ခြင်းတောင်းထဲတွင် ဘာမှမရှိသေးပါ။</p>";
} else {
    let html = "<ul class='list-group mb-3'>";
    let total = 0;
    cart.forEach((item, index) => {
        let subtotal = item.price * item.quantity;
        total += subtotal;
        html += `<li class='list-group-item d-flex justify-content-between align-items-center'>
                    <div>
                        <h6 class='my-0'>${item.name}</h6>
                        <small class='text-muted'>ဈေးနှုန်း: ${item.price} MMK x ${item.quantity}</small>
                    </div>
                    <span class='text-danger fw-bold'>${subtotal} MMK</span>
                    <button class='btn btn-sm btn-outline-danger' onclick='removeItem(${index})'>ဖျက်ရန်</button>
                 </li>`;
    });
    html += `<li class='list-group-item d-flex justify-content-between bg-light'>
                <span class='fw-bold'>စုစုပေါင်း (Total)</span>
                <strong class='text-success'>${total} MMK</strong>
             </li>`;
    html += "</ul>";
    container.innerHTML = html;
    checkoutSection.style.display = "block";
}

function removeItem(index) {
    cart.splice(index, 1);
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    location.reload();
}

function submitOrder() {
    let table = "<?php echo $current_table; ?>";
    
    fetch('process_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_no: table, cart: cart })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            alert('အော်ဒါတင်ခြင်း အောင်မြင်ပါသည်။ ကျေးဇူးတင်ပါတယ်!');
            localStorage.removeItem('restaurant_cart');
            window.location.href = 'index.php?table=' + table;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('အော်ဒါတင်ရာတွင် အမှားအယွင်း ရှိis နေပါသည်။');
    });
}
</script>
</body>
</html>