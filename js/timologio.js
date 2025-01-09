document.addEventListener("DOMContentLoaded", function () {
	const orderTypeRadios = document.querySelectorAll(
		'input[name="type_of_order"]'
	);

	function updateDisplay() {
		const selectedValue = document.querySelector(
			'input[name="type_of_order"]:checked'
		).value;
		document
			.querySelectorAll(".timologio")
			.forEach(
				(el) =>
					(el.style.display =
						selectedValue === "type_of_order" ? "block" : "none")
			);
	}

	updateDisplay();
	orderTypeRadios.forEach((radio) =>
		radio.addEventListener("change", updateDisplay)
	);
});
