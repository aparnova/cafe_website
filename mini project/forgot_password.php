<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family:'Poppins', sans-serif;
    transition: all 0.3s ease;
}

body {
    background-color: whitesmoke;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    flex-direction: column;
}

.logo {
    margin-bottom: 10px;
}

.logo img {
    max-width: 90px;
    display: block;
    margin: 0 auto;
}

.reset-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 50px 40px;
    width: 100%;
    max-width: 450px;
    height: 600px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.reset-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

h1 {
    color: #333;
    font-size: 24px;
    margin-bottom: 20px;
    text-align: center;
}


.form-group {
    margin-bottom: 25px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: bold;
}

input[type="email"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: box-shadow 0.3s, border-color 0.3s;
}

input[type="email"]:hover,
input[type="email"]:focus {
      border-color: #999;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    outline: none;
}

.reset-btn {
    width: 100%;
    padding: 12px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    margin-top: 10px;
}

.reset-btn:hover {
    background-color: #333;
    transform: scale(1.02);
}

.login-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #000;
}

.login-link a {
    color: #000;
    text-decoration: none;
    transition: color 0.3s;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
    color: #000;
}
</style>
</head>
<body>

<div class="reset-container">

    <div class="reset-content">
        <div class="logo">
    <img src="img.png" alt="Logo">
</div>
        <h1>Forgot Password</h1>
       
        <form id="resetForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter Your Email">
            </div>
            
            <button type="submit" class="reset-btn">Reset</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.html">Login here</a>
        </div>
    </div>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    
    alert(Password reset link has been sent to ${email});
});
</script>

</body>
</html>