define(['lib/ajax', 'lib/translate'], function (callAjax, translate) {
    var autosuggestionBox = document.getElementById('search-autosuggestion'),
        searchInput = document.getElementById('search'),
        minimalLength = 2;

    searchInput.addEventListener('input', function () {
        searchInput.parentNode.querySelector('button').disabled = searchInput.value.length === 0;

        if (searchInput.value.length < minimalLength) {
            autosuggestionBox.innerHTML = '';
            return;
        }

        callAjax(baseUrl + 'api/product/?q=' + searchInput.value + '&limit=5', function (responseText) {
            if (!isJson(responseText)) {
                console.log('Not a valid JSON:' + responseText);
                return;
            }

            var searchResults = JSON.parse(responseText);
            renderAutosuggestionLayer(searchResults)
        }, 'application/vnd.lizards-and-pumpkins.product.v1+json');
    }, true);

    searchInput.addEventListener('blur', function () {
//            autosuggestionBox.innerHTML = '';
    }, true);

    searchInput.addEventListener('keydown', function (event) {

        var keyUp = 38,
            keyDown = 40,
            keyEnter = 13;


        if (keyUp === event.which || keyDown === event.which || keyEnter === event.which) {
            var items = Array.prototype.slice.call(autosuggestionBox.querySelectorAll('li')),
                selectedItemIndex = getSelectedAutosuggestionItemIndex(items);
        }

        if (keyUp === event.which || keyDown === event.which) {

            if (selectedItemIndex > -1 && items.length > 1) {
                items[selectedItemIndex].className = items[selectedItemIndex].className.replace(/\bselected\b/i, '');
            }

            items[getNewSelectedItemIndex(event, selectedItemIndex, items.length)].className += ' selected';
        }

        if (keyEnter === event.which && -1 !== selectedItemIndex) {
            document.location.href = items[selectedItemIndex].querySelector('a').href;
            event.preventDefault();
        }
    });

    function getSelectedAutosuggestionItemIndex(items) {
        return items.reduce(function (carry, item, index) {
            if (carry === -1 && item.className.match(/\bselected\b/i)) {
                return index;
            }
            return carry;
        }, -1);
    }

    function getNewSelectedItemIndex(event, selectedItemIndex, numItems) {
        if (38 === event.which) {
            return selectedItemIndex > 0 ? selectedItemIndex - 1 : numItems - 1;
        }

        if (40 === event.which) {
            return selectedItemIndex + 1 < numItems ? selectedItemIndex + 1 : 0;
        }
    }

    function isJson(string) {
        try {
            JSON.parse(string);
        } catch (e) {
            return false;
        }
        return true;
    }

    function renderAutosuggestionLayer(searchResults) {
        var searchResultsPageUrl = window.baseUrl + 'catalogsearch/result?q=' + searchInput.value,
            resultsList = document.createElement('UL');

        resultsList.appendChild(createNumResultsRow(searchResultsPageUrl, searchResults.total));

        if (searchResults.total > 0) {

            searchResults.data.map(function (productData) {
                resultsList.appendChild(createSearchResultRow(productData));
            });

            resultsList.appendChild(createMoreResultsRow(searchResultsPageUrl));
        }

        autosuggestionBox.innerHTML = '';
        autosuggestionBox.appendChild(resultsList);
    }

    function createNumResultsRow(searchResultsPageUrl, totalNumberOfResults) {
        var numResults = document.createElement('LI'),
            numResultsLink = document.createElement('A');

        numResultsLink.href = searchResultsPageUrl;
        numResultsLink.textContent = translate(searchInput.value + ' (' + totalNumberOfResults + ' Results)');
        numResults.className = 'no-thumbnail';
        numResults.appendChild(numResultsLink);

        return numResults;
    }

    function createSearchResultRow(productData) {
        var searchResultRow = document.createElement('LI'),
            searchResultRowLink = document.createElement('A'),
            searchResultImage = document.createElement('IMG');

        searchResultRowLink.href = window.baseUrl + productData.attributes.url_key;
        searchResultImage.src = productData.images['search-autosuggestion'][0]['url'];

        searchResultRowLink.appendChild(searchResultImage);
        searchResultRowLink.appendChild(document.createTextNode(productData.attributes.name));

        searchResultRow.appendChild(searchResultRowLink);

        return searchResultRow;
    }

    function createMoreResultsRow(searchResultsPageUrl) {
        var moreResults = document.createElement('LI'),
            moreResultsLink = document.createElement('A');

        moreResultsLink.href = searchResultsPageUrl;
        moreResultsLink.textContent = translate('More Results');
        moreResults.className = 'no-thumbnail';
        moreResults.appendChild(moreResultsLink);

        return moreResults;
    }
});
