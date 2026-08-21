<?php
// menu_dashboard.php
session_start();

// Database Connection
$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ===================================================================
// 🛠️ ADMIN MENU EDIT - ADD ITEM LOGIC (သန့်ရှင်းရေးလုပ်ပြီး)
// ===================================================================
if (isset($_POST['add_item'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $price = (int)$_POST['price'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_name = time() . '_' . basename($_FILES["item_image"]["name"]);
    $target_file = $target_dir . $image_name;

    // ပုံအစစ် ဟုတ်မဟုတ် စစ်ဆေးခြင်း
    $check = getimagesize($_FILES["item_image"]["tmp_name"]);
    if($check !== false) {
        if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
            
            // 📝 ကွက်တိ ပြင်ဆင်ပြီးသား SQL Insert Query
// 📝 status column ကို ဖြုတ်ထားသော SQL Insert Query အမှန်
$sql_insert = "INSERT INTO restaurant_menu (item_name, price, category, item_image) VALUES ('$item_name', '$price', '$category', '$target_file')";            
            if ($conn->query($sql_insert) === TRUE) {
                header("Location: menu_dashboard.php?page_tab=admin-edit&success=1");
                exit();
            } else {
                echo "<script>alert('Error: " . $conn->error . "'); window.location.href='menu_dashboard.php?page_tab=admin-edit';</script>";
                exit();
            }
        } else {
            echo "<script>alert('စိတ်မရှိပါနဲ့... ပုံတင်ရတာ မအောင်မြင်ပါဘူး။'); window.location.href='menu_dashboard.php?page_tab=admin-edit';</script>";
            exit();
        }
    } else {
        echo "<script>alert('တင်လိုက်သောဖိုင်သည် ပုံအစစ်မဟုတ်ပါ။'); window.location.href='menu_dashboard.php?page_tab=admin-edit';</script>";
        exit();
    }
}

// ===================================================================
// ⚡ Real-time AJAX API - အော်ဒါအသစ် စစ်ဆေးသည့်စနစ်
// ===================================================================
if (isset($_GET['check_new_orders'])) {
    header('Content-Type: application/json');
    $sql_check = "SELECT COUNT(*) as new_count FROM customer_orders WHERE status = 'Pending' AND created_at >= NOW() - INTERVAL 5 SECOND";
    $res_check = $conn->query($sql_check);
    $row_check = $res_check->fetch_assoc();
    echo json_encode(['new_orders' => intval($row_check['new_count']) > 0]);
    exit();
}

// ===================================================================
// 🛠️ KITCHEN & CASHIER STATUS UPDATES
// ===================================================================
if (isset($_GET['update_status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = htmlspecialchars($_GET['update_status']);
    
    $stmt = $conn->prepare("UPDATE customer_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: menu_dashboard.php?page_tab=kitchen");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'settle_bill' && isset($_GET['table_num'])) {
    $table_num = intval($_GET['table_num']);
    
    $stmt = $conn->prepare("UPDATE customer_orders SET status = 'Paid' WHERE table_number = ? AND status = 'Served'");
    $stmt->bind_param("i", $table_num);
    $stmt->execute();
    $stmt->close();
    
    header("Location: menu_dashboard.php?page_tab=cashier");
    exit();
}

// ===================================================================
// 🛠️ ADMIN MENU EDIT - UPDATE & DELETE OPERATIONS
// ===================================================================
if (isset($_POST['update_item'])) {
    $item_id = intval($_POST['item_id']);
    $item_name = trim($_POST['item_name']);
    $price = doubleval($_POST['price']);
    $category = trim($_POST['category']);

    if (isset($_FILES['update_image']) && $_FILES['update_image']['error'] == 0) {
        $target_dir = "uploads/";
        $ext = pathinfo($_FILES["update_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["update_image"]["tmp_name"], $target_file);
        
        $stmt = $conn->prepare("UPDATE restaurant_menu SET item_name = ?, price = ?, category = ?, item_image = ? WHERE id = ?");
        $stmt->bind_param("sdssi", $item_name, $price, $category, $target_file, $item_id);
    } else {
        $stmt = $conn->prepare("UPDATE restaurant_menu SET item_name = ?, price = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $item_name, $price, $category, $item_id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: menu_dashboard.php?page_tab=admin-edit");
    exit();
}

if (isset($_GET['delete_item_id'])) {
    $delete_id = intval($_GET['delete_item_id']);
    $stmt = $conn->prepare("DELETE FROM restaurant_menu WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: menu_dashboard.php?page_tab=admin-edit");
    exit();
}

// ===================================================================
// 📊 DATA FETCHING
// ===================================================================
$sql_kitchen = "SELECT * FROM customer_orders WHERE status IN ('Pending', 'Cooking') ORDER BY id DESC";
$result_kitchen = $conn->query($sql_kitchen);

$sql_cashier = "SELECT table_number, GROUP_CONCAT(item_name SEPARATOR '<br>') AS all_items, GROUP_CONCAT(price SEPARATOR ',') AS all_prices, SUM(price) AS total_bill FROM customer_orders WHERE status = 'Served' GROUP BY table_number ORDER BY table_number ASC";
$result_cashier = $conn->query($sql_cashier);

$sql_history = "SELECT table_number, GROUP_CONCAT(item_name SEPARATOR ', ') AS item_list, SUM(price) AS paid_total, MAX(created_at) AS order_date FROM customer_orders WHERE status = 'Paid' GROUP BY table_number, DATE(created_at) ORDER BY order_date DESC";
$result_history = $conn->query($sql_history);

// Sales Report ဒေတာ
$sql_total_sales = "SELECT SUM(price) AS total_amount FROM customer_orders WHERE status IN ('Served', 'Paid')";
$res_total_sales = $conn->query($sql_total_sales);
$row_total_sales = $res_total_sales->fetch_assoc();
$monthly_income = !empty($row_total_sales['total_amount']) ? $row_total_sales['total_amount'] : 0;

$sql_food_items = "SELECT * FROM restaurant_menu ORDER BY id DESC";
$result_food_items = $conn->query($sql_food_items);

$current_tab = isset($_GET['page_tab']) ? htmlspecialchars($_GET['page_tab']) : 'kitchen';

// 🌐 Server Local IP ကို အလိုအလျောက် ရှာဖွေပေးမည့် စနစ်
// 🌟 သင့်စက်၏ IP ဘယ်လိုပဲပြောင်းပြောင်း အလိုအလျောက် ကွက်တိရှာဖွေပေးမည့် စမတ်ကျသော စနစ်
$server_ip = gethostbyname(gethostname()); 

// အကယ်၍ အပေါ်ကနည်းလမ်းက Localhost ('127.0.0.1' သို့မဟုတ် '::1') ပဲ ထွက်နေပါက Network IP ကို ထပ်မံရှာဖွေခြင်း
if ($server_ip === '127.0.0.1' || $server_ip === '::1') {
    if (!empty($_SERVER['HTTP_HOST'])) {
        // URL Host ဆီကနေ IP ကို လှမ်းယူခြင်း (ဥပမာ- 192.168.1.x)
        $server_ip = explode(':', $_SERVER['HTTP_HOST'])[0];
    } else {
        $server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.107'; // အားလုံးမရမှသာ Default IP သုံးမည်
    }
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
        @keyframes blinker {
    50% { opacity: 0.4; }
}
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
   <a href="logout.php" class="menu-link text-danger fw-bold" onclick="return confirm('အကောင့်မှ တကယ်ထွက်မှာ သေချာပါသလား?');"><i class="fa-solid fa-right-from-bracket me-2"></i>  (Logout)</a>
    <button onclick="playTestSound()" class="btn btn-sm btn-warning mx-auto d-block mt-4 px-3 fw-bold"><i class="fa-solid fa-volume-high"></i> 🔊 Test Sound</button>
</div>

<div class="main-content">

    <div class="audio-banner" id="audioBanner">
        <span>⚠️ စနစ်မှ အသံပုံမှန်မြည်ရန် စာမျက်နှာပေါ်တွင် တစ်ချက်နှိပ်ပေးပါ သို့မဟုတ် ဘေးကခလုတ်ကို နှိပ်ပါ -</span>
        <button onclick="enableAudioSystem()" class="btn btn-sm btn-success fw-bold">အသံစနစ် ခွင့်ပြုမည်</button>
    </div>

    <div id="page-kitchen" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-danger mb-4"><i class="fa-solid fa-fire-burner me-2"></i> စားဖိုဆောင်အတွက် မှာယူမှုများ (Kitchen Orders)</h3>
            
            <div id="kitchen-orders-table">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr><th>ခုံနံပါတ်</th><th>ဟင်းလျာအမည်</th><th>လက်ရှိအခြေအနေ</th><th>လုပ်ဆောင်ချက်</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result_kitchen && $result_kitchen->num_rows > 0) {
                            while($row = $result_kitchen->fetch_assoc()) { ?>
                            <tr>
                                <td class="fw-bold text-primary">ခုံ - <?php echo $row['table_number']; ?></td>
                                <td class="fw-bold text-start">
                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                    
                                    <?php if(!empty($row['order_comment'])): ?>
                                        <br>
                                        <span class="badge bg-danger mt-1" style="font-size: 12px; animation: blinker 1.5s linear infinite;">
                                            📝 @မှတ်ချက်: <?php echo htmlspecialchars($row['order_comment']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo ($row['status'] == 'Pending') ? 'bg-warning text-dark' : 'bg-info'; ?>"><?php echo $row['status']; ?></span></td>
                                <td>
                                    <?php if ($row['status'] == 'Pending') { ?>
                                        <a href="menu_dashboard.php?update_status=Cooking&order_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary fw-bold"><i class="fa-solid fa-fire me-1"></i> ချက်ပြုတ်မည်</a>
                                    <?php } else if ($row['status'] == 'Cooking') { ?>
                                        <a href="menu_dashboard.php?update_status=Served&order_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success fw-bold"><i class="fa-solid fa-check-double me-1"></i> Served (ပွဲပြင်ပြီး)</a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } } else { ?>
                            <tr><td colspan="4" class="text-muted py-4">လက်ရှိ ချက်ရန် အော်ဒါမရှိသေးပါ။</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div> </div> </div> ```    <div id="page-cashier" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-success mb-4"><i class="fa-solid fa-calculator me-2"></i> Ngwe Shinn Rarn Sar Pwal Khon Myar (Cashier)</h3>
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-success">
                    <tr><th>ခုံနံပါတ်</th><th>မှာယူခဲ့သော ဟင်းလျာများ</th><th>စုစုပေါင်း ကျသင့်ငွေ</th><th>လုပ်ဆောင်ချက်</th></tr>
                </thead>
                <tbody>
                    <?php if ($result_cashier && $result_cashier->num_rows > 0) {
                        while($row = $result_cashier->fetch_assoc()) { ?>
                        <tr>
                            <td class="fw-bold">ခုံ - <?php echo $row['table_number']; ?></td>
                            <td class="text-start"><?php echo $row['all_items']; ?></td>
                            <td class="fw-bold text-danger"><?php echo number_format($row['total_bill']); ?> MMK</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-dark fw-bold me-1 print-bill-btn" 
                                        data-table="<?php echo $row['table_number']; ?>" 
                                        data-items="<?php echo htmlspecialchars($row['all_items']); ?>" 
                                        data-prices="<?php echo $row['all_prices']; ?>" 
                                        data-total="<?php echo $row['total_bill']; ?>">
                                    <i class="fa-solid fa-print me-1"></i> Print Bill
                                </button>
                                <a href="menu_dashboard.php?action=settle_bill&table_num=<?php echo $row['table_number']; ?>" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('ငွေရှင်းပြီးပါပြီလား?')"><i class="fa-solid fa-check"></i> Paid (ရှင်းပြီး)</a>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="4" class="text-muted py-4">ငွေရှင်းရန် ခုံမရှိသေးပါ။</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="page-qrcode" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-info mb-4"><i class="fa-solid fa-qrcode me-2"></i> စားပွဲတင် QR Codes များ ထုတ်ယူရန် (Offline စနစ်ဖြစ်၍ အင်တာနက်မလိုပါ)</h3>
            <div class="p-4 border rounded-4 bg-light shadow-sm" style="max-width: 500px;">
                <div class="mb-3">
                    <label class="fw-bold small text-dark">လက်ရှိ ကွန်ပျူတာ၏ IP Address :</label>
                    <input type="text" id="computerIP" class="form-control form-control-sm fw-bold text-dark bg-white" value="<?php echo htmlspecialchars($server_ip); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small text-dark">ခုံနံပါတ် ရိုက်ထည့်ရန် :</label>
                    <input type="number" id="inputTableNum" class="form-control text-center fw-bold text-primary" value="1" oninput="generateSelfQR()">
                </div>
                <div class="text-center p-3 bg-white border rounded shadow-sm">
                    <h5 class="fw-bold text-dark mb-2">ခုံနံပါတ် - <span id="displayTableNum">1</span></h5>
                    <div id="qrcode-canvas" class="my-2"></div>
<div class="small text-muted mt-1" id="qrLinkText">http://<?php echo htmlspecialchars($server_ip); ?>/my-saas-project/index.php?table=1</div>                </div>
            </div>
        </div>
    </div>

    <div id="page-invoice" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-secondary mb-4"><i class="fa-solid fa-file-invoice-dollar me-2"></i> ရှင်းပြီးသား ဘေလ်မှတ်တမ်းများ (History)</h3>
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-secondary">
                    <tr><th>ရက်စွဲ / အချိန်</th><th>ခုံနံပါတ်</th><th>ဟင်းလျာများ</th><th>စုစုပေါင်းငွေ</th></tr>
                </thead>
                <tbody>
                    <?php if ($result_history && $result_history->num_rows > 0) {
                        while($row = $result_history->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['order_date']; ?></td>
                            <td class="fw-bold">ခုံ - <?php echo $row['table_number']; ?></td>
                            <td class="text-start small"><?php echo htmlspecialchars($row['item_list']); ?></td>
                            <td class="fw-bold text-success"><?php echo number_format($row['paid_total']); ?> MMK</td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="4" class="text-muted py-4">ရှင်းပြီးသား မှတ်တမ်းမရှိသေးပါ။</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
// ၁။ ၁၂ လစာအတွက် အလွတ် Array တစ်ခု ဆောက်မည် (0 လို့ စထားမယ်)
$monthly_sales = array_fill(1, 12, 0);

// ၂။ Database ထဲမှ လက်ရှိနှစ်အတွက် လအလိုက် ရောင်းရငွေများကို ပေါင်းမည်
$current_year = date('Y');
$sql = "SELECT MONTH(created_at) as m, SUM(price) as total FROM customer_orders WHERE YEAR(created_at) = '$current_year' GROUP BY MONTH(created_at)";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $month_num = (int)$row['m'];
        $monthly_sales[$month_num] = (float)$row['total'];
    }
}
?>

<div id="page-sales" class="page-section">
    <div class="dashboard-box">
        <h3 class="fw-bold text-primary mb-4">📊 Sales Report (အင်တာနက်မလိုဘဲ တိုက်ရိုက်ပြသခြင်း)</h3>
        <div class="row mb-4">
            <div class="col-md-6 mb-3"><div class="report-card"><p class="text-muted mb-1">ယနေ့ အရောင်း စုစုပေါင်း</p><h2 class="fw-bold text-success"><?php echo number_format($monthly_income); ?> MMK</h2></div></div>
            <div class="col-md-6 mb-3"><div class="report-card" style="border-top-color: #2ecc71;"><p class="text-muted mb-1">ယခုလ အရောင်း စုစုပေါင်း</p><h2 class="fw-bold text-primary"><?php echo number_format($monthly_income); ?> MMK</h2></div></div>
        </div>
        
        <h5 class="fw-bold text-dark mb-2">📊 လအလိုက် ရောင်းရငွေ ပြည့်စုံသောဇယားကွက်</h5>
        <div class="bar-container">
            <?php
            $months = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            // အမြင့်ဆုံးပမာဏကို ရှာပြီး ဂရပ်အမြင့် (Percentage) ကို တွက်ချက်ရန်
            $max_sale = max($monthly_sales) > 0 ? max($monthly_sales) : 1;
            $current_month_num = (int)date('n');

            foreach ($months as $num => $name) {
                $sale_amount = $monthly_sales[$num];
                // ရာခိုင်နှုန်းအလိုက် ဘားတံအမြင့် တွက်ချက်ခြင်း (အများဆုံး ၉၀% ထိ)
                $height_percent = ($sale_amount / $max_sale) * 85;
                if ($sale_amount > 0 && $height_percent < 10) {
                    $height_percent = 10; // သေးလွန်းပါက အနည်းဆုံးမြင်ရအောင် ပုံဖော်ခြင်း
                }

                $is_current = ($num == $current_month_num);
                $bar_color = $is_current ? '#3498db' : '#2ecc71';
                $label_class = $is_current ? 'text-primary fw-bold' : '';
            ?>
                <div class="bar-item">
                    <div class="bar-fill" style="height: <?php echo $height_percent; ?>%; background: <?php echo $bar_color; ?>" data-value="<?php echo number_format($sale_amount); ?> MMK"></div>
                    <div class="bar-label <?php echo $label_class; ?>"><?php echo $name; ?></div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<div id="page-admin-edit" class="page-section">
        <div class="dashboard-box">
            <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-utensils text-warning me-2"></i> ဟင်းလျာများနှင့် BBQ များ စီမံခန့်ခွဲခြင်း</h3>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card p-3 shadow-sm bg-light border-0">
                        <h5 class="fw-bold text-primary mb-3">➕ ဟင်းပွဲအသစ်ထည့်ရန်</h5>
                        <form action="menu_dashboard.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3"><label class="small fw-bold mb-1">ဟင်းလျာအမည်</label><input type="text" name="item_name" class="form-control" required></div>
                            <div class="mb-3"><label class="small fw-bold mb-1">ဈေးနှုန်း</label><input type="number" name="price" class="form-control" required></div>
                            <div class="mb-3"><label class="small fw-bold mb-1">အမျိုးအစား</label><select name="category" class="form-select"><option value="အကင်">🔥 အကင်</option><option value="အသုပ်">🥗 အသုပ်</option></select></div>
                            <div class="mb-3"><label class="small fw-bold mb-1">ပုံတင်ရန်</label><input type="file" name="item_image" class="form-control" accept="image/*" required></div>
                            <button type="submit" name="add_item" class="btn btn-primary w-100 fw-bold">ဒေတာသိမ်းမည်</button>
                        </form>
                    </div>
                </div>
<div class="col-md-8">
    <table class="table table-hover align-middle text-center bg-white border rounded">
        <thead class="table-dark"><tr><th>ဟင်းပွဲပုံ</th><th>အမည်</th><th>အမျိုးအစား</th><th>ဈေးနှုန်း</th><th>လုပ်ဆောင်ချက်</th></tr></thead>
        <tbody>
            <?php if ($result_food_items && $result_food_items->num_rows > 0) {
                while ($item = $result_food_items->fetch_assoc()) { 
                    
                    // 💡 ပြင်ဆင်လိုက်သောအပိုင်း- ဒေတာဘေ့စ်ထဲမှာ uploads/ ပါပြီးသားမို့လို့ တိုက်ရိုက်စစ်ဆေးပါသည်
// 💡 အဟောင်းရော အသစ်ရော လမ်းကြောင်းမှန်အောင် အလိုအလျောက် စစ်ဆေးပေးမည့်စနစ်
                    $img_file = !empty($item['item_image']) ? $item['item_image'] : 'uploads/default.jpg';

                    // အကယ်၍ database ထဲက စာသားထဲမှာ 'uploads/' မပါနေပါက ရှေ့ကနေ ထည့်ပေါင်းပေးမည်
                    if (strpos($img_file, 'uploads/') === false) {
                        $img_src = "uploads/" . $img_file;
                    } else {
                        $img_src = $img_file;
                    }

                    // ပုံ တကယ်ရှိမရှိ ထပ်မံစစ်ဆေးပြီး မရှိပါက default.jpg ပြမည်
                    if (!file_exists($img_src)) {
                        $img_src = "uploads/default.jpg";
                    }                    ?>
                <tr>
                    <form action="menu_dashboard.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <td><img src="<?php echo $img_src; ?>" class="menu-thumb" width="50" style="object-fit: cover; border-radius: 8px;"></td>
                        <td><input type="text" name="item_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($item['item_name']); ?>" required></td>
                        <td><select name="category" class="form-select form-select-sm"><option value="အကင်" <?php echo ($item['category'] == 'အကင်')?'selected':''; ?>>🔥 အကင်</option><option value="အသုပ်" <?php echo ($item['category'] == 'အသုပ်')?'selected':''; ?>>🥗 အသုပ်</option></select></td>
                        <td><input type="number" name="price" class="form-control form-control-sm" value="<?php echo $item['price']; ?>" required></td>
                        <td>
                            <button type="submit" name="update_item" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                            <a href="menu_dashboard.php?delete_item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ဖျက်မှာလား?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </form>                                </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<audio id="orderSound" src="file:///C:/Windows/Media/notify.wav" preload="auto"></audio>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let isAudioEnabled = false;
let qrCodeGenerator = null;

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

function enableAudioSystem() {
    isAudioEnabled = true;
    document.getElementById('audioBanner').style.display = 'none';
}

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
    } catch(e) {
        alert("Audio Device Error");
    }
}

function generateSelfQR() {
    let ip = document.getElementById('computerIP').value;
    let tableNum = document.getElementById('inputTableNum').value;
    if(!tableNum || tableNum < 1) tableNum = 1;
    
    document.getElementById('displayTableNum').innerText = tableNum;
let finalLink = "http://" + ip + "/my-saas-project/index.php?table=" + tableNum;    document.getElementById('qrLinkText').innerText = finalLink;
    
    let qrContainer = document.getElementById("qrcode-canvas");
    qrContainer.innerHTML = ""; 
    
    qrCodeGenerator = new QRCode(qrContainer, {
        text: finalLink,
        width: 200,
        height: 200,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
}

function checkIncomingOrders() {
    fetch('menu_dashboard.php?check_new_orders=1')
        .then(response => response.json())
        .then(data => {
            if (data.new_orders) {
                // ၁။ အော်ဒါသစ်တက်လာတိုင်း အသံအရင်မြည်မည်
                playTestSound(); 
                
                // ၂။ HTML ထဲမှာ #page-kitchen (Kitchen Section) ရှိနေပြီး ၎င်းသည် မျက်နှာပြင်ပေါ်တွင် ပွင့်နေ၊ ပြသနေပါက
                // (ဘယ်လို URL ပုံစံမျိုးနဲ့ပဲ ဖွင့်ထားဖွင့်ထား ကွက်တိ Refresh လုပ်ပေးမည့် စနစ်ဖြစ်ပါသည်)
                const kitchenSection = document.getElementById('page-kitchen');
                if (kitchenSection && kitchenSection.style.display !== 'none' && !kitchenSection.classList.contains('d-none')) {
                    
                    // စာမျက်နှာတစ်ခုလုံး Reload မဖြစ်စေဘဲ #kitchen-orders-table ဇယားကွက်ဧရိယာလေးကိုတင် နောက်ကွယ်ကနေ အလိုအလျောက် Refresh လုပ်ခြင်း
                    $("#kitchen-orders-table").load(window.location.href + " #kitchen-orders-table > *");
                    console.log("Kitchen orders table refreshed successfully!");
                }
            }
        })
        .catch(e => console.log("Offline or server pending"));
}
document.addEventListener('click', function() {
    enableAudioSystem();
});

document.addEventListener("DOMContentLoaded", function() {
    let currentTab = "<?php echo $current_tab; ?>";
    switchPage(currentTab, null);
    setInterval(checkIncomingOrders, 4000);

    // 🖨️ Print Bill Listener
    document.querySelectorAll('.print-bill-btn').forEach(button => {
        button.addEventListener('click', function() {
            let tableNum = this.getAttribute('data-table');
            let itemsRaw = this.getAttribute('data-items');
            let pricesString = this.getAttribute('data-prices');
            let totalBill = Number(this.getAttribute('data-total'));

            let items = itemsRaw.split('<br>');
            let prices = pricesString.split(',');
            let today = new Date().toLocaleString();

            let receiptWindow = window.open('', '_blank', 'width=450,height=650');
            if(!receiptWindow) {
                alert("Please allow pop-ups to print bill!");
                return;
            }

            let receiptHtml = `
            <html>
            <head>
                <title>Receipt Table ${tableNum}</title>
                <style>
                    body { font-family: sans-serif; padding: 20px; text-align: center; }
                    .header { font-size: 20px; font-weight: bold; }
                    .separator { border-top: 1px dashed #000; margin: 10px 0; }
                    .details { text-align: left; font-size: 14px; line-height: 1.6; }
                    .item-table { width: 100%; font-size: 14px; text-align: left; border-collapse: collapse; }
                    .item-table th { border-bottom: 1px solid #000; padding-bottom: 5px; }
                    .item-table td { padding: 6px 0; }
                    .total-row { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; color: red; }
                </style>
            </head>
            <body>
                <div class="header">🏪 Taste of Myanmar</div>
                <div class="separator"></div>
                <div class="details"><b>ပြေစာနေ့စွဲ:</b> ${today}<br><b>နေရာ:</b> Table - ${tableNum}</div>
                <div class="separator"></div>
                <table class="item-table">
                    <thead><tr><th>ဟင်းလျာအမည်</th><th style="text-align:right;">ဈေးနှုန်း</th></tr></thead>
                    <tbody>`;

            for(let i=0; i<items.length; i++) {
                if(items[i].trim() !== "") {
                    receiptHtml += `<tr><td>${items[i]}</td><td style="text-align:right;">${Number(prices[i] || 0).toLocaleString()} MMK</td></tr>`;
                }
            }

            receiptHtml += `</tbody></table>
                <div class="separator"></div>
                <div class="total-row">စုစုပေါင်း: ${totalBill.toLocaleString()} MMK</div>
                <div style="margin-top:25px; font-style:italic;">~ ကျေးဇူးတင်ပါသည် ~</div>
            </body>
            </html>`;

            receiptWindow.document.write(receiptHtml);
            receiptWindow.document.close();
            setTimeout(() => { receiptWindow.focus(); receiptWindow.print(); }, 500);
        });
    });
});
</script>
</body>
</html>