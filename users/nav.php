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

        body {
            background-color: #f9f9f9;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Fixed Header - CENTERED */
        .header {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 500px;
            background-color: var(--primary-green);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            z-index: 1000;
            box-shadow: var(--shadow);
            border-bottom-left-radius: 4mm;
            border-bottom-right-radius: 4mm;
        }

        .header span{
            font-size: 15px;
        }

        .logo {
            font-weight: bold;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.5rem;
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 140px);
            -webkit-overflow-scrolling: touch;
        }

        /* Containers */
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .container-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--primary-green);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .container-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* Balance Container */
        .balance-container {
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            color: white;
            border-radius: 15px;
            padding: 25px 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .balance-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .balance-subtext {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Account Info - UPDATED: All in one container */
        .account-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-label i {
            color: var(--primary-green);
            font-size: 1rem;
            width: 20px;
        }

        .detail-value {
            font-weight: 600;
            font-size: 0.95rem;
            color: #343a40;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }

        .copy-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-left: 8px;
            transition: background 0.3s;
        }

        .copy-btn:hover {
            background: var(--light-green);
        }

       /* Services */
.services-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 15px;
}

.service-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px 37px;
    background-color: #f5f5f5;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s, background-color 0.2s;
}

.service-item:hover, .service-item:active {
    transform: translateY(-3px);
    background-color: #e8f5e9;
}

.service-icon {
    background-color: var(--light-green);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    color: white;
    font-size: 1.2rem;
}

.service-name {
    font-weight: 600;
    text-align: center;
    font-size: 0.85rem;
    width: 20px;
    padding: -20px;
}

        body.dark-mode .service-item {
            background-color: #2a2a2a;
        }

        .service-item:hover, .service-item:active {
            transform: translateY(-3px);
            background-color: #e8f5e9;
        }

        body.dark-mode .service-item:hover, 
        body.dark-mode .service-item:active {
            background-color: #3a3a3a;
        }

        @media(max-width: 480px){
             .service-icon {
            background-color: var(--light-green);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: white;
            font-size: 1.2rem;
        }

        .service-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4px 22px;
    background-color: #f5f5f5;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s, background-color 0.2s;
}

            .service-name {
                font-weight: 600;
                text-align: center;
                font-size: 0.85rem;
            }
                /* Services */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
        width: 10%;
     }
        }
        

        /* View All Button */
        .view-all-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-green);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
        }

        .view-all-btn:hover, .view-all-btn:active {
            background-color: var(--light-green);
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
            color: #777;
            transition: color 0.3s;
        }

        .nav-item.active, .nav-item:hover {
            color: var(--primary-green);
        }

        .nav-icon {
            font-size: 1.2rem;
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
            <span class="nav-label">Settings</span>
        </a>
    </nav>
</body>
</html>