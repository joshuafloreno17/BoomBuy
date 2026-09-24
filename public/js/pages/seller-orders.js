function openRejectModal(requestId) {
    const overlay = document.getElementById('rejectModalOverlay');
    const form = document.getElementById('rejectForm');

    // Build the reject route dynamically using the base URL pattern.
    form.action = overlay.dataset.baseUrl + "/" + requestId + "/reject";

    document.getElementById('seller_note').value = '';
    overlay.classList.add('active');
}

function closeRejectModal() {
    document.getElementById('rejectModalOverlay').classList.remove('active');
}

// Close modal when clicking outside the box
document.getElementById('rejectModalOverlay').addEventListener('click', function (e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
