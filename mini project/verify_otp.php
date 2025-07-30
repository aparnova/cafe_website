<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP</title>
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

input[type="text"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: box-shadow 0.3s, border-color 0.3s;
}

input[type="text"]:hover,
input[type="text"]:focus {
    border-color: #999;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    outline: none;
}

.otp-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 25px;
}

.otp-input {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 18px;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.otp-input:focus {
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

.resend-otp {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.resend-otp a {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.resend-otp a:hover {
    text-decoration: underline;
}

.timer {
    text-align: center;
    margin-top: 10px;
    font-size: 14px;
    color: #ff0000;
    font-weight: 600;
}

.error-message {
    color: #ff0000;
    font-size: 14px;
    margin-top: 5px;
    text-align: center;
}
</style>
</head>
<body>

<div class="reset-container">
    <div class="reset-content">
        <div class="logo">
            <img src="img.png" alt="Logo">
        </div>
        <h1>Verify OTP</h1>
        <p style="text-align: center; margin-bottom: 20px;">Enter the 6-digit OTP sent to your email</p>
       
        <form id="otpForm">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <div class="otp-container">
                    <input type="text" id="otp1" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" id="otp2" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" id="otp3" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" id="otp4" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" id="otp5" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" id="otp6" class="otp-input" maxlength="1" pattern="\d" required>
                </div>
                <div id="otpError" class="error-message"></div>
                <div class="timer" id="timer">01:00</div>
            </div>
            
            <button type="submit" class="reset-btn">Verify OTP</button>
        </form>
        
        <div class="resend-otp">
            Didn't receive OTP? <a href="#" id="resendLink">Resend OTP</a>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="login.html">Login here</a>
        </div>
    </div>
</div>

<script>
// Auto-focus and move between OTP inputs
const otpInputs = document.querySelectorAll('.otp-input');
otpInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        }
    });
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

// Timer functionality
let timeLeft = 60;
const timerElement = document.getElementById('timer');
const resendLink = document.getElementById('resendLink');

// Initially disable resend link
resendLink.style.pointerEvents = 'none';
resendLink.style.opacity = '0.5';

const timerInterval = setInterval(() => {
    timeLeft--;
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerElement.textContent = 'OTP expired';
        resendLink.style.pointerEvents = 'auto';
        resendLink.style.opacity = '1';
    }
}, 1000);

// Resend OTP functionality
resendLink.addEventListener('click', async function(e) {
    e.preventDefault();
    
    if (timeLeft > 0) return;
    
    const email = sessionStorage.getItem('resetEmail');
    if (!email) {
        window.location.href = 'forgot_password.html';
        return;
    }
    
    try {
        const response = await fetch('send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email }),
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reset timer
            timeLeft = 60;
            resendLink.style.pointerEvents = 'none';
            resendLink.style.opacity = '0.5';
            timerElement.textContent = '01:00';
            
            const newTimerInterval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(newTimerInterval);
                    timerElement.textContent = 'OTP expired';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                }
            }, 1000);
            
            alert('New OTP has been sent to your email');
        } else {
            alert(result.message || 'Failed to resend OTP');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    }
});

// OTP verification
document.getElementById('otpForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get all OTP inputs
    const otp1 = document.getElementById('otp1').value;
    const otp2 = document.getElementById('otp2').value;
    const otp3 = document.getElementById('otp3').value;
    const otp4 = document.getElementById('otp4').value;
    const otp5 = document.getElementById('otp5').value;
    const otp6 = document.getElementById('otp6').value;
    
    // Combine OTP digits
    const fullOtp = otp1 + otp2 + otp3 + otp4 + otp5 + otp6;
    const email = sessionStorage.getItem('resetEmail');
    const otpError = document.getElementById('otpError');
    
    if (!email) {
        window.location.href = 'forgot_password.html';
        return;
    }
    
    otpError.textContent = '';
    
    try {
        const response = await fetch('verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, otp: fullOtp }),
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store verification token in sessionStorage
            sessionStorage.setItem('resetToken', result.token);
            // Redirect to password reset page
            window.location.href = 'reset_password.html';
        } else {
            otpError.textContent = result.message || 'Invalid OTP';
        }
    } catch (error) {
        otpError.textContent = 'An error occurred. Please try again.';
        console.error('Error:', error);
    }
});
</script>

</body>
</html>