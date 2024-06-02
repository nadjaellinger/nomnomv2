document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('recipeForm');

  if (form) {
    form.addEventListener('blur', function (event) {
      var target = event.target;

      if (target.matches('#title, #description, #instructions, .ingredient-name, .ingredient-amount, .ingredient-unit')) {
        checkField(target);
      }
    }, true); // Use capture phase for event delegation
  }

  var saveButton = document.getElementById('saveRecipe');

  if (saveButton) {
    saveButton.addEventListener('click', function (event) {
      console.log('Save button clicked');
      event.preventDefault();

      if (!checkAllFieldsFilled()) {
        console.log('Not all fields are filled out');
        return;
      }

      var name = document.getElementById('title').value;
      var description = document.getElementById('description').value;
      var instructions = document.getElementById('instructions').value;
      var ingredients = [];
      document.querySelectorAll('.ingredient').forEach(function (ingredient) {
        var ingredientName = ingredient.querySelector('.ingredient-name').value || '';
        var ingredientAmount = ingredient.querySelector('.ingredient-amount').value || '';
        var ingredientUnit = ingredient.querySelector('.ingredient-unit').value || '';
        var id = ingredient.querySelector('.ingredient-id').value || null;
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

function checkAllFieldsFilled() {
  var fields = document.querySelectorAll('#title, #description, #instructions, .ingredient-name, .ingredient-amount, .ingredient-unit');
  var isValid = true;
  for (var i = 0; i < fields.length; i++) {
    checkField(fields[i]);
    if (fields[i].classList.contains('is-invalid')) {
      isValid = false;
    }
  }
  return isValid;
}

function checkField(field) {
  if (field.value.trim() === '') {
    field.classList.add('is-invalid');
  } else {
    if (field.classList.contains('ingredient-amount') && isNaN(field.value)) {
      field.classList.add('is-invalid');
    } else {
    field.classList.remove('is-invalid');
    }
  }
}
