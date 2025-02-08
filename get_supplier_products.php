<?php
require_once 'config/database.php';

$supplier_id = $_GET['supplier_id'];

$stmt = $pdo->prepare("SELECT p.* FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE c.supplier_id = ?");
$stmt->execute([$supplier_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($products as $product) {
    $result[$product['id']] = $product;
}

header('Content-Type: application/json');
echo json_encode($result);
?>
