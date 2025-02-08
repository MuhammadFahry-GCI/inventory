<?php
session_start();
require_once 'config/database.php';

// Get products with low stock (below 10 items)
$stmt = $pdo->query("SELECT p.*, c.name as category_name, s.name as supplier_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     JOIN suppliers s ON c.supplier_id = s.id 
                     WHERE p.stock < 10
                     ORDER BY p.stock ASC");
$low_stock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stock Alerts - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content p-4" style="flex: 1;">
            <h2>Stock Alerts</h2>
            
            <div class="alert alert-warning">
                Products with stock below 10 items
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Current Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $product): ?>
                    <tr>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo $product['supplier_name']; ?></td>
                        <td>
                            <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                        </td>
                        <td>
                            <a href="purchases.php?product=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-primary">Create Purchase</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

// Tambahkan ini di products.php
<script>
function searchProducts() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
</script>

<!-- Tambahkan input search -->
<input type="text" 
       id="searchInput" 
       class="form-control mb-3" 
       placeholder="Search products..." 
       onkeyup="searchProducts()">


<!-- Tambahkan input search -->
<input type="text" 
       id="searchInput" 
       class="form-control mb-3" 
       placeholder="Search products..." 
       onkeyup="searchProducts()">
