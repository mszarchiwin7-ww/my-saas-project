<?php
session_start();
session_destroy(); // Session အားလုံးကို ဖျက်ဆီးပစ်မည်
header("Location: login.php");
exit();
?>