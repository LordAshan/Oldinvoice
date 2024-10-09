// Public/js/index.js

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap's Modals
    const editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));

    // Event listener for edit buttons to populate the modal with product data
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productNumber = this.getAttribute('data-number');
            const productName = this.getAttribute('data-name');
            const productPrice = this.getAttribute('data-price');

            // Populate the modal fields with product data
            document.getElementById('modal_product_id').value = productId;
            document.getElementById('modal_product_number').value = productNumber;
            document.getElementById('modal_name').value = productName;
            document.getElementById('modal_price').value = productPrice;

            // Show the modal
            editProductModal.show();
        });
    });

    // Bootstrap's custom validation
    const forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
});
