<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function swipelux_MetaData()
{
    return array(
        'DisplayName' => 'swipelux',
        'DisableLocalCreditCardInput' => true,
    );
}

function swipelux_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'swipelux',
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
            'Description' => 'Insert your USDC Polygon Wallet address.',
        ),
    );
}

function swipelux_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/swipelux.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_swipeluxcom_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;
	$paygatedotto_swipeluxcom_final_total = $amount;

if ($paygatedotto_swipeluxcom_currency === 'USD') {
        $paygatedotto_swipeluxcom_minimumcheck_final_total = $amount;
		} else {
$paygatedotto_swipeluxcom_minimumcheck_response = file_get_contents('https://api.paygate.to/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_swipeluxcom_currency));


$paygatedotto_swipeluxcom_minimumcheck_conversion_resp = json_decode($paygatedotto_swipeluxcom_minimumcheck_response, true);

if ($paygatedotto_swipeluxcom_minimumcheck_conversion_resp && isset($paygatedotto_swipeluxcom_minimumcheck_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_swipeluxcom_minimumcheck_final_total	= $paygatedotto_swipeluxcom_minimumcheck_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}

}

if ($paygatedotto_swipeluxcom_minimumcheck_final_total < 14) {
return "Error: Invoice total must be $14 USD or more for the selected payment provider.";
}
				
$paygatedotto_swipeluxcom_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_swipeluxcom_wallet_decbody = json_decode($paygatedotto_swipeluxcom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_swipeluxcom_wallet_decbody && isset($paygatedotto_swipeluxcom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_swipeluxcom_gen_addressIn = $paygatedotto_swipeluxcom_wallet_decbody['address_in'];
        $paygatedotto_swipeluxcom_gen_polygon_addressIn = $paygatedotto_swipeluxcom_wallet_decbody['polygon_address_in'];
		$paygatedotto_swipeluxcom_gen_callback = $paygatedotto_swipeluxcom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_swipeluxcom_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_swipeluxcom_gen_addressIn . '&amount=' . $paygatedotto_swipeluxcom_final_total . '&provider=swipelux&email=' . urlencode($email) . '&currency=' . $paygatedotto_swipeluxcom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function swipelux_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'swipelux gateway activated successfully.');
}

function swipelux_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'swipelux gateway deactivated successfully.');
}

function swipelux_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function swipelux_output($vars)
{
    // Output additional information if needed
}

function swipelux_error($vars)
{
    // Handle errors if needed
}
