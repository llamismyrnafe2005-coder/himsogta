<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_super_admin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!admin_verify_csrf()) {
        admin_flash("Invalid form submission.", "error");
    } else {
        $action = $_POST["action"] ?? "";
        if ($action === "create") {
            $name = trim($_POST["full_name"] ?? "");
            $email = strtolower(trim($_POST["email"] ?? ""));
            $password = $_POST["password"] ?? "";
            $role = $_POST["role"] ?? "clinic_admin";
            if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                admin_flash("Enter a valid name and email.", "error");
            } elseif (strlen($password) < 12) {
                admin_flash("Admin passwords must contain at least 12 characters.", "error");
            } elseif (!in_array($role, ["super_admin", "clinic_admin"], true)) {
                admin_flash("Invalid administrator role.", "error");
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $conn->prepare("INSERT INTO admins (full_name,email,password,role,status) VALUES (?,?,?,?, 'active')");
                    $stmt->bind_param("ssss", $name, $email, $hash, $role);
                    $stmt->execute(); $stmt->close(); admin_flash("Administrator account created.");
                } catch (mysqli_sql_exception $e) {
                    admin_flash($e->getCode() === 1062 ? "That email is already in use." : "Could not create the account.", "error");
                }
            }
        } elseif ($action === "status") {
            $id = (int)($_POST["admin_id"] ?? 0);
            $status = $_POST["status"] ?? "";
            if ($id === (int)$_SESSION["admin_id"]) {
                admin_flash("You cannot deactivate your own account.", "error");
            } elseif ($id > 0 && in_array($status, ["active","inactive"], true)) {
                $stmt=$conn->prepare("UPDATE admins SET status=? WHERE admin_id=?");
                $stmt->bind_param("si",$status,$id); $stmt->execute(); $stmt->close(); admin_flash("Administrator status updated.");
            }
        }
    }
    header("Location: manage-admins.php"); exit;
}
$result=$conn->query("SELECT admin_id,full_name,email,role,status,created_at FROM admins ORDER BY created_at DESC");
admin_header("Admin accounts");
?>
<div class="panel"><h2>Create administrator</h2><p>Only the Super Admin can create administrator accounts. Use a unique email and share credentials securely.</p>
<form method="post" class="grid-form"><input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="create">
<label>Full name<input name="full_name" maxlength="150" required></label><label>Email<input type="email" name="email" maxlength="150" required></label>
<label>Temporary password (12+ characters)<input type="password" name="password" minlength="12" required></label>
<label>Role<select name="role"><option value="clinic_admin">Clinic Admin</option><option value="super_admin">Super Admin</option></select></label>
<button class="btn">Create account</button></form></div>
<div class="panel table-wrap"><h2>Existing administrators</h2><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if ($result && $result->num_rows): while ($row=$result->fetch_assoc()): ?><tr><td><?= admin_escape($row["full_name"]) ?></td><td><?= admin_escape($row["email"]) ?></td><td><?= admin_escape($row["role"]) ?></td><td><?= admin_escape($row["status"]) ?></td><td><?php if ((int)$row["admin_id"] !== (int)$_SESSION["admin_id"]): ?><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="admin_id" value="<?= (int)$row["admin_id"] ?>"><select name="status"><option value="active" <?= $row["status"]==="active"?"selected":"" ?>>Active</option><option value="inactive" <?= $row["status"]==="inactive"?"selected":"" ?>>Inactive</option></select><button class="btn small">Save</button></form><?php else: ?>Current account<?php endif; ?></td></tr><?php endwhile; else: ?><tr><td colspan="5">No admin accounts found.</td></tr><?php endif; ?></tbody></table></div>
<?php admin_footer(); ?>
