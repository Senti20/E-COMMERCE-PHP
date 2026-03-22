<?php
define("TRACCAR_URL", "http://10.167.124.81:8082/");
define("TRACCAR_TOKEN", "e8f73f4c-90d3-4a42-bea4-952dfcb1f09d");

function traccar_send_sms($to, $message) {
    $payload = json_encode([
        "to" => $to,
        "message" => $message
    ]);

    $ch = curl_init(TRACCAR_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: " . TRACCAR_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return [false, "cURL error: " . $error];
    }

    if ($http < 200 || $http >= 300) {
        return [false, "HTTP $http: " . $response];
    }

    return [true, $response ?: "OK"];
}

function format_phone_for_sms($phone) {
    $phone = trim($phone);
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (preg_match('/^09(\d{9})$/', $phone, $matches)) {
        return "+63" . $matches[1];
    }
    
    if (preg_match('/^63(\d{10})$/', $phone, $matches)) {
        return "+" . $phone;
    }
    
    if (preg_match('/^\+63(\d{10})$/', $phone)) {
        return $phone;
    }
    
    return false;
}

function send_otp_sms($phone, $otp) {
    $formattedPhone = format_phone_for_sms($phone);
    
    if (!$formattedPhone) {
        return [false, "Invalid Philippine mobile number format. Please use 09xxxxxxxxx format."];
    }
    
    $message = "SKYNET PC OTP: $otp. This code will expire in 5 minutes. Do not share this code with anyone.";
    
    return traccar_send_sms($formattedPhone, $message);
}
?>