<?php
$api_key  = "6448334bfb17055d87c74b53";
$url      = "https://v6.exchangerate-api.com/v6/$api_key/latest/USD";
$response = file_get_contents($url);
echo $response;
?>