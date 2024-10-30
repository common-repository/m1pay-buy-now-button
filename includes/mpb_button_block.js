/* This section of the code registers a new block, sets an icon and a category, and indicates what type of fields it'll include. */

wp.blocks.registerBlockType('m1/pay-button', {
    title: 'M1 Buy Now',
    icon: 'cart',
    category: 'common',
    attributes: {
        price: {type: 'number'},
        productDescription: {type: 'string'},
        productName: {type: 'string'},
        buttonText: {type: 'string'},
    },

    /* This configures how the price and color fields will work, and sets up the necessary elements */

    edit: function (props) {
        function updatePrice(event) {
            props.setAttributes({price: event.target.value})
        }

        function updateDescription(event) {
            props.setAttributes({productDescription: event.target.value})
        }

        function updateProductName(event) {
            props.setAttributes({productName: event.target.value})
        }

        function updateButtonText(event) {
            props.setAttributes({buttonText: event.target.value})
        }

        return React.createElement(
            "div",
            null,
            React.createElement("h3", null, "M1 Pay Button"),
            React.createElement("input", {
                style: {width: '100%'},
                placeholder: 'Enter Price Here...',
                type: "number",
                value: props.attributes.price,
                onChange: updatePrice
            }),
            React.createElement("input", {
                style: {width: '100%'},
                placeholder: 'Enter Product Name...',
                type: "string",
                value: props.attributes.productName,
                onChange: updateProductName
            }),
            React.createElement("input", {
                style: {width: '100%'},
                placeholder: 'Enter Product Description...',
                type: "string",
                value: props.attributes.productDescription,
                onChange: updateDescription
            }),
            React.createElement("input", {
                style: {width: '100%'},
                placeholder: 'Enter Button Text...',
                type: "string",
                value: props.attributes.buttonText,
                onChange: updateButtonText
            }),
        );
    },


    save: function (props) {
        return React.createElement(
            "div",
            null,
            React.createElement("button", {class: "clicker-" + props.attributes.price + "-" + props.attributes.productDescription + "-" + props.attributes.productName}, props.attributes.buttonText),
        )
    }
})