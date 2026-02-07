<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

require_once 'config.php';

// Auto-logout after 30 minutes
if (isset($_SESSION['login']) && time() - $_SESSION['login'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}
$_SESSION['login'] = time();

$query ="SELECT * FROM users ";
$stmt = $pdo->query($query);
$row = $stmt->fetch();

// Get all transactions for the user management table
$txnH_query = $pdo->query("SELECT * FROM data_plans");
$txnH = $txnH_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Prices || </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #7209b7;
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

        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 25px;
            transition: all 0.3s;
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

        @media (max-width: 992px) {
            .tables-container {
                grid-template-columns: 1fr;
            }
            
        }

        @media (max-width: 768px) {            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 15px;
            }
        }

        @media (max-width: 576px) {
         .tables-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
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

        @media (max-width: 768px) {
            
            .transaction-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .content {
                padding: 15px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="dashboard-container">

        <!-- Main Content -->
        <div class="main-content">
                    <!-- Tables Section -->
                      <a href="dashboard.php"><i class="fas fa-arrow-left" style="color:black;"></i></a><br>
                <div class="recent-transactions">
                    <h3>Data Prices</h3>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Plan Id</th>
                                <th>Network</th>
                                <th>Plan Type</th>
                                <th>Amount</th>
                                 <th>Size</th>
                                  <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($txnH as $txn): 
                            ?>
                               <tr>
                                 <td data-label="trans_id"><?php echo htmlspecialchars($txn['plan_id']); ?></td>
                                 <td data-label="trans_id"><?php echo htmlspecialchars($txn['network']); ?></td>
                                  <td data-label="type"><?php echo htmlspecialchars($txn['category']); ?></td>
                                   <td data-label="amount"><?php echo htmlspecialchars($txn['amount']); ?></td>
                                   <td data-label="network"><?php echo htmlspecialchars($txn['quantity']); ?></td>
                                    <td data-label="status"><?php echo htmlspecialchars($txn['status']); ?></td>
                               </tr>
                             <?php endforeach; ?>
                        </tbody>
                    </table>
                </div><br>
        </div>
    </div>
</body>
</html>