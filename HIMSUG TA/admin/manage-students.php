<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_admin();
$result = $conn->query("SELECT student_id,full_name,school_id,email,college,year_level FROM students ORDER BY student_id DESC");
admin_header("Registered students");
?>
<p class="lead">View student accounts registered through the HIMSOG TA! student registration page. Passwords are never displayed.</p>
<div class="panel table-wrap"><table><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>College</th><th>Year level</th></tr></thead><tbody>
<?php if ($result && $result->num_rows): while ($row = $result->fetch_assoc()): ?>
<tr><td><?= admin_escape($row["full_name"]) ?></td><td><?= admin_escape($row["school_id"]) ?></td><td><?= admin_escape($row["email"]) ?></td><td><?= admin_escape($row["college"]) ?></td><td><?= admin_escape($row["year_level"]) ?></td></tr>
<?php endwhile; else: ?><tr><td colspan="5">No registered students found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_footer(); ?>
