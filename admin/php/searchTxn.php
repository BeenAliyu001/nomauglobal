<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../../../index.php");
    exit();
}

// Get admin user data
$admin_id = $_SESSION['user_id'];
$query = $pdo->prepare("SELECT id, username, email, phone FROM admins WHERE id = ?");
$query->execute([$admin_id]);
$admin = $query->fetch(PDO::FETCH_ASSOC);

// Check if admin exists
if (!$admin) {
    // If no admin found, log out the user
    session_destroy();
    header("Location: ../../../index.php");
    exit();
}

// Initialize variables
$user_id = "";
$name = "";
$user_transactions = [];
$user_details = null;
$search_performed = false;

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $name = trim($_POST['username'] ?? '');
    $search_performed = true;
    
    // Build query based on search parameters
    $sql = "SELECT t.*, u.username, u.username 
            FROM transactions t 
            INNER JOIN users u ON t.user_email = u.email
            WHERE 1=1";
    $params = [];
    
    if (!empty($user_id)) {
        $sql .= " AND t.user_email = ?";
        $params[] = $user_id;
    }
    
    if (!empty($name)) {
        $sql .= " AND u.username LIKE ?";
        $params[] = "%$name%";
    }
    
    $sql .= " ORDER BY t.type DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $user_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user details if we found transactions
    if (!empty($user_transactions)) {
        $user_details = [
            'id' => $user_transactions[0]['u_id'],
            'username' => $user_transactions[0]['username'],
            'username' => $user_transactions[0]['username']
        ];
    } elseif (!empty($user_id) || !empty($username)) {
        // Try to get user details even if no transactions found
        $user_sql = "SELECT id, username FROM users WHERE 1=1";
        $user_params = [];
        
        if (!empty($user_id)) {
            $user_sql .= " AND id = ?";
            $user_params[] = $user_id;
        }
        
        if (!empty($name)) {
            $user_sql .= " AND name LIKE ?";
            $user_params[] = "%$name%";
        }
        
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute($user_params);
        $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_result) {
            $user_details = $user_result;
        }
    }
}

// Get dashboard statistics
$user_count_query = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$user_count = $user_count_query->fetch(PDO::FETCH_ASSOC)['total_users'];

$tr_count_query = $pdo->query("SELECT COUNT(*) as total_trx FROM transactions");
$tr_count = $tr_count_query->fetch(PDO::FETCH_ASSOC)['total_trx'];

$s_count_query = $pdo->query("SELECT COUNT(*) as total_success FROM transactions WHERE status != 'pending'");
$s_count = $s_count_query->fetch(PDO::FETCH_ASSOC)['total_success'];

$p_count_query = $pdo->query("SELECT COUNT(*) as total_pending FROM transactions WHERE status = 'pending'");
$p_count = $p_count_query->fetch(PDO::FETCH_ASSOC)['total_pending'];

$a_count_query = $pdo->query("SELECT COUNT(*) as total_airtrx FROM transactions WHERE type = 'airtime'");
$a_count = $a_count_query->fetch(PDO::FETCH_ASSOC)['total_airtrx'];

$d_count_query = $pdo->query("SELECT COUNT(*) as total_datatrx FROM transactions WHERE type = 'data'");
$d_count = $d_count_query->fetch(PDO::FETCH_ASSOC)['total_datatrx'];

// Total profit
$stmt = $pdo->query("SELECT SUM(profit) AS total_profit FROM transactions");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_profit = $row['total_profit'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Transaction Analysis - Normauglobalsub Sub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #4CAF50;
            --primary-dark: #8BC34A;
            --secondary: #388E3C;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --success: #10b981;
            --pending: #f59e0b;
            --failed: #ef4444;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        body {
            background: linear-gradient(120deg, #f0f9ff, #e0f2fe);
            color: var(--dark);
            line-height: 1.6;
            padding: 15px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-icon {
            background: var(--primary);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .card-title {
            font-size: 12px;
            color: var(--secondary);
            margin-bottom: 5px;
            text-align: center;
        }
        
        .card-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .search-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--secondary);
        }
        
        .form-input {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .user-details {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            display: <?= $user_details ? 'block' : 'none' ?>;
        }
        
        .user-details-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: var(--secondary);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .transactions-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .table-header {
            background: var(--primary);
            color: white;
            padding: 15px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            width: 100%;
        }
        
        .search-box input {
            background: transparent;
            border: none;
            color: white;
            padding: 8px;
            width: 100%;
            outline: none;
            font-size: 14px;
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .filters {
            display: flex;
            gap: 8px;
            padding: 12px 15px;
            background: var(--light);
            border-bottom: 1px solid var(--border);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .filters::-webkit-scrollbar {
            display: none;
        }
        
        .filter-btn {
            padding: 8px 14px;
            border-radius: 20px;
            background: white;
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Mobile-friendly transaction cards */
        .transactions-list {
            display: none; /* Hidden by default, shown on mobile */
            padding: 15px;
        }
        
        .transaction-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
        }
        
        .transaction-card.success {
            border-left-color: var(--success);
        }
        
        .transaction-card.pending {
            border-left-color: var(--pending);
        }
        
        .transaction-card.failed {
            border-left-color: var(--failed);
        }
        
        .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .card-label {
            font-weight: 500;
            color: var(--secondary);
            font-size: 13px;
        }
        
        .card-value {
            font-weight: 600;
            text-align: right;
            font-size: 13px;
        }
        
        .card-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-success {
            background: #dcfce7;
            color: var(--success);
        }
        
        .status-pending {
            background: #fef3c7;
            color: var(--pending);
        }
        
        .status-failed {
            background: #fee2e2;
            color: var(--failed);
        }
        
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px; /* Minimum table width */
        }
        
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        th {
            background: var(--light);
            font-weight: 600;
            color: var(--secondary);
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: var(--secondary);
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--border);
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            padding: 15px;
            gap: 8px;
        }
        
        .pagination-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            header {
                padding: 12px;
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                padding: 12px;
            }
            
            /* Show cards on mobile, hide table */
            .transactions-list {
                display: block;
            }
            
            .table-container {
                display: none;
            }
            
            .filters {
                padding: 10px 12px;
            }
            
            .filter-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
        
        @media (min-width: 769px) {
            /* Show table on desktop, hide cards */
            .transactions-list {
                display: none;
            }
            
            .table-container {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .card {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                text-align: left;
            }
            
            .card-icon {
                margin-bottom: 0;
                margin-right: 12px;
            }
            
            .card-content {
                text-align: right;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="logo-text">Nomauglobal sub</div>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                </div>
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </header>
        
        <h1 class="page-title">User Transaction Analysis</h1>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="card-title">Total Users</div>
                    <div class="card-value"><?= $user_count ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <div class="card-title">Total Transactions</div>
                    <div class="card-value"><?= $tr_count ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="card-title">Successful</div>
                    <div class="card-value"><?= $s_count ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <div class="card-title">Total Profit</div>
                    <div class="card-value">₦ <?= $total_profit ?></div>
                </div>
            </div>
        </div>
        
        <div class="search-section">
            <h2 class="search-title">Search User Transactions</h2>
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label class="form-label">User ID</label>
                    <input type="text" name="user_id" class="form-input" placeholder="Enter user ID" value="<?= htmlspecialchars($user_id) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">name</label>
                    <input type="text" name="name" class="form-input" placeholder="Enter name" value="<?= htmlspecialchars($name) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($user_details): ?>
        <div class="user-details">
            <h2 class="user-details-title">User Information</h2>
            <div class="user-info-grid">
                <div class="info-item">
                    <span class="info-label">User ID</span>
                    <span class="info-value"><?= $user_details['id'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= $user_details['username'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Transactions</span>
                    <span class="info-value"><?= count($user_transactions) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($search_performed): ?>
        <div class="transactions-table">
            <div class="table-header">
                <h2 class="table-title">User Transactions</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search transactions..." id="searchInput">
                </div>
            </div>
            
            <div class="filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="success">Successful</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="failed">Failed</button>
                <button class="filter-btn" data-filter="airtime">Airtime</button>
                <button class="filter-btn" data-filter="data">Data</button>
            </div>
            
            <!-- Mobile View: Card Layout -->
            <div class="transactions-list" id="transactionsList">
                <?php if (count($user_transactions) > 0): ?>
                    <?php foreach ($user_transactions as $txn): ?>
                    <div class="transaction-card <?= $txn['status'] ?>">
                        <div class="card-row">
                            <span class="card-label">Reference:</span>
                            <span class="card-value"><?= $txn['reference'] ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Type:</span>
                            <span class="card-value"><?= ucfirst($txn['type']) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Network:</span>
                            <span class="card-value"><?= $txn['network'] ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Phone:</span>
                            <span class="card-value"><?= $txn['phone'] ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Amount:</span>
                            <span class="card-value card-amount">₦<?= number_format($txn['amount'], 2) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Status:</span>
                            <span class="card-value">
                                <span class="status status-<?= $txn['status'] ?>">
                                    <?= ucfirst($txn['status']) ?>
                                </span>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Date:</span>
                            <span class="card-value"><?= date('M j, Y g:i A', strtotime($txn['created_at'])) ?></span>
                        </div>
                        <div class="card-row" style="margin-top: 15px; margin-bottom: 0;">
                            <span></span>
                            <a href="receipt.php?id=<?= $txn['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-receipt"></i> View Receipt
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No transactions found</h3>
                    <p>No transactions match your search criteria.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop View: Table Layout -->
            <div class="table-container">
                <?php if (count($user_transactions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Type</th>
                            <th>Network</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_transactions as $txn): ?>
                        <tr>
                            <td><?= $txn['trans_id'] ?></td>
                            <td><?= ucfirst($txn['type']) ?></td>
                            <td><?= $txn['category'] ?></td>
                            <td>₦<?= number_format($txn['amount'], 2) ?></td>
                            <td>
                                <span class="status status-<?= $txn['status'] ?>">
                                    <?= ucfirst($txn['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($txn['create_at'])) ?></td>
                            <td>
                                <a href="receipt.php?id=<?= $txn['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No transactions found</h3>
                    <p>No transactions match your search criteria.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($user_transactions) > 0): ?>
            <div class="pagination">
                <div class="pagination-btn active">1</div>
                <div class="pagination-btn">2</div>
                <div class="pagination-btn">3</div>
                <div class="pagination-btn">></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const tableRows = document.querySelectorAll('tbody tr');
            const transactionCards = document.querySelectorAll('.transaction-card');
            const searchInput = document.getElementById('searchInput');
            
            // Filter button functionality
            if (filterButtons.length > 0) {
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const filter = this.getAttribute('data-filter');
                        
                        // Update active state
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Filter table rows
                        tableRows.forEach(row => {
                            filterRow(row, filter);
                        });
                        
                        // Filter transaction cards
                        transactionCards.forEach(card => {
                            filterCard(card, filter);
                        });
                    });
                });
            }
            
            // Filter function for table rows
            function filterRow(row, filter) {
                if (filter === 'all') {
                    row.style.display = '';
                    return;
                }
                
                const type = row.cells[1].textContent.toLowerCase();
                const status = row.cells[5].querySelector('.status').textContent.toLowerCase();
                
                if (filter === 'airtime' && type === 'airtime') {
                    row.style.display = '';
                } else if (filter === 'data' && type === 'data') {
                    row.style.display = '';
                } else if (filter === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Filter function for transaction cards
            function filterCard(card, filter) {
                if (filter === 'all') {
                    card.style.display = '';
                    return;
                }
                
                const type = card.querySelector('.card-row:nth-child(2) .card-value').textContent.toLowerCase();
                const status = card.querySelector('.card-row:nth-child(6) .status').textContent.toLowerCase();
                
                if (filter === 'airtime' && type === 'airtime') {
                    card.style.display = '';
                } else if (filter === 'data' && type === 'data') {
                    card.style.display = '';
                } else if (filter === status) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    // Search in table rows
                    tableRows.forEach(row => {
                        searchRow(row, searchTerm);
                    });
                    
                    // Search in transaction cards
                    transactionCards.forEach(card => {
                        searchCard(card, searchTerm);
                    });
                });
            }
            
            // Search function for table rows
            function searchRow(row, searchTerm) {
                let found = false;
                
                for (let i = 0; i < row.cells.length - 1; i++) {
                    const cellText = row.cells[i].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
            
            // Search function for transaction cards
            function searchCard(card, searchTerm) {
                let found = false;
                const cardText = card.textContent.toLowerCase();
                
                if (cardText.includes(searchTerm)) {
                    found = true;
                }
                
                card.style.display = found ? '' : 'none';
            }
        });
    </script>
</body>
</html>