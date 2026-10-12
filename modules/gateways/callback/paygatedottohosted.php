<?php

if (!function_exists('paygatedotto_http_get')) {
    /**
     * Perform an HTTP GET request using cURL.
     * Drop-in replacement for file_get_contents() on a URL:
     * returns the response body as a string, or false on failure.
     */
    function paygatedotto_http_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        if ($response === false || curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $response;
    }
}

if (!function_exists('paygatedotto_api_host')) {
    /**
     * Host for server-to-server API calls: the "API Domain" setting (your dedicated
     * PayGate API endpoint) when it is set, otherwise the Custom Domain, as before.
     */
    function paygatedotto_api_host($params)
    {
        $host = isset($params['api_domain']) ? rtrim(str_replace(array('https://', 'http://'), '', trim((string) $params['api_domain'])), '/') : '';
        if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            $host = isset($params['custom_domain']) ? rtrim(str_replace(array('https://', 'http://'), '', trim((string) $params['custom_domain'])), '/') : '';
        }
        return $host;
    }
}

// Retrieve the invoice ID and received amount from the query string
$invoiceId = $_GET['invoice_id'];
$paid_coinname = $_GET['coin'];

if (empty($invoiceId)) {
    die("Invalid invoice ID");
}

// Include WHMCS required files
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Get the gateway module name from the filename
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Retrieve the invoice information using localAPI
$secret = hash('sha256', 'paygate_salt_' . $gatewayParams['wallet_address']);
if(!hash_equals(hash_hmac('sha256', $invoiceId, $secret), ($_GET['sig'] ?? ''))) { die('Invalid callback signature'); }

// Received amount in USD (coin payouts are priced via info.php; needs $gatewayParams, so it runs after the settings are loaded)
if ($paid_coinname == 'polygon_pol' || $paid_coinname == 'eth' || $paid_coinname == 'bep20_bnb') {
	
$paygatedottogateway_hostedpaygatedottopaid_strname_coin = str_replace('_', '/', $paid_coinname);

$paygatedottogateway_hostedpaygate_response_minimum = paygatedotto_http_get('https://' . paygatedotto_api_host($gatewayParams) . '/crypto/' . $paygatedottogateway_hostedpaygatedottopaid_strname_coin . '/info.php');
$paygatedottogateway_hostedpaygate_conversion_resp_minimum = json_decode($paygatedottogateway_hostedpaygate_response_minimum, true);
if ($paygatedottogateway_hostedpaygate_conversion_resp_minimum && isset($paygatedottogateway_hostedpaygate_conversion_resp_minimum['prices']['USD'])) {
   $receivedAmount = $paygatedottogateway_hostedpaygate_conversion_resp_minimum['prices']['USD'] * $_GET['value_coin'];
} else {
    $receivedAmount = $_GET['value_coin'];
}

} else {
$receivedAmount = $_GET['value_coin']; // This should be the amount received in the callback
}

$invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

if ($invoice['result'] == 'success' && $invoice['status'] != 'Paid') {
    // Get the client's currency
    $clientId = $invoice['userid'];
    $currency = getCurrency($clientId); 
    $invoiceCurrencyCode = $currency['code']; // Currency code, e.g., USD, EUR, etc.
    $invoiceTotal = $invoice['total']; // Get the total amount of the invoice

    // Convert invoice total to USD if necessary
    if ($invoiceCurrencyCode !== 'USD') {
        // Fetch conversion rate from paygate.to API
        $conversionResponse = paygatedotto_http_get(
            'https://' . paygatedotto_api_host($gatewayParams) . '/control/convert.php?value=' . $invoiceTotal . '&from=' . strtolower($invoiceCurrencyCode)
        );

        if ($conversionResponse === false) {
            die("Error: Could not convert currency. Please try again.");
        }

        $conversionData = json_decode($conversionResponse, true);

        if (!isset($conversionData['value_coin'])) {
            die("Error: Conversion failed. Please check your currency settings.");
        }

        $convertedAmount = (float)$conversionData['value_coin'];
    } else {
        $convertedAmount = (float)$invoiceTotal;
    }

    // Determine if the payment meets the threshold (60% of the invoice total)
    $threshold = 0.60 * $convertedAmount;
    

    if ($receivedAmount < $threshold) {
        // Payment is less than 60% of the expected amount, do not mark as paid
        die("Error: Payment received is less than 60% of the invoice total. Provider sent $receivedAmount The converted to USD amount is $convertedAmount USD and the original invoice was for $invoiceTotal $invoiceCurrencyCode");
    }

    // Mark the invoice as paid
    $paymentSuccess = [
        'invoiceid' => $invoiceId,
        'transid' => 'paygatedottohosted_payment_' . ($_GET['txid_out'] ?? time()), // Replace with the actual transaction ID if available
        'date' => date('Y-m-d H:i:s'),
    ];

    $result = localAPI('AddInvoicePayment', $paymentSuccess);

    if ($result['result'] == 'success') {
        header("HTTP/1.1 200 OK");
        header("Content-Type: text/plain");
        echo "*ok*";
        exit;
    } else {
        // Redirect to the invoice page with an error
        $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink&error=payment_failed");
        exit;
    }
} else {
    // Redirect to the invoice page
    $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
    header("Location: $invoiceLink&error=invalid_invoice");
    exit;
}
?>
