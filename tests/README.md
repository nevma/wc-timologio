# Testing Guide for WooCommerce Timologio Plugin

This directory contains unit tests for the WooCommerce Timologio plugin using PHPUnit.

## Setup

### Install Dependencies

First, install the testing dependencies via Composer:

```bash
cd /path/to/timologio
composer install
```

This will install:
- PHPUnit 9.5 - Testing framework
- Mockery - Mocking library
- Brain Monkey - WordPress function mocking

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
./prefixed/vendor/bin/phpunit
```

### Run Specific Test File

```bash
./prefixed/vendor/bin/phpunit tests/Unit/AadeTest.php
```

### Run Tests with Coverage Report

Generate an HTML coverage report:

```bash
composer test-coverage
```

The coverage report will be generated in the `coverage/` directory. Open `coverage/index.html` in your browser to view it.

## Test Structure

```
tests/
├── bootstrap.php          # Test bootstrap file
├── Unit/                  # Unit tests directory
│   ├── AadeTest.php      # Tests for Aade class
│   ├── ViesTest.php      # Tests for Vies class
│   └── TimologioTest.php # Tests for main Timologio class
└── README.md             # This file
```

## What's Being Tested

### AadeTest.php
- AADE API integration (Greek Tax Authority)
- VAT number validation
- XML parsing and data extraction
- AJAX request handling
- Block-based vs. classic checkout detection

### ViesTest.php
- VIES API integration (EU VAT validation)
- VAT number sanitization
- AJAX handlers
- JavaScript output

### TimologioTest.php
- Plugin initialization
- Singleton pattern
- WordPress hooks registration
- Asset enqueueing
- Plugin dependency checks
- HPOS compatibility

## Writing New Tests

When adding new features, create corresponding test files in the `tests/Unit/` directory:

1. **Extend the base TestCase**:
   ```php
   use PHPUnit\Framework\TestCase;

   class YourTest extends TestCase {
       // Your tests here
   }
   ```

2. **Mock WordPress functions** using Brain Monkey:
   ```php
   use Brain\Monkey\Functions;

   Functions\when('get_option')->justReturn('value');
   ```

3. **Follow the naming convention**:
   - Test files: `ClassNameTest.php`
   - Test methods: `test_what_it_does()`

## Mocking WordPress Functions

Brain Monkey allows you to mock WordPress functions without a full WordPress installation:

```php
// Simple mock
Functions\when('get_option')->justReturn('some_value');

// Conditional mock
Functions\when('is_checkout')->justReturn(true);

// Expect function to be called
Functions\expect('wp_enqueue_script')
    ->once()
    ->with('script-handle', Mockery::type('string'));
```

## CI/CD Integration

These tests can be integrated into your CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
- name: Install dependencies
  run: composer install

- name: Run tests
  run: composer test
```

## Tips

1. **Isolation**: Each test should be independent and not rely on other tests
2. **Setup/Teardown**: Use `setUp()` and `tearDown()` methods to prepare test environment
3. **Assertions**: Use descriptive assertions that make failures clear
4. **Mock External APIs**: Always mock external API calls to avoid network dependencies
5. **Coverage**: Aim for high test coverage, especially for critical business logic

## Troubleshooting

### Tests Not Found
Make sure you've run `composer install` and the autoloader is properly configured.

### WordPress Function Errors
Use Brain Monkey to mock all WordPress functions used in your code:
```php
Functions\when('function_name')->justReturn($value);
```

### Memory Issues
If you encounter memory issues, increase PHP memory limit:
```bash
php -d memory_limit=512M ./prefixed/vendor/bin/phpunit
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Brain Monkey Documentation](https://brain-wp.github.io/BrainMonkey/)
- [Mockery Documentation](http://docs.mockery.io/)
