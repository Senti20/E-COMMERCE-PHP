<?php
require_once __DIR__ . "/traccar_sms.php";

$myNumber = "09630221412";
$testOtp = rand(100000, 999999);
$msg = "SKYNET PC Test OTP: $testOtp - Testing local mode";

// Get the IP from the URL for display
$ip = parse_url(TRACCAR_URL, PHP_URL_HOST);
$port = parse_url(TRACCAR_URL, PHP_URL_PORT);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Traccar Local Mode Diagnostic Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; border-left: 4px solid green; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; border-left: 4px solid red; }
        .info { background: #e8f0fe; padding: 10px; border-radius: 5px; border-left: 4px solid blue; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .step { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>📱 Traccar Local Mode Diagnostic Test</h1>";

// Show configuration
echo "<div class='info'>";
echo "<h2>📋 Current Configuration:</h2>";
echo "<pre>";
echo "TRACCAR_URL: " . TRACCAR_URL . "\n";
echo "TRACCAR_TOKEN: " . substr(TRACCAR_TOKEN, 0, 20) . "... (truncated)\n";
echo "Phone IP: $ip\n";
echo "Port: $port\n";
echo "Your Number (raw): $myNumber\n";
$formatted = format_phone_for_sms($myNumber);
echo "Formatted Number: " . ($formatted ?: 'INVALID') . "\n";
echo "</pre>";
echo "</div>";

// Test 1: Can we reach the phone's IP?
echo "<div class='info'>";
echo "<h2>📡 Test 1: Phone Connectivity</h2>";

$connection = @fsockopen($ip, $port, $errno, $errstr, 5);
if ($connection) {
    fclose($connection);
    echo "<p class='success'>✅ Can reach phone at $ip:$port</p>";
    
    // Try to get a response from the API
    $ch = curl_init(TRACCAR_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: " . TRACCAR_TOKEN
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http == 200) {
        echo "<p class='success'>✅ Traccar API responded (HTTP $http)</p>";
    } else {
        echo "<p class='warning'>⚠️ Traccar API responded with HTTP $http</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot reach phone at $ip:$port</p>";
    echo "<p>Error: $errstr ($errno)</p>";
}
echo "</div>";

// Test 2: Send Test SMS
echo "<div class='info'>";
echo "<h2>📱 Test 2: Send Test SMS</h2>";
echo "<p>Sending to: $myNumber</p>";
echo "<p>Message: $msg</p>";

list($ok, $response) = send_otp_sms($myNumber, $testOtp);

if (!$ok) {
    echo "<div class='error'>";
    echo "<h3>❌ FAILED TO SEND SMS</h3>";
    echo "<p>Error: " . htmlspecialchars($response) . "</p>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>✅ SMS SENT SUCCESSFULLY!</h3>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    echo "</div>";
}
echo "</div>";

// Phone-side checklist
echo "<div class='info'>";
echo "<h2>📱 Phone-Side Checklist</h2>";
echo "<div class='step'>";
echo "<input type='checkbox' id='check1'> ";
echo "<label for='check1'><strong>Local Service is ENABLED</strong> (checkbox checked in Traccar app)</label>";
echo "</div>";

echo "<div class='step'>";
echo "<input type='checkbox' id='check2'> ";
echo "<label for='check2'><strong>IP address matches</strong> ($ip is correct in Traccar app)</label>";
echo "</div>";

echo "<div class='step'>";
echo "<input type='checkbox' id='check3'> ";
echo "<label for='check3'><strong>App is running</strong> (persistent notification visible)</label>";
echo "</div>";

echo "<div class='step'>";
echo "<input type='checkbox' id='check4'> ";
echo "<label for='check4'><strong>Battery optimization OFF</strong> (Settings → Apps → Traccar → Battery → Unrestricted)</label>";
echo "</div>";

echo "<div class='step'>";
echo "<input type='checkbox' id='check5'> ";
echo "<label for='check5'><strong>SMS permissions GRANTED</strong></label>";
echo "</div>";

echo "<div class='step'>";
echo "<input type='checkbox' id='check6'> ";
echo "<label for='check6'><strong>Phone has SMS load balance</strong></label>";
echo "</div>";

echo "<button onclick='checkAll()'>✓ Check All</button>";
echo "<script>
function checkAll() {
    for(let i = 1; i <= 6; i++) {
        document.getElementById('check' + i).checked = true;
    }
}
</script>";
echo "</div>";

// Troubleshooting guide
echo "<div class='info'>";
echo "<h2>🔧 Troubleshooting Guide</h2>";

echo "<h3>If connection test fails:</h3>";
echo "<ol>";
echo "<li><strong>Verify phone's IP address</strong> - It might have changed. Check Traccar app for current IP</li>";
echo "<li><strong>Check network connection</strong> - Phone and computer must be on same network</li>";
echo "<li><strong>Disable firewall</strong> - Temporarily disable Windows firewall</li>";
echo "<li><strong>Test with browser</strong> - Open <code>$ip:$port</code> in browser</li>";
echo "</ol>";

echo "<h3>If SMS sends but doesn't arrive:</h3>";
echo "<ol>";
echo "<li><strong>Make Traccar default SMS app</strong> (Settings → Apps → Default Apps → SMS App)</li>";
echo "<li><strong>Check SMS center number</strong> in phone settings</li>";
echo "<li><strong>Test manual SMS</strong> - Send a text to someone to verify SMS works</li>";
echo "<li><strong>Restart phone</strong> - Sometimes fixes SMS issues</li>";
echo "</ol>";

echo "<h3>Quick Fix Buttons:</h3>";
echo "<button onclick='window.location.href=\"?action=ping\"'>📡 Ping Phone</button> ";
echo "<button onclick='window.location.href=\"?action=restart\"'>🔄 Restart Service</button>";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'ping') {
        echo "<div class='info'>";
        echo "<h4>Ping Result:</h4>";
        $output = shell_exec("ping -n 4 $ip 2>&1");
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }
    if ($_GET['action'] == 'restart') {
        echo "<div class='warning'>";
        echo "<p>Restart command sent - please restart Traccar app manually</p>";
        echo "</div>";
    }
}
echo "</div>";

echo "</body></html>";
?>