<?php
session_start();
require_once 'config/database.php';

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get purchase report
$stmt = $pdo->prepare("SELECT 
    p.purchase_date,
    s.name as supplier_name,
    pr.name as product_name,
    pi.quantity,
    pi.price,
    (pi.quantity * pi.price) as total
    FROM purchases p
    JOIN purchase_items pi ON p.id = pi.purchase_id
    JOIN products pr ON pi.product_id = pr.id
    JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.purchase_date BETWEEN ? AND ?
    ORDER BY p.purchase_date DESC");
$stmt->execute([$start_date, $end_date]);
$purchases = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content p-4" style="flex: 1;">
            <h2>Purchase Reports</h2>
            
            <form class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">Print</button>
                    </div>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo $purchase['purchase_date']; ?></td>
                        <td><?php echo $purchase['supplier_name']; ?></td>
                        <td><?php echo $purchase['product_name']; ?></td>
                        <td><?php echo $purchase['quantity']; ?></td>
                        <td>$<?php echo number_format($purchase['price'], 2); ?></td>
                        <td>$<?php echo number_format($purchase['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
