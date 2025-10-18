// WordPress Interactivity API integration for VAT lookup
(function () {
	// Wait for wp.interactivity to be available
	if (typeof wp === 'undefined' || typeof wp.interactivity === 'undefined') {
		console.error('WordPress Interactivity API is not available');
		return;
	}

	const { store, getContext } = wp.interactivity;

	store('nvm-checkout', {
		state: {
			vatNumber: '',
			companyName: '',
			irsOffice: '',
			businessActivity: '',
			isLoading: false,
			ajaxUrl: '',
			ajaxNonce: '',
		},
		actions: {
			*updateVat({ event }) {
				const context = getContext();
				const state = context.state;
				const vatValue = event.target.value;

				// Update the state with VAT input
				state.vatNumber = vatValue;

				// Remove non-numeric characters for validation
				const numericVat = vatValue.replace(/\D/g, '');

				// Only proceed if VAT has at least 8 digits
				if (numericVat.length < 8) {
					state.companyName = '';
					state.irsOffice = '';
					state.businessActivity = '';
					return;
				}

				// Set loading state
				state.isLoading = true;

				try {
					// Get AJAX URL and nonce from state or fallback to localized data
					const ajaxUrl = state.ajaxUrl || (typeof nvmCheckoutData !== 'undefined' ? nvmCheckoutData.ajax_url : '');
					const ajaxNonce = state.ajaxNonce || (typeof nvmCheckoutData !== 'undefined' ? nvmCheckoutData.ajax_nonce : '');

					if (!ajaxUrl || !ajaxNonce) {
						console.error('AJAX URL or nonce not available');
						return;
					}

					// Make AJAX request to fetch VAT details
					const formData = new FormData();
					formData.append('action', 'fetch_vat_details');
					formData.append('vat_number', vatValue);
					formData.append('security', ajaxNonce);

					const response = yield fetch(ajaxUrl, {
						method: 'POST',
						body: formData,
					});

					const data = yield response.json();

				if (data.success) {
					// Update state with fetched data
					state.companyName = data.data.epwnymia || '';
					state.irsOffice = data.data.doy || '';

					// Handle activity (can be array or string)
					if (Array.isArray(data.data.drastiriotita)) {
						state.businessActivity = data.data.drastiriotita.join(', ');
					} else {
						state.businessActivity = data.data.drastiriotita || '';
					}

					// Update address fields as well
					const addressInput = document.querySelector('input[name="billing_address_1"]');
					const cityInput = document.querySelector('input[name="city"]');
					const postcodeInput = document.querySelector('input[name="postcode"]');
					const countryInput = document.querySelector('select[name="country"]');

					if (addressInput && data.data.address) {
						addressInput.value = data.data.address;
						addressInput.dispatchEvent(new Event('input', { bubbles: true }));
						addressInput.dispatchEvent(new Event('change', { bubbles: true }));
					}
					if (cityInput && data.data.city) {
						cityInput.value = data.data.city;
						cityInput.dispatchEvent(new Event('input', { bubbles: true }));
						cityInput.dispatchEvent(new Event('change', { bubbles: true }));
					}
					if (postcodeInput && data.data.postcode) {
						postcodeInput.value = data.data.postcode;
						postcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
						postcodeInput.dispatchEvent(new Event('change', { bubbles: true }));
					}
					if (countryInput && data.data.country) {
						countryInput.value = data.data.country;
						countryInput.dispatchEvent(new Event('input', { bubbles: true }));
						countryInput.dispatchEvent(new Event('change', { bubbles: true }));
					}
				} else {
					console.error('Invalid VAT number or unable to fetch details.');
					// Clear fields on error
					state.companyName = '';
					state.irsOffice = '';
					state.businessActivity = '';
				}
			} catch (error) {
				console.error('Error fetching VAT details:', error);
				// Clear fields on error
				state.companyName = '';
				state.irsOffice = '';
				state.businessActivity = '';
			} finally {
				state.isLoading = false;
			}
		},
	},
	});
})();
