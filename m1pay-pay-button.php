<?php
if (!defined('ABSPATH')) exit;
include_once "includes/mpb_common_utils.php";
include_once "includes/mpb_transaction_model.php";
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/*
Plugin Name: M1Pay Buy Now Button
Description: Add a M1 Pay Buy Now Button to your website and start selling today.
Plugin URI: https://mobilityone.com.my
Author: Sahba Changizi
Author URI: https://mobilityone.com.my
License: GPL2
Version: 1.1
*/


register_activation_hook(__FILE__, "mpb_activate");
register_deactivation_hook(__FILE__, "mpb_deactivate");
register_uninstall_hook(__FILE__, "mpb_uninstall");

function mpb_activate()
{
    $mpb_settings_options = array(
        'clientID' => '',
        'secretKey' => '',
        'privateKey' => '',
        'mode' => '2',
    );
    add_option("m1pay_settingsoptions", $mpb_settings_options);
    global $table_prefix, $wpdb;
    $tblname = 'mpb_orders';
    $mpb_track_table = $table_prefix . "$tblname ";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $mpb_track_table (
                id int(11) NOT NULL auto_increment,
                price FLOAT NOT NULL,
                product_name varchar(60) NOT NULL,
                product_description varchar(10) NOT NULL,
                email varchar(60) NOT NULL,
                name varchar(60) NOT NULL,
                mobile varchar(60) NOT NULL,
                address_1 varchar(60) NOT NULL,
                address_2 varchar(60) NOT NULL,
                city varchar(60) NOT NULL,
                postal varchar(60) NOT NULL,
                country varchar(60) NOT NULL,
                state varchar(60) NOT NULL,
                description varchar(60) NOT NULL,
                transaction_id varchar(60) NOT NULL,
                status varchar(20) NOT NULL,
                processed int(1) NOT NULL,
                UNIQUE KEY id (id)
        ) $charset_collate;";
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function mpb_load_block()
{
    wp_enqueue_script(
        'mpb-pay-button-block',
        plugin_dir_url(__FILE__) . 'includes/mpb_button_block.js',
        array('wp-blocks', 'wp-editor'),
        true
    );
}

add_action('enqueue_block_editor_assets', 'mpb_load_block');

function mpb_enqueue_scripts()
{
    wp_enqueue_style('MPBStyleCSS', plugins_url('includes/mpb_style.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'mpb_enqueue_scripts');

function mpb_load_custom_template($template)
{
    global $wp_query;
    if ($wp_query->is_404) {
        $wp_query->is_404 = false;
        $wp_query->is_archive = true;
    }
    header("HTTP/1.1 200 OK");
    include($template);
    exit;
}

function mpb_template_redirect()
{
    global $wp;
    if ($wp->request == 'MPBCheckStatus') {
        mpb_load_custom_template(plugin_dir_path(__FILE__) . 'templates/mpb-template-status.php');
    }
    if ($wp->request == 'MPBInvoice') {
        mpb_load_custom_template(plugin_dir_path(__FILE__) . 'templates/mpb-template-invoice.php');
    }
}

add_action('template_redirect', 'mpb_template_redirect');

add_action('rest_api_init', function () {
    register_rest_route('m1pay', '/createTransaction', array(
        'methods' => 'POST',
        'callback' => 'mpb_create_transaction_api',
    ));
    register_rest_route('m1pay', '/createOrder', array(
        'methods' => 'POST',
        'callback' => 'mpb_create_order_api',
    ));
});

function mpb_create_order_api($request)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "mpb_orders";
    $price = floatval($request['amount']);
    $product_name = $request['product_name'];
    $product_description = $request['description'];
    $email = $request['email'];
    $name = $request['name'];
    $mobile = $request['mobile'];
    $address_1 = $request['address_1'];
    $address_2 = $request['address_2'];
    $city = $request['city'];
    $postal = $request['postal'];
    $country = $request['country'];
    $state = $request['state'];
    $description = $request['description'];
    $transaction_id = $request['transaction_id'];
    $wpdb->insert($table_name, array('price' => $price, 'product_name' => $product_name, 'product_description' => $product_description, 'email' => $email, 'name' => $name, 'mobile' => $mobile, 'address_1' => $address_1, 'address_2' => $address_2, 'city' => $city, 'postal' => $postal, 'country' => $country, 'state' => $state, 'description' => $description, 'transaction_id' => $transaction_id, 'processed' => 0));
}

function mpb_create_transaction_api($request)
{
    $parameters = floatval($request['amount']);
    $description = $request['description'];
    $value = mpb_load_settings();
    $clientSecret = $value['secretKey'];
    $privateKey = $value['privateKey'];
    $merchantID = $value['clientID'];
    $isSandBox = true;
    $order_id = time();
    $amount = number_format($parameters, 2, ".", "");
    $data = "$description|$amount|$order_id|$order_id|MYR|null|$merchantID";
    $pKeyID = openssl_get_privatekey($privateKey);
    openssl_sign($data, $signature, $pKeyID, "sha1WithRSAEncryption");
    $signature = mpb_str_to_hex($signature);
    $token = mpb_get_token($merchantID, $clientSecret, $isSandBox);
    if ($token) {
        $transAction = new MPB_Transaction_Model();
        $transAction->token = $token;
        $transAction->merchantID = $merchantID;
        $transAction->description = $description;
        $transAction->mobile = ' ';
        $transAction->merchantOrderID = $order_id;
        $transAction->sellerName = ' ';
        $transAction->signedData = $signature;
        $transAction->amount = $amount;
        $gateway_url = mpb_get_transaction_id($transAction, $isSandBox);
        $parts = parse_url($gateway_url);
        parse_str($parts['query'], $query);
        return ['url' => $gateway_url, 'transaction_id' => $query['transactionId']];
    }
    return null;
}

function mpb_check_transaction_service()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "mpb_orders";
    $value = mpb_load_settings();
    $clientSecret = $value['secretKey'];
    $merchantID = $value['clientID'];
    $isSandBox = true;
    $token = mpb_get_token($merchantID, $clientSecret, $isSandBox);
    $send_reference_id = mpb_validate_numeric_input($_GET['transactionId']);
    $transactionStatus = mpb_check_transaction_status($token, $send_reference_id, $isSandBox);
    $sql = $wpdb->prepare("SELECT * from $table_name WHERE transaction_id LIKE '%%%s%%';", $wpdb->esc_like($send_reference_id));
    $result = $wpdb->get_results($sql, ARRAY_A);
    return ['status' => is_array($transactionStatus) ? $transactionStatus['status'] : $transactionStatus, 'result' => sizeof($result) > 0 ? $result[0] : []];
}

function mpb_update_transaction_process_field()
{
    global $wpdb;
    $send_reference_id = mpb_validate_numeric_input($_GET['transactionId']);
    $table_name = $wpdb->prefix . "mpb_orders";
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET processed=1 WHERE transaction_id=%s", $send_reference_id));

}

function mpb_update_payment_status($status)
{
    global $wpdb;
    $send_reference_id = mpb_validate_numeric_input($_GET['transactionId']);
    $table_name = $wpdb->prefix . "mpb_orders";
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET status=%s WHERE transaction_id=%s", $status, $send_reference_id));

}

function mpb_add_click_script()
{
    wp_enqueue_script(
        'clicker-script',
        plugin_dir_url(__FILE__) . 'includes/mpb_clicker.js',
        array('jquery')
    );
}

add_action('wp_enqueue_scripts', 'mpb_add_click_script');

function mpb_deactivate()
{
    delete_option("mpb_notice_shown");
}

function mpb_uninstall()
{
    if (!defined('WP_UNINSTALL_PLUGIN')) exit();
    global $wpdb;
    $table_name = $wpdb->prefix . "mpb_orders";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

function mpb_admin_notices()
{
    if (!get_option('mpb_notice_shown')) {
        echo "<div class='updated'><p><a href='admin.php?page=mpb-ecommerce-settings'>Click here to view the plugin settings</a>.</p></div>";
        update_option("mpb_notice_shown", "true");
    }
}

add_action('admin_notices', 'mpb_admin_notices');

function mpb_menu()
{
    add_options_page("M1Pay Button", "M1Pay Button", "manage_options", "mpb-ecommerce-settings", "mpb_options");
    add_options_page("M1Pay Orders", "M1Pay Orders", "manage_options", "mpb-ecommerce-orders", "mpb_orders");
}

add_action("admin_menu", "mpb_menu");

function mpb_action_links($links, $file)
{
    static $this_plugin;
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }
    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=mpb-ecommerce-settings">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

add_filter('plugin_action_links', 'mpb_action_links', 10, 2);

function mpb_settings_link($links)
{
    unset($links['edit']);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'mpb_settings_link');


function mpb_options()
{
    if (!current_user_can("manage_options")) {
        wp_die(__("You do not have sufficient permissions to access this page."));
    }
    echo "<form method='post' action='" . $_SERVER["REQUEST_URI"] . "'>";
    if (isset($_POST['update'])) {
        $options['clientID'] = mpb_validate_numeric_input($_POST['clientID']);
        $options['secretKey'] = mpb_validate_text_input($_POST['secretKey'], false);
        $options['mode'] = mpb_validate_text_input($_POST['mode']);
        $options['privateKey'] = mpb_validate_text_input($_POST['privateKey'], false);
        update_option("m1pay_settingsoptions", $options);
        echo "<br /><div class='updated'><p><strong>";
        _e("Settings Updated.");
        echo "</strong></p></div>";
    }
    $options = get_option('m1pay_settingsoptions');
    foreach ($options as $k => $v) {
        $value[$k] = $v;
    }
    echo "<br />";
    echo '<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
        &nbsp; Usage
    </div>
    <div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br/>

        In a page or post editor you will see a new button called "M1 Pay Button" located above the text area beside the
        "Add Media" button. By using this you can
        create blocks which will show up as Buy Now button on your site.
        <br/><br/>
        You can put the Buy Now buttons as many times in a page or post as you want, there is no limit.
        <br/><br/>

        Please send <strong>' . get_bloginfo('wpurl') . '/MPBCheckStatus</strong> as redirect URL of the
        gateway to M1 Pay
        support team.
    </div>
    <div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
        &nbsp; M1 Pay Account
    </div>
    <div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br/>';
    echo "<b>Client ID: </b><input type='text' name='clientID' value='" . $value['clientID'] . "'> Required	<br /><br />";
    echo "<b>Secret Key: </b><input type='text' name='secretKey' value='" . $value['secretKey'] . "'> Required 	<br /><br />";
    echo "<b>Private Key: </b><textarea name='privateKey'>" . $value['privateKey'] . "</textarea> Required 	<br /><br />";

    echo "<b>Sandbox Mode:</b>";
    echo "&nbsp; &nbsp; <input ";
    if ($value['mode'] == "1") {
        echo "checked='checked'";
    }
    echo " type='radio' name='mode' value='1'>On (Sandbox mode)";
    echo "&nbsp; &nbsp; <input ";
    if ($value['mode'] == "2") {
        echo "checked='checked'";
    }
    echo " type='radio' name='mode' value='2'>Off (Live mode)";
    echo "<br /><br /></div>";
    echo '<br/><br/>
    <input type="hidden" name=""update"><br/>
    <input type="submit" name="btn2" class="button-primary" style="font-size: 17px;line-height: 28px;height: 32px;"
           value="Save Settings">
    <br/><br/><br/>
    </form>
    </div>';
}

function mpb_orders()
{
    if (!current_user_can("manage_options")) {
        wp_die(__("You do not have sufficient permissions to access this page."));
    }
    $exampleListTable = new M1_Order_Table();
    $exampleListTable->prepare_items();

    echo '<div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br/>';
    $exampleListTable->display();
    echo '</div>';
}


function mpb_load_settings()
{
    $options = get_option('m1pay_settingsoptions');
    foreach ($options as $k => $v) {
        $value[$k] = $v;
    }
    return $value;
}

class M1_Order_Table extends WP_List_Table
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page' => $perPage
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = array(
            'id' => 'ID',
            'price' => 'Price',
            'product_name' => 'Product Name',
            'product_description' => 'Product Description',
            'email' => 'Email',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'address_1' => 'Address 1',
            'address_2' => 'Address 2',
            'city' => 'City',
            'postal' => 'Postal',
            'country' => 'Country',
            'state' => 'State',
            'description' => 'Description',
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return array();
    }

    public function get_sortable_columns()
    {
        return array('email' => array('email', false));
    }

    private function table_data()
    {
        $data = array();
        global $wpdb;
        $table_name = $wpdb->prefix . "mpb_orders";
        $result = $wpdb->get_results("SELECT * from $table_name");

        return $result;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'price':
            case 'product_name':
            case 'product_description':
            case 'email':
            case 'name':
            case 'mobile':
            case 'address_1':
            case 'address_2':
            case 'city':
            case 'postal':
            case 'country':
            case 'state':
            case 'description':
                return $item->{$column_name};

            default:
                return print_r($item, true);
        }
    }

    private function sort_data($a, $b)
    {
        $orderby = 'email';
        $order = 'asc';
        if (!empty($_GET['orderby'])) {
            $orderby = mpb_validate_text_input($_GET['orderby']);
        }
        if (!empty($_GET['order'])) {
            $order = mpb_validate_text_input($_GET['order']);
        }
        $result = strcmp($a->{$orderby}, $b->{$orderby});

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }
}

?>