document.addEventListener("DOMContentLoaded", function () {
	// Select all radio buttons with the name 'type_of_order'
	const orderTypeRadios = document.querySelectorAll(
		'input[name="type_of_order"]'
	);

	// Function to update the display of .timologio elements
	function updateDisplay() {
		// Get the selected radio button's value
		const selectedValue = document.querySelector(
			'input[name="type_of_order"]:checked'
		).value;

		// Show or hide .timologio elements based on the selected value
		document.querySelectorAll(".timologio").forEach((el) => {
			el.style.display = selectedValue === "timologio" ? "block" : "none";
		});
	}

	// Initial call to set the correct display on page load
	updateDisplay();

	// Add event listeners to each radio button to detect changes
	orderTypeRadios.forEach((radio) =>
		radio.addEventListener("change", updateDisplay)
	);
});
