<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Add logic to handle delete_cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cart'])) {
    $cart_id = (int)$_POST['delete_cart'];
    $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete->bind_param('ii', $cart_id, $user_id);
    $delete->execute();
    header('Location: cart.php');
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    // Check if item already in cart
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param('ii', $user_id, $product_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_qty = $row['quantity'] + $quantity;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param('ii', $new_qty, $row['id']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param('iii', $user_id, $product_id, $quantity);
        $insert->execute();
    }
    header('Location: cart.php');
    exit();
}

// Handle update or remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $update->bind_param('iii', $qty, $cart_id, $user_id);
            $update->execute();
        } else {
            $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $delete->bind_param('ii', $cart_id, $user_id);
            $delete->execute();
        }
    }
    header('Location: cart.php');
    exit();
}

// Fetch cart items
$sql = "SELECT cart.id as cart_id, products.*, cart.quantity FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;

include 'header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
        .cart-container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
        }
        .cart-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 2rem;
            text-align: left;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .cart-table th, .cart-table td {
            border: 1px solid #ddd;
            padding: 12px 10px;
            text-align: left;
        }
        .cart-table th {
            background: #f0f0f0;
        }
        .cart-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .cart-table input[type="number"] {
            width: 48px;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 1rem;
            text-align: center;
        }
        .cart-action-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .cart-action-btn:hover {
            background: #c0392b;
        }
        .cart-update-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .cart-update-btn:hover {
            background: #217dbb;
        }
        .cart-checkout {
            text-align: right;
            margin-top: 18px;
        }
        .cart-checkout-link {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 12px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.2s;
        }
        .cart-checkout-link:hover {
            background: #217dbb;
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <h1 class="cart-title">Your Cart</h1>
        <?php if ($cart_items->num_rows > 0): ?>
            <form method="post">
            <table class="cart-table">
                <tr>
                    <th>Product</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
                <?php while ($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>
                        <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1">
                    </td>
                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                    <td>
                        <button type="submit" name="delete_cart" value="<?php echo $item['cart_id']; ?>" class="cart-action-btn">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="4" align="right"><strong>Total:</strong></td>
                    <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                    <td></td>
                </tr>
            </table>
            <button type="submit" name="update_cart" class="cart-update-btn">Update Cart</button>
            </form>
            <div class="cart-checkout">
                <a href="checkout.php" class="cart-checkout-link">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;margin-top:60px;">
                <div style="font-size:1.3rem;color:#2c3e50;font-weight:500;margin-bottom:18px;">Your cart is empty.</div>
                <a href="index.php" style="display:inline-block;background:#3498db;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-weight:600;font-size:1.1rem;box-shadow:0 2px 8px rgba(44,62,80,0.08);transition:background 0.2s;">Shop Now</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 