<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Database ချိတ်ဆက်မှု အောင်မြင်ရင် အောက်ပါတို့ကို ဆက်လုပ်ပါ
session_start();
$conn->set_charset("utf8mb4");

// 1. Get Table Number
$current_table = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';

$query = "SELECT * FROM restaurant_menu";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U Myanmarfood</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-bottom: 100px; font-family: sans-serif; }
        .hero { background: #d32f2f; color: white; padding: 30px; text-align: center; }
        .table-info { background: #fff; padding: 10px; text-align: center; font-weight: bold; font-size: 1.2rem; border-bottom: 2px solid #eee; }
        .food-card { background: white; border-radius: 20px; padding: 15px; margin: 15px auto; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .food-img { width: 100px; height: 100px; object-fit: cover; border-radius: 15px; float: left; margin-right: 15px; border: 1px solid #ddd; }
        .btn-add { background: #ff5e57; color: white; border: none; width: 100%; border-radius: 10px; padding: 10px; font-weight: bold; }
        .order-controls { display: none; margin-top: 10px; }
        #floating-cart-bar { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 500px; background: #ff8c52; color: white; display: none; justify-content: space-between; align-items: center; padding: 15px 20px; border-radius: 30px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div class="hero">
    <h1>U Myanmarfood 🍲</h1>
</div>

<!-- Display Current Table -->
<div class="table-info">
    စားပွဲခုံ - <?php echo $current_table; ?>
</div>

<div class="container mt-4">
    <?php while ($row = $result->fetch_assoc()): 
        // Improved image logic: Check if file exists in 'uploads/'
        $img_path = !empty($row['item_image']) ? 'uploads/' . $row['item_image'] : '';
        $display_img = (file_exists($img_path)) ? $img_path : 'https://via.placeholder.com/100?text=No+Image';
    ?>
    <div class="food-card">
        <img src="<?php echo $display_img; ?>" class="food-img" onerror="this.src='https://via.placeholder.com/100?text=Error'">
        <h5><?php echo htmlspecialchars($row['item_name']); ?></h5>
        <p class="text-danger fw-bold"><?php echo number_format($row['price']); ?> MMK</p>
        
        <button class="btn-add" id="btn-<?php echo $row['id']; ?>" onclick="showControls('<?php echo $row['id']; ?>')">
            ➕ ခြင်းတောင်းထဲထည့်မည်
        </button>

        <div id="controls-<?php echo $row['id']; ?>" class="order-controls">
            <div class="d-flex align-items-center mb-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="changeQty('<?php echo $row['id']; ?>', -1)">-</button>
                <input type="text" id="qty-<?php echo $row['id']; ?>" value="1" class="form-control mx-2 text-center" style="width: 50px;">
                <button class="btn btn-sm btn-outline-secondary" onclick="changeQty('<?php echo $row['id']; ?>', 1)">+</button>
            </div>
            <button class="btn btn-success w-100" onclick="addToCart('<?php echo $row['id']; ?>', '<?php echo addslashes($row['item_name']); ?>', '<?php echo $row['price']; ?>')">
                အတည်ပြုမည်
            </button>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div id="floating-cart-bar">
    <div><span id="cart-count" class="badge bg-white text-danger">0</span> ပွဲ မှာထားသည်</div>
    <!-- 🌟 URL ထဲမှာ current_table ပါသွားအောင် ?table=... ထည့်ပေးခြင်း -->
    <a href="shopping_cart.php?table=<?php echo urlencode($current_table); ?>" class="text-white fw-bold" style="text-decoration:none">ခြင်းတောင်းကြည့်မည် ></a>
</div>  

<script>
// 🌟 ၁။ URL ထဲက table နံပါတ်ကို ဖမ်းယူပြီး LocalStorage ထဲ သိမ်းမည့် အပိုင်း
const urlParams = new URLSearchParams(window.location.search);
const urlTable = urlParams.get('table');

if (urlTable) {
    // URL ထဲမှာ table ပါလာရင် အဲဒီတန်ဖိုးအသစ်ကို သိမ်းမယ်
    localStorage.setItem('current_table', urlTable);
}

function showControls(id) {
    document.getElementById('btn-' + id).style.display = 'none';
    document.getElementById('controls-' + id).style.display = 'block';
}

function changeQty(id, val) {
    let input = document.getElementById('qty-' + id);
    let newVal = parseInt(input.value) + val;
    if(newVal >= 1) input.value = newVal;
}

function addToCart(id, name, price) {
    let qty = document.getElementById('qty-' + id).value;
    let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
    cart.push({ id, name, price, quantity: parseInt(qty) });
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    alert('ခြင်းတောင်းထဲသို့ ထည့်ပြီးပါပြီ!');
    location.reload();
}

// ဤနေရာတွင် 'restaurant_cart' ကိုသာ သုံးပါ
let cartData = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
if(cartData.length > 0) {
    let cartBar = document.getElementById('floating-cart-bar');
    if(cartBar) {
        cartBar.style.display = 'flex';
    }
    // ပစ္စည်းအရေအတွက် စုစုပေါင်းကို ပေါင်းပြခြင်း
    let totalQty = cartData.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
    let cartCount = document.getElementById('cart-count');
    if(cartCount) {
        cartCount.innerText = totalQty;
    }
}
</script>
</body>
</html>
