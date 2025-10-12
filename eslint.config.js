module.exports = [
	{
		languageOptions: {
			ecmaVersion: 2021,
			sourceType: 'script',
			globals: {
				wp: 'readonly',
				wc: 'readonly',
				nvmCheckoutData: 'readonly',
				vat_ajax_object: 'readonly',
				jQuery: 'readonly',
				console: 'readonly',
				document: 'readonly',
				window: 'readonly',
			},
		},
		rules: {
			'no-console': 'warn',
			camelcase: 'warn',
			'no-unused-vars': 'warn',
			semi: [ 'error', 'always' ],
			quotes: [ 'error', 'single' ],
			indent: [ 'error', 'tab' ],
		},
	},
];
