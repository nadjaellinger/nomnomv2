// assets/js/saveButton.js
document.addEventListener('DOMContentLoaded', function() {
    const saveButton = document.getElementById('saveRecipe');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            // Add your save functionality here
            console.log('Save button clicked!');
        });
    }
});
