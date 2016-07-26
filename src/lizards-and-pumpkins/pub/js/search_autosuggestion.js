define(['lib/ajax'], function (callAjax) {
    var autosuggestionBox = document.getElementById('search-autosuggestion'),
        searchInput = document.querySelector('#search_mini_form input'),
        minimalLength = 2;

    searchInput.addEventListener('input', function () {
        searchInput.parentNode.querySelector('button').disabled = searchInput.value.length === 0;

        if (searchInput.value.length < minimalLength) {
            autosuggestionBox.innerHTML = '';
            return;
        }

        callAjax(baseUrl + 'catalogsearch/suggest?q=' + searchInput.value, function (responseText) {
            autosuggestionBox.innerHTML = responseText;
        });
    }, true);

    searchInput.addEventListener('blur', function () {
//            autosuggestionBox.innerHTML = '';
    }, true);
});
