<?php
// Database connection and form processing at the top of the file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "westleys_resto_cafe";

// Initialize variables
$name = $email = $phone = $subject = $message = "";
$errors = [];
$success = false;

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
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
            $success = true;
            // Clear form fields
            $name = $email = $phone = $subject = $message = "";
        } else {
            $errors[] = "Error submitting form. Please try again later.";
        }
        
        $stmt->close();
    }
    
    $conn->close();
}

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
    /* Font & Color Variables - Exact copy from menu page */
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

    /* General Styles - Exact copy from menu page */
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

    /* Header Styles - Exact copy from menu page */
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

    /* Section Title with Underline Animation - Exact copy from menu page */
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

    /* Main Content Padding - Matching menu page */
    .main-content {
      padding-top: 80px;
    }

    /* Contact Section - Using menu page's background approach */
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
      padding: 40px;
      background: color-mix(in srgb, var(--surface-color), transparent 20%);
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Contact Form Styles */
    .contact-form h3 {
      color: var(--heading-color);
      font-size: 24px;
      margin-bottom: 30px;
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
      margin: 15px auto;
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
    }

    .form-group {
      flex: 1;
      min-width: 0;
      position: relative;
    }

    .form-group.half-width {
      flex: 0 0 calc(50% - 10px);
    }

    .form-control {
      width: 100%;
      padding: 15px;
      font-size: 16px;
      color: var(--default-color);
      background-color: color-mix(in srgb, var(--surface-color), transparent 30%);
      border: 1px solid color-mix(in srgb, var(--accent-color), transparent 80%);
      border-radius: 4px;
      transition: all 0.4s;
    }

    .form-control:focus {
      border-color: var(--accent-color);
      outline: none;
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color), transparent 80%);
      transform: translateY(-2px);
    }

    .form-control::placeholder {
      color: color-mix(in srgb, var(--default-color), transparent 50%);
      transition: opacity 0.3s ease;
    }

    .form-control:focus::placeholder {
      opacity: 0.5;
    }

    textarea.form-control {
      min-height: 180px;
      resize: vertical;
    }

    /* Submit Button with Icon Animation */
    .submit-btn {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 15px 30px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 4px;
      cursor: pointer;
      width: 105%;
      transition: all 0.4s ease;
      text-transform: uppercase;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .submit-btn .btn-text {
      transition: transform 0.3s ease;
    }

    .submit-btn .icon {
      position: absolute;
      right: -30px;
      opacity: 0;
      transition: all 0.4s ease;
      font-size: 20px;
    }

    .submit-btn:hover {
      background-color: color-mix(in srgb, var(--accent-color), black 10%);
      padding-right: 50px;
    }

    .submit-btn:hover .btn-text {
      transform: translateX(-15px);
    }

    .submit-btn:hover .icon {
      right: 20px;
      opacity: 1;
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
      
      .form-group.half-width {
        flex: 0 0 100%;
      }

      .submit-btn:hover {
        padding-right: 30px;
      }
    }

    @media (max-width: 768px) {
      .contact {
        padding: 60px 0;
      }
      
      .contact-info-cell,
      .contact-form-cell {
        padding: 30px;
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
        margin-bottom: 15px;
      }
      
      .section-title p {
        font-size: 28px;
      }
    }

    /* Form message styles */
    .form-message {
      display: none;
      margin-top: 20px;
      padding: 15px;
      border-radius: 4px;
    }

    .form-message.success {
      background: rgba(205, 164, 94, 0.2);
      border-left: 4px solid var(--accent-color);
      color: var(--accent-color);
    }

    .form-message.error {
      background: rgba(220, 53, 69, 0.1);
      border-left: 4px solid #dc3545;
      color: #dc3545;
    }

    .form-message ul {
      margin: 10px 0 0 20px;
      padding: 0;
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
          <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48389.78314118045!2d-74.006138!3d40.710059!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div><!-- End Google Maps -->

        <div class="contact-table">
          <div class="contact-row">
            <div class="contact-info-cell">
              <div class="info-items-container">
                <div class="info-item">
                  <i class="bi bi-geo-alt"></i>
                  <div class="info-item-content">
                    <h3>Location</h3>
                    <p>Metro Pillar 481, Ground Floor, Kaliyath Building, Palarivattom,Kerala 682025</p>
                  </div>
                </div><!-- End Info Item -->

                <div class="info-item">
                  <i class="bi bi-clock"></i>
                  <div class="info-item-content">
                    <h3>Open Hours</h3>
                    <p>Monday-Saturday:<br>8:00 AM - 11:00 PM</p>
                  </div>
                </div><!-- End Info Item -->

                <div class="info-item">
                  <i class="bi bi-telephone"></i>
                  <div class="info-item-content">
                    <h3>Call Us</h3>
                    <p>+1 5589 55488 55</p>
                  </div>
                </div><!-- End Info Item -->

                <div class="info-item">
                  <i class="bi bi-envelope"></i>
                  <div class="info-item-content">
                    <h3>Email Us</h3>
                    <p>westleysrestocafe.com</p>
                  </div>
                </div><!-- End Info Item -->
              </div>
            </div>

            <div class="contact-form-cell">
              <div class="contact-form">
                <h3>Send Us a Message</h3>
                
                <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                  <?php if ($success): ?>
                    <div class="form-message success">
                      <i class="bi bi-check-circle-fill"></i> Your message has been sent successfully!
                    </div>
                  <?php elseif (!empty($errors)): ?>
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
                <?php endif; ?>
                
                <form method="post" action="" id="contactForm">
                  <div class="form-row">
                    <div class="form-group half-width">
                      <input type="text" class="form-control" name="name" placeholder="Your Name" required autocomplete="off" value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    <div class="form-group half-width">
                      <input type="email" class="form-control" name="email" placeholder="Your Email" required autocomplete="off" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group half-width">
                      <input type="tel" class="form-control" name="phone" placeholder="Your Phone" required autocomplete="off" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                    <div class="form-group half-width">
                      <input type="text" class="form-control" name="subject" placeholder="Subject" required autocomplete="off" value="<?php echo htmlspecialchars($subject); ?>">
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <textarea class="form-control" name="message" placeholder="Your Message" required><?php echo htmlspecialchars($message); ?></textarea>
                  </div><br>
                  
                  <button type="submit" class="submit-btn" id="submitBtn">
                    <span class="btn-text">SEND MESSAGE</span>
                    <i class="bi bi-send icon"></i>
                  </button>
                </form>
              </div>
            </div><!-- End Contact Form -->
          </div>
        </div>
      </div>
    </section><!-- /Contact Section -->
  </main>

  <script>
    // JavaScript to enhance form submission
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      const btnText = submitBtn.querySelector('.btn-text');
      
      // Show loading state
      btnText.textContent = 'SENDING...';
      submitBtn.disabled = true;
      
      // If the form is valid and being submitted
      if (this.checkValidity()) {
        // After form submission, show notification if successful
        <?php if ($success): ?>
          showNotification();
        <?php endif; ?>
      }
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
    <?php if ($success): ?>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification();
      });
    <?php endif; ?>
  </script>
</body>
</html>