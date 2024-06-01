document.addEventListener('DOMContentLoaded', function () {
  var saveButton = document.getElementById('saveRecipe');
  if (saveButton) {
    saveButton.addEventListener('click', function (event) {
      //prevent default action
      event.preventDefault();
      var name = document.getElementById('title').value ?? '';
      var description = document.getElementById('description').value ?? '';
      var instructions = document.getElementById('instructions').value ?? '';
      var ingredientList = document.getElementById('ingredientList');
      var ingredients = [];
      ingredientList.querySelectorAll('.ingredient').forEach(function (ingredient) {
        var ingredientName = ingredient.querySelector('.ingredient-name').value ?? '';
        var ingredientAmount = ingredient.querySelector('.ingredient-amount').value ?? '';
        var ingredientUnit = ingredient.querySelector('.ingredient-unit').value ?? '';
        var id = ingredient.querySelector('.ingredient-id').value ?? 0;
        ingredients.push({
          name: ingredientName,
          amount: ingredientAmount,
          unit: ingredientUnit,
          id: id
        });
      });

      var data = {
        name: name,
        description: description,
        instructions: instructions,
        ingredients: ingredients
      };
      var recipeId = document.getElementById('recipeId').value;
      saveRecipe(data, recipeId);
    });
  }
});

function saveRecipe(data, recipeId) {
  fetch(`/rezept/${recipeId}/bearbeiten`, {  
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