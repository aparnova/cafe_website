<?php
// Start session for better form handling
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "westleys_resto_cafe";

// Initialize variables
$name = $email = $phone = $subject = $message = "";
$errors = [];
$showSuccessMessage = false;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for success message from session
if (isset($_SESSION['form_success']) && $_SESSION['form_success'] === true) {
    $showSuccessMessage = true;
    unset($_SESSION['form_success']); // Clear it immediately after reading
    // Clear all form variables on success
    $name = $email = $phone = $subject = $message = "";
}

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $name = sanitizeInput($_POST["name"]);
    $email = sanitizeInput($_POST["email"]);
    $phone = sanitizeInput($_POST["phone"]);
    $subject = sanitizeInput($_POST["subject"]);
    $message = sanitizeInput($_POST["message"]);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Clear all form variables before redirect
            $name = $email = $phone = $subject = $message = "";
            
            // Set success flag in session
            $_SESSION['form_success'] = true;
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $errors[] = "Error submitting form. Please try again later.";
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
    }
    // If there are errors, the form will redisplay with values intact for correction
}

// Fetch contact settings from database
$maps_url = '';
$contact_items = [];

// Fetch maps URL
$maps_result = $conn->query("SELECT maps_url FROM contact_settings LIMIT 1");
if ($maps_result && $maps_result->num_rows > 0) {
    $maps_row = $maps_result->fetch_assoc();
    $maps_url = $maps_row['maps_url'];
}

// Fetch contact info items
$contact_result = $conn->query("SELECT * FROM contact_info ORDER BY display_order, id");
if ($contact_result && $contact_result->num_rows > 0) {
    while ($row = $contact_result->fetch_assoc()) {
        $contact_items[] = $row;
    }
}

// Close connection
$conn->close();

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Font & Color Variables */
    :root {
      --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      --heading-font: "Playfair Display", sans-serif;
      --nav-font: "Poppins", sans-serif;
      --background-color: #0c0b09;
      --default-color: rgba(255, 255, 255, 0.7);
      --heading-color: #ffffff;
      --accent-color: #cda45e;
      --surface-color: #29261f;
      --contrast-color: #0c0b09;
    }

    /* General Styles */
    body {
      color: var(--default-color);
      background-color: var(--background-color);
      font-family: var(--default-font);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    a {
      color: var(--accent-color);
      text-decoration: none;
      transition: 0.3s;
    }

    a:hover {
      color: color-mix(in srgb, var(--accent-color), transparent 25%);
    }

    h1, h2, h3, h4, h5, h6 {
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .container {
      width: 100%;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .section {
      padding: 60px 0;
    }

    /* Header Styles */
    .header {
      --background-color: rgba(12, 11, 9, 0.61);
      color: var(--default-color);
      transition: all 0.5s;
      z-index: 997;
      position: fixed;
      width: 100%;
      top: 0;
    }

    .header .branding {
      background-color: var(--background-color);
      min-height: 60px;
      padding: 10px 0;
      transition: 0.3s;
      border-bottom: 1px solid var(--background-color);
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      line-height: 1;
      display: flex;
      align-items: center;
    }

    .header .logo img {
      height: 50px;
      margin-right: 15px;
    }

    .header .logo h1 {
      font-size: 24px;
      margin: 0;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    /* Home Button */
    .home-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border-radius: 50%;
      width: 20px;
      height: 20px;
      transition: all 0.3s ease;
    }

    .home-btn:hover {
      background-color: color-mix(in srgb, var(--accent-color), white 15%);
      transform: translateY(-2px) rotate(45deg);
      box-shadow: 0 5px 15px rgba(205, 164, 94, 0.3);
    }

    .home-btn i {
      font-size: 20px;
      transition: transform 0.3s ease;
    }

    /* Section Title with Underline Animation */
    .section-title {
      padding-bottom: 60px;
      position: relative;
      text-align: center;
    }

    .section-title h2 {
      font-size: 14px;
      font-weight: 500;
      padding: 0;
      line-height: 1px;
      margin: 0;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      position: relative;
    }

    .section-title h2::after {
      content: "";
      width: 120px;
      height: 1px;
      display: inline-block;
      background: var(--accent-color);
      margin: 4px 10px;
    }

    .section-title p {
      color: var(--accent-color);
      margin: 15px 0 0;
      font-size: 36px;
      font-weight: 600;
      font-family: var(--heading-font);
      position: relative;
      display: inline-block;
      cursor: pointer;
    }

    .section-title p::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      background: var(--accent-color);
      bottom: -10px;
      left: 0;
      transition: width 0.3s ease;
    }

    .section-title p:hover::after {
      width: 100%;
    }

    /* Main Content Padding */
    .main-content {
      padding-top: 80px;
    }

    /* Contact Section */
    .contact {
      background: url("../img/about-bg.jpg") center center;
      background-size: cover;
      position: relative;
      padding: 80px 0;
    }

    .contact:before {
      content: "";
      background: color-mix(in srgb, var(--background-color), transparent 12%);
      position: absolute;
      bottom: 0;
      top: 0;
      left: 0;
      right: 0;
    }

    .contact .container {
      position: relative;
      z-index: 2;
    }

    .contact iframe {
      border: 0;
      width: 100%;
      height: 400px;
      margin-bottom: 50px;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .contact iframe:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
    }

    /* Table layout for contact section */
    .contact-table {
      display: table;
      width: 100%;
      border-collapse: separate;
      border-spacing: 30px;
    }
    
    .contact-row {
      display: table-row;
    }
    
    .contact-info-cell {
      display: table-cell;
      width: 40%;
      vertical-align: top;
      padding: 40px;
      background: color-mix(in srgb, var(--surface-color), transparent 20%);
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .contact-form-cell {
      display: table-cell;
      width: 60%;
      vertical-align: top;
      padding: 45px;
      background: color-mix(in srgb, var(--surface-color), transparent 20%);
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Contact Form Styles */
    .contact-form {
      max-width: 100%;
      margin: 0 auto;
    }

    .contact-form h3 {
      color: var(--heading-color);
      font-size: 26px;
      margin-bottom: 35px;
      font-family: var(--heading-font);
      text-align: center;
      position: relative;
    }

    .contact-form h3::after {
      content: '';
      display: block;
      width: 60px;
      height: 2px;
      background: var(--accent-color);
      margin: 18px auto;
      transition: width 0.3s ease;
    }

    .contact-form h3:hover::after {
      width: 100px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 25px;
      align-items: stretch;
    }

    .form-group {
      flex: 1;
      min-width: 0;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    .form-group.half-width {
      flex: 0 0 calc(50% - 10px);
    }

    .form-group.full-width {
      flex: 0 0 100%;
    }

    .form-control {
      width: 100%;
      padding: 18px 20px;
      font-size: 16px;
      color: var(--default-color);
      background-color: color-mix(in srgb, var(--surface-color), transparent 30%);
      border: 2px solid color-mix(in srgb, var(--accent-color), transparent 80%);
      border-radius: 6px;
      transition: all 0.4s;
      box-sizing: border-box;
      font-family: var(--default-font);
      line-height: 1.4;
    }

    .form-control:focus {
      border-color: var(--accent-color);
      outline: none;
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color), transparent 85%);
      transform: translateY(-2px);
    }

    .form-control::placeholder {
      color: color-mix(in srgb, var(--default-color), transparent 45%);
      transition: opacity 0.3s ease;
    }

    .form-control:focus::placeholder {
      opacity: 0.6;
    }

    textarea.form-control {
      min-height: 180px;
      resize: vertical;
      padding: 20px;
      line-height: 1.6;
    }

    /* Submit Button Container */
    .submit-container {
      display: flex;
      justify-content: center;
      margin-top: 35px;
      padding-top: 10px;
    }

    /* Submit Button with Icon Animation */
    .submit-btn {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 18px 40px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      min-width: 200px;
      transition: all 0.4s ease;
      text-transform: uppercase;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      letter-spacing: 0.5px;
    }

    .submit-btn .btn-text {
      transition: transform 0.3s ease;
    }

    .submit-btn .icon {
      position: absolute;
      right: -35px;
      opacity: 0;
      transition: all 0.4s ease;
      font-size: 18px;
    }

    .submit-btn:hover {
      background-color: color-mix(in srgb, var(--accent-color), black 10%);
      padding-right: 55px;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(205, 164, 94, 0.3);
    }

    .submit-btn:hover .btn-text {
      transform: translateX(-18px);
    }

    .submit-btn:hover .icon {
      right: 20px;
      opacity: 1;
    }

    .submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Info Items */
    .info-items-container {
      display: flex;
      flex-direction: column;
      gap: 80px;
    }

    .info-item {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      transition: all 0.3s ease;
    }

    .info-item:hover {
      transform: translateX(10px);
    }

    .info-item i {
      color: var(--contrast-color);
      background: var(--accent-color);
      font-size: 20px;
      width: 44px;
      height: 44px;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 4px;
      flex-shrink: 0;
      transition: all 0.3s ease;
    }

    .info-item:hover i {
      transform: rotate(10deg);
      background: color-mix(in srgb, var(--accent-color), white 15%);
    }

    .info-item-content {
      display: flex;
      flex-direction: column;
    }

    .info-item h3 {
      font-size: 18px;
      margin: 0 0 8px 0;
      color: var(--heading-color);
      transition: color 0.3s ease;
    }

    .info-item:hover h3 {
      color: var(--accent-color);
    }

    .info-item p {
      margin: 0;
      line-height: 1.6;
      transition: transform 0.3s ease;
    }

    .info-item:hover p {
      transform: translateX(5px);
    }

    /* Form message styles */
    .form-message {
      margin: 25px 0;
      padding: 18px 22px;
      border-radius: 6px;
      text-align: center;
      font-weight: 500;
    }

    .form-message.success {
      background: rgba(205, 164, 94, 0.15);
      border: 2px solid var(--accent-color);
      color: var(--accent-color);
    }

    .form-message.error {
      background: rgba(220, 53, 69, 0.1);
      border: 2px solid #dc3545;
      color: #dc3545;
    }

    .form-message ul {
      margin: 12px 0 0 0;
      padding: 0;
      list-style: none;
      text-align: left;
    }

    .form-message li {
      padding: 4px 0;
    }

    /* Notification styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border-radius: 4px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      z-index: 9999;
      transform: translateX(200%);
      transition: transform 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification.show {
      transform: translateX(0);
    }

    .notification i {
      font-size: 20px;
    }

    /* Fade out animation */
    .fade-out {
      animation: fadeOut 3s ease-out forwards;
    }

    @keyframes fadeOut {
      0% {
        opacity: 1;
      }
      70% {
        opacity: 1;
      }
      100% {
        opacity: 0;
        display: none;
      }
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
      .contact-table,
      .contact-row,
      .contact-info-cell,
      .contact-form-cell {
        display: block;
        width: 100%;
      }
      
      .contact-table {
        border-spacing: 0;
      }
      
      .contact-info-cell {
        margin-bottom: 30px;
      }
      
      .contact-form-cell {
        padding: 35px;
      }
      
      .form-group.half-width {
        flex: 0 0 100%;
      }

      .submit-btn:hover {
        padding-right: 40px;
      }

      .home-btn {
        width: 40px;
        height: 40px;
        padding: 10px;
      }

      .home-btn i {
        font-size: 18px;
      }
    }

    @media (max-width: 768px) {
      .contact {
        padding: 60px 0;
      }
      
      .contact-info-cell,
      .contact-form-cell {
        padding: 25px;
      }
      
      .info-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .info-item:hover {
        transform: translateY(-5px);
      }

      .form-row {
        gap: 15px;
        margin-bottom: 20px;
      }

      .form-control {
        padding: 16px 18px;
      }

      .submit-btn {
        min-width: 180px;
        padding: 16px 35px;
      }
      
      .section-title p {
        font-size: 28px;
      }

      .header .logo h1 {
        font-size: 18px;
      }

      .home-btn {
        width: 38px;
        height: 38px;
        padding: 9px;
      }

      .home-btn i {
        font-size: 16px;
      }
    }
  </style>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>

  <!-- Header -->
  <header class="header">
    <div class="branding">
      <div class="container">
        <div class="logo">
          <img src="img.png" alt="Westley's Resto Cafe">
          <h1>Westley's Resto Cafe</h1>
        </div>
        <a href="homepage.php" class="home-btn">
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>
  </header>

  <!-- Notification -->
  <div id="notification" class="notification">
    <i class="bi bi-check-circle-fill"></i>
    <span>Your message has been sent successfully!</span>
  </div>

  <main class="main-content">
    <!-- Contact Section -->
    <section id="contact" class="contact section">
      <div class="container">
        <!-- Section Title -->
        <div class="section-title">
          <h2>Contact</h2>
          <p>Get in Touch</p>
        </div><!-- End Section Title -->

        <!-- Google Maps -->
        <div class="mb-5">
          <?php if (!empty($maps_url)): ?>
            <iframe src="<?php echo htmlspecialchars($maps_url); ?>" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <?php else: ?>
            <p>No maps URL configured. Please set up the Google Maps URL in the admin panel.</p>
          <?php endif; ?>
        </div><!-- End Google Maps -->

        <div class="contact-table">
          <div class="contact-row">
            <div class="contact-info-cell">
              <div class="info-items-container">
                <?php if (count($contact_items) > 0): ?>
                  <?php foreach ($contact_items as $item): ?>
                    <div class="info-item">
                      <i class="<?php echo htmlspecialchars($item['icon_class']); ?>"></i>
                      <div class="info-item-content">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                      </div>
                    </div><!-- End Info Item -->
                  <?php endforeach; ?>
                <?php else: ?>
                  <p>No contact information available. Please add contact items in the admin panel.</p>
                <?php endif; ?>
              </div>
            </div>

            <div class="contact-form-cell">
              <div class="contact-form">
                <h3>Send Us a Message</h3>
                
                <?php if ($showSuccessMessage): ?>
                  <div class="form-message success fade-out" id="successMessage">
                    <i class="bi bi-check-circle-fill"></i> Your message has been sent successfully!
                  </div>
                <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($errors)): ?>
                  <div class="form-message error">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    <strong>Error submitting form:</strong>
                    <ul>
                      <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
                
                <form method="post" action="" id="contactForm">
                  <div class="form-row">
                    <div class="form-group half-width">
                      <input type="text" class="form-control" name="name" placeholder="Your Name" required autocomplete="off" value="<?php echo (!empty($errors) && !$showSuccessMessage) ? htmlspecialchars($name) : ''; ?>">
                    </div>
                    <div class="form-group half-width">
                      <input type="email" class="form-control" name="email" placeholder="Your Email" required autocomplete="off" value="<?php echo (!empty($errors) && !$showSuccessMessage) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group half-width">
                      <input type="tel" name="phone" id="phone" class="form-control" placeholder="Phone" autocomplete="off" required maxlength="10" pattern="[0-9]{10}" value="<?php echo (!empty($errors) && !$showSuccessMessage) ? htmlspecialchars($phone) : ''; ?>">
                    </div>
                    <div class="form-group half-width">
                      <input type="text" class="form-control" name="subject" placeholder="Subject" required autocomplete="off" value="<?php echo (!empty($errors) && !$showSuccessMessage) ? htmlspecialchars($subject) : ''; ?>">
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group full-width">
                      <textarea class="form-control" name="message" placeholder="Your Message" required><?php echo (!empty($errors) && !$showSuccessMessage) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                  </div>
                  
                  <div class="submit-container">
                    <button type="submit" class="submit-btn" id="submitBtn">
                      <span class="btn-text">SEND MESSAGE</span>
                      <i class="bi bi-send icon"></i>
                    </button>
                  </div>
                </form>
              </div>
            </div><!-- End Contact Form -->
          </div>
        </div>
      </div>
    </section><!-- /Contact Section -->
  </main>

  <script>
    // Force form reset on page load (including back button navigation)
    window.addEventListener('pageshow', function(event) {
      // Check if page is loaded from cache (back/forward button)
      if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        // Clear the form
        document.getElementById('contactForm').reset();
      }
    });

    // Clear form on page load
    window.addEventListener('load', function() {
      <?php if ($showSuccessMessage): ?>
        // If success message is showing, clear the form
        document.getElementById('contactForm').reset();
      <?php endif; ?>
    });

    // JavaScript to enhance form submission
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      const btnText = submitBtn.querySelector('.btn-text');
      
      // Show loading state
      btnText.textContent = 'SENDING...';
      submitBtn.disabled = true;
    });

    // Function to show notification
    function showNotification() {
      const notification = document.getElementById('notification');
      notification.classList.add('show');
      
      // Hide after 3 seconds
      setTimeout(() => {
        notification.classList.remove('show');
      }, 3000);
    }
    
    // Show notification if form was successfully submitted
    <?php if ($showSuccessMessage): ?>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification();
        
        // Auto-hide success message after 3 seconds
        setTimeout(() => {
          const successMessage = document.getElementById('successMessage');
          if (successMessage) {
            successMessage.style.display = 'none';
          }
        }, 3000);
      });
    <?php endif; ?>
  </script>
</body>
</html>