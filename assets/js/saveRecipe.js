document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('recipeForm');

  if (form) {
    form.addEventListener('blur', function (event) {
      var target = event.target;

      if (target.matches('#title, #description, #instructions, .ingredient-name, .ingredient-amount, .ingredient-unit')) {
        checkField(target);
      }
    }, true); // Use capture phase for event delegation

    form.addEventListener('click', function (event) {
      var target = event.target;

      if (target.matches('.removeIngredient')) {
        removeIngredient(event);
      }
    });
  }

  var saveButton = document.getElementById('saveRecipe');
  var createButton = document.getElementById('createRecipe');

  if (saveButton) {
    saveButton.addEventListener('click', function (event) {
      console.log('Save button clicked');
      event.preventDefault();

      if (!checkAllFieldsFilled()) {
        console.log('Not all fields are filled out');
        return;
      }
      let {data, recipeId} = getFormData();
      saveRecipe(data, recipeId);
    });
  }

  if (createButton) {
    createButton.addEventListener('click', function (event) {
      console.log('Create button clicked');
      event.preventDefault();

      if (!checkAllFieldsFilled()) {
        console.log('Not all fields are filled out');
        return;
      }
      let data = getFormData();
      createRecipe(data.data);
    });
  }
});

function createRecipe(data) {
  fetch('/rezept/neu', {
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

function getFormData() {
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
  var recipeId = document.getElementById('recipeId').value || 0;
  return {data, recipeId};
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

function removeIngredient(event) {
  var ingredient = event.target.closest('.ingredient');
  ingredient.remove();
}
