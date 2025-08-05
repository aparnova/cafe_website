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
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // avoid ambiguous chars
    do {
        $id = '';
        for ($i = 0; $i < 8; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        // ensure unique
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

// Process form submission (new reservation) - normal form POST (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'check_status')) {
    // Verify form token
    if (!isset($_POST['form_token']) || !isset($_SESSION['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        // invalid token: set response and fall through to display error (no die so modal/unexpected flow doesn't break)
        $response['message'] = 'Invalid form submission. Please refresh the page and try again.';
    } else {
        // Sanitize and validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $time_ampm = trim($_POST['time_ampm'] ?? '');
        $people = (int)($_POST['people'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        // Combine time and AM/PM into a time string
        $full_time = date("H:i:s", strtotime("$time $time_ampm"));

        // Validate inputs
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
            if (!$stmt) {
                $response['message'] = "Database prepare error: " . $conn->error;
            } else {
                $stmt->bind_param("sssssisss", $name, $email, $phone, $date, $full_time, $people, $message, $booking_id, $status);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $formatted_time = date("g:i A", strtotime("$time $time_ampm"));
                    $formatted_date = date("F j, Y", strtotime($date));
                    $response['message'] = "Your reservation for $formatted_date at $formatted_time has been confirmed and is pending admin approval. Your Booking ID is <strong>$booking_id</strong>. Please save it to check status.";

                    // store in session and redirect to clear POST (PRG pattern)
                    $_SESSION['success_message'] = $response['message'];
                    $_SESSION['last_booking_id'] = $booking_id;

                    $stmt->close();
                    $conn->close();

                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $response['message'] = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $response['message'] = implode("<br>", $errors);
        }
    }
}

// Handle normal (non-AJAX) status check submission from modal (action=check_status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_status') {
    $booking_id = trim($_POST['booking_id'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($booking_id)) {
        $status_result_html = '<strong>Please enter a booking ID.</strong>';
        $show_status_modal = true;
    } else {
        $query = "SELECT name, email, phone, date, time, people, message, status FROM reservations WHERE booking_id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $status_result_html = '<strong>Database error. Please try again later.</strong>';
            $show_status_modal = true;
        } else {
            $stmt->bind_param("s", $booking_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $status_result_html = '<strong>Booking ID not found.</strong>';
                $show_status_modal = true;
            } else {
                $stmt->bind_result($r_name, $r_email, $r_phone, $r_date, $r_time, $r_people, $r_message, $r_status);
                $stmt->fetch();

                if (!empty($email) && strtolower($email) !== strtolower($r_email)) {
                    $status_result_html = '<strong>Booking ID and email do not match.</strong>';
                    $show_status_modal = true;
                } else {
                    if ($r_status === 'confirmed') {
                        $status_msg = 'Your booking is accepted';
                    } else if ($r_status === 'cancelled') {
                        $status_msg = 'Sorry, your booking was not accepted';
                    } else {
                        $status_msg = 'Pending admin approval';
                    }

                    $formatted_date = date("F j, Y", strtotime($r_date));
                    $formatted_time = date("g:i A", strtotime($r_time));

                    // Build HTML for display inside modal
                    $out = '<strong>' . htmlspecialchars($status_msg, ENT_QUOTES) . '</strong><br>';
                    $out .= '<div style="margin-top:8px;font-size:14px;">';
                    $out .= '<div><strong>Name:</strong> ' . htmlspecialchars($r_name, ENT_QUOTES) . '</div>';
                    $out .= '<div><strong>Date:</strong> ' . htmlspecialchars($formatted_date, ENT_QUOTES) . '</div>';
                    $out .= '<div><strong>Time:</strong> ' . htmlspecialchars($formatted_time, ENT_QUOTES) . '</div>';
                    $out .= '<div><strong>Guests:</strong> ' . htmlspecialchars($r_people, ENT_QUOTES) . '</div>';
                    if (!empty($r_message)) $out .= '<div><strong>Requests:</strong> ' . htmlspecialchars($r_message, ENT_QUOTES) . '</div>';
                    $out .= '</div>';

                    $status_result_html = $out;
                    $show_status_modal = true;
                }
            }
            $stmt->close();
        }
    }
}

// Generate new form token (for reservation form)
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
    /* (Your original CSS unchanged) */
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
    .header { --background-color: rgba(12, 11, 9, 0.61); color: var(--default-color); transition: all 0.5s; z-index: 997; position: fixed; width: 100%; top: 0; }
    .header .branding { background-color: var(--background-color); min-height: 60px; padding: 10px 0; transition: 0.3s; border-bottom: 1px solid var(--background-color); }
    .header .container { display:flex; justify-content:space-between; align-items:center; }
    .header .logo { line-height:1; display:flex; align-items:center; }
    .header .logo img { height:50px; margin-right:15px; }
    .header .logo h1 { font-size:24px; margin:0; color:var(--heading-color); font-family:var(--heading-font); }
    .section-title { padding-bottom: 60px; position: relative; text-align:center; }
    .section-title h2 { font-size:14px; font-weight:500; padding:0; line-height:1px; margin:0; letter-spacing:1.5px; text-transform:uppercase; color: color-mix(in srgb, var(--default-color), transparent 30%); position: relative; }
    .section-title h2::after { content: ""; width: 120px; height: 1px; display: inline-block; background: var(--accent-color); margin: 4px 10px; }
    .section-title p { color: var(--accent-color); margin: 15px 0 0; font-size: 36px; font-weight: 600; font-family: var(--heading-font); position: relative; display:inline-block; cursor:pointer; }
    .section-title p::after { content: ''; position: absolute; width:0; height:2px; background: var(--accent-color); bottom:-10px; left:0; transition: width 0.3s ease; }
    .section-title p:hover::after { width:100%; }
    .book-a-table { background: url("../img/about-bg.jpg") center center; background-size: cover; position: relative; padding: 80px 0; padding-top: 140px; }
    .book-a-table::before { content:""; background: color-mix(in srgb, var(--background-color), transparent 12%); position:absolute; bottom:0; top:0; left:0; right:0; }
    .book-a-table .container { position: relative; z-index:2; }
    .form-container { max-width:800px; margin:0 auto; background: rgba(41, 38, 31, 0.85); border-radius:10px; padding:40px; box-shadow:0 10px 30px rgba(0,0,0,0.3); border:1px solid rgba(205,164,94,0.3); backdrop-filter: blur(5px); position:relative; }
    .form-progress { display:flex; justify-content:space-between; margin-bottom:40px; position:relative; }
    .form-progress::before { content:''; position:absolute; top:50%; left:0; right:0; height:2px; background:rgba(205,164,94,0.2); transform: translateY(-50%); z-index:1; }
    .progress-step { width:40px; height:40px; border-radius:50%; background: rgba(205,164,94,0.2); display:flex; align-items:center; justify-content:center; color:var(--default-color); font-weight:bold; position:relative; z-index:2; transition: all 0.3s ease; }
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
    .input-group input:focus + label, .input-group input:not(:placeholder-shown) + label, .input-group textarea:focus + label, .input-group textarea:not(:placeholder-shown) + label { top:-10px; left:15px; font-size:13px; background:var(--surface-color); color:var(--accent-color); padding:0 5px; }
    .input-icon { position:absolute; right:20px; top:17px; color:rgba(255,255,255,0.5); }
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
    @media (max-width:768px) {
      .form-container { padding:30px; }
      .form-progress { margin-bottom:30px; }
      .progress-step { width:30px; height:30px; font-size:14px; }
      .time-input-container { flex-direction:column; }
      .ampm-select { width:100%; margin-top:10px; }
      .btn { padding:10px 20px; font-size:14px; }
    }
    .status-icon { position:absolute; top:18px; right:18px; background: rgba(205,164,94,0.12); color:var(--default-color); border:1px solid rgba(205,164,94,0.2); width:42px; height:42px; display:flex; align-items:center; justify-content:center; border-radius:50%; cursor:pointer; z-index:999; }
    .modal-overlay { position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.6); display:none; align-items:center; justify-content:center; z-index:10000; padding:20px; }
    .modal { width:100%; max-width:520px; background: rgba(41,38,31,0.95); border-radius:8px; padding:20px; border:1px solid rgba(205,164,94,0.2); }
    .modal h3 { margin-bottom:12px; color:var(--accent-color); }
    .modal .close-btn { float:right; background:transparent; border:none; color:var(--default-color); font-size:18px; cursor:pointer; }
    .modal .small { font-size:13px; color: rgba(255,255,255,0.8); }
    .modal .form-row { margin-bottom:12px; }
    .modal .result { margin-top:12px; padding:12px; border-radius:6px; background: rgba(255,255,255,0.04); }
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

  <!-- Book A Table Section -->
  <section class="book-a-table section">
    <div class="container">
      <div class="section-title">
        <h2>Reservation</h2>
        <p>Book Your Table</p>
      </div>

      <div class="form-container">
        <!-- Status Icon (click to open booking check modal) -->
        <div class="status-icon" id="open-status-check" title="Check booking status">
          <i class="fas fa-ticket-alt"></i>
        </div>

        <?php
        // Display success message from session (after redirect)
        if (isset($_SESSION['success_message'])) {
            echo '<div class="form-message sent-message" style="display:block;">'.$_SESSION['success_message'].'</div>';
            unset($_SESSION['success_message']);
        }

        // Display reservation form response errors (if any from server-side validation)
        if (!empty($response['message'])) {
            echo '<div class="form-message ' . ($response['success'] ? 'sent-message' : 'error-message') . '" style="display:block;">' . $response['message'] . '</div>';
        }
        ?>

        <!-- Reservation Form: normal POST (non-AJAX) -->
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
                <i class="fas fa-calendar-day input-icon"></i>
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
                <i class="fas fa-users input-icon"></i>
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

  <!-- Status Check Modal (now uses normal POST) -->
  <div class="modal-overlay" id="status-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="status-title">
      <button class="close-btn" id="close-status-modal"><i class="fas fa-times"></i></button>
      <h3 id="status-title">Check Booking Status</h3>
      <p class="small">Enter your Booking ID to view the status. If you have the email used to book, you may enter it for verification (optional).</p>

      <!-- Status check form: normal POST to same page -->
      <form method="post" id="status-check-form">
        <input type="hidden" name="action" value="check_status" />
        <div class="form-row">
          <input class="form-control" type="text" name="booking_id" id="check-booking-id" placeholder=" " />
          <label style="position:relative; top:-40px; left:10px; color:rgba(255,255,255,0.7)">Booking ID</label>
        </div>
        <div class="form-row">
          <input class="form-control" type="email" name="email" id="check-email" placeholder=" " />
          <label style="position:relative; top:-40px; left:10px; color:rgba(255,255,255,0.7)">Email (optional)</label>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" class="btn btn-outline" id="status-cancel">Cancel</button>
          <button type="submit" class="btn btn-solid">Check Status</button>
        </div>
      </form>

      <div class="result" id="status-result" style="display: none;"></div>
    </div>
  </div>

  <!-- Optional Customer Login Modal (re-uses status-check POST) -->
  <div class="modal-overlay" id="login-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
      <button class="close-btn" id="close-login-modal"><i class="fas fa-times"></i></button>
      <h3 id="login-title">Customer Login</h3>
      <p class="small">Login with your Booking ID and email to view reservation details.</p>

      <form method="post" id="login-form">
        <input type="hidden" name="action" value="check_status" />
        <div class="form-row">
          <input class="form-control" type="text" name="booking_id" id="login-booking-id" placeholder=" " />
          <label style="position:relative; top:-40px; left:10px; color:rgba(255,255,255,0.7)">Booking ID</label>
        </div>
        <div class="form-row">
          <input class="form-control" type="email" name="email" id="login-email" placeholder=" " />
          <label style="position:relative; top:-40px; left:10px; color:rgba(255,255,255,0.7)">Email</label>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" class="btn btn-outline" id="login-cancel">Cancel</button>
          <button type="submit" class="btn btn-solid">Login</button>
        </div>
      </form>

      <div class="result" id="login-result" style="display:none;"></div>
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
      const successMessage = document.querySelector('.sent-message');
      const statusIcon = document.getElementById('open-status-check');
      const statusModal = document.getElementById('status-modal');
      const statusClose = document.getElementById('close-status-modal');
      const statusCancel = document.getElementById('status-cancel');
      const statusResult = document.getElementById('status-result');
      const loginModal = document.getElementById('login-modal');
      const loginClose = document.getElementById('close-login-modal');
      const loginCancel = document.getElementById('login-cancel');

      let currentStep = 1;

      // Floating labels (basic)
      document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
          const label = this.nextElementSibling;
          if (label && label.tagName === 'LABEL') {
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
            if (label && label.tagName === 'LABEL') {
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
        const curr = document.querySelector(`.form-step[data-step="${currentStep}"]`);
        if (curr) curr.classList.add('active');
        progressSteps.forEach(step => {
          const stepNum = parseInt(step.dataset.step);
          step.classList.remove('active', 'completed');
          if (stepNum < currentStep) step.classList.add('completed');
          else if (stepNum === currentStep) step.classList.add('active');
        });
        curr && curr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function updateConfirmation() {
        document.getElementById('confirm-name').textContent = document.getElementById('name').value;
        document.getElementById('confirm-email').textContent = document.getElementById('email').value;
        document.getElementById('confirm-phone').textContent = document.getElementById('phone').value;

        const dateVal = document.getElementById('date').value;
        if (dateVal) {
          const date = new Date(dateVal);
          document.getElementById('confirm-date').textContent = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        } else {
          document.getElementById('confirm-date').textContent = '';
        }

        const time = document.getElementById('time').value;
        const ampm = document.querySelector('select[name="time_ampm"]').value;
        document.getElementById('confirm-time').textContent = `${formatTimeDisplay(time)} ${ampm}`;

        document.getElementById('confirm-people').textContent = document.getElementById('people').value;
        const message = document.getElementById('message').value;
        document.getElementById('confirm-message').textContent = message || 'None';
      }

      function formatTimeDisplay(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let hh = parseInt(parts[0], 10);
        const mm = parts[1] || '00';
        let ampm = 'AM';
        if (hh >= 12) { ampm = 'PM'; if (hh > 12) hh -= 12; } else if (hh === 0) { hh = 12; }
        return `${hh}:${mm}`;
      }

      function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      function hideError() { errorMessage.style.display = 'none'; }
      function validateEmail(email) { const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; return re.test(email); }

      // When reservation form submits, let browser handle the post.
      // Show a small loading indicator while browser is navigating (optional).
      form.addEventListener('submit', function() {
        // (No AJAX) show loading indicator so user sees something
        loadingMessage.style.display = 'flex';
      });

      // Status modal open/close
      statusIcon.addEventListener('click', function() {
        statusModal.style.display = 'flex';
        statusModal.setAttribute('aria-hidden', 'false');
        document.getElementById('check-booking-id').value = '';
        document.getElementById('check-email').value = '';
        statusResult.style.display = 'none';
        document.getElementById('check-booking-id').focus();
      });
      statusClose.addEventListener('click', function() { statusModal.style.display = 'none'; statusModal.setAttribute('aria-hidden', 'true'); });
      statusCancel.addEventListener('click', function() { statusModal.style.display = 'none'; statusModal.setAttribute('aria-hidden', 'true'); });

      // Login modal handlers
      document.getElementById('close-login-modal')?.addEventListener('click', function(){ loginModal.style.display='none'; loginModal.setAttribute('aria-hidden','true'); });
      loginCancel?.addEventListener('click', function(){ loginModal.style.display='none'; loginModal.setAttribute('aria-hidden','true'); });

      // click outside modal to close
      window.addEventListener('click', function(e) {
        if (e.target === statusModal) { statusModal.style.display = 'none'; statusModal.setAttribute('aria-hidden','true'); }
        if (e.target === loginModal) { loginModal.style.display = 'none'; loginModal.setAttribute('aria-hidden','true'); }
      });

      // sanitize simple escaping for client-inserted server HTML
      function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function (m) {
          return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
      }

      // If server returned status result html and indicated modal should show, inject it and open modal
      <?php if ($show_status_modal && !empty($status_result_html)): ?>
        (function() {
          var statusModalEl = document.getElementById('status-modal');
          var statusResultEl = document.getElementById('status-result');
          statusResultEl.style.display = 'block';
          // Insert server-generated HTML. It is already escaped server-side where appropriate.
          statusResultEl.innerHTML = <?php echo json_encode($status_result_html); ?>;
          statusModalEl.style.display = 'flex';
          statusModalEl.setAttribute('aria-hidden','false');
        })();
      <?php endif; ?>
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>
