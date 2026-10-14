# Instant Payment Gateway WHMCS
Instant WHMCS payment gateway to accept credit or debit cards or Google Pay or Apple Pay. Instant payouts to USDC wallet.

> 🪙 **Want crypto too?** Our free [WHMCS crypto module](https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/whmcs/) is the only crypto payment gateway with instant payouts for Solana, Monero, Zcash and Bitcoin.

## Features

- Accept **Visa, Mastercard, Apple Pay, Google Pay**, SEPA and local bank transfers.
- **Instant approval**: no sign up, no merchant account, no API keys and no KYC for you.
- **Instant payouts** to your own USDC (Polygon) wallet with every payment.
- **Zero chargebacks**: all payments are final.
- Multi-provider hosted payment page that picks the best provider for each customer's country, or one provider of your choice.
- Payment confirmations signed by PayGate.to and verified by the module: orders are updated automatically.
- Works with your own checkout domain and a free dedicated API endpoint ([custom domain guide](https://paygate.to/white-label-api-custom-domain-guide/)).
- Fees: **1% + provider fees**.

📘 **Step-by-step guide:** [https://paygate.to/instant-payment-gateway/whmcs/](https://paygate.to/instant-payment-gateway/whmcs/)

## Requirements

- WHMCS with admin access, and FTP/SFTP or your hosting File Manager
- PHP with cURL and OpenSSL
- Your WHMCS **System URL** set to your public https address (payment confirmations are sent there)
- A USDC (Polygon) wallet address

## Installation

1. **Get your payout wallet.** Card payments are paid out to a **USDC wallet on Polygon** (an address that starts with `0x`). Payouts may arrive as USDC, USDT (Polygon or BEP-20), ETH or POL, and the same address receives all of them. Use a **self-custodial wallet** you control (Trust Wallet, MetaMask, Rabby, a hardware wallet). **Never use an exchange deposit address**, and never paste the USDC token contract address.
2. **Download** [WHMCS-Instant-Payment-Gateway-main.zip](https://github.com/paygate-to/WHMCS-Instant-Payment-Gateway/archive/refs/heads/main.zip) and unzip it.
3. **Upload the `modules` folder** from inside the unzipped folder into your **WHMCS root** (the folder with `configuration.php`), merging it with your existing `modules` folder:

   ```
   WHMCS-Instant-Payment-Gateway-main/      <whmcs root>/
   └── modules/            ── upload ──►    └── modules/gateways/
                                                ├── paygatedottohosted.php
                                                ├── paygatedottocustom.php
                                                ├── paygatedotto/        (security library, PayGate key)
                                                ├── paygatedottohosted/  (logo)
                                                ├── paygatedottocustom/  (logo)
                                                └── callback/
                                                    ├── paygatedottohosted.php
                                                    └── paygatedottocustom.php
   ```

   Do not upload it into `modules/gateways`, or the files end up one level too deep.
4. **Activate**: WHMCS 8: **Configuration › System Settings › Payment Gateways › All Payment Gateways** (WHMCS 7: **Setup › Payments › Payment Gateways**). Click **paygatedottohosted** (recommended) and/or **paygatedottocustom**.
5. **Configure** and click **Save Changes**:

   | Field | What to enter |
   |---|---|
   | Display Name (WHMCS) | The name clients see, e.g. "Credit / Debit Card" |
   | Custom Domain | Leave `checkout.paygate.to` unless you use your own domain |
   | API Domain | Optional: your dedicated API endpoint |
   | USDC Polygon Wallet Address (required) | Your Polygon address (`0x...`) |
   | Fees Tolerance | How far below the invoice total a payment may be before it is not accepted, default 40% |
   | Custom Provider Tag (custom mode) | Provider tag, default `moonpay` ([provider list](https://api.paygate.to/control/provider-status/)) |
   | Minimum Invoice Amount (USD) (custom mode) | Default 15 |

6. **Test** with an invoice: the client clicks **Pay Now** and pays on the secure card page. When the payment is confirmed, the invoice is marked **Paid** automatically.

Every confirmation is also listed under **Billing › Gateway Log**: Successful, Underpaid (below your Fees Tolerance, the invoice stays unpaid) or Rejected (not a genuine PayGate.to confirmation).

**Updating:** upload the `modules` folder again, overwriting the old files (include the new `paygatedotto` folder). Settings are kept and invoices created before the update still settle.

## Troubleshooting

- **Gateway not listed**: check that `modules/gateways/paygatedottohosted.php` is directly inside your WHMCS root.
- **Invoice stays Unpaid**: check the System URL and that `/modules/gateways/callback/` is reachable (no password, IP allow-list or maintenance mode). See [WHMCS troubleshooting](https://paygate.to/whmcs-payment-gateway-troubleshooting/).
- **Minimum amounts** vary by provider (about $1 to $30): [see the minimums](https://paygate.to/instant-payment-gateway/#minimumorder).

Full product page: [https://paygate.to/instant-payment-gateway/](https://paygate.to/instant-payment-gateway/) · Support: [Telegram](https://t.me/PayGate_to)
