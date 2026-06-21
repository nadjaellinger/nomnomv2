document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ImportRecipeForm');
    if (form) {
        var saveButton = document.getElementById('addButton');
        var textField = document.getElementById('text');
        var urlField = document.getElementById('url');
        var imageField = document.getElementById('image');
        if (saveButton) {
            saveButton.addEventListener('click', function (event) {
                event.preventDefault();
                let formData = new FormData(form);
                formData.append('text', textField.value);
                formData.append('url', urlField.value);
                if (imageField.files.length > 0 && imageField.files[0] !== undefined && imageField.files[0] !== null) {
                    formData.append('image', imageField.files[0]);
                }
                importRecipe(formData);
            });
        }
    }
});

function importRecipe(formData) {
    console.log(formData);
    fetch('/importRecipe', {
        method: 'POST',
        body: formData
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
        console.error('No redirect URL returned');
        console.log(data);
    })
    .catch((error) => {
        console.error('Error:', error);
    });
}
