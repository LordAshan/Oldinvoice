document.addEventListener('DOMContentLoaded', () => {
    /**
     * Updates the expire date based on the selected duration.
     */
    function updateExpireDate() {
        const purchaseDateInput = document.getElementById('purchase_date');
        const expireDurationSelect = document.getElementById('expire_duration');
        const expireDateInput = document.getElementById('expire_date');

        const purchaseDateValue = purchaseDateInput.value;
        const duration = expireDurationSelect.value;

        if (!purchaseDateValue || !duration) {
            expireDateInput.value = '';
            return;
        }

        let purchaseDate = new Date(purchaseDateValue);
        if (isNaN(purchaseDate)) {
            expireDateInput.value = '';
            return;
        }

        switch (duration) {
            case '1_month':
                purchaseDate.setMonth(purchaseDate.getMonth() + 1);
                break;
            case '2_months':
                purchaseDate.setMonth(purchaseDate.getMonth() + 2);
                break;
            case '3_months':
                purchaseDate.setMonth(purchaseDate.getMonth() + 3);
                break;
            case '6_months':
                purchaseDate.setMonth(purchaseDate.getMonth() + 6);
                break;
            case '1_year':
                purchaseDate.setFullYear(purchaseDate.getFullYear() + 1);
                break;
            case '2_years':
                purchaseDate.setFullYear(purchaseDate.getFullYear() + 2);
                break;
            case '3_years':
                purchaseDate.setFullYear(purchaseDate.getFullYear() + 3);
                break;
            case '5_years':
                purchaseDate.setFullYear(purchaseDate.getFullYear() + 5);
                break;
            default:
                expireDateInput.value = '';
                return;
        }

        const year = purchaseDate.getFullYear();
        const month = String(purchaseDate.getMonth() + 1).padStart(2, '0');
        const day = String(purchaseDate.getDate()).padStart(2, '0');

        const formattedExpireDate = `${year}-${month}-${day}`;
        expireDateInput.value = formattedExpireDate;
    }

    /**
     * Fetches product details based on the selected product name and updates the form fields.
     * Also, shows or hides the Upgrader Key field for specific products.
     *
     * @param {HTMLElement} selectElement - The select element for product names.
     */
    function fetchProductDetails(selectElement) {
        const selectedProductName = selectElement.value;

        // Ensure 'products' array is available
        if (typeof products === 'undefined' || !Array.isArray(products)) {
            return;
        }

        // Find the product object from the products array
        const product = products.find(p => p.name === selectedProductName);

        if (product) {
            const productItem = selectElement.closest('.product-item');

            // Update hidden fields
            const priceInput = productItem.querySelector('.price-input');
            const priceDisplay = productItem.querySelector('.price span');

            if (priceInput && priceDisplay) {
                priceInput.value = product.price;
                priceDisplay.textContent = parseFloat(product.price).toFixed(2);
                calculateTotal();
            }

            // Show or hide the Upgrader Key field based on the selected product
            const upgraderKeyField = productItem.querySelector('.upgrader-key-field');
            if (upgraderKeyField) {
                if (product.product_number.toLowerCase() === 'p0001') {
                    upgraderKeyField.classList.remove('d-none');
                } else {
                    upgraderKeyField.classList.add('d-none');
                    upgraderKeyField.querySelector('.upgrader-key').value = '';
                }
            }
        }
    }

    /**
     * Calculates the subtotal and total based on selected products, quantities, and discounts.
     */
    function calculateTotal() {
        let subtotal = 0;
        const productItems = document.querySelectorAll('.product-item');

        productItems.forEach(item => {
            const priceInput = item.querySelector('.price-input');
            const quantityInput = item.querySelector('.quantity');

            const price = parseFloat(priceInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 1;
            const totalItemPrice = price * quantity;
            subtotal += totalItemPrice;
        });

        const discountInput = document.getElementById('discount');
        const discount = parseFloat(discountInput.value) || 0;
        const total = subtotal - (subtotal * (discount / 100));

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
    }

    /**
     * Adds a new product entry to the invoice form.
     */
    function addProduct() {
        const productList = document.getElementById('product-list');
        const productItem = document.createElement('div');
        productItem.className = 'product-item border p-3 mb-3 position-relative';

        // Remove Product Button
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn-close position-absolute top-0 end-0 m-2';
        removeButton.setAttribute('aria-label', 'Remove Product');
        removeButton.onclick = function() {
            removeProduct(this);
        };
        productItem.appendChild(removeButton);

        // Row for Product Details
        const row = document.createElement('div');
        row.className = 'row g-3';

        // Product Name Dropdown
        const productNameCol = document.createElement('div');
        productNameCol.className = 'col-md-6';
        const productNameLabel = document.createElement('label');
        productNameLabel.className = 'form-label';
        productNameLabel.textContent = 'Product Name *';
        const productNameSelect = document.createElement('select');
        productNameSelect.className = 'form-select product-name';
        productNameSelect.name = 'product_name[]';
        productNameSelect.required = true;
        productNameSelect.onchange = function() {
            fetchProductDetails(this);
        };
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select a product';
        productNameSelect.appendChild(defaultOption);
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.name;
            option.textContent = product.name;
            productNameSelect.appendChild(option);
        });
        productNameCol.appendChild(productNameLabel);
        productNameCol.appendChild(productNameSelect);
        row.appendChild(productNameCol);

        // Quantity Input
        const quantityCol = document.createElement('div');
        quantityCol.className = 'col-md-3';
        const quantityLabel = document.createElement('label');
        quantityLabel.className = 'form-label';
        quantityLabel.textContent = 'Quantity *';
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.className = 'form-control quantity';
        quantityInput.name = 'quantity[]';
        quantityInput.value = '1';
        quantityInput.min = '1';
        quantityInput.required = true;
        quantityInput.oninput = calculateTotal;
        quantityCol.appendChild(quantityLabel);
        quantityCol.appendChild(quantityInput);
        row.appendChild(quantityCol);

        // Price Display
        const priceCol = document.createElement('div');
        priceCol.className = 'col-md-3';
        const priceLabel = document.createElement('label');
        priceLabel.className = 'form-label';
        priceLabel.textContent = 'Price';
        const priceInput = document.createElement('input');
        priceInput.type = 'hidden';
        priceInput.className = 'form-control price-input';
        priceInput.name = 'price[]';
        const priceDisplay = document.createElement('p');
        priceDisplay.className = 'form-control-plaintext';
        priceDisplay.innerHTML = 'LKR <span class="price">0.00</span>';
        priceCol.appendChild(priceLabel);
        priceCol.appendChild(priceInput);
        priceCol.appendChild(priceDisplay);
        row.appendChild(priceCol);

        // Upgrader Key Field (Hidden by Default)
        const upgraderKeyCol = document.createElement('div');
        upgraderKeyCol.className = 'col-12 upgrader-key-field d-none';
        const upgraderKeyLabel = document.createElement('label');
        upgraderKeyLabel.className = 'form-label';
        upgraderKeyLabel.textContent = 'Upgrader Key';
        const upgraderKeyInput = document.createElement('input');
        upgraderKeyInput.type = 'text';
        upgraderKeyInput.className = 'form-control upgrader-key';
        upgraderKeyInput.name = 'upgrader_key[]';
        upgraderKeyInput.placeholder = 'Enter Upgrader Key';
        upgraderKeyCol.appendChild(upgraderKeyLabel);
        upgraderKeyCol.appendChild(upgraderKeyInput);
        row.appendChild(upgraderKeyCol);

        productItem.appendChild(row);
        productList.appendChild(productItem);
    }

    /**
     * Removes a product entry from the invoice form.
     *
     * @param {HTMLElement} button - The remove button that was clicked.
     */
    function removeProduct(button) {
        const productItem = button.closest('.product-item');
        if (productItem) {
            productItem.remove();
            calculateTotal();
        }
    }

    /**
          * Resets the form after submission, clearing all fields and resetting to the initial state.
     */
    function resetForm() {
        const form = document.getElementById('invoice-form');
        form.reset();
        calculateTotal();
        updateExpireDate();

        // Hide all Upgrader Key fields and clear their values
        const upgraderKeyFields = document.querySelectorAll('.upgrader-key-field');
        upgraderKeyFields.forEach(field => {
            field.classList.remove('d-block');
            field.classList.add('d-none');
            const upgraderKeyInput = field.querySelector('.upgrader-key');
            if (upgraderKeyInput) {
                upgraderKeyInput.value = '';
            }
        });

        // Reset Product Items to initial state
        const productList = document.getElementById('product-list');
        productList.innerHTML = `
            <div class="product-item border p-3 mb-3 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Remove Product" onclick="removeProduct(this)"></button>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <select class="form-select product-name" name="product_name[]" onchange="fetchProductDetails(this)" required>
                            <option value="">Select a product</option>
                            ${products.map(product => `<option value="${product.name}">${product.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity" name="quantity[]" value="1" min="1" required oninput="calculateTotal()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price</label>
                        <input type="hidden" class="form-control price-input" name="price[]">
                        <p class="form-control-plaintext">LKR <span class="price">0.00</span></p>
                    </div>
                    <div class="col-12 upgrader-key-field d-none">
                        <label class="form-label">Upgrader Key</label>
                        <input type="text" class="form-control upgrader-key" name="upgrader_key[]" placeholder="Enter Upgrader Key">
                    </div>
                </div>
            </div>`;
    }

    /**
     * Handles the form submission by resetting the form after a short delay.
     * This ensures that the form data is submitted before the form is cleared.
     */
    function handleFormSubmission() {
        const form = document.getElementById('invoice-form');
        form.addEventListener('submit', function(event) {
            // Delay reset to ensure form data is sent
            setTimeout(() => {
                resetForm();
            }, 1000);
        });
    }

    // Initialize event listeners and calculations
    function initialize() {
        calculateTotal();
        updateExpireDate();
        handleFormSubmission();
    }

    // Initialize the script
    initialize();

    // Expose functions to the global scope for dynamic elements
    window.updateExpireDate = updateExpireDate;
    window.fetchProductDetails = fetchProductDetails;
    window.calculateTotal = calculateTotal;
    window.addProduct = addProduct;
    window.removeProduct = removeProduct;
});
