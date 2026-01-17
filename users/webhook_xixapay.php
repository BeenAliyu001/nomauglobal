<?php
require_once "config.php"; // database connection

// Step 1: Read and validate signature
$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_XIXAPAY'] ?? '';
$secretKey = 'c603c5f6921d00b353bdb8964a1884b103d290a1c6173f64543f3d6f37931bb111ff9f82b089b8b4c940ab5dbda7d73185e47d1de343950e850e118b'; // replace this with your actual key

$calculatedSignature = hash_hmac('sha256', $payload, $secretKey);

file_put_contents('xixapay_payload.log', 
    "=== Webhook Request " . date('Y-m-d H:i:s') . " ===\n" .
    "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n" .
    "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n" .
    "Headers: " . print_r(getallheaders(), true) . "\n" .
    "Raw Input: " . file_get_contents('php://input') . "\n" .
    "POST Data: " . print_r($_POST, true) . "\n" .
    "GET Data: " . print_r($_GET, true) . "\n\n",
    FILE_APPEND
);
if (!hash_equals($calculatedSignature, $signatureHeader)) {
    http_response_code(400);
    echo "Invalid signature!";
    exit;
}

// Step 2: Decode payload
$data = json_decode($payload, true);
$reference = $data['transaction_id'] ?? '';

if (empty($reference)) {
    http_response_code(400);
    echo "Missing transaction reference.";
    exit;
}

// Step 3: Check for duplicate reference before any processing
$check = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE trans_id = :trans_id");
$check->execute([':trans_id' => $reference]);
if ($check->fetchColumn() > 0) {
    http_response_code(200); // OK but don't process again
    echo "Transaction already exists. Skipping.";
    exit;
}

// Step 4: Extract details
$amount = $data['amount_paid'] ?? 0;
$settlementFee = $data['settlement_fee'] ?? 0;
$status = $data['transaction_status'] ?? 'success';
$timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
$customerEmail = $data['customer']['email'] ?? '';
$beneficiary = $data['receiver']['accountNumber'] ?? '';
$profit = 0;
$type = "Credit";
$category = "Wallet Funding";
$api_response = $data['description'] ?? 'Payment webhook received.';

// Step 5: Get user
$userStmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$userStmt->execute([':email' => $customerEmail]);

if ($userStmt->rowCount() === 0) {
    http_response_code(400);
   echo "User not found.";
    exit;
}

$user = $userStmt->fetch(PDO::FETCH_ASSOC);
$prev_balance = floatval($user['balance']);
$new_balance = $prev_balance + floatval($amount);

// Step 6: Update user balance
$update = $pdo->prepare("UPDATE users SET balance = :balance WHERE email = :email");
$update->execute([
    ':balance' => $new_balance,
    ':email' => $customerEmail
]);

// Step 7: Insert transaction
$insert = $pdo->prepare("INSERT INTO transactions (
    trans_id, amount, user_email, status, type, beneficiary, category,
    api_response, date, prev_balance, post_balance, profit
) VALUES (
    :trans_id, :amount, :user_email, :status, :type, :beneficiary, :category,
    :api_response, :date, :prev_balance, :post_balance, :profit
)");

$insert->execute([
    ':trans_id' => $reference,
    ':amount' => $amount,
    ':user_email' => $customerEmail,
    ':status' => $status,
    ':type' => $type,
    ':beneficiary' => $beneficiary,
    ':category' => $category,
    ':api_response' => $api_response,
    ':date' => $timestamp,
    ':prev_balance' => $prev_balance,
    ':post_balance' => $new_balance,
    ':profit' => $profit
]);

http_response_code(200);
echo "Transaction processed successfully.";
?>