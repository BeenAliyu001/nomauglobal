<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// database connection
include 'config.php';

// ✅ Function to validate Nigerian local numbers
function isValidNigerianLocalNumber($phone) {
    $number = preg_replace('/\D/', '', $phone);
    return (strlen($number) === 11 && $number[0] === '0' && preg_match('/^0(7\d|8\d|9\d)\d{8}$/', $number));
}

function detectNetwork($phone) {
    $prefix = substr($phone, 0, 4);

    $mtnPrefixes = ["0803","0702","0806","0703","0706","0813","0816","0810","0814","0903","0906","0913","0916"];
    $gloPrefixes = ["0805","0807","0705","0815","0811","0905","0915"];
    $airtelPrefixes = ["0802","0808","0708","0812","0701","0902","0901","0904","0907","0912"];
    $nineMobilePrefixes = ["0809","0817","0818","0909","0908"];

    if (in_array($prefix, $mtnPrefixes)) return "MTN";
    if (in_array($prefix, $gloPrefixes)) return "GLO";
    if (in_array($prefix, $airtelPrefixes)) return "AIRTEL";
    if (in_array($prefix, $nineMobilePrefixes)) return "9MOBILE";
    return "Unknown";
}

$id = $_SESSION['user_id'];
$user_id = $_SESSION['email'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['warning'] = "User not found.";
    header("Location: ../index");
    exit();
}

$balance = $user['balance'];
$email = $_SESSION['email'];

$stmt1 = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt1->execute(['email' => $email]);
$data = $stmt1->fetchAll();

foreach ($data as $rows) {
    $balance = $rows['balance'];
    $account = $rows['accountNumber'];
    $fname = $rows['name'];
    $bank = $rows['bankName'];
}

// ✅ API credentials
$userId = 'CK101257942';
$apiKey = 'S419L46X3135R9LM16NGX984GMUYH3TZ198H1TVALU3B0L6R39S829JH26OIFJ95';
$apiUrl = 'https://www.nellobytesystems.com/APIAirtimeV1.asp';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $network = $_POST['network'];
    $amount = $_POST['amount'];
    $pin = $_POST['pin'];

    $callbackUrl = 'localhost/smartdata/customers/users/airtime';

    if (!isValidNigerianLocalNumber($phone)) {
        $_SESSION['warning'] = "Invalid phone number! Must start with 0 and be 11 digits (e.g., 08012345678).";
        $_SESSION['num'] = $phone;
        $_SESSION['price']  = $amount;
        header("Location: airtime");
        exit();
    }

    $phoneNumber = preg_replace('/\D/', '', $phone);

    switch (strtolower($network)) {
        case 'mtn': $network = "01"; break;
        case 'glo': $network = "02"; break;
        case '9mobile': $network = "03"; break;
        case 'airtel': $network = "04"; break;
        default:
            $_SESSION['warning'] = "Unknown network selected.";
            $_SESSION['num'] = $phone;
            $_SESSION['price']  = $amount;
            header("Location: airtime");
            exit();
    }
    
    if ($amount <= $balance) {
       if($user['pin'] == ""){
         $_SESSION['error'] = "PIN not set .";
        $_SESSION['num'] = $phone;
        $_SESSION['price']  = $amount;
        header("Location: airtime.php");
        exit();
       }elseif ($pin != $user['pin']) {
         $_SESSION['errorP'] = "Incorrect PIN used !";
        $_SESSION['num'] = $phone;
        $_SESSION['price']  = $amount;
        header("Location: airtime");
        exit();
       }else {
                if ($amount >= 50 && $amount <= 2000) {
            $requestUrl = $apiUrl . '?UserID=' . urlencode($userId) .
                '&APIKey=' . urlencode($apiKey) .
                '&MobileNetwork=' . urlencode($network) .
                '&Amount=' . urlencode($amount) .
                '&MobileNumber=' . urlencode($phone) .
                '&CallBackURL=' . urlencode($callbackUrl);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $_SESSION['warning'] = 'An error occurred. Please try again later.';
                $_SESSION['num'] = $phone;
                $_SESSION['price']  = $amount;
                header("Location: airtime");
                exit();
            } else {
                $data = json_decode($response, true);
                $orderId = $data['orderid'] ?? '';

                if (!empty($orderId)) {
                    $_SESSION['amount'] = $amount;
                    $_SESSION['num'] = $phone;
                    header("Location: airtime_comfirm?orderid=$orderId");
                    exit();
                } else {
                    $_SESSION['warning'] = "An Internal Error Occurred!";
                    $_SESSION['num'] = $phone;
                    $_SESSION['price']  = $amount;
                    header("Location: airtime");
                    exit();
                }
            }

            curl_close($ch);
        } else {
            $_SESSION['warning'] = "Minimum purchase amount is ₦50 and maximum is ₦2000.";
            $_SESSION['num'] = $phone;
            $_SESSION['price']  = $amount;
            header("Location: airtime");
            exit();
        }
       }

    } else {
        $_SESSION['warning'] = "Insufficient Funds!";
        $_SESSION['num'] = $phone;
        $_SESSION['price']  = $amount;
        header("Location: airtime");
        exit();
    }
}
?>