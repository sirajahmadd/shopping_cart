<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}
include 'config.php';

// Add color
if (isset($_POST['add']) && !empty($_POST['name'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("INSERT INTO colors (name) VALUES ('$name')");
    header('Location: admin_colors.php');
    exit();
}
// Edit color
if (isset($_POST['edit']) && isset($_POST['id']) && !empty($_POST['name'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("UPDATE colors SET name = '$name' WHERE id = $id");
    header('Location: admin_colors.php');
    exit();
}
// Delete color
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM colors WHERE id = $id");
    header('Location: admin_colors.php');
    exit();
}
// Fetch colors
$colors = $conn->query("SELECT * FROM colors ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Colors</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: #f7f7f7; }
        .admin-colors-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
        }
        h1, h2 { color: #2c3e50; margin-bottom: 18px; }
        .color-form input { margin-bottom: 10px; margin-right: 10px; border-radius: 6px; border: 1px solid #ccc; padding: 8px 12px; font-size: 1rem; background: #fafbfc; }
        .color-form button { padding: 8px 20px; border-radius: 6px; font-size: 1rem; font-weight: 600; border: none; margin-top: 8px; margin-right: 8px; background: #3498db; color: #fff; transition: background 0.2s; }
        .color-form button:hover { background: #217dbb; }
        .colors-table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 24px; }
        .colors-table th, .colors-table td { border: 1px solid #e0e0e0; padding: 8px 6px; text-align: left; }
        .colors-table th { background: #f0f0f0; color: #2c3e50; font-weight: 600; }
        .colors-table td input { width: 100%; box-sizing: border-box; border-radius: 4px; border: 1px solid #ccc; padding: 6px 8px; font-size: 1rem; background: #fafbfc; }
        .colors-table td.actions-col { width: 140px; }
        .action-btn { padding: 6px 16px; border-radius: 5px; font-size: 1rem; font-weight: 500; border: none; margin-right: 6px; cursor: pointer; transition: background 0.2s; }
        .edit-btn { background: #3498db; color: #fff; }
        .edit-btn:hover { background: #217dbb; }
        .delete-btn { background: #e74c3c; color: #fff; }
        .delete-btn:hover { background: #c0392b; }
        .save-btn { background: #27ae60; color: #fff; }
        .save-btn:hover { background: #219150; }
        .cancel-btn { background: #888; color: #fff; }
        .cancel-btn:hover { background: #555; }
    </style>
    <script>
    let editingRow = null;
    function startEdit(rowId) {
        if (editingRow !== null) cancelEdit(editingRow);
        editingRow = rowId;
        document.getElementById('row-display-' + rowId).style.display = 'none';
        document.getElementById('row-edit-' + rowId).style.display = '';
    }
    function cancelEdit(rowId) {
        document.getElementById('row-display-' + rowId).style.display = '';
        document.getElementById('row-edit-' + rowId).style.display = 'none';
        editingRow = null;
    }
    </script>
</head>
<body>
<div class="admin-colors-container">
    <h1>Manage Colors</h1>
    <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
    <h2>Add New Color</h2>
    <form method="post" class="color-form" style="display:flex;align-items:center;gap:10px;">
        <input type="text" name="name" placeholder="Color name" required>
        <button type="submit" name="add">Add</button>
    </form>
    <h2>All Colors</h2>
    <table class="colors-table">
        <tr><th>ID</th><th>Name</th><th class="actions-col">Actions</th></tr>
        <?php while ($color = $colors->fetch_assoc()): ?>
            <tr id="row-display-<?php echo $color['id']; ?>">
                <td><?php echo $color['id']; ?></td>
                <td><?php echo htmlspecialchars($color['name']); ?></td>
                <td>
                    <button type="button" class="action-btn edit-btn" onclick="startEdit(<?php echo $color['id']; ?>)">Edit</button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $color['id']; ?>">
                        <button type="submit" name="delete" class="action-btn delete-btn" onclick="return confirm('Delete this color?');">Delete</button>
                    </form>
                </td>
            </tr>
            <tr id="row-edit-<?php echo $color['id']; ?>" style="display:none;">
                <form method="post" class="color-form" style="display:flex;align-items:center;gap:6px;">
                    <td><?php echo $color['id']; ?><input type="hidden" name="id" value="<?php echo $color['id']; ?>"></td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($color['name']); ?>" required></td>
                    <td>
                        <button type="submit" name="edit" class="action-btn save-btn">Save</button>
                        <button type="button" class="action-btn cancel-btn" onclick="cancelEdit(<?php echo $color['id']; ?>)">Cancel</button>
                    </td>
                </form>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html> 