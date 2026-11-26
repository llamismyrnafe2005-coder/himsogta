<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_admin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!admin_verify_csrf()) {
        admin_flash("Invalid form submission.", "error");
    } else {
        $action = $_POST["action"] ?? "";
        if ($action === "save") {
            $id = (int)($_POST["service_id"] ?? 0);
            $name = trim($_POST["service_name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $active = isset($_POST["is_active"]) ? 1 : 0;
            if ($name === "" || strlen($name) > 150) {
                admin_flash("Enter a service name (maximum 150 characters).", "error");
            } elseif ($id > 0) {
                $stmt = $conn->prepare("UPDATE clinic_services SET service_name=?,description=?,is_active=? WHERE service_id=?");
                $stmt->bind_param("ssii", $name, $description, $active, $id);
                $stmt->execute(); $stmt->close(); admin_flash("Service updated.");
            } else {
                $stmt = $conn->prepare("INSERT INTO clinic_services (service_name,description,is_active) VALUES (?,?,?)");
                $stmt->bind_param("ssi", $name, $description, $active);
                $stmt->execute(); $stmt->close(); admin_flash("Service added.");
            }
        } elseif ($action === "delete") {
            $id = (int)($_POST["service_id"] ?? 0);
            $stmt = $conn->prepare("DELETE FROM clinic_services WHERE service_id=?");
            $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
            admin_flash("Service deleted.");
        }
    }
    header("Location: manage-services.php"); exit;
}
$edit = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM clinic_services WHERE service_id=?");
    $stmt->bind_param("i", $id); $stmt->execute(); $edit = $stmt->get_result()->fetch_assoc(); $stmt->close();
}
$result = $conn->query("SELECT * FROM clinic_services ORDER BY service_name");
admin_header("Manage clinic services");
?>
<div class="panel"><h2><?= $edit ? "Edit service" : "Add a service" ?></h2><form method="post" class="grid-form">
<input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="service_id" value="<?= (int)($edit["service_id"] ?? 0) ?>">
<label>Service name<input name="service_name" maxlength="150" required value="<?= admin_escape($edit["service_name"] ?? "") ?>"></label>
<label>Description<textarea name="description" rows="3"><?= admin_escape($edit["description"] ?? "") ?></textarea></label>
<label class="check"><input type="checkbox" name="is_active" value="1" <?= !isset($edit["is_active"]) || $edit["is_active"] ? "checked" : "" ?>> Active / visible</label>
<button class="btn">Save service</button><?php if ($edit): ?><a href="manage-services.php">Cancel edit</a><?php endif; ?></form></div>
<div class="panel table-wrap"><table><thead><tr><th>Service</th><th>Description</th><th>Visibility</th><th>Actions</th></tr></thead><tbody>
<?php if ($result && $result->num_rows): while ($row=$result->fetch_assoc()): ?><tr><td><?= admin_escape($row["service_name"]) ?></td><td><?= admin_escape($row["description"]) ?></td><td><?= $row["is_active"] ? "Active" : "Hidden" ?></td><td><a href="?edit=<?= (int)$row["service_id"] ?>">Edit</a><form method="post" class="inline-form" onsubmit="return confirm('Delete this service?')"><input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="service_id" value="<?= (int)$row["service_id"] ?>"><button class="link-button">Delete</button></form></td></tr><?php endwhile; else: ?><tr><td colspan="4">No services added yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_footer(); ?>
