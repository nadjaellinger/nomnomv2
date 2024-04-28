document.addEventListener('DOMContentLoaded', function () {
  var saveButton = document.getElementById('saveRecipe');
  if (saveButton) {
    saveButton.addEventListener('click', function (event) {
      //prevent default action
      event.preventDefault();
      var name = document.getElementById('title').value ?? '';
      var description = document.getElementById('description').value ?? '';
      var instructions = document.getElementById('instructions').value ?? '';

      var data = {
        name: name,
        description: description,
        instructions: instructions
      };
      var recipeId = 1; // Replace this with the actual recipe ID
      saveRecipe(data, recipeId);
      console.log('Save button clicked!');
    });
  }
});

function saveRecipe(data, recipeId) {
  fetch(`/rezept/${recipeId}/bearbeiten`, {  // Using template literals to insert the recipeId
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(data)
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.statusText);
    }
    console.log(response);
    return response;
  })
  .then(data => {
    console.log('Success:', data);
    window.location.href = '/rezept/' + recipeId;  // Redirect using the returned ID
  })
  .catch((error) => {
    console.error('Error:', error);
  });
}