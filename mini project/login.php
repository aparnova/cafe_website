<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'] ?? 'user'; // Default to user

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            $table = '';
            switch ($user_type) {
                case 'admin':
                    $table = 'admins';
                    break;
                case 'delivery':
                    $table = 'delivery_boys';
                    break;
                default:
                    $table = 'users';
            }

            $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user_type;
                
                // Redirect based on user type
                if ($user_type === 'admin') {
                    header('Location: admin_dashboard.php');
                } elseif ($user_type === 'delivery') {
                    header('Location: delivery_dashboard.php');
                } else {
                    header('Location: user_dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Westley's Resto Cafe</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
    }
    
    body {
      background: #f2f2f2;
      color: #333;
    }
    
    .container {
      display: flex;
      height: 100vh;
    }
    
    .left-panel {
      width: 50%;
      background: url("cake.jpg") no-repeat center center/cover;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 40px;
      color: #fff;
      position: relative;
    }
    
    .left-panel::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 0;
    }
    
    .left-panel h2, 
    .left-panel p {
      position: relative;
      z-index: 1;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
    }
    
    .left-panel h2 {
      font-size: 28px;
      margin: 0;
    }
    
    .left-panel p {
      font-size: 14px;
      margin-top: 10px;
      opacity: 0.9;
    }
    
    .right-panel {
      width: 50%;
      background: #fff;
      padding: 60px 80px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .right-panel h2 {
      margin-bottom: 20px;
      color: #0C1E17;
      font-size: 24px;
    }
    
    .login-form {
      display: flex;
      flex-direction: column;
    }
    
    .form-group {
      margin-bottom: 15px;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
      color: #555;
    }
    
    .form-group input, .form-group select {
      width: 100%;
      padding: 14px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }
    
    .form-group input:hover, .form-group select:hover {
      border-color: #999;
      box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }
    
    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: #000;
      box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    }
    
    .form-group input.error, .form-group select.error {
      border-color: #ff4444;
    }
    
    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: 5px;
      display: block;
    }
    
    .login-btn {
      margin-top: 20px;
      padding: 14px;
      background-color: #000;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 15px;
      transition: background-color 0.3s, transform 0.2s;
    }
    
    .login-btn:hover {
      background-color: #333;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .login-btn:active {
      transform: translateY(0);
    }
    
    .login-btn.loading {
      background-color: #666;
      pointer-events: none;
    }
    
    .login-btn.loading::after {
      content: "";
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
      margin-left: 8px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .forgot-password {
      text-align: right;
      margin-top: 10px;
    }
    
    .forgot-password a {
      color: #666;
      font-size: 13px;
      text-decoration: none;
    }
    
    .forgot-password a:hover {
      color: #000;
      text-decoration: underline;
    }
    
    .or-line {
      display: flex;
      align-items: center;
      margin: 20px 0;
      color: #999;
    }
    
    .or-line hr {
      flex: 1;
      border: none;
      border-top: 1px solid #ddd;
    }
    
    .or-line span {
      margin: 0 10px;
      font-size: 14px;
    }
    
    .register-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #000;
    }
    
    .register-link a {
      color: #000;
      text-decoration: none;
      font-weight: 600;
      position: relative;
    }
    
    .register-link a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background-color: #000;
      transition: width 0.3s;
    }
    
    .register-link a:hover::after {
      width: 100%;
    }
    
    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }
    
      .left-panel, .right-panel {
        width: 100%;
        height: 50%;
      }
    
      .right-panel {
        padding: 40px 30px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Left Image Panel -->
    <div class="left-panel">
      <h2>Welcome to Westley's Resto Cafe</h2>
      <p>Login to enjoy our delicious meals and services</p>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
      <h2>Login to Your Account</h2>
      
      <?php if (!empty($error)): ?>
        <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <form id="loginForm" class="login-form" method="POST" action="login.php">
        <div class="form-group">
          <label for="user_type">Login As</label>
          <select id="user_type" name="user_type" required>
            <option value="user">Customer</option>
            <option value="admin">Admin</option>
            <option value="delivery">Delivery Boy</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        
        <div class="forgot-password">
          <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <button type="submit" class="login-btn" id="loginButton">Login</button>
      </form>
      
      <div class="or-line">
        <hr><span>OR</span><hr>
      </div>
      
      <div class="register-link">
        Don't have an account? <a href="register.php">Register as Customer</a>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const loginButton = document.getElementById('loginButton');
      
      form.addEventListener('submit', function(e) {
        // Client-side validation
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        
        if (!email.value || !password.value) {
          e.preventDefault();
          alert('Please fill in all fields');
          return false;
        }
        
        // Show loading state
        loginButton.classList.add('loading');
        loginButton.disabled = true;
      });
    });
  </script>
</body>
</html>