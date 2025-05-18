<?php
require_once 'config.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Get all products
    public function getAllProducts($department = null, $category_id = null, $limit = null, $offset = null) {
        $query = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if ($department) {
            $query .= " AND department = ?";
            $params[] = $department;
        }
        
        if ($category_id) {
            $query .= " AND category_id = ?";
            $params[] = $category_id;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset) {
                $query .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
// Get featured products
public function getFeaturedProducts($limit = 8, $department = null) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE p.is_featured = TRUE";
    
    $params = [];
    
    if ($department) {
        $query .= " AND p.department = ?";
        $params[] = $department;
    }
    
    $query .= " ORDER BY RAND() LIMIT ?";
    $params[] = $limit;
    
    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Get product by ID
    public function getProductById($product_id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = ?
        ");
        $stmt->execute([$product_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add new product
    public function addProduct($data) {
        $stmt = $this->db->prepare("
            INSERT INTO products (name, description, price, stock_quantity, category_id, department, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['stock_quantity'],
            $data['category_id'],
            $data['department'],
            $data['image_url']
        ]);
    }

    // Update product
    public function updateProduct($product_id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        
        $params[] = $product_id;
        $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE product_id = ?";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($params);
    }

    // Delete product
    public function deleteProduct($product_id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id = ?");
        return $stmt->execute([$product_id]);
    }

    // Search products
    public function searchProducts($keyword, $department = null, $category_id = null, $limit = null, $offset = null) {
        $query = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ?";
        $params = ["%$keyword%", "%$keyword%"];
        
        if ($department) {
            $query .= " AND department = ?";
            $params[] = $department;
        }
        
        if ($category_id) {
            $query .= " AND category_id = ?";
            $params[] = $category_id;
        }
        
        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset) {
                $query .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get product categories
    public function getCategories($department = null) {
        $query = "SELECT * FROM categories";
        $params = [];
        
        if ($department) {
            $query .= " WHERE department = ?";
            $params[] = $department;
        }
        
        $query .= " ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get products by category
    public function getProductsByCategory($category_id, $limit = null, $offset = null) {
        $query = "SELECT * FROM products WHERE category_id = ?";
        $params = [$category_id];
        
        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset) {
                $query .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>