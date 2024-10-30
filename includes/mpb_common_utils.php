<?php
if (!defined('ABSPATH')) exit;

$sand_box_token_url = "https://keycloak.m1pay.com.my/auth/realms/master/protocol/openid-connect/token";
$sand_box_transaction_url = "https://gateway-uat.m1pay.com.my/m1paywall/api/transaction";
$sand_box_check_transaction_url = "https://gateway-uat.m1pay.com.my/m1paywall/api/m-1-pay-transactions/";

$production_token_url = "https://keycloak.m1pay.com.my/auth/realms/m1pay-users/protocol/openid-connect/token";
$production_transaction_url = "https://gateway.m1pay.com.my/wall/api/transaction";
$production_check_transaction_url = "https://gateway.m1pay.com.my/wall/api/m-1-pay-transactions/";

function mpb_get_token($client_id, $client_secret, $is_sand_box)
{
    global $sand_box_token_url, $production_token_url;
    $pload = array(
        'method' => 'POST',
        'headers' => [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'sslverify' => false,
        'body' => [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ],
    );
    $url = $is_sand_box == 'no' ?  $production_token_url : $sand_box_token_url;
    $response = wp_remote_post($url, $pload);
    $responseArray = json_decode($response['body'], TRUE);

    if ($responseArray['access_token'])
        return $responseArray['access_token'];
    else
        return null;
}

function mpb_str_to_hex($string)
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0' . $hexCode, -2);
    }
    return strToUpper($hex);
}

function mpb_get_transaction_id($transAction, $is_sand_box)
{
    global $sand_box_transaction_url, $production_transaction_url;
    $data = array(
        'transactionAmount' => $transAction->amount,
        'merchantId' => $transAction->merchantID,
        'transactionCurrency' => 'MYR',
        'merchantOrderNo' => $transAction->merchantOrderID,
        'exchangeOrderNo' => $transAction->merchantOrderID,
        'productDescription' => $transAction->description,
        'fpxBank' => '1',
        'signedData' => $transAction->signedData,
    );
    $body = json_encode($data);
    $pload = array(
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $transAction->token,
            'Cache-Control' => 'no-cache',
            'Content-Length' => strlen($body),
            'Content-Type' => 'application/json'
        ],
        'sslverify' => false,
        'body' => $body,
        'data_format' => 'body',
    );
    $url = $is_sand_box == 'no' ? $production_transaction_url : $sand_box_transaction_url;
    $response = wp_remote_post($url, $pload);
    return $response['body'];
}

function mpb_check_transaction_status($token, $transactionId, $is_sand_box)
{
    global $sand_box_check_transaction_url, $production_check_transaction_url;
    if (!empty($token)) {
        $pload = array(
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'sslverify' => false,
        );
        $url = $is_sand_box == 'no' ? $production_check_transaction_url : $sand_box_check_transaction_url;
        $response = wp_remote_get($url . $transactionId, $pload);
        $result = array_key_exists('body', $response) ? json_decode($response['body'], true) : [];
        return array_key_exists('transactionStatus', $result) ? $result['transactionStatus'] : '';
    }
}

function mpb_validate_numeric_input($raw_input)
{
    $processed_input = $raw_input;
    if ($processed_input) {
        $processed_input = sanitize_text_field($processed_input);
        $processed_input = htmlspecialchars($processed_input);
        if (!ctype_digit($processed_input)) {
            return '';
        }

    }
    return $processed_input;
}

function mpb_validate_text_input($raw_input, $should_escape_special_chars = true)
{
    $processed_input = $raw_input;
    if ($processed_input) {
        $processed_input = sanitize_text_field($processed_input);
        if ($should_escape_special_chars) {
            $processed_input = htmlspecialchars($processed_input);
        }
    }
    return $processed_input;
}
