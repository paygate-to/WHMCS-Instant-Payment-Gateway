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

// Retrieve the invoice ID from the query string
$invoiceId = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;
$paid_coinname = isset($_GET['coin']) ? (string) $_GET['coin'] : '';

if ($invoiceId <= 0) {
    die("Invalid invoice ID");
}

// Include WHMCS required files
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../paygatedotto/lib.php';

// Get the gateway module name from the filename
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (empty($gatewayParams['type'])) {
    paygatedotto_cb_fail(404, 'Module not activated');
}

// Check 1: the per-invoice signature in the callback URL we registered.
if (!paygatedotto_cb_sig_ok($invoiceId, $gatewayParams['wallet_address'], isset($_GET['sig']) ? $_GET['sig'] : '')) {
    logTransaction($gatewayParams['name'], $_GET, 'Rejected: invalid callback signature');
    paygatedotto_cb_fail(403, 'Invalid callback signature');
}

// Check 2: PayGate's RSA signature over the exact URL it requested. Without it,
// value_coin could be forged, so unsigned or tampered callbacks are rejected.
$systemUrl = isset($CONFIG['SystemURL']) ? $CONFIG['SystemURL'] : \WHMCS\Config\Setting::getValue('SystemURL');
$paygateSignature = paygatedotto_verify_paygate_signature(rtrim($systemUrl, '/') . '/modules/gateways/callback/' . $gatewayModuleName . '.php');
if (!$paygateSignature['verified']) {
    logTransaction($gatewayParams['name'], $_GET, 'Rejected: PayGate signature ' . $paygateSignature['reason']);
    paygatedotto_cb_fail(403, 'Invalid PayGate signature (' . $paygateSignature['reason'] . ')');
}

// Received amount in USD (coin payouts are priced via info.php)
$valueCoin = isset($_GET['value_coin']) ? (float) $_GET['value_coin'] : 0.0;
if ($paid_coinname == 'polygon_pol' || $paid_coinname == 'eth' || $paid_coinname == 'bep20_bnb') {
	
$paygatedottogateway_hostedpaygatedottopaid_strname_coin = str_replace('_', '/', $paid_coinname);

$paygatedottogateway_hostedpaygate_response_minimum = paygatedotto_http_get('https://' . paygatedotto_api_host($gatewayParams) . '/crypto/' . $paygatedottogateway_hostedpaygatedottopaid_strname_coin . '/info.php');
$paygatedottogateway_hostedpaygate_conversion_resp_minimum = json_decode($paygatedottogateway_hostedpaygate_response_minimum, true);
if ($paygatedottogateway_hostedpaygate_conversion_resp_minimum && isset($paygatedottogateway_hostedpaygate_conversion_resp_minimum['prices']['USD'])) {
   $receivedAmount = $paygatedottogateway_hostedpaygate_conversion_resp_minimum['prices']['USD'] * $valueCoin;
} else {
    $receivedAmount = $valueCoin;
}

} else {
$receivedAmount = $valueCoin; // Amount received, in USD
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

    // Fees tolerance: the share of the invoice total that must arrive (default 0.60,
    // the fixed value older versions used). 0 disables the amount check.
    $tolerance = (isset($gatewayParams['tolerance_percentage']) && is_numeric($gatewayParams['tolerance_percentage']))
        ? max(0.0, min(1.0, (float) $gatewayParams['tolerance_percentage']))
        : 0.60;
    $threshold = $tolerance * $convertedAmount;

    if ($receivedAmount < $threshold) {
        // Payment is below the tolerance, do not mark as paid
        logTransaction($gatewayParams['name'], $_GET, 'Underpaid');
        die("Error: Payment received is below the fees tolerance. Provider sent $receivedAmount USD, the invoice total is $convertedAmount USD ($invoiceTotal $invoiceCurrencyCode) and at least " . round($tolerance * 100) . "% is required.");
    }

    // A verified callback can arrive more than once: never credit the same transaction twice.
    if (!empty($_GET['txid_out'])) {
        checkCbTransID('paygatedottohosted_payment_' . $_GET['txid_out']);
    }

    // Mark the invoice as paid
    $paymentSuccess = [
        'invoiceid' => $invoiceId,
        'transid' => 'paygatedottohosted_payment_' . ($_GET['txid_out'] ?? time()), // Replace with the actual transaction ID if available
        'date' => date('Y-m-d H:i:s'),
    ];

    $result = localAPI('AddInvoicePayment', $paymentSuccess);

    if ($result['result'] == 'success') {
        logTransaction($gatewayParams['name'], $_GET, 'Successful');
        header("HTTP/1.1 200 OK");
        header("Content-Type: text/plain");
        echo "*ok*";
        exit;
    } else {
        // Redirect to the invoice page with an error
        $invoiceLink = rtrim($systemUrl, '/') . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink&error=payment_failed");
        exit;
    }
} elseif ($invoice['result'] == 'success' && $invoice['status'] == 'Paid') {
    // Already paid (for example a repeated callback): acknowledge so PayGate stops retrying.
    header("HTTP/1.1 200 OK");
    header("Content-Type: text/plain");
    echo "*ok*";
    exit;
} else {
    // Redirect to the invoice page
    $invoiceLink = rtrim($systemUrl, '/') . '/viewinvoice.php?id=' . $invoiceId;
    header("Location: $invoiceLink&error=invalid_invoice");
    exit;
}
?>
