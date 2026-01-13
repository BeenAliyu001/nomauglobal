<?php
session_start();
date_default_timezone_set('Africa/Lagos');
// Include the database connection
include 'config.php';
 // preparing details for request
$userId = 'CK101257942';
$apiKey = 'S419L46X3135R9LM16NGX984GMUYH3TZ198H1TVALU3B0L6R39S829JH26OIFJ95';
$email = $_SESSION['email'];
$u_id = $_SESSION['user_id'];
$amount = $_SESSION['amount'];
$percentage = $amount * 0.01;
$pay_amount = $amount - $percentage;
$orderid = $_GET['orderid'];
$phone = $_SESSION['num'];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate required variables are set
    if (empty($email) || empty($userId) || empty($apiKey) || empty($orderid) || empty($phone) || !isset($amount) || !isset($pay_amount)) {
        throw new Exception("Missing required parameters.");
    }

    // Fetch user balance securely
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    $balance = $user['balance'];

    // API endpoint
    $apiUrl = 'https://www.nellobytesystems.com/APIQueryV1.asp';
    $requestUrl = "$apiUrl?UserID=" . urlencode($userId) . "&APIKey=" . urlencode($apiKey) . "&OrderID=" . urlencode($orderid);

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $curlError = curl_error($ch);
        curl_close($ch);

        // No changes made yet, so no need to commit or rollback here
        $_SESSION['warning'] = "Network error: " . $curlError;
        $_SESSION['num'] = $phone;
        $_SESSION['price'] = $amount;
        header("Location: airtime");
        exit;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!$data) {
        throw new Exception("Invalid response from API.");
    }

    // Start transaction before DB changes
    $pdo->beginTransaction();

    // Use orderid from API response for checking if transaction exists
    $req_id = $data['orderid'] ?? null;

    if (!$req_id) {
        throw new Exception("Order ID missing from API response.");
    }

    // Check if transaction with the same order ID already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE trans_id = :trans_id");
    $stmt->bindParam(':trans_id', $req_id, PDO::PARAM_STR);
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        $pdo->rollBack();

        $_SESSION['warning'] = "Transaction already processed";
        $_SESSION['num'] = $phone;
        $_SESSION['price'] = $amount;
        header("Location: airtime");
        exit;
    }

    // Prepare data for insert
    $date = date('Y-m-d H:i:s'); // Use 4-digit year for clarity
    $api_response = $data['remark'] ?? '';
    $number = $data['mobilenumber'] ?? '';
    $mobilenetwork = $data['mobilenetwork'] ?? '';
    $charge = $data['amountcharged'] ?? 0;
    $profit = $pay_amount - $charge;
    $new_balance = $balance - $pay_amount;
    $type = "airtime";
    $prev_balance = $balance;
    $create_at = date('Y-m-d H:i:s');
    $post_balance = $new_balance;

    // Insert transaction record
    $stmt = $pdo->prepare("INSERT INTO transactions (trans_id, category, amount, beneficiary, date, status, type, user_email, api_response, prev_balance, post_balance, profit, create_at)
                           VALUES (:trans_id, :category, :amount, :beneficiary, :date, :status, :type, :user_email, :api_response, :prev_balance, :post_balance, :profit, :create_at)");
    $stmt->execute([
        ':trans_id' => $req_id,
        ':category' => $mobilenetwork,
        ':amount' => $amount,
        ':beneficiary' => $number,
        ':date' => $date,
        ':status' => $data['status'] ?? '',
        ':type' => $type,
        ':user_email' => $email,
        ':api_response' => $api_response,
        ':prev_balance' => $prev_balance,
        ':post_balance' => $post_balance,
        ':profit' => $profit,
        ':create_at' => $create_at
    ]);

    // Update user balance
    $stmt = $pdo->prepare("UPDATE users SET balance = :new_balance WHERE email = :email");
    $stmt->execute([':new_balance' => $new_balance, ':email' => $email]);

    $pdo->commit();

    $_SESSION['success'] = "Purchase Successful";
    $_SESSION['num'] = $phone;
    $_SESSION['price'] = $amount;
    header("Location: airtime");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['warning'] = "Something went wrong: " . $e->getMessage();
    $_SESSION['num'] = $phone;
    $_SESSION['price'] = $amount;
    header("Location: airtime");
    exit;
}

?>
