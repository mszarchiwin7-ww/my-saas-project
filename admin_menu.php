<?php
error_reporting(0);
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

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

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $img_query = $conn->query("SELECT item_image FROM restaurant_menu WHERE id = $delete_id");
    if($img_row = $img_query->fetch_assoc()) {
        if(!empty($img_row['item_image']) && file_exists("uploads/" . $img_row['item_image'])) {
            unlink("uploads/" . $img_row['item_image']);
        }
    }
    $conn->query("DELETE FROM restaurant_menu WHERE id = $delete_id");
    header("Location: admin_menu.php");
    exit();
}

$sql = "SELECT * FROM restaurant_menu ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Pyidaungsu', sans-serif; }
        .sidebar { width: 260px; background-color: #1e293b; min-height: 100vh; color: white; position: fixed; }
        .sidebar a { color: #94a3b8; text-decoration: none; padding: 12px 20px; display: block; font-weight: 500; border-radius: 8px; margin: 4px 10px; }
        .sidebar a:hover, .sidebar a.active { background-color: #0f172a; color: white; }
        .main-content { margin-left: 260px; padding: 30px; }
        .admin-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<!-- Sidebar Section -->
<div class="sidebar d-flex flex-column p-3">
    <h4 class="text-white fw-bold px-3 mb-4 mt-2">Taste of Myanmar</h4>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item"><a href="menu_dashboard.php">Kitchen Dashboard</a></li>
        <li class="nav-item"><a href="#">Cashier (Checkout)</a></li>
        <li class="nav-item"><a href="#">QR Codes Download</a></li>
        <li class="nav-item"><a href="#">Bill Invoice History</a></li>
        <li class="nav-item"><a href="admin_menu.php" class="active">Admin Menu Edit</a></li>
        <li class="nav-item"><a href="#">Sales Report</a></li>
    </ul>
    <hr>
    <div class="px-3">
        <a href="#" class="text-danger fw-bold">(Logout)</a>
    </div>
</div>

<!-- Main Content Section -->
<div class="main-content">
    <div class="container-fluid">
        <div class="admin-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark">⚙️ ဟင်းလျာမီနူးများ စီမံခန့်ခွဲခြင်း (Menu Panel)</h3>
                <a href="admin_add_item.php" class="btn btn-success fw-bold">+ ဟင်းလျာအသစ်ထည့်မည်</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ပုံရိပ်</th>
                            <th>ဟင်းလျာအမည်</th>
                            <th>အမျိုးအစား</th>
                            <th>ဈေးနှုန်း</th>
                            <th>လုပ်ဆောင်ချက်</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $img_src = !empty($row['item_image']) 
                                ? "uploads/" . $row['item_image'] 
                                : "https://via.placeholder.com/60"; 
                    ?>
                        <tr>
                            <td><img src="<?php echo $img_src; ?>" class="table-img" alt="food"></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo isset($row['category']) && !empty($row['category']) ? htmlspecialchars($row['category']) : 'Uncategorized'; ?>
                                </span>
                            </td>
                            <td class="text-danger fw-bold"><?php echo isset($row['price']) ? number_format($row['price']) : '0'; ?> MMK</td>
                            <td>
                                <a href="admin_edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning fw-bold text-dark">✏️ ပြင်မည်</a>
                                <a href="admin_menu.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger fw-bold ms-1" onclick="return confirm('သေချာပေါက် ဖျက်မှာပါလားဗျာ?')">🗑️ ဖျက်မည်</a>
                            </td>
                        ></tr> 
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-muted py-4'>လောလောဆယ် မီနူးထဲမှာ ဘာဟင်းပွဲမှ မရှိသေးပါဗျာ။</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>