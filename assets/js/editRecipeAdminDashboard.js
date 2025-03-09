document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('admin-table');
    if (form) {
        form.addEventListener('click', function (event) {
            //if the id is deleteRecipe
            if (event.target.id === 'editRecipe') {
                event.preventDefault();
                let recipeId = event.target.getAttribute('data-id');
                window.location.href = '/rezept/' + recipeId + '/bearbeiten';
            }
        });
    }
}
);