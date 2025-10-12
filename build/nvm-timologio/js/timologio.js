document.addEventListener("DOMContentLoaded", function () {
	// Select all radio buttons with the name 'type_of_order'
	const orderTypeRadios = document.querySelectorAll(
		'input[name="type_of_order"]'
	);

	// Function to update the display of .timologio elements
	function updateDisplay() {
		// Get the selected radio button
		const selectedRadio = document.querySelector(
			'input[name="type_of_order"]:checked'
		);

		// Check if a radio button is selected
		if (selectedRadio) {
			const selectedValue = selectedRadio.value;

			// Show or hide .timologio elements based on the selected value
			document.querySelectorAll(".timologio").forEach((el) => {
				el.style.display = selectedValue === "timologio" ? "block" : "none";
			});
		} else {
			// If no radio button is selected, hide all .timologio elements
			document.querySelectorAll(".timologio").forEach((el) => {
				el.style.display = "none";
			});
		}
	}

	// Initial call to set the correct display on page load
	updateDisplay();

	// Add event listeners to each radio button to detect changes
	orderTypeRadios.forEach((radio) =>
		radio.addEventListener("change", updateDisplay)
	);
});

document.addEventListener("DOMContentLoaded", function () {
	function initInvoiceCheckbox() {
		const invoiceCheckbox = document.querySelector(
			"#contact-nvm-invoice_or_timologio"
		);

		if (invoiceCheckbox) {
			function toggleInvoiceFields() {
				const invoiceFields = document.querySelectorAll(
					'[data-nvm*="timologio"]'
				);
				invoiceFields.forEach((field) => {
					const container = field.closest(".wc-block-components-text-input");
					if (container) {
						container.style.display = invoiceCheckbox.checked
							? "block"
							: "none";
					}
				});

				// Also handle the first row and last row fields
				const firstRowFields = document.querySelectorAll(
					'[data-nvm*="nvm-first-row"]'
				);
				const lastRowFields = document.querySelectorAll(
					'[data-nvm*="nvm-last-row"]'
				);

				firstRowFields.forEach((field) => {
					const container = field.closest(".wc-block-components-text-input");
					if (container) {
						container.style.display = invoiceCheckbox.checked
							? "block"
							: "none";
					}
				});

				lastRowFields.forEach((field) => {
					const container = field.closest(".wc-block-components-text-input");
					if (container) {
						container.style.display = invoiceCheckbox.checked
							? "block"
							: "none";
					}
				});
			}

			toggleInvoiceFields();
			invoiceCheckbox.addEventListener("change", toggleInvoiceFields);
		}
	}

	// Detect if the content loads dynamically
	const observer = new MutationObserver(initInvoiceCheckbox);
	observer.observe(document.body, { childList: true, subtree: true });

	// Initial call in case content is already loaded
	initInvoiceCheckbox();
});
