const { store, getContext } = wp.interactivity;

console.log("Running Interactivity API"); // ✅ Debug check

store("nvm-checkout", {
	state: {
		vatNumber: "",
		companyName: "",
		irsOffice: "",
		businessActivity: "",
	},
	actions: {
		updateVat({ event }) {
			const vatValue = event.target.value;

			// Debugging
			console.log("VAT input detected:", vatValue);

			// Update the state with VAT input
			getContext().state.vatNumber = vatValue;

			if (vatValue.length >= 9) {
				getContext().state.companyName = `Εταιρία ${vatValue}`;
				getContext().state.irsOffice = `ΔΟΥ ${vatValue.slice(-3)}`;
				getContext().state.businessActivity = "Εμπορική Δραστηριότητα";
			} else {
				getContext().state.companyName = "";
				getContext().state.irsOffice = "";
				getContext().state.businessActivity = "";
			}
		},
	},
});
