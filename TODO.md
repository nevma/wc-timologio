# TODO List - NVM Timologio Plugin

## ✅ Completed Features

### 1. VIES VAT Validation

- [x] Add EU VIES check for international VAT numbers ✅
- [x] Handle different country VAT prefixes (DE, FR, IT, etc.) ✅
- [x] Auto-detect VAT type: Greek (AADE) vs EU (VIES) ✅
- [x] Integrate VIES API for EU VAT validation ✅
- [x] Return standardized data format for both AADE and VIES ✅

### 2. AADE VAT Validation

- [x] Greek VAT number validation (9 digits, EL, GR prefixes) ✅
- [x] AADE SOAP API integration ✅
- [x] Auto-populate company details from AADE ✅
- [x] Cache AADE results (1 hour) ✅

## 🔄 Pending Features

### 3. Field Validation

- [x] Auto-detect and validate VAT format ✅
- [x] Show visual feedback during AADE/VIES lookup (loading spinner) ✅
- [x] Display success/error messages after lookup ✅

### 4. UI/UX Improvements

- [x] Remove "(προαιρετικό)" optional label from fields ✅
- [x] Clean console output (removed debug logging) ✅
- [x] Add loading indicator during VAT lookup ✅
- [ ] Show tooltip/help text explaining what ΑΦΜ is
- [ ] Improve error messages (Greek translations)
- [ ] Add "retry" button if AADE lookup fails
- [ ] Make fields readonly after auto-fill (with option to edit)

### 5. Performance Optimization

- [x] Cache AADE results (1 hour) ✅
- [x] Add VIES result caching (1 hour for valid, 15 min for invalid) ✅
- [x] Lazy load scripts only on checkout page ✅

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

## 📊 **Summary**

**Completed:** 16 tasks ✅
**Pending:** 35+ tasks

**Recent Achievements:**

- ✅ VIES VAT validation for EU countries
- ✅ AADE VAT validation for Greece
- ✅ Auto-detection of VAT type (AADE vs VIES)
- ✅ React-compatible field updates for WooCommerce blocks
- ✅ Removed optional labels from fields
- ✅ Silent operation (no console logging)
- ✅ Loading spinner during VAT lookup
- ✅ Success/error messages after VAT validation (Greek)
- ✅ VIES result caching (1 hour valid, 15 min invalid)
- ✅ Lazy loading scripts (checkout page only)

---

**Last Updated:** October 19, 2025
