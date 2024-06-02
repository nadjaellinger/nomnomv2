document.addEventListener('DOMContentLoaded', function () {
    let button = document.getElementById('addIngredient');
    

    if (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            let ingredientList = document.getElementById('ingredientList');
            fetchIngredientTemplate().then(template => {
                ingredientList.insertAdjacentHTML('beforeend', template);
            });
        });
    }

    async function fetchIngredientTemplate() {
        try {
            let response = await fetch('/ingredient/template');
            if (response.ok) {
                return await response.text();
            } else {
                console.error('Failed to fetch ingredient template');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
});
