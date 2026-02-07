      <?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Your DB connection file
// include 'networkDetect.php'; // to automatically detect a network number belong to

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../../index.php");
    exit();
}


// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, phone  FROM admins WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(isset($_POST['submit'])){
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);

    $upd = $pdo->prepare("UPDATE admins SET username = ?, email = ?, phone = ? WHERE id = ? ");
    $upd->execute([$username, $email, $phone, $user_id]);
    $suc = $upd;

    if($suc){
         echo '
      <!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <meta http-equiv="X-UA-Compatible" content="ie=edge">
          <title>Document</title>
          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      </head>
      <body>
          
          <script>
              swal.fire({
                   text : "Account Updated ...",
                  timer : 3000,
                  showConfirmButton : false,
              }).then(() => {
                  window.location.href = "adminDashboard.php";
              });
      
              setTime(() => {
                  window.location.href = "adminDashboard.php";
              }, 3000);
              </script>
      </body>
      </html>
      ';
    }else{
                 echo '
      <!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <meta http-equiv="X-UA-Compatible" content="ie=edge">
          <title>Document</title>
          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      </head>
      <body>
          
          <script>
              swal.fire({
                   text : "Something went wrong !",
                  timer : 3000,
                  showConfirmButton : false,
              }).then(() => {
                  window.location.href = window.location.href;
              });
      
              setTime(() => {
                  window.location.href = window.location.href;
              }, 3000);
              </script>
      </body>
      </html>
      ';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - BeenAliyu Sub</title>
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
            --primary: #4CAF50;
            --primary-dark: #8BC34A;
            --secondary: #388E3C;
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
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
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
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
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
                    <i class="fas fa-user-alt"></i>
                </div>
                <div class="logo-text">My Profile</div>
            </div>
            <p class="subtitle"></p>
        </div>

        <div class="form-container">
            <div class="success-message" id="success-message">
            </div>
            
            <form action="#" method="post" autocomplete="off">
                 <div class="input-group">
                     <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="password" name="username" value="<?php echo $user['username'] ?>" readonly>
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-eye"></i> -->
                        </div>
                    </div>
                </div>
                <div class="input-group">
                     <label for="username">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="password" name="email" value="<?php echo $user['email'] ?>">
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-eye"></i> -->
                        </div>
                    </div>
                </div>
                <div class="input-group">
                     <label for="username">Phone Number</label>
                    <div class="input-wrapper">
                        <input type="text" id="password" name="phone" value="<?php echo $user['phone'] ?>">
                        <div class="input-icon password-toggle" id="password-toggle">
                            <!-- <i class="far fa-eye"></i> -->
                        </div>
                    </div>
                </div>
                <button type="submit" name="submit" id="login-btn">Update Account</button>
                
                <div class="footer">
                    <div class="remember">
    </div>
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
          </form>
</body>
</html>