<?php
// Shared setup for the KAABAG administrator portal.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . "/database.php";

function admin_escape($value): string {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function admin_csrf_token(): string {
    if (empty($_SESSION["admin_csrf_token"])) {
        $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["admin_csrf_token"];
}

function admin_verify_csrf(): bool {
    $token = $_POST["csrf_token"] ?? "";
    return is_string($token)
        && !empty($_SESSION["admin_csrf_token"])
        && hash_equals($_SESSION["admin_csrf_token"], $token);
}

function require_admin(): void {
    if (empty($_SESSION["admin_id"])) {
        header("Location: admin-login.php");
        exit;
    }
}

function require_super_admin(): void {
    require_admin();
    if (($_SESSION["admin_role"] ?? "") !== "super_admin") {
        http_response_code(403);
        exit("Access denied. Only the KAABAG Super Admin can perform this action.");
    }
}

function admin_flash(string $message, string $type = "success"): void {
    $_SESSION["admin_flash"] = ["message" => $message, "type" => $type];
}

function admin_show_flash(): void {
    if (!empty($_SESSION["admin_flash"])) {
        $flash = $_SESSION["admin_flash"];
        unset($_SESSION["admin_flash"]);
        echo '<div class="notice ' . admin_escape($flash["type"]) . '" role="status">'
            . admin_escape($flash["message"]) . '</div>';
    }
}

function admin_header(string $title): void {
    $name = admin_escape($_SESSION["admin_name"] ?? "Administrator");
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . admin_escape($title) . ' | KAABAG HIMSOG TA!</title>';
    echo '<link rel="stylesheet" href="../css/admin.css"></head><body>';
    echo '<header class="topbar"><a class="brand" href="admin-dashboard.php">';
    echo '<img class="brand-logo" src="../images/kaabag-logo.jpg" alt="KAABAG company logo"><span><b>KAABAG</b><small>HIMSOG TA! ADMIN</small></span></a>';
    echo '<div class="top-user">' . $name . ' <span class="role">'
        . admin_escape(str_replace("_", " ", $_SESSION["admin_role"] ?? "")) . '</span></div></header>';
    echo '<div class="shell"><aside class="sidebar"><nav>';
    $links = [
        "admin-dashboard.php" => "Dashboard",
        "manage-appointments.php" => "Appointments",
        "manage-students.php" => "Students",
        "manage-services.php" => "Clinic services",
        "announcements.php" => "Announcements",
        "reports.php" => "Reports"
    ];
    foreach ($links as $href => $label) {
        echo '<a href="' . $href . '">' . $label . '</a>';
    }
    if (($_SESSION["admin_role"] ?? "") === "super_admin") {
        echo '<a href="manage-admins.php">Admin accounts</a>';
    }
    echo '<a href="admin-logout.php" class="logout-link">Log out</a>';
    echo '</nav></aside><main class="content">';
    admin_show_flash();
    echo '<h1>' . admin_escape($title) . '</h1>';
}

function admin_footer(): void {
    echo '</main></div><footer class="footer">KAABAG • HIMSOG TA! — Local project prototype</footer></body></html>';
}
