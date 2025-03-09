document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('admin-table');
    if (form) {
        form.addEventListener('click', function (event) {
            //if the id is deleteRecipe
            if (event.target.id === 'deleteRecipe') {
                event.preventDefault();
                let recipeId = event.target.getAttribute('data-id');
                deleteRecipe(recipeId);
            }
            if (event.target.id === 'editRecipe') {
                event.preventDefault();
                let recipeId = event.target.getAttribute('data-id');
                window.location.href = '/rezept/' + recipeId + '/bearbeiten';
            }
        });
    }
}
);

function deleteRecipe(recipeId) {
    fetch('/admin/dashboard', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ recipeId: recipeId, action: 'delete' }),
    })
        .then(response => { // Check if the response is ok
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            console.log(response);
            return response.json(); // Return the response as JSON
        })
        .then(data => {
            console.log('Success:', data.redirect);
            if (data.redirect)
                window.location.href = data.redirect;  // Redirect using the returned ID
            else
                window.location.reload();
            console.log(data);
        })
        .catch((error) => {
            console.error('Error:', error);
        });
}