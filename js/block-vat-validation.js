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
					jQuery("#contact-nvm-billing_irs").val(response.data.doy);
					jQuery("#contact-nvm-billing_company").val(response.data.epwnymia);
					jQuery("#contact-nvm-billing_activity").val(
						response.data.drastiriotita
					);
				} else {
					alert("Invalid VAT number or unable to fetch details.");
				}
			},
		});
	}
});
