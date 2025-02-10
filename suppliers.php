<?php
session_start();
require_once 'config/database.php';

// Handle delete
if (isset($_POST['delete_supplier'])) {
    $supplier_id = $_POST['delete_supplier'];
    
    // Check if supplier has products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $productCount = $stmt->fetchColumn();
    
    if ($productCount > 0) {
        $_SESSION['error'] = "Cannot delete supplier - has associated products";
        header("Location: suppliers.php");
        exit();
    }
    
    // Safe to delete
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE supplier_id = ?");
        $stmt->execute([$supplier_id]);
        
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Supplier deleted successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting supplier";
    }
    
    header("Location: suppliers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $categories = isset($_POST['categories']) ? array_filter($_POST['categories']) : [];

    if (empty($name)) {
        $_SESSION['error'] = "Supplier name is required";
        header("Location: suppliers.php");
        exit();
    }

    $pdo->beginTransaction();
    try {
        if (isset($_POST['supplier_id']) && !empty($_POST['supplier_id'])) {
            $supplier_id = $_POST['supplier_id'];
    
            // Update supplier basic info
            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, address = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $address, $phone, $email, $supplier_id]);

            // Handle categories separately - this won't affect products
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE supplier_id = ?");
            $stmt->execute([$supplier_id]);
            $existingCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Add new categories
            $stmt = $pdo->prepare("INSERT INTO categories (supplier_id, name) VALUES (?, ?)");
            foreach ($categories as $category) {
                if (!empty($category) && !in_array($category, $existingCategories)) {
                    $stmt->execute([$supplier_id, $category]);
                }
            }

            // Remove unchecked categories
            foreach ($existingCategories as $existingCategory) {
                if (!in_array($existingCategory, $categories)) {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE supplier_id = ? AND name = ?");
                    $stmt->execute([$supplier_id, $existingCategory]);
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, address, phone, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $address, $phone, $email]);
            $supplier_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO categories (supplier_id, name) VALUES (?, ?)");
            foreach ($categories as $category) {
                if (!empty($category)) {
                    $stmt->execute([$supplier_id, $category]);
                }
            }
        }
        $pdo->commit();
        $_SESSION['success'] = "Supplier saved successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error saving supplier";
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
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

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
                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            <td>
                                Phone: <?php echo htmlspecialchars($supplier['phone']); ?><br>
                                Email: <?php echo htmlspecialchars($supplier['email']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['categories']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editSupplier(<?php echo json_encode($supplier); ?>)'>Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="delete_supplier" value="<?php echo $supplier['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</button>
                                </form>
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
                            <div id="categoryInputs"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addCategoryInput()">Add Category</button>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="supplier_id" id="edit_supplier_id">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" id="edit_address" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Categories</label>
                            <div id="editCategoryInputs"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addEditCategoryInput()">Add Category</button>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Supplier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addCategoryInput(value = '') {
            let div = document.createElement('div');
            div.classList.add('d-flex', 'gap-2', 'mb-2');
            
            let input = document.createElement('input');
            input.type = 'text';
            input.name = 'categories[]';
            input.classList.add('form-control');
            input.value = value;
            
            let deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.classList.add('btn', 'btn-danger');
            deleteBtn.innerHTML = '×';
            deleteBtn.onclick = function() {
                div.remove();
            };
            
            div.appendChild(input);
            div.appendChild(deleteBtn);
            document.getElementById('categoryInputs').appendChild(div);
        }

        function addEditCategoryInput(value = '') {
            let div = document.createElement('div');
            div.classList.add('d-flex', 'gap-2', 'mb-2');
            
            let input = document.createElement('input');
            input.type = 'text';
            input.name = 'categories[]';
            input.classList.add('form-control');
            input.value = value;
            
            let deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.classList.add('btn', 'btn-danger');
            deleteBtn.innerHTML = '×';
            deleteBtn.onclick = function() {
                div.remove();
            };
            
            div.appendChild(input);
            div.appendChild(deleteBtn);
            document.getElementById('editCategoryInputs').appendChild(div);
        }

        function editSupplier(supplier) {
            document.getElementById('edit_supplier_id').value = supplier.id;
            document.getElementById('edit_name').value = supplier.name;
            document.getElementById('edit_address').value = supplier.address;
            document.getElementById('edit_phone').value = supplier.phone;
            document.getElementById('edit_email').value = supplier.email;

            let categoryContainer = document.getElementById('editCategoryInputs');
            categoryContainer.innerHTML = '';

            if (supplier.categories) {
                let categories = supplier.categories.split(',');
                categories.forEach(category => {
                    addEditCategoryInput(category.trim());
                });
            }

            new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
        }

        window.addEventListener('DOMContentLoaded', (event) => {
            addCategoryInput();
        });
    </script>
</body>
</html>
