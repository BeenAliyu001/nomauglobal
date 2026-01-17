<?php
// error_reporting();
require_once "config.php";
$error = ""; // message to display when an error occur
$success = ""; // mesage to display when no error
$aerror = "";
// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
if($_SERVER['REQUEST_METHOD'] === 'POST')
{
  $email = sanitize_input($_POST['email']);
  $password = sanitize_input($_POST['password']);
  $Cpassword = sanitize_input($_POST['Cpassword']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
   if (empty($email)) {
    $error = "Email is required !";
   }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format";
  }elseif(empty($password)) {
    $error = "PIN is required !";
   }elseif(empty($Cpassword)) {
    $error = "Confirm PIN is required !";
   }elseif($password !== $Cpassword) {
    $error = "PIN does not match !";
   }else{
     if($user > 0){
        if($user['pin']){
            $error = "PIN already created";
            }else {
                $query = $pdo->prepare("UPDATE users SET pin = ? WHERE email = ? ");
                $stmt = $query->execute([$password, $user['email']]);
                $success = "PIN created successfully !";
            }
   }else {
    $aerror = "Only registered user can do this !";
   }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create PIN - Beenaliyusub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        :root {
            --primary:; #8BC34A
            --primary-dark: #4CAF50;
            --secondary: #64748b;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --error: #ef4444;
            --success: #10b981;
        }
        
        body {
            background: linear-gradient(120deg, #f0f9ff, #e0f2fe);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        
        /* Header Section */
        .header {
            padding: 32px 32px 24px;
            text-align: center;
            background: linear-gradient(to right, #4CAF50);
            color: white;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            backdrop-filter: blur(10px);
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: 700;
        }
        
        .subtitle {
            opacity: 0.9;
            font-size: 16px;
            margin-top: 5px;
        }
        
        /* Form Styles */
        .form-container {
            padding: 32px;
        }
        
        .input-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 46px 14px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.2s;
            background: var(--light);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        
        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-size: 18px;
        }
        
        .password-toggle {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        #login-btn {
            background: linear-gradient(to right, #4CAF50);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 10px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        
        #login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
        }
        
        /* Footer Links */
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .remember {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .remember input {
            accent-color: var(--primary);
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 28px 0;
            color: var(--secondary);
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        
        .divider span {
            padding: 0 12px;
            font-size: 14px;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 24px;
            font-size: 15px;
            color: var(--secondary);
        }
        
        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        /* Form Validation Styles */
        .input-group.error input {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .error-message {
            color: var(--error);
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }
        
        .input-group.error .error-message {
            display: block;
        }
        
        .success-message {
            background: var(--success);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                border-radius: 12px;
            }
            
            .header {
                padding: 24px 24px 20px;
            }
            
            .form-container {
                padding: 24px;
            }
            
            .footer {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="logo-text">Change txn PIN</div>
            </div>
            <!-- <p class="subtitle">Set your transaction PIN for first time</p> -->
        </div>

        <div class="form-container">
            <div class="success-message" id="success-message">
            </div>
            
            <form action="#" method="post" autocomplete="off">
               
                 <div class="input-group">
                     <label for="username">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email address">
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-envelope"></i> -->
                        </div>
                    </div>
                </div>
              
                 <div class="input-group">
                     <label for="username">Enter PIN</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" maxlength="4" name="password" placeholder="Enter PIN">
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-eye"></i> -->
                        </div>
                    </div>
                </div>

                  <div class="input-group">
                     <label for="username">Confirm PIN</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" maxlength="4" name="Cpassword" placeholder="Confirm PIN">
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-eye"></i> -->
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="submit" id="login-btn">Set PIN</button>

            </form>
            
            <div class="divider">
                <!-- <span>Or continue with</span> -->
            </div>
                        
            <div class="signup-link">
                <!-- <span>Already have an account? <a href="../index.php">Sign in here</a></span> -->
            </div>
        </div>
    </div>
     <?php include("nav.php") ?>
         <script>
            const password = document.getElementById("password");
            const passwordToggle = document.getElementById('password-toggle');
                // Password visibility toggle
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        </script>
        <?php if($error) : ?>
        <script>
             Swal.fire({
        icon: 'info',
        title: 'Oops...',
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
        // title: 'Txn PIN Created',
        text: <?= json_encode($success) ?>,
        showConfirmButton: false,
        timer: 3000,
      }).then(() => {
        window.location.href = "dashboard.php";
      });
        </script>
        <?php endif; ?>
        <?php if($aerror) : ?>
        <script>
             Swal.fire({
        icon: 'info',
        // title: 'Txn PIN Created',
        text: <?= json_encode($aerror) ?>,
        showConfirmButton: false,
        timer: 3000,
      }).then(() => {
        window.location.href = "../index.php";
      });
        </script>
        <?php endif; ?>
</body>
</html>