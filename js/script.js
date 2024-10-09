document.addEventListener('DOMContentLoaded', () => {
    /**
     * Adds a new product selection row to the invoice form.
     */
    function addProduct() {
        const productList = document.getElementById('product-list');
        const productItem = document.createElement('div');
        productItem.classList.add('product-item');
        productItem.innerHTML = `
            <label for="product_name">Product Name:</label>
            <select class="product-name" name="product_name[]" onchange="fetchProductDetails(this)" required>
                <option value="">Select a product</option>
                ${populateProductOptions()}
            </select>

            <input type="hidden" class="product-number" name="product_number[]">
            <input type="hidden" class="price-input" name="price[]">

            <label for="quantity">Quantity:</label>
            <input type="number" class="quantity" name="quantity[]" value="1" min="1" required oninput="calculateTotal()"><br><br>

            <p class="price">Price: <span>0.00</span></p>
        `;
        productList.appendChild(productItem);

        // Attach event listeners to the new quantity input
        productItem.querySelector('.quantity').addEventListener('input', calculateTotal);
    }

    /**
     * Populates the product selection options dynamically based on the 'products' array.
     *
     * @return {string} HTML string of option elements.
     */
    function populateProductOptions() {
        // Escape HTML to prevent XSS
        return products.map(product => `<option value="${escapeHtml(product.name)}">${escapeHtml(product.name)}</option>`).join('');
    }

    /**
     * Calculates and updates the subtotal and total based on selected products and discount.
     */
    function calculateTotal() {
        const productItems = document.querySelectorAll('.product-item');
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        let subtotal = 0;

        productItems.forEach(item => {
            const quantity = parseInt(item.querySelector('.quantity').value) || 0;
            const price = parseFloat(item.querySelector('.price span').textContent) || 0;
            subtotal += price * quantity;
        });

        // Update the subtotal display
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        
        // Calculate the total after applying the discount
        const discountAmount = subtotal * (discount / 100);
        const total = subtotal - discountAmount;

        // Update the total display
        document.getElementById('total').textContent = total.toFixed(2);
    }

    /**
     * Fetches product details (product_number and price) via AJAX based on the selected product name.
     *
     * @param {HTMLElement} element The select element that triggered the change.
     */
    window.fetchProductDetails = function(element) {
        const productName = element.value;
        const productItem = element.closest('.product-item');

        if (productName) {
            fetch(`fetch_price.php?product_name=${encodeURIComponent(productName)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        productItem.querySelector('.product-number').value = data.product_number;
                        productItem.querySelector('.price span').textContent = parseFloat(data.price).toFixed(2);
                        productItem.querySelector('.price-input').value = data.price;
                        calculateTotal(); // Recalculate total after price update
                    } else {
                        alert(data.message);
                        element.value = ""; // Reset the select if product not found
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    alert('An error occurred while fetching product details.');
                });
        }
    }

    /**
     * Updates the expire date field based on the selected purchase date and duration.
     */
    window.updateExpireDate = function() {
        const purchaseDate = document.getElementById('purchase_date').value;
        const duration = document.getElementById('expire_duration').value;
        const expireDateField = document.getElementById('expire_date');

        if (purchaseDate && duration) {
            const date = new Date(purchaseDate);
            switch (duration) {
                case '1_month':
                    date.setMonth(date.getMonth() + 1);
                    break;
                case '2_months':
                    date.setMonth(date.getMonth() + 2);
                    break;
                case '3_months':
                    date.setMonth(date.getMonth() + 3);
                    break;
                case '6_months':
                    date.setMonth(date.getMonth() + 6);
                    break;
                case '1_year':
                    date.setFullYear(date.getFullYear() + 1);
                    break;
                case '2_years':
                    date.setFullYear(date.getFullYear() + 2);
                    break;
                case '3_years':
                    date.setFullYear(date.getFullYear() + 3);
                    break;
                case '5_years':
                    date.setFullYear(date.getFullYear() + 5);
                    break;
                default:
                    expireDateField.value = '';
                    return;
            }
            const formattedDate = date.toISOString().split('T')[0];
            expireDateField.value = formattedDate;
        } else {
            expireDateField.value = '';
        }
    }

    /**
     * Escapes HTML special characters to prevent XSS.
     *
     * @param {string} text The text to escape.
     * @return {string} The escaped text.
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Expose functions to the global scope
    window.addProduct = addProduct;
    window.updateExpireDate = updateExpireDate;

    // Attach event listeners
    document.getElementById('discount').addEventListener('input', calculateTotal);
    document.querySelectorAll('.quantity').forEach(input => input.addEventListener('input', calculateTotal));
});

document.getElementById('invoice-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const formData = new FormData(this);

    fetch('invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank'); // Open PDF in a new tab
    })
    .catch(error => {
        console.error('Error generating invoice PDF:', error);
        alert('An error occurred while generating the invoice. Please try again.');
    });
});
