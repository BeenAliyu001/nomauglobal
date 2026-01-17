<?php
session_start();
include 'config.php';
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_email = ? ORDER BY create_at DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - nomauglobalsub</title>
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
            --secondary: #64748b;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --success: #10b981;
            --pending: #f59e0b;
            --failed: #ef4444;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        body {
            background: linear-gradient(120deg, var(--primary), var(--primary-dark));
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
            gap: 35px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
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
        
        .btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
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
        }
        @media(max-width: 700px){
            .dashboard-cards {
            display: flex;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            margin-bottom: 10px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            width: 40%;

            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <!-- <div class="logo-icon">
                    <i class="fas fa-wifi"></i>
                </div> -->
                <i class="fas fa-arrow-left" id="back" style="color:lightgreen;"></i><div class="logo-text">My Transactions History</div>
                <script>
                    document.getElementById("back").addEventListener("click", function(){
                        window.location.href="dashboard.php";
;                    });
                </script>
            </div>
            <div class="user-info">
                <!-- <div class="user-avatar">
                    <?php 
                        // Get first letter of username for avatar
                        if(isset($_SESSION['user_email'])) {
                            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_email']]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo strtoupper(substr($user['username'], 0, 1));
                        }
                    ?>
                </div> -->
                <!-- <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a> -->
            </div>
        </header>
        
        <!-- <h1 class="page-title">My Transactions History</h1> -->
        
        <!-- <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <div class="card-title">Total Transactions</div>
                    <div class="card-value"><?php echo count($transactions); ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="card-title">Successful</div>
                    <div class="card-value">
                        <?php 
                            $successful = array_filter($transactions, function($txn) {
                                return $txn['status'] != 'pending';
                            });
                            echo count($successful);
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <div class="card-title">Pending</div>
                    <div class="card-value">
                        <?php 
                            $pending = array_filter($transactions, function($txn) {
                                return $txn['status'] === 'pending';
                            });
                            echo count($pending);
                        ?>
                    </div>
                </div>
            </div>
        </div>
         -->
        <div class="transactions-table">
            <div class="table-header">
                <h2 class="table-title">All Transactions</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search transactions..." id="searchInput">
                </div>
            </div>
            
            <div class="filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="airtime">Airtime</button>
                <button class="filter-btn" data-filter="data">Data</button>
            </div>
            
            <!-- Mobile View: Card Layout -->
            <div class="transactions-list" id="transactionsList">
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $txn): ?>
                    <div class="transaction-card <?= $txn['status'] ?>">
                        <div class="card-row">
                            <span class="card-label">Reference:</span>
                            <span class="card-value"><?= $txn['trans_id'] ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Type:</span>
                            <span class="card-value"><?= ucfirst($txn['type']) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Network:</span>
                            <span class="card-value"><?= $txn['category'] ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Phone:</span>
                            <span class="card-value"><?= $txn['beneficiary'] ?></span>
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
                            <span class="card-value"><?= date('M j, Y g:i A', strtotime($txn['create_at'])) ?></span>
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
                    <h3>No transactions yet</h3>
                    <p>Your transaction history will appear here once you make a purchase.</p>
                    <a href="airtime.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-shopping-cart"></i> Buy Airtime
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop View: Table Layout -->
            <div class="table-container">
                <?php if (count($transactions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Type</th>
                            <th>Network</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?= $txn['trans_id'] ?></td>
                            <td><?= ucfirst($txn['type']) ?></td>
                            <td><?= $txn['category'] ?></td>
                            <td><?= $txn['beneficiary'] ?></td>
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
                    <h3>No transactions yet</h3>
                    <p>Your transaction history will appear here once you make a purchase.</p>
                    <a href="airtime.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-shopping-cart"></i> Buy Airtime
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($transactions) > 0): ?>
            <div class="pagination">
                <div class="pagination-btn active">1</div>
                <div class="pagination-btn">2</div>
                <div class="pagination-btn">3</div>
                <div class="pagination-btn">></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const tableRows = document.querySelectorAll('tbody tr');
            const transactionCards = document.querySelectorAll('.transaction-card');
            const searchInput = document.getElementById('searchInput');
            
            // Filter button functionality
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