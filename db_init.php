<?php
error_reporting(0);
ini_set('display_errors', 0);

// Railway Database Connection
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

// Get table number from URL
$table_num = isset($_GET['table']) ? intval($_GET['table']) : 1;
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
    စားပွဲခုံ - <?php echo $table_num; ?>
</div>

<div class="container mt-4">
    <?php
    $result = $conn->query("SELECT * FROM restaurant_menu ORDER BY id DESC");
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $item_id = $row['id'];
            $item_name = htmlspecialchars($row['item_name']);
            $price = $row['price'];
$img_src = !empty($row['item_image']) ? "uploads/" . $row['item_image'] : "https://via.placeholder.com/100?text=No+Image";
 ?>
        <div class="food-card">
            <img src="<?php echo $img_src; ?>" class="food-img" onerror="this.src='https://via.placeholder.com/100?text=Error'">
            <h5><?php echo $item_name; ?></h5>
            <p class="text-danger fw-bold"><?php echo number_format($price); ?>Baht</p>
            
            <button class="btn-add" id="btn-<?php echo $item_id; ?>" onclick="showControls('<?php echo $item_id; ?>')">
                ➕ ခြင်းတောင်းထဲထည့်မည်
            </button>

            <div id="controls-<?php echo $item_id; ?>" class="order-controls">
                <div class="d-flex align-items-center mb-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="changeQty('<?php echo $item_id; ?>', -1)">-</button>
                    <input type="text" id="qty-<?php echo $item_id; ?>" value="1" class="form-control mx-2 text-center" style="width: 50px;">
                    <button class="btn btn-sm btn-outline-secondary" onclick="changeQty('<?php echo $item_id; ?>', 1)">+</button>
                </div>
                <button class="btn btn-success w-100" onclick="addToCart('<?php echo $item_id; ?>', '<?php echo addslashes($item_name); ?>', '<?php echo $price; ?>')">
                    အတည်ပြုမည်
                </button>
            </div>
        </div>
    <?php
        }
    } else {
        echo "<p class='text-center text-muted mt-5'>ဟင်းလျာများ မရှိသေးပါ။</p>";
    }
    ?>
</div>

<div id="floating-cart-bar">
    <div><span id="cart-count" class="badge bg-white text-danger">0</span> ပွဲ မှာထားသည်</div>
    <a href="shopping_cart.php?table=<?php echo $table_num; ?>" class="text-white fw-bold" style="text-decoration:none">ခြင်းတောင်းကြည့်မည် ></a>
</div>  

<script>
const urlParams = new URLSearchParams(window.location.search);
const urlTable = urlParams.get('table');

if (urlTable) {
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
    cart.push({ id, name, price: parseFloat(price), quantity: parseInt(qty) });
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    alert('ခြင်းတောင်းထဲသို့ ထည့်ပြီးပါပြီ!');
    location.reload();
}

let cartData = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
if(cartData.length > 0) {
    let cartBar = document.getElementById('floating-cart-bar');
    if(cartBar) {
        cartBar.style.display = 'flex';
    }
    let totalQty = cartData.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
    let cartCount = document.getElementById('cart-count');
    if(cartCount) {
        cartCount.innerText = totalQty;
    }
}
</script>
</body>
</html>
<?php $conn->close(); ?>