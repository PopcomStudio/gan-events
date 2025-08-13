document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="event[type]"]');
    const momentsSection = document.querySelector('[data-type="standard_plus_moments"]').closest('.form-group');
    const addMomentButton = momentsSection.querySelector('.arkounay-ux-collection-add');

    function toggleMomentsSection() {
        if (typeSelect.value === 'standard_plus_moments') {
            momentsSection.style.display = 'block';
            if (addMomentButton) {
                addMomentButton.style.display = 'block';
            }
        } else {
            momentsSection.style.display = 'none';
            if (addMomentButton) {
                addMomentButton.style.display = 'none';
            }
        }
    }

    // Initial state
    toggleMomentsSection();

    // Listen for changes
    typeSelect.addEventListener('change', toggleMomentsSection);
}); 