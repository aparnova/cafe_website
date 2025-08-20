<?php
session_start();
require 'db.php'; // Make sure this path is correct

// Handle real-time validation AJAX requests
if (isset($_POST['validate']) && $_POST['validate'] === '1') {
    header('Content-Type: application/json');
    
    $response = [
        'valid' => true,
        'message' => ''
    ];
    
    $field = $_POST['field'];
    $value = trim($_POST['value']);
    
    if ($field === 'email') {
        if (empty($value)) {
            $response['valid'] = false;
            $response['message'] = 'Email is required';
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $response['valid'] = false;
            $response['message'] = 'Please enter a valid email';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['valid'] = false;
                $response['message'] = 'Email already exists';
            } else {
                $response['valid'] = true;
                $response['message'] = 'Email is available';
            }
        }
    }
    
    if ($field === 'password') {
        if (empty($value)) {
            $response['valid'] = false;
            $response['message'] = 'Password is required';
        } elseif (strlen($value) < 8) {
            $response['valid'] = false;
            $response['message'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $value)) {
            $response['valid'] = false;
            $response['message'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $value)) {
            $response['valid'] = false;
            $response['message'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $value)) {
            $response['valid'] = false;
            $response['message'] = 'Password must contain at least one number';
        } else {
            $response['valid'] = true;
            $response['message'] = 'Password is strong';
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX request
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $response = [
        'success' => false,
        'errors' => [],
        'message' => ''
    ];
    
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($fullname)) {
        $response['errors']['fullname'] = 'Full name is required';
    }
    
    if (empty($email)) {
        $response['errors']['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Please enter a valid email';
    } else {
        // Check if email already exists - USING MYSQLI NOW
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['errors']['email'] = 'Email already registered';
        }
    }
    
    if (empty($phone)) {
        $response['errors']['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $response['errors']['phone'] = 'Please enter a valid phone number';
    }
    
    if (empty($password)) {
        $response['errors']['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one number';
    }
    
    if ($password !== $confirm_password) {
        $response['errors']['confirm_password'] = 'Passwords do not match';
    }

    // If no errors, register user
    if (empty($response['errors'])) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed_password);
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                $response['success'] = true;
                $response['message'] = 'Registration successful! You can now login.';
            } else {
                $response['errors']['database'] = 'Registration failed. Please try again.';
            }
        } catch (mysqli_sql_exception $e) {
            $response['errors']['database'] = 'Registration failed: ' . $e->getMessage();
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle regular form submission (fallback)
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
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
        // Check if email already exists - USING MYSQLI NOW
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
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
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // If no errors, register user - USING MYSQLI NOW
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed_password);
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $fullname = $email = $phone = $password = $confirm_password = '';
            } else {
                $errors['database'] = 'Registration failed. Please try again.';
            }
        } catch (mysqli_sql_exception $e) {
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
      background-color: whitesmoke;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
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
    }

    .left-panel {
      width: 45%;
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('cofee.jpg') no-repeat left center/cover;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 40px;
      color: #fff;
      position: relative;
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
    }

    .success-message {
      color: #28a745;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: 2px;
      margin-bottom: 5px;
      display: block;
      min-height: 18px;
    }

    .success-validation {
      color: #28a745;
      font-size: 12px;
      margin-top: 2px;
      margin-bottom: 5px;
      display: block;
      min-height: 18px;
    }

    .right-panel form {
      display: flex;
      flex-direction: column;
    }

    .input-group {
      position: relative;
      margin: 12px 0;
    }

    .right-panel input {
      width: 100%;
      padding: 14px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    /* Fixed the shake animation */
    .right-panel input.error {
      border-color: #ff4444;
      animation: shake 0.5s ease-in-out;
    }

    .right-panel input.success {
      border-color: #28a745;
    }

    .validation-spinner {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #333;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      display: none;
    }

    /* Fixed shake animation to only affect horizontal position */
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }

    @keyframes spin {
      to { transform: translateY(-50%) rotate(360deg); }
    }

    .right-panel input:hover {
      border-color: #999;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .right-panel input:focus {
      outline: none;
      border-color: #000;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
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
      overflow: hidden;
    }

    .right-panel button.register-btn:hover {
      background-color: #333333;
      box-shadow: 0 7px 14px rgba(0, 0, 0, 0.2);
    }

    .right-panel button.register-btn:disabled {
      background-color: #666;
      cursor: not-allowed;
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
    }

    .bottom-link a:hover {
      color: #333;
      text-decoration: underline;
    }

    .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-left: 10px;
    }

    /* Close button */
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
    }

    .close-btn:hover {
      background: #e6e6e6;
      transform: rotate(90deg);
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

    /* Success notification */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      border-radius: 8px;
      color: white;
      font-weight: 600;
      z-index: 1000;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.3s ease;
    }

    .notification.success {
      background-color: #28a745;
    }

    .notification.error {
      background-color: #dc3545;
    }

    .notification.show {
      opacity: 1;
      transform: translateX(0);
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        width: 90%;
        height: auto;
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
      <div class="close-btn" onclick="window.location.href='homepage.php'"></div>
      
      <h2>Create Account</h2>
      
      <div id="messageContainer">
        <?php if ($success): ?>
          <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($errors['database'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
      </div>
      
      <form id="registerForm" method="POST" action="register.php" autocomplete="off">
        <input type="hidden" name="ajax" value="1">
        
        <div class="input-group">
          <input type="text" id="fullname" name="fullname" placeholder="Full Name" value="<?php echo htmlspecialchars($fullname ?? ''); ?>" required>
          <div id="fullname-error" class="error-message" style="display: none;"></div>
          <?php if (isset($errors['fullname'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['fullname']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="input-group">
          <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
          <div class="validation-spinner" id="email-spinner"></div>
          <div id="email-error" class="error-message" style="display: none;"></div>
          <div id="email-success" class="success-validation" style="display: none;"></div>
          <?php if (isset($errors['email'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="input-group">
          <input type="tel" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
          <div id="phone-error" class="error-message" style="display: none;"></div>
          <?php if (isset($errors['phone'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['phone']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="input-group">
          <input type="password" id="password" name="password" placeholder="Password" required>
          <div class="validation-spinner" id="password-spinner"></div>
          <div id="password-error" class="error-message" style="display: none;"></div>
          <div id="password-success" class="success-validation" style="display: none;"></div>
          <?php if (isset($errors['password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="input-group">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
          <div id="confirm_password-error" class="error-message" style="display: none;"></div>
          <?php if (isset($errors['confirm_password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
          <?php endif; ?>
        </div>
        
        <button type="submit" class="register-btn" id="submitBtn">
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
      const submitBtn = document.getElementById('submitBtn');
      const messageContainer = document.getElementById('messageContainer');

      // Validation timeout variables
      let emailTimeout;
      let passwordTimeout;

      // Real-time validation function
      function validateField(field, value) {
        const spinner = document.getElementById(field + '-spinner');
        const errorElement = document.getElementById(field + '-error');
        const successElement = document.getElementById(field + '-success');
        const inputElement = document.getElementById(field);

        // Show spinner
        if (spinner) spinner.style.display = 'block';

        // Hide previous messages
        if (errorElement) {
          errorElement.style.display = 'none';
          errorElement.textContent = '';
        }
        if (successElement) {
          successElement.style.display = 'none';
          successElement.textContent = '';
        }

        // Remove previous styling
        inputElement.classList.remove('error', 'success');

        // Create FormData for validation request
        const formData = new FormData();
        formData.append('validate', '1');
        formData.append('field', field);
        formData.append('value', value);

        fetch('register.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Hide spinner
          if (spinner) spinner.style.display = 'none';

          if (data.valid) {
            // Show success message
            if (successElement) {
              successElement.textContent = data.message;
              successElement.style.display = 'block';
            }
            inputElement.classList.add('success');
          } else {
            // Show error message
            if (errorElement) {
              errorElement.textContent = data.message;
              errorElement.style.display = 'block';
            }
            inputElement.classList.add('error');
          }
        })
        .catch(error => {
          console.error('Validation error:', error);
          if (spinner) spinner.style.display = 'none';
        });
      }

      // Email validation on blur and input
      const emailInput = document.getElementById('email');
      emailInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value.length > 0) {
          clearTimeout(emailTimeout);
          emailTimeout = setTimeout(() => {
            validateField('email', value);
          }, 500); // Debounce for 500ms
        }
      });

      emailInput.addEventListener('blur', function() {
        const value = this.value.trim();
        if (value.length > 0) {
          clearTimeout(emailTimeout);
          validateField('email', value);
        }
      });

      // Password validation on blur and input
      const passwordInput = document.getElementById('password');
      passwordInput.addEventListener('input', function() {
        const value = this.value;
        if (value.length > 0) {
          clearTimeout(passwordTimeout);
          passwordTimeout = setTimeout(() => {
            validateField('password', value);
          }, 500); // Debounce for 500ms
        }
      });

      passwordInput.addEventListener('blur', function() {
        const value = this.value;
        if (value.length > 0) {
          clearTimeout(passwordTimeout);
          validateField('password', value);
        }
      });

      // Clear previous errors
      function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message[id*="-error"]');
        errorElements.forEach(element => {
          element.style.display = 'none';
          element.textContent = '';
        });
        
        // Remove error class from inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
          input.classList.remove('error');
        });
      }

      // Display errors
      function displayErrors(errors) {
        clearErrors();
        
        for (const field in errors) {
          const errorElement = document.getElementById(field + '-error');
          const inputElement = document.getElementById(field);
          
          if (errorElement) {
            errorElement.textContent = errors[field];
            errorElement.style.display = 'block';
          }
          
          if (inputElement) {
            inputElement.classList.add('error');
          }
        }
      }

      // Show notification
      function showNotification(message, type = 'success') {
        // Remove existing notification
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
          existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
          notification.classList.add('show');
        }, 100);

        // Hide notification after 4 seconds
        setTimeout(() => {
          notification.classList.remove('show');
          setTimeout(() => {
            notification.remove();
          }, 300);
        }, 4000);
      }

      // AJAX form submission
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        
        // Show loading state
        spinner.style.display = 'inline-block';
        submitBtn.disabled = true;

        // Create FormData object
        const formData = new FormData(form);

        // Make AJAX request
        fetch('register.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          // Hide loading state
          spinner.style.display = 'none';
          submitBtn.disabled = false;

          if (data.success) {
            // Success - show notification and clear form
            showNotification(data.message, 'success');
            form.reset();
            
            // Clear all validation messages
            const validationMessages = document.querySelectorAll('.error-message, .success-validation');
            validationMessages.forEach(msg => {
              msg.style.display = 'none';
            });
            
            // Remove all validation classes
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
              input.classList.remove('error', 'success');
            });
            
            // Optional: redirect to login page after 2 seconds
            setTimeout(() => {
              window.location.href = 'login.php';
            }, 2000);
            
          } else {
            // Display errors
            if (data.errors) {
              displayErrors(data.errors);
              
              // Show general error notification if database error
              if (data.errors.database) {
                showNotification(data.errors.database, 'error');
              }
            }
          }
        })
        .catch(error => {
          // Hide loading state
          spinner.style.display = 'none';
          submitBtn.disabled = false;
          
          console.error('Error:', error);
          showNotification('An error occurred. Please try again.', 'error');
        });
      });
      
      // Real-time password confirmation validation
      const confirmPasswordInput = document.getElementById('confirm_password');
      
      confirmPasswordInput.addEventListener('input', function() {
        const passwordValue = passwordInput.value;
        const confirmValue = this.value;
        const errorElement = document.getElementById('confirm_password-error');
        
        if (passwordValue && confirmValue) {
          if (passwordValue !== confirmValue) {
            this.classList.add('error');
            this.classList.remove('success');
            if (errorElement) {
              errorElement.textContent = 'Passwords do not match';
              errorElement.style.display = 'block';
            }
          } else {
            this.classList.remove('error');
            this.classList.add('success');
            if (errorElement) {
              errorElement.style.display = 'none';
            }
          }
        }
      });
    });
  </script>
</body>
</html>