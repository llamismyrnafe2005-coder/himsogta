<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";

$countResult = $conn->query("SELECT COUNT(*) AS total FROM admins");
$adminCount = (int)$countResult->fetch_assoc()["total"];

if ($adminCount > 0) {
    header("Location: admin-login.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (!admin_verify_csrf()) {
        $error = "Invalid form submission. Refresh and try again.";
    } elseif ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid name and email address.";
    } elseif (strlen($password) < 12) {
        $error = "Use a password with at least 12 characters.";
    } elseif ($password !== $confirm) {
        $error = "The passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (full_name,email,password,role,status) VALUES (?, ?, ?, 'super_admin', 'active')");
        $stmt->bind_param("sss", $name, $email, $hash);
        $stmt->execute();
        $stmt->close();
        admin_flash("First Super Admin created. Please log in.");
        header("Location: admin-login.php");
        exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Initial Admin Setup | KAABAG</title><link rel="stylesheet" href="../css/admin.css"></head><body class="auth-page">
<section class="auth-card"><img class="brand-logo large" src="../images/kaabag-logo.jpg" alt="KAABAG company logo"><p class="eyebrow">KAABAG • HIMSOG TA!</p><h1>Set up Super Admin</h1>
<p>This one-time page creates the first administrator account. It becomes unavailable after the first admin is created.</p>
<?php if ($error): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
<label>Full name<input name="full_name" maxlength="150" required></label>
<label>Email address<input type="email" name="email" maxlength="150" required></label>
<label>Password (minimum 12 characters)<input type="password" name="password" minlength="12" required></label>
<label>Confirm password<input type="password" name="confirm_password" minlength="12" required></label>
<button class="btn" type="submit">Create Super Admin</button>
</form><p class="hint">Keep this account secure. Do not share the Super Admin password.</p></section></body></html>
