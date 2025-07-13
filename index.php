<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ...rest of your code
session_start();
include 'config.php';

// Get selected category from query string
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
// Get search query from query string
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch products, filtered by category and/or search if set
if ($selected_category && $search_query !== '') {
    $like = '%' . $conn->real_escape_string($search_query) . '%';
    $product_query = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND (name LIKE ? OR description LIKE ?) ORDER BY id ASC");
    $product_query->bind_param('iss', $selected_category, $like, $like);
    $product_query->execute();
    $result = $product_query->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else if ($selected_category) {
    $product_query = $conn->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY id ASC");
    $product_query->bind_param('i', $selected_category);
    $product_query->execute();
    $result = $product_query->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else if ($search_query !== '') {
    $like = '%' . $conn->real_escape_string($search_query) . '%';
    $product_query = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id ASC");
    $product_query->bind_param('ss', $like, $like);
    $product_query->execute();
    $result = $product_query->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $product_query = $conn->query("SELECT * FROM products ORDER BY id ASC");
    $products = [];
    while ($row = $product_query->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch user's favourites if logged in
$fav_ids = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_result = $conn->query("SELECT product_id FROM favourites WHERE user_id = $user_id");
    while ($row = $fav_result->fetch_assoc()) {
        $fav_ids[] = $row['product_id'];
    }
}
// Pass selected_category to header.php for highlighting
$active_category_id = $selected_category;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2c3e50;
            padding: 20px 40px;
            color: #fff;
        }
        .header-bar .logo {
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .header-bar .auth-links a {
            background: #3498db;
            color: #fff;
            padding: 8px 18px;
            border-radius: 4px;
            margin-left: 10px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .header-bar .auth-links a:hover {
            background: #217dbb;
            text-decoration: none;
        }
        .product-list {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin: 40px 0;
        }
        .product-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            width: 260px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s;
        }
        .product-item:hover {
            box-shadow: 0 4px 16px rgba(44,62,80,0.12);
        }
        .product-item img {
            width: 260px;
            height: 220px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .product-item h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        .product-item p {
            margin: 0 0 10px 0;
            color: #555;
            font-size: 0.98rem;
            line-height: 1.3;
        }
        .product-actions {
            display: flex;
            gap: 8px;
            width: 100%;
            justify-content: center;
            align-items: center;
        }
        .cart-form {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .qty-btn {
            width: 24px;
            height: 24px;
            font-size: 1.1rem;
            background: #eee;
            border: 1px solid #ccc;
            border-radius: 4px;
            color: #2c3e50;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .qty-btn:hover {
            background: #3498db;
            color: #fff;
        }
        .cart-form input[type="text"] {
            width: 18px;
            height: 20px;
            font-size: 0.95rem;
            text-align: center;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin: 0 2px;
            background: #fff;
            padding: 0;
        }
        .cart-form button[type="submit"] {
            padding: 6px 10px;
            font-size: 0.95rem;
            border-radius: 4px;
            background: #3498db;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            margin-left: 4px;
        }
        .cart-form button[type="submit"]:hover {
            background: #217dbb;
        }
        .fav-btn {
            background: none !important;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #bbb;
            padding: 0 6px;
            transition: color 0.2s;
            vertical-align: middle;
            box-shadow: none;
            outline: none;
        }
        .fav-btn.fav {
            color: #e74c3c;
            background: none !important;
        }
        .fav-btn:focus {
            outline: none;
            background: none !important;
        }
        .fav-btn:hover {
            color: #c0392b;
            background: none !important;
        }
    </style>
    <script>
    function changeQty(btn, delta) {
        var input = btn.parentNode.querySelector('.qty-input');
        var val = parseInt(input.value) || 1;
        val += delta;
        if (val < 1) val = 1;
        input.value = val;
    }
    function favNotLoggedIn(e) {
        e.preventDefault();
        window.location.href = 'login.php';
    }
    </script>
</head>
<body>
    <?php $active_category_id = isset($active_category_id) ? $active_category_id : 0; include 'header.php'; ?>
    
    <?php if ($search_query !== '' && empty($products)): ?>
        <div style="text-align: center; margin: 40px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; max-width: 600px; margin-left: auto; margin-right: auto;">
            <h3 style="color: #6c757d; margin-bottom: 10px;">No products found</h3>
            <p style="color: #6c757d; margin: 0;">No products found matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 0.9rem;">Try searching with different keywords or browse all products.</p>
            <a href="index.php" style="display: inline-block; margin-top: 15px; padding: 8px 16px; background: #3498db; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500;">View All Products</a>
        </div>
    <?php else: ?>
        <div class="product-list">
            <?php foreach ($products as $prod): ?>
                <div class="product-item">
                    <a href="product.php?id=<?php echo $prod['id']; ?>" style="text-decoration:none;color:inherit;">
                        <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                        <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
                    </a>
                    <p><?php echo htmlspecialchars($prod['description']); ?></p>
                    <p>Price: $<?php echo number_format($prod['price'], 2); ?></p>
                    <div class="product-actions">
                        <form class="cart-form" method="post" action="<?php echo isset($_SESSION['user_id']) ? 'cart.php' : 'login.php'; ?>" style="display:inline-flex;align-items:center;">
                            <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                            <button type="button" class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="text" name="quantity" value="1" class="qty-input" readonly>
                            <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                            <button type="submit">Add</button>
                        </form>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="post" action="favourites.php" style="display:inline-block;">
                            <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                            <button type="submit" name="<?php echo in_array($prod['id'], $fav_ids) ? 'remove_fav' : 'add_fav'; ?>" class="fav-btn<?php echo in_array($prod['id'], $fav_ids) ? ' fav' : ''; ?>" title="Favourite">
                                <?php if (in_array($prod['id'], $fav_ids)): ?>
                                    &#10084;
                                <?php else: ?>
                                    &#9825;
                                <?php endif; ?>
                            </button>
                        </form>
                        <?php else: ?>
                            <button class="fav-btn" onclick="favNotLoggedIn(event)" title="Login to add to wishlist">&#9825;</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html> 