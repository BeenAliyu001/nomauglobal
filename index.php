<?php
session_start();
error_reporting(0);
$error = ""; // message to display when an error occur
$success = ""; // message to display when success
require_once "users/config.php";
if($_SERVER['REQUEST_METHOD'] === "POST"){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(empty($username)){
        $error = "Username input cannot be empty !";
    }elseif (empty($password)) {
        $error = "Password input cannot be empty !";
    }else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if($user && password_verify($password, $user['password'])){
             $_SESSION['user_id'] = $user['id'];
             $_SESSION['email'] = $user['email'];
              $success = "Logged in successfully redirecting to dashboard ...";
        }else{
             $_SESSION['login_attempt']+=1;
             $error = "Incorrect login details !";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy cheap data and airtime | nomauglobalventures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-green: #4CAF50;
            --light-green: #8BC34A;
            --dark-green: #388E3C;
            --accent-green: #C8E6C9;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #E0E0E0;
            --bg-light: #F9F9F9;
            --white: #FFFFFF;
            --error-color: #F44336;
            --success-color: #4CAF50;
        }

        body {
            background-color: #f5f9f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: linear-gradient(to bottom right, #f0f7f0, #e8f5e9);
        }

        .container {
            width: 100%;
            max-width: 480px;
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(76, 175, 80, 0.1);
            overflow: hidden;
            border: 1px solid #e0f2e0;
        }

        /* Header Styles with Light Green Theme */
        .header {
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            padding: 30px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 150px;
            height: 150px;
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo-text {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 5px;
            position: relative;
            z-index: 1;
        }

        /* Form Container */
        .form-container {
            padding: 40px;
        }

        .success-message {
            background-color: #E8F5E9;
            border: 1px solid #C8E6C9;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            color: var(--dark-green);
            display: none;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 48px 16px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: var(--bg-light);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.2);
            background-color: var(--white);
        }

        .input-wrapper input::placeholder {
            color: #aaa;
        }

        /* Password Toggle Eye Icon */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary-green);
        }

        /* Button Styles with Light Green Theme */
        button[type="submit"] {
            width: 100%;
            padding: 18px;
            background: linear-gradient(to right, var(--primary-green), var(--light-green));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover {
            background: linear-gradient(to right, var(--dark-green), var(--primary-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        button[type="submit"]:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        /* Sign up link */
        .signup-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            font-size: 15px;
        }

        .signup-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* BVN message */
        #msg {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
            font-style: italic;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        #msg:hover {
            color: var(--primary-green);
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .container {
                max-width: 100%;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .form-container {
                padding: 30px 25px;
            }
            
            .logo-text {
                font-size: 28px;
            }
            
            button[type="submit"] {
                padding: 16px;
            }
        }

        /* Animation for form */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container form {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="logo-text">Sign Up</div>
            </div>
            <!-- <p class="subtitle">Sign up to get you started</p> -->
        </div>

        <div class="form-container">
            <div class="success-message" id="success-message">
            </div>
            
            <form action="#" method="post" autocomplete="on" id="registerForm">
                <div class="input-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="Enter Username" value="<?php echo $username ?>">
                        <!-- <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div> -->
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="confirm-password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm-password" name="password" placeholder="Enter password">
                        <div class="input-icon password-toggle" id="confirm-password-toggle">
                            <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                </div>
               
                <button type="submit" name="submit" id="login-btn">Sign Up</button>
            </form>
            
            <div class="signup-link">
                <span>Didnt have an account? <a href="users/register.php">Sign up here</a></span>
            </div>
        </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            
            // Toggle confirm password visibility
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        
              
            });
    </script>
      <?php if($error) : ?>
        <script>
             Swal.fire({
        icon: 'error',
        // title: 'Oops...',
        text: <?= json_encode($error) ?>,
        showConfirmButton: 'Ok',
        timer: 3000,
      }).then(() => {
        window.location.href = window.location.href;
      });
        </script>
        <?php endif; ?>

        <?php if($success) : ?>
        <script>
             Swal.fire({
        icon: 'success',
        // title: '',
        text: <?= json_encode($success) ?>,
        showConfirmButton: false,
        timer: 3000,
      }).then(() => {
        window.location.href = window.location.href;
      });
        </script>
        <?php endif; ?>
</body>
</html>