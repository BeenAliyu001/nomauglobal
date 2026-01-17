<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Your DB connection file
// include 'networkDetect.php'; // to automatically detect a network number belong to

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
$error = ""; // message to display when an error occur
$aerror = ""; // message to display when an error occur
$success = ""; // message to display when success
if (isset($_SESSION['login']) && time() - $_SESSION['login'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}
$_SESSION['login'] = time();

$query ="SELECT * FROM users ";
$stmt = $pdo->query($query);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $email = htmlspecialchars($_POST['email']);
    $issue = htmlspecialchars($_POST['issue']);

  if(empty($email) || empty($issue)){
    $aerror = "all fields are required";
  }else{
         $query = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $query->execute([$email]);
        $res = $query->fetch();
         if($res >0){
            $ins = $pdo->prepare("INSERT INTO reports (email, issue) VALUES(:email, :issue)");
            $ins->execute(['email'=>$email,
                            'issue'=>$issue
        ]);
        $success = "submitted successfull";
        }else{
            $error = "Only registerd email can report issue !";
   }
  }   
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Smart Sub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <style>
        :root {
            --primary-green: #4CAF50;
            --light-green: #8BC34A;
            --dark-green: #388E3C;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
            --card-bg: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-primary);
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Header */
        .container {
            width: 100%;
            padding-bottom: 70px;
        }

        .header {
            background-color: var(--primary-green);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 5px;
        }

        .logo-icon {
            position: absolute;
            left: 20px;
        }

        .logo-icon i {
            font-size: 1.3rem;
            cursor: pointer;
            padding: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .logo-icon i:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Form Container */
        .form-container {
            padding: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        /* User Info Section */
        .ui {
            background: linear-gradient(135deg, var(--primary-green));
            color: white;
            padding: 25px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            box-shadow: var(--shadow);
        }

        .ui i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .ui h3 {
            font-size: 1.4rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .ui a {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .ui p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Account Sections */
        .acc {
            background-color: var(--primary-green);
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .acc h4 {
            background-color: var(--primary-green);
            color: white;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .ab {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            border-bottom: 1px solid var(--medium-gray);
            transition: background-color 0.3s;
        }

        .ab:last-child {
            border-bottom: none;
        }

        .ab i {
            color: #fff;
            font-size: 1.3rem;
            width: 30px;
            margin-right: 15px;
        }

        .ab h5 {
            flex: 1;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
        }

        .ab p {
            color: #fff;
            font-size: 0.9rem;
            margin-top: 3px;
        }

        .ab a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        /* Footer */
        .footer {
            padding: 20px;
            text-align: center;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .remember {
            margin-bottom: 10px;
        }

        /* Responsive Design */
        @media (min-width: 501px) {
            body {
                border-left: 1px solid var(--medium-gray);
                border-right: 1px solid var(--medium-gray);
            }
            
            .ui {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .ui i {
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px;
            }
            
            .logo-text {
                font-size: 1.3rem;
            }
            
            .logo-icon i {
                width: 35px;
                height: 35px;
                font-size: 1.1rem;
            }
            
            .form-container {
                padding: 15px;
            }
            
            .ui {
                padding: 20px 15px;
            }
            
            .ui h3 {
                font-size: 1.2rem;
            }
            
            .ui p {
                font-size: 1rem;
            }
            
            .ab {
                padding: 15px;
            }
            
            .ab h5 {
                font-size: 0.95rem;
            }
            
            .ab p {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 360px) {
            .logo-text {
                font-size: 1.2rem;
            }
            
            .ui {
                padding: 15px 10px;
            }
            
            .ui a {
                padding: 5px 12px;
                font-size: 0.8rem;
            }
            
            .ab {
                padding: 12px 10px;
            }
            
            .ab i {
                font-size: 1.2rem;
                margin-right: 12px;
            }
        }

        /* Animation for cards */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ui, .acc {
            animation: fadeIn 0.5s ease-out;
        }

        .acc:nth-child(2) {
            animation-delay: 0.1s;
        }

        .acc:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --light-gray: #121212;
                --card-bg: #1e1e1e;
                --medium-gray: #333333;
                --text-primary: #ffffff;
                --text-secondary: #b0b0b0;
            }
        }

        /* Print styles */
        @media print {
            .ui a, .ab a {
                display: none;
            }
            
            body {
                box-shadow: none;
            }
            
            .ui, .acc {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

          #login-btn {
            background: var(--primary-green);
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
        .input-group {
            margin-bottom: 24px;
            position: relative;
            padding: 4px 20px;
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
            border: 1px solid var(--primary-green);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.2s;
            background: var(--light);
        }

        .input-group textarea {
            width: 100%;
            padding: 14px 46px 14px 16px;
            border: 1px solid var(--primary-green);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.2s;
            background: var(--light);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon"  onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div class="logo-text">Contact Us via</div>
            </div>
            <!-- <p class="subtitle">All user information</p> -->
        </div>

        <div class="form-container">
            <div class="success-message" id="success-message">
            </div>
            
                <div class="ui">
                    <i class="fas fa-link"></i><h3 class="link">for more info <a href="http://beenaliyu.great-site.net">Visit Developer Site</a></h3>
                </div>

                     <div class="acc">
                        <h4>Contact Information</h4>
                        <div class="ab">
                            <i class="fas fa-phone"></i><h5>Phone Number.</h5>
                            <p>07026987245</p>
                        </div>
                        <div class="ab">
                            <i class="fas fa-phone"></i><h5>WhatssApp Number.</h5>
                            <p>07026987245</p>
                        </div>
                        <div class="ab">
                            <i class="fas fa-envelope"></i><h5>Email Address</h5>
                             <p>beenaliyusub@gmail.com</p>
                        </div>
                     </div><br>
                    <form action="#" method="post">
                        
                     <div class="input-group">
                     <label for="username">Report Issue (if any) :</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="your email here...">
                        </div><br>
                    <div class="input-wrapper">
                        <textarea type="text" name="issue" placeholder="type your message..."></textarea>
                        </div>
                    </div>

                     <button type="submit" name="submit" id="login-btn">Submit</button>
                

                    </form>                                        
                <div class="footer">
                    <div class="remember">
    </div><br><br>
    <?php include("nav.php") ?>

    <?php if($error) : ?>
        <script>
             Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: <?= json_encode($error) ?>,
        showConfirmButton: false,
        timer: 3000,
      }).then(() => {
        window.location.href = "../index.php";
      });
        </script>
        <?php endif; ?>

            <?php if($aerror) : ?>
        <script>
             Swal.fire({
        icon: 'info',
        title: 'Oops...',
        text: <?= json_encode($aerror) ?>,
        showConfirmButton: false,
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
        title: 'Submitted Successfully',
        text: <?= json_encode($success) ?>,
        showConfirmButton: false,
        timer: 3000,
      }).then(() => {
        window.location.href = 'dashboard.php';
      });
        </script>
        <?php endif; ?>
    
        <script>
        
        </script>
          </form>
</body>
</html>