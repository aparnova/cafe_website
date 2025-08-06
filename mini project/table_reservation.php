<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'westleys_resto_cafe');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper: generate unique booking id (8 chars alphanumeric)
function generateBookingId($conn) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $id = '';
        for ($i = 0; $i < 8; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE booking_id = ? LIMIT 1");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $id;
}

// Variables for page rendering
$response = ['success' => false, 'message' => ''];
$status_result_html = '';
$show_status_modal = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'check_status')) {
    if (!isset($_POST['form_token']) || !isset($_SESSION['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $response['message'] = 'Invalid form submission. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $time_ampm = trim($_POST['time_ampm'] ?? '');
        $people = (int)($_POST['people'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        $full_time = date("H:i:s", strtotime("$time $time_ampm"));

        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
        if (empty($phone)) $errors[] = "Phone number is required";
        if (empty($date)) $errors[] = "Date is required";
        if (empty($time)) $errors[] = "Time is required";
        if ($people < 1 || $people > 20) $errors[] = "Number of guests must be 1-20";

        if (empty($errors)) {
            $booking_id = generateBookingId($conn);
            $status = 'pending';

            $stmt = $conn->prepare("INSERT INTO reservations (name, email, phone, date, time, people, message, booking_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $phone, $date, $full_time, $people, $message, $booking_id, $status])) {
                $formatted_time = date("g:i A", strtotime("$time $time_ampm"));
                $formatted_date = date("F j, Y", strtotime($date));
                $response = [
                    'success' => true,
                    'message' => "Your reservation for $formatted_date at $formatted_time has been confirmed and is pending admin approval. Your Booking ID is <strong>$booking_id</strong>."
                ];
                $_SESSION['success_message'] = $response['message'];
                $_SESSION['last_booking_id'] = $booking_id;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = implode("<br>", $errors);
        }
    }
}

// Handle status check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_status') {
    $booking_id = trim($_POST['booking_id'] ?? '');
    
    if (!empty($booking_id)) {
        $stmt = $conn->prepare("SELECT name, email, phone, date, time, people, message, status FROM reservations WHERE booking_id = ? LIMIT 1");
        if ($stmt->bind_param("s", $booking_id) && $stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($r_name, $r_email, $r_phone, $r_date, $r_time, $r_people, $r_message, $r_status);
                $stmt->fetch();
                
                $status_msg = match($r_status) {
                    'confirmed' => 'Your booking is accepted',
                    'cancelled' => 'Sorry, your booking was not accepted',
                    default => 'Pending admin approval'
                };
                
                $formatted_date = date("F j, Y", strtotime($r_date));
                $formatted_time = date("g:i A", strtotime($r_time));
                
                $status_result_html = '<strong>' . htmlspecialchars($status_msg) . '</strong><br>';
                $status_result_html .= '<div style="margin-top:8px;font-size:14px;">';
                $status_result_html .= '<div><strong>Name:</strong> ' . htmlspecialchars($r_name) . '</div>';
                $status_result_html .= '<div><strong>Date:</strong> ' . htmlspecialchars($formatted_date) . '</div>';
                $status_result_html .= '<div><strong>Time:</strong> ' . htmlspecialchars($formatted_time) . '</div>';
                $status_result_html .= '<div><strong>Guests:</strong> ' . htmlspecialchars($r_people) . '</div>';
                if (!empty($r_message)) $status_result_html .= '<div><strong>Requests:</strong> ' . htmlspecialchars($r_message) . '</div>';
                $status_result_html .= '</div>';
                
                $show_status_modal = true;
            } else {
                $status_result_html = '<strong>Booking ID not found.</strong>';
                $show_status_modal = true;
            }
        }
        $stmt->close();
    } else {
        $status_result_html = '<strong>Please enter a booking ID.</strong>';
        $show_status_modal = true;
    }
}

$form_token = bin2hex(random_bytes(32));
$_SESSION['form_token'] = $form_token;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book A Table - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
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
      --success-color: #4CAF50;
      --error-color: #F44336;
      --transition: all 0.3s ease;
    }
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: var(--default-font); color:var(--default-color); background-color:var(--background-color); line-height:1.6; }
    .container { width:100%; max-width:1140px; margin:0 auto; padding:0 15px; }
    .section { padding:60px 0; }
    .header { background-color: rgba(12, 11, 9, 0.61); position: fixed; width: 100%; top: 0; z-index: 1000; }
    .header .branding { min-height: 60px; padding: 10px 0; border-bottom: 1px solid var(--background-color); }
    .header .container { display:flex; justify-content:space-between; align-items:center; }
    .header .logo { display:flex; align-items:center; }
    .header .logo img { height:50px; margin-right:15px; }
    .header .logo h1 { font-size:24px; margin:0; color:var(--heading-color); font-family:var(--heading-font); }
    
    /* New top status check button */
    .top-status-check {
      position: fixed;
      top: 20px;
      right: 30px;
      background: rgba(205,164,94,0.9);
      color: var(--contrast-color);
      border: 1px solid rgba(205,164,94,0.9);
      width: 42px;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      cursor: pointer;
      z-index: 1001;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      transition: var(--transition);
    }
    .top-status-check:hover {
      background: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    
    .section-title { padding-bottom: 60px; text-align:center; }
    .section-title h2 { font-size:14px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color: color-mix(in srgb, var(--default-color), transparent 30%); }
    .section-title h2::after { content: ""; width: 120px; height: 1px; display: inline-block; background: var(--accent-color); margin: 4px 10px; }
    .section-title p { color: var(--accent-color); margin: 15px 0 0; font-size: 36px; font-weight: 600; font-family: var(--heading-font); display:inline-block; }
    .section-title p::after { content: ''; position: absolute; width:0; height:2px; background: var(--accent-color); bottom:-10px; left:0; transition: width 0.3s ease; }
    .section-title p:hover::after { width:100%; }
    .book-a-table { background: url("../img/about-bg.jpg") center center; background-size: cover; position: relative; padding: 80px 0; padding-top: 140px; }
    .book-a-table::before { content:""; background: color-mix(in srgb, var(--background-color), transparent 12%); position:absolute; inset:0; }
    .book-a-table .container { position: relative; z-index:2; }
    .form-container { max-width:800px; margin:0 auto; background: rgba(41, 38, 31, 0.85); border-radius:10px; padding:40px; box-shadow:0 10px 30px rgba(0,0,0,0.3); border:1px solid rgba(205,164,94,0.3); backdrop-filter: blur(5px); position:relative; z-index: 1; }
    .form-progress { display:flex; justify-content:space-between; margin-bottom:40px; position:relative; }
    .form-progress::before { content:''; position:absolute; top:50%; left:0; right:0; height:2px; background:rgba(205,164,94,0.2); transform: translateY(-50%); z-index:1; }
    .progress-step { width:40px; height:40px; border-radius:50%; background: rgba(205,164,94,0.2); display:flex; align-items:center; justify-content:center; color:var(--default-color); font-weight:bold; position:relative; z-index:2; transition: var(--transition); }
    .progress-step.active { background: var(--accent-color); color: var(--contrast-color); transform: scale(1.1); }
    .progress-step.completed { background: var(--accent-color); color: var(--contrast-color); }
    .progress-step.completed::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
    .form-step { display:none; animation: fadeIn 0.5s ease; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(20px);} to { opacity:1; transform: translateY(0);} }
    .form-step.active { display:block; }
    .form-group { margin-bottom:25px; }
    .input-group { position:relative; }
    .form-control { width:100%; padding:15px 20px; background: rgba(12,11,9,0.3); border:1px solid rgba(205,164,94,0.3); border-radius:5px; color:var(--default-color); font-size:15px; transition:var(--transition); }
    .form-control:focus { outline:none; border-color:var(--accent-color); box-shadow: 0 0 0 3px rgba(205,164,94,0.2); }
    .input-group label { position:absolute; top:15px; left:20px; color:rgba(255,255,255,0.7); pointer-events:none; transition:var(--transition); }
    .input-group input:focus + label, 
    .input-group input:not(:placeholder-shown) + label, 
    .input-group textarea:focus + label, 
    .input-group textarea:not(:placeholder-shown) + label { 
      top:-10px; left:15px; font-size:13px; background:var(--surface-color); color:var(--accent-color); padding:0 5px; 
    }
    .input-icon { position:absolute; right:20px; top:17px; color:white; }
    .time-input-container { display:flex; gap:10px; align-items:center; }
    .time-input-wrapper { flex:1; }
    .ampm-select { width:80px; padding:15px; background: rgba(12,11,9,0.3); border:1px solid rgba(205,164,94,0.3); border-radius:5px; color:var(--default-color); cursor:pointer; }
    textarea.form-control { min-height:150px; resize:vertical; }
    .form-navigation { display:flex; justify-content:space-between; margin-top:30px; }
    .btn { padding:12px 30px; border-radius:50px; font-size:15px; cursor:pointer; transition:var(--transition); border:none; }
    .btn-outline { background:transparent; border:2px solid var(--accent-color); color:var(--default-color); }
    .btn-outline:hover { background:var(--accent-color); color:var(--contrast-color); }
    .btn-solid { background:var(--accent-color); color:var(--contrast-color); }
    .btn-solid:hover { background: color-mix(in srgb, var(--accent-color), transparent 20%); transform: translateY(-3px); }
    .form-message { padding:15px; margin-bottom:25px; border-radius:5px; text-align:center; display:none; }
    .error-message { background: var(--error-color); color:white; }
    .sent-message { background: var(--success-color); color:white; }
    .loading { background: var(--surface-color); display:flex; align-items:center; justify-content:center; }
    .loading:before { content:""; display:inline-block; width:20px; height:20px; border:3px solid var(--accent-color); border-top-color:transparent; border-radius:50%; animation: spin 1s linear infinite; margin-right:10px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .confirmation-details { background: rgba(12,11,9,0.3); border-radius:8px; padding:20px; margin-bottom:30px; border:1px solid rgba(205,164,94,0.3); }
    .confirmation-item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed rgba(205,164,94,0.2); }
    .confirmation-item:last-child { border-bottom:none; }
    .confirmation-label { color:var(--accent-color); font-weight:500; }
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; align-items:center; justify-content:center; z-index:10000; padding:20px; }
    .modal { width:100%; max-width:520px; background: rgba(41,38,31,0.95); border-radius:8px; padding:20px; border:1px solid rgba(205,164,94,0.2); z-index:10001; position:relative; }
    .modal h3 { margin-bottom:12px; color:var(--accent-color); }
    .modal .close-btn { float:right; background:transparent; border:none; color:var(--default-color); font-size:18px; cursor:pointer; }
    .modal .small { font-size:13px; color: rgba(255,255,255,0.8); }
    .modal .form-row { margin-bottom:12px; }
    .modal .result { margin-top:12px; padding:12px; border-radius:6px; background: rgba(255,255,255,0.04); }
    @media (max-width:768px) {
      .form-container { padding:30px; }
      .form-progress { margin-bottom:30px; }
      .progress-step { width:30px; height:30px; font-size:14px; }
      .time-input-container { flex-direction:column; }
      .ampm-select { width:100%; margin-top:10px; }
      .btn { padding:10px 20px; font-size:14px; }
      .top-status-check {
        top: 15px;
        right: 15px;
        width: 36px;
        height: 36px;
      }
    }
  </style>
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

  <!-- Status check button at the top of the page -->
  <div class="top-status-check" id="open-status-check" title="Check booking status">
    <i class="fas fa-ticket-alt"></i>
  </div>

  <!-- Book A Table Section -->
  <section class="book-a-table section">
    <div class="container">
      <div class="section-title">
        <h2>Reservation</h2>
        <p>Book Your Table</p>
      </div>

      <div class="form-container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="form-message sent-message" style="display:block;">'.$_SESSION['success_message'].'</div>';
            unset($_SESSION['success_message']);
        }

        if (!empty($response['message'])) {
            echo '<div class="form-message ' . ($response['success'] ? 'sent-message' : 'error-message') . '" style="display:block;">' . $response['message'] . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="reservation-form">
          <input type="hidden" name="form_token" value="<?php echo $form_token; ?>">

          <div class="form-progress">
            <div class="progress-step active" data-step="1">1</div>
            <div class="progress-step" data-step="2">2</div>
            <div class="progress-step" data-step="3">3</div>
            <div class="progress-step" data-step="4">4</div>
          </div>

          <!-- Step 1 -->
          <div class="form-step active" data-step="1">
            <div class="form-group">
              <div class="input-group">
                <input type="text" name="name" id="name" class="form-control" placeholder=" " required>
                <label for="name">Your Name</label>
                <i class="fas fa-user input-icon"></i>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <input type="email" name="email" id="email" class="form-control" placeholder=" " required>
                <label for="email">Your Email</label>
                <i class="fas fa-envelope input-icon"></i>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <input type="tel" name="phone" id="phone" class="form-control" placeholder=" " required>
                <label for="phone">Phone Number</label>
                <i class="fas fa-phone input-icon"></i>
              </div>
            </div>

            <div class="form-navigation">
              <button type="button" class="btn btn-outline" disabled>Previous</button>
              <button type="button" class="btn btn-solid next-step">Next</button>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="form-step" data-step="2">
            <div class="form-group">
              <div class="input-group">
                <input type="date" name="date" id="date" class="form-control" placeholder=" " required>
                <label for="date">Reservation Date</label>
                <i class="fas fa-calendar input-icon"></i>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <div class="time-input-container">
                  <div class="time-input-wrapper">
                    <input type="time" name="time" id="time" class="form-control" placeholder=" " required>
                    <label for="time">Reservation Time</label>
                    <i class="fas fa-clock input-icon"></i>
                  </div>
                  <select name="time_ampm" class="ampm-select" required>
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <input type="number" name="people" id="people" class="form-control" placeholder=" " min="1" max="20" required>
                <label for="people">Number of Guests</label>
                <i class="fas fa-user-friends input-icon"></i>
              </div>
            </div>

            <div class="form-navigation">
              <button type="button" class="btn btn-outline prev-step">Previous</button>
              <button type="button" class="btn btn-solid next-step">Next</button>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="form-step" data-step="3">
            <div class="form-group">
              <div class="input-group">
                <textarea name="message" id="message" class="form-control" placeholder=" "></textarea>
                <label for="message">Special Requests (Optional)</label>
              </div>
            </div>

            <div class="form-navigation">
              <button type="button" class="btn btn-outline prev-step">Previous</button>
              <button type="button" class="btn btn-solid next-step">Next</button>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="form-step" data-step="4">
            <div class="confirmation-details">
              <div class="confirmation-item">
                <span class="confirmation-label">Name:</span>
                <span id="confirm-name"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Email:</span>
                <span id="confirm-email"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Phone:</span>
                <span id="confirm-phone"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Date:</span>
                <span id="confirm-date"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Time:</span>
                <span id="confirm-time"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Guests:</span>
                <span id="confirm-people"></span>
              </div>
              <div class="confirmation-item">
                <span class="confirmation-label">Special Requests:</span>
                <span id="confirm-message"></span>
              </div>
            </div>

            <div class="form-navigation">
              <button type="button" class="btn btn-outline prev-step">Previous</button>
              <button type="submit" class="btn btn-solid">Confirm Reservation</button>
            </div>
          </div>

          <div class="form-message loading" style="display:none;">Processing your reservation...</div>
          <div class="form-message error-message" style="display:none;"></div>
        </form>
      </div>
    </div>
  </section>

  <!-- Status Check Modal -->
  <div class="modal-overlay" id="status-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="status-title">
      <button class="close-btn" id="close-status-modal"><i class="fas fa-times"></i></button>
      <h3 id="status-title">Check Booking Status</h3>
      <p class="small">Enter your Booking ID to view the status.</p>

      <form method="post" id="status-check-form">
        <input type="hidden" name="action" value="check_status" />
        <div class="form-row">
          <input class="form-control" type="text" name="booking_id" id="check-booking-id" placeholder=" " required />
          <label style="position:relative; top:-40px; left:10px; color:rgba(255,255,255,0.7)">Booking ID</label>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" class="btn btn-outline" id="status-cancel">Cancel</button>
          <button type="submit" class="btn btn-solid">Check Status</button>
        </div>
      </form>

      <div class="result" id="status-result" style="display: none;"></div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('reservation-form');
      const steps = document.querySelectorAll('.form-step');
      const progressSteps = document.querySelectorAll('.progress-step');
      const nextButtons = document.querySelectorAll('.next-step');
      const prevButtons = document.querySelectorAll('.prev-step');
      const loadingMessage = document.querySelector('.loading');
      const errorMessage = document.querySelector('.error-message');
      const statusIcon = document.getElementById('open-status-check');
      const statusModal = document.getElementById('status-modal');
      const statusClose = document.getElementById('close-status-modal');
      const statusCancel = document.getElementById('status-cancel');
      const statusResult = document.getElementById('status-result');

      let currentStep = 1;

      // Floating labels
      document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
          const label = this.nextElementSibling;
          if (label?.tagName === 'LABEL') {
            label.style.top = '-10px';
            label.style.left = '15px';
            label.style.fontSize = '13px';
            label.style.background = 'var(--surface-color)';
            label.style.color = 'var(--accent-color)';
          }
        });

        input.addEventListener('blur', function() {
          if (!this.value) {
            const label = this.nextElementSibling;
            if (label?.tagName === 'LABEL') {
              label.style.top = '15px';
              label.style.left = '20px';
              label.style.fontSize = '15px';
              label.style.background = 'transparent';
              label.style.color = 'rgba(255,255,255,0.7)';
            }
          }
        });
      });

      // Step navigation
      nextButtons.forEach(button => {
        button.addEventListener('click', function() {
          if (validateStep(currentStep)) {
            if (currentStep === 3) updateConfirmation();
            currentStep++;
            updateStepDisplay();
          }
        });
      });
      prevButtons.forEach(button => {
        button.addEventListener('click', function() {
          if (currentStep > 1) {
            currentStep--;
            updateStepDisplay();
          }
        });
      });

      function validateStep(step) {
        let isValid = true;
        if (step === 1) {
          const name = document.getElementById('name').value.trim();
          const email = document.getElementById('email').value.trim();
          const phone = document.getElementById('phone').value.trim();
          if (!name) { showError('Please enter your name'); isValid = false; }
          else if (!email || !validateEmail(email)) { showError('Please enter a valid email address'); isValid = false; }
          else if (!phone) { showError('Please enter your phone number'); isValid = false; }
        } else if (step === 2) {
          const date = document.getElementById('date').value;
          const time = document.getElementById('time').value;
          const people = document.getElementById('people').value;
          if (!date) { showError('Please select a date'); isValid = false; }
          else if (!time) { showError('Please select a time'); isValid = false; }
          else if (!people || people < 1 || people > 20) { showError('Please enter number of guests (1-20)'); isValid = false; }
        }
        if (isValid) hideError();
        return isValid;
      }

      function updateStepDisplay() {
        steps.forEach(step => step.classList.remove('active'));
        document.querySelector(`.form-step[data-step="${currentStep}"]`)?.classList.add('active');
        
        progressSteps.forEach(step => {
          const stepNum = parseInt(step.dataset.step);
          step.classList.remove('active', 'completed');
          if (stepNum < currentStep) step.classList.add('completed');
          else if (stepNum === currentStep) step.classList.add('active');
        });
      }

      function updateConfirmation() {
        document.getElementById('confirm-name').textContent = document.getElementById('name').value;
        document.getElementById('confirm-email').textContent = document.getElementById('email').value;
        document.getElementById('confirm-phone').textContent = document.getElementById('phone').value;

        const dateVal = document.getElementById('date').value;
        document.getElementById('confirm-date').textContent = dateVal 
          ? new Date(dateVal).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
          : '';

        const time = document.getElementById('time').value;
        const ampm = document.querySelector('select[name="time_ampm"]').value;
        document.getElementById('confirm-time').textContent = time ? `${formatTimeDisplay(time)} ${ampm}` : '';

        document.getElementById('confirm-people').textContent = document.getElementById('people').value;
        document.getElementById('confirm-message').textContent = document.getElementById('message').value || 'None';
      }

      function formatTimeDisplay(timeStr) {
        if (!timeStr) return '';
        const [hh, mm] = timeStr.split(':');
        let hours = parseInt(hh, 10);
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${mm || '00'}`;
      }

      function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      
      function hideError() { 
        errorMessage.style.display = 'none'; 
      }
      
      function validateEmail(email) { 
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); 
      }

      // Form submission
      form.addEventListener('submit', function() {
        loadingMessage.style.display = 'flex';
      });

      // Status modal handling
      statusIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        statusModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('check-booking-id').focus();
      });

      [statusClose, statusCancel].forEach(btn => {
        btn.addEventListener('click', function() {
          statusModal.style.display = 'none';
          document.body.style.overflow = '';
        });
      });

      window.addEventListener('click', function(e) {
        if (e.target === statusModal) {
          statusModal.style.display = 'none';
          document.body.style.overflow = '';
        }
      });

      <?php if ($show_status_modal && !empty($status_result_html)): ?>
        (function() {
          const statusModalEl = document.getElementById('status-modal');
          const statusResultEl = document.getElementById('status-result');
          statusResultEl.style.display = 'block';
          statusResultEl.innerHTML = <?php echo json_encode($status_result_html); ?>;
          statusModalEl.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        })();
      <?php endif; ?>
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>