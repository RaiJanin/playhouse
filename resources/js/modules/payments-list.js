const bookingInput = document.getElementById('booking-lookup');

if (bookingInput) {
    bookingInput.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        const value = bookingInput.value.trim();
        if (!value) return;

        window.location.href = `/payments/${encodeURIComponent(value)}`;
    });
}
