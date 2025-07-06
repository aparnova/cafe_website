<?php
session_start();
require_once 'db.php'; // Make sure this path is correct

// Rest of your code...
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($fullname)) {
        $errors['fullname'] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullname, $email, $phone, $hashed_password]);
            
            $success = 'Registration successful! You can now login.';
            
            // Clear form
            $fullname = $email = $phone = $password = $confirm_password = '';
        } catch (PDOException $e) {
            $errors['database'] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | Westley's Resto Cafe</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  
  <style>
    * {
      box-sizing: border-box;
      transition: all 0.3s ease;
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: #f2f2f2;
      color: #333;
    }

    .container {
      display: flex;
      height: 100vh;
    }

    .left-panel {
      width: 50%;
      background: url("cofee.jpg") no-repeat center center/cover;
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
      color: #fff;
    }

    .left-panel p {
      font-size: 14px;
      margin-top: 10px;
      color: rgba(255, 255, 255, 0.9);
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
      margin-bottom: 10px;
      color: #0C1E17;
      font-size: 24px;
    }

    .success-message {
      color: #28a745;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: -5px;
      margin-bottom: 5px;
    }

    .right-panel form {
      display: flex;
      flex-direction: column;
    }

    .right-panel input {
      margin: 10px 0;
      padding: 14px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }

    .right-panel input.error {
      border-color: #ff4444;
    }

    .right-panel input:hover {
      border-color: #999;
      box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }

    .right-panel input:focus {
      outline: none;
      border-color: #000;
      box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    }

    .right-panel button.register-btn {
      margin-top: 20px;
      padding: 14px;
      background-color: #000000;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 15px;
      position: relative;
      transition: all 0.3s ease;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .right-panel button.register-btn:hover {
      background-color: #333333;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .right-panel button.register-btn:active {
      transform: translateY(0);
      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
    }

    .bottom-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #000;
    }

    .bottom-link a {
      color: #000;
      text-decoration: none;
      font-weight: 600;
      position: relative;
      padding-bottom: 2px;
    }

    .bottom-link a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: #000;
      transition: width 0.3s ease;
    }

    .bottom-link a:hover::after {
      width: 100%;
    }

    .bottom-link a:hover {
      color: #333;
    }

    .spinner {
      display: none;
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: translateY(-50%) rotate(360deg); }
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
      <h2>Join Westley's Resto Cafe</h2>
      <p>Order delicious meals and relax with our cozy atmosphere.</p>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
      <h2>Create Customer Account</h2>
      
      <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      
      <?php if (isset($errors['database'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($errors['database']); ?></div>
      <?php endif; ?>
      
      <form id="registerForm" method="POST" action="register.php">
        <input type="text" id="fullname" name="fullname" placeholder="Full Name" value="<?php echo htmlspecialchars($fullname ?? ''); ?>" required>
        <?php if (isset($errors['fullname'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['fullname']); ?></div>
        <?php endif; ?>
        
        <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        <?php if (isset($errors['email'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
        <?php endif; ?>
        
        <input type="tel" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
        <?php if (isset($errors['phone'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['phone']); ?></div>
        <?php endif; ?>
        
        <input type="password" id="password" name="password" placeholder="Password" required>
        <?php if (isset($errors['password'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
        <?php endif; ?>
        
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
        <?php if (isset($errors['confirm_password'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
        <?php endif; ?>
        
        <button type="submit" class="register-btn">
          Register
          <span class="spinner" id="spinner"></span>
        </button>
      </form>

      <div class="bottom-link">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('registerForm');
      const spinner = document.getElementById('spinner');
      
      form.addEventListener('submit', function(e) {
        // Client-side validation
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        
        if (password.value !== confirm_password.value) {
          e.preventDefault();
          alert('Passwords do not match');
          return false;
        }
        
        if (password.value.length < 8) {
          e.preventDefault();
          alert('Password must be at least 8 characters');
          return false;
        }
        
        // Show loading spinner
        spinner.style.display = 'block';
      });
    });
  </script>
</body>
</html>