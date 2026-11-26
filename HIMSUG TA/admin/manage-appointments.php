
<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";

require_admin();

$message = "";

// Allowed appointment statuses
$allowedStatuses = [
    "Pending",
    "Approved",
    "Rejected",
    "Completed"
];

// Handle appointment status updates
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!admin_verify_csrf()) {

        admin_flash("Invalid form submission. Please refresh the page.", "error");
        header("Location: manage-appointments.php");
        exit;

    }

    $appointmentId = filter_input(
        INPUT_POST,
        "appointment_id",
        FILTER_VALIDATE_INT
    );

    $newStatus = trim($_POST["status"] ?? "");

    if (
        !$appointmentId ||
        !in_array($newStatus, $allowedStatuses, true)
    ) {

        admin_flash("Invalid appointment information.", "error");

    } else {

        try {

            // Retrieve current appointment status
            $check = $conn->prepare(
                "SELECT status
                 FROM clinic_appointments
                 WHERE appointment_id = ?
                 LIMIT 1"
            );

            $check->bind_param("i", $appointmentId);
            $check->execute();

            $appointment = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$appointment) {

                admin_flash("Appointment not found.", "error");

            } else {

                $currentStatus = $appointment["status"];

                // Only permit valid status transitions
                $validTransition =
                    ($currentStatus === "Pending" &&
                        in_array($newStatus, ["Approved", "Rejected"], true))
                    ||
                    ($currentStatus === "Approved" &&
                        $newStatus === "Completed");

                if (!$validTransition) {

                    admin_flash(
                        "This appointment cannot be changed from its current status.",
                        "error"
                    );

                } else {

                    $update = $conn->prepare(
                        "UPDATE clinic_appointments
                         SET status = ?
                         WHERE appointment_id = ?"
                    );

                    $update->bind_param(
                        "si",
                        $newStatus,
                        $appointmentId
                    );

                    if ($update->execute()) {

                        admin_flash(
                            "Appointment KBG-" . $appointmentId .
                            " successfully updated to " . $newStatus . "."
                        );

                    } else {

                        admin_flash(
                            "Unable to update appointment. Please try again.",
                            "error"
                        );

                    }

                    $update->close();
                }
            }

        } catch (Throwable $e) {

            error_log("KAABAG Appointment Update: " . $e->getMessage());

            admin_flash(
                "A database error occurred while updating the appointment.",
                "error"
            );

        }
    }

    header("Location: manage-appointments.php");
    exit;
}


// Search and filter
$search = trim($_GET["search"] ?? "");
$statusFilter = trim($_GET["status"] ?? "");

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

// Retrieve appointments
$sql = "
    SELECT
        appointment_id,
        student_name,
        student_id,
        email,
        college,
        service_name,
        preferred_date,
        preferred_time,
        reason,
        status,
        created_at
    FROM clinic_appointments
    WHERE (? = '' OR status = ?)
    AND (
        ? = ''
        OR student_name LIKE ?
        OR student_id LIKE ?
        OR email LIKE ?
    )
    ORDER BY
        CASE status
            WHEN 'Pending' THEN 1
            WHEN 'Approved' THEN 2
            WHEN 'Rejected' THEN 3
            WHEN 'Completed' THEN 4
            ELSE 5
        END,
        created_at DESC
";

$stmt = $conn->prepare($sql);

$searchTerm = "%" . $search . "%";

$stmt->bind_param(
    "ssssss",
    $statusFilter,
    $statusFilter,
    $search,
    $searchTerm,
    $searchTerm,
    $searchTerm
);

$stmt->execute();

$result = $stmt->get_result();

$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$stmt->close();


// Count appointment statuses
$counts = [
    "Pending" => 0,
    "Approved" => 0,
    "Rejected" => 0,
    "Completed" => 0
];

$countResult = $conn->query(
    "SELECT status, COUNT(*) AS total
     FROM clinic_appointments
     GROUP BY status"
);

if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        if (isset($counts[$row["status"]])) {
            $counts[$row["status"]] = (int)$row["total"];
        }
    }
}

$totalAppointments = array_sum($counts);

admin_header("Manage appointments");

?>

<style>
.appointment-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin: 22px 0;
}

.appointment-stat {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 12px;
    padding: 18px;
}

.appointment-stat span {
    display: block;
    font-size: 13px;
    color: #64748b;
}

.appointment-stat strong {
    display: block;
    font-size: 27px;
    margin-top: 7px;
}

.filter-panel {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: end;
    margin-bottom: 20px;
}

.filter-panel label {
    flex: 1;
    min-width: 180px;
}

.filter-panel input,
.filter-panel select {
    width: 100%;
    padding: 11px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    margin-top: 6px;
}

.appointment-table-wrap {
    overflow-x: auto;
}

.appointment-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.appointment-table th,
.appointment-table td {
    padding: 13px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    vertical-align: top;
    font-size: 14px;
}

.appointment-table th {
    background: #f8fafc;
}

.appointment-table small {
    display: block;
    color: #64748b;
    margin-top: 5px;
}

.status-label {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    background: #e5e7eb;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-approved {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-completed {
    background: #dbeafe;
    color: #1e40af;
}

.action-form {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.action-form button {
    border: 0;
    border-radius: 6px;
    padding: 8px 10px;
    cursor: pointer;
    font-weight: 600;
}

.btn-approve {
    background: #dcfce7;
    color: #166534;
}

.btn-reject {
    background: #fee2e2;
    color: #991b1b;
}

.btn-complete {
    background: #dbeafe;
    color: #1e40af;
}

.empty-result {
    text-align: center;
    padding: 35px;
    color: #64748b;
}

@media(max-width: 700px) {
    .appointment-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>


<p class="lead">
    Review student appointment requests and manage their schedules.
    All updates are saved directly to your KAABAG database.
</p>


<!-- STATISTICS -->

<div class="appointment-stats">

    <div class="appointment-stat">
        <span>Total appointments</span>
        <strong><?= $totalAppointments ?></strong>
    </div>

    <div class="appointment-stat">
        <span>Pending</span>
        <strong><?= $counts["Pending"] ?></strong>
    </div>

    <div class="appointment-stat">
        <span>Approved</span>
        <strong><?= $counts["Approved"] ?></strong>
    </div>

    <div class="appointment-stat">
        <span>Completed</span>
        <strong><?= $counts["Completed"] ?></strong>
    </div>

</div>


<!-- SEARCH AND FILTER -->

<section class="panel">

    <h2>Appointment requests</h2>

    <form method="GET" class="filter-panel">

        <label>
            Search student

            <input
                type="search"
                name="search"
                placeholder="Name, student ID, or email"
                value="<?= admin_escape($search) ?>"
            >
        </label>

        <label>
            Filter status

            <select name="status">

                <option value="">All statuses</option>

                <?php foreach ($allowedStatuses as $status): ?>

                    <option
                        value="<?= admin_escape($status) ?>"
                        <?= $statusFilter === $status ? "selected" : "" ?>
                    >
                        <?= admin_escape($status) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <button type="submit" class="btn">
            Search
        </button>

        <a href="manage-appointments.php" class="btn secondary">
            Reset
        </a>

    </form>


    <!-- APPOINTMENT TABLE -->

    <div class="appointment-table-wrap">

        <table class="appointment-table">

            <thead>

                <tr>
                    <th>Request</th>
                    <th>Student information</th>
                    <th>Clinic service</th>
                    <th>Preferred schedule</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($appointments)): ?>

                <tr>
                    <td colspan="6" class="empty-result">
                        No appointment requests found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($appointments as $item): ?>

                    <tr>

                        <td>
                            <strong>
                                KBG-<?= (int)$item["appointment_id"] ?>
                            </strong>

                            <small>
                                <?= admin_escape($item["created_at"]) ?>
                            </small>
                        </td>

                        <td>

                            <strong>
                                <?= admin_escape($item["student_name"]) ?>
                            </strong>

                            <small>
                                ID: <?= admin_escape($item["student_id"]) ?>
                            </small>

                            <small>
                                <?= admin_escape($item["email"]) ?>
                            </small>

                            <small>
                                <?= admin_escape($item["college"]) ?>
                            </small>

                        </td>

                        <td>

                            <?= admin_escape($item["service_name"]) ?>

                            <details>
                                <summary>View reason</summary>
                                <p><?= nl2br(admin_escape($item["reason"])) ?></p>
                            </details>

                        </td>

                        <td>

                            <?= admin_escape($item["preferred_date"]) ?>

                            <small>
                                <?= admin_escape($item["preferred_time"]) ?>
                            </small>

                        </td>

                        <td>

                            <span class="status-label status-<?= strtolower(admin_escape($item["status"])) ?>">
                                <?= admin_escape($item["status"]) ?>
                            </span>

                        </td>

                        <td>

                            <?php if ($item["status"] === "Pending"): ?>

                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= admin_escape(admin_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int)$item["appointment_id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="status"
                                        value="Approved"
                                        class="btn-approve"
                                        onclick="return confirm('Approve this appointment request?')"
                                    >
                                        Approve
                                    </button>

                                    <button
                                        type="submit"
                                        name="status"
                                        value="Rejected"
                                        class="btn-reject"
                                        onclick="return confirm('Reject this appointment request?')"
                                    >
                                        Reject
                                    </button>

                                </form>

                            <?php elseif ($item["status"] === "Approved"): ?>

                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= admin_escape(admin_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int)$item["appointment_id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="status"
                                        value="Completed"
                                        class="btn-complete"
                                        onclick="return confirm('Mark this appointment as completed?')"
                                    >
                                        Mark completed
                                    </button>

                                </form>

                            <?php else: ?>

                                <small>No further actions</small>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<div class="notice">
    <strong>Reminder:</strong>
    Approving an appointment means the administrator has reviewed
    the request. Please coordinate actual clinic scheduling with
    authorized clinic personnel.
</div>

<?php admin_footer(); ?>
