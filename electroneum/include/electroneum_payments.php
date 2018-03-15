<?php

/*
 * Main Gateway of Monero using a daemon online
 * Authors: Serhack and cryptochangements
 *
 * Modified March 2018 by NirvanaLabs to allow WooCommerce to accept Electroneum.com (ETN) Cryptocurrency
 * Main Gateway of Electroneum using a daemon online
 * Author URI: http://nirvanalabs.co
 */


class Electroneum_Gateway extends WC_Payment_Gateway
{
    private $reloadTime = 17000;
    private $discount;
    private $confirmed = false;
    private $electroneum_daemon;
    private $non_rpc = false;
    private $zero_cofirm = false;

    function __construct()
    {
        $this->id = "electroneum_gateway";
        $this->method_title = __("Electroneum GateWay", 'electroneum_gateway');
        $this->method_description = __("Electroneum Payment Gateway Plug-in for WooCommerce. You can find more information about this payment gateway on our website. You'll need a daemon online for your address.", 'electroneum_gateway');
        $this->title = __("Electroneum Gateway", 'electroneum_gateway');
        $this->version = "2.0";
        //
        $this->icon = apply_filters('woocommerce_offline_icon', '');
        $this->has_fields = false;

        $this->log = new WC_Logger();

        $this->init_form_fields();
        $this->host = $this->get_option('daemon_host');
        $this->port = $this->get_option('daemon_port');
        $this->address = $this->get_option('electroneum_address');
        $this->viewKey = $this->get_option('viewKey');
        $this->discount = $this->get_option('discount');
        $this->accept_zero_conf = $this->get_option('zero_conf');

        $this->use_viewKey = $this->get_option('use_viewKey');
        $this->use_rpc = $this->get_option('use_rpc');

        if($this->use_viewKey == 'yes')
        {
            $this->non_rpc = true;
        }
        if($this->use_rpc == 'yes')
        {
            $this->non_rpc = false;
        }
        if($this->accept_zero_conf == 'yes')
        {
            $this->zero_confirm = true;
        }
        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option('title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        add_action('admin_notices', array($this, 'do_ssl_check'));
        add_action('admin_notices', array($this, 'validate_fields'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'instruction'));
        if (is_admin()) {
            /* Save Settings */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_currencies', 'add_my_currency');
            add_filter('woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);
        }
        $this->electroneum_daemon = new Electroneum_Library($this->host, $this->port);
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'electroneum_gateway'),
                'label' => __('Enable this payment gateway', 'electroneum_gateway'),
                'type' => 'checkbox',
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'electroneum_gateway'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.', 'electroneum_gateway'),
                'default' => __('Electroneum (ETN) Payment', 'electroneum_gateway')
            ),
            'description' => array(
                'title' => __('Description', 'electroneum_gateway'),
                'type' => 'textarea',
                'desc_tip' => __('Payment description the customer will see during the checkout process.', 'electroneum_gateway'),
                'default' => __('Pay securely using ETN.', 'electroneum_gateway')

            ),
            'use_viewKey' => array(
                'title' => __('Use ViewKey', 'electroneum_gateway'),
                'label' => __(' Verify Transaction with ViewKey ', 'electroneum_gateway'),
                'type' => 'checkbox',
                'description' => __('Fill in the Address and ViewKey fields to verify transactions with your ViewKey', 'electroneum_gateway'),
                'default' => 'no'
            ),
            'electroneum_address' => array(
                'title' => __('Electroneum Address', 'electroneum_gateway'),
                'label' => __('Useful for people that have not a daemon online'),
                'type' => 'text',
                'desc_tip' => __('Electroneum Wallet Address', 'electroneum_gateway')
            ),
            'viewKey' => array(
                'title' => __('Secret ViewKey', 'electroneum_gateway'),
                'label' => __('Secret ViewKey'),
                'type' => 'text',
                'desc_tip' => __('Your secret ViewKey', 'electroneum_gateway')
            ),
            'use_rpc' => array(
                'title' => __('Use electroneum-wallet-rpc', 'electroneum_gateway'),
                'label' => __(' Verify transactions with the electroneum-wallet-rpc ', 'electroneum_gateway'),
                'type' => 'checkbox',
                'description' => __('This must be setup seperatly', 'electroneum_gateway'),
                'default' => 'no'
            ),
            'daemon_host' => array(
                'title' => __('Electroneum wallet rpc Host/ IP', 'electroneum_gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with port', 'electroneum_gateway'),
                'default' => 'localhost',
            ),
            'daemon_port' => array(
                'title' => __('Electroneum wallet rpc port', 'electroneum_gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with port', 'electroneum_gateway'),
                'default' => '18080',
            ),
            'discount' => array(
                'title' => __('% discount for using ETN', 'electroneum_gateway'),

                'desc_tip' => __('Provide a discount to your customers for making a private payment with ETN!', 'electroneum_gateway'),
                'description' => __('Do you want to spread the word about Electroneum? Offer a small discount! Leave this empty if you do not wish to provide a discount', 'electroneum_gateway'),
                'type' => __('number'),
                'default' => '5'

            ),
            'environment' => array(
                'title' => __(' Testnet', 'electroneum_gateway'),
                'label' => __(' Check this if you are using testnet ', 'electroneum_gateway'),
                'type' => 'checkbox',
                'description' => __('Check this box if you are using testnet', 'electroneum_gateway'),
                'default' => 'no'
            ),
            'zero_conf' => array(
                'title' => __(' Accept 0 conf txs', 'electroneum_gateway'),
                'label' => __(' Accept 0-confirmation transactions ', 'electroneum_gateway'),
                'type' => 'checkbox',
                'description' => __('This is faster but less secure', 'electroneum_gateway'),
                'default' => 'no'
            ),
            'onion_service' => array(
                'title' => __(' SSL warnings ', 'electroneum_gateway'),
                'label' => __(' Check to Silence SSL warnings', 'electroneum_gateway'),
                'type' => 'checkbox',
                'description' => __('Check this box if you are running on an Onion Service (Suppress SSL errors)', 'electroneum_gateway'),
                'default' => 'no'
            ),
        );
    }

    public function add_my_currency($currencies)
    {
        $currencies['ETN'] = __('Electroneum', 'woocommerce');
        return $currencies;
    }

    function add_my_currency_symbol($currency_symbol, $currency)
    {
        switch ($currency) {
            case 'ETN':
                $currency_symbol = 'ETN';
                break;
        }
        return $currency_symbol;
    }

    public function admin_options()
    {
        $this->log->add('Electroneum_gateway', '[SUCCESS] Electroneum Settings OK');
        echo "<h1>Electroneum Payment Gateway</h1>";

        echo "<p>Welcome to Electroneum Extension for WooCommerce. Getting started: Make a connection with daemon <a href='http://nirvanalabs.co'>Contact Us</a>";
        echo "<div style='border:1px solid #DDD;padding:5px 10px;font-weight:bold;color:#223079;background-color:#9ddff3;'>";

        if(!$this->non_rpc) // only try to get balance data if using wallet-rpc
            $this->getamountinfo();

        echo "</div>";
        echo "<table class='form-table'>";
        $this->generate_settings_html();
        echo "</table>";
        echo "<h4>Learn more about using electroneum-wallet-rpc <a href=\"https://github.com/rajnirvanalabs/electroneumwp/blob/master/README.md\">here</a></h4>";
    }

    public function getamountinfo()
    {
        $wallet_amount = $this->electroneum_daemon->getbalance();
        if (!isset($wallet_amount)) {
            $this->log->add('Electroneum_gateway', '[ERROR] Can not connect to electroneum-wallet-rpc');
            echo "</br>Your balance is: Not Avaliable </br>";
            echo "Unlocked balance: Not Avaliable";
        }
        else
        {
            $real_wallet_amount = $wallet_amount['balance'] / 100;
            $real_amount_rounded = round($real_wallet_amount, 2);

            $unlocked_wallet_amount = $wallet_amount['unlocked_balance'] / 100;
            $unlocked_amount_rounded = round($unlocked_wallet_amount, 2);

            echo "Your balance is: " . $real_amount_rounded . " ETN </br>";
            echo "Unlocked balance: " . $unlocked_amount_rounded . " ETN </br>";
        }
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('on-hold', __('Awaiting offline payment', 'electroneum_gateway'));
        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        WC()->cart->empty_cart();

        // Return thank you redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );

    }

    // Submit payment and handle response

    public function validate_fields()
    {
        if ($this->check_electroneum() != TRUE) {
            echo "<div class=\"error\"><p>Your Electroneum Address doesn't look valid. Have you checked it?</p></div>";
        }
        if(!$this->check_viewKey())
        {
            echo "<div class=\"error\"><p>Your ViewKey doesn't look valid. Have you checked it?</p></div>";
        }
        if($this->check_checkedBoxes())
        {
            echo "<div class=\"error\"><p>You must choose to either use electroneum-wallet-rpc or a ViewKey, not both</p></div>";
        }

    }


    // Validate fields

    public function check_electroneum()
    {
        $electroneum_address = $this->settings['electroneum_address'];
        if (strlen($electroneum_address) == 98 && substr($electroneum_address, 0,3)=='etn') {
            return true;
        }
        return false;
    }
    public function check_viewKey()
    {
        if($this->use_viewKey == 'yes')
        {
            if (strlen($this->viewKey) == 64) {
                return true;
            }
            return false;
        }
        return true;
    }
    public function check_checkedBoxes()
    {
        if($this->use_viewKey == 'yes')
        {
            if($this->use_rpc == 'yes')
            {
                return true;
            }
        }
        else
            return false;
    }

    public function is_virtual_in_cart($order_id)
    {
        $order = wc_get_order( $order_id );
        $items = $order->get_items();

        foreach ( $items as $item ) {
            $product = new WC_Product( $item['product_id'] );
            if ( $product->is_virtual() ) {
                return true;
            }
        }

        return false;
    }

    public function instruction($order_id)
    {
        if($this->non_rpc)
        {
            echo "<noscript><h1>You must enable javascript in order to confirm your order</h1></noscript>";
            $order = wc_get_order($order_id);
            $amount = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
            $payment_id = $this->set_paymentid_cookie(32);
            $currency = $order->get_currency();
            //echo "Currency ".$currency;
            $amount_etn2 = $this->changeto($amount, $currency, $payment_id);
            $address = $this->address;

            $order->update_meta_data( "Payment ID", $payment_id);
            $order->update_meta_data( "Amount requested (ETN)", $amount_etn2);
            $order->save();

            if (!isset($address)) {
                // If there isn't address (merchant missed that field!), $address will be the Electroneum address for donating :)
                $address = "INVALID ADDRESS"; // Fill With Donations Address
            }
            $uri = "electroneum:$address?tx_payment_id=$payment_id";

            if($this->zero_confirm){
                $this->verify_zero_conf($payment_id, $amount_etn2, $order_id);
            }
            else{
                $this->verify_non_rpc($payment_id, $amount_etn2, $order_id);
            }
            if($this->confirmed == false)
            {
                echo "<h4><font color=DC143C> We are waiting for your transaction to be confirmed </font></h4>";
            }
            if($this->confirmed)
            {
                echo "<h4><font color=006400> Your transaction has been successfully confirmed! </font></h4>";
            }

            echo "
              <!-- page container  -->
              <div class='page-container'>
                <!-- electroneum container payment box -->
                <div class='container-etn-payment'>
                  <!-- header -->
                  <div class='header-etn-payment'>
                    <span class='logo-etn'><img src='https://my.electroneum.com/img/logo-light.png' width='150' /></span>
                    <span class='etn-payment-text-header'><h2>ELECTRONEUM PAYMENT</h2></span>
                  </div>
                  <!-- end header -->
                  <!-- ETN content box -->
                  <div class='table-responsive'>
                    <table class='table table-striped' style='table-layout: fixed;'>
                      <tbody>
                        <tr>
                          <td style='width:150px'>Send</td>
                          <td><strong>".$amount_etn2." ETN</strong></td>
                        </tr>
                        <tr>
                          <td>Payment ID</td>
                          <td><strong>".$payment_id."</strong></td>
                        </tr>
                        <tr>
                          <td>To this address:</td>
                          <td><strong>".$address."</strong></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class='clear'></div>
                  <div class='content-etn-payment'>
                    <div class='etn-qr-code'>
                      <span class='etn-label'>Or scan QR:</span>
                      <div class='etn-qr-code-box'><img src='https://api.qrserver.com/v1/create-qr-code/? size=200x200&data=".$uri."' /></div>
                    </div>
                  </div>
                  <div class='clear'></div>
                  <!-- end content box -->
                  <!-- footer etn payment -->
                  <div class='footer-etn-payment'>
                    <a href='https://electroneum.com' target='_blank'>Help</a> | <a href='https://electroneum.com' target='_blank'>About Electroneum</a>
                  </div>
                  <p>&nbsp;</p>
                  <!-- end footer etn payment -->
                </div>
                <!-- end electroneum container payment box -->
              </div>
              <!-- end page container  -->
                ";

                echo "
                <script type='text/javascript'>setTimeout(function () { location.reload(true); }, $this->reloadTime);</script>";
        }
        else
        {
            $order = wc_get_order($order_id);
            $amount = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
            $payment_id = $this->set_paymentid_cookie(8);
            $currency = $order->get_currency();
            $amount_etn2 = $this->changeto($amount, $currency, $payment_id);

            $order->update_meta_data( "Payment ID", $payment_id);
            $order->update_meta_data( "Amount requested (ETN)", $amount_etn2);
            $order->save();

            $uri = "electroneum:$address?tx_payment_id=$payment_id";
            $array_integrated_address = $this->electroneum_daemon->make_integrated_address($payment_id);
            if (!isset($array_integrated_address)) {
                $this->log->add('Electroneum_Gateway', '[ERROR] Unable get integrated address');
                // Seems that we can't connect with daemon, then set array_integrated_address, little hack
                $array_integrated_address["integrated_address"] = $address;
            }
            $message = $this->verify_payment($payment_id, $amount_etn2, $order);
            if ($this->confirmed) {
                $color = "006400";
            } else {
                $color = "DC143C";
            }
            echo "<h4><font color=$color>" . $message . "</font></h4>";

            echo "
              <!-- page container  -->
              <div class='page-container'>
                <!-- electroneum container payment box -->
                <div class='container-etn-payment'>
                  <!-- header -->
                  <div class='header-etn-payment'>
                    <span class='logo-etn'><img src='https://my.electroneum.com/img/logo-light.png' width='150' /></span>
                    <span class='etn-payment-text-header'><h2>ELECTRONEUM PAYMENT</h2></span>
                  </div>
                  <!-- end header -->
                  <!-- ETN content box -->
                  <div class='table-responsive'>
                    <table class='table table-striped' style='table-layout: fixed;'>
                      <tbody>
                        <tr>
                          <td style='width:150px;'>Send</td>
                          <td><strong>".$amount_etn2." ETN</strong></td>
                        </tr>
                        <tr>
                          <td>Payment ID</td>
                          <td><strong>".$payment_id."</strong></td>
                        </tr>
                        <tr>
                          <td>To this address:</td>
                          <td><strong>".$address."</strong></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class='clear'></div>
                  <div class='content-etn-payment'>
                    <div class='etn-qr-code'>
                      <span class='etn-label'>Or scan QR:</span>
                      <div class='etn-qr-code-box'><img src='https://api.qrserver.com/v1/create-qr-code/? size=200x200&data=".$uri."' /></div>
                    </div>
                  </div>
                  <div class='clear'></div>
                  <!-- end content box -->
                  <!-- footer etn payment -->
                  <div class='footer-etn-payment'>
                    <a href='https://electroneum.com' target='_blank'>Help</a> | <a href='https://electroneum.com' target='_blank'>About Electroneum</a>
                  </div>
                  <p>&nbsp;</p>
                  <!-- end footer etn payment -->
                </div>
                <!-- end electroneum container payment box -->
              </div>
              <!-- end page container  -->
            ";

            echo "
          <script type='text/javascript'>setTimeout(function () { location.reload(true); }, $this->reloadTime);</script>";
        }
    }

    private function set_paymentid_cookie($size)
    {
        if (!isset($_COOKIE['payment_id'])) {
            $payment_id = bin2hex(openssl_random_pseudo_bytes($size));
            setcookie('payment_id', $payment_id, time() + 2700);
        }
        else{
            $payment_id = $this->sanatize_id($_COOKIE['payment_id']);
        }
        return $payment_id;
    }

    public function sanatize_id($payment_id)
    {
        // Limit payment id to alphanumeric characters
        $sanatized_id = preg_replace("/[^a-zA-Z0-9]+/", "", $payment_id);
	return $sanatized_id;
    }

    public function changeto($amount, $currency, $payment_id)
    {
        global $wpdb;
        // This will create a table named whatever the payment id is inside the database "WordPress"
        $create_table = "CREATE TABLE IF NOT EXISTS $payment_id (
									rate INT
									)";
        $wpdb->query($create_table);
        $rows_num = $wpdb->get_results("SELECT count(*) as count FROM $payment_id");
        if ($rows_num[0]->count > 0) // Checks if the row has already been created or not
        {
            $stored_rate = $wpdb->get_results("SELECT rate FROM $payment_id");

            $stored_rate_transformed = $stored_rate[0]->rate / 100; //this will turn the stored rate back into a decimaled number

            if (isset($this->discount)) {
                $sanatized_discount = preg_replace('/[^0-9]/', '', $this->discount);
                $discount_decimal = $sanatized_discount / 100;
                $new_amount = $amount / $stored_rate_transformed;
                $discount = $new_amount * $discount_decimal;
                $final_amount = $new_amount - $discount;
                //echo $final_amount;
                //$new_amount = $new_amount + 0.02; // Transaction Fee
                $rounded_amount = round($final_amount, 2);//the electroneum wallet can't handle decimals smaller than 0.01
            } else {
                $new_amount = $amount / $stored_rate_transformed;
                //$new_amount = $new_amount + 0.02; // Transaction Fee
                $rounded_amount = round($new_amount, 2); //the electroneum wallet can't handle decimals smaller than 0.01
            }
        } else // If the row has not been created then the live exchange rate will be grabbed and stored
        {
            $etn_live_price = $this->retriveprice($currency);
            $live_for_storing = $etn_live_price * 100; //This will remove the decimal so that it can easily be stored as an integer
            //echo "Live price for ".$live_for_storing;

            $wpdb->query("INSERT INTO $payment_id (rate) VALUES ($live_for_storing)");
            if(isset($this->discount))
            {
               $new_amount = $amount / $etn_live_price;
               //$new_amount = $new_amount + 0.02; // Transaction Fee
               $discount = $new_amount * $this->discount / 100;
               $discounted_price = $new_amount - $discount;
               $rounded_amount = round($discounted_price, 2);
            }
            else
            {
               $new_amount = $amount / $etn_live_price;
               $rounded_amount = round($new_amount, 2);
            }
        }
        return $rounded_amount;
    }


    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway

    public function retriveprice($currency)
    {
        $etn_price = file_get_contents('https://min-api.cryptocompare.com/data/price?fsym=ETN&tsyms=BTC,USD,EUR,CAD,INR,GBP,COP,SGD&extraParams=electroneum_woocommerce');
        $price = json_decode($etn_price, TRUE);
        if (!isset($price)) {
            $this->log->add('Electroneum_Gateway', '[ERROR] Unable to get the price of Electroneum');
        }
        switch ($currency) {
            case 'USD':
                return $price['USD'];
            case 'EUR':
                return $price['EUR'];
            case 'CAD':
                return $price['CAD'];
            case 'GBP':
                return $price['GBP'];
            case 'INR':
                return $price['INR'];
            case 'COP':
                return $price['COP'];
            case 'SGD':
                return $price['SGD'];
            case 'ETN':
                return 1;
        }
    }

    private function on_verified($payment_id, $amount_atomic_units, $order_id)
    {
        $message = "Payment has been received and confirmed. Thanks!";
        $this->log->add('Electroneum_gateway', '[SUCCESS] Payment has been recorded. Congratulations!');
        $this->confirmed = true;
        $order = wc_get_order($order_id);

        if($this->is_virtual_in_cart($order_id) == true){
            $order->update_status('completed', __('Payment has been received.', 'electroneum_gateway'));
        }
        else{
            $order->update_status('processing', __('Payment has been received.', 'electroneum_gateway')); // Show payment id used for order
        }
        global $wpdb;
        $wpdb->query("DROP TABLE $payment_id"); // Drop the table from database after payment has been confirmed as it is no longer needed

        $this->reloadTime = 3000000000000; // Greatly increase the reload time as it is no longer needed
        return $message;
    }

    public function verify_payment($payment_id, $amount, $order_id)
    {
        /*
         * function for verifying payments
         * Check if a payment has been made with this payment id then notify the merchant
         */
        $message = "We are waiting for your payment to be confirmed";
        $amount_atomic_units = $amount * 100;
        $get_payments_method = $this->electroneum_daemon->get_payments($payment_id);
        if (isset($get_payments_method["payments"][0]["amount"])) {
            if ($get_payments_method["payments"][0]["amount"] >= $amount_atomic_units)
            {
                $message = $this->on_verified($payment_id, $amount_atomic_units, $order_id);
            }
            if ($get_payments_method["payments"][0]["amount"] < $amount_atomic_units)
            {
                $totalPayed = $get_payments_method["payments"][0]["amount"];
                $outputs_count = count($get_payments_method["payments"]); // number of outputs recieved with this payment id
                $output_counter = 1;

                while($output_counter < $outputs_count)
                {
                         $totalPayed += $get_payments_method["payments"][$output_counter]["amount"];
                         $output_counter++;
                }
                if($totalPayed >= $amount_atomic_units)
                {
                    $message = $this->on_verified($payment_id, $amount_atomic_units, $order_id);
                }
            }
        }
        return $message;
    }
    public function last_block_seen($height) // sometimes 2 blocks are mined within a few seconds of eacher. Make sure we don't miss one
    {
        if (!isset($_COOKIE['last_seen_block']))
        {
            setcookie('last_seen_block', $height, time() + 2700);
            return 0;
        }
        else{
            $cookie_block = $_COOKIE['last_seen_block'];
            $difference = $height - $cookie_block;
            setcookie('last_seen_block', $height, time() + 2700);
            return $difference;
        }
    }
    public function verify_non_rpc($payment_id, $amount, $order_id)
    {
        $tools = new NodeTools();
        $bc_height = $tools->get_last_block_height();

        $block_difference = $this->last_block_seen($bc_height);

        $txs_from_block = $tools->get_txs_from_block($bc_height);
        $tx_count = count($txs_from_block) - 1; // The tx at index 0 is a coinbase tx so it can be ignored

        $output_found;
        $block_index;

        if($block_difference != 0)
        {
            if($block_difference >= 2){
                $this->log->add('error','[WARNING] Block difference is greater or equal to 2');
            }

            $txs_from_block_2 = $tools->get_txs_from_block($bc_height - 1);
            $tx_count_2 = count($txs_from_block_2) - 1;

            $i = 1;
            while($i <= $tx_count_2)
            {
                $tx_hash = $txs_from_block_2[$i]['tx_hash'];
                if(strlen($txs_from_block_2[$i]['payment_id']) != 0)
                {
                    $result = $tools->check_tx($tx_hash, $this->address, $this->viewKey);
                    if($result)
                    {
                        $output_found = $result;
                        $block_index = $i;
                        $i = $tx_count_2; // finish loop
                    }
                }
                $i++;
            }
        }

        $i = 1;
        while($i <= $tx_count)
        {
            $tx_hash = $txs_from_block[$i]['tx_hash'];
            if(strlen($txs_from_block[$i]['payment_id']) != 0)
            {
                $result = $tools->check_tx($tx_hash, $this->address, $this->viewKey);
                if($result)
                {
                    $output_found = $result;
                    $block_index = $i;
                    $i = $tx_count; // finish loop
                }
            }
            $i++;
        }

        if(isset($output_found))
        {
            $amount_atomic_units = $amount * 100;
            $final_transaction_value = 0;
            foreach($output_found as $single_transaction_amount) {
              if($single_transaction_amount['match']==true)
                $final_transaction_value += $single_transaction_amount["amount"];
            }

            if($txs_from_block[$block_index]['payment_id'] == $payment_id && $final_transaction_value >= $amount_atomic_units)
            {
                $this->on_verified($payment_id, $amount_atomic_units, $order_id);
            }
            if($txs_from_block_2[$block_index]['payment_id'] == $payment_id && $output_found['amount'] >= $amount_atomic_units)
            {
                $this->on_verified($payment_id, $amount_atomic_units, $order_id);
            }

            return true;
        }
            return false;
    }

    public function verify_zero_conf($payment_id, $amount, $order_id)
    {
        $tools = new NodeTools();
        $txs_from_mempool = $tools->get_mempool_txs();
        $tx_count = count($txs_from_mempool['data']['txs']);
        $i = 0;
        $output_found;

        while($i <= $tx_count)
        {
            $tx_hash = $txs_from_mempool['data']['txs'][$i]['tx_hash'];
            if(strlen($txs_from_mempool['data']['txs'][$i]['payment_id']) != 0)
            {
                $result = $tools->check_tx($tx_hash, $this->address, $this->viewKey);
                if($result)
                {
                    $output_found = $result;
                    $tx_i = $i;
                    $i = $tx_count; // finish loop
                }
            }
            $i++;
        }
        if(isset($output_found))
        {
            $amount_atomic_units = $amount * 100;
            $final_transaction_value = 0;
            foreach($output_found as $single_transaction_amount) {
              if($single_transaction_amount['match']==true)
                $final_transaction_value += $single_transaction_amount["amount"];
            }

            if($txs_from_mempool['data']['txs'][$tx_i]['payment_id'] == $payment_id && $final_transaction_value >= $amount_atomic_units)
            {
                $this->on_verified($payment_id, $amount_atomic_units, $order_id);
            }
            return true;
        }
        else
            return false;
    }

    public function do_ssl_check()
    {
        if ($this->enabled == "yes" && !$this->get_option('onion_service')) {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    public function connect_daemon()
    {
        $host = $this->settings['daemon_host'];
        $port = $this->settings['daemon_port'];
        $electroneum_library = new Electroneum($host, $port);
        if ($electroneum_library->works() == true) {
            echo "<div class=\"notice notice-success is-dismissible\"><p>Everything works! Congratulations and welcome to Electroneum. <button type=\"button\" class=\"notice-dismiss\">
						<span class=\"screen-reader-text\">Dismiss this notice.</span>
						</button></p></div>";

        } else {
            $this->log->add('Electroneum_gateway', '[ERROR] Plugin can not reach wallet rpc.');
            echo "<div class=\" notice notice-error\"><p>Error with connection of daemon, see documentation!</p></div>";
        }
    }
}
