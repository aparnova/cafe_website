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
    
    // In register.php, add password strength validation
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number';
    }

    function isPasswordCompromised($password) {
        $hash = sha1($password);
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        $response = file_get_contents("https://api.pwnedpasswords.com/range/".$prefix);
        return strpos($response, strtoupper($suffix)) !== false;
    }

    // Usage in registration:
    if (isPasswordCompromised($password)) {
        $errors['password'] = 'This password has been compromised in a data breach. Please choose a different one.';
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
  
  <!-- Animate.css for smooth animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  
  <style>
    * {
      box-sizing: border-box;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
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
      width: 80%;
      max-width: 900px;
      height: auto;
      min-height: 500px;
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
      background: url("cofee.jpg") no-repeat left center/cover;
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
      color: #fff;
    }

    .left-panel p {
      font-size: 14px;
      margin-top: 10px;
      color: rgba(255, 255, 255, 0.9);
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
      margin-bottom: 10px;
      color: #0C1E17;
      font-size: 24px;
      transform: translateY(20px);
      opacity: 0;
      animation: slideUpFadeIn 0.8s ease-out 0.4s forwards;
    }

    .success-message {
      color: #28a745;
      margin-bottom: 20px;
      font-size: 14px;
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 0.6s forwards;
    }

    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: -5px;
      margin-bottom: 5px;
      transform: translateY(5px);
      opacity: 0;
      animation: slideUpFadeIn 0.3s ease-out forwards;
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
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out forwards;
    }

    /* Staggered animations for form inputs */
    .right-panel input:nth-child(1) { animation-delay: 0.5s; }
    .right-panel input:nth-child(2) { animation-delay: 0.6s; }
    .right-panel input:nth-child(3) { animation-delay: 0.7s; }
    .right-panel input:nth-child(4) { animation-delay: 0.8s; }
    .right-panel input:nth-child(5) { animation-delay: 0.9s; }

    .right-panel input.error {
      border-color: #ff4444;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }

    .right-panel input:hover {
      border-color: #999;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-3px);
    }

    .right-panel input:focus {
      outline: none;
      border-color: #000;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
      transform: translateY(-5px);
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
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 1s forwards;
      overflow: hidden;
    }

    .right-panel button.register-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: 0.5s;
    }

    .right-panel button.register-btn:hover::before {
      left: 100%;
    }

    .right-panel button.register-btn:hover {
      background-color: #333333;
      transform: translateY(-3px);
      box-shadow: 0 7px 14px rgba(0, 0, 0, 0.2);
    }

    .right-panel button.register-btn:active {
      transform: translateY(1px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .bottom-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #000;
      transform: translateY(10px);
      opacity: 0;
      animation: slideUpFadeIn 0.5s ease-out 1.1s forwards;
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
      <h2>Join Westley's Resto Cafe</h2>
      <p>Order delicious meals and relax with our cozy atmosphere.</p>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
      <div class="close-btn" onclick="window.location.href='homepage.html'"></div>
      
      <h2>Create Customer Account</h2>
      
      <?php if ($success): ?>
        <div class="success-message animate__animated animate__pulse"><?php echo htmlspecialchars($success); ?></div>
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
      const passwordInput = document.getElementById('password');
    
      // Form submission handler
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