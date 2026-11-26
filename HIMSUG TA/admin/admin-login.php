<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
if (!empty($_SESSION["admin_id"])) {
    header("Location: admin-dashboard.php"); exit;
}
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    if (!admin_verify_csrf()) {
        $error = "Invalid form submission. Refresh the page.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id,full_name,email,password,role,status FROM admins WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($admin && $admin["status"] === "active" && password_verify($password, $admin["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = (int)$admin["admin_id"];
            $_SESSION["admin_name"] = $admin["full_name"];
            $_SESSION["admin_email"] = $admin["email"];
            $_SESSION["admin_role"] = $admin["role"];
            unset($_SESSION["admin_csrf_token"]);
            header("Location: admin-dashboard.php"); exit;
        }
        $error = "Invalid email or password, or this account is inactive.";
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | KAABAG</title><link rel="stylesheet" href="../css/admin.css"></head><body class="auth-page">
<section class="auth-card"><img class="brand-logo large" src="../images/kaabag-logo.jpg" alt="KAABAG company logo"><p class="eyebrow">KAABAG • HIMSOG TA!</p><h1>Administrator login</h1><p>Authorized clinic and KAABAG administrators only.</p>
<?php if ($error): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<?php admin_show_flash(); ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
<label>Email address<input type="email" name="email" autocomplete="username" required></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button class="btn" type="submit">Log in</button>
</form><p class="hint"><a href="../index.html">Return to HIMSOG TA! website</a></p></section></body></html>
