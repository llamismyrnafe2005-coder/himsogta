
<?php
session_start();

require_once __DIR__ . "/config/database.php";

// Require student login
if (
    !isset($_SESSION["student_id"]) ||
    !isset($_SESSION["school_id"])
) {
    header("Location: login.php");
    exit;
}

// Student information from authenticated session
$studentName = $_SESSION["full_name"];
$schoolId = $_SESSION["school_id"];
$studentEmail = $_SESSION["email"];
$studentCollege = $_SESSION["college"];

$success = "";
$error = "";

// Allowed clinic services
$services = [
    "Prescription Refill",
    "Physical Examination",
    "Dental Services",
    "Psychological Services",
    "Guidance Counseling",
    "General Clinic Consultation"
];

// Allowed appointment times
$times = [
    "8:00 AM",
    "9:00 AM",
    "10:00 AM",
    "11:00 AM",
    "1:00 PM",
    "2:00 PM",
    "3:00 PM",
    "4:00 PM"
];

// Generate CSRF token
if (empty($_SESSION["appointment_csrf_token"])) {
    $_SESSION["appointment_csrf_token"] = bin2hex(random_bytes(32));
}

// Display successful submission message
if (isset($_SESSION["appointment_success"])) {
    $success = $_SESSION["appointment_success"];
    unset($_SESSION["appointment_success"]);
}

$selectedService = "";
$selectedDate = "";
$selectedTime = "";
$reason = "";

// Process appointment request
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selectedService = trim($_POST["service"] ?? "");
    $selectedDate = trim($_POST["date"] ?? "");
    $selectedTime = trim($_POST["time"] ?? "");
    $reason = trim($_POST["reason"] ?? "");
    $csrfToken = $_POST["csrf_token"] ?? "";

    $consent = isset($_POST["consent"]);

    // CSRF validation
    if (
        !is_string($csrfToken) ||
        !hash_equals(
            $_SESSION["appointment_csrf_token"],
            $csrfToken
        )
    ) {
        $error = "Invalid form submission. Please refresh the page.";

    } elseif (
        !in_array($selectedService, $services, true) ||
        !in_array($selectedTime, $times, true)
    ) {
        $error = "Please select a valid clinic service and appointment time.";

    } elseif (!$consent) {
        $error = "Please confirm the appointment demonstration notice.";

    } elseif (
        strlen($reason) > 2000 ||
        $reason === ""
    ) {
        $error = "Please provide a valid reason for your visit.";

    } else {

        // Validate date
        $dateObject = DateTime::createFromFormat(
            "!Y-m-d",
            $selectedDate
        );

        if (
            !$dateObject ||
            $dateObject->format("Y-m-d") !== $selectedDate ||
            $selectedDate < date("Y-m-d")
        ) {
            $error = "Please select a valid future appointment date.";

        } else {

            // Save appointment using authenticated student information
            $sql = "INSERT INTO clinic_appointments
                    (
                        student_name,
                        student_id,
                        email,
                        college,
                        service_name,
                        preferred_date,
                        preferred_time,
                        reason
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "ssssssss",
                    $studentName,
                    $schoolId,
                    $studentEmail,
                    $studentCollege,
                    $selectedService,
                    $selectedDate,
                    $selectedTime,
                    $reason
                );

                if ($stmt->execute()) {

                    $appointmentId = $conn->insert_id;

                    $_SESSION["appointment_success"] =
                        "Your appointment request was submitted successfully! Your request number is KBG-" .
                        $appointmentId .
                        ". Current status: Pending.";

                    // Prevent duplicate submissions
                    $_SESSION["appointment_csrf_token"] =
                        bin2hex(random_bytes(32));

                    $stmt->close();

                    header("Location: appointments.php");
                    exit;

                } else {
                    error_log("Appointment Error: " . $stmt->error);
                    $error = "Unable to save your appointment. Please try again.";
                }

                $stmt->close();

            } else {
                error_log("Appointment Prepare Error: " . $conn->error);
                $error = "A database error occurred. Please try again.";
            }
        }
    }
}

// Retrieve only this student's appointments
$history = [];

$historyStmt = $conn->prepare(
    "SELECT
        appointment_id,
        service_name,
        preferred_date,
        preferred_time,
        status,
        created_at
     FROM clinic_appointments
     WHERE student_id = ?
     ORDER BY created_at DESC"
);

if ($historyStmt) {

    $historyStmt->bind_param("s", $schoolId);
    $historyStmt->execute();

    $result = $historyStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $historyStmt->close();
}

// Escape output
function escapeOutput($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Appointments | KAABAG - HIMSOG TA!</title>

<link rel="stylesheet" href="css/style.css">
<script src="js/home.js" defer></script>

</head>

<body>

<header class="site-header">

    <a class="brand" href="index.html">

        <img class="brand-logo"
             src="images/kaabag-logo.jpg"
             alt="KAABAG company logo">

        <span>
            <strong>KAABAG</strong>
            <small>HIMSOG TA! • USTP</small>
        </span>

    </a>

    <button class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false">
        ☰
    </button>

    <nav class="main-nav" id="mainNav">

        <a href="index.html">Home</a>
        <a href="about.html">About Us</a>
        <a href="services.html">Our Services</a>
        <a href="clinic-info.html">Clinic Information</a>
        <a href="wellness.html">Health &amp; Wellness</a>
        <a class="active nav-appointment" href="appointments.php">
            Appointments
        </a>

        <a href="student-dashboard.php">My Account</a>

        <a href="logout.php">Log Out</a>

    </nav>

</header>

<main>

<section class="appointment-hero">

    <p class="eyebrow">
        <span></span> PLAN YOUR CLINIC VISIT
    </p>

    <h1>Request an <em>appointment.</em></h1>

    <p>
        Welcome, <?= escapeOutput($studentName) ?>!
        Request your preferred clinic service and schedule.
    </p>

</section>


<section class="appointment-layout">

    <div class="appointment-form-card">

        <div class="section-heading">
            <div>
                <p class="eyebrow blue">VERIFIED STUDENT ACCOUNT</p>
                <h2>Student Information</h2>
            </div>
        </div>

        <div class="form-notice">

            <strong>Account verified through login.</strong>

            Your appointment will automatically be associated
            with your registered student account.

        </div>

        <div class="student-info">

            <p>
                <strong>Full name:</strong>
                <?= escapeOutput($studentName) ?>
            </p>

            <p>
                <strong>Student ID:</strong>
                <?= escapeOutput($schoolId) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= escapeOutput($studentEmail) ?>
            </p>

            <p>
                <strong>College:</strong>
                <?= escapeOutput($studentCollege) ?>
            </p>

        </div>

        <div class="section-heading">
            <div>
                <p class="eyebrow blue">STUDENT APPOINTMENT REQUEST</p>
                <h2>Request a schedule</h2>
            </div>
        </div>

        <?php if ($success !== ""): ?>
            <div class="request-confirmation"
                 role="status"
                 aria-live="polite">
                <?= escapeOutput($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="form-notice"
                 role="alert"
                 aria-live="assertive">
                <strong>Error:</strong>
                <?= escapeOutput($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="appointments.php"
              class="appointment-form">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= escapeOutput($_SESSION["appointment_csrf_token"]) ?>">

            <label>
                Clinic service

                <select name="service" required>

                    <option value="">Select a service</option>

                    <?php foreach ($services as $item): ?>

                        <option
                            value="<?= escapeOutput($item) ?>"
                            <?= $selectedService === $item ? "selected" : "" ?>
                        >
                            <?= escapeOutput($item) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

            <div class="form-row">

                <label>
                    Preferred date

                    <input
                        type="date"
                        name="date"
                        min="<?= escapeOutput($today) ?>"
                        value="<?= escapeOutput($selectedDate) ?>"
                        required
                    >
                </label>

                <label>
                    Preferred time

                    <select name="time" required>

                        <option value="">Select a time</option>

                        <?php foreach ($times as $item): ?>

                            <option
                                value="<?= escapeOutput($item) ?>"
                                <?= $selectedTime === $item ? "selected" : "" ?>
                            >
                                <?= escapeOutput($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

            </div>

            <label>
                Reason for visit
                <span class="optional">(brief description)</span>

                <textarea
                    name="reason"
                    rows="4"
                    maxlength="2000"
                    placeholder="Briefly describe your concern."
                    required
                ><?= escapeOutput($reason) ?></textarea>

            </label>

            <label class="consent-line">

                <input type="checkbox"
                       name="consent"
                       value="yes"
                       required>

                <span>
                    I understand that this is a project demonstration.
                    My request will be saved in the KAABAG database
                    and is not directly sent to the actual USTP School Clinic.
                </span>

            </label>

            <button class="primary-button" type="submit">
                Submit appointment request <span>→</span>
            </button>

            <p class="form-small">
                Please do not use this form for emergencies.
                Seek immediate assistance for urgent concerns.
            </p>

        </form>

    </div>


    <aside class="appointment-side">

        <div class="side-info-card">
            <div class="side-icon">◷</div>
            <h3>Clinic hours</h3>
            <p>8:00 AM–5:00 PM</p>
            <small>Hours subject to confirmation.</small>
        </div>

        <div class="side-info-card">
            <div class="side-icon">⌖</div>
            <h3>Clinic location</h3>
            <p>Inside USTP campus, near Building 25.</p>
            <small>Confirm the exact location before visiting.</small>
        </div>

        <div class="side-info-card">
            <div class="side-icon">✓</div>
            <h3>Request status</h3>
            <p><b>Pending</b> → Approved or Rejected</p>
            <small>Your request requires clinic review.</small>
        </div>

    </aside>

</section>


<!-- STUDENT APPOINTMENT HISTORY -->

<section class="request-list-section">

    <div class="section-heading">
        <div>
            <p class="eyebrow blue">STUDENT ACCOUNT</p>
            <h2>My Appointment History</h2>
        </div>
    </div>

    <p class="request-list-note">
        These are the appointment requests associated
        with your registered student account.
    </p>

    <div class="request-list">

        <?php if (empty($history)): ?>

            <p class="empty-requests">
                You have not submitted any appointments yet.
            </p>

        <?php else: ?>

            <?php foreach ($history as $appointment): ?>

                <article class="request-item">

                    <div>

                        <strong>
                            <?= escapeOutput($appointment["service_name"]) ?>
                        </strong>

                        <small>
                            <?= escapeOutput($appointment["preferred_date"]) ?>
                            •
                            <?= escapeOutput($appointment["preferred_time"]) ?>
                        </small>

                        <small>
                            Request ID:
                            KBG-<?= escapeOutput($appointment["appointment_id"]) ?>
                        </small>

                    </div>

                    <span class="status-pending">
                        <?= escapeOutput($appointment["status"]) ?>
                    </span>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

</main>


<footer class="site-footer">

    <div class="footer-brand brand">

        <img class="brand-logo footer-logo"
             src="images/kaabag-logo.jpg"
             alt="KAABAG company logo">

        <span>
            <strong>KAABAG</strong>
            <small>HIMSOG TA! • USTP</small>
        </span>

    </div>

    <p>
        HIMSOG TA! is a proposed student-centered school clinic
        information and appointment platform by KAABAG.
    </p>

    <small>Local development prototype.</small>

</footer>

</body>
</html>
