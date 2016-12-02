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
