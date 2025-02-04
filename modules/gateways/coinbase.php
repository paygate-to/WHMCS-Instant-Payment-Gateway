<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function coinbase_MetaData()
{
    return array(
        'DisplayName' => 'coinbase',
        'DisableLocalCreditCardInput' => true,
    );
}

function coinbase_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'coinbase',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using Credit/debit card (including MasterCard, Visa, and Apple Pay).',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
    );
}

function coinbase_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/coinbase.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_coinbasecom_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;
	$paygatedotto_coinbasecom_final_total = $amount;

if ($paygatedotto_coinbasecom_currency === 'USD') {
        $paygatedotto_coinbasecom_minimumcheck_final_total = $amount;
		} else {
$paygatedotto_coinbasecom_minimumcheck_response = file_get_contents('https://api.paygate.to/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_coinbasecom_currency));


$paygatedotto_coinbasecom_minimumcheck_conversion_resp = json_decode($paygatedotto_coinbasecom_minimumcheck_response, true);

if ($paygatedotto_coinbasecom_minimumcheck_conversion_resp && isset($paygatedotto_coinbasecom_minimumcheck_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_coinbasecom_minimumcheck_final_total	= $paygatedotto_coinbasecom_minimumcheck_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}

}

if ($paygatedotto_coinbasecom_minimumcheck_final_total < 2) {
return "Error: Invoice total must be $2 USD or more for the selected payment provider.";
}
				
$paygatedotto_coinbasecom_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_coinbasecom_wallet_decbody = json_decode($paygatedotto_coinbasecom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_coinbasecom_wallet_decbody && isset($paygatedotto_coinbasecom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_coinbasecom_gen_addressIn = $paygatedotto_coinbasecom_wallet_decbody['address_in'];
        $paygatedotto_coinbasecom_gen_polygon_addressIn = $paygatedotto_coinbasecom_wallet_decbody['polygon_address_in'];
		$paygatedotto_coinbasecom_gen_callback = $paygatedotto_coinbasecom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_coinbasecom_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_coinbasecom_gen_addressIn . '&amount=' . $paygatedotto_coinbasecom_final_total . '&provider=coinbase&email=' . urlencode($email) . '&currency=' . $paygatedotto_coinbasecom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function coinbase_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'coinbase gateway activated successfully.');
}

function coinbase_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'coinbase gateway deactivated successfully.');
}

function coinbase_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function coinbase_output($vars)
{
    // Output additional information if needed
}

function coinbase_error($vars)
{
    // Handle errors if needed
}
