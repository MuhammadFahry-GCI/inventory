<?php
session_start();
require_once 'config/database.php';

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];
    
    $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, stock = ?, price = ? WHERE id = ?");
    $stmt->execute([$category_id, $name, $description, $stock, $price, $product_id]);
    
    header("Location: products.php");
    exit();
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['edit_product'])) {
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];
    
    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, stock, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$category_id, $name, $description, $stock, $price]);
    
    header("Location: products.php");
    exit();
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    header("Location: products.php");
    exit();
}

// Get all categories for dropdown
$stmt = $pdo->query("SELECT c.*, s.name as supplier_name 
                     FROM categories c 
                     JOIN suppliers s ON c.supplier_id = s.id");
$categories = $stmt->fetchAll();

// Get all products with category and supplier info
$stmt = $pdo->query("SELECT p.*, c.name as category_name, s.name as supplier_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     JOIN suppliers s ON c.supplier_id = s.id");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Management - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content p-4" style="flex: 1;">
            <h2>Product Management</h2>
            
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                Add New Product
            </button>
            
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo $product['supplier_name']; ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>Rp<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Category</label>
                            <select name="category_id" class="form-control" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo $category['name'] . ' (' . $category['supplier_name'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Stock</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="edit_product" value="1">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="mb-3">
                            <label>Category</label>
                            <select name="category_id" id="edit_category_id" class="form-control" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo $category['name'] . ' (' . $category['supplier_name'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="edit_description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Stock</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editProduct(product) {
        document.getElementById('edit_product_id').value = product.id;
        document.getElementById('edit_category_id').value = product.category_id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_description').value = product.description;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_price').value = product.price;
        
        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    }

    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            window.location.href = 'products.php?delete=' + productId;
        }
    }

    function searchProducts() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let rows = document.querySelectorAll('table tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }
    </script>
</body>
</html>
