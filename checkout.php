<?php
session_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle add address
if (isset($_POST['add_address'])) {
    $city = $conn->real_escape_string($_POST['city']);
    $pincode = $conn->real_escape_string($_POST['pincode']);
    $address = $conn->real_escape_string($_POST['address']);
    $conn->query("INSERT INTO addresses (user_id, city, pincode, address) VALUES ($user_id, '$city', '$pincode', '$address')");
}

// Handle delete address
if (isset($_POST['delete_address']) && isset($_POST['address_id'])) {
    $address_id = (int)$_POST['address_id'];
    $conn->query("DELETE FROM addresses WHERE id = $address_id AND user_id = $user_id");
}

// Fetch addresses
$addresses = [];
$addr_result = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY id DESC");
while ($row = $addr_result->fetch_assoc()) {
    $addresses[] = $row;
}

// Fetch cart items
$sql = "SELECT cart.id as cart_id, products.*, cart.quantity FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;
$items = [];
while ($item = $cart_items->fetch_assoc()) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    $items[] = $item;
}

// Place order with selected address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && $total > 0 && isset($_POST['address_id'])) {
    $address_id = (int)$_POST['address_id'];
    // Create order
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, total, address_id) VALUES (?, NOW(), ?, ?)");
    $order_stmt->bind_param('idi', $user_id, $total, $address_id);
    $order_stmt->execute();
    $order_id = $conn->insert_id;

    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $item_stmt->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
        $item_stmt->execute();
    }

    // Clear cart
    $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_stmt->bind_param('i', $user_id);
    $clear_stmt->execute();

    $message = 'Order placed successfully! <a href="order_history.php">View your orders</a>.';
    $items = [];
    $total = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html, body { margin: 0; padding: 0; }
        .checkout-container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
        }
        .checkout-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 2rem;
            text-align: left;
        }
        .address-section {
            margin-bottom: 24px;
        }
        .address-list {
            margin-bottom: 12px;
        }
        .address-item {
            margin-bottom: 8px;
        }
        .add-address-form {
            display: none;
            margin-bottom: 18px;
            background: #f7f7f7;
            padding: 16px;
            border-radius: 8px;
        }
        .add-address-form input, .add-address-form textarea {
            display: block;
            margin-bottom: 10px;
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        .add-address-btn, .show-address-form-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .add-address-btn:hover, .show-address-form-btn:hover {
            background: #217dbb;
        }
        .order-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            background: #fafbfc;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(44,62,80,0.06);
        }
        .order-summary-table th, .order-summary-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 10px;
            text-align: left;
        }
        .order-summary-table th {
            background: #f0f0f0;
            color: #2c3e50;
            font-weight: 600;
        }
        .order-summary-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .order-summary-total-row td {
            background: #f0f8ff;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .place-order-btn {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 18px;
            float: right;
            transition: background 0.2s;
        }
        .place-order-btn:hover {
            background: #219150;
        }
    </style>
    <script>
    function showAddressForm() {
        document.getElementById('addAddressForm').style.display = 'block';
        document.getElementById('showAddressFormBtn').style.display = 'none';
    }
    </script>
</head>
<body>
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
        <?php if ($message): ?>
            <div style="background:#27ae60;color:#fff;padding:12px 18px;border-radius:6px;margin-bottom:18px;font-weight:600;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <div class="address-section">
            <h3>Select Delivery Address</h3>
            <form method="post" id="addressSelectForm">
                <div class="address-list">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-item" style="display:flex;align-items:center;gap:8px;">
                            <label style="flex:1;">
                                <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" required>
                                <?php echo htmlspecialchars($addr['city']) . ', ' . htmlspecialchars($addr['pincode']) . ', ' . htmlspecialchars($addr['address']); ?>
                            </label>
                            <button type="submit" name="delete_address" value="<?php echo $addr['id']; ?>" title="Delete Address" style="background:#e74c3c;color:#fff;border:none;border-radius:50%;width:28px;height:28px;font-size:1.2rem;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;">&minus;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="show-address-form-btn" id="showAddressFormBtn" onclick="showAddressForm()">+ Add Address</button>
            </form>
            <form method="post" id="addAddressForm" class="add-address-form">
                <h4>Add New Address</h4>
                <input type="text" name="city" placeholder="City/Town" required>
                <input type="text" name="pincode" placeholder="Pincode" required>
                <textarea name="address" placeholder="Full Address" required></textarea>
                <button type="submit" name="add_address" class="add-address-btn">Save Address</button>
            </form>
        </div>
        <h3>Order Summary</h3>
        <table class="order-summary-table">
            <tr>
                <th>Product</th>
                <th>Description</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['description']); ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="order-summary-total-row">
                <td colspan="4" align="right">Total:</td>
                <td>$<?php echo number_format($total, 2); ?></td>
            </tr>
        </table>
        <form method="post" id="placeOrderForm">
            <?php foreach ($addresses as $addr): ?>
                <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
            <?php endforeach; ?>
            <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
        </form>
    </div>
</body>
</html> 