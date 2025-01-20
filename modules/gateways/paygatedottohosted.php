<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function paygatedottohosted_MetaData()
{
    return array(
        'DisplayName' => 'paygatedottohosted',
        'DisableLocalCreditCardInput' => true,
    );
}

function paygatedottohosted_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'paygatedottohosted',
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

function paygatedottohosted_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/paygatedottohosted.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_paygatedottohostedio_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;
	$paygatedotto_paygatedottohostedio_final_total = $amount;
				
$paygatedotto_paygatedottohostedio_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_paygatedottohostedio_wallet_decbody = json_decode($paygatedotto_paygatedottohostedio_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_paygatedottohostedio_wallet_decbody && isset($paygatedotto_paygatedottohostedio_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_paygatedottohostedio_gen_addressIn = $paygatedotto_paygatedottohostedio_wallet_decbody['address_in'];
        $paygatedotto_paygatedottohostedio_gen_polygon_addressIn = $paygatedotto_paygatedottohostedio_wallet_decbody['polygon_address_in'];
		$paygatedotto_paygatedottohostedio_gen_callback = $paygatedotto_paygatedottohostedio_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_paygatedottohostedio_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/pay.php?address=' . $paygatedotto_paygatedottohostedio_gen_addressIn . '&amount=' . $paygatedotto_paygatedottohostedio_final_total . '&email=' . urlencode($email) . '&currency=' . $paygatedotto_paygatedottohostedio_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function paygatedottohosted_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'paygatedottohosted gateway activated successfully.');
}

function paygatedottohosted_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'paygatedottohosted gateway deactivated successfully.');
}

function paygatedottohosted_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function paygatedottohosted_output($vars)
{
    // Output additional information if needed
}

function paygatedottohosted_error($vars)
{
    // Handle errors if needed
}
