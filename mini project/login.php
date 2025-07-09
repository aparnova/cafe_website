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
                    header('Location: homepage.html');
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    body {
      background-color:whitesmoke;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
      animation: fadeIn 0.5s ease-out;
    }
    
    .container {
      display: flex;
      width: 75%;
      max-width: 1000px;
      height: 75vh;
      min-height: 550px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
      border-radius: 15px;
      overflow: hidden;
      animation: zoomIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      transform-origin: center center;
    }

    @keyframes zoomIn {
      from {
        transform: scale(0.9);
        opacity: 0;
      }
      to {
        transform: scale(1);
        opacity: 1;
      }
    }
    
    .left-panel {
      width: 45%;
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
      transition: all 0.5s ease;
    }

    .left-panel:hover::before {
      background: rgba(0, 0, 0, 0.4);
    }
    
    .left-panel h2, 
    .left-panel p {
      position: relative;
      z-index: 1;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
      transform: translateY(20px);
      opacity: 0;
      animation: slideUpFadeIn 0.8s ease-out 0.3s forwards;
    }

    @keyframes slideUpFadeIn {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    .left-panel h2 {
      font-size: 28px;
      margin: 0;
    }
    
    .left-panel p {
      font-size: 14px;
      margin-top: 10px;
      opacity: 0.9;
      animation-delay: 0.5s;
    }
    
    .right-panel {
      width: 55%;
      background: #fff;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }
    
    .right-panel h2 {
      margin-bottom: 20px;
      color: #0C1E17;
      font-size: 24px;
      transform: translateY(20px);
      opacity: 0;
      animation: slideUpFadeIn 0.8s ease-out 0.4s forwards;
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
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out forwards;
    }

    /* Staggered animations for form elements */
    .form-group:nth-child(1) label { animation-delay: 0.5s; }
    .form-group:nth-child(2) label { animation-delay: 0.6s; }
    
    .form-group input {
      width: 100%;
      padding: 14px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out forwards;
    }

    .form-group:nth-child(1) input { animation-delay: 0.6s; }
    .form-group:nth-child(2) input { animation-delay: 0.7s; }
    
    .form-group input:hover {
      border-color: #999;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-3px);
    }
    
    .form-group input:focus {
      outline: none;
      border-color: #000;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
      transform: translateY(-5px);
    }
    
    .form-group input.error {
      border-color: #ff4444;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }
    
    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: 5px;
      display: block;
      transform: translateY(5px);
      opacity: 0;
      animation: slideUpFadeIn 0.3s ease-out forwards;
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
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 0.8s forwards;
      position: relative;
      overflow: hidden;
    }

    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: 0.5s;
    }

    .login-btn:hover::before {
      left: 100%;
    }
    
    .login-btn:hover {
      background-color: #333;
      transform: translateY(-3px);
      box-shadow: 0 7px 14px rgba(0, 0, 0, 0.2);
    }
    
    .login-btn:active {
      transform: translateY(1px);
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
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 0.9s forwards;
    }
    
    .forgot-password a {
      color: #666;
      font-size: 13px;
      text-decoration: none;
      position: relative;
    }
    
    .forgot-password a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 1px;
      bottom: -2px;
      left: 0;
      background-color: #666;
      transition: width 0.3s;
    }
    
    .forgot-password a:hover::after {
      width: 100%;
    }
    
    .forgot-password a:hover {
      color: #000;
    }
    
    .or-line {
      display: flex;
      align-items: center;
      margin: 20px 0;
      color: #999;
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 1s forwards;
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
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 1.1s forwards;
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

    /* Close button for popup effect */
    .close-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 30px;
      height: 30px;
      background: #f2f2f2;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      transition: all 0.3s ease;
      opacity: 0.7;
      z-index: 1;
    }

    .close-btn:hover {
      opacity: 1;
      transform: rotate(90deg);
      background: #e6e6e6;
    }

    .close-btn::before, .close-btn::after {
      content: '';
      position: absolute;
      width: 15px;
      height: 2px;
      background: #333;
    }

    .close-btn::before {
      transform: rotate(45deg);
    }

    .close-btn::after {
      transform: rotate(-45deg);
    }
    
    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        width: 90%;
        height: auto;
        min-height: auto;
        animation: fadeInUp 0.5s ease-out;
      }
    
      .left-panel, .right-panel {
        width: 100%;
        height: auto;
      }
    
      .left-panel {
        min-height: 200px;
      }
    
      .right-panel {
        padding: 30px;
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
      <div class="close-btn" onclick="window.location.href='homepage.html'"></div>
      
      <h2>Login to Your Account</h2>
      
      <?php if (!empty($error)): ?>
        <div class="error-message animate__animated animate__shakeX" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <form id="loginForm" class="login-form" method="POST" action="login.php">
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        
        <div class="forgot-password">
          <a href="forgot_password.html">Forgot Password?</a>
        </div>
        
        <button type="submit" class="login-btn" id="loginButton">Login</button>
      </form>
      
      <div class="or-line">
        <hr><span>OR</span><hr>
      </div>
      
      <div class="register-link">
        Don't have an account? <a href="register.php">Register</a>
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

      // Add focus animations to inputs
      const inputs = document.querySelectorAll('input');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.style.transform = 'translateY(-5px)';
          this.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.15)';
        });
        
        input.addEventListener('blur', function() {
          this.style.transform = 'translateY(0)';
          this.style.boxShadow = '0 0 0 3px rgba(0, 0, 0, 0.1)';
        });
      });
    });
  </script>
</body>
</html>