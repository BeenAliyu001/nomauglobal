<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Get admin user data
$admin_id = $_SESSION['user_id'];
$query = $pdo->prepare("SELECT id, username, rank, email, phone FROM admins WHERE id = ?");
$query->execute([$admin_id]);
$admin = $query->fetch(PDO::FETCH_ASSOC);

// Check if admin exists
if (!$admin) {
    // If no admin found, log out the user
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

// Get all transactions for the user management table
$txnH_query = $pdo->query("SELECT id, email, issue, date FROM reports ORDER BY date");
$txnH = $txnH_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All NIN - Transactions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #8BC34A;
            --secondary: #388E3C;
            --success: #4cc9f0;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Fixed Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            height: 70px;
        }

        .header h1 {
            font-size: 22px;
            color: var(--dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            font-size: 22px;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        /* Menu Toggle */
        .menu-toggle {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 24px;
            cursor: pointer;
            margin-right: 15px;
        }

        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            padding-top: 70px; /* Offset for fixed header */
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
            color: white;
            height: calc(100vh - 70px);
            overflow-y: auto;
            transition: all 0.3s;
            position: fixed;
            z-index: 999;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 10px 0;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 25px;
            transition: all 0.3s;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-title {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            display: flex;
            align-items: center;
        }

        .stat-change.up {
            color: #28a745;
        }

        .stat-change.down {
            color: var(--danger);
        }

        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-actions button {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 16px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Tables Section */
        .tables-container {
            display: grid;
            grid-template-columns: 20fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }

        .data-table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 14px;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-success {
            background: #e6f7ee;
            color: #28a745;
        }

        .status-pending {
            background: #fef5e7;
            color: #f39c12;
        }

        .status-failed {
            background: #fdecea;
            color: #e74c3c;
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .tables-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 15px;
            }
            
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            /* Mobile card adjustments */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-card {
                padding: 10px;
                flex-direction: column;
                text-align: center;
            }
            
            .stat-icon {
                width: 50px;
                height: 30px;
                font-size: 20px;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-title {
                font-size: 13px;
            }
            
            .chart-container {
                height: 250px;
            }
            .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .user-info span {
                display: none;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }
              .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
         .tables-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        }

        /* Overlay for mobile sidebar */
        .overlay {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 998;
        }

        .overlay.active {
            display: block;
        }

        /* Specific colors for stat icons */
        .icon-users { background: rgba(67, 97, 238, 0.15); color: var(--primary); }
        .icon-transactions { background: rgba(114, 9, 183, 0.15); color: var(--secondary); }
        .icon-profit { background: rgba(76, 201, 240, 0.15); color: var(--success); }
        .icon-airtime { background: rgba(247, 37, 133, 0.15); color: var(--warning); }
        .icon-data { background: rgba(67, 97, 238, 0.15); color: var(--primary); }
        .icon-success { background: rgba(76, 201, 240, 0.15); color: var(--success); }
        .icon-pending { background: rgba(247, 37, 133, 0.15); color: var(--warning); }
        .icon-failed { background: rgba(230, 57, 70, 0.15); color: var(--danger); }
        .icon-login { background: rgba(114, 9, 183, 0.15); color: var(--secondary); }

        .sub-menu {
            list-style: none;
            /* background-color: rgba(0, 0, 0, 0.1); */
            margin-top: 10px;
            margin-left: 20px;
            border-radius: 5px;
            overflow: hidden;
            display: none;
        }

        .sub-menu.active {
            display: block;
        }

        .sub-menu li {
            padding: 5px 15px;
            font-size: 0.9rem;
        }

        .sub-menu li a {
            color: rgba(255, 255, 255, 0.8);
            padding: 3px 5px;
        }

        .sub-menu li:hover a {
            color: white;
        }

        .has-submenu {
            position: relative;
        }

        .has-submenu::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 20px;
            transition: var(--transition);
        }

        .has-submenu.active::after {
            transform: rotate(180deg);
        }
         /* Chart Section */
        .chart-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .pie-container {
            background: white;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            color: var(--dark);
            font-weight: 600;
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-actions button {
            background: var(--light);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .chart-actions button.active {
            background: var(--primary);
            color: white;
        }

        .chart-actions button:hover:not(.active) {
            background: #e9ecef;
        }
         /* Recent Transactions */
        .recent-transactions {
            background: white;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .recent-transactions h3 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #7f8c8d;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .transaction-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
        }

        .transaction-table tr:last-child td {
            border-bottom: none;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status.success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .status.pending {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .status.failed {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
         /* Responsive Design */
        @media (max-width: 1200px) {           
            .chart-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            
            .transaction-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .chart-actions {
                flex-wrap: wrap;
            }
            
            .content {
                padding: 15px 10px;
            }
        }

        /* Overlay for mobile menu */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Fixed Header -->
    <div class="header">
        <div style="display: flex; align-items: center;">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Admin Transaction Analysis Dashboard</h1>
        </div>
        <div class="user-info">
            <span><?php echo $admin['username']; ?></span>
             <div class="user-avatar"><?php echo substr($admin['username'], 0, 1); ?></div>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Welcome Back </h3>
                <p style="font-size: 20px;"><?php echo $admin['username']; ?> <span style="font-size: 10px">(<?php echo $admin['rank']; ?>)</span></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="adminDashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="viewUsers.php"><i class="fas fa-users"></i>All Users</a></li>
               <li class="has-submenu" id="transactionMenu">
                    <a href="#"><i class="fas fa-exchange-alt"></i>All Transactions</a>
                    <ul class="sub-menu" id="transactionSubMenu">
                        <li><a href="alltxn.php"><i class="fas fa-wifi"></i> All Transaction</a></li>
                        <li><a href="airtimetxn.php"><i class="fas fa-mobile-alt"></i> Airtime TXN</a></li>
                        <li><a href="datatxn.php"><i class="fas fa-wifi"></i> Data TXN</a></li>
                    </ul>
                </li>
                <li><a href="payments.php"><i class="fas fa-user-plus"></i>Payments</a></li>
                <li><a href="addBundles.php"><i class="fas fa-user-plus"></i>Insert Bundles</a></li>
                <li><a href="loginHistory.php"><i class="fas fa-history"></i>Login History</a></li>
                <li><a href="reports.php"><i class="fas fa-comments"></i> Issues</a></li>
                <li><a href="userProfile.php"><i class="fas fa-user"></i> My Account</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
                    <!-- Tables Section -->
                     <div class="recent-transactions">
                    <h3>Users Reports</h3>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Issue ID</th>
                                <th>User Email</th>
                                  <th>Issue</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($txnH as $txn): 
                            ?>
                               <tr>
                                 <td data-label="trans_id"><?php echo htmlspecialchars($txn['id']); ?></td>
                                   <td data-label="amount"><?php echo htmlspecialchars($txn['email']); ?></td>
                                    <td data-label="status"><?php echo htmlspecialchars($txn['issue']); ?></td>
                                    <td data-label="date"><?php echo htmlspecialchars($txn['date']); ?></td>
                               </tr>
                             <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        });

        // Close sidebar when clicking on overlay
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        });

         // Transaction submenu toggle
        document.getElementById('transactionMenu').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('transactionSubMenu').classList.toggle('active');
        });
    </script>
</body>
</html>