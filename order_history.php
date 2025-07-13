<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Cancel order logic (set status to 'Canceled')
if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $conn->query("UPDATE orders SET status = 'Canceled' WHERE id = $order_id AND user_id = $user_id");
    header('Location: order_history.php');
    exit();
}

include 'header.php';

// Fetch orders
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$order_stmt->bind_param('i', $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();

// Fetch order items for all orders
$order_items = [];
if ($orders->num_rows > 0) {
    $order_ids = [];
    while ($order = $orders->fetch_assoc()) {
        $order_ids[] = $order['id'];
        $all_orders[] = $order;
    }
    if (count($order_ids) > 0) {
        $ids_str = implode(',', array_map('intval', $order_ids));
        $items_sql = "SELECT order_items.*, products.name FROM order_items JOIN products ON order_items.product_id = products.id WHERE order_id IN ($ids_str)";
        $items_result = $conn->query($items_sql);
        while ($item = $items_result->fetch_assoc()) {
            $order_items[$item['order_id']][] = $item;
        }
    }
} else {
    $all_orders = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order History</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
        .order-history-container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            padding: 32px 40px 32px 40px;
        }
        .order-history-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 2rem;
            text-align: left;
        }
        .order-block {
            margin-bottom: 32px;
        }
        .order-block h3 {
            color: #2c3e50;
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .order-table th, .order-table td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: left;
        }
        .order-table th {
            background: #f0f0f0;
        }
        .order-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .cancel-order-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .cancel-order-btn:hover {
            background: #c0392b;
        }
        .order-status {
            display: inline-block;
            margin-left: 16px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 600;
            background: #eee;
            color: #888;
        }
        .order-status.canceled {
            background: #e74c3c;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="order-history-container">
        <h1 class="order-history-title">Your Order History</h1>
        <?php if (count($all_orders) > 0): ?>
            <?php foreach ($all_orders as $order): ?>
                <div class="order-block">
                    <h3>Order #<?php echo $order['id']; ?> | Date: <?php echo $order['order_date']; ?> | Total: $<?php echo number_format($order['total'], 2); ?>
                        <?php $status = isset($order['status']) ? $order['status'] : 'Placed'; ?>
                        <span class="order-status<?php if (strtolower($status) == 'canceled') echo ' canceled'; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </h3>
                    <table class="order-table">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php if (isset($order_items[$order['id']])): ?>
                            <?php foreach ($order_items[$order['id']] as $item): ?>
                            <tr>
                                <td><a href="product.php?id=<?php echo $item['product_id']; ?>" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($item['name']); ?></a></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                    <?php if (strtolower($status) != 'canceled'): ?>
                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');" style="margin-bottom:0;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" name="cancel_order" class="cancel-order-btn">Cancel Order</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have no orders yet. <a href="index.php">Shop now</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html> 