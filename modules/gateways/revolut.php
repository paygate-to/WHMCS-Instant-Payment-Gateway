<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function revolut_MetaData()
{
    return array(
        'DisplayName' => 'revolut',
        'DisableLocalCreditCardInput' => true,
    );
}

function revolut_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'revolut',
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
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in ETH or USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
    );
}

function revolut_link($params)
{
    $walletAddress = $params['wallet_address'];
    $customDomain = rtrim(str_replace(['https://','http://'], '', $params['custom_domain']), '/');
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/revolut.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_revolutcom_currency = $params['currency'];
	$secret = hash('sha256', 'paygate_salt_' . $walletAddress);
	$sig = hash_hmac('sha256', $invoiceId, $secret);
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId . '&sig=' . $sig;
	$paygatedotto_revolutcom_final_total = $amount;

if ($paygatedotto_revolutcom_currency === 'USD') {
        $paygatedotto_revolutcom_minimumcheck_final_total = $amount;
		} else {
$paygatedotto_revolutcom_minimumcheck_response = file_get_contents('https://api.paygate.to/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_revolutcom_currency));


$paygatedotto_revolutcom_minimumcheck_conversion_resp = json_decode($paygatedotto_revolutcom_minimumcheck_response, true);

if ($paygatedotto_revolutcom_minimumcheck_conversion_resp && isset($paygatedotto_revolutcom_minimumcheck_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_revolutcom_minimumcheck_final_total	= $paygatedotto_revolutcom_minimumcheck_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}

}

if ($paygatedotto_revolutcom_minimumcheck_final_total < 8) {
return "Error: Invoice total must be $8 USD or more for the selected payment provider.";
}
				
$paygatedotto_revolutcom_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_revolutcom_wallet_decbody = json_decode($paygatedotto_revolutcom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_revolutcom_wallet_decbody && isset($paygatedotto_revolutcom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_revolutcom_gen_addressIn = $paygatedotto_revolutcom_wallet_decbody['address_in'];
        $paygatedotto_revolutcom_gen_polygon_addressIn = $paygatedotto_revolutcom_wallet_decbody['polygon_address_in'];
		$paygatedotto_revolutcom_gen_callback = $paygatedotto_revolutcom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_revolutcom_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://' . $customDomain . '/process-payment.php?address=' . $paygatedotto_revolutcom_gen_addressIn . '&amount=' . $paygatedotto_revolutcom_final_total . '&provider=revolut&email=' . urlencode($email) . '&currency=' . $paygatedotto_revolutcom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function revolut_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'revolut gateway activated successfully.');
}

function revolut_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'revolut gateway deactivated successfully.');
}

function revolut_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function revolut_output($vars)
{
    // Output additional information if needed
}

function revolut_error($vars)
{
    // Handle errors if needed
}
