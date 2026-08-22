<?php
// ၁။ ဒေတာဘေ့စ် ချိတ်ဆက်ခြင်း
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

$message = "";
$message_type = "";
$row = [];

// ၂။ ပြင်ဆင်မည့် Item ရဲ့ ID ကို URL မှတစ်ဆင့် ဖမ်းယူခြင်း
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM restaurant_menu WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        die("ဟင်းလျာကို ရှာမတွေ့ပါဗျာ။");
    }
} else {
    die("အိုင်ဒီ (ID) မှားယွင်းနေပါသည်ဗျာ။");
}

// 🌟 [Update System] ပြင်ဆင်ပြီး "ဒေတာအသစ်သိမ်းမည်" ခလုတ်နှိပ်လိုက်သည့်အခါ
if (isset($_POST['update_item'])) {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $price = doubleval($_POST['price']);
    $image_name = $row['item_image']; // ပုံအဟောင်းကို အရင်ယူထားမည်

    // အကယ်၍ ဓာတ်ပုံအသစ် ရွေးချယ်တင်လိုက်ပါက
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/";
        
        // ပုံဟောင်းရှိရင် server ပေါ်ကနေ အရင်ဖျက်ပစ်မည်
        if (!empty($row['item_image']) && file_exists($target_dir . $row['item_image'])) {
            unlink($target_dir . $row['item_image']);
        }

        // ပုံအသစ်ကို နာမည်ပြောင်းပြီး တင်ခြင်း
        $file_ext = pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_dir . $image_name);
    }

    // ဒေတာဘေ့စ်ထဲတွင် Update ပြုလုပ်ခြင်း
    $stmt = $conn->prepare("UPDATE restaurant_menu SET item_name = ?, price = ?, item_image = ?, category = ? WHERE id = ?");
    $stmt->bind_param("sdssi", $item_name, $price, $image_name, $category, $id);
    
    if ($stmt->execute()) {
        $message = "📝 ဟင်းလျာအချက်အလက်များကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီဗျာ!";
        $message_type = "success";
        
        // ဒေတာအသစ်ကို ဖောင်ထဲမှာ ချက်ချင်းပြန်ပြနိုင်အောင် Re-fetch လုပ်ခြင်း
        $result = $conn->query("SELECT * FROM restaurant_menu WHERE id = $id");
        $row = $result->fetch_assoc();
        $stmt->close();
    } else {
        $message = "❌ ပြင်ဆင်မှု မအောင်မြင်ပါ- " . $conn->error;
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Food Item - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Pyidaungsu', sans-serif; }
        .form-box { max-width: 600px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); margin: 50px auto; }
        .btn-custom { background: linear-gradient(135deg, #ffb300, #f57c00); color: white; border: none; font-weight: bold; padding: 12px; border-radius: 10px; }
        .btn-custom:hover { opacity: 0.9; color: white; }
        .current-img { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; margin-top: 10px; border: 2px solid #dee2e6; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-box">
        <h3 class="fw-bold text-center mb-4 text-warning" style="color: #f57c00 !important;">📝 ဟင်းလျာအချက်အလက် ပြင်ဆင်ရန်ပုံစံ</h3>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> fw-bold text-center" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">🍲 ဟင်းလျာအမည်</label>
                <input type="text" name="item_name" class="form-control" value="<?php echo htmlspecialchars($row['item_name']); ?>" required style="border-radius: 8px;">
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">🏷️ အမျိုးအစား (Category)</label>
                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($row['category']); ?>" required style="border-radius: 8px;">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">💰 ဈေးနှုန်း (MMK)</label>
                <input type="number" name="price" class="form-control" value="<?php echo $row['price']; ?>" required style="border-radius: 8px;">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">📸 ဟင်းလျာဓာတ်ပုံ (ပုံအသစ်လဲလိုပါက ရွေးချယ်ပါ)</label>
                <input type="file" name="item_image" class="form-control" accept="image/*" style="border-radius: 8px;">
                
                <div class="mt-2">
                    <span class="text-muted d-block small">လက်ရှိအသုံးပြုထားသောပုံရိပ် -</span>
                    <?php 
                    $img_src = (!empty($row['item_image']) && file_exists("uploads/" . $row['item_image'])) 
                               ? "uploads/" . $row['item_image'] 
                               : "https://via.placeholder.com/100";
                    ?>
                    <img src="<?php echo $img_src; ?>" class="current-img" alt="current food">
                </div>
            </div>

            <button type="submit" name="update_item" class="btn btn-custom w-100 shadow-sm mb-3">💾 ဒေတာအသစ်သိမ်းမည်</button>
            
            <a href="admin_menu.php" class="btn btn-light text-secondary w-100 fw-bold" style="border-radius: 10px;">⬅️ မီနူးစာရင်းသို့ ပြန်သွားမည်</a>
        </form>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>