<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'config.php';

// Check if user is logged in (simplified for this example)
if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit();
}
$email = $_SESSION['email'];
$sqli = "SELECT * FROM users WHERE email = :email";
$stmt1 = $pdo->prepare($sqli);
$stmt1->execute(['email' => $email]);
$userData = $stmt1->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    $_SESSION['warning'] = "User not found.";
    header("Location: ../index.php");
    exit();
}


$u_id = $userData['id'];
$balance = $userData['balance'];
$account = $userData['accountName'];
$fname = $userData['username'];
$bank = $userData['bankName'];
// $pin = $userData['pin'];

// Function to detect network based on phone number prefix
function detectNetwork($phone) {
    $prefix = substr($phone, 0, 4);
    
    $mtnPrefixes = ["0803","0702","0806","0703","0706","0813","0816","0810","0814","0903","0906","0913","0916"];
    $gloPrefixes = ["0805","0807","0705","0815","0811","0905","0915"];
    $airtelPrefixes = ["0802","0808","0708","0812","0701","0902","0901","0904","0907","0912"];
    $nineMobilePrefixes = ["0809","0817","0818","0909","0908"];
    
    if (in_array($prefix, $mtnPrefixes)) return 'MTN';
    if (in_array($prefix, $airtelPrefixes)) return 'AIRTEL';
    if (in_array($prefix, $gloPrefixes)) return 'GLO';
    if (in_array($prefix, $nineMobilePrefixes)) return '9MOBILE';
    
    return 'UNKNOWN';
}

// Function to get bundle types from database
function getBundleTypes($network, $pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT category 
        FROM data_plans 
        WHERE network = ? AND status = 'available'
        ORDER BY category
    ");
    $stmt->execute([$network]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


function getBundles($network, $type, $pdo) {
    $stmt = $pdo->prepare("
         SELECT plan_id, quantity, validity, amount
        FROM data_plans
        WHERE network = ?
          AND category = ?
          AND status = 'available'
        ORDER BY amount ASC
    ");
    $stmt->execute([$network, $type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'detect_network':
                    if (!empty($_POST['phone'])) {
                        $network = detectNetwork($_POST['phone']);
                        echo json_encode(['success' => true, 'network' => $network]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Phone number is required']);
                    }
                    exit;
                    
                case 'get_bundle_types':
                    if (!empty($_POST['network'])) {
                        $types = getBundleTypes($_POST['network'], $pdo);
                        echo json_encode(['success' => true, 'types' => $types]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Network is required']);
                    }
                    exit;
                    
                case 'get_bundles':
                    if (!empty($_POST['network']) && !empty($_POST['type'])) {
                        $bundles = getBundles($_POST['network'], $_POST['type'], $pdo);
                        echo json_encode(['success' => true, 'bundles' => $bundles]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Network and type are required']);
                    }
                    exit;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Process form submission for purchasing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    // Validate and process purchase
    $phone = $_POST['phone'];
    $pin = $_POST['pin'];
    $network = $_POST['network'];
    $bundle_id = (int) $_POST['selectedBundle'];
    $api_stmt = $pdo->prepare(
        "SELECT plan_id, amount FROM data_plans WHERE plan_id = ? LIMIT 1"
    );
    $api_stmt->execute([$bundle_id]);   // ← THIS WAS MISSING
    $api = $api_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$api) {
        $_SESSION['warning'] = "Invalid bundle selected.";
        $_SESSION['no'] = $phone;
        header("Location: data_bundles.php");
        exit();
    }
    $price = $api['amount'];
    $_SESSION['price'] = $price;
    $_SESSION['network'] = $network;   
    // $_SESSION['datapack'] = $bundle_id;

      if($userData['pin'] == ""){
         $_SESSION['warn'] = "PIN not set .";
        $_SESSION['no'] = $phone;
        header("Location: data_bundles.php");
        exit();
}

    if($pin != $userData['pin']){
         $_SESSION['warning'] = "Incorrect PIN used.";
        $_SESSION['no'] = $phone;
        header("Location: data_bundles.php");
        exit();
}

if ($price > $balance) {
    $_SESSION['warning'] = "Insufficient Funds";
    $_SESSION['no'] = $phone;
    header("Location: data_bundles.php");
    exit();
}
    $networkMap = [
    'mtn' => 1,
    'airtel'=> 4,
    'glo'=> 2,
    '9mobile'=> 3
];

$selectedNetworkKey = strtolower($network); // use the original string for key check
if (!array_key_exists($selectedNetworkKey, $networkMap)) {
    $_SESSION['warning'] = "Invalid network selection.";
    $_SESSION['no'] = $phone;
    header("Location: data_bundles.php");
    exit();
}

$selectedNetwork = $networkMap[$selectedNetworkKey]; // now safe to map


    // Generate stable transactionsID
    $uniq_id = "beenaliyusub-" . $phone . "-" . $bundle_id . "-" . date("YmdHis");

    // Prevent duplicate in PHP
    $check = $pdo->prepare("SELECT trans_id FROM transactions WHERE trans_id = ?");
    $check->execute([$uniq_id]);
    if ($check->rowCount() > 0) {
        $_SESSION['warning'] = "Duplicate transactions prevented.";
        header("Location: data_bundles.php");
        exit();
    }

     // Prepare API request
   $request = [
            // 'network' => $selectedNetwork,
            'plan'=> $bundle_id,
            'mobile_number' => $phone,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.rgcdata.com.ng/api/v2/purchase/data',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($request),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2UiOiJhcGkiLCJpZCI6NDAwMCwiZHNpIjoiOTg1Mzg1ODc3NzI1OTgyODUxODgiLCJpYXQiOjE3Njk4MjgwMzUsImV4cCI6MjA1Mzc1NjI1NCwiaXNzIjoiUmdjZGF0YSJ9.DmXMEObt9uQPriPZMHYJArerlhq3wgNlSXcJW6V8hGo',
            'Content-Type: application/json'
        ),
        ));

    $response = curl_exec($curl);
    curl_close($curl);

    $res = json_decode($response, true);

       $isSuccess =
    (isset($res['status']) && strtolower($res['status']) === 'success') ||
    (isset($res['success']) && $res['success'] === true);

if ($isSuccess) {
    $newbalance = $balance - $price;

    $pdo->beginTransaction();

    try {
        // Debit wallet
        $stmt = $pdo->prepare("
            UPDATE users 
            SET balance = :balance 
            WHERE email = :email
        ");
        $stmt->execute([
            'balance' => $newbalance,
            'email'   => $email
        ]);

        // Log transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions
            (trans_id, category, amount, beneficiary, date, status,
             user_email, api_response, network, profit,
             create_at, prev_balance, post_balance, type)
            VALUES
            (:trans_id, :category, :amount, :beneficiary, :date, :status,
             :user_email, :api_response, :network, :profit,
             :create_at, :prev_balance, :post_balance, :type)
        ");

        $stmt->execute([
            'trans_id'     => $uniq_id,
            'category'     => $network,
            'amount'       => $price,
            'beneficiary'  => $number,
            'date'         => $date,
            'status'       => 'success',
            'user_email'   => $email,
            'api_response' => json_encode($res),
            'network'      => $network,
            'profit'       => $profit,
            'create_at'    => $create_at,
            'prev_balance' => $balance,
            'post_balance' => $newbalance,
            'type'         => 'data'
        ]);

        $pdo->commit();

        $_SESSION['success'] = "Data purchase successful.";
        header("Location: data_bundles.php");
        exit();

    } catch (Throwable $e) {
        $pdo->rollBack();
        $_SESSION['warning'] = "Transaction error.";
        header("Location: data_bundles.php");
        exit();
    }

} else {
    $_SESSION['warning'] = $res['message'] ?? "Transaction failed.";
    header("Location: data_bundles.php");
    exit();
}  
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Data Bundle - nomauglobal sub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        :root {
            --primary: #4CAF50;
            --primary-dark: #8BC34A;
            --secondary: #64748b;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --error: #ef4444;
            --success: #10b981;
        }
        
        body {
            background: linear-gradient(120deg, #f0f9ff, #e0f2fe);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            /* min-height: 100vh; */
            padding:60px 10px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .header {
            padding: 32px 32px 24px;
            text-align: center;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            backdrop-filter: blur(10px);
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: 700;
        }
        
        .subtitle {
            opacity: 0.9;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .form-container {
            padding: 32px;
        }
        
        .input-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 14px 46px 14px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.2s;
            background: var(--light);
        }
        
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        
        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-size: 18px;
        }
        
        .password-toggle {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        #buy-btn {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 10px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        
        #buy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
        }
        
        .network-logo-container {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        
        .network-logo {
            width: 60px;
            height: 60px;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            object-fit: cover;
        }
        
        input[type="radio"] {
            display: none;
        }
        
        input[type="radio"]:checked + img {
            border: 2px solid var(--primary);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.5);
        }
        
        .bundle-container {
            margin-top: 20px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px;
            background: var(--light);
        }
        
        .bundle-container h3 {
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        
        .bundle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, .5fr));
            gap: 12px;
        }
        
        .bundle-item {
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .bundle-item input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            font-weight: normal;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .bundle-item.selected input {
            border-color: var(--primary);
            background: linear-gradient(to left, var(--primary), var(--primary-dark));
            color: white;
            transform: scale(1.03);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .amount-container {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .amount-container label {
            color: var(--primary);
            font-weight: 600;
        }
        
        #amountToPay {
            font-size: 24px;
            font-weight: 700;
            color: var(--success);
            text-align: center;
            background: transparent;
            border: none;
            width: 100%;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        .alert-success {
            background: #d1fae5;
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        @media (max-width: 700px) {
            .container {
                border-radius: 12px;
                width: 100%;
            }
            
            .header {
                padding: 24px 24px 20px;
            }
            
            .form-container {
                padding: 24px;
            }
            
            .network-logo {
                width: 50px;
                height: 50px;
            }
            
            .bundle-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
    <style>
        .network-logo-container {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}

.network-logo {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    object-fit: cover;
    /* Default state - all in color */
    filter: grayscale(0%) brightness(100%);
    opacity: 1;
}

.network-logo.inactive {
    /* Inactive state - grayed out */
    filter: grayscale(100%) brightness(70%);
    opacity: 0.6;
}

.network-logo.active {
    /* Active state - highlighted */
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

/* Remove the old styles */
input[type="radio"] {
    display: none;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="logo-text">Buy Data</div>
            </div>
            <!-- <p class="subtitle">Get data plans at affordable rates</p> -->
        </div>
        
        <div class="form-container">
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form action="#" method="post" autocomplete="on">
                <input type="hidden" name="purchase" value="1">
                
                <!-- Network Selection -->
                <!-- Network Selection -->
<div class="input-group">
    <div class="network-logo-container">
        <label>
            <input type="radio" name="networkRadio" value="MTN" id="mtnRadio" onclick="manualSelect('MTN')">
            <img src="../icons/mtn.png" alt="MTN" class="network-logo" id="mtnImg">
        </label>
        <label>
            <input type="radio" name="networkRadio" value="AIRTEL" id="airtelRadio" onclick="manualSelect('AIRTEL')">
            <img src="../icons/airtel.png" alt="Airtel" class="network-logo" id="airtelImg">
        </label>
        <label>
            <input type="radio" name="networkRadio" value="GLO" id="gloRadio" onclick="manualSelect('GLO')">
            <img src="../icons/glo.jpg" alt="Glo" class="network-logo" id="gloImg">
        </label>
        <label>
            <input type="radio" name="networkRadio" value="9MOBILE" id="etisalatRadio" onclick="manualSelect('9MOBILE')">
            <img src="../icons/etisalat.png" alt="9mobile" class="network-logo" id="etisalatImg">
        </label>
    </div>
</div>

                <!-- Phone Number Input -->
                <div class="input-group">
                    <!-- <label for="phone">Phone Number:</label> -->
                    <div class="input-wrapper">
                        <input type="number" id="phone" name="phone" placeholder="Enter phone number" oninput="detectNetwork()" required>
                        <div class="input-icon password-toggle" onclick="pickContact()">
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>
                </div>

                <!-- Detected Network Display -->
                <div class="input-group">
                    <!-- <label for="networkDisplay">Detected Network:</label> -->
                    <input type="hidden" id="networkDisplay" readonly>
                    <input type="hidden" id="network" name="network">
                </div>

                <!-- Bundle Type Selection -->
                <div class="input-group">
                    <!-- <label for="bundleType">Select Bundle Type:</label> -->
                    <select id="bundleType" name="category" onchange="loadBundles()" disabled>
                        <option value="">-- Select a network first --</option>
                    </select>
                </div>

                <!-- Available Bundles -->
               
                    <!-- <h6><i class="fas fa-wifi"></i> Available Bundles:</h6> -->
                    <div class="bundle-grid" id="bundleOptions">
                       
                    </div>
                
                <!-- Hidden field to store selected bundle -->
                <input type="hidden" id="selectedBundle" name="selectedBundle"><br>
                
                <!-- Amount to Pay -->
                <!-- <div class="amount-container">
                    <label>Amount to Pay:</label>
                    <input type="hidden" id="amountToPay" readonly value="₦0.00">
                </div> -->
                
                 <div class="input-group">
                    <label for="phone">Txn PIN:</label>
                    <div class="input-wrapper">
                        <input type="password" id="phone" name="pin" placeholder="Enter your Txn PIN"> 
                    </div>
                </div>
                <!-- Submit Button -->
                <button type="submit" id="buy-btn">Buy Data Bundle</button>
            </form>
        </div>
    </div>
    <?php include('nav.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SweetAlert notifications
var messageText2 = "<?= $_SESSION['success']?? ''; ?>";
if(messageText2 != ''){
    Swal.fire({
        icon: "success",
        title: messageText2,
        text: "We are fast and reliable",
    });
    <?php unset($_SESSION['success']); ?>  
}

var messageText2 = "<?= $_SESSION['warning']?? ''; ?>";
if(messageText2 != ''){
    Swal.fire({
        icon: "warning",
        title: messageText2,
        text: "Thank You for using our service",
    });
    <?php unset($_SESSION['warning']); ?>  
}
var messageText2 = "<?= $_SESSION['warn']?? ''; ?>";
if(messageText2 != ''){
    Swal.fire({
        icon: "warning",
        title: messageText2,
        text: "navigate to profile to create a PIN",
    });
    <?php unset($_SESSION['warning']); ?>  
}

// Function to update network visual states (active/inactive)
function updateNetworkVisualState(activeNetwork) {
    const networks = ['mtn', 'airtel', 'glo', 'etisalat'];
    
    networks.forEach(network => {
        const img = document.getElementById(network + 'Img');
        const radio = document.getElementById(network + 'Radio');
        
        if (network === activeNetwork.toLowerCase()) {
            // Active network - stays colored, gets active class
            img.classList.remove('inactive');
            img.classList.add('active');
            radio.checked = true;
        } else {
            // Inactive networks - grayed out
            img.classList.remove('active');
            img.classList.add('inactive');
            radio.checked = false;
        }
    });
}

// Function to manually select a network
function manualSelect(network) {
    updateNetworkVisualState(network.toLowerCase());
    
    document.getElementById('network').value = network;
    document.getElementById('networkDisplay').value = network;
    loadBundleTypes(network);
}

// Update the detectNetwork function to handle active state
function detectNetwork() {
    const phone = document.getElementById('phone').value;
    if (!phone || phone.length < 4) return;
    
    // Send AJAX request to detect network
    const formData = new FormData();
    formData.append('action', 'detect_network');
    formData.append('phone', phone);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNetworkVisualState(data.network.toLowerCase());
            
            document.getElementById('network').value = data.network;
            document.getElementById('networkDisplay').value = data.network;
            loadBundleTypes(data.network);
        }
    })
    .catch(error => {
        console.error('Error detecting network:', error);
    });
}

// Function to reset all networks to default (all in color)
function resetNetworkStates() {
    const allNetworkImgs = document.querySelectorAll('.network-logo');
    allNetworkImgs.forEach(img => {
        img.classList.remove('active', 'inactive');
    });
    
    // Uncheck all radio buttons
    const allRadios = document.querySelectorAll('input[name="networkRadio"]');
    allRadios.forEach(radio => {
        radio.checked = false;
    });
    
    // Clear network value
    document.getElementById('network').value = '';
    document.getElementById('networkDisplay').value = '';
}

// Call resetNetworkStates when phone input is cleared
document.getElementById('phone').addEventListener('input', function() {
    if (this.value.length < 4) {
        resetNetworkStates();
        document.getElementById('bundleType').innerHTML = '<option value="">-- Select a network first --</option>';
        document.getElementById('bundleType').disabled = true;
        document.getElementById('bundleOptions').innerHTML = '';
        document.getElementById('selectedBundle').value = '';
    }
});

// Function to load bundle types for a network
function loadBundleTypes(network) {
    const typeSelect = document.getElementById('bundleType');
    typeSelect.innerHTML = '<option value="">Loading...</option>';
    typeSelect.disabled = true;
    
    // Send AJAX request to get bundle types
    const formData = new FormData();
    formData.append('action', 'get_bundle_types');
    formData.append('network', network);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            typeSelect.innerHTML = '<option value="">--Select Bundle Type--</option>';
            data.types.forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = type;
                typeSelect.appendChild(option);
            });
            typeSelect.disabled = false;
        } else {
            typeSelect.innerHTML = '<option value="">No bundle types available</option>';
        }
    })
    .catch(error => {
        console.error('Error loading bundle types:', error);
        typeSelect.innerHTML = '<option value="">Error loading bundle types</option>';
    });
}

// Function to load bundles for a network and type
function loadBundles() {
    const network = document.getElementById('network').value;
    const type = document.getElementById('bundleType').value;
    const container = document.getElementById('bundleOptions');
    
    if (!network || !type) {
        container.innerHTML = '<p>Select a network and bundle type to see available bundles</p>';
        return;
    }
    
    container.innerHTML = '<p>Loading bundles...</p>';
    
    // Send AJAX request to get bundles
    const formData = new FormData();
    formData.append('action', 'get_bundles');
    formData.append('network', network);
    formData.append('type', type);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.bundles.length > 0) {
            container.innerHTML = '';
            
            data.bundles.forEach(bundle => {
                const wrapper = document.createElement('div');
                wrapper.classList.add('bundle-item');
                
                const input = document.createElement('input');
                input.type = 'text';
                input.readOnly = true;
                input.value = `${bundle.quantity} - ₦${bundle.amount}`;
                input.dataset.id = bundle.plan_id;
                input.dataset.amount = bundle.amount;
                
                // On click → select bundle
                wrapper.addEventListener('click', function() {
                    document.querySelectorAll('.bundle-item').forEach(el => {
                        el.classList.remove('selected');
                    });
                    wrapper.classList.add('selected');
                    document.getElementById('selectedBundle').value = bundle.plan_id;
                    document.getElementById('buy-btn').disabled = false;
                });
                
                wrapper.appendChild(input);
                container.appendChild(wrapper);
            });
        } else {
            container.innerHTML = '<p>No bundles available for this selection</p>';
        }
    })
    .catch(error => {
        console.error('Error loading bundles:', error);
        container.innerHTML = '<p>Error loading bundles</p>';
    });
}

// Function to simulate contact picker (for demonstration)
        async function pickContact() {
    try {
        const contacts = await navigator.contacts.select(['name', 'tel'], { multiple: false });
        if (contacts.length > 0) {
            document.getElementById('phone').value = contacts[0].tel[0];
        }
    } catch (err) {
        alert("Contact picker not supported on this browser.");
    }
}

    </script> 
</body>
</html>