<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $categories = $_POST['categories']; // Array of categories
    
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, address, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $address, $phone, $email]);
    $supplier_id = $pdo->lastInsertId();
    
    // Insert categories
    $stmt = $pdo->prepare("INSERT INTO categories (supplier_id, name) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$supplier_id, $category]);
    }
    
    header("Location: suppliers.php");
    exit();
}

$stmt = $pdo->query("SELECT s.*, GROUP_CONCAT(c.name) as categories 
                     FROM suppliers s 
                     LEFT JOIN categories c ON s.id = c.supplier_id 
                     GROUP BY s.id");
$suppliers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supplier Management - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content p-4" style="flex: 1;">
            <h2>Supplier Management</h2>
            
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                Add New Supplier
            </button>
            
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Categories</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo $supplier['name']; ?></td>
                        <td>
                            Phone: <?php echo $supplier['phone']; ?><br>
                            Email: <?php echo $supplier['email']; ?>
                        </td>
                        <td><?php echo $supplier['categories']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Categories</label>
                            <div id="categoryInputs">
                                <input type="text" name="categories[]" class="form-control mb-2" placeholder="Category name">
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addCategoryInput()">Add Category</button>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function addCategoryInput() {
        const div = document.createElement('div');
        div.innerHTML = '<input type="text" name="categories[]" class="form-control mb-2" placeholder="Category name">';
        document.getElementById('categoryInputs').appendChild(div);
    }
    </script>
</body>
</html>
