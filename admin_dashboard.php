<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}
include 'config.php';

// Fetch statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_categories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// Handle sorting and pagination for orders
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'order_date';
$sort_dir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';
$valid_sort = ['order_date', 'id'];
if (!in_array($sort_by, $valid_sort)) $sort_by = 'order_date';

// Pagination
$orders_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $orders_per_page;

// Get total order count for pagination
$total_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_pages = ceil($total_orders_count / $orders_per_page);

// Fetch paginated and sorted orders
$recent_orders = $conn->query("SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.id ORDER BY $sort_by $sort_dir LIMIT $orders_per_page OFFSET $offset");

// Helper: Get order total
function get_order_total($conn, $order_id) {
    $sql = "SELECT SUM(products.price * order_items.quantity) as total FROM order_items JOIN products ON order_items.product_id = products.id WHERE order_items.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ? $row['total'] : 0;
}

// Fetch order statistics
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
        .admin-header {
            width: 100%;
            background: #2c3e50;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            height: 100px;
            box-sizing: border-box;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            font-family: Arial, sans-serif;
        }
        .admin-header .logo {
            display: flex;
            align-items: center;
        }
        .admin-header .logo img {
            height: 68px;
            width: auto;
            display: block;
        }
        .admin-header .header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .admin-header .header-action-btn {
            color: #fff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.93rem;
            position: relative;
            transition: color 0.2s;
            cursor: pointer;
        }
        .admin-header .header-action-btn:hover {
            color: #f1c40f;
        }
        .admin-header .header-icon {
            font-size: 1.6rem;
            margin-bottom: 2px;
        }
        /* Profile Dropdown */
        .profile-dropdown {
            display: none;
            position: absolute;
            top: 60px;
            right: 0;
            background: #fff;
            color: #2c3e50;
            min-width: 140px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.12);
            border-radius: 8px;
            z-index: 1000;
            padding: 10px 0;
            text-align: left;
        }
        .profile-dropdown a {
            display: block;
            padding: 10px 20px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .profile-dropdown a:hover {
            background: #f0f0f0;
        }
        .profile-dropdown.show {
            display: block;
        }
        .profile-btn-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        /* Dashboard Layout */
        .admin-dashboard {
            padding: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-dashboard h1 {
            margin-bottom: 32px;
            color: #2c3e50;
            font-size: 2.2rem;
            text-align: center;
        }
        
        /* Statistics Section */
        .stats-section {
            margin-bottom: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(44,62,80,0.12);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .stat-card .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Recent Orders Section */
        .recent-orders {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            margin-bottom: 32px;
        }
        .recent-orders h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .order-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-canceled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Admin Actions */
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .admin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #3498db;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 16px 24px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .admin-btn:hover {
            background: #217dbb;
            box-shadow: 0 4px 16px rgba(44,62,80,0.12);
        }
        .admin-dashboard .back-link {
            text-align: center;
            margin-top: 24px;
        }
        .admin-dashboard .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .admin-dashboard .back-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 700px) {
            .admin-header {
                flex-direction: column;
                height: auto;
                padding: 12px;
            }
            .admin-header .logo img {
                height: 40px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .admin-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
    function toggleProfileDropdown(e) {
        e.preventDefault();
        var dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('show');
    }
    document.addEventListener('click', function(event) {
        var profileBtn = document.getElementById('profileBtn');
        var dropdown = document.getElementById('profileDropdown');
        if (profileBtn && dropdown && !profileBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
    // --- Scroll to Recent Orders after sort/pagination ---
    function flagScrollToOrders() {
        sessionStorage.setItem('scroll_to_orders', '1');
    }
    function scrollToOrdersIfFlagged() {
        if (sessionStorage.getItem('scroll_to_orders')) {
            var ordersSection = document.getElementById('recent-orders');
            if (ordersSection) {
                ordersSection.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
            sessionStorage.removeItem('scroll_to_orders');
        }
    }
    window.addEventListener('DOMContentLoaded', scrollToOrdersIfFlagged);
    document.addEventListener('DOMContentLoaded', function() {
        var sortForm = document.querySelector('.recent-orders form');
        if (sortForm) {
            sortForm.addEventListener('submit', flagScrollToOrders);
        }
        document.querySelectorAll('.recent-orders .orders-pagination-link').forEach(function(link) {
            link.addEventListener('click', flagScrollToOrders);
        });
    });
    </script>
</head>
<body>
    <div class="admin-header">
        <div class="logo">
            <img src="assets/logo.png" alt="Logo">
        </div>
        <div class="header-actions">
            <div class="profile-btn-wrapper">
                <a href="#" class="header-action-btn" id="profileBtn" onclick="toggleProfileDropdown(event)">
                    <span class="header-icon">&#128100;</span>
                    <span>Profile</span>
                </a>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="admin_logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <div class="admin-dashboard">
        <h1>Admin Dashboard</h1>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📦</span>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📂</span>
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">👥</span>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🛒</span>
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">⏳</span>
                    <div class="stat-number"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $completed_orders; ?></div>
                    <div class="stat-label">Completed Orders</div>
                </div>
            </div>
        </div>
        
        <!-- Admin Actions -->
        <div class="admin-actions">
            <a href="admin_categories.php" class="admin-btn">Manage Categories</a>
            <a href="admin_products.php" class="admin-btn">Manage Products</a>
            <a href="admin_sizes.php" class="admin-btn">Manage Sizes</a>
            <a href="admin_colors.php" class="admin-btn">Manage Colors</a>
        </div>
        
        <!-- Recent Orders Section -->
        <div class="recent-orders" id="recent-orders">
            <h2>Recent Orders</h2>
            <form method="get" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                <label for="sort_by">Sort by:</label>
                <select name="sort_by" id="sort_by" onchange="this.form.submit()">
                    <option value="order_date" <?php if($sort_by=='order_date') echo 'selected'; ?>>Date</option>
                    <option value="id" <?php if($sort_by=='id') echo 'selected'; ?>>Order ID</option>
                </select>
                <select name="sort_dir" onchange="this.form.submit()">
                    <option value="desc" <?php if($sort_dir=='DESC') echo 'selected'; ?>>Descending</option>
                    <option value="asc" <?php if($sort_dir=='ASC') echo 'selected'; ?>>Ascending</option>
                </select>
                <input type="hidden" name="page" value="1">
            </form>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format(get_order_total($conn, $order['id']), 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <!-- Pagination Controls -->
            <div style="margin-top:18px;text-align:center;">
                <?php if ($total_pages > 1): ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?sort_by=<?php echo $sort_by; ?>&sort_dir=<?php echo strtolower($sort_dir); ?>&page=<?php echo $i; ?>" class="orders-pagination-link" style="display:inline-block;padding:6px 14px;margin:0 2px;border-radius:5px;text-decoration:none;font-weight:600;<?php if($i==$page)echo'background:#3498db;color:#fff;';else echo'background:#f0f0f0;color:#2c3e50;'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</body>
</html> 