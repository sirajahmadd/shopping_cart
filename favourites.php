<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle add to favourites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fav'], $_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    // Prevent duplicates
    $check = $conn->prepare("SELECT id FROM favourites WHERE user_id = ? AND product_id = ?");
    $check->bind_param('ii', $user_id, $product_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO favourites (user_id, product_id) VALUES (?, ?)");
        $insert->bind_param('ii', $user_id, $product_id);
        $insert->execute();
    }
    header('Location: favourites.php');
    exit();
}

// Handle remove from favourites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_fav'], $_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $delete = $conn->prepare("DELETE FROM favourites WHERE user_id = ? AND product_id = ?");
    $delete->bind_param('ii', $user_id, $product_id);
    $delete->execute();
    header('Location: favourites.php');
    exit();
}

// Fetch favourite products
$sql = "SELECT products.* FROM favourites JOIN products ON favourites.product_id = products.id WHERE favourites.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$fav_products = $stmt->get_result();

include 'header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Favourites</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
        /* Remove old .header-bar styles, keep only page-specific styles if needed */
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
    </style>
</head>
<body>
    <h1>Your Favourites</h1>
    <?php if ($fav_products->num_rows > 0): ?>
        <div class="product-list">
            <?php while ($prod = $fav_products->fetch_assoc()): ?>
                <div class="product-item">
                    <a href="product.php?id=<?php echo $prod['id']; ?>" style="text-decoration:none;color:inherit;">
                        <?php if ($prod['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" width="150">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
                    </a>
                    <p><?php echo htmlspecialchars($prod['description']); ?></p>
                    <p>Price: $<?php echo number_format($prod['price'], 2); ?></p>
                    <form method="post" action="favourites.php">
                        <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                        <button type="submit" name="remove_fav">Remove from Favourites</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;margin-top:60px;">
            <div style="font-size:1.3rem;color:#2c3e50;font-weight:500;margin-bottom:18px;">You have no favourite products yet.</div>
            <a href="index.php" style="display:inline-block;background:#3498db;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-weight:600;font-size:1.1rem;box-shadow:0 2px 8px rgba(44,62,80,0.08);transition:background 0.2s;">Browse Products</a>
        </div>
    <?php endif; ?>
</body>
</html> 