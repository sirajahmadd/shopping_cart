<?php
// header.php - reusable header for all pages
include_once 'config.php';
$header_categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
while ($row = $cat_result->fetch_assoc()) {
    $header_categories[] = $row;
}
$active_category_id = isset($active_category_id) ? $active_category_id : 0;
// Cart count logic
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $cart_result = $conn->query("SELECT COUNT(DISTINCT product_id) as cnt FROM cart WHERE user_id = $uid");
    if ($cart_result) {
        $row = $cart_result->fetch_assoc();
        $cart_count = (int)$row['cnt'];
    }
}
?>
<style>
html, body {
    margin: 0;
    padding: 0;
}
.site-header {
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
.site-header .header-left {
    display: flex;
    align-items: center;
    gap: 32px;
}
.site-header .logo {
    display: flex;
    align-items: center;
}
.site-header .logo img {
    height: 68px;
    width: auto;
    display: block;
}
.site-header .nav-links {
    display: flex;
    align-items: center;
    gap: 24px;
}
.site-header .nav-links a {
    color: #fff;
    text-decoration: none;
    font-size: 1.08rem;
    font-weight: 500;
    letter-spacing: 1px;
    padding: 6px 10px;
    border-radius: 3px;
    transition: background 0.2s;
}
.site-header .nav-links a:hover {
    background: #34495e;
}
.site-header .nav-links a.active-category {
    background: #fff;
    color: #2c3e50;
    font-weight: bold;
}
.site-header .header-search {
    display: flex;
    align-items: center;
    margin-left: 32px;
    position: relative;
}
.site-header .header-search input[type="text"] {
    padding: 11px 32px 11px 32px;
    border-radius: 20px;
    border: none;
    font-size: 1rem;
    width: 220px;
    outline: none;
}
.site-header .header-search .search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
}
.site-header .header-actions {
    display: flex;
    align-items: center;
    gap: 18px;
}
.site-header .header-action-btn {
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
.site-header .header-action-btn:hover {
    color: #f1c40f;
}
.site-header .header-icon {
    font-size: 1.6rem;
    margin-bottom: 2px;
}
.site-header .header-badge {
    position: absolute;
    top: -4px;
    right: -8px;
    background: #e74c3c;
    color: #fff;
    font-size: 0.75rem;
    border-radius: 50%;
    padding: 2px 6px;
    min-width: 18px;
    text-align: center;
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
@media (max-width: 700px) {
    .site-header {
        flex-direction: column;
        height: auto;
        padding: 12px;
    }
    .site-header .header-left {
        flex-direction: column;
        gap: 10px;
    }
    .site-header .header-search input[type="text"] {
        width: 120px;
    }
    .site-header .logo img {
        height: 40px;
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
    if (!profileBtn.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
</script>
<div class="site-header">
    <div class="header-left">
        <div class="logo">
            <a href="index.php"><img src="assets/logo.png" alt="Logo"></a>
        </div>
        <nav class="nav-links">
            <?php foreach ($header_categories as $cat): ?>
                <a href="index.php?category=<?php echo $cat['id']; ?>"<?php if ($active_category_id == $cat['id']) echo ' class="active-category"'; ?>><?php echo htmlspecialchars($cat['name']); ?></a>
            <?php endforeach; ?>
        </nav>
        <form class="header-search" action="index.php" method="get">
            <span class="search-icon">
                <svg width="18" height="18" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="7"/><line x1="13" y1="13" x2="17" y2="17"/></svg>
            </span>
            <input type="text" name="q" placeholder="Search product">
        </form>
    </div>
    <div class="header-actions">
        <div class="profile-btn-wrapper">
            <a href="#" class="header-action-btn" id="profileBtn" onclick="toggleProfileDropdown(event)">
                <span class="header-icon">&#128100;</span>
                <span>Profile</span>
            </a>
            <div class="profile-dropdown" id="profileDropdown">
                <?php if (isset($_SESSION['username'])): ?>
                    <div style="padding: 10px 20px; font-weight: 600; color: #3498db; border-bottom: 1px solid #f0f0f0; background: #f8f9fa;">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        <a href="favourites.php" class="header-action-btn">
            <span class="header-icon">&#10084;</span>
            <span>Wishlist</span>
        </a>
        <a href="cart.php" class="header-action-btn" style="position:relative;">
            <span class="header-icon">&#128722;</span>
            <span>Cart</span>
            <?php if ($cart_count > 0): ?>
                <span class="header-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div> 