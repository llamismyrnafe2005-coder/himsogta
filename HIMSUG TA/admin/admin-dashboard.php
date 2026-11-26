<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_admin();

function table_count(mysqli $conn, string $table): int {
    // Table names below are fixed in this application, never user supplied.
    $allowed = ["students", "admins", "clinic_appointments", "clinic_services", "clinic_announcements"];
    if (!in_array($table, $allowed, true)) return 0;
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    return $result ? (int)$result->fetch_assoc()["total"] : 0;
}
$students = table_count($conn, "students");
$appointments = table_count($conn, "clinic_appointments");
$pendingResult = $conn->query("SELECT COUNT(*) AS total FROM clinic_appointments WHERE status='Pending'");
$pending = $pendingResult ? (int)$pendingResult->fetch_assoc()["total"] : 0;
$services = table_count($conn, "clinic_services");
$announcements = table_count($conn, "clinic_announcements");

admin_header("Admin dashboard");
?>
<p class="lead">Welcome, <?= admin_escape($_SESSION["admin_name"]) ?>. Manage the HIMSOG TA! clinic information and requests from this portal.</p>
<div class="stats">
<div class="stat"><span>Registered students</span><strong><?= $students ?></strong></div>
<div class="stat"><span>Appointment requests</span><strong><?= $appointments ?></strong></div>
<div class="stat"><span>Pending appointments</span><strong><?= $pending ?></strong></div>
<div class="stat"><span>Clinic services</span><strong><?= $services ?></strong></div>
<div class="stat"><span>Announcements</span><strong><?= $announcements ?></strong></div>
</div>
<section class="panel"><h2>Quick actions</h2><div class="quick-links">
<a class="btn" href="manage-appointments.php">Review appointments</a>
<a class="btn secondary" href="announcements.php">Post announcement</a>
<a class="btn secondary" href="manage-services.php">Manage services</a>
<?php if (($_SESSION["admin_role"] ?? "") === "super_admin"): ?><a class="btn secondary" href="manage-admins.php">Manage admin accounts</a><?php endif; ?>
</div></section>
<div class="notice">Prototype note: appointment requests are stored in <b>clinic_appointments</b>. The existing student-facing appointment form must be connected to this table before real student submissions appear here.</div>
<?php admin_footer(); ?>
