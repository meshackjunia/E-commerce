<?php
require_once 'config.php';

class Cart {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Get or create cart for user
    private function getUserCart($user_id) {
        $stmt = $this->db->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            $stmt = $this->db->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            return $this->db->lastInsertId();
        }
        
        return $cart['cart_id'];
    }

    // Add item to cart
    public function addToCart($user_id, $product_id, $quantity = 1) {
        $cart_id = $this->getUserCart($user_id);
        
        // Check if product already in cart
        $stmt = $this->db->prepare("
            SELECT cart_item_id, quantity FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$cart_id, $product_id]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Update quantity if product already in cart
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $this->db->prepare("
                UPDATE cart_items SET quantity = ? 
                WHERE cart_item_id = ?
            ");
            return $stmt->execute([$new_quantity, $existing_item['cart_item_id']]);
        } else {
            // Add new item to cart
            $stmt = $this->db->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$cart_id, $product_id, $quantity]);
        }
    }

    // Remove item from cart
    public function removeFromCart($user_id, $product_id) {
        $cart_id = $this->getUserCart($user_id);
        
        $stmt = $this->db->prepare("
            DELETE FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        return $stmt->execute([$cart_id, $product_id]);
    }

    // Update cart item quantity
    public function updateCartItem($user_id, $product_id, $quantity) {
        $cart_id = $this->getUserCart($user_id);
        
        if ($quantity <= 0) {
            return $this->removeFromCart($user_id, $product_id);
        }
        
        $stmt = $this->db->prepare("
            UPDATE cart_items SET quantity = ? 
            WHERE cart_id = ? AND product_id = ?
        ");
        return $stmt->execute([$quantity, $cart_id, $product_id]);
    }

    // Get cart contents
    public function getCartContents($user_id) {
        $cart_id = $this->getUserCart($user_id);
        
        $stmt = $this->db->prepare("
            SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cart_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get cart total
    public function getCartTotal($user_id) {
        $items = $this->getCartContents($user_id);
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }

    // Get cart item count
    public function getCartItemCount($user_id) {
        $cart_id = $this->getUserCart($user_id);
        
        $stmt = $this->db->prepare("
            SELECT SUM(quantity) as total_items 
            FROM cart_items 
            WHERE cart_id = ?
        ");
        $stmt->execute([$cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_items'] ? $result['total_items'] : 0;
    }

    // Clear cart
    public function clearCart($user_id) {
        $cart_id = $this->getUserCart($user_id);
        
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        return $stmt->execute([$cart_id]);
    }
}
?>