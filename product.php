<?php
session_start();
include 'config.php';
include 'header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$product_id) {
    die('Product not found.');
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    die('Product not found.');
}

// Fetch available sizes
$sizes = [];
$size_result = $conn->query("SELECT s.id, s.name FROM sizes s JOIN product_sizes ps ON s.id = ps.size_id WHERE ps.product_id = $product_id");
while ($row = $size_result->fetch_assoc()) {
    $sizes[] = $row;
}

// Fetch available colors
$colors = [];
$color_result = $conn->query("SELECT c.id, c.name FROM colors c JOIN product_colors pc ON c.id = pc.color_id WHERE pc.product_id = $product_id");
while ($row = $color_result->fetch_assoc()) {
    $colors[] = $row;
}

// Fetch similar products (same category, exclude current)
$similar = [];
$cat_id = $product['category_id'];
$sim_result = $conn->query("SELECT * FROM products WHERE category_id = $cat_id AND id != $product_id LIMIT 4");
while ($row = $sim_result->fetch_assoc()) {
    $similar[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - Product Details</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { padding-top: 0 !important; }
        .product-detail-container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
            display: flex;
            gap: 40px;
        }
        .product-detail-img {
            width: 340px;
            height: 320px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.06);
        }
        .product-detail-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .product-detail-info h2 {
            margin-top: 0;
            font-size: 2rem;
            color: #2c3e50;
        }
        .product-detail-info p {
            color: #555;
            font-size: 1.08rem;
        }
        .product-detail-info .price {
            font-size: 1.3rem;
            color: #3498db;
            font-weight: 600;
            margin: 18px 0 18px 0;
        }
        .product-detail-info label {
            font-weight: 500;
            margin-right: 10px;
        }
        .product-detail-info select {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-right: 18px;
            font-size: 1rem;
        }
        .product-detail-info .actions {
            margin-top: 24px;
            display: flex;
            gap: 16px;
        }
        .product-detail-info .actions button, .product-detail-info .actions form button {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-detail-info .actions button:hover, .product-detail-info .actions form button:hover {
            background: #217dbb;
        }
        .similar-products {
            max-width: 900px;
            margin: 40px auto 0 auto;
        }
        .similar-products h3 {
            color: #2c3e50;
            margin-bottom: 18px;
        }
        .similar-list {
            display: flex;
            gap: 24px;
        }
        .similar-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            width: 180px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .similar-item img {
            width: 120px;
            height: 110px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .similar-item h4 {
            margin: 0 0 8px 0;
            font-size: 1.05rem;
            color: #2c3e50;
            text-align: center;
        }
        .similar-item p {
            font-size: 0.95rem;
            color: #555;
            text-align: center;
        }
        .similar-item a {
            color: #3498db;
            font-size: 0.98rem;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="product-detail-container">
        <img class="product-detail-img" src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <div class="product-detail-info">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="price">Price: $<?php echo number_format($product['price'], 2); ?></div>
            <?php if ($product['stock'] <= 0): ?>
                <div style="color: #e74c3c; font-weight: bold; margin: 10px 0;">Out of Stock</div>
                <div class="actions" style="display:flex;gap:16px;margin-top:24px;">
                    <button type="button" disabled style="background: #ccc; color: #fff; cursor: not-allowed;">Add to Cart</button>
                </div>
            <?php else: ?>
                <form method="post" action="cart.php" style="display:inline;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <label for="size">Size:</label>
                    <select name="size_id" id="size" required>
                        <option value="">Select Size</option>
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?php echo $size['id']; ?>"><?php echo htmlspecialchars($size['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="color">Color:</label>
                    <select name="color_id" id="color" required>
                        <option value="">Select Color</option>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="quantity">Qty:</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" style="width:50px;">
                    <div class="actions" style="display:flex;gap:16px;margin-top:24px;">
                        <button type="submit">Add to Cart</button>
                    </div>
                </form>
            <?php endif; ?>
            <div class="actions" style="display:flex;gap:16px;margin-top:24px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post" action="favourites.php" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="add_fav" style="background:#e74c3c;">&#10084; Add to Favourites</button>
                    </form>
                <?php else: ?>
                    <button style="background:#e74c3c; color:#fff; border:none; padding:10px 24px; border-radius:6px; font-size:1.08rem; font-weight:600; cursor:pointer;" onclick="favNotLoggedIn(event)">&#10084; Add to Favourites</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (count($similar) > 0): ?>
    <div class="similar-products">
        <h3>Similar Products</h3>
        <div class="similar-list">
            <?php foreach ($similar as $sim): ?>
                <div class="similar-item">
                    <a href="product.php?id=<?php echo $sim['id']; ?>" style="text-decoration:none;color:inherit;">
                        <img src="<?php echo htmlspecialchars($sim['image_url']); ?>" alt="<?php echo htmlspecialchars($sim['name']); ?>">
                        <h4><?php echo htmlspecialchars($sim['name']); ?></h4>
                    </a>
                    <p><?php echo htmlspecialchars($sim['description']); ?></p>
                    <div style="color:#3498db;font-weight:600;">$<?php echo number_format($sim['price'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <script>
    function favNotLoggedIn(e) {
        e.preventDefault();
        window.location.href = 'login.php';
    }
    </script>
</body>
</html> 