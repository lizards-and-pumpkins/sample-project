( function () {
    'use strict';

    var languageSwitch = document.getElementById('language-switcher'),
        body = document.querySelector('body'),
        header = document.querySelector('header'),
        content = document.getElementById('content'),
        closeLanguageSwitch = document.querySelector('.language-switch-close'),
        prevPos = document.body.scrollTop,
        headerHeight = header.offsetHeight,
        mobileNavTrigger = document.getElementById('mobile-menu'),
        pageWrapper = document.getElementById('page'),
        scrollWrapper = document.querySelector('.scroll-wrapper'),
        dropDownToggles = document.getElementsByClassName('nav-level-1'),
        mobileSearchTrigger = document.getElementById('mobile-search'),
        searchForm = document.getElementById('search_mini_form'),
        searchInput = document.getElementById('search'),
        autosuggestionBox = document.getElementById('search-autosuggestion');

    languageSwitch.addEventListener('click', function () {
        toggleClassName(header, 'pushed');
        toggleClassName(content, 'pushed');
    });

    closeLanguageSwitch.addEventListener('click', function () {
        header.className = header.className.replace(/\bpushed\b/ig, ' ');
        content.className = content.className.replace(/\bpushed\b/ig, ' ');
    });

    window.addEventListener('scroll', function () {
        var currentPos = document.body.scrollTop;

        if (currentPos > prevPos && currentPos > headerHeight) {
            header.className += ' hidden';
        } else {
            header.className = header.className.replace(/\bpushed\b|\bhidden\b/ig, ' ');
        }

        content.className = content.className.replace(/\bpushed\b/ig, ' ');

        prevPos = document.body.scrollTop;
    });

    mobileNavTrigger.addEventListener('click', function () {
        toggleClassName(pageWrapper, 'pushed');
        toggleClassName(scrollWrapper, 'visible');
        toggleClassName(header, 'active');
        toggleClassName(body, 'no-flow');
    });

    function toggleClassName(element, className) {
        var classNameRegExp = new RegExp('\\b' + className + '\\b', 'ig');

        if (element.className.match(classNameRegExp)) {
            element.className = element.className.replace(classNameRegExp, ' ');
            return;
        }

        element.className += ' ' + className;
    }

    function toggleDropdown() {
        toggleClassName(this, 'active');
    }

    for (var i = 0; i < dropDownToggles.length; i++) {
        dropDownToggles[i].addEventListener('click', toggleDropdown, false);
    }

    mobileSearchTrigger.addEventListener('click', function () {
        toggleClassName(searchForm, 'active');
    });

    searchInput.addEventListener('keyup', function () {
        var searchTerm = this.value;

        if (searchTerm.length < 2) {
            destroyAutosuggestionBox();
            return;
        }

        callAjax(Mage.baseUrl + 'api/product/?q=' + searchInput.value + '&limit=5', function (responseText) {
            if (!isJson(responseText)) {
                console.log('Not a valid JSON:' + responseText);
                return;
            }

            var searchResults = JSON.parse(responseText);
            renderAutosuggestionLayer(searchResults)
        }, 'application/vnd.lizards-and-pumpkins.product.v1+json');
    });

    body.addEventListener('click', function (e) {
        if (e.target !== searchInput) {
            destroyAutosuggestionBox();
        }
    });

    function destroyAutosuggestionBox() {
        autosuggestionBox.innerHTML = '';
    }

    function callAjax(url, callback, acceptHeaderValue) {
        var xmlhttp = new XMLHttpRequest;
        xmlhttp.onreadystatechange = function () {
            if (4 === xmlhttp.readyState && 200 === xmlhttp.status) {
                callback(xmlhttp.responseText);
            }
        };
        xmlhttp.open('GET', url, true);
        if (acceptHeaderValue) {
            xmlhttp.setRequestHeader('Accept', acceptHeaderValue);
        }
        xmlhttp.send();
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
        numResultsLink.textContent = searchInput.value + ' (' + totalNumberOfResults + ' Results)';
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
        moreResultsLink.textContent = 'More Results';
        moreResults.className = 'no-thumbnail';
        moreResults.appendChild(moreResultsLink);

        return moreResults;
    }
})();
