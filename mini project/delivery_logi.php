<?php
session_start();
require 'db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch delivery boy from delivery_boys table
    $stmt = $conn->prepare("SELECT id, name, password FROM delivery_boys WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['user'] = $row['name'];
            $_SESSION['role'] = 'delivery';
            $_SESSION['delivery_boy_id'] = $row['id']; // ✅ important
            header("Location: delivery_dashboard.php");
            exit();
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "No delivery account found with this email";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delivery Login</title>
</head>
<body>
    <h2>Delivery Boy Login</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>
</body>
</html>
