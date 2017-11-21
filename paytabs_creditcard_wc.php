<?php
/**
 * Plugin Name: WooCommerce Credit Card Payment Gateway - PayTabs
 * Plugin URI: http://www.paytabs.com/plugin
 * Description: PayTabs Credit Card Payment gateway for woocommerce. This plugin supports woocommerce version 3.0.0 or greater version.
 * Version: 2.2
 * Author: PayTabs
 * Author URL: http://www.pgit aytabs.com
 * Text Domain: PayTabs
 * Domain Path: /i18n/languages/.
 **/

// init session if not start yet.
@\session_start();

//load plugin functions when WooCommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_creditcard_wc_init', 0);

/**
 * PayTab plugin function.
 */
function woocommerce_paytabs_creditcard_wc_init()
{
    if (!\class_exists('WC_Payment_Gateway')) {
        return;
    }

    load_plugin_textdomain('PayTabs', false, plugin_basename(__DIR__) . '/i18n/languages');

    /**
     * WooCommerce Payment Gateway class.
     *
     * Extended by individual payment gateways to handle payments.
     *
     * @class       WC_Payment_Gateway
     * @extends     WC_Settings_API
     *
     * @version     2.1.0
     *
     * @category    Abstract Class
     *
     * @author      WooThemes
     */
    class WC_Gateway_Paytabs_creditcard_wc extends WC_Payment_Gateway
    {
        protected $send_shipping = 'no';

        public function __construct()
        {
            $pluginpath = WC()->plugin_url();
            $pluginpath = \explode('plugins', $pluginpath);
            $this->id = 'paytabs';
            $this->icon = apply_filters('woocommerce_paytabs_icon', $pluginpath[0] . 'plugins/paytabs-payment-gateway-woocommerce/icons/paytabs.png');
            $this->medthod_title = 'PayTabs';
            $this->has_fields = false;
            $this->init_form_fields();
            $this->init_settings();

            //fetch data from admin setting
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->website = $this->settings['website'];
            $this->merchant_id = $this->settings['merchant_id'];
            $this->password = $this->settings['password'];
            $this->redirect_page_id = $this->settings['redirect_page_id'] ?? 0;
            //live payment url
            $this->liveurl = 'https://www.paytabs.com/';
            $this->form_submission_method = $this->get_option('form_submission_method') == 'yes' ? true : false;
            $this->msg['message'] = '';
            $this->msg['class'] = '';
            $this->msg['class'] = '';

            //call initially check_paymnet_response funtion when checkout process call and before payment process
            // add_action('init', array(&$this, 'check_paytabs_response'));

            //when payment done and redirected with payment reference code
            if (isset($_REQUEST['payment_reference'])) {
                $order = new WC_Order($order_id);
                $this->complete_transaction($order->get_id());
            }

            if (\version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [&$this, 'process_admin_options']);
            } else {
                add_action('woocommerce_update_options_payment_gateways', [&$this, 'process_admin_options']);
            }
        }

        /**
         * {@inheritdoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => __('Enable/Disable', 'PayTabs'),
                    'type' => 'checkbox',
                    'label' => __('Enable PayTabs Payment Gateway .', 'PayTabs'),
                    'default' => 'no',],
                'title' => [
                    'title' => __('Title:', 'PayTabs'),
                    'type' => 'text',
                    'description' => __('Making any changes to the above may result in suspension or termination of your PayTabs Merchant Account.', 'PayTabs'),
                    'default' => __('PayTabs', 'PayTabs'),],
                'description' => [
                    'title' => __('Description:', 'PayTabs'),
                    'type' => 'textarea',
                    'description' => __('Making any changes to the above may result in suspension or termination of your PayTabs Merchant Account.', 'PayTabs'),
                    'default' => __('Pay securely by Credit or Debit card or internet banking through PayTabs Secure Servers.', 'PayTabs'),],
                'merchant_id' => [
                    'title' => __('Email', 'PayTabs'),
                    'type' => 'text',
                    'value' => '',
                    'description' => __('Please enter the email id of your PayTabs merchant account.', 'PayTabs'),
                    'default' => '',
                    'required' => true,],

                'password' => [
                    'title' => __('Secret Key', 'PayTabs'),
                    'type' => 'text',
                    'value' => '',
                    'size' => '120',
                    'description' => __('Please enter your PayTabs Secret Key. You can find the secret key on your Merchant’s Dashboard >> PayTabs Services >> ecommerce Plugins and API.', 'PayTabs'),
                    'default' => '',
                    'required' => true,],
                'website' => [

                    'title' => __('WebSite', 'PayTabs'),
                    'type' => 'text',
                    'value' => '',
                    'description' => __('Please enter your Website; Your SITE URL MUST match to the website URL you provided in your PayTabs merchant account (Case Sensitive). For Demo Users: You can edit your site URL by going to “My Profile” and clicking on edit, enter your correct site URL and click on Save. For Live Merchants: You can use the website that you have submitted in the Go-Live application. If you need to edit/change the site URL, you can send a request to ', 'PayTabs'),
                    'default' => '',
                    'required' => true,
                ],
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function admin_options()
        {
            echo '<h3>' . __('PayTabs', 'PayTabs') . '</h3>';
            echo ' <script type="text/javascript">
                jQuery("#mainform").submit(function(){
                  var marchantid=jQuery("#woocommerce_PayTabs_merchant_id").val();
                  var marchantpass=jQuery("#woocommerce_PayTabs_password").val();
                  var err_flag=0;
                  var errormsg="Required fields \t\n";          
                  if(marchantid==""){
                            errormsg+="\tPlease enter merchant id";
                  err_flag=1;
                          }
                          if(marchantpass==""){
                            errormsg+="\t\nPlease enter PayTabs Secret Key.";
                            err_flag=1;
                          } 
                  if(err_flag==1){                  
                      alert(errormsg) ;
                    return false;
                  }
                  else{
                  return true;
                  } 
                }); 
             </script>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        /**
         *  There are no payment fields for paytabs, but we want to show the description if set.
         **/
        public function payment_fields()
        {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
        }

        /**
         * Get Paytabs Args for passing to PP.
         *
         *
         * @param mixed $order
         *
         * @return array
         */
        public function get_paytabs_args($order)
        {
            $order_id = $order->get_id();
            $txnid = $order_id . '_' . \date('ymds');
            $redirect = $order->get_checkout_payment_url(true);
            //array values for authentication

            $_SESSION['secret_key'] = $this->password;
            $_SESSION['merchant_id'] = $this->merchant_id;

            // PayTabs Args
            $paytabs_args = [
                'txnid' => $txnid,
                'merchant_email' => $this->merchant_id,
                'secret_key' => $this->password,
                'productinfo' => isset($productinfo) ? $productinfo : '',
                'firstname' => $order->get_billing_first_name(),
                'lastname' => $order->get_billing_last_name(),
                'address1' => $order->get_billing_address_1(),
                'address2' => $order->get_billing_address_2(),
                'zipcode' => $order->get_billing_postcode(),
                'cc_phone_number' => $this->getccPhone($order->get_billing_country()),
                'phone' => $order->get_billing_phone(),
                'cc_first_name' => $order->get_billing_first_name(),
                'cc_last_name' => $order->get_billing_last_name(),
                'phone_number' => $order->get_billing_phone(),
                'billing_address' => $order->get_billing_address_1(),
                'state' => $order->get_billing_state(),
                'city' => $order->get_billing_city(),
                'postal_code' => $order->get_billing_postcode(),
                'postal_code_shipping' => $order->get_billing_postcode(),
                'country' => $this->getCountryIsoCode($order->get_billing_country()),
                'email' => $order->get_billing_email(),
                'amount' => $order->get_total() + $order->get_total_discount(),
                'discount' => $order->get_total_discount(),
                'reference_no' => $txnid,
                'currency' => \strtoupper(get_woocommerce_currency()),
                'title' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'ip_customer' => $_SERVER['REMOTE_ADDR'],
                'ip_merchant' => (\getenv('REMOTE_ADDR') ? \getenv('REMOTE_ADDR') : $_SERVER['SERVER_ADDR']),
                'return_url' => $redirect,
                'cms_with_version' => ' WooCommerce  :' . WOOCOMMERCE_VERSION,
                'reference_no' => $txnid,
                'site_url' => $this->website,
                'CustomerId' => $txnid,
                'msg_lang' => is_rtl() ? 'Arabic' : 'English',
            ];

            // Shipping
            if ('yes' == $this->send_shipping) {
                $paytabs_args['address_shipping'] = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
                $paytabs_args['city_shipping'] = $order->get_billing_city();
                $paytabs_args['state_shipping'] = $order->get_billing_state();
                $paytabs_args['country_shipping'] = $this->getCountryIsoCode($order->get_billing_country());
                $paytabs_args['postal_code_shipping'] = $order->get_billing_postcode();
            } else {
                $paytabs_args['address_shipping'] = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
                $paytabs_args['city_shipping'] = $order->get_billing_city();
                $paytabs_args['state_shipping'] = $order->get_billing_state();
                $paytabs_args['country_shipping'] = $this->getCountryIsoCode($order->get_billing_country());
                $paytabs_args['postal_code_shipping'] = $order->get_billing_postcode();
                //$paytabs_args['discount']             = $order->get_order_discount();
            }
            $paytabs_args['products_per_title'] = '';
            $paytabs_args['ProductName'] = '';
            $paytabs_args['unit_price'] = '';
            $paytabs_args['quantity'] = '';
            $paytabs_args['other_charges'] = '';
            $paytabs_args['ProductCategory'] = '';

            if ($order->get_billing_postcode() == '') {
                $paytabs_args['postal_code'] = \substr($order->get_billing_phone(), 0, 5);
                $paytabs_args['postal_code_shipping'] = \substr($order->get_billing_phone(), 0, 5);
            }
            // Cart Contents
            $item_loop = 0;
            $total_product_value = 0;
            foreach ($order->get_items() as $item) {
                if ($item['qty']) {
                    ++$item_loop;
                    $product = $order->get_product_from_item($item);
                    $item_name = $item['name'];
                    $meta = wc_display_item_meta($item, array(
                        'before' => '',
                        'after' => '',
                        'separator' => '',
                        'echo' => false,
                        'autop' => true,
                    ));

                    if (!empty($meta)) {
                        $item_name .= ' ( ' . strip_tags($meta) . ' )';
                    }

                    //product description
                    if ($paytabs_args['products_per_title'] != '') {
                        $paytabs_args['products_per_title'] = $paytabs_args['products_per_title'] . ' || ' . $item_name;
                    } else {
                        $paytabs_args['products_per_title'] = $item_name;
                    }
                    //product description
                    if ($paytabs_args['ProductName'] != '') {
                        $paytabs_args['ProductName'] = $paytabs_args['ProductName'] . ' || ' . $item_name;
                    } else {
                        $paytabs_args['ProductName'] = $item_name;
                    }
                    //product quantity
                    if ($paytabs_args['quantity'] != '') {
                        $paytabs_args['quantity'] = $paytabs_args['quantity'] . ' || ' . $item['qty'];
                    } else {
                        $paytabs_args['quantity'] = $item['qty'];
                    }
                    //product  unit price
                    if ($paytabs_args['unit_price'] != '') {
                        $paytabs_args['unit_price'] = $paytabs_args['unit_price'] . ' || ' . $order->get_item_subtotal($item, false);
                    } else {
                        $paytabs_args['unit_price'] = $order->get_item_subtotal($item, false);
                    }
                    $total_product_value = $total_product_value + $item['qty'] * $order->get_item_subtotal($item, false);
                    // if($paytabs_args['other_charges']!=''){
                    //   echo "here 2  ".$order->order_total-($order->get_item_subtotal( $item, false ) * $item['qty'])+$order->get_total_discount();
                    //       $paytabs_args['other_charges']   = $order->order_total-($order->get_item_subtotal( $item, false ) * $item['qty'])+$order->get_total_discount();

                    // }else{
                    //   echo "here 1  ".$order->order_total-($order->get_item_subtotal( $item, false ) * $item['qty'])+$order->get_total_discount();
                    //       $paytabs_args['other_charges']= $order->order_total-($order->get_item_subtotal( $item, false ) * $item['qty'])+$order->get_total_discount();
                    // }
                    //product category name
                    if ($paytabs_args['ProductCategory'] != '') {
                        $paytabs_args['ProductCategory'] = $paytabs_args['ProductCategory'] . '||' . $item['type'];
                    } else {
                        $paytabs_args['ProductCategory'] = $item['type'];
                    }
                }
            }
            $total = $order->get_total() - $total_product_value + $order->get_total_discount();
            $paytabs_args['other_charges'] = $total;

            $paytabs_args['ShippingMethod'] = $order->get_shipping_method();
            $paytabs_args['DeliveryType'] = $order->get_shipping_method();
            $paytabs_args['CustomerID'] = get_current_user_id();
            $paytabs_args['channelOfOperations'] = 'channelOfOperations';
            //$paytabs_args['amount'] = $paytabs_args['amount']+$paytabs_args['discount'];

            // print_r( $paytabs_args);
            $paytabs_args = apply_filters('woocommerce_paytabs_args', $paytabs_args);

            $pay_url = $this->before_process($paytabs_args);

            return $pay_url;
        }

        /**
         * Process the payment and return the result.
         *
         * @param mixed $order_id
         *
         * @return array
         */
        public function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            $_SESSION['order_id'] = $order_id;
            if (!$this->form_submission_method) {
                $paytabs_payment_url = $this->get_paytabs_args($order);
                $paytabs_adr = $paytabs_payment_url->payment_url;

                //check if api is wrong or dont get payment url
                if ($paytabs_adr == '') {
                    $this->msg['class'] = 'woocommerce_message';
                    // Change the status to pending / unpaid
                    $order->update_status('failed', __('Payment failed', 'woocommerce'));
                    // Add error for the customer when we return back to the cart
                    $message = $paytabs_payment_url->result;
                    wc_add_notice(__($message, 'error'), 'error');

                    return [
                        'result' => 'success',
                        'redirect' => $order->get_checkout_payment_url(true),
                    ];
                }

                return [
                    'result' => 'success',
                    'redirect' => $paytabs_adr,
                ];
            }

            wc_add_notice('<strong>Error:</strong> ' . __('Transaction declined .', 'woocommerce'), 'error');

            //  wc_add_notice( 'Transaction declined', 'error' );

            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            ];
        }

        /**
         * Check process for form submittion.
         *
         * @param mixed $array
         *
         * @return array|mixed|object
         */
        public function before_process($array)
        {
            $gateway_url = $this->liveurl;
            $request_string = \http_build_query($array);
            $response_data = $this->sendRequest($gateway_url . 'apiv2/create_pay_page', $request_string);

            $object = \json_decode($response_data);

            return $object;
        }

        /**
         * Get response throgh 3 rd party.
         *
         * @param mixed $gateway_url
         * @param mixed $request_string
         *
         * @return mixed
         */
        public function sendRequest($gateway_url, $request_string)
        {
            $ch = @\curl_init();
            @\curl_setopt($ch, CURLOPT_URL, $gateway_url);
            @\curl_setopt($ch, CURLOPT_POST, true);
            @\curl_setopt($ch, CURLOPT_POSTFIELDS, $request_string);
            @\curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @\curl_setopt($ch, CURLOPT_HEADER, false);
            @\curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            @\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            @\curl_setopt($ch, CURLOPT_VERBOSE, true);
            $result = @\curl_exec($ch);
            if (!$result) {
                die(\curl_error($ch));
            }

            @\curl_close($ch);

            return $result;
        }

        //show message when success or not success or payment status
        public function showMessage($content)
        {
            return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
        }

        public function get_cancel_endpoint()
        {
            $cancel_endpoint = wc_get_page_permalink('cart');
            if (!$cancel_endpoint) {
                $cancel_endpoint = home_url();
            }

            if (false === \strpos($cancel_endpoint, '?')) {
                $cancel_endpoint = trailingslashit($cancel_endpoint);
            }

            return $cancel_endpoint;
        }

        /**
         * Get cancel order url
         *
         * @param WC_Order $order
         * @param string $redirect
         *
         * @return string
         */
        public function get_cancel_order_url($order, $redirect = '')
        {
            return apply_filters('woocommerce_get_cancel_order_url_raw', add_query_arg(array(
                'cancel_order' => 'true',
                'order' => $this->id,
                'order_id' => $order->get_id(),
                'redirect' => $redirect,
            ), $this->get_cancel_endpoint()));
        }

        /**
         * When transaction completed it is check the status is transaction completed or rejected
         */
        public function complete_transaction()
        {
            $order = new WC_Order($_SESSION['order_id']);

            $request_string = [
                'secret_key' => $_SESSION['secret_key'],
                'merchant_email' => $_SESSION['merchant_id'],
                'payment_reference' => $_REQUEST['payment_reference'],
                'msg_lang' => is_rtl() ? 'Arabic' : 'English',
            ];
            $gateway_url = $this->liveurl . 'apiv2/verify_payment';
            $getdataresponse = $this->sendRequest($gateway_url, $request_string);
            $object = \json_decode($getdataresponse);

            if (isset($object->response_code)) {
                //if get response successfull
                if ($object->response_code == '100') {
                    //  thankyou and set error message
                    $this->msg['class'] = 'woocommerce_message';
                    // process payment complete.
                    $order->payment_complete();
                    // Remove cart
                    wc()->cart->empty_cart();
                    wc_add_notice(__('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'PayTabs'), 'success');
                    // Redirect back to view order
                    $redirect = $this->get_return_url($order);
                } else {
                    // Change the status to pending / unpaid
                    $order->update_status('failed', __('Payment Cancelled', 'error'));
                    // Add error for the customer when we return back to the cart
                    wc_add_notice('<strong></strong> ' . __($object->result, 'error'), 'error');
                    // Redirect back to the last step in the checkout process
                    $redirect = $this->get_cancel_order_url($order, wc_get_cart_url());
                }

                wp_redirect($redirect);
                exit;
            }
        }

        /**
         * get phone code base of country code.
         *
         * @param $code
         *
         * @return mixed
         */
        public function getccPhone($code)
        {
            $countries = [
                'AF' => '+93', //array("AFGHANISTAN", "AF", "AFG", "004"),
                'AL' => '+355', //array("ALBANIA", "AL", "ALB", "008"),
                'DZ' => '+213', //array("ALGERIA", "DZ", "DZA", "012"),
                'AS' => '+376', //array("AMERICAN SAMOA", "AS", "ASM", "016"),
                'AD' => '+376', //array("ANDORRA", "AD", "AND", "020"),
                'AO' => '+244', //array("ANGOLA", "AO", "AGO", "024"),
                'AG' => '+1-268', //array("ANTIGUA AND BARBUDA", "AG", "ATG", "028"),
                'AR' => '+54', //array("ARGENTINA", "AR", "ARG", "032"),
                'AM' => '+374', //array("ARMENIA", "AM", "ARM", "051"),
                'AU' => '+61', //array("AUSTRALIA", "AU", "AUS", "036"),
                'AT' => '+43', //array("AUSTRIA", "AT", "AUT", "040"),
                'AZ' => '+994', //array("AZERBAIJAN", "AZ", "AZE", "031"),
                'BS' => '+1-242', //array("BAHAMAS", "BS", "BHS", "044"),
                'BH' => '+973', //array("BAHRAIN", "BH", "BHR", "048"),
                'BD' => '+880', //array("BANGLADESH", "BD", "BGD", "050"),
                'BB' => '1-246', //array("BARBADOS", "BB", "BRB", "052"),
                'BY' => '+375', //array("BELARUS", "BY", "BLR", "112"),
                'BE' => '+32', //array("BELGIUM", "BE", "BEL", "056"),
                'BZ' => '+501', //array("BELIZE", "BZ", "BLZ", "084"),
                'BJ' => '+229', // array("BENIN", "BJ", "BEN", "204"),
                'BT' => '+975', //array("BHUTAN", "BT", "BTN", "064"),
                'BO' => '+591', //array("BOLIVIA", "BO", "BOL", "068"),
                'BA' => '+387', //array("BOSNIA AND HERZEGOVINA", "BA", "BIH", "070"),
                'BW' => '+267', //array("BOTSWANA", "BW", "BWA", "072"),
                'BR' => '+55', //array("BRAZIL", "BR", "BRA", "076"),
                'BN' => '+673', //array("BRUNEI DARUSSALAM", "BN", "BRN", "096"),
                'BG' => '+359', //array("BULGARIA", "BG", "BGR", "100"),
                'BF' => '+226', //array("BURKINA FASO", "BF", "BFA", "854"),
                'BI' => '+257', //array("BURUNDI", "BI", "BDI", "108"),
                'KH' => '+855', //array("CAMBODIA", "KH", "KHM", "116"),
                'CA' => '+1', //array("CANADA", "CA", "CAN", "124"),
                'CV' => '+238', //array("CAPE VERDE", "CV", "CPV", "132"),
                'CF' => '+236', //array("CENTRAL AFRICAN REPUBLIC", "CF", "CAF", "140"),
                'CM' => '+237', //array("CENTRAL AFRICAN REPUBLIC", "CF", "CAF", "140"),
                'TD' => '+235', //array("CHAD", "TD", "TCD", "148"),
                'CL' => '+56', //array("CHILE", "CL", "CHL", "152"),
                'CN' => '+86', //array("CHINA", "CN", "CHN", "156"),
                'CO' => '+57', //array("COLOMBIA", "CO", "COL", "170"),
                'KM' => '+269', //array("COMOROS", "KM", "COM", "174"),
                'CG' => '+242', //array("CONGO", "CG", "COG", "178"),
                'CR' => '+506', //array("COSTA RICA", "CR", "CRI", "188"),
                'CI' => '+225', //array("COTE D'IVOIRE", "CI", "CIV", "384"),
                'HR' => '+385', //array("CROATIA (local name: Hrvatska)", "HR", "HRV", "191"),
                'CU' => '+53', //array("CUBA", "CU", "CUB", "192"),
                'CY' => '+357', //array("CYPRUS", "CY", "CYP", "196"),
                'CZ' => '+420', //array("CZECH REPUBLIC", "CZ", "CZE", "203"),
                'DK' => '+45', //array("DENMARK", "DK", "DNK", "208"),
                'DJ' => '+253', //array("DJIBOUTI", "DJ", "DJI", "262"),
                'DM' => '+1-767', //array("DOMINICA", "DM", "DMA", "212"),
                'DO' => '+1-809', //array("DOMINICAN REPUBLIC", "DO", "DOM", "214"),
                'EC' => '+593', //array("ECUADOR", "EC", "ECU", "218"),
                'EG' => '+20', //array("EGYPT", "EG", "EGY", "818"),
                'SV' => '+503', //array("EL SALVADOR", "SV", "SLV", "222"),
                'GQ' => '+240', //array("EQUATORIAL GUINEA", "GQ", "GNQ", "226"),
                'RS' => '+381', //array("SERBIA", "RS", "SRB", "688"),
                'ME' => '+382', //array("MONTENERGO","ME","MNE","382"),
                'CD' => '+243', //array("CONGO", "CD", "COD", "243"),
                'TF' => '+262', //array("FRENCH SOUTHERN TERRITORIES", "TF", "ATF", "260"),
                'VG' => '+1', //array("VIRGIN ISLANDS (BRITISH)", "VG", "VGB", "92"),
                'ER' => '+291', //array("ERITREA", "ER", "ERI", "232"),
                'EE' => '+372', //array("ESTONIA", "EE", "EST", "233"),
                'ET' => '+251', //array("ETHIOPIA", "ET", "ETH", "210"),
                'FJ' => '+679', //array("FIJI", "FJ", "FJI", "242"),
                'FI' => '+358', //array("FINLAND", "FI", "FIN", "246"),
                'FR' => '+33', //array("FRANCE", "FR", "FRA", "250"),
                'GA' => '+241', //array("GABON", "GA", "GAB", "266"),
                'GM' => '+220', //array("GAMBIA", "GM", "GMB", "270"),
                'GE' => '+995', //array("GEORGIA", "GE", "GEO", "268"),
                'DE' => '+49', //array("GERMANY", "DE", "DEU", "276"),
                'GH' => '+233', //array("GHANA", "GH", "GHA", "288"),
                'GR' => '+30', //array("GREECE", "GR", "GRC", "300"),
                'GD' => '+1-473', //array("GRENADA", "GD", "GRD", "308"),
                'GT' => '+502', //array("GUATEMALA", "GT", "GTM", "320"),
                'GN' => '+224', //array("GUINEA", "GN", "GIN", "324"),
                'GW' => '+245', //array("GUINEA-BISSAU", "GW", "GNB", "624"),
                'GY' => '+592', //array("GUYANA", "GY", "GUY", "328"),
                'HT' => '+509', //array("HAITI", "HT", "HTI", "332"),
                'HN' => '+504', //array("HONDURAS", "HN", "HND", "340"),
                'HK' => '+852', //array("HONG KONG", "HK", "HKG", "344"),
                'HU' => '+36', //array("HUNGARY", "HU", "HUN", "348"),
                'IS' => '+354', //array("ICELAND", "IS", "ISL", "352"),
                'IN' => '+91', //array("INDIA", "IN", "IND", "356"),
                'ID' => '+62', //array("INDONESIA", "ID", "IDN", "360"),
                'IR' => '+98', //array("IRAN, ISLAMIC REPUBLIC OF", "IR", "IRN", "364"),
                'IQ' => '+964', //array("IRAQ", "IQ", "IRQ", "368"),
                'IE' => '+353', //array("IRELAND", "IE", "IRL", "372"),
                'IL' => '+972', //array("ISRAEL", "IL", "ISR", "376"),
                'IT' => '+39', //array("ITALY", "IT", "ITA", "380"),
                'JM' => '+1-876', //array("JAMAICA", "JM", "JAM", "388"),
                'JP' => '+81', //array("JAPAN", "JP", "JPN", "392"),
                'JO' => '+962', //array("JORDAN", "JO", "JOR", "400"),
                'KZ' => '+7', //array("KAZAKHSTAN", "KZ", "KAZ", "398"),
                'KE' => '+254', //array("KENYA", "KE", "KEN", "404"),
                'KI' => '+686', //array("KIRIBATI", "KI", "KIR", "296"),
                'KP' => '+850', //array("KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF", "KP", "PRK", "408"),
                'KR' => '+82', //array("KOREA, REPUBLIC OF", "KR", "KOR", "410"),
                'KW' => '+965', //array("KUWAIT", "KW", "KWT", "414"),
                'KG' => '+996', //array("KYRGYZSTAN", "KG", "KGZ", "417"),
                'LA' => '+856', //array("LAO PEOPLE'S DEMOCRATIC REPUBLIC", "LA", "LAO", "418"),
                'LV' => '+371', //array("LATVIA", "LV", "LVA", "428"),
                'LB' => '+961', //array("LEBANON", "LB", "LBN", "422"),
                'LS' => '+266', //array("LESOTHO", "LS", "LSO", "426"),
                'LR' => '+231', //array("LIBERIA", "LR", "LBR", "430"),
                'MO' => '+231', //array("LIBERIA", "LR", "LBR", "430"),

                'LY' => '+218', //array("LIBYAN ARAB JAMAHIRIYA", "LY", "LBY", "434"),
                'LI' => '+423', //array("LIECHTENSTEIN", "LI", "LIE", "438"),
                'LU' => '+352', //array("LUXEMBOURG", "LU", "LUX", "442"),
                'MO' => '+389', //array("MACAU", "MO", "MAC", "446"),
                'MG' => '+261', //array("MADAGASCAR", "MG", "MDG", "450"),
                'MW' => '+265', //array("MALAWI", "MW", "MWI", "454"),
                'MY' => '+60', //array("MALAYSIA", "MY", "MYS", "458"),
                'MX' => '+52', //array("MEXICO", "MX", "MEX", "484"),
                'MC' => '+377', //array("MONACO", "MC", "MCO", "492"),
                'MA' => '+212', //array("MOROCCO", "MA", "MAR", "504")
                'NP' => '+977', //array("NEPAL", "NP", "NPL", "524"),
                'NL' => '+31', //array("NETHERLANDS", "NL", "NLD", "528"),
                'NZ' => '+64', //array("NEW ZEALAND", "NZ", "NZL", "554"),
                'NI' => '+505', //array("NICARAGUA", "NI", "NIC", "558"),
                'NE' => '+227', //array("NIGER", "NE", "NER", "562"),
                'NG' => '+234', //array("NIGERIA", "NG", "NGA", "566"),
                'NO' => '+47', //array("NORWAY", "NO", "NOR", "578"),
                'OM' => '+968', //array("OMAN", "OM", "OMN", "512"),
                'PK' => '+92', //array("PAKISTAN", "PK", "PAK", "586"),
                'PA' => '+507', //array("PANAMA", "PA", "PAN", "591"),
                'PG' => '+675', //array("PAPUA NEW GUINEA", "PG", "PNG", "598"),
                'PY' => '+595', // array("PARAGUAY", "PY", "PRY", "600"),
                'PE' => '+51', // array("PERU", "PE", "PER", "604"),
                'PH' => '+63', // array("PHILIPPINES", "PH", "PHL", "608"),
                'PL' => '48', //array("POLAND", "PL", "POL", "616"),
                'PT' => '+351', //array("PORTUGAL", "PT", "PRT", "620"),
                'QA' => '+974', //array("QATAR", "QA", "QAT", "634"),
                'RU' => '+7', //array("RUSSIAN FEDERATION", "RU", "RUS", "643"),
                'RW' => '+250', //array("RWANDA", "RW", "RWA", "646"),
                'SA' => '+966', //array("SAUDI ARABIA", "SA", "SAU", "682"),
                'SN' => '+221', //array("SENEGAL", "SN", "SEN", "686"),
                'SG' => '+65', //array("SINGAPORE", "SG", "SGP", "702"),
                'SK' => '+421', //array("SLOVAKIA (Slovak Republic)", "SK", "SVK", "703"),
                'SI' => '+386', //array("SLOVENIA", "SI", "SVN", "705"),
                'ZA' => '+27', //array("SOUTH AFRICA", "ZA", "ZAF", "710"),
                'ES' => '+34', //array("SPAIN", "ES", "ESP", "724"),
                'LK' => '+94', //array("SRI LANKA", "LK", "LKA", "144"),
                'SD' => '+249', //array("SUDAN", "SD", "SDN", "736"),
                'SZ' => '+268', //array("SWAZILAND", "SZ", "SWZ", "748"),
                'SE' => '+46', //array("SWEDEN", "SE", "SWE", "752"),
                'CH' => '+41', //array("SWITZERLAND", "CH", "CHE", "756"),
                'SY' => '+963', //array("SYRIAN ARAB REPUBLIC", "SY", "SYR", "760"),
                'TZ' => '+255', //array("TANZANIA, UNITED REPUBLIC OF", "TZ", "TZA", "834"),
                'TH' => '+66', //array("THAILAND", "TH", "THA", "764"),
                'TG' => '+228', //array("TOGO", "TG", "TGO", "768"),
                'TO' => '+676', //array("TONGA", "TO", "TON", "776"),
                'TN' => '+216', //array("TUNISIA", "TN", "TUN", "788"),
                'TR' => '+90', //array("TURKEY", "TR", "TUR", "792"),
                'TM' => '+993', //array("TURKMENISTAN", "TM", "TKM", "795"),
                'UA' => '+380', //array("UKRAINE", "UA", "UKR", "804"),
                'AE' => '+971', //array("UNITED ARAB EMIRATES", "AE", "ARE", "784"),
                'GB' => '+44', //array("UNITED KINGDOM", "GB", "GBR", "826"),
                'US' => '+1', //array("UNITED STATES", "US", "USA", "840"),
            ];

            return $countries[$code];
        }

        /**
         * Get country code function.
         *
         * @param $code
         *
         * @return mixed
         */
        public function getCountryIsoCode($code)
        {
            $countries = [
                'AF' => ['AFGHANISTAN', 'AF', 'AFG', '004'],
                'AL' => ['ALBANIA', 'AL', 'ALB', '008'],
                'DZ' => ['ALGERIA', 'DZ', 'DZA', '012'],
                'AS' => ['AMERICAN SAMOA', 'AS', 'ASM', '016'],
                'AD' => ['ANDORRA', 'AD', 'AND', '020'],
                'AO' => ['ANGOLA', 'AO', 'AGO', '024'],
                'AI' => ['ANGUILLA', 'AI', 'AIA', '660'],
                'AQ' => ['ANTARCTICA', 'AQ', 'ATA', '010'],
                'AG' => ['ANTIGUA AND BARBUDA', 'AG', 'ATG', '028'],
                'AR' => ['ARGENTINA', 'AR', 'ARG', '032'],
                'AM' => ['ARMENIA', 'AM', 'ARM', '051'],
                'AW' => ['ARUBA', 'AW', 'ABW', '533'],
                'AU' => ['AUSTRALIA', 'AU', 'AUS', '036'],
                'AT' => ['AUSTRIA', 'AT', 'AUT', '040'],
                'AZ' => ['AZERBAIJAN', 'AZ', 'AZE', '031'],
                'BS' => ['BAHAMAS', 'BS', 'BHS', '044'],
                'BH' => ['BAHRAIN', 'BH', 'BHR', '048'],
                'BD' => ['BANGLADESH', 'BD', 'BGD', '050'],
                'BB' => ['BARBADOS', 'BB', 'BRB', '052'],
                'BY' => ['BELARUS', 'BY', 'BLR', '112'],
                'BE' => ['BELGIUM', 'BE', 'BEL', '056'],
                'BZ' => ['BELIZE', 'BZ', 'BLZ', '084'],
                'BJ' => ['BENIN', 'BJ', 'BEN', '204'],
                'BM' => ['BERMUDA', 'BM', 'BMU', '060'],
                'BT' => ['BHUTAN', 'BT', 'BTN', '064'],
                'BO' => ['BOLIVIA', 'BO', 'BOL', '068'],
                'BA' => ['BOSNIA AND HERZEGOVINA', 'BA', 'BIH', '070'],
                'BW' => ['BOTSWANA', 'BW', 'BWA', '072'],
                'BV' => ['BOUVET ISLAND', 'BV', 'BVT', '074'],
                'BR' => ['BRAZIL', 'BR', 'BRA', '076'],
                'IO' => ['BRITISH INDIAN OCEAN TERRITORY', 'IO', 'IOT', '086'],
                'BN' => ['BRUNEI DARUSSALAM', 'BN', 'BRN', '096'],
                'BG' => ['BULGARIA', 'BG', 'BGR', '100'],
                'BF' => ['BURKINA FASO', 'BF', 'BFA', '854'],
                'BI' => ['BURUNDI', 'BI', 'BDI', '108'],
                'KH' => ['CAMBODIA', 'KH', 'KHM', '116'],
                'CM' => ['CAMEROON', 'CM', 'CMR', '120'],
                'CA' => ['CANADA', 'CA', 'CAN', '124'],
                'CV' => ['CAPE VERDE', 'CV', 'CPV', '132'],
                'KY' => ['CAYMAN ISLANDS', 'KY', 'CYM', '136'],
                'CF' => ['CENTRAL AFRICAN REPUBLIC', 'CF', 'CAF', '140'],
                'TD' => ['CHAD', 'TD', 'TCD', '148'],
                'CL' => ['CHILE', 'CL', 'CHL', '152'],
                'CN' => ['CHINA', 'CN', 'CHN', '156'],
                'CX' => ['CHRISTMAS ISLAND', 'CX', 'CXR', '162'],
                'CC' => ['COCOS (KEELING) ISLANDS', 'CC', 'CCK', '166'],
                'CO' => ['COLOMBIA', 'CO', 'COL', '170'],
                'KM' => ['COMOROS', 'KM', 'COM', '174'],
                'CG' => ['CONGO', 'CG', 'COG', '178'],
                'CD' => ['CONGO', 'CD', 'COD', '243'],
                'CK' => ['COOK ISLANDS', 'CK', 'COK', '184'],
                'CR' => ['COSTA RICA', 'CR', 'CRI', '188'],
                'CI' => ["COTE D'IVOIRE", 'CI', 'CIV', '384'],
                'HR' => ['CROATIA (local name: Hrvatska)', 'HR', 'HRV', '191'],
                'CU' => ['CUBA', 'CU', 'CUB', '192'],
                'CY' => ['CYPRUS', 'CY', 'CYP', '196'],
                'CZ' => ['CZECH REPUBLIC', 'CZ', 'CZE', '203'],
                'DK' => ['DENMARK', 'DK', 'DNK', '208'],
                'DJ' => ['DJIBOUTI', 'DJ', 'DJI', '262'],
                'DM' => ['DOMINICA', 'DM', 'DMA', '212'],
                'DO' => ['DOMINICAN REPUBLIC', 'DO', 'DOM', '214'],
                'TL' => ['EAST TIMOR', 'TL', 'TLS', '626'],
                'EC' => ['ECUADOR', 'EC', 'ECU', '218'],
                'EG' => ['EGYPT', 'EG', 'EGY', '818'],
                'SV' => ['EL SALVADOR', 'SV', 'SLV', '222'],
                'GQ' => ['EQUATORIAL GUINEA', 'GQ', 'GNQ', '226'],
                'ER' => ['ERITREA', 'ER', 'ERI', '232'],
                'EE' => ['ESTONIA', 'EE', 'EST', '233'],
                'ET' => ['ETHIOPIA', 'ET', 'ETH', '210'],
                'FK' => ['FALKLAND ISLANDS (MALVINAS)', 'FK', 'FLK', '238'],
                'FO' => ['FAROE ISLANDS', 'FO', 'FRO', '234'],
                'FJ' => ['FIJI', 'FJ', 'FJI', '242'],
                'FI' => ['FINLAND', 'FI', 'FIN', '246'],
                'FR' => ['FRANCE', 'FR', 'FRA', '250'],
                'FX' => ['FRANCE, METROPOLITAN', 'FX', 'FXX', '249'],
                'GF' => ['FRENCH GUIANA', 'GF', 'GUF', '254'],
                'PF' => ['FRENCH POLYNESIA', 'PF', 'PYF', '258'],
                'TF' => ['FRENCH SOUTHERN TERRITORIES', 'TF', 'ATF', '260'],
                'GA' => ['GABON', 'GA', 'GAB', '266'],
                'GM' => ['GAMBIA', 'GM', 'GMB', '270'],
                'GE' => ['GEORGIA', 'GE', 'GEO', '268'],
                'DE' => ['GERMANY', 'DE', 'DEU', '276'],
                'GH' => ['GHANA', 'GH', 'GHA', '288'],
                'GI' => ['GIBRALTAR', 'GI', 'GIB', '292'],
                'GR' => ['GREECE', 'GR', 'GRC', '300'],
                'GL' => ['GREENLAND', 'GL', 'GRL', '304'],
                'GD' => ['GRENADA', 'GD', 'GRD', '308'],
                'GP' => ['GUADELOUPE', 'GP', 'GLP', '312'],
                'GU' => ['GUAM', 'GU', 'GUM', '316'],
                'GT' => ['GUATEMALA', 'GT', 'GTM', '320'],
                'GN' => ['GUINEA', 'GN', 'GIN', '324'],
                'GW' => ['GUINEA-BISSAU', 'GW', 'GNB', '624'],
                'GY' => ['GUYANA', 'GY', 'GUY', '328'],
                'HT' => ['HAITI', 'HT', 'HTI', '332'],
                'HM' => ['HEARD ISLAND & MCDONALD ISLANDS', 'HM', 'HMD', '334'],
                'HN' => ['HONDURAS', 'HN', 'HND', '340'],
                'HK' => ['HONG KONG', 'HK', 'HKG', '344'],
                'HU' => ['HUNGARY', 'HU', 'HUN', '348'],
                'IS' => ['ICELAND', 'IS', 'ISL', '352'],
                'IN' => ['INDIA', 'IN', 'IND', '356'],
                'ID' => ['INDONESIA', 'ID', 'IDN', '360'],
                'IR' => ['IRAN, ISLAMIC REPUBLIC OF', 'IR', 'IRN', '364'],
                'IQ' => ['IRAQ', 'IQ', 'IRQ', '368'],
                'IE' => ['IRELAND', 'IE', 'IRL', '372'],
                'IL' => ['ISRAEL', 'IL', 'ISR', '376'],
                'IT' => ['ITALY', 'IT', 'ITA', '380'],
                'JM' => ['JAMAICA', 'JM', 'JAM', '388'],
                'JP' => ['JAPAN', 'JP', 'JPN', '392'],
                'JO' => ['JORDAN', 'JO', 'JOR', '400'],
                'KZ' => ['KAZAKHSTAN', 'KZ', 'KAZ', '398'],
                'KE' => ['KENYA', 'KE', 'KEN', '404'],
                'KI' => ['KIRIBATI', 'KI', 'KIR', '296'],
                'KP' => ["KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF", 'KP', 'PRK', '408'],
                'KR' => ['KOREA, REPUBLIC OF', 'KR', 'KOR', '410'],
                'KW' => ['KUWAIT', 'KW', 'KWT', '414'],
                'KG' => ['KYRGYZSTAN', 'KG', 'KGZ', '417'],
                'LA' => ["LAO PEOPLE'S DEMOCRATIC REPUBLIC", 'LA', 'LAO', '418'],
                'LV' => ['LATVIA', 'LV', 'LVA', '428'],
                'LB' => ['LEBANON', 'LB', 'LBN', '422'],
                'LS' => ['LESOTHO', 'LS', 'LSO', '426'],
                'LR' => ['LIBERIA', 'LR', 'LBR', '430'],
                'LY' => ['LIBYAN ARAB JAMAHIRIYA', 'LY', 'LBY', '434'],
                'LI' => ['LIECHTENSTEIN', 'LI', 'LIE', '438'],
                'LT' => ['LITHUANIA', 'LT', 'LTU', '440'],
                'LU' => ['LUXEMBOURG', 'LU', 'LUX', '442'],
                'MO' => ['MACAU', 'MO', 'MAC', '446'],
                'MK' => ['MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'MK', 'MKD', '807'],
                'MG' => ['MADAGASCAR', 'MG', 'MDG', '450'],
                'MW' => ['MALAWI', 'MW', 'MWI', '454'],
                'MY' => ['MALAYSIA', 'MY', 'MYS', '458'],
                'MV' => ['MALDIVES', 'MV', 'MDV', '462'],
                'ML' => ['MALI', 'ML', 'MLI', '466'],
                'MT' => ['MALTA', 'MT', 'MLT', '470'],
                'MH' => ['MARSHALL ISLANDS', 'MH', 'MHL', '584'],
                'MQ' => ['MARTINIQUE', 'MQ', 'MTQ', '474'],
                'MR' => ['MAURITANIA', 'MR', 'MRT', '478'],
                'MU' => ['MAURITIUS', 'MU', 'MUS', '480'],
                'YT' => ['MAYOTTE', 'YT', 'MYT', '175'],
                'MX' => ['MEXICO', 'MX', 'MEX', '484'],
                'FM' => ['MICRONESIA, FEDERATED STATES OF', 'FM', 'FSM', '583'],
                'MD' => ['MOLDOVA, REPUBLIC OF', 'MD', 'MDA', '498'],
                'ME' => ['MONTENERGO', 'ME', 'MNE', '382'],
                'MC' => ['MONACO', 'MC', 'MCO', '492'],
                'MN' => ['MONGOLIA', 'MN', 'MNG', '496'],
                'MS' => ['MONTSERRAT', 'MS', 'MSR', '500'],
                'MA' => ['MOROCCO', 'MA', 'MAR', '504'],
                'MZ' => ['MOZAMBIQUE', 'MZ', 'MOZ', '508'],
                'MM' => ['MYANMAR', 'MM', 'MMR', '104'],
                'NA' => ['NAMIBIA', 'NA', 'NAM', '516'],
                'NR' => ['NAURU', 'NR', 'NRU', '520'],
                'NP' => ['NEPAL', 'NP', 'NPL', '524'],
                'NL' => ['NETHERLANDS', 'NL', 'NLD', '528'],
                'AN' => ['NETHERLANDS ANTILLES', 'AN', 'ANT', '530'],
                'NC' => ['NEW CALEDONIA', 'NC', 'NCL', '540'],
                'NZ' => ['NEW ZEALAND', 'NZ', 'NZL', '554'],
                'NI' => ['NICARAGUA', 'NI', 'NIC', '558'],
                'NE' => ['NIGER', 'NE', 'NER', '562'],
                'NG' => ['NIGERIA', 'NG', 'NGA', '566'],
                'NU' => ['NIUE', 'NU', 'NIU', '570'],
                'NF' => ['NORFOLK ISLAND', 'NF', 'NFK', '574'],
                'MP' => ['NORTHERN MARIANA ISLANDS', 'MP', 'MNP', '580'],
                'NO' => ['NORWAY', 'NO', 'NOR', '578'],
                'OM' => ['OMAN', 'OM', 'OMN', '512'],
                'PK' => ['PAKISTAN', 'PK', 'PAK', '586'],
                'PW' => ['PALAU', 'PW', 'PLW', '585'],
                'PA' => ['PANAMA', 'PA', 'PAN', '591'],
                'PG' => ['PAPUA NEW GUINEA', 'PG', 'PNG', '598'],
                'PY' => ['PARAGUAY', 'PY', 'PRY', '600'],
                'PE' => ['PERU', 'PE', 'PER', '604'],
                'PH' => ['PHILIPPINES', 'PH', 'PHL', '608'],
                'PN' => ['PITCAIRN', 'PN', 'PCN', '612'],
                'PL' => ['POLAND', 'PL', 'POL', '616'],
                'PT' => ['PORTUGAL', 'PT', 'PRT', '620'],
                'PR' => ['PUERTO RICO', 'PR', 'PRI', '630'],
                'QA' => ['QATAR', 'QA', 'QAT', '634'],
                'RE' => ['REUNION', 'RE', 'REU', '638'],
                'RO' => ['ROMANIA', 'RO', 'ROU', '642'],
                'RU' => ['RUSSIAN FEDERATION', 'RU', 'RUS', '643'],
                'RW' => ['RWANDA', 'RW', 'RWA', '646'],
                'KN' => ['SAINT KITTS AND NEVIS', 'KN', 'KNA', '659'],
                'LC' => ['SAINT LUCIA', 'LC', 'LCA', '662'],
                'VC' => ['SAINT VINCENT AND THE GRENADINES', 'VC', 'VCT', '670'],
                'WS' => ['SAMOA', 'WS', 'WSM', '882'],
                'SM' => ['SAN MARINO', 'SM', 'SMR', '674'],
                'ST' => ['SAO TOME AND PRINCIPE', 'ST', 'STP', '678'],
                'SA' => ['SAUDI ARABIA', 'SA', 'SAU', '682'],
                'SN' => ['SENEGAL', 'SN', 'SEN', '686'],
                'RS' => ['SERBIA', 'RS', 'SRB', '688'],
                'SC' => ['SEYCHELLES', 'SC', 'SYC', '690'],
                'SL' => ['SIERRA LEONE', 'SL', 'SLE', '694'],
                'SG' => ['SINGAPORE', 'SG', 'SGP', '702'],
                'SK' => ['SLOVAKIA (Slovak Republic)', 'SK', 'SVK', '703'],
                'SI' => ['SLOVENIA', 'SI', 'SVN', '705'],
                'SB' => ['SOLOMON ISLANDS', 'SB', 'SLB', '90'],
                'SO' => ['SOMALIA', 'SO', 'SOM', '706'],
                'ZA' => ['SOUTH AFRICA', 'ZA', 'ZAF', '710'],
                'ES' => ['SPAIN', 'ES', 'ESP', '724'],
                'LK' => ['SRI LANKA', 'LK', 'LKA', '144'],
                'SH' => ['SAINT HELENA', 'SH', 'SHN', '654'],
                'PM' => ['SAINT PIERRE AND MIQUELON', 'PM', 'SPM', '666'],
                'SD' => ['SUDAN', 'SD', 'SDN', '736'],
                'SR' => ['SURINAME', 'SR', 'SUR', '740'],
                'SJ' => ['SVALBARD AND JAN MAYEN ISLANDS', 'SJ', 'SJM', '744'],
                'SZ' => ['SWAZILAND', 'SZ', 'SWZ', '748'],
                'SE' => ['SWEDEN', 'SE', 'SWE', '752'],
                'CH' => ['SWITZERLAND', 'CH', 'CHE', '756'],
                'SY' => ['SYRIAN ARAB REPUBLIC', 'SY', 'SYR', '760'],
                'TW' => ['TAIWAN, PROVINCE OF CHINA', 'TW', 'TWN', '158'],
                'TJ' => ['TAJIKISTAN', 'TJ', 'TJK', '762'],
                'TZ' => ['TANZANIA, UNITED REPUBLIC OF', 'TZ', 'TZA', '834'],
                'TH' => ['THAILAND', 'TH', 'THA', '764'],
                'TG' => ['TOGO', 'TG', 'TGO', '768'],
                'TK' => ['TOKELAU', 'TK', 'TKL', '772'],
                'TO' => ['TONGA', 'TO', 'TON', '776'],
                'TT' => ['TRINIDAD AND TOBAGO', 'TT', 'TTO', '780'],
                'TN' => ['TUNISIA', 'TN', 'TUN', '788'],
                'TR' => ['TURKEY', 'TR', 'TUR', '792'],
                'TM' => ['TURKMENISTAN', 'TM', 'TKM', '795'],
                'TC' => ['TURKS AND CAICOS ISLANDS', 'TC', 'TCA', '796'],
                'TV' => ['TUVALU', 'TV', 'TUV', '798'],
                'UG' => ['UGANDA', 'UG', 'UGA', '800'],
                'UA' => ['UKRAINE', 'UA', 'UKR', '804'],
                'AE' => ['UNITED ARAB EMIRATES', 'AE', 'ARE', '784'],
                'GB' => ['UNITED KINGDOM', 'GB', 'GBR', '826'],
                'US' => ['UNITED STATES', 'US', 'USA', '840'],
                'UM' => ['UNITED STATES MINOR OUTLYING ISLANDS', 'UM', 'UMI', '581'],
                'UY' => ['URUGUAY', 'UY', 'URY', '858'],
                'UZ' => ['UZBEKISTAN', 'UZ', 'UZB', '860'],
                'VU' => ['VANUATU', 'VU', 'VUT', '548'],
                'VA' => ['VATICAN CITY STATE (HOLY SEE)', 'VA', 'VAT', '336'],
                'VE' => ['VENEZUELA', 'VE', 'VEN', '862'],
                'VN' => ['VIET NAM', 'VN', 'VNM', '704'],
                'VG' => ['VIRGIN ISLANDS (BRITISH)', 'VG', 'VGB', '92'],
                'VI' => ['VIRGIN ISLANDS (U.S.)', 'VI', 'VIR', '850'],
                'WF' => ['WALLIS AND FUTUNA ISLANDS', 'WF', 'WLF', '876'],
                'EH' => ['WESTERN SAHARA', 'EH', 'ESH', '732'],
                'YE' => ['YEMEN', 'YE', 'YEM', '887'],
                'YU' => ['YUGOSLAVIA', 'YU', 'YUG', '891'],
                'ZR' => ['ZAIRE', 'ZR', 'ZAR', '180'],
                'ZM' => ['ZAMBIA', 'ZM', 'ZMB', '894'],
                'ZW' => ['ZIMBABWE', 'ZW', 'ZWE', '716'],
            ];

            return $countries[$code][2];
        }
    }

    /**
     * Add the Gateway to WooCommerce.
     *
     * @param array $methods
     *
     * @return array
     */
    function woocommerce_add_paytabs_creditcard_wc_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Paytabs_creditcard_wc';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytabs_creditcard_wc_gateway');
}
