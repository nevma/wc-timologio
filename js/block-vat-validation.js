// Ensure the script runs after WooCommerce Blocks are ready
wp.hooks.addFilter(
	"woocommerce.blocks.checkout.customerAddressEdit",
	"nvm/vat-validation",
	function (addressFields, type) {
		return addressFields.map((field) => {
			if (field.name === "billing_vat") {
				return {
					...field,
					validate: ["required", "nvm-validate-vat"],
					placeholder: "Enter VAT Number",
				};
			}
			return field;
		});
	}
);

// Custom VAT Validation Logic
wp.hooks.addFilter(
	"woocommerce.blocks.checkout.validation",
	"nvm/vat-custom-validation",
	function (validators) {
		validators["nvm-validate-vat"] = function (value) {
			const numericVat = value.replace(/\D/g, "");
			if (numericVat.length < 8) {
				return "The VAT number must have at least 8 digits.";
			}
			return true;
		};
		return validators;
	}
);

// Trigger AJAX Call After VAT Validation
jQuery(document).on("focusout", 'input[name="billing_vat"]', function () {
	var vatNumber = jQuery(this).val();

	if (vatNumber) {
		var numericVat = vatNumber.replace(/\D/g, "");
		if (numericVat.length < 8) {
			return;
		}

		jQuery.ajax({
			url: vat_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "fetch_vat_details",
				vat_number: vatNumber,
				security: vat_ajax_object.ajax_nonce,
			},
			success: function (response) {
				if (response.success) {
					jQuery('input[name="billing_irs"]').val(response.data.doy);
					jQuery('input[name="billing_company"]').val(response.data.epwnymia);
					jQuery('input[name="billing_activity"]').val(
						response.data.drastiriotita
					);
					jQuery('input[name="billing_address_1"]').val(response.data.address);
					jQuery('input[name="billing_country"]').val(response.data.country);
					jQuery('input[name="billing_city"]').val(response.data.city);
					jQuery('input[name="billing_postcode"]').val(response.data.postcode);
				} else {
					alert("Invalid VAT number or unable to fetch details.");
				}
			},
		});
	}
});
