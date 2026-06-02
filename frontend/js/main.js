// frontend/js/main.js
// Handles UI dynamics, modal controls, and dynamic rental fee calculations

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-dismiss Alert Messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 2. Booking Modal Logic
    const rentButtons = document.querySelectorAll('.btn-rent-trigger');
    const bookingModal = document.getElementById('bookingModal');
    const modalClose = document.getElementById('modalClose');

    if (rentButtons.length > 0 && bookingModal) {
        const modalCarName = document.getElementById('modalCarName');
        const modalCarPrice = document.getElementById('modalCarPrice');
        const modalCarId = document.getElementById('modalCarId');
        const modalHireDays = document.getElementById('modalHireDays');
        const modalTotalPrice = document.getElementById('modalTotalPrice');
        const modalDailyCharge = document.getElementById('modalDailyCharge');

        rentButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const carId = btn.getAttribute('data-id');
                const brand = btn.getAttribute('data-brand');
                const model = btn.getAttribute('data-model');
                const charge = parseFloat(btn.getAttribute('data-charge'));

                modalCarId.value = carId;
                modalCarName.textContent = `${brand} ${model}`;
                modalCarPrice.textContent = `KSh ${new Intl.NumberFormat().format(charge)}`;
                modalDailyCharge.value = charge;

                // Reset inputs
                modalHireDays.value = 1;
                calculateTotal(1, charge);

                // Show modal
                bookingModal.classList.add('active');
            });
        });

        // Close Modal
        modalClose.addEventListener('click', () => {
            bookingModal.classList.remove('active');
        });

        // Close when clicking outside content
        bookingModal.addEventListener('click', (e) => {
            if (e.target === bookingModal) {
                bookingModal.classList.remove('active');
            }
        });

        // Dynamic Calculation
        modalHireDays.addEventListener('input', () => {
            const days = parseInt(modalHireDays.value) || 0;
            const charge = parseFloat(modalDailyCharge.value) || 0;
            calculateTotal(days, charge);
        });

        function calculateTotal(days, charge) {
            const total = days * charge;
            modalTotalPrice.textContent = `KSh ${new Intl.NumberFormat().format(total)}`;
        }
    }

    // 3. Confirm Delete Alert
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const confirmed = confirm("Are you sure you want to delete this car? This action cannot be undone.");
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
});
