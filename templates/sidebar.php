<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<div class="sidebar bg-dark text-white" style="width: 250px; min-height: 100vh;">
    <div class="p-3">
        <h4>Inventory System</h4>
        <hr>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="dashboard.php">Dashboard</a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="users.php">Manage Users</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="products.php">Data Produk</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="suppliers.php">Suppliers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="purchases.php">pembelian</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</div>