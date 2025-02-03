<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function bitnovo_MetaData()
{
    return array(
        'DisplayName' => 'bitnovo',
        'DisableLocalCreditCardInput' => true,
    );
}

function bitnovo_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'bitnovo',
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

function bitnovo_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/bitnovo.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_bitnovocom_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

if ($paygatedotto_bitnovocom_currency === 'USD') {
        $paygatedotto_bitnovocom_final_total = $amount;
		} else {
		
$paygatedotto_bitnovocom_response = file_get_contents('https://api.paygate.to/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_bitnovocom_currency));


$paygatedotto_bitnovocom_conversion_resp = json_decode($paygatedotto_bitnovocom_response, true);

if ($paygatedotto_bitnovocom_conversion_resp && isset($paygatedotto_bitnovocom_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_bitnovocom_final_total	= $paygatedotto_bitnovocom_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		}
		
if ($paygatedotto_bitnovocom_final_total < 10) {
return "Error: Invoice total must be $10 USD or more for the selected payment provider.";
}		
		
		
$paygatedotto_bitnovocom_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_bitnovocom_wallet_decbody = json_decode($paygatedotto_bitnovocom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_bitnovocom_wallet_decbody && isset($paygatedotto_bitnovocom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_bitnovocom_gen_addressIn = $paygatedotto_bitnovocom_wallet_decbody['address_in'];
        $paygatedotto_bitnovocom_gen_polygon_addressIn = $paygatedotto_bitnovocom_wallet_decbody['polygon_address_in'];
		$paygatedotto_bitnovocom_gen_callback = $paygatedotto_bitnovocom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_bitnovocom_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_bitnovocom_gen_addressIn . '&amount=' . $paygatedotto_bitnovocom_final_total . '&provider=bitnovo&email=' . urlencode($email) . '&currency=' . $paygatedotto_bitnovocom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function bitnovo_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'bitnovo gateway activated successfully.');
}

function bitnovo_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'bitnovo gateway deactivated successfully.');
}

function bitnovo_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function bitnovo_output($vars)
{
    // Output additional information if needed
}

function bitnovo_error($vars)
{
    // Handle errors if needed
}
