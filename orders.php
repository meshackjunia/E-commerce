<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/products.php';
require_once 'includes/cart.php';

class Order {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = (new Database())->connect();
        $this->auth = new Auth();
    }

    // Create a new order from cart
    public function createOrder($user_id, $payment_method, $shipping_address, $billing_address = null) {
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            $cart = new Cart();
            $cart_items = $cart->getCartContents($user_id);
            
            if (empty($cart_items)) {
                throw new Exception("Your cart is empty");
            }
            
            // Calculate total and verify stock
            $total = 0;
            foreach ($cart_items as $item) {
                $total += $item['price'] * $item['quantity'];
                
                // Verify stock availability
                $product = (new Product())->getProductById($item['product_id']);
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for {$item['name']}");
                }
            }
            
            // Create order
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user_id,
                $total,
                $payment_method,
                $shipping_address,
                $billing_address ?: $shipping_address
            ]);
            $order_id = $this->db->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                $stmt = $this->db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Update product stock
                $stmt = $this->db->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear the cart
            $cart->clearCart($user_id);
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'total' => $total
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Get order details
    public function getOrder($order_id, $user_id = null) {
        $query = "SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.phone 
                  FROM orders o
                  JOIN users u ON o.user_id = u.user_id
                  WHERE o.order_id = ?";
        
        $params = [$order_id];
        
        if ($user_id && !$this->auth->isAdmin()) {
            $query .= " AND o.user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name, p.image_url, p.department
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    }

    // Get all orders for a user or all orders (admin)
    public function getOrders($user_id = null, $page = 1, $per_page = 10, $filters = []) {
        $query = "SELECT o.*, u.username, u.email 
                  FROM orders o
                  JOIN users u ON o.user_id = u.user_id";
        
        $where = [];
        $params = [];
        
        if ($user_id && !$this->auth->isAdmin()) {
            $where[] = "o.user_id = ?";
            $params[] = $user_id;
        }
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "o.order_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "o.order_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(o.order_id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $search_term = "%{$filters['search']}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Count total records for pagination
        $count_query = "SELECT COUNT(*) as total FROM ($query) as subquery";
        $count_stmt = $this->db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Add sorting and pagination
        $query .= " ORDER BY o.order_date DESC";
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = ($page - 1) * $per_page;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    // Update order status
    public function updateOrderStatus($order_id, $status, $user_id = null) {
        // Validate status
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return [
                'success' => false,
                'message' => 'Invalid order status'
            ];
        }
        
        $query = "UPDATE orders SET status = ? WHERE order_id = ?";
        $params = [$status, $order_id];
        
        // Non-admin users can only cancel their own orders
        if ($user_id && !$this->auth->isAdmin()) {
            $query .= " AND user_id = ?";
            $params[] = $user_id;
            
            // Users can only cancel pending orders
            if ($status === 'cancelled') {
                $query .= " AND status = 'pending'";
            } else {
                return [
                    'success' => false,
                    'message' => 'You can only cancel orders'
                ];
            }
        }
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($params);
        
        if ($success && $stmt->rowCount() > 0) {
            // If order is cancelled, restore stock
            if ($status === 'cancelled') {
                $this->restoreOrderStock($order_id);
            }
            
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'message' => 'Order not found or status not updated'
            ];
        }
    }

    // Restore product stock when order is cancelled
    private function restoreOrderStock($order_id) {
        $stmt = $this->db->prepare("
            SELECT product_id, quantity 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ? 
                WHERE product_id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
    }

    // Process payment for an order
    public function processPayment($order_id, $payment_data) {
        $this->db->beginTransaction();
        
        try {
            // Verify order exists and is unpaid
            $order = $this->getOrder($order_id);
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            if ($order['payment_status'] === 'completed') {
                throw new Exception("Order already paid");
            }
            
            // In a real application, you would integrate with a payment gateway here
            // This is a simplified example
            
            // Record payment
            $stmt = $this->db->prepare("
                INSERT INTO payments (order_id, amount, payment_method, transaction_id, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $order['total_amount'],
                $payment_data['payment_method'],
                $payment_data['transaction_id'],
                'completed'
            ]);
            
            // Update order payment status
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET payment_status = 'completed' 
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payment_id' => $this->db->lastInsertId()
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Get payment details for an order
    public function getPaymentDetails($order_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM payments 
            WHERE order_id = ?
            ORDER BY payment_date DESC
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generate invoice for an order
    public function generateInvoice($order_id, $user_id = null) {
        $order = $this->getOrder($order_id, $user_id);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found'
            ];
        }
        
        $payment = $this->getPaymentDetails($order_id);
        
        // Format invoice data
        $invoice = [
            'order_id' => $order['order_id'],
            'order_date' => $order['order_date'],
            'status' => $order['status'],
            'customer' => [
                'name' => "{$order['first_name']} {$order['last_name']}",
                'email' => $order['email'],
                'phone' => $order['phone']
            ],
            'shipping_address' => $order['shipping_address'],
            'billing_address' => $order['billing_address'],
            'items' => array_map(function($item) {
                return [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'department' => $item['department']
                ];
            }, $order['items']),
            'subtotal' => $order['total_amount'],
            'tax' => 0, // You would calculate this based on your tax rules
            'shipping' => 0, // You would calculate this based on your shipping rules
            'total' => $order['total_amount'],
            'payment' => $payment ? [
                'method' => $payment['payment_method'],
                'transaction_id' => $payment['transaction_id'],
                'date' => $payment['payment_date'],
                'amount' => $payment['amount']
            ] : null
        ];
        
        return [
            'success' => true,
            'invoice' => $invoice
        ];
    }
}
?>