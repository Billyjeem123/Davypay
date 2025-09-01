<?php

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://sandbox.nomba.com/v1/auth/accounts/virtual",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\n  \"accountRef\": \"Ref-QVH48GXEKU-1753339284\",\n  \"accountName\": \"samm lee\"\n}",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJHOjYxMzAyN2RmLWI4N2UtNDE4Yi1iMzk5LWMyOWQ0MTExYmVlYyI6Ikc6NjEzMDI3ZGYtYjg3ZS00MThiLWIzOTktYzI5ZDQxMTFiZWVjIiwiRTpzYW5kYm94LkpTdmFlcGhMS09uZGxiZGNmZHNvc3dmamxnZmtod3J6bXdudmlhd3plcEB2ZW5kb3JfYXBpLmNvbSI6IkU6c2FuZGJveC5KU3ZhZXBoTEtPbmRsYmRjZmRzb3N3ZmpsZ2ZraHdyem13bnZpYXd6ZXBAdmVuZG9yX2FwaS5jb20iLCJSOlZFTkRPUl9BUElfQURNSU4iOiJSOlZFTkRPUl9BUElfQURNSU4iLCJpYXQiOjE3NTMzMzk1OTAsInN1YiI6ImE4NDJkMjFhLWFhMWEtNGIzMC1iZjMxLWRkMmIwZDA4NzQ5NSIsImV4cCI6MTc1MzM1MDM5MH0.KrKqQjgkHEU_wjHws2WeiAYFXFkqskWtUw1H_mNkfqc",
        "Content-Type: application/json",
        "accountId: 613027df-b87e-418b-b399-c29d4111beec"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}
