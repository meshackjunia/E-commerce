<?php
header('Content-Type: application/json');
require_once '../includes/products.php';
require_once '../config/db.php';

$department = isset($_GET['department']) ? $_GET['department'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

try {
    $product = new Product();
    $featuredProducts = $product->getFeaturedProducts($limit, $department);
    
    // Format product data for frontend
    $formattedProducts = array_map(function($product) {
        return [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => number_format($product['price'], 2),
            'old_price' => isset($product['old_price']) ? number_format($product['old_price'], 2) : null,
            'image_url' => $product['image_url'] ?? '',
            'department' => $product['department'],
            'category_name' => $product['category_name'] ?? '',
            'rating' => isset($product['avg_rating']) ? (float)$product['avg_rating'] : 0,
            'review_count' => $product['review_count'] ?? 0,
            'is_featured' => (bool)$product['is_featured']
        ];
    }, $featuredProducts);
    
    echo json_encode([
        'success' => true,
        'products' => $formattedProducts
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load featured products',
        'error' => $e->getMessage()
    ]);
}
?>