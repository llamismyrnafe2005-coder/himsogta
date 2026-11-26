<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_admin();
$counts = [];
foreach (["Pending","Approved","Rejected","Completed","Cancelled"] as $status) {
    $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM clinic_appointments WHERE status=?");
    $stmt->bind_param("s",$status); $stmt->execute();
    $counts[$status]=(int)$stmt->get_result()->fetch_assoc()["total"]; $stmt->close();
}
admin_header("Reports");
?>
<p class="lead">Appointment request summary from the KAABAG clinic appointment records.</p>
<div class="stats"><?php foreach ($counts as $status=>$count): ?><div class="stat"><span><?= admin_escape($status) ?></span><strong><?= $count ?></strong></div><?php endforeach; ?></div>
<div class="panel"><h2>Appointment status summary</h2><table><thead><tr><th>Status</th><th>Number of requests</th></tr></thead><tbody><?php foreach ($counts as $status=>$count): ?><tr><td><?= admin_escape($status) ?></td><td><?= $count ?></td></tr><?php endforeach; ?></tbody></table></div>
<p class="hint">These figures update as requests are submitted and their statuses are changed.</p>
<?php admin_footer(); ?>
