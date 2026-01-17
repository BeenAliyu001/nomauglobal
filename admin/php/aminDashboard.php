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

// Auto-logout after 30 minutes
if (isset($_SESSION['login']) && time() - $_SESSION['login'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}
$_SESSION['login'] = time();

// Get admin user data
$admin_id = $_SESSION['user_id'];
$query = $pdo->prepare("SELECT id, username, rank, email, phone FROM admins WHERE id = ?");
$query->execute([$admin_id]);
$admin = $query->fetch(PDO::FETCH_ASSOC);

// Check if admin exists
if (!$admin) {
    // If no admin found, log out the user
    session_destroy();
    header("Location: ../../../index.php");
    exit();
}

// Get total user count
$user_count_query = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$user_count = $user_count_query->fetch(PDO::FETCH_ASSOC)['total_users'];

// Get total transaction count
$tr_count_query = $pdo->query("SELECT COUNT(*) as total_trx FROM transactions");
$tr_count = $tr_count_query->fetch(PDO::FETCH_ASSOC)['total_trx'];

// Get total successful transaction count
$s_count_query = $pdo->query("SELECT COUNT(*) as total_success FROM transactions WHERE status != 'failed' AND status != 'pending' ");
$s_count = $s_count_query->fetch(PDO::FETCH_ASSOC)['total_success'];

// Get total successful transaction count
$f_count_query = $pdo->query("SELECT COUNT(*) as total_failed FROM transactions WHERE status = 'failed'");
$f_count = $f_count_query->fetch(PDO::FETCH_ASSOC)['total_failed'];

// Get total pending transaction count
$p_count_query = $pdo->query("SELECT COUNT(*) as total_pending FROM transactions WHERE status = 'pending' ");
$p_count = $p_count_query->fetch(PDO::FETCH_ASSOC)['total_pending'];

// Get total pending transaction count
// $f_count_query = $pdo->query("SELECT COUNT(*) as total_failed FROM transactions WHERE status = 'failed' ");
// $f_count = $f_count_query->fetch(PDO::FETCH_ASSOC)['total_failed'];

// Get total airtime transaction count
$a_count_query = $pdo->query("SELECT COUNT(*) as total_airtrx FROM transactions WHERE type = 'airtime' ");
$a_count = $a_count_query->fetch(PDO::FETCH_ASSOC)['total_airtrx'];

// Get total data transaction count
$d_count_query = $pdo->query("SELECT COUNT(*) as total_datatrx FROM transactions WHERE type = 'data' ");
$d_count = $d_count_query->fetch(PDO::FETCH_ASSOC)['total_datatrx'];

// Get total login history count
$tl_count_query = $pdo->query("SELECT COUNT(*) as total_lh FROM login_history");
$stl_count = $tl_count_query->fetch(PDO::FETCH_ASSOC)['total_lh'];

// Calculate percentages for changes since last month
// User count percentage change
$last_month_user_query = $pdo->query("SELECT COUNT(*) as count FROM users WHERE joinDate >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND joinDate < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_user_count = $last_month_user_query->fetch(PDO::FETCH_ASSOC)['count'];
$user_percentage_change = $last_month_user_count > 0 ? (($user_count - $last_month_user_count) / $last_month_user_count) * 100 : 0;

// Transaction count percentage change
$last_month_trx_query = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_trx_count = $last_month_trx_query->fetch(PDO::FETCH_ASSOC)['count'];
$trx_percentage_change = $last_month_trx_count > 0 ? (($tr_count - $last_month_trx_count) / $last_month_trx_count) * 100 : 0;

// Profit calculation and percentage change
$stmt = $pdo->query("SELECT SUM(profit) AS total_profit FROM transactions");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_profit = $row['total_profit'] ?? 0;

$last_month_profit_query = $pdo->query("SELECT SUM(profit) AS profit FROM transactions WHERE create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_profit_row = $last_month_profit_query->fetch(PDO::FETCH_ASSOC);
$last_month_profit = $last_month_profit_row['profit'] ?? 0;
$profit_percentage_change = $last_month_profit > 0 ? (($total_profit - $last_month_profit) / $last_month_profit) * 100 : 0;

// Airtime transaction percentage change
$last_month_airtime_query = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE type = 'airtime' AND create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_airtime_count = $last_month_airtime_query->fetch(PDO::FETCH_ASSOC)['count'];
$airtime_percentage_change = $last_month_airtime_count > 0 ? (($a_count - $last_month_airtime_count) / $last_month_airtime_count) * 100 : 0;

// Data transaction percentage change
$last_month_data_query = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE type = 'data' AND create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_data_count = $last_month_data_query->fetch(PDO::FETCH_ASSOC)['count'];
$data_percentage_change = $last_month_data_count > 0 ? (($d_count - $last_month_data_count) / $last_month_data_count) * 100 : 0;

// Successful transaction percentage change
$last_month_success_query = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'success' AND create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_success_count = $last_month_success_query->fetch(PDO::FETCH_ASSOC)['count'];
$success_percentage_change = $last_month_success_count > 0 ? (($s_count - $last_month_success_count) / $last_month_success_count) * 100 : 0;

// Pending transaction percentage change
$last_month_pending_query = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'pending' AND create_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND create_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$last_month_pending_count = $last_month_pending_query->fetch(PDO::FETCH_ASSOC)['count'];
$pending_percentage_change = $last_month_pending_count > 0 ? (($p_count - $last_month_pending_count) / $last_month_pending_count) * 100 : 0;

// // Login count percentage change
// $last_month_login_query = $pdo->query("SELECT COUNT(*) as count FROM login_history WHERE login_time >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND login_time < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
// $last_month_login_count = $last_month_login_query->fetch(PDO::FETCH_ASSOC)['count'];
// $login_percentage_change = $last_month_login_count > 0 ? (($stl_count - $last_month_login_count) / $last_month_login_count) * 100 : 0;

// Get all users for the user management table
$users_query = $pdo->query("SELECT username, email,  balance, joinDate FROM users");
$users = $users_query->fetchAll(PDO::FETCH_ASSOC);

// Get all transactions for the user management table
$u_query = $pdo->query("SELECT user_email, type, amount, beneficiary, status, api_response, profit, create_at FROM transactions");
$us = $u_query->fetchAll(PDO::FETCH_ASSOC);

// // Get all login history for the user management
$lh_query = $pdo->query("SELECT user_id, last_login FROM login_counts");
$clh = $lh_query->fetchAll(PDO::FETCH_ASSOC);

// // Get total user count
$user_count_query = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$user_count = $user_count_query->fetch(PDO::FETCH_ASSOC)['total_users'];

// Get all transactions for the user management table
$txnH_query = $pdo->query("SELECT trans_id, user_email, type, amount, category, status, api_response, create_at FROM transactions ORDER BY trans_id DESC LIMIT 10 ");
$txnH = $txnH_query->fetchAll(PDO::FETCH_ASSOC);

// // Get all transactions for the user management table
$lgH_query = $pdo->query("SELECT user_id, last_login FROM login_counts ORDER BY last_login DESC LIMIT 5 ");
$lgH = $lgH_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VTU Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-green: #4CAF50;
            --light-green: #8BC34A;
            --dark-green: #388E3C;
            --card-bg: #ffffff;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196F3;
            --success: #4CAF50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background-color: var(--primary-green);
            color: white;
            padding: 0 20px;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-left: 15px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--text-primary), var(--light-green));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            flex: 1;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-green);
            color: white;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            z-index: 999;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #fff;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--light-green);
        }

        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-green);
        }

        .sidebar-menu i {
            width: 25px;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Submenu Styles */
        .has-submenu {
            position: relative;
        }

        .sub-menu {
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: rgba(0,0,0,0.2);
        }

        .sub-menu.active {
            max-height: 300px;
        }

        .sub-menu li a {
            padding-left: 55px;
            font-size: 0.9rem;
        }

        .has-submenu > a::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .has-submenu.active > a::after {
            transform: rotate(180deg);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-users { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .icon-transactions { background: linear-gradient(135deg, #3498db, #2980b9); }
        .icon-profit { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .icon-airtime { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .icon-data { background: linear-gradient(135deg, #1abc9c, #16a085); }
        .icon-success { background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); }
        .icon-pending { background: linear-gradient(135deg, #f39c12, #d35400); }
        .icon-login { background: linear-gradient(135deg, #34495e, #2c3e50); }

        .stat-content {
            flex: 1;
        }


        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-title {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-change.up {
            color: var(--success);
        }

        .stat-change.down {
            color: var(--danger);
        }

        /* Charts Section */
        .chart-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container, .pie-container {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-actions button {
            padding: 6px 15px;
            border: 1px solid var(--border-color);
            background: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .chart-actions button:hover {
            background-color: #f5f5f5;
        }

        .chart-actions button.active {
            background-color: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .chart-section {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .chart-actions {
                align-self: flex-end;
            }
            
            .header {
                padding: 0 15px;
            }
            
            .header h1 {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .chart-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .sidebar {
                width: 100%;
            }
        }

        /* Mobile optimization */
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1rem;
                margin-left: 10px;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
            }
            
            .menu-toggle {
                font-size: 1.3rem;
            }
            
            .chart-container, .pie-container {
                padding: 15px;
            }
        }

        /* Print styles */
        @media print {
            .sidebar, .header, .menu-toggle, .overlay, .chart-actions {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stat-card, .chart-container, .pie-container {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: #2d2d2d;
                --text-primary: #ffffff;
                --text-secondary: #b0b0b0;
                --border-color: #404040;
            }
            
            body {
                background-color: #121212;
            }
            
            .chart-actions button:not(.active) {
                background-color: #404040;
                color: white;
                border-color: #404040;
            }
            
            .chart-actions button:not(.active):hover {
                background-color: #505050;
            }
        }
        /* Mobile view (max-width: 768px) */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
    
    .stat-card {
        min-height: 120px !important;
        padding: 15px !important;
        flex-direction: column !important;
        text-align: center !important;
        gap: 10px !important;
    }
    
    .stat-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem !important;
    }
    
    .stat-value {
        font-size: 1.4rem !important;
        margin-bottom: 3px !important;
    }
    
    .stat-title {
        font-size: 0.8rem !important;
        margin-bottom: 3px !important;
    }
    
    .stat-change {
        font-size: 0.7rem !important;
        justify-content: center !important;
    }
}

/* Extra small mobile view (max-width: 480px) */
@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    
    .stat-card {
        padding: 12px !important;
        min-height: 110px !important;
    }
    
    .stat-icon {
        width: 20px !important;
        height: 40px !important;
        font-size: 1.1rem !important;
    }
    
    .stat-value {
        font-size: 1.3rem !important;
    }
    
    .stat-title {
        font-size: 0.75rem !important;
    }
    
    .stat-change {
        font-size: 0.65rem !important;
    }
}

@media (max-width: 576px) {
    .sidebar {
        width: 60% !important;
        max-width: 60% !important;
    }
    
    .sidebar-header {
        text-align: center;
        padding: 25px 20px !important;
    }
    
    .sidebar-menu {
        padding: 15px !important;
    }
}

/* Very small screens (max-width: 360px) */
@media (max-width: 360px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    .stat-card {
        min-height: auto !important;
        flex-direction: row !important;
        text-align: left !important;
        gap: 15px !important;
        padding: 15px !important;
    }
    
    .stat-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.3rem !important;
    }
    
    .stat-value {
        font-size: 1.5rem !important;
    }
    
    .stat-title {
        font-size: 0.85rem !important;
    }
    
    .stat-change {
        font-size: 0.75rem !important;
        justify-content: flex-start !important;
    }
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
            <span></span>
             <div class="user-avatar"><?php echo substr($admin['username'], 0, 1); ?></div>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Welcome <?php echo $admin['username'];?></h3>
                <p style="font-size: 20px;"><span style="font-size: 10px"></span></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="adminDashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="viewUsers.php"><i class="fas fa-users"></i>All Users</a></li>
                <li><a href="searchTxn.php"><i class="fas fa-user"></i>Search User Txn</a></li>
               <li class="has-submenu" id="transactionMenu">
                    <a href="#"><i class="fas fa-exchange-alt"></i>All Transactions</a>
                    <ul class="sub-menu" id="transactionSubMenu">
                        <li><a href="transaction.php"><i class="fas fa-mobile-alt"></i> All</a></li>
                         <li><a href="airtimetxn.php"><i class="fas fa-mobile-alt"></i> Airtime</a></li>
                        <li><a href="datatxn.php"><i class="fas fa-wifi"></i> Data</a></li>
                         <li><a href="airtime2cash.php"><i class="fas fa-exchange"></i> Airtime 2 Cash</a></li>
                        <li><a href="bvn.php"><i class="fas fa-university"></i> BVN Slip</a></li>
                        <li><a href="nin.php"><i class="fas fa-fingerprint"></i> NIN Slip</a></li>
                    </ul>
                </li>
                <li><a href="payments.php"><i class="fas fa-user-plus"></i>Payments</a></li>
                <li><a href="addBundles.php"><i class="fas fa-user-plus"></i>Insert Bundles</a></li>
                <li><a href="loginHistory.php"><i class="fas fa-history"></i>Login History</a></li>
                <li><a href="reports.php"><i class="fas fa-comments"></i>Issues</a></li>
                <li><a href="userProfile.php"><i class="fas fa-user"></i> My Account</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalUsers"><?php echo $user_count; ?></div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 12.5% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-transactions">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalTransactions"><?php echo $tr_count; ?></div>
                        <div class="stat-title">Total Transactions</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 8.3% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-profit">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalProfit">₦ <?php echo $total_profit; ?></div>
                        <div class="stat-title">Total Profit</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 15.2% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-airtime">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="airtimeTransactions"><?php echo $a_count; ?></div>
                        <div class="stat-title">Airtime Transactions</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 5.7% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-data">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="dataTransactions"><?php echo $d_count; ?></div>
                        <div class="stat-title">Data Transactions</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 9.4% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="successfulRequests"><?php echo $s_count; ?></div>
                        <div class="stat-title">Successful Requests</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 7.8% since last month
                        </div>
                    </div>
                </div>
                 <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="failedRequests"><?php echo $f_count; ?></div>
                        <div class="stat-title">Failed Requests</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 7.8% since last month
                        </div>
                    </div>
                </div>
              
                <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="pendingRequests"><?php echo $p_count; ?></div>
                        <div class="stat-title">Pending Requests</div>
                        <div class="stat-change down">
                            <i class="fas fa-arrow-down"></i> 3.2% since last month
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-login">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="loginCount"><?php echo $stl_count; ?></div>
                        <div class="stat-title">Login Count</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 10.6% since last month
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
           <div class="chart-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Transaction Analytics</h3>
                            <div class="chart-actions">
                                <button class="active">Daily</button>
                                <button>Weekly</button>
                                <button>Monthly</button>
                            </div>
                        </div>
                        <canvas id="transactionChart" height="100"></canvas>
                    </div>
                    <div class="pie-container">
                        <div class="chart-header">
                            <h3>Transaction Distribution</h3>
                        </div>
                        <canvas id="transactionPieChart" height="100"></canvas>
                    </div>
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

        // Close submenu when clicking outside
        document.addEventListener('click', function(event) {
            const transactionMenu = document.getElementById('transactionMenu');
            if (!transactionMenu.contains(event.target)) {
                transactionMenu.classList.remove('active');
                document.getElementById('transactionSubMenu').classList.remove('active');
            }
        });
       // Line Chart initialization
        const ctx = document.getElementById('transactionChart').getContext('2d');
        const transactionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [
                    {
                        label: 'Airtime Transactions',
                        data: [120, 150, 180, 200, 220, 240, 260],
                        borderColor: '#9b59b6',
                        backgroundColor: 'rgba(155, 89, 182, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: 'Data Transactions',
                        data: [80, 100, 120, 150, 180, 200, 230],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: 'Cable TV Transactions',
                        data: [60, 80, 100, 120, 140, 160, 180],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            padding: 10
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                }
            }
        });

        // Pie Chart initialization
        const pieCtx = document.getElementById('transactionPieChart').getContext('2d');
        const transactionPieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Airtime', 'Data', 'Cable TV', 'Electricity', 'Others'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: [
                        '#9b59b6',
                        '#3498db',
                        '#e74c3c',
                        '#2ecc71',
                        '#f39c12'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Chart period buttons
        const chartButtons = document.querySelectorAll('.chart-actions button');
        chartButtons.forEach(button => {
            button.addEventListener('click', function() {
                chartButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update chart data based on selected period
                const period = this.textContent.toLowerCase();
                updateChartData(period);
            });
        });

        // Function to update chart data based on period
        function updateChartData(period) {
            // In a real application, you would fetch new data from the server
            // based on the selected time period
            
            // For demo purposes, we'll just simulate data changes
            if (period === 'daily') {
                transactionChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                transactionChart.data.datasets[0].data = [45, 52, 48, 55, 60, 65, 70];
                transactionChart.data.datasets[1].data = [35, 40, 38, 45, 50, 55, 60];
                transactionChart.data.datasets[2].data = [25, 30, 28, 32, 35, 40, 45];
            } else if (period === 'weekly') {
                transactionChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                transactionChart.data.datasets[0].data = [320, 350, 380, 400];
                transactionChart.data.datasets[1].data = [280, 300, 320, 350];
                transactionChart.data.datasets[2].data = [240, 260, 280, 300];
            } else { // monthly
                transactionChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
                transactionChart.data.datasets[0].data = [120, 150, 180, 200, 220, 240, 260];
                transactionChart.data.datasets[1].data = [80, 100, 120, 150, 180, 200, 230];
                transactionChart.data.datasets[2].data = [60, 80, 100, 120, 140, 160, 180];
            }
            
            transactionChart.update();
        }

        // Responsive chart resizing
        window.addEventListener('resize', function() {
            transactionChart.resize();
            transactionPieChart.resize();
        });

        // Initialize with the correct chart size
        setTimeout(() => {
            transactionChart.resize();
            transactionPieChart.resize();
        }, 100);
        
    </script>
</body>
</html>