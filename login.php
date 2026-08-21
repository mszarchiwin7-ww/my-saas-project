<?php
// ၁။ Session စတင်ခြင်း
session_start();

// ၂။ ဒေတာဘေ့စ် ချိတ်ဆက်ခြင်း
$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Error ပြရန် ယာယီ Variable တစ်ခု ကြေညာထားခြင်း
$error_message = "";


// ၃။ Form တင်လိုက်လျှင် (POST Request ဖြစ်လျှင်) စစ်ဆေးခြင်း
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($username) && !empty($password)) {
        
        // 🎯 Prepare Statement သုံးပြီး app_users ထဲကနေ ရှာဖွေခြင်း
        $stmt = $conn->prepare("SELECT id, username, password FROM app_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 🎯 ဒေတာဘေ့စ်ထဲက Hashed Password ကို တိုက်စစ်ခြင်း
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                
                $stmt->close();
                
                // အောင်မြင်လျှင် Dashboard သို့ တိုက်ရိုက်ပို့ခြင်း
                header("Location: menu_dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }

    // ❌ အချက်အလက်မှားယွင်းပါက Variable ထဲသို့ စာသားထည့်ခြင်း (Redirect မလုပ်တော့ပါ)
    $error_message = "Username သို့မဟုတ် Password မှားယွင်းနေပါသည်။";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SaaS QR Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Pyidaungsu', sans-serif; }
        .login-container { max-width: 420px; margin: 80px auto; padding: 0 15px; }
        .login-card { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.06); padding: 35px 30px; }
        .login-btn { 
            background-color: #0d6efd !important; 
            color: white !important; 
            border: none !important; 
            border-radius: 8px !important; 
            font-weight: bold; 
            padding: 12px; 
            transition: all 0.2s ease-in-out;
        }
        .login-btn:hover { background-color: #0b5ed7 !important; color: white !important; }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="card login-card">
        
        <h4 class="text-center fw-bold mb-4">🏪 ဆိုင်ရှင် Login ဝင်ရန်</h4>

        <?php if (!empty($error_message)) { ?>
            <div class="alert alert-danger mb-3 text-center fw-bold" style="border-radius: 10px; font-size: 14px;">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php } ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="username ကို ရိုက်ထည့်ပါ" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="password ကို ရိုက်ထည့်ပါ" required>
            </div>

            <button type="submit" class="btn login-btn w-100 fs-5">
                📘 Login ဝင်မည်
            </button>
        </form>

    </div> 
</div> 
</body>
</html>