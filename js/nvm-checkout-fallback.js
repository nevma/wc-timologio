// Fallback VAT lookup for when Interactivity API is not available
(function () {
	'use strict';

	// Helper to set input value for React/WooCommerce blocks
	function setReactValue(element, value) {
		const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
			window.HTMLInputElement.prototype,
			'value'
		).set;
		nativeInputValueSetter.call(element, value);

		// Trigger all events that React/WooCommerce might listen to
		const events = ['input', 'change', 'blur'];
		events.forEach(eventType => {
			const event = new Event(eventType, { bubbles: true });
			element.dispatchEvent(event);
		});
	}

	// Wait for DOM to be ready
	function initVatLookup() {
		const vatInput = document.getElementById('contact-nvm-billing_vat');

		if (!vatInput) {
			// Try again after a short delay (for dynamic content)
			setTimeout(initVatLookup, 500);
			return;
		}

		console.log('NVM VAT Lookup: Initialized (fallback mode)');

		vatInput.addEventListener('focusout', function () {
			const vatValue = this.value;
			const numericVat = vatValue.replace(/\D/g, '');

			// Only proceed if VAT has at least 8 digits
			if (numericVat.length < 8) {
				return;
			}

			console.log('NVM VAT Lookup: Fetching details for VAT:', vatValue);

			// Get AJAX data
			const ajaxUrl = typeof nvmCheckoutData !== 'undefined' ? nvmCheckoutData.ajax_url : '';
			const ajaxNonce = typeof nvmCheckoutData !== 'undefined' ? nvmCheckoutData.ajax_nonce : '';

			if (!ajaxUrl || !ajaxNonce) {
				console.error('NVM VAT Lookup: AJAX URL or nonce not available');
				return;
			}

			// Make AJAX request
			const formData = new FormData();
			formData.append('action', 'fetch_vat_details');
			formData.append('vat_number', vatValue);
			formData.append('security', ajaxNonce);

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
			})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						console.log('NVM VAT Lookup: Success', data.data);

						// Helper function to find and update field
						function updateField(selectors, value) {
							if (!value) return;

							for (const selector of selectors) {
								const input = document.querySelector(selector);
								if (input) {
									// Use setReactValue to properly update React-controlled inputs
									setReactValue(input, value);
									console.log('NVM VAT Lookup: Updated field', selector, 'with value:', value);
									return true;
								}
							}
							console.warn('NVM VAT Lookup: Could not find field for selectors:', selectors);
							return false;
						}

						// Update company name field
						updateField(
							[
								'#contact-nvm-billing_company',
								'input[id*="billing_company"]',
								'input[name*="billing_company"]'
							],
							data.data.epwnymia
						);

						// Update IRS/DOY field
						updateField(
							[
								'#contact-nvm-billing_irs',
								'input[id*="billing_irs"]',
								'input[name*="billing_irs"]'
							],
							data.data.doy
						);

						// Update activity field
						const activity = Array.isArray(data.data.drastiriotita)
							? data.data.drastiriotita.join(', ')
							: data.data.drastiriotita;
						updateField(
							[
								'#contact-nvm-billing_activity',
								'input[id*="billing_activity"]',
								'input[name*="billing_activity"]'
							],
							activity
						);

						// Update address fields using setReactValue
						const addressInput = document.querySelector('input[name="billing_address_1"]');
						if (addressInput && data.data.address) {
							setReactValue(addressInput, data.data.address);
							console.log('NVM VAT Lookup: Updated address field');
						}

						const cityInput = document.querySelector('input[name="city"]');
						if (cityInput && data.data.city) {
							setReactValue(cityInput, data.data.city);
							console.log('NVM VAT Lookup: Updated city field');
						}

						const postcodeInput = document.querySelector('input[name="postcode"]');
						if (postcodeInput && data.data.postcode) {
							setReactValue(postcodeInput, data.data.postcode);
							console.log('NVM VAT Lookup: Updated postcode field');
						}

						const countryInput = document.querySelector('select[name="country"]');
						if (countryInput && data.data.country) {
							// For select elements, standard approach should work
							countryInput.value = data.data.country;
							countryInput.dispatchEvent(new Event('change', { bubbles: true }));
							countryInput.dispatchEvent(new Event('input', { bubbles: true }));
							console.log('NVM VAT Lookup: Updated country field');
						}
					} else {
						console.error('NVM VAT Lookup: Invalid VAT number or unable to fetch details');
					}
				})
				.catch(error => {
					console.error('NVM VAT Lookup: Error fetching VAT details:', error);
				});
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initVatLookup);
	} else {
		initVatLookup();
	}
})();
