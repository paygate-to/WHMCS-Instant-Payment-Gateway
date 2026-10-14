<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

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

function paygatedottocustom_MetaData()
{
    return array(
        'DisplayName' => 'paygatedottocustom',
        'DisableLocalCreditCardInput' => true,
    );
}

function paygatedottocustom_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'paygatedottocustom',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using Credit/debit card (including MasterCard, Visa, and Apple Pay).',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'custom_domain' => array(
            'FriendlyName' => 'Custom Domain',
            'Type' => 'text',
			'Default' => 'checkout.paygate.to',
            'Description' => 'Follow the custom domain guide to use your own domain name for the checkout pages and links.',
        ),
        'api_domain' => array(
            'FriendlyName' => 'API Domain',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Your dedicated API endpoint from <a href="https://paygate.to/white-label-api-custom-domain-guide/#dedicated-endpoint" target="_blank" rel="noopener">paygate.to</a>, e.g. api-yourshop-1a2b&hellip;.paygate.to, used for wallet and conversion calls. Leave empty to keep using the Custom Domain above.',
        ),
		'custom_provider' => array(
            'FriendlyName' => 'Custom Provider Tag',
            'Type' => 'text',
			'Default' => 'moonpay',
            'Description' => 'Insert custom provider tag per the API docs or contact paygate.to support for further instructions.',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in ETH or USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
        'tolerance_percentage' => array(
            'FriendlyName' => 'Fees Tolerance',
            'Type' => 'dropdown',
            'Options' => array(
                '0.90' => '10%',
                '0.80' => '20%',
                '0.70' => '30%',
                '0.60' => '40%',
                '0.50' => '50%',
                '0.40' => '60%',
                '0.30' => '70%',
                '0.20' => '80%',
                '0.10' => '90%',
                '0' => 'Disabled amount detection',
            ),
            'Default' => '0.60',
            'Description' => 'Select percentage to tolerate higher provider fees while detecting manipulation by bad customers at checkout page.',
        ),
		'minimum_amount' => array(
            'FriendlyName' => 'Minimum Invoice Amount (USD)',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '15',
            'Description' => 'Set the minimum invoice total (in USD) allowed for this provider.',
        ),
    );
}

function paygatedottocustom_link($params)
{
    $walletAddress = $params['wallet_address'];
    $customDomain = rtrim(str_replace(['https://','http://'], '', $params['custom_domain']), '/');
	$customProvider = $params['custom_provider'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/paygatedottocustom.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_paygatedottocustom_currency = $params['currency'];
	// Callback signed with a secret kept only in this WHMCS database (see paygatedotto/lib.php).
	require_once __DIR__ . '/paygatedotto/lib.php';
	$sig = paygatedotto_cb_sig($invoiceId);
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId . '&sig=' . $sig;

    $minimumAmount = isset($params['minimum_amount']) && is_numeric($params['minimum_amount']) ? floatval($params['minimum_amount']) : 15.0;


if ($paygatedotto_paygatedottocustom_currency === 'USD') {
        $paygatedotto_paygatedottocustom_final_total = $amount;
		} else {
		
$paygatedotto_paygatedottocustom_response = paygatedotto_http_get('https://' . paygatedotto_api_host($params) . '/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_paygatedottocustom_currency));


$paygatedotto_paygatedottocustom_conversion_resp = json_decode($paygatedotto_paygatedottocustom_response, true);

if ($paygatedotto_paygatedottocustom_conversion_resp && isset($paygatedotto_paygatedottocustom_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_paygatedottocustom_final_total	= $paygatedotto_paygatedottocustom_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		}
		
if ($paygatedotto_paygatedottocustom_final_total < $minimumAmount) {
return "Error: Invoice total must be $" . number_format($minimumAmount, 2) . " USD or more for the selected payment provider.";
}		
		
		
$paygatedotto_paygatedottocustom_gen_wallet = paygatedotto_http_get('https://' . paygatedotto_api_host($params) . '/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_paygatedottocustom_wallet_decbody = json_decode($paygatedotto_paygatedottocustom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_paygatedottocustom_wallet_decbody && isset($paygatedotto_paygatedottocustom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_paygatedottocustom_gen_addressIn = $paygatedotto_paygatedottocustom_wallet_decbody['address_in'];
        $paygatedotto_paygatedottocustom_gen_polygon_addressIn = $paygatedotto_paygatedottocustom_wallet_decbody['polygon_address_in'];
		$paygatedotto_paygatedottocustom_gen_callback = $paygatedotto_paygatedottocustom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_paygatedottocustom_gen_polygon_addressIn";

            // Update the invoice notes, without overwriting notes the admin wrote
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $currentNotes = isset($invoice['notes']) ? trim((string) $invoice['notes']) : '';
            if ($currentNotes !== $invoiceDescription && ($currentNotes === '' || strpos($currentNotes, 'Payment reference number: ') === 0)) {
                localAPI("UpdateInvoice", array('invoiceid' => $invoiceId, 'notes' => $invoiceDescription));
            }

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://' . $customDomain . '/process-payment.php?address=' . $paygatedotto_paygatedottocustom_gen_addressIn . '&amount=' . $amount . '&provider=' . $customProvider . '&email=' . urlencode($email) . '&currency=' . $paygatedotto_paygatedottocustom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function paygatedottocustom_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'paygatedottocustom gateway activated successfully.');
}

function paygatedottocustom_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'paygatedottocustom gateway deactivated successfully.');
}

function paygatedottocustom_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function paygatedottocustom_output($vars)
{
    // Output additional information if needed
}

function paygatedottocustom_error($vars)
{
    // Handle errors if needed
}
