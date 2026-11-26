
<?php
session_start();

require_once __DIR__ . "/config/database.php";

// Escape output to prevent HTML injection.
function escapeOutput($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Initialize messages and form values.
$message = "";
$messageType = "";

$fullName = "";
$schoolId = "";
$email = "";
$college = "";
$yearLevel = "";

// Generate CSRF protection token.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Process registration form.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Retrieve submitted information.
    $fullName = trim($_POST["fullName"] ?? "");
    $schoolId = trim($_POST["studentId"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $college = trim($_POST["college"] ?? "");
    $yearLevel = trim($_POST["yearLevel"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";
    $csrfToken = $_POST["csrf_token"] ?? "";
    $consent = $_POST["prototypeConsent"] ?? "";

    // Validate CSRF token.
    if (
        !is_string($csrfToken) ||
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $csrfToken)
    ) {
        $message = "Invalid form submission. Please refresh the page.";
        $messageType = "error";

    // Validate prototype consent.
    } elseif ($consent !== "yes") {
        $message = "Please confirm the prototype agreement.";
        $messageType = "error";

    // Check required fields.
    } elseif (
        $fullName === "" ||
        $schoolId === "" ||
        $email === "" ||
        $college === "" ||
        $yearLevel === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $message = "Please complete all required fields.";
        $messageType = "error";

    // Check field lengths.
    } elseif (
        strlen($fullName) > 150 ||
        strlen($schoolId) > 50 ||
        strlen($email) > 150 ||
        strlen($college) > 100 ||
        strlen($yearLevel) > 30
    ) {
        $message = "One or more fields exceed the allowed length.";
        $messageType = "error";

    // Validate email.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    // Validate year level.
    } elseif (!in_array($yearLevel, [
        "1st Year",
        "2nd Year",
        "3rd Year",
        "4th Year",
        "5th Year",
        "Other"
    ], true)) {
        $message = "Please select a valid year level.";
        $messageType = "error";

    // Validate password length.
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = "Password must contain 8 to 72 characters.";
        $messageType = "error";

    // Check password confirmation.
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        try {

            // Check for existing email or student ID.
            $check = $conn->prepare(
                "SELECT student_id
                 FROM students
                 WHERE email = ? OR school_id = ?
                 LIMIT 1"
            );

            if (!$check) {
                throw new Exception("Database query preparation failed.");
            }

            $check->bind_param("ss", $email, $schoolId);
            $check->execute();

            $existingStudent = $check->get_result();
            $alreadyExists = ($existingStudent->num_rows > 0);

            $check->close();

            if ($alreadyExists) {

                $message = "This email address or student ID is already registered.";
                $messageType = "error";

            } else {

                // Securely hash the password.
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashedPassword === false) {
                    throw new Exception("Password hashing failed.");
                }

                // Insert student information into MySQL.
                $insert = $conn->prepare(
                    "INSERT INTO students
                    (
                        full_name,
                        school_id,
                        email,
                        college,
                        year_level,
                        password
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                if (!$insert) {
                    throw new Exception("Registration query preparation failed.");
                }

                $insert->bind_param(
                    "ssssss",
                    $fullName,
                    $schoolId,
                    $email,
                    $college,
                    $yearLevel,
                    $hashedPassword
                );

                $insert->execute();
                $insert->close();

                // Registration successful.
                $message = "Your account has been created successfully! You can now log in.";
                $messageType = "success";

                // Clear form fields.
                $fullName = "";
                $schoolId = "";
                $email = "";
                $college = "";
                $yearLevel = "";

                // Regenerate CSRF token.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
            }

        } catch (mysqli_sql_exception $e) {

            error_log("KAABAG Registration Error: " . $e->getMessage());

            if ((int)$e->getCode() === 1062) {
                $message = "This email address or student ID is already registered.";
            } else {
                $message = "Unable to complete registration. Please try again.";
            }

            $messageType = "error";

        } catch (Throwable $e) {

            error_log("KAABAG Error: " . $e->getMessage());

            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Create your student account at KAABAG HIMSOG TA!">

    <title>Student Registration | KAABAG - HIMSOG TA!</title>

    <link rel="stylesheet" href="css/style.css">

    <script src="js/home.js" defer></script>
</head>

<body>

<!-- HEADER -->
<header class="site-header">

    <a class="brand"
       href="index.html"
       aria-label="KAABAG HIMSOG TA home">

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
        <a href="appointments.php">Appointments</a>
        <a class="active" href="register.php">Student Account</a>

    </nav>

</header>


<!-- REGISTRATION SECTION -->
<main>

    <section class="account-section">

        <div class="account-card">

            <p class="eyebrow blue">
                JOIN HIMSOG TA!
            </p>

            <h1>Create your student account</h1>

            <p class="account-intro">
                Register to access the HIMSOG TA! student dashboard
                and clinic appointment-request features.
            </p>


            <!-- REGISTRATION FORM -->
            <form action="register.php"
                  method="POST"
                  class="account-form">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= escapeOutput($_SESSION["csrf_token"]) ?>">


                <!-- FULL NAME -->
                <label>
                    Full name

                    <input
                        type="text"
                        name="fullName"
                        placeholder="Enter your full name"
                        value="<?= escapeOutput($fullName) ?>"
                        maxlength="150"
                        autocomplete="name"
                        required>
                </label>


                <!-- STUDENT ID -->
                <label>
                    Student ID

                    <input
                        type="text"
                        name="studentId"
                        placeholder="Enter your student ID"
                        value="<?= escapeOutput($schoolId) ?>"
                        maxlength="50"
                        required>
                </label>


                <!-- EMAIL -->
                <label>
                    Email address

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?= escapeOutput($email) ?>"
                        maxlength="150"
                        autocomplete="email"
                        required>
                </label>


                <!-- COLLEGE AND YEAR LEVEL -->
                <div class="form-row">

                    <label>
                        College / Department

                        <input
                            type="text"
                            name="college"
                            placeholder="Enter your college"
                            value="<?= escapeOutput($college) ?>"
                            maxlength="100"
                            required>
                    </label>


                    <label>
                        Year level

                        <select name="yearLevel" required>

                            <option value="">
                                Select year
                            </option>

                            <?php
                            $years = [
                                "1st Year",
                                "2nd Year",
                                "3rd Year",
                                "4th Year",
                                "5th Year",
                                "Other"
                            ];

                            foreach ($years as $year):
                            ?>

                                <option
                                    value="<?= escapeOutput($year) ?>"
                                    <?= $yearLevel === $year ? "selected" : "" ?>>

                                    <?= escapeOutput($year) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </label>

                </div>


                <!-- PASSWORD -->
                <div class="form-row">

                    <label>
                        Password

                        <input
                            type="password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            required>
                    </label>


                    <label>
                        Confirm password

                        <input
                            type="password"
                            name="confirmPassword"
                            minlength="8"
                            maxlength="72"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            required>
                    </label>

                </div>


                <!-- CONSENT -->
                <label class="consent-line">

                    <input
                        type="checkbox"
                        name="prototypeConsent"
                        value="yes"
                        required>

                    <span>
                        I understand that this is a project prototype
                        and not an official USTP student account.
                    </span>

                </label>


                <!-- SUBMIT BUTTON -->
                <button class="primary-button" type="submit">

                    Create account <span>→</span>

                </button>


                <!-- FEEDBACK MESSAGE -->
                <?php if ($message !== ""): ?>

                    <p class="account-message <?= escapeOutput($messageType) ?>"
                       role="<?= $messageType === "success" ? "status" : "alert" ?>"
                       aria-live="polite">

                        <?= escapeOutput($message) ?>

                    </p>

                <?php endif; ?>

            </form>


            <!-- LOGIN LINK -->
            <p class="account-switch">

                Already registered?

                <a href="login.php">
                    Log in
                </a>

            </p>

        </div>

    </section>

</main>


<!-- FOOTER -->
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

    <small>
        Local development prototype.
        Confirm clinic information and procedures with USTP School Clinic.
    </small>

</footer>

</body>
</html>
