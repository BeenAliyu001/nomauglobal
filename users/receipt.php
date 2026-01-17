<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

require '../vendor/autoload.php';

use Dompdf\Dompdf;
$id = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT t.*, u.username as user_name, u.email as u_id 
    FROM transactions t 
    JOIN users u ON t.u_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$txn = $stmt->fetch(PDO::FETCH_ASSOC);

// Format amount with thousand separators
$formattedAmount = '₦' . number_format($txn['amount'], 2);
// Format date to be more readable
$formattedDate = date('F j, Y \a\t g:i A', strtotime($txn['create_at']));
// Status with proper styling
$statusClass = strtolower($txn['status']);
$statusText = ucfirst($txn['status']);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaction Receipt - ' . $txn['trans_id'] . '</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding:0px 40px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .receipt-container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #8BC34A 0%, #8BC34A 100%);
            color: white;
            padding: 5px;
            text-align: center;
            position: relative;
        }
        
        .receipt-header::after {
            content: "";
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.05);
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .receipt-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
        }
        
        .receipt-body {
            padding:10px 10px;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 18px;
            font-weight: 20;
            margin-bottom: 30px;
            color: #8BC34A;
            position: relative;
        }
        
        .receipt-title::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: #8BC34A;
            border-radius: 3px;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            border-left: 4px solid #8BC34A;
        }
        
        .welcome-text {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-weight: 400;
            color: #8BC34A;
            font-size: 22px;
        }
        
        .receipt-details {
            margin-bottom: 30px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #8BC34A;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 500;
            color: #64748b;
            flex: 1;
        }
        
        .detail-value {
            font-weight: 600;
            text-align: right;
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-success {
            background-color: #dcfce7;
            color: #10b981;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #f59e0b;
        }
        
        .status-failed {
            background-color: #fee2e2;
            color: #ef4444;
        }
        
        .amount-highlight {
            font-size: 28px;
            font-weight: 700;
            color: #8BC34A;
        }
        
        .transaction-summary {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin: 30px 0;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            color: #8BC34A;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .summary-content {
            text-align: center;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .customer-message {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 30px 0;
            text-align: center;
            font-size: 16px;
            border-left: 4px solid #8BC34A;
        }
        
        .message-title {
            font-weight: 600;
            color: #8BC34A;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .receipt-footer {
            text-align: center;
            padding: 30px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #64748b;
        }
        
        .thank-you {
            font-weight: 600;
            color: #8BC34A;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .contact-info {
            margin-top: 10px;
        }
        
        .support-note {
            margin-top: 15px;
            font-style: italic;
        }
        
        /* Decorative elements */
        .corner {
            position: absolute;
            width: 20px;
            height: 20px;
        }
        
        .corner-top-left {
            top: 15px;
            left: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.5);
            border-left: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .corner-top-right {
            top: 15px;
            right: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.5);
            border-right: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .corner-bottom-left {
            bottom: 15px;
            left: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            border-left: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .corner-bottom-right {
            bottom: 15px;
            right: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            border-right: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 120px;
            font-weight: 800;
            color: #2563eb;
            transform: rotate(-45deg);
            top: 40%;
            left: 10%;
            z-index: 0;
            pointer-events: none;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="corner corner-top-left"></div>
            <div class="corner corner-top-right"></div>
            <div class="corner corner-bottom-left"></div>
            <div class="corner corner-bottom-right"></div>
            
            <div class="receipt-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="company-name">Been Aliyu Data Sub</div>
            <div class="company-tagline">Reliable. Fast. Secure.</div>
        </div>
        
        <div class="receipt-body">
            <div class="watermark">PAID</div>
            
            <h2 class="receipt-title">Transaction Receipt</h2>
            
            <div class="welcome-section">
                <p class="welcome-text">Thank you for your purchase <span class="user-name">' . htmlspecialchars($txn['user_name']) .'</span> your transaction was completed successfully .</p>
            </div>
            
            <div class="receipt-details">
                <h3 class="section-title">Transaction Information</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Transaction Date:</span>
                    <span class="detail-value">' . $formattedDate . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Transaction Type:</span>
                    <span class="detail-value">' . ucfirst($txn['type']) . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Network:</span>
                    <span class="detail-value">' . $txn['category'] . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Phone Number:</span>
                    <span class="detail-value">' . $txn['beneficiary'] . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value amount-highlight">' . $formattedAmount . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><span class="status-badge status-' . $statusClass . '">' . $statusText . '</span></span>
                </div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <div>Need assistance? Contact our support team</div>
            <div class="contact-info">nomauglobalsub@gmail.com</div>
             <p>Thank you for choosing our services.</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header("Content-Type: application/pdf");
$dompdf->stream("test.pdf", ["Attachment" => false]);


?>