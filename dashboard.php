<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get total products
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$total_products = $stmt->fetch()['total'];

// Get total suppliers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM suppliers");
$total_suppliers = $stmt->fetch()['total'];

// Get recent purchases
$stmt = $pdo->query("SELECT p.*, s.name as supplier_name 
                     FROM purchases p 
                     JOIN suppliers s ON p.supplier_id = s.id 
                     ORDER BY p.created_at DESC LIMIT 5");
$recent_purchases = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content p-4" style="flex: 1;">
            <h2>Dashboard</h2>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Products</h5>
                            <h2><?php echo $total_products; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Suppliers</h5>
                            <h2><?php echo $total_suppliers; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h4>Recent Purchases</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_purchases as $purchase): ?>
                        <tr>
                            <td><?php echo $purchase['purchase_date']; ?></td>
                            <td><?php echo $purchase['supplier_name']; ?></td>
                            <td>Rp<?php echo number_format($purchase['total_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
