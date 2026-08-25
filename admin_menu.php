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

// Handle Add Item
if (isset($_POST['add_item'])) {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $price = $_POST['price'];
    $category = $conn->real_escape_string($_POST['category']);
    
    $image_name = "";
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $image_name = time() . "_" . basename($_FILES['item_image']['name']);
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        move_uploaded_file($_FILES['item_image']['tmp_name'], "uploads/" . $image_name);
    }
    
    $conn->query("INSERT INTO restaurant_menu (item_name, price, category, item_image) VALUES ('$item_name', '$price', '$category', '$image_name')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Update Item
if (isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $price = $_POST['price'];
    $category = $conn->real_escape_string($_POST['category']);

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $image_name = time() . "_" . basename($_FILES['item_image']['name']);
        move_uploaded_file($_FILES['item_image']['tmp_name'], "uploads/" . $image_name);
        $conn->query("UPDATE restaurant_menu SET item_name='$item_name', price='$price', category='$category', item_image='$image_name' WHERE id='$item_id'");
    } else {
        $conn->query("UPDATE restaurant_menu SET item_name='$item_name', price='$price', category='$category' WHERE id='$item_id'");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Item
if (isset($_GET['delete_item_id'])) {
    $del_id = intval($_GET['delete_item_id']);
    $conn->query("DELETE FROM restaurant_menu WHERE id='$del_id'");
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taste of Myanmar - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: sans-serif; margin: 0; padding: 0; display: flex; }
        .sidebar { width: 260px; height: 100vh; background-color: #2c3e50; color: white; position: fixed; top: 0; left: 0; padding-top: 15px; z-index: 1000; }
        .sidebar .brand { padding: 15px 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #34495e; margin-bottom: 15px; color: #ecf0f1; text-align: center; }
        .sidebar a { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: block; font-size: 14px; border-left: 4px solid transparent; cursor: pointer; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; border-left-color: #3498db; }
        .sidebar a i { margin-right: 12px; width: 20px; text-align: center; }
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); min-height: 100vh; box-sizing: border-box; }
        .page-section { display: none; }
        .page-section.active { display: block; }
        .dashboard-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .menu-thumb { width: 65px; height: 55px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .table th, .table td { border: 1px solid #dee2e6 !important; padding: 12px !important; }
        .report-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid #3498db; }
        .audio-banner { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 12px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .bar-container { display: flex; align-items: flex-end; height: 200px; background: #eee; padding: 10px; border-radius: 10px; gap: 10px; margin-top: 20px; }
        .bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
        .bar-fill { width: 100%; background: #2ecc71; border-radius: 5px 5px 0 0; min-height: 5px; transition: height 0.5s ease; position: relative; }
        .bar-fill:hover::before { content: attr(data-value); position: absolute; top: -25px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 3px 6px; font-size: 11px; border-radius: 3px; white-space: nowrap; }
        .bar-label { font-size: 12px; font-weight: bold; margin-top: 5px; color: #555; }
        #qrcode-canvas { padding: 10px; background: white; display: inline-block; border: 1px solid #ddd; border-radius: 5px; }
        @keyframes blinker { 50% { opacity: 0.4; }}
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">🏪 Taste of Myanmar</div>
    <a id="link-kitchen" class="menu-link" onclick="switchPage('kitchen', this)"><i class="fa-solid fa-fire-burner text-danger"></i> Kitchen Dashboard</a>
    <a id="link-cashier" class="menu-link" onclick="switchPage('cashier', this)"><i class="fa-solid fa-calculator text-success"></i> Cashier (Checkout)</a>
    <a id="link-qrcode" class="menu-link" onclick="switchPage('qrcode', this)"><i class="fa-solid fa-qrcode text-info"></i> QR Codes Download</a>
    <a id="link-invoice" class="menu-link" onclick="switchPage('invoice', this)"><i class="fa-solid fa-file-invoice-dollar text-secondary"></i> Bill Invoice History</a>
    <a id="link-admin-edit" class="menu-link" onclick="switchPage('admin-edit', this)"><i class="fa-solid fa-utensils text-warning"></i> Admin Menu Edit</a>
    <a id="link-sales" class="menu-link" onclick="switchPage('sales', this)"><i class="fa-solid fa-chart-line text-primary"></i> Sales Report</a>
    <a href="logout.php" class="menu-link text-danger fw-bold" onclick="return confirm('အကောင့်မှ တကယ်ထွက်မှာ သေချာပါသလား?');"><i class="fa-solid fa-right-from-bracket me-2"></i> (Logout)</a>
    <button onclick="playTestSound()" class="btn btn-sm btn-warning mx-auto d-block mt-4 px-3 fw-bold"><i class="fa-solid fa-volume-high"></i> 🔊 Test Sound</button>
</div>

<div class="main-content">
    <div class="audio-banner" id="audioBanner">
        <span>⚠️ စနစ်မှ အသံပုံမှန်မြည်ရန် စာမျက်နှာပေါ်တွင် တစ်ချက်နှိပ်ပေးပါ -</span>
        <button onclick="enableAudioSystem()" class="btn btn-sm btn-success fw-bold">အသံစနစ် ခွင့်ပြုမည်</button>
    </div>

    <!-- Kitchen Section -->
    <div id="page-kitchen" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-danger mb-4"><i class="fa-solid fa-fire-burner me-2"></i> စားဖိုဆောင်အတွက် မှာယူမှုများ (Kitchen Orders)</h3>
            <div id="kitchen-orders-table">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr><th>ခုံနံပါတ်</th><th>ဟင်းလျာအမည်</th><th>လက်ရှိအခြေအနေ</th><th>လုပ်ဆောင်ချက်</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="text-muted py-4">မှာယူမှု မရှိသေးပါ။</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cashier Section -->
    <div id="page-cashier" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-success mb-4"><i class="fa-solid fa-calculator me-2"></i> Cashier (Checkout)</h3>
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-success">
                    <tr><th>ခုံနံပါတ်</th><th>မှာယူခဲ့သော ဟင်းလျာများ</th><th>စုစုပေါင်း ကျသင့်ငွေ</th><th>လုပ်ဆောင်ချက်</th></tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="text-muted py-4">ငွေရှင်းရန် ခုံမရှိသေးပါ။</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- QR Code Section -->
    <div id="page-qrcode" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-info mb-4"><i class="fa-solid fa-qrcode me-2"></i> စားပွဲတင် QR Codes များ ထုတ်ယူရန်</h3>
            <div class="p-4 border rounded-4 bg-light shadow-sm" style="max-width: 500px;">
                <div class="mb-3">
                    <label class="fw-bold small text-dark">Domain / IP Address :</label>
                    <input type="text" id="computerIP" class="form-control form-control-sm fw-bold text-dark bg-white" value="<?php echo $_SERVER['HTTP_HOST']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small text-dark">ခုံနံပါတ် ရိုက်ထည့်ရန် :</label>
                    <input type="number" id="inputTableNum" class="form-control text-center fw-bold text-primary" value="1" oninput="generateSelfQR()">
                </div>
                <div class="text-center p-3 bg-white border rounded shadow-sm">
                    <h5 class="fw-bold text-dark mb-2">ခုံနံပါတ် - <span id="displayTableNum">1</span></h5>
                    <div id="qrcode-canvas" class="my-2"></div>
                    <div class="small text-muted mt-1" id="qrLinkText"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Invoice History Section -->
    <div id="page-invoice" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-secondary mb-4"><i class="fa-solid fa-file-invoice-dollar me-2"></i> ရှင်းပြီးသား ဘေလ်မှတ်တမ်းများ (History)</h3>
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-secondary">
                    <tr><th>ရက်စွဲ / အချိန်</th><th>ခုံနံပါတ်</th><th>ဟင်းလျာများ</th><th>စုစုပေါင်းငွေ</th></tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="text-muted py-4">မှတ်တမ်း မရှိသေးပါ။</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales Report Section -->
    <div id="page-sales" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-primary mb-4">📊 Sales Report</h3>
            <div class="row mb-4">
                <div class="col-md-6 mb-3"><div class="report-card"><p class="text-muted mb-1">ယနေ့ အရောင်း စုစုပေါင်း</p><h2 class="fw-bold text-success">0 MMK</h2></div></div>
                <div class="col-md-6 mb-3"><div class="report-card"><p class="text-muted mb-1">ယခုလ အရောင်း စုစုပေါင်း</p><h2 class="fw-bold text-primary">0 MMK</h2></div></div>
            </div>
        </div>
    </div>

<!-- Admin Menu Edit Section -->
    <div id="page-admin-edit" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-utensils text-warning me-2"></i> ဟင်းလျာများနှင့် BBQ များ စီမံခန့်ခွဲခြင်း</h3>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card p-3 shadow-sm bg-light border-0">
                        <h5 class="fw-bold text-primary mb-3">➕ ဟင်းပွဲအသစ်ထည့်ရန်</h5>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="small fw-bold mb-1">ဟင်းလျာအမည်</label>
                                <input type="text" name="item_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold mb-1">ဈေးနှုန်း</label>
                                <input type="number" name="price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold mb-1">အမျိုးအစား</label>
                                <select name="category" class="form-select">
                                    <option value="အကင်">🔥 အကင်</option>
                                    <option value="အသုပ်">🥗 အသုပ်</option>
                                    <option value="အသုပ်">🥗 အရည်</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold mb-1">ပုံတင်ရန်</label>
                                <input type="file" name="item_image" class="form-control" accept="image/*" required>
                            </div>
                            <button type="submit" name="add_item" class="btn btn-primary w-100 fw-bold">ဒေတာသိမ်းမည်</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <table class="table table-hover align-middle text-center bg-white border rounded">
                        <thead class="table-dark">
                            <tr>
                                <th>ဟင်းပွဲပုံ</th>
                                <th>အမည်</th>
                                <th>အမျိုးအစား</th>
                                <th>ဈေးနှုန်း</th>
                                <th>လုပ်ဆောင်ချက်</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM restaurant_menu ORDER BY id DESC");
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $img_src = !empty($row['item_image']) ? "uploads/" . $row['item_image'] : "https://via.placeholder.com/60?text=No+Image";
                        ?>
                            <tr>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                    <td>
                                        <img src="<?php echo $img_src; ?>" class="menu-thumb" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;" onerror="this.src='https://via.placeholder.com/60?text=Error'">
                                    </td>
                                    <td>
                                        <input type="text" name="item_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['item_name']); ?>" required>
                                    </td>
                                    <td>
                                        <select name="category" class="form-select form-select-sm">
                                            <option value="အကင်" <?php if($row['category']=='အကင်') echo 'selected'; ?>>🔥 အကင်</option>
                                            <option value="အသုပ်" <?php if($row['category']=='အသုပ်') echo 'selected'; ?>>🥗 အသုပ်</option>
                                            <option value="အရည်" <?php if($row['category']=='အရည်') echo 'selected'; ?>>🥗 အရည်</option>

                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="price" class="form-control form-control-sm" value="<?php echo $row['price']; ?>" required>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_item" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                        <a href="?delete_item_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ဖျက်မှာလား?')"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </form>
                            </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-muted py-4'>ဟင်းပွဲ မရှိသေးပါ။</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function switchPage(pageId, element) {
    document.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
    if(element) { element.classList.add('active'); } 
    else { let activeLink = document.getElementById("link-" + pageId); if(activeLink) activeLink.classList.add('active'); }
    document.querySelectorAll('.page-section').forEach(page => page.classList.remove('active'));
    let targetPage = document.getElementById('page-' + pageId);
    if(targetPage) targetPage.classList.add('active');
    
    if(pageId === 'qrcode') {
        setTimeout(generateSelfQR, 200);
    }
}
function enableAudioSystem() { document.getElementById('audioBanner').style.display = 'none'; }
function playTestSound() {
    try {
        let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        let oscillator = audioCtx.createOscillator();
        let gainNode = audioCtx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
        gainNode.gain.setValueAtTime(0.5, audioCtx.currentTime);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.3);
    } catch(e) {}
}
function generateSelfQR() {
    let host = document.getElementById('computerIP').value;
    let tableNum = document.getElementById('inputTableNum').value;
    if(!tableNum || tableNum < 1) tableNum = 1;
    document.getElementById('displayTableNum').innerText = tableNum;
    let finalLink = "https://" + host + "/index.php?table=" + tableNum;
    document.getElementById('qrLinkText').innerText = finalLink;
    let qrContainer = document.getElementById("qrcode-canvas");
    qrContainer.innerHTML = "";
    new QRCode(qrContainer, {
        text: finalLink,
        width: 200,
        height: 200,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
}
document.addEventListener('click', function() { enableAudioSystem(); });
document.addEventListener("DOMContentLoaded", function() {
    switchPage('admin-edit', document.getElementById('link-admin-edit'));
});
</script>
</body>
</html>
<?php $conn->close(); ?>