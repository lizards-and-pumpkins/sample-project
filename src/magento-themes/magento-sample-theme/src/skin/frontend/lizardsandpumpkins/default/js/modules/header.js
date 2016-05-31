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
        searchAutoSuggest = document.querySelector('.form-search-suggest'),
        suggestHighlights = document.querySelectorAll('.form-search-suggest .name span');

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

    // fake search auto suggest
    searchInput.addEventListener('keyup', function () {
        var searchTerm = this.value;

        [].forEach.call(suggestHighlights, function (i) {
            i.innerHTML = searchTerm;
        });

        if (this.value.length >= 2) {
            searchAutoSuggest.className += ' active';
        }
    });

    // hide fake auto suggest
    body.addEventListener('click', function (e) {
        if (e.target !== searchInput) {
            searchAutoSuggest.className = searchAutoSuggest.className.replace(/\bactive\b/ig, ' ');
        }
    })
})();
