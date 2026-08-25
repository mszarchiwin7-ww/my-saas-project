<?php
error_reporting(0);
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

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
    <a href="logout.php" class="menu-link text-danger fw-bold"><i class="fa-solid fa-right-from-bracket me-2"></i> (Logout)</a>
    <button onclick="playTestSound()" class="btn btn-sm btn-warning mx-auto d-block mt-4 px-3 fw-bold"><i class="fa-solid fa-volume-high"></i> 🔊 Test Sound</button>
</div>

<div class="main-content">
    <div class="audio-banner" id="audioBanner">
        <span>⚠️ စနစ်မှ အသံပုံမှန်မြည်ရန် စာမျက်နှာပေါ်တွင် တစ်ချက်နှိပ်ပေးပါ -</span>
        <button onclick="enableAudioSystem()" class="btn btn-sm btn-success fw-bold">အသံစနစ် ခွင့်ပြုမည်</button>
    </div>

    <!-- Admin Edit Section (Railway Compatible) -->
    <div id="page-admin-edit" class="page-section active">
        <div class="dashboard-box">
            <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-utensils text-warning me-2"></i> ဟင်းလျာများနှင့် BBQ များ စီမံခန့်ခွဲခြင်း</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center bg-white border rounded">
                    <thead class="table-dark">
                        <tr><th>ဟင်းပွဲပုံ</th><th>အမည်</th><th>အမျိုးအစား</th><th>ဈေးနှုန်း</th><th>လုပ်ဆောင်ချက်</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM restaurant_menu ORDER BY id DESC");
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $img_src = !empty($row['item_image']) ? "uploads/" . $row['item_image'] : "https://via.placeholder.com/60";
                    ?>
                        <tr>
                            <td><img src="<?php echo $img_src; ?>" class="menu-thumb"></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['category'] ?? 'Uncategorized'); ?></span></td>
                            <td class="text-danger fw-bold"><?php echo number_format($row['price']); ?> MMK</td>
                            <td>
                                <a href="admin_menu.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ဖျက်မှာလား?')"><i class="fa-solid fa-trash"></i> ဖျက်မည်</a>
                            </td>
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

<script>
function switchPage(pageId, element) {
    document.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
    if(element) { element.classList.add('active'); }
    document.querySelectorAll('.page-section').forEach(page => page.classList.remove('active'));
    let targetPage = document.getElementById('page-' + pageId);
    if(targetPage) targetPage.classList.add('active');
}
function enableAudioSystem() { document.getElementById('audioBanner').style.display = 'none'; }
function playTestSound() { alert("Sound Test"); }
</script>
</body>
</html>
<?php $conn->close(); ?>