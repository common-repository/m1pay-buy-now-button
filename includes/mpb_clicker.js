(function ($) {
    function setCookie(key, value, expiry) {
        var expires = new Date();
        expires.setTime(expires.getTime() + (expiry * 24 * 60 * 60 * 1000));
        document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
    }

    function getCookie(key) {
        var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
        return keyValue ? keyValue[2] : null;
    }

    $(document).ready(function () {
        var price = 0.00;
        var description = ' ';
        $('button[class^=\'clicker\']').on('click', function () {
            price = this.className.split("-")[1];
            name = this.className.split("-")[2];
            description = this.className.split("-")[3];
            setCookie('mpb_product_name', name, 1);
            setCookie('mpb_product_description', description, 1);
            setCookie('mpb_price', parseFloat(price).toFixed(2), 1);
            window.location.replace("/MPBInvoice");
        });
        $('.pay-button').on('click', function () {
            var form_data = $("#mpb-contact input");
            var error_free = true;
            for ( var i = 0; i < form_data.length; i++ ) {
                var inputSelector = form_data[i];
                var element = $(inputSelector);
                var required = element.attr("required");
                var val = element.val();
                if (required && !val) {
                    element.addClass("error");
                    error_free = false;
                } else {
                    element.removeClass("error");
                }
            }
            if (!error_free) {
                event.preventDefault();
            } else {
                $(this).attr("disabled", true);
                let price = getCookie('mpb_price');
                let product_description = getCookie('mpb_product_description');
                let product_name = getCookie('mpb_product_name');
                var url = window.location.origin;
                $.post(url + '/wp-json/m1pay/createTransaction', {amount: price, description: product_name},
                    function (returnedData) {
                        $.post(url + '/wp-json/m1pay/createOrder', {
                            amount: price,
                            product_name: product_name,
                            description: product_description,
                            email: $("#mpb-email-input").val(),
                            name: $("#mpb-name-input").val(),
                            mobile: $("#mpb-mobile-input").val(),
                            address_1: $("#mpb-address-1-input").val(),
                            address_2: $("#mpb-address-2-input").val(),
                            city: $("#mpb-city-input").val(),
                            postal: $("#mpb-postal-input").val(),
                            country: $("#mpb-country-input").val(),
                            state: $("#mpb-state-input").val(),
                            description: $("#mpb-description-input").val(),
                            transaction_id: returnedData['transaction_id'],
                        });
                        window.location.replace(returnedData['url']);
                    });
            }
        });
        $('#mpb-quantity').change(function () {
            let quantity = $(this).val();
            let price = $("#mpb-total").html();
            let newPrice = getCookie('mpb_price') * parseInt(quantity);
            let float_value = newPrice.toFixed(2);
            setCookie('mpb_price', float_value, 1)
            $("#mpb-total").html(float_value)
        });
    });
})(jQuery);