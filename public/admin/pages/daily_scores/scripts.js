function attachEventListeners() {
    const podSelect = document.getElementById('pod');
    if (podSelect) {
        podSelect.addEventListener('change', function() {
            document.getElementById('selectionForm').submit();
        });
    }

    const dateSelect = document.getElementById('date');
    if (dateSelect) {
        dateSelect.addEventListener('change', function() {
            document.getElementById('selectionForm').submit();
        });
    }
}

document.addEventListener('DOMContentLoaded', attachEventListeners);