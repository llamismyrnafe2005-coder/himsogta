<?php
require_once __DIR__ . "/../config/admin-bootstrap.php";
require_admin();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!admin_verify_csrf()) {
        admin_flash("Invalid form submission.", "error");
    } else {
        $action = $_POST["action"] ?? "";
        if ($action === "save") {
            $id = (int)($_POST["announcement_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $body = trim($_POST["body"] ?? "");
            $active = isset($_POST["is_published"]) ? 1 : 0;
            if ($title === "" || $body === "") {
                admin_flash("Title and announcement details are required.", "error");
            } elseif ($id > 0) {
                $stmt = $conn->prepare("UPDATE clinic_announcements SET title=?,body=?,is_published=? WHERE announcement_id=?");
                $stmt->bind_param("ssii", $title, $body, $active, $id);
                $stmt->execute(); $stmt->close(); admin_flash("Announcement updated.");
            } else {
                $stmt = $conn->prepare("INSERT INTO clinic_announcements (title,body,is_published,created_by) VALUES (?,?,?,?)");
                $adminId = (int)$_SESSION["admin_id"];
                $stmt->bind_param("ssii", $title, $body, $active, $adminId);
                $stmt->execute(); $stmt->close(); admin_flash("Announcement posted.");
            }
        } elseif ($action === "delete") {
            $id = (int)($_POST["announcement_id"] ?? 0);
            $stmt = $conn->prepare("DELETE FROM clinic_announcements WHERE announcement_id=?");
            $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close(); admin_flash("Announcement deleted.");
        }
    }
    header("Location: announcements.php"); exit;
}
$edit = null;
if (isset($_GET["edit"])) {
    $id=(int)$_GET["edit"]; $stmt=$conn->prepare("SELECT * FROM clinic_announcements WHERE announcement_id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $edit=$stmt->get_result()->fetch_assoc(); $stmt->close();
}
$result=$conn->query("SELECT * FROM clinic_announcements ORDER BY created_at DESC");
admin_header("Clinic announcements");
?>
<div class="panel"><h2><?= $edit ? "Edit announcement" : "Create announcement" ?></h2><form method="post">
<input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="announcement_id" value="<?= (int)($edit["announcement_id"] ?? 0) ?>">
<label>Title<input name="title" maxlength="200" required value="<?= admin_escape($edit["title"] ?? "") ?>"></label>
<label>Announcement details<textarea name="body" rows="5" required><?= admin_escape($edit["body"] ?? "") ?></textarea></label>
<label class="check"><input type="checkbox" name="is_published" value="1" <?= !isset($edit["is_published"]) || $edit["is_published"] ? "checked" : "" ?>> Published / visible to students</label>
<button class="btn">Save announcement</button><?php if ($edit): ?> <a href="announcements.php">Cancel</a><?php endif; ?></form></div>
<div class="panel"><h2>Existing announcements</h2>
<?php if ($result && $result->num_rows): while ($row=$result->fetch_assoc()): ?>
<article class="announcement"><div><b><?= admin_escape($row["title"]) ?></b><p><?= nl2br(admin_escape($row["body"])) ?></p><small><?= $row["is_published"] ? "Published" : "Draft" ?> • <?= admin_escape($row["created_at"]) ?></small></div><div><a href="?edit=<?= (int)$row["announcement_id"] ?>">Edit</a><form method="post" class="inline-form" onsubmit="return confirm('Delete this announcement?')"><input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="announcement_id" value="<?= (int)$row["announcement_id"] ?>"><button class="link-button">Delete</button></form></div></article>
<?php endwhile; else: ?><p>No announcements yet.</p><?php endif; ?></div>
<?php admin_footer(); ?>
