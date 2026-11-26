<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/database.php";

$studentId = (int) $_SESSION["student_id"];
$student = null;
$appointments = [];
$errorMessage = "";

try {
    $studentStmt = $conn->prepare(
        "SELECT student_id, full_name, school_id, email, college, year_level
         FROM students WHERE student_id = ? LIMIT 1"
    );

    if (!$studentStmt) {
        throw new Exception("Unable to prepare student profile query.");
    }

    $studentStmt->bind_param("i", $studentId);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $student = $studentResult->fetch_assoc();
    $studentStmt->close();

    if (!$student) {
        $_SESSION = [];
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $appointmentStmt = $conn->prepare(
        "SELECT a.appointment_id, a.appointment_date, a.appointment_time,
                a.reason, a.status, s.service_name
         FROM appointments a
         INNER JOIN services s ON a.service_id = s.service_id
         WHERE a.student_id = ?
         ORDER BY a.appointment_date DESC, a.appointment_time DESC"
    );

    if (!$appointmentStmt) {
        throw new Exception("Unable to prepare appointment history query.");
    }

    $appointmentStmt->bind_param("i", $studentId);
    $appointmentStmt->execute();
    $appointmentResult = $appointmentStmt->get_result();

    while ($row = $appointmentResult->fetch_assoc()) {
        $appointments[] = $row;
    }

    $appointmentStmt->close();
} catch (Throwable $e) {
    // Keep technical database details out of the student-facing page.
    error_log("KAABAG dashboard error: " . $e->getMessage());
    $errorMessage = "We couldn't load some dashboard information. Please refresh the page or try again later.";
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function statusClass($status) {
    switch ($status) {
        case "Approved": return "approved";
        case "Rejected": return "rejected";
        default: return "pending";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | HIMSOG TA!</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root { --navy:#123b67; --blue:#2875b9; --yellow:#ffd447; --pale:#f3f8fd; --ink:#203247; }
        body { margin:0; background:var(--pale); color:var(--ink); font-family:Arial, sans-serif; }
        .dash-nav { background:#fff; display:flex; justify-content:space-between; align-items:center; gap:16px; padding:14px 5%; box-shadow:0 2px 12px #173b6012; }
        .brand { display:flex; align-items:center; gap:11px; color:var(--navy); text-decoration:none; font-weight:800; }
        .brand img { width:48px; height:48px; object-fit:contain; }
        .brand small { display:block; color:#64748b; font-size:11px; letter-spacing:1px; }
        .nav-links { display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
        .nav-links a { color:var(--navy); text-decoration:none; font-weight:600; font-size:14px; }
        .logout-btn { border:0; border-radius:22px; background:var(--yellow); color:var(--navy); padding:10px 18px; font-weight:700; cursor:pointer; }
        .dash-wrap { width:min(1100px, 90%); margin:34px auto 60px; }
        .welcome { background:linear-gradient(120deg,#123b67,#2875b9); color:white; padding:30px; border-radius:20px; display:flex; justify-content:space-between; gap:20px; align-items:center; }
        .welcome h1 { margin:0 0 8px; font-size:clamp(25px,4vw,34px); }
        .welcome p { margin:0; color:#e5f1ff; line-height:1.6; }
        .welcome-tag { background:var(--yellow); color:var(--navy); padding:10px 15px; border-radius:30px; font-weight:800; white-space:nowrap; }
        .section-title { color:var(--navy); margin:30px 0 14px; font-size:21px; }
        .profile-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
        .info-card, .history-card, .quick-card { background:white; border-radius:15px; padding:19px; box-shadow:0 4px 18px #173b600b; }
        .info-card span { display:block; color:#718096; font-size:12px; margin-bottom:7px; }
        .info-card strong { color:var(--navy); overflow-wrap:anywhere; }
        .quick-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .quick-card { display:flex; justify-content:space-between; align-items:center; gap:14px; text-decoration:none; color:var(--navy); border:1px solid #e6eef7; }
        .quick-card strong { display:block; margin-bottom:5px; }
        .quick-card span { color:#718096; font-size:13px; line-height:1.5; }
        .quick-icon { background:#fff4c6; width:43px; height:43px; display:grid; place-items:center; border-radius:13px; font-size:21px; flex-shrink:0; }
        .history-card { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:650px; }
        th,td { text-align:left; padding:13px 12px; border-bottom:1px solid #edf1f5; font-size:14px; }
        th { color:#60758c; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
        .status { display:inline-block; padding:6px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        .pending { background:#fff3cd; color:#8a6500; }
        .approved { background:#d9f5e5; color:#176b3b; }
        .rejected { background:#fde2e2; color:#a12828; }
        .notice { background:#fff8df; border-left:4px solid var(--yellow); padding:13px 16px; border-radius:8px; margin:18px 0; color:#624d13; font-size:14px; }
        .error { background:#fff0f0; border:1px solid #f4caca; color:#9d2525; padding:13px 16px; border-radius:10px; margin:18px 0; }
        .empty { text-align:center; padding:28px 12px; color:#718096; }
        footer { text-align:center; color:#718096; font-size:13px; padding:24px; }
        @media(max-width:720px) {
            .dash-nav { align-items:flex-start; flex-direction:column; }
            .nav-links { gap:12px; }
            .welcome { align-items:flex-start; flex-direction:column; }
            .profile-grid { grid-template-columns:1fr 1fr; }
            .quick-grid { grid-template-columns:1fr; }
        }
        @media(max-width:440px) { .profile-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<header class="dash-nav">
    <a class="brand" href="index.html">
        <img src="images/kaabag-logo.jpg" alt="KAABAG logo">
        <span>KAABAG <small>HIMSOG TA!</small></span>
    </a>
    <nav class="nav-links" aria-label="Main navigation">
        <a href="index.html">Home</a>
        <a href="services.html">Clinic Services</a>
        <a href="clinic-info.html">Clinic Information</a>
        <form action="logout.php" method="POST" style="margin:0">
            <input type="hidden" name="logout_token" value="<?= h($_SESSION["logout_token"]) ?>">
            <button class="logout-btn" type="submit">Log Out</button>
        </form>
    </nav>
</header>

<main class="dash-wrap">
    <?php if ($errorMessage): ?>
        <div class="error"><?= h($errorMessage) ?></div>
    <?php endif; ?>

    <section class="welcome">
        <div>
            <h1>Hello, <?= h($student["full_name"] ?? $_SESSION["student_name"] ?? "Trailblazer") ?>!</h1>
            <p>Welcome to your HIMSOG TA! student dashboard.<br>Here you can review your account and keep track of your clinic appointment requests.</p>
        </div>
        <div class="welcome-tag">USTP Trailblazer</div>
    </section>

    <h2 class="section-title">My Student Information</h2>
    <?php if ($student): ?>
        <section class="profile-grid">
            <div class="info-card"><span>Full Name</span><strong><?= h($student["full_name"]) ?></strong></div>
            <div class="info-card"><span>Student ID</span><strong><?= h($student["school_id"]) ?></strong></div>
            <div class="info-card"><span>Email Address</span><strong><?= h($student["email"]) ?></strong></div>
            <div class="info-card"><span>College</span><strong><?= h($student["college"]) ?></strong></div>
            <div class="info-card"><span>Year Level</span><strong><?= h($student["year_level"] ?: "Not provided") ?></strong></div>
            <div class="info-card"><span>Account Type</span><strong>Student Account</strong></div>
        </section>
    <?php endif; ?>

    <h2 class="section-title">Quick Access</h2>
    <section class="quick-grid">
        <a class="quick-card" href="appointments.php">
            <div><strong>Clinic Appointment</strong><span>View the appointment page and request a clinic schedule.</span></div>
            <div class="quick-icon" aria-hidden="true">📅</div>
        </a>
        <a class="quick-card" href="services.html">
            <div><strong>Explore Clinic Services</strong><span>Learn about the services listed on HIMSOG TA!</span></div>
            <div class="quick-icon" aria-hidden="true">🩺</div>
        </a>
    </section>

    <h2 class="section-title">My Appointment History</h2>
    <div class="notice">Appointment requests are subject to clinic review and confirmation. A status of “Pending” means your request has not yet been finalized.</div>
    <section class="history-card">
        <?php if (!$appointments): ?>
            <div class="empty">
                <strong>No appointment records yet.</strong>
                <p>Your appointment requests will appear here once they are submitted.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Service</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= h($appointment["service_name"]) ?></td>
                        <td><?= h(date("M d, Y", strtotime($appointment["appointment_date"]))) ?></td>
                        <td><?= h(date("h:i A", strtotime($appointment["appointment_time"]))) ?></td>
                        <td><?= h($appointment["reason"] ?: "—") ?></td>
                        <td><span class="status <?= h(statusClass($appointment["status"])) ?>"><?= h($appointment["status"]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<footer>© <?= date("Y") ?> KAABAG | HIMSOG TA! — Student Clinic Information and Appointment Platform</footer>
</body>
</html>
