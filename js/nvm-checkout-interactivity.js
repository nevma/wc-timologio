import { store, getContext, getElement } from "@wordpress/interactivity";

console.log("Running");

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
