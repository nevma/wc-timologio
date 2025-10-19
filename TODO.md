# TODO List - NVM Timologio Plugin

## 🔄 Pending Features

### 1. VIES VAT Validation
- [ ] Add EU VIES check for international VAT numbers
- [ ] Implement fallback: if Greek VAT not found in AADE, try VIES
- [ ] Handle different country VAT prefixes (DE, FR, IT, etc.)
- [ ] Display appropriate error messages for invalid EU VAT numbers
- [ ] Cache VIES results to avoid rate limiting

### 2. Field Validation
- [ ] Add real-time VAT format validation (9 digits for Greek VAT)
- [ ] Show visual feedback during AADE/VIES lookup (loading spinner)
- [ ] Display success/error messages after lookup
- [ ] Validate required fields before allowing checkout

### 3. UI/UX Improvements
- [ ] Add loading indicator during VAT lookup
- [ ] Show tooltip/help text explaining what ΑΦΜ is
- [ ] Improve error messages (Greek translations)
- [ ] Add "retry" button if AADE lookup fails
- [ ] Make fields readonly after auto-fill (with option to edit)

### 4. Performance Optimization
- [ ] Increase AADE cache duration (currently 1 hour)
- [ ] Add local database caching for frequently looked up VAT numbers
- [ ] Debounce VAT input to avoid excessive API calls
- [ ] Lazy load scripts only on checkout page

### 5. Admin Features
- [ ] Add admin settings page for AADE credentials
- [ ] Option to enable/disable VIES check
- [ ] View cached VAT lookups in admin
- [ ] Export invoice/receipt data
- [ ] Statistics dashboard (how many invoices vs receipts)

### 6. Testing
- [ ] Write unit tests for AADE API integration
- [ ] Write unit tests for VIES integration
- [ ] Test with different WordPress versions
- [ ] Test with different WooCommerce versions
- [ ] Test browser compatibility (Chrome, Firefox, Safari, Edge)

### 7. Documentation
- [ ] Add inline code documentation (PHPDoc)
- [ ] Create user guide (Greek)
- [ ] Create developer documentation
- [ ] Add screenshots to README
- [ ] Video tutorial for setup

### 8. Code Quality
- [ ] Fix PSR-4 autoloading warnings
- [ ] Add proper error logging
- [ ] Implement proper error handling for API failures
- [ ] Add TypeScript for JavaScript files
- [ ] Set up automated tests (CI/CD)

### 9. Accessibility
- [ ] Add ARIA labels to all form fields
- [ ] Ensure keyboard navigation works properly
- [ ] Test with screen readers
- [ ] Add focus management for error states

### 10. Security
- [ ] Audit nonce implementation
- [ ] Add rate limiting for AADE API calls
- [ ] Sanitize all user inputs
- [ ] Escape all outputs
- [ ] Regular security updates

## 🐛 Known Issues

- [ ] None currently

## 💡 Ideas for Future

- [ ] Support for multiple invoice types (Retail, Corporate, Export)
- [ ] PDF invoice generation
- [ ] Email invoice to customer automatically
- [ ] Integration with Greek myDATA API
- [ ] Support for credit notes
- [ ] Multi-currency support for EU invoices

---

**Last Updated:** October 18, 2025
