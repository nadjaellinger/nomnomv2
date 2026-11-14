(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-plan-recipe-picker]').forEach(setupPicker);
    });

    function setupPicker(root) {
        var form = root.closest('form');
        var input = root.querySelector('[data-plan-recipe-search]');
        var results = root.querySelector('[data-plan-recipe-results]');
        var chips = root.querySelector('[data-plan-recipe-chips]');
        var chipsEmpty = root.querySelector('[data-plan-recipe-chips-empty]');
        var status = root.querySelector('[data-plan-recipe-status]');
        var more = root.querySelector('[data-plan-recipe-more]');
        var fallbackWrap = form ? form.querySelector('[data-plan-recipe-fallback-wrap]') : null;
        var fallback = form ? form.querySelector('[data-plan-recipe-fallback]') : null;
        var searchUrl = root.dataset.searchUrl;

        if (!form || !input || !results || !chips || !searchUrl) {
            return;
        }

        var selected = new Map();
        var items = [];
        var activeIndex = -1;
        var debounceTimer = null;
        var requestController = null;
        var requestSequence = 0;

        function announce(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function setMore(visible) {
            if (more) {
                more.hidden = !visible;
            }
        }

        function updateChips() {
            chips.replaceChildren();

            selected.forEach(function (recipe, id) {
                var chip = document.createElement('span');
                chip.className = 'plan-chip';

                var label = document.createElement('span');
                label.className = 'plan-chip-label';
                label.textContent = recipe.name;

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'plan-chip-remove';
                remove.setAttribute('aria-label', recipe.name + ' entfernen');
                remove.textContent = '\u00d7';
                remove.addEventListener('click', function () {
                    removeRecipe(id);
                });

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'recipeIds[]';
                hidden.value = id;

                chip.append(label, remove, hidden);
                chips.appendChild(chip);
            });

            if (chipsEmpty) {
                chipsEmpty.hidden = selected.size > 0;
            }

            announce(
                selected.size === 0
                    ? 'Keine Rezepte ausgew\u00e4hlt.'
                    : selected.size + (selected.size === 1 ? ' Rezept ausgew\u00e4hlt.' : ' Rezepte ausgew\u00e4hlt.')
            );
        }

        function renderResults() {
            results.replaceChildren();

            if (items.length === 0) {
                var empty = document.createElement('p');
                empty.className = 'plan-picker-empty';
                empty.setAttribute('role', 'presentation');
                empty.textContent = input.value.trim() === ''
                    ? 'Keine Rezepte vorhanden.'
                    : 'Keine Treffer.';
                results.appendChild(empty);
                activeIndex = -1;
                input.removeAttribute('aria-activedescendant');
                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            items.forEach(function (recipe, index) {
                var option = document.createElement('button');
                option.type = 'button';
                option.className = 'plan-result';
                option.id = 'plan-result-' + recipe.id;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.tabIndex = -1;
                option.addEventListener('click', function () {
                    selectRecipe(recipe);
                });
                option.addEventListener('mousemove', function () {
                    setActive(index);
                });

                var name = document.createElement('span');
                name.className = 'plan-result-name';
                name.textContent = recipe.name;
                option.appendChild(name);

                if (recipe.owner) {
                    var owner = document.createElement('span');
                    owner.className = 'plan-result-owner';
                    owner.textContent = 'von ' + recipe.owner;
                    option.appendChild(owner);
                }

                results.appendChild(option);
            });

            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            setActive(0);
        }

        function setActive(index) {
            activeIndex = index;
            var options = results.querySelectorAll('.plan-result');

            options.forEach(function (option, i) {
                var isActive = i === index;
                option.classList.toggle('is-active', isActive);
                option.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            if (index >= 0 && options[index]) {
                input.setAttribute('aria-activedescendant', options[index].id);
                options[index].scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function closeResults() {
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function selectRecipe(recipe) {
            selected.set(String(recipe.id), recipe);
            updateChips();
            input.value = '';
            input.setCustomValidity('');

            var wasFocused = document.activeElement === input;
            closeResults();
            input.focus();
            if (wasFocused) {
                search('');
            }
        }

        function removeRecipe(id) {
            selected.delete(String(id));
            updateChips();
            input.focus();
        }

        function search(query) {
            if (requestController) {
                requestController.abort();
            }
            requestController = new AbortController();
            var sequence = ++requestSequence;

            fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' },
                signal: requestController.signal
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Suche fehlgeschlagen');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (sequence !== requestSequence) {
                        return;
                    }
                    items = (Array.isArray(data.recipes) ? data.recipes : []).filter(function (recipe) {
                        return !selected.has(String(recipe.id));
                    });
                    setMore(Boolean(data.hasMore));
                    renderResults();
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    items = [];
                    setMore(false);
                    results.replaceChildren();

                    var message = document.createElement('p');
                    message.className = 'plan-picker-error';
                    message.setAttribute('role', 'presentation');
                    message.textContent = 'Die Rezeptsuche ist gerade nicht erreichbar.';
                    results.appendChild(message);
                    results.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                    announce('Die Rezeptsuche ist gerade nicht erreichbar.');
                });
        }

        input.addEventListener('input', function () {
            input.setCustomValidity('');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                search(input.value.trim());
            }, 180);
        });

        input.addEventListener('focus', function () {
            if (results.hidden) {
                search(input.value.trim());
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (results.hidden) {
                    search(input.value.trim());
                    return;
                }
                if (items.length) {
                    setActive((activeIndex + 1) % items.length);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (items.length) {
                    setActive(activeIndex <= 0 ? items.length - 1 : activeIndex - 1);
                }
            } else if (event.key === 'Enter') {
                if (!results.hidden && activeIndex >= 0 && items[activeIndex]) {
                    event.preventDefault();
                    selectRecipe(items[activeIndex]);
                } else if (input.value.trim() !== '') {
                    event.preventDefault();
                    clearTimeout(debounceTimer);
                    search(input.value.trim());
                }
            } else if (event.key === 'Escape') {
                closeResults();
            } else if (event.key === 'Backspace' && input.value === '' && selected.size > 0) {
                var ids = Array.from(selected.keys());
                removeRecipe(ids[ids.length - 1]);
            }
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeResults();
            }
        });

        form.addEventListener('submit', function (event) {
            if (selected.size === 0) {
                event.preventDefault();
                input.setCustomValidity('Bitte w\u00e4hle mindestens ein Rezept aus.');
                input.reportValidity();
                announce('Bitte w\u00e4hle mindestens ein Rezept aus.');
                input.focus();
            }
        });

        updateChips();

        root.hidden = false;
        if (fallbackWrap) {
            fallbackWrap.hidden = true;
        }
        if (fallback) {
            fallback.hidden = true;
            fallback.disabled = true;
        }
    }
})();
