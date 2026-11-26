
<?php
session_start();

require_once __DIR__ . "/config/database.php";

// Redirect students who are already logged in.
if (isset($_SESSION["student_id"])) {
    header("Location: student-dashboard.php");
    exit;
}

// Escape output.
function escapeOutput($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$message = "";
$email = "";

// Generate CSRF token.
if (empty($_SESSION["login_csrf_token"])) {
    $_SESSION["login_csrf_token"] = bin2hex(random_bytes(32));
}

// Process login form.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $csrfToken = $_POST["csrf_token"] ?? "";

    // Validate CSRF token.
    if (
        !is_string($csrfToken) ||
        empty($_SESSION["login_csrf_token"]) ||
        !hash_equals($_SESSION["login_csrf_token"], $csrfToken)
    ) {
        $message = "Invalid form submission. Please refresh the page.";

    } elseif ($email === "" || $password === "") {
        $message = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } else {

        try {

            // Find student account.
            $stmt = $conn->prepare(
                "SELECT
                    student_id,
                    full_name,
                    school_id,
                    email,
                    college,
                    year_level,
                    password
                 FROM students
                 WHERE email = ?
                 LIMIT 1"
            );

            if (!$stmt) {
                throw new Exception("Login query preparation failed.");
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            $stmt->close();

            // Verify account and password.
            if (
                $student &&
                password_verify($password, $student["password"])
            ) {

                // Prevent session fixation.
                session_regenerate_id(true);

                // Store student information in the session.
                $_SESSION["student_id"] = $student["student_id"];
                $_SESSION["full_name"] = $student["full_name"];
                $_SESSION["school_id"] = $student["school_id"];
                $_SESSION["email"] = $student["email"];
                $_SESSION["college"] = $student["college"];
                $_SESSION["year_level"] = $student["year_level"];

                // Remove login CSRF token.
                unset($_SESSION["login_csrf_token"]);

                // Redirect to student dashboard.
                header("Location: student-dashboard.php");
                exit;

            } else {

                $message = "Incorrect email or password. Please try again.";

            }

        } catch (Throwable $e) {

            error_log("KAABAG Login Error: " . $e->getMessage());

            $message = "Unable to process your login right now. Please try again.";

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
          content="Student login for KAABAG HIMSOG TA!">

    <title>Student Login | KAABAG - HIMSOG TA!</title>

    <link rel="stylesheet" href="css/style.css">

    <script src="js/home.js" defer></script>

</head>

<body>

<!-- HEADER -->
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

        <a href="appointments.php">Appointments</a>

        <a class="nav-appointment active"
           href="login.php">
            Student Account
        </a>

    </nav>

</header>


<!-- LOGIN SECTION -->
<main class="account-page">

    <section class="account-card">

        <div class="account-brand">

            <img src="images/kaabag-logo.jpg"
                 alt="KAABAG logo">

            <p class="eyebrow blue">
                WELCOME BACK, TRAILBLAZER
            </p>

            <h1>Student Login</h1>

            <p>
                Sign in to access your HIMSOG TA! student dashboard
                and manage your clinic appointment requests.
            </p>

        </div>


        <!-- LOGIN FORM -->
        <form action="login.php"
              method="POST"
              class="account-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeOutput($_SESSION["login_csrf_token"]) ?>">


            <!-- EMAIL -->
            <label>

                Email address

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your registered email"
                    value="<?= escapeOutput($email) ?>"
                    autocomplete="username"
                    maxlength="150"
                    required>

            </label>


            <!-- PASSWORD -->
            <label>

                Password

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>

            </label>


            <!-- SUBMIT -->
            <button class="primary-button" type="submit">

                Log in <span>→</span>

            </button>


            <!-- ERROR MESSAGE -->
            <?php if ($message !== ""): ?>

                <p class="account-message error"
                   role="alert"
                   aria-live="polite">

                    <?= escapeOutput($message) ?>

                </p>

            <?php endif; ?>

        </form>


        <!-- REGISTER LINK -->
        <p class="account-switch">

            Don't have an account?

            <a href="register.php">
                Create one
            </a>

        </p>

        <p class="account-switch">

            <a href="admin.html">
                Clinic Admin Demo
            </a>

        </p>

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
    </small>

</footer>

</body>
</html>
