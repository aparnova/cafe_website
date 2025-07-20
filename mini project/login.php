<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Check Admin table
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows === 1) {
        $admin = $adminResult->fetch_assoc();
        if ($admin['password'] === $password) {
            $_SESSION['user'] = $admin['name'];
            $_SESSION['role'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // 2. Check Delivery Boy table
    $stmt = $conn->prepare("SELECT * FROM delivery_boys WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $deliveryResult = $stmt->get_result();

    if ($deliveryResult->num_rows === 1) {
        $delivery = $deliveryResult->fetch_assoc();
        if ($delivery['password'] === $password) {
            $_SESSION['user'] = $delivery['name'];
            $_SESSION['role'] = 'delivery';
            $_SESSION['delivery_id'] = $delivery['id'];
            header("Location: delivery_dashboard.php");
            exit();
        }
    }

    // 3. Check Users (Customers) table
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows === 1) {
        $user = $userResult->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['fullname'];
            $_SESSION['role'] = 'customer';
            $_SESSION['user_id'] = $user['id'];
            header("Location: homepage.php");
            exit();
        }
    }

    $error = "Invalid email or password!";
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
    
    .form-group input.error-field {
      border-color: #ff4444;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }
    
    .form-error {
      color: #ff4444;
      font-size: 14px;
      margin: 10px 0;
      padding: 10px;
      border-radius: 5px;
      background-color: rgba(255, 68, 68, 0.1);
      border-left: 3px solid #ff4444;
      display: none;
      animation: slideUpFadeIn 0.3s ease-out;
    }
    
    .field-error {
      color: #ff4444;
      font-size: 12px;
      margin-top: 5px;
      display: none;
      animation: slideUpFadeIn 0.3s ease-out;
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
      <div class="close-btn" onclick="window.location.href='homepage.php'"></div>
      
      <h2>Login to Your Account</h2>
      
      <!-- Form error message -->
      <div id="form-error" class="form-error" <?php echo empty($error) ? 'style="display: none;"' : ''; ?>>
        <?php echo htmlspecialchars($error); ?>
      </div>
    
      <form id="loginForm" class="login-form" method="POST" action="login.php">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
          <div id="email-error" class="field-error"></div>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <div id="password-error" class="field-error"></div>
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
      const formError = document.getElementById('form-error');
      const emailError = document.getElementById('email-error');
      const passwordError = document.getElementById('password-error');
      
      function clearErrors() {
        formError.style.display = 'none';
        emailError.style.display = 'none';
        passwordError.style.display = 'none';
        document.getElementById('email').classList.remove('error-field');
        document.getElementById('password').classList.remove('error-field');
      }
      
      function showFieldError(field, message) {
        const errorElement = document.getElementById(field + '-error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        document.getElementById(field).classList.add('error-field');
      }
      
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();
        
        // Client-side validation
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        let isValid = true;
        
        if (!email) {
          showFieldError('email', 'Email is required');
          isValid = false;
        } else if (!validateEmail(email)) {
          showFieldError('email', 'Please enter a valid email address');
          isValid = false;
        }
        
        if (!password) {
          showFieldError('password', 'Password is required');
          isValid = false;
        }
        
        if (!isValid) return false;
        
        // Submit the form if validation passes
        form.submit();
      });

      function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
      }
      
      // Input field focus/blur effects
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