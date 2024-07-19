document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('AIaddForm');
    if (form) {
        var saveButton = document.getElementById('addButton');
        var textField = document.getElementById('text');
        var UrlField = document.getElementById('url');
        if (saveButton) {
            saveButton.addEventListener('click', function (event) {
                event.preventDefault();
                var data = {
                    text: textField.value,
                    url: UrlField.value
                };
                importRecipe(data);
            });
        }
    }
});

function importRecipe(data) {
    console.log(data);
    fetch('/importRecipe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
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
