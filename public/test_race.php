<?php

$endpoint = "http://bilpay.test/api/payment/transfer/bank";
$token = "51|cnSTG2Ou6mN25cgSzG57erP6nzJfgtOkl5RYf71jc4ea5f42"; // Sanctum token

$params = [
    "amount" => 200,
    "account_number" => "3146614382",
    "bank_code" => "011",
    "account_name" => "BILLYHADIAT TAOFEEQ OLADEJI",
    "bank_name" => "First Bank of Nigeria",
    "narration" => "Transfer from Billy billia",
    "transaction_pin" => "1234"
];

$payload = json_encode($params);

$multiHandle = curl_multi_init();
$curlHandles = [];

// create 10 parallel requests
for ($i = 0; $i < 4; $i++) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token",
            "idempotency-key: 009876540"
        ],
    ]);

    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[$i] = $ch;
}

// execute all requests concurrently
$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// fetch responses
foreach ($curlHandles as $i => $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "Response " . ($i + 1) . " (HTTP $httpCode):\n$response\n\n";

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);
