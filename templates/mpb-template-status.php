<?php
if (!defined('ABSPATH')) exit;
get_header();
the_post()
?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">

            <article id="post-<?php the_ID(); ?>" class="hentry">

                <header class="entry-header">
                    <h1 class="entry-title"><?php $payment_status = mpb_check_transaction_service();
                        $order_info = $payment_status['result'];
                        $status = $payment_status['status'];
                        if (($status == 'APPROVED' || $status == 'CAPTURED')) echo 'Payment Received'; else echo 'Payment Failed'; ?></h1>
                </header>
                <div class="entry-content">
                    <?php
                    $email_address = array_key_exists('email', $order_info) ? $order_info["email"] : '';
                    $address = array_key_exists('address_1', $order_info) ? $order_info["address_1"] : '';
                    $mobile = array_key_exists('mobile', $order_info) ? $order_info["mobile"] : '';
                    $name = array_key_exists('name', $order_info) ? $order_info["name"] : '';
                    $address_1 = array_key_exists('address_1', $order_info) ? $order_info["address_1"] : '';
                    $address_2 = array_key_exists('address_2', $order_info) ? $order_info["address_2"] : '';
                    $city = array_key_exists('city', $order_info) ? $order_info["city"] : '';
                    $postal = array_key_exists('postal', $order_info) ? $order_info["postal"] : '';
                    $country = array_key_exists('country', $order_info) ? $order_info["country"] : '';
                    $state = array_key_exists('state', $order_info) ? $order_info["state"] : '';
                    $description = array_key_exists('description', $order_info) ? $order_info["description"] : '';
                    if ($name) {
                        echo '<strong>Name:</strong> ' . $name . '<br/>';
                        echo '<strong>Email Address:</strong> ' . $email_address . '<br/>';
                        echo '<strong>Shipping Address:</strong> ' . $address . '<br/>';
                        echo '<strong>Mobile Number:</strong> ' . $mobile . '<br/>';
                        echo '</ul>';
                    }
                    mpb_update_payment_status($status);
                    if (($status == 'APPROVED' || $status == 'CAPTURED') && !$order_info['processed']) {
                        $to = get_option('admin_email');
                        $subject = '[' . get_bloginfo('name') . ']: New payment';
                        $message = "You’ve received the following payment from " . $name .
                            '<br/> <strong>Email Address:</strong> ' . $email_address . '<br/><strong>Mobile Phone:</strong> ' . $mobile .
                            '<br/><strong>Address 1:</strong> ' . $address_1 . '<br/><strong>Address 2:</strong> ' . $address_2 .
                            '<br/><strong>City:</strong> ' . $city .
                            '<br/><strong>Postal:</strong> ' . $postal .
                            '<br/><strong>Country:</strong> ' . $country .
                            '<br/><strong>State:</strong> ' . $state .
                            '<br/><strong>Description:</strong> ' . $description .
                            '<br/>' . get_the_date();
                        $headers = array('Content-Type: text/html; charset=UTF-8');
                        wp_mail($to, $subject, $message, $headers);
                        mpb_update_transaction_process_field();
                    }
                    ?>

                </div>

            </article>

        </main><!-- .site-main -->
    </div><!-- .content-area -->

<?php
get_footer();
?>
