<?php
require_once 'config.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Register a new user
    public function register($username, $password, $email, $first_name, $last_name, $phone = null, $address = null) {
        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            return false; // User already exists
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, email, first_name, last_name, phone, address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $phone, $address])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    // Login user
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                return true;
            }
        }
        
        return false;
    }

    // Check if user is logged in
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }

    // Get current user info
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Logout user
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        return true;
    }

    // Update user profile
    public function updateProfile($user_id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            // Don't update password here (use changePassword method)
            if ($key !== 'password' && !empty($value)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $user_id;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($values);
    }

    // Change password
    public function changePassword($user_id, $current_password, $new_password) {
        // Verify current password
        $stmt = $this->db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            return false;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    }
}
?>