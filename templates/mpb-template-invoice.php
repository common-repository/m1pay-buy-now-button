<?php
if (!defined('ABSPATH')) exit;
get_header();
the_post()
?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">

            <article id="post-<?php the_ID(); ?>" class="hentry">

                <div class="entry-content">
                    <?php
                    echo "<h3>" . mpb_validate_text_input($_COOKIE['mpb_product_name']) . "</h3>";
                    echo "<p>" . mpb_validate_text_input($_COOKIE['mpb_product_description']) . "</p>";
                    echo '<input id="mpb-quantity" min="1" type="number" value="1"/>';
                    echo '<h3 class="header-title">Order Summary</h3>';
                    echo '<table style="width: 100%">
                            <thead>
                            </thead>
                            <tbody>
                                <tr class="border-up">
                                    <td>Total</td>
                                    <td style="text-align: right; font-size: 18px;">RM <span id="mpb-total">' . mpb_validate_text_input($_COOKIE['mpb_price']) . '</span></td>
                                </tr>
                            </tbody>
                        </table>';
                    echo '<h3 class="header-title">Contact Information</h3>';
                    echo '<div id="mpb-contact"><input required placeholder="Name *" id="mpb-name-input" class="form-input">';
                    echo '<input required placeholder="Email *" id="mpb-email-input" class="form-input">';
                    echo '<input required placeholder="Mobile Number *" id="mpb-mobile-input" class="form-input">';
                    echo '<h3 class="header-title">Delivery</h3>';
                    echo '<input required placeholder="Address 1 *" id="mpb-address-1-input" class="form-input">';
                    echo '<input placeholder="Address 2" id="mpb-address-2-input" class="form-input">';
                    echo '<input required placeholder="City *" id="mpb-city-input" class="form-input">';
                    echo '<input required placeholder="Postal Code *" id="mpb-postal-input" class="form-input">';
                    echo '<div id="ships_from_countries_field">';
                    echo '<div id="shipping-country">';
                    woocommerce_form_field('my_country_field', array(
                            'type' => 'country',
                            'id' => 'mpb-country-input',
                            'class' => array('chzn-drop', 'select-input'),
                            'placeholder' => 'Select a Country *',
                        )
                    );
                    echo '</div>';
                    echo '<div id="ships_from_state_field">';
                    woocommerce_form_field('my_state_field', array(
                            'type' => 'state',
                            'id' => 'mpb-state-input',
                            'class' => array('chzn-drop', 'select-input'),
                            'placeholder' => 'Select a State *'
                        )
                    );
                    echo '</div></div>';
                    echo '<h3 class="header-title">Order Description</h3>';
                    echo '<input placeholder="Order Description (Optional)" id="mpb-description-input" class="form-input"></div>';
                    echo '<button class="pay-button" style="float: right">Pay With M1Pay</button>';
                    ?>
                </div>

            </article>

        </main><!-- .site-main -->
    </div><!-- .content-area -->

<?php
get_footer();
?>