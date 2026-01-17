<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheap data and airtime provider</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #4CAF50;
            --light-green: #8BC34A;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --light-text: #f5f5f5;
            --gray-text: #b0b0b0;
            --border-color: #333;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Footer Navigation - CENTERED */
        .footer-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 500px;
            background-color: white;
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--primary-green);
            transition: color 0.3s;
        }

        .nav-icon {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .nav-label {
            font-size: 0.8rem;
        }

        /* Responsive adjustments */
        @media (min-width: 501px) {
            body {
                border-left: 1px solid #ddd;
                border-right: 1px solid #ddd;
            }
            
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Footer Navigation - CENTERED -->
  <nav class="footer-nav">
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-home nav-icon"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user nav-icon"></i>
            <span class="nav-label">Profile</span>
        </a>
        <a href="contact.php" class="nav-item">
            <i class="fas fa-headset nav-icon"></i>
            <span class="nav-label">Contact</span>
        </a>
        <a href="setting.php" class="nav-item">
            <i class="fas fa-cog nav-icon"></i>
            <span class="nav-label">Setting</span>
        </a>
    </nav>
</body>
</html>