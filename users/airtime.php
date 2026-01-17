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
// $id = $_SESSION['user_id'];
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
    $fname = $rows['username'];
    $bank = $rows['bankName'];
}

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
        case 'mtn': $network = "mtn"; break;
        case 'glo': $network = "glo"; break;
        case '9mobile': $network = "9mobile"; break;
        case 'airtel': $network = "airtel"; break;
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
         $_SESSION['error'] = "Incorrect PIN used !";
        $_SESSION['num'] = $phone;
        $_SESSION['price']  = $amount;
        header("Location: airtime");
        exit();
       }else {
        if ($amount >= 50 && $amount <= 2000) {
                    // Generate stable transactionsID
        $uniq_id = "nomauglobalsub-" . uniqid();

        // Prevent duplicate in PHP
        $check = $pdo->prepare("SELECT trans_id FROM transactions WHERE trans_id = ?");
        $check->execute([$uniq_id]);
        if ($check->rowCount() > 0) {
            $_SESSION['warning'] = "Duplicate transactions prevented.";
            header("Location: airtime.php");
            exit();
        }
            $request = [
            'network' => $network,
            'phone' => $phone,
            'amount' => $amount
        ];

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://gearoneplus.com.ng/api/v1/airtime',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($request),
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer cbeb0f55a9682b15eb957e5a3239e63fb0cf23df258a7d0de13a42a8d7b161f6',
    'Content-Type: application/json'
  ),
));

    $response = curl_exec($curl);
    curl_close($curl);

    $res = json_decode($response);

    // echo "<pre>";
    // var_dump($response);
    // var_dump($res);
    // exit;

        $status = strtolower($res->status ?? '');
        $status = $status ?: 'failed';
        if (($status=='success')){
        // Extract details
        $number = $phone;
        $date = date('Y-m-d H:i:s');
        $amount_charge = isset($res->amount) ? floatval($res->amount) : floatval($amount); // default to 0 if missing
        $profit = max(0, $amount - $amount_charge);  // now profit will be correct
        $api_response = $res->message;
        $create_at = date('Y-m-d H:i:s');
        $prev_balance = $balance;
        $newbalance = $balance - $amount;
        $post_balance = $balance - $amount;
        $type = "airtime";
                $pdo->beginTransaction();

try {
    // Update balance
    $stmt = $pdo->prepare("
        UPDATE users 
        SET balance = :balance
        WHERE email = :email
    ");
    $stmt->execute([
        'balance' => $newbalance,
        'email'   => $email
    ]);

    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (trans_id, category, amount, beneficiary, date, status, user_email,
         api_response, network, profit, create_at, prev_balance, post_balance, type)
        VALUES
        (:trans_id, :category, :amount, :beneficiary, :date, :status, :user_email,
         :api_response, :network, :profit, :create_at, :prev_balance, :post_balance, :type)
    ");

    $stmt->execute([
        'trans_id'     => $uniq_id,
        'category'     => $network,
        'amount'       => $amount_charge,
        'beneficiary'  => $number,
        'date'         => $date,
        'status'       => $status,
        'user_email'   => $email,
        'api_response' => $api_response,
        'network'      => $network,
        'profit'       => $profit,
        'create_at'    => $create_at,
        'prev_balance' => $prev_balance,
        'post_balance' => $post_balance,
        'type'         => $type
    ]);

    $pdo->commit(); // ✅ REQUIRED

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['warning'] = "Transaction error: " . $e->getMessage();
    header("Location: airtime.php");
    exit();
}

    } else {
        $_SESSION['warning'] = "Transaction failed.";
        $_SESSION['no'] = $phone;
        header("Location: airtime.php");
        exit();
    }
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy cheap data and airtime | nomauglobalsub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content=" #4CAF50">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-green: #4CAF50;
            --light-green: #8BC34A;
            --dark-green: #388E3C;
            --accent-green: #C8E6C9;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #E0E0E0;
            --bg-light: #F9F9F9;
            --white: #FFFFFF;
            --error-color: #F44336;
            --success-color: #4CAF50;
        }

        body {
            background-color: #f5f9f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: linear-gradient(to bottom right, #f0f7f0, #e8f5e9);
        }

        .container {
            width: 100%;
            max-width: 480px;
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(76, 175, 80, 0.1);
            overflow: hidden;
            border: 1px solid #e0f2e0;
        }

        /* Header Styles with Light Green Theme */
        .header {
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            padding: 30px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 150px;
            height: 150px;
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 5px;
            position: relative;
            z-index: 1;
        }

        /* Form Container */
        .form-container {
            padding: 40px;
        }

        .success-message {
            background-color: #E8F5E9;
            border: 1px solid #C8E6C9;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            color: var(--dark-green);
            display: none;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 48px 16px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: var(--bg-light);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--light-green);
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.2);
            background-color: var(--white);
        }

        .input-wrapper input::placeholder {
            color: #aaa;
        }

        /* Password Toggle Eye Icon */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary-green);
        }

        /* Button Styles with Light Green Theme */
        button[type="submit"] {
            width: 100%;
            padding: 18px;
            background: linear-gradient(to right, var(--primary-green), var(--light-green));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover {
            background: linear-gradient(to right, var(--dark-green), var(--primary-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        button[type="submit"]:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        /* Sign up link */
        .signup-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            font-size: 15px;
        }

        .signup-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* BVN message */
        #msg {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
            font-style: italic;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        #msg:hover {
            color: var(--primary-green);
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .container {
                max-width: 100%;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .form-container {
                padding: 30px 25px;
            }
            
            .logo-text {
                font-size: 28px;
            }
            
            button[type="submit"] {
                padding: 16px;
            }
        }

        /* Animation for form */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container form {
            animation: fadeIn 0.5s ease-out;
        }
         /* WhatsApp Button Styles */
        .whatsapp-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            animation: pulse 2s infinite;
        }

        .whatsapp-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(37, 211, 102, 0.7);
        }

        .whatsapp-button i {
            color: white;
            font-size: 32px;
        }

        .whatsapp-tooltip {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: #2c3e50;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .whatsapp-button:hover .whatsapp-tooltip {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(37, 211, 102, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
            }
        }
        @media(max-width: 576px){
            .whatsapp-button{
                bottom:20px;
                right:20px;
                width:50px;
                height:50px;
            }
             .whatsapp-button i{
                font-size: 26px;
             }
        }
        .network-selector {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 10px;
        }
        
        .network-option {
            flex: 1;
            text-align: center;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .network-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .network-option.active .network-logo {
            border-color: var(--primary-green);
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
            transform: scale(1.1);
        }
        
        .network-option.muted .network-logo {
            opacity: 0.5;
            filter: grayscale(80%);
            border-color: var(--muted-color);
        }
        
        .network-option.active {
            border-color: var(--primary-green);
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .network-name {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            transition: color 0.3s ease;
        }
        
        .network-option.muted .network-name {
            color: #999;
        }
        
        .network-option.active .network-name {
            color: var(--primary-green);
            font-weight: bold;
        }
        
        /* Hide radio buttons but keep them accessible */
        .network-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        /* Dark mode support */
        body.dark-mode .network-name {
            color: var(--light-text);
        }
        
        body.dark-mode .network-option.muted .network-name {
            color: var(--gray-text);
        }
        
        body.dark-mode .network-option.muted .network-logo {
            border-color: var(--gray-text);
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .network-logo {
                width: 40px;
                height: 40px;
            }
            
            .network-selector {
                gap: 5px;
            }
        }
        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="logo-text">Buy Airtime</div>
            </div>
            <!-- <p class="subtitle">Sign up to get you started</p> -->
        </div>

        <div class="form-container">
            <div class="success-message" id="success-message">
                 <!-- Network Selection with Detection -->
            </div>
            <div class="network-selector" id="networkSelector">
                <label class="network-option" id="mtnOption">
                    <input type="radio" name="networkRadio" value="MTN" onclick="manualSelect('MTN')">
                    <img src="../icons/mtn.png" alt="MTN" class="network-logo">
                    <div class="network-name">MTN</div>
                </label>
                <label class="network-option" id="airtelOption">
                    <input type="radio" name="networkRadio" value="AIRTEL" onclick="manualSelect('AIRTEL')">
                    <img src="../icons/airtel.png" alt="Airtel" class="network-logo">
                    <div class="network-name">AIRTEL</div>
                </label>
                <label class="network-option" id="gloOption">
                    <input type="radio" name="networkRadio" value="GLO" onclick="manualSelect('GLO')">
                    <img src="../icons/glo.jpg" alt="Glo" class="network-logo">
                    <div class="network-name">GLO</div>
                </label>
                <label class="network-option" id="nineMobileOption">
                    <input type="radio" name="networkRadio" value="9MOBILE" onclick="manualSelect('9MOBILE')">
                    <img src="../icons/9mobile.png" alt="9mobile" class="network-logo">
                    <div class="network-name">9MOBILE</div>
                </label>
            </div>
            
            <form action="#" method="post" autocomplete="on" id="registerForm">
              <div class="input-group">
                     <!-- <label for="username">Email Address</label> -->
                    <div class="input-wrapper">
                        <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" oninput="detectNetwork()">
                        <!-- <div class="input-icon"><i class="fas fa-phone" onclick="pickContact()"></i></div> -->
                    </div>

                </div>
                 <div class="input-group">
                     <!-- <label for="username">Password</label> -->
                    <div class="input-wrapper">
                        <input type="number" id="amount" name="amount" placeholder="Enter amount >= 50">
                    </div>
                </div>

                 <div class="input-group">
                     <!-- <label for="username">Password</label> -->
                    <div class="input-wrapper">
                        <input type="text" id="network" name="network" placeholder="Network Type" readonly>
                    </div>
                </div>
                 <div class="input-group">
                     <!-- <label for="username">Password</label> -->
                    <div class="input-wrapper">
                        <input type="number" id="pin" name="pin" placeholder="Enter PIN">
                    </div>
                </div>
               
                <button type="submit" name="submit" id="login-btn">Buy Airtime</button>
            </form>
        </div><br><br><br>
    </div>
<?php include("nav.php") ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Get all network options
        const mtnOption = document.getElementById('mtnOption');
        const airtelOption = document.getElementById('airtelOption');
        const gloOption = document.getElementById('gloOption');
        const nineMobileOption = document.getElementById('nineMobileOption');
        const networkOptions = [mtnOption, airtelOption, gloOption, nineMobileOption];

        function resetNetworkOptions() {
            networkOptions.forEach(option => {
                option.classList.remove('active', 'muted');
            });
        }

        function setActiveNetwork(network) {
            resetNetworkOptions();
            
            switch(network) {
                case 'MTN':
                    mtnOption.classList.add('active');
                    airtelOption.classList.add('muted');
                    gloOption.classList.add('muted');
                    nineMobileOption.classList.add('muted');
                    break;
                case 'AIRTEL':
                    mtnOption.classList.add('muted');
                    airtelOption.classList.add('active');
                    gloOption.classList.add('muted');
                    nineMobileOption.classList.add('muted');
                    break;
                case 'GLO':
                    mtnOption.classList.add('muted');
                    airtelOption.classList.add('muted');
                    gloOption.classList.add('active');
                    nineMobileOption.classList.add('muted');
                    break;
                case '9MOBILE':
                    mtnOption.classList.add('muted');
                    airtelOption.classList.add('muted');
                    gloOption.classList.add('muted');
                    nineMobileOption.classList.add('active');
                    break;
                default:
                    // If unknown network, don't mute any
                    resetNetworkOptions();
            }
        }

        function detectNetwork() {
            let phone = document.getElementById('phone').value.trim();
            let networkField = document.getElementById('network');

            phone = phone.replace(/\D/g, '');
            if (phone.length >= 4) {
                let prefix = phone.substring(0, 4);
                const mtnPrefixes = ["0803","0702","0806","0703","0706","0813","0816","0810","0814","0903","0906","0913","0916"];
                const gloPrefixes = ["0805","0807","0705","0815","0811","0905","0915"];
                const airtelPrefixes = ["0802","0808","0708","0812","0701","0902","0901","0904","0907","0912"];
                const nineMobilePrefixes = ["0809","0817","0818","0909","0908"];

                let detectedNetwork = "";
                if (mtnPrefixes.includes(prefix)) detectedNetwork = "MTN";
                else if (gloPrefixes.includes(prefix)) detectedNetwork = "GLO";
                else if (airtelPrefixes.includes(prefix)) detectedNetwork = "AIRTEL";
                else if (nineMobilePrefixes.includes(prefix)) detectedNetwork = "9MOBILE";
                else detectedNetwork = "Unknown";

                networkField.value = detectedNetwork;
                if (detectedNetwork !== "Unknown") {
                    setActiveNetwork(detectedNetwork);
                    document.querySelector(`input[value="${detectedNetwork}"]`).checked = true;
                } else {
                    resetNetworkOptions();
                    document.querySelectorAll('input[name="networkRadio"]').forEach(r => r.checked = false);
                }
            } else {
                networkField.value = "";
                resetNetworkOptions();
                document.querySelectorAll('input[name="networkRadio"]').forEach(r => r.checked = false);
            }
        }

        function manualSelect(network) {
            document.getElementById('network').value = network;
            setActiveNetwork(network);
            document.querySelector(`input[value="${network}"]`).checked = true;
        }

         const successMsg = <?= json_encode($_SESSION['success'] ?? ""); ?>;
      const warningMsg = <?= json_encode($_SESSION['warning'] ?? ""); ?>;
      const errMsg = <?= json_encode($_SESSION['error'] ?? ""); ?>;

      if (successMsg) {
          Swal.fire({
              icon: "success",
              title: successMsg,
            //   text: "We are fast and reliable"
          });
          <?php unset($_SESSION['success']); ?>
      }

       if (errMsg) {
          Swal.fire({
              icon: "info",
              title: errMsg,
              text: "goto profile tab and click on create TXN PIN."
          });
          <?php unset($_SESSION['error']); ?>
      }

      if (warningMsg) {
          Swal.fire({
              icon: "warning",
              title: warningMsg,
            //   text: "Thank you for using our service"
          });
          <?php unset($_SESSION['warning']); ?>
      }
  </script>
</body>
</html>