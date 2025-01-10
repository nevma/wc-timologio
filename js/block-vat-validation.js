// Trigger AJAX Call After VAT Validation
jQuery(document).on("focusout", "#contact-nvm-billing_vat", function () {
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
					nvm_updateField("#contact-nvm-billing_irs", response.data.doy);
					nvm_updateField(
						"#contact-nvm-billing_company",
						response.data.epwnymia
					);
					nvm_updateField(
						"#contact-nvm-billing_activity",
						response.data.drastiriotita
					);
				} else {
					alert("Invalid VAT number or unable to fetch details.");
				}
			},
		});
	}
});

/**
 * Updates the field value and triggers input and change events.
 *
 * @param {string} selector - The jQuery selector for the input field.
 * @param {string} value - The value to set in the input field.
 */
function nvm_updateField(selector, value) {
	var $field = jQuery(selector);
	$field
		.val(value)
		.attr("value", value)
		.trigger("input") // Mimics typing in the input field
		.trigger("change"); // Ensures change event listeners also fire
}
