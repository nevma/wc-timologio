const { store, getContext } = wp.interactivity;

store('nvm-checkout', {
	state: {
		vatNumber: '',
		companyName: '',
		irsOffice: '',
		businessActivity: '',
		isLoading: false,
	},
	actions: {
		*updateVat({ event }) {
			const context = getContext();
			const vatValue = event.target.value;

			// Update the state with VAT input
			context.state.vatNumber = vatValue;

			// Remove non-numeric characters for validation
			const numericVat = vatValue.replace(/\D/g, '');

			// Only proceed if VAT has at least 8 digits
			if (numericVat.length < 8) {
				context.state.companyName = '';
				context.state.irsOffice = '';
				context.state.businessActivity = '';
				return;
			}

			// Set loading state
			context.state.isLoading = true;

			try {
				// Make AJAX request to fetch VAT details
				const formData = new FormData();
				formData.append('action', 'fetch_vat_details');
				formData.append('vat_number', vatValue);
				formData.append('security', nvmCheckoutData.ajax_nonce);

				const response = yield fetch(nvmCheckoutData.ajax_url, {
					method: 'POST',
					body: formData,
				});

				const data = yield response.json();

				if (data.success) {
					// Update state with fetched data
					context.state.companyName = data.data.epwnymia || '';
					context.state.irsOffice = data.data.doy || '';

					// Handle activity (can be array or string)
					if (Array.isArray(data.data.drastiriotita)) {
						context.state.businessActivity = data.data.drastiriotita.join(', ');
					} else {
						context.state.businessActivity = data.data.drastiriotita || '';
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
					context.state.companyName = '';
					context.state.irsOffice = '';
					context.state.businessActivity = '';
				}
			} catch (error) {
				console.error('Error fetching VAT details:', error);
				// Clear fields on error
				context.state.companyName = '';
				context.state.irsOffice = '';
				context.state.businessActivity = '';
			} finally {
				context.state.isLoading = false;
			}
		},
	},
});
