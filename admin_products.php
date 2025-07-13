<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}
include 'config.php';

$error_message = '';

// Fetch categories for dropdown
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $cat_result->fetch_assoc()) {
    $categories[$row['id']] = $row['name'];
}

// Fetch all sizes and colors for the form
$all_sizes = [];
$size_result = $conn->query("SELECT * FROM sizes ORDER BY name ASC");
while ($row = $size_result->fetch_assoc()) {
    $all_sizes[] = $row;
}
$all_colors = [];
$color_result = $conn->query("SELECT * FROM colors ORDER BY name ASC");
while ($row = $color_result->fetch_assoc()) {
    $all_colors[] = $row;
}

// Add product
if (isset($_POST['add']) && !empty($_POST['name']) && isset($_POST['category_id'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $category_id = (int)$_POST['category_id'];
    $conn->query("INSERT INTO products (name, description, price, image_url, category_id) VALUES ('$name', '$desc', $price, '$image_url', $category_id)");
    $product_id = $conn->insert_id;
    // Insert sizes
    if (!empty($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $size_id) {
            $conn->query("INSERT INTO product_sizes (product_id, size_id) VALUES ($product_id, " . (int)$size_id . ")");
        }
    }
    // Insert colors
    if (!empty($_POST['colors'])) {
        foreach ($_POST['colors'] as $color_id) {
            $conn->query("INSERT INTO product_colors (product_id, color_id) VALUES ($product_id, " . (int)$color_id . ")");
        }
    }
    header('Location: admin_products.php');
    exit();
}

// Edit product
if (isset($_POST['edit']) && isset($_POST['id']) && !empty($_POST['name']) && isset($_POST['category_id'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $category_id = (int)$_POST['category_id'];
    $conn->query("UPDATE products SET name = '$name', description = '$desc', price = $price, image_url = '$image_url', category_id = $category_id WHERE id = $id");
    // Update sizes
    $conn->query("DELETE FROM product_sizes WHERE product_id = $id");
    if (!empty($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $size_id) {
            $conn->query("INSERT INTO product_sizes (product_id, size_id) VALUES ($id, " . (int)$size_id . ")");
        }
    }
    // Update colors
    $conn->query("DELETE FROM product_colors WHERE product_id = $id");
    if (!empty($_POST['colors'])) {
        foreach ($_POST['colors'] as $color_id) {
            $conn->query("INSERT INTO product_colors (product_id, color_id) VALUES ($id, " . (int)$color_id . ")");
        }
    }
    header('Location: admin_products.php');
    exit();
}

// Delete product
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    // Check for order_items referencing this product
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE product_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    if ($row['cnt'] > 0) {
        $error_message = 'Cannot delete product: it has been ordered by a user.';
    } else {
        $conn->query("DELETE FROM products WHERE id = $id");
        header('Location: admin_products.php');
        exit();
    }
}

// Fetch products
$products = $conn->query("SELECT * FROM products ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: #f7f7f7;
        }
        .admin-products-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 18px;
        }
        .product-form input, .product-form select, .product-form .multi-select {
            margin-bottom: 10px;
            margin-right: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            font-size: 1rem;
            background: #fafbfc;
        }
        .product-form .multi-select {
            min-width: 120px;
            min-height: 38px;
        }
        .product-form label {
            font-weight: 500;
            margin-right: 8px;
        }
        .product-form button {
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            margin-top: 8px;
            margin-right: 8px;
            background: #3498db;
            color: #fff;
            transition: background 0.2s;
        }
        .product-form button:hover {
            background: #217dbb;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 24px;
        }
        .products-table th, .products-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 6px;
            text-align: left;
        }
        .products-table th {
            background: #f0f0f0;
            color: #2c3e50;
            font-weight: 600;
        }
        .products-table td input, .products-table td select, .products-table td .multi-select {
            width: 100%;
            box-sizing: border-box;
            border-radius: 4px;
            border: 1px solid #ccc;
            padding: 6px 8px;
            font-size: 1rem;
            background: #fafbfc;
        }
        .products-table td select, .products-table td .multi-select {
            min-width: 80px;
        }
        .products-table td.price-col { width: 80px; }
        .products-table td.image-col { width: 180px; }
        .products-table td.cat-col { width: 120px; }
        .products-table td.actions-col { width: 140px; }
        .action-btn-group {
            display: flex;
            gap: 6px;
        }
        .action-btn {
            padding: 6px 16px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
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
<div class="admin-products-container">
    <h1>Manage Products</h1>
    <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
    <h2>Add New Product</h2>
    <form method="post" class="product-form" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
        <input type="text" name="name" placeholder="Product name" required>
        <input type="text" name="description" placeholder="Description">
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <input type="text" name="image_url" placeholder="Image URL">
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach (
                $categories as $cat_id => $cat_name): ?>
                <option value="<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></option>
            <?php endforeach; ?>
        </select>
        <div style="width:100%;display:flex;flex-wrap:wrap;align-items:flex-start;gap:24px;">
            <div style="display:flex;flex-direction:column;">
                <label style="margin-bottom:4px;">Sizes:</label>
                <select name="sizes[]" class="multi-select" multiple>
                    <?php foreach ($all_sizes as $size): ?>
                        <option value="<?php echo $size['id']; ?>"><?php echo htmlspecialchars($size['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;">
                <label style="margin-bottom:4px;">Colors:</label>
                <select name="colors[]" class="multi-select" multiple>
                    <?php foreach ($all_colors as $color): ?>
                        <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add">Add</button>
    </form>
    <h2>All Products</h2>
    <table class="products-table">
        <tr><th>ID</th><th>Name</th><th>Description</th><th class="price-col">Price</th><th class="image-col">Image URL</th><th class="cat-col">Category</th><th>Sizes</th><th>Colors</th><th class="actions-col">Actions</th></tr>
        <?php
        // Helper to get sizes/colors for a product
        function get_product_sizes($conn, $pid) {
            $res = $conn->query("SELECT s.id, s.name FROM sizes s JOIN product_sizes ps ON s.id = ps.size_id WHERE ps.product_id = $pid");
            $out = [];
            while ($row = $res->fetch_assoc()) $out[] = $row;
            return $out;
        }
        function get_product_colors($conn, $pid) {
            $res = $conn->query("SELECT c.id, c.name FROM colors c JOIN product_colors pc ON c.id = pc.color_id WHERE pc.product_id = $pid");
            $out = [];
            while ($row = $res->fetch_assoc()) $out[] = $row;
            return $out;
        }
        while ($prod = $products->fetch_assoc()):
            $sizes = get_product_sizes($conn, $prod['id']);
            $colors = get_product_colors($conn, $prod['id']);
            $size_ids = array_map(function($s){return $s['id'];}, $sizes);
            $color_ids = array_map(function($c){return $c['id'];}, $colors);
        ?>
            <tr id="row-display-<?php echo $prod['id']; ?>">
                <td><?php echo $prod['id']; ?></td>
                <td><?php echo htmlspecialchars($prod['name']); ?></td>
                <td><?php echo htmlspecialchars($prod['description']); ?></td>
                <td><?php echo $prod['price']; ?></td>
                <td><?php echo htmlspecialchars($prod['image_url']); ?></td>
                <td><?php echo isset($categories[$prod['category_id']]) ? htmlspecialchars($categories[$prod['category_id']]) : ''; ?></td>
                <td><?php echo implode(', ', array_map(function($s){return $s['name'];}, $sizes)); ?></td>
                <td><?php echo implode(', ', array_map(function($c){return $c['name'];}, $colors)); ?></td>
                <td class="actions-col">
                    <div class="action-btn-group">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                            <button type="button" class="action-btn edit-btn" onclick="startEdit(<?php echo $prod['id']; ?>)">Edit</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                            <button type="submit" name="delete" class="action-btn delete-btn" onclick="return confirm('Delete this product?');">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <tr id="row-edit-<?php echo $prod['id']; ?>" style="display:none;">
                <form method="post" class="product-form" style="display:flex;align-items:center;gap:6px;">
                    <td><?php echo $prod['id']; ?><input type="hidden" name="id" value="<?php echo $prod['id']; ?>"></td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($prod['name']); ?>" required></td>
                    <td><input type="text" name="description" value="<?php echo htmlspecialchars($prod['description']); ?>"></td>
                    <td><input type="number" step="0.01" name="price" value="<?php echo $prod['price']; ?>" required></td>
                    <td><input type="text" name="image_url" value="<?php echo htmlspecialchars($prod['image_url']); ?>"></td>
                    <td>
                        <select name="category_id" required>
                            <?php foreach ($categories as $cat_id => $cat_name): ?>
                                <option value="<?php echo $cat_id; ?>" <?php if ($prod['category_id'] == $cat_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="sizes[]" class="multi-select" multiple>
                            <?php foreach ($all_sizes as $size): ?>
                                <option value="<?php echo $size['id']; ?>" <?php if (in_array($size['id'], $size_ids)) echo 'selected'; ?>><?php echo htmlspecialchars($size['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="colors[]" class="multi-select" multiple>
                            <?php foreach ($all_colors as $color): ?>
                                <option value="<?php echo $color['id']; ?>" <?php if (in_array($color['id'], $color_ids)) echo 'selected'; ?>><?php echo htmlspecialchars($color['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button type="submit" name="edit" class="action-btn save-btn">Save</button>
                        <button type="button" class="action-btn cancel-btn" onclick="cancelEdit(<?php echo $prod['id']; ?>)">Cancel</button>
                    </td>
                </form>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html> 