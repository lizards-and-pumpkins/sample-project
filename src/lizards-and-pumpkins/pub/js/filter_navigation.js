define(['lib/url', 'pagination', 'lib/translate'], function (url, pagination, translate) {

    function getSelectedFilterValues(filterCode) {
        var rawSelectedValues = url.getQueryParameterValue(filterCode);

        if (null === rawSelectedValues) {
            return [];
        }

        return rawSelectedValues.split(',');
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function scrollFirstSelectedFilterOptionsIntoView(filterContainer) {
        if (domElementHasVerticalScrolling(filterContainer)) {
            var activeOption = filterContainer.querySelector('.active');

            if (null !== activeOption) {
                filterContainer.scrollTop = activeOption.offsetTop;
            }
        }
    }

    function domElementHasVerticalScrolling(domElement) {
        return domElement.scrollHeight > domElement.offsetHeight;
    }

    function createFilterHeading(filterCode) {
        var heading = document.createElement('DIV');
        heading.className = 'filter-title';
        heading.textContent = translate(filterCode);
        return heading;
    }

    function isDomNode(parentNode) {
        return null !== parentNode && parentNode.nodeType > 0;
    }

    function getNormalizedRanges(filterOption) {
        return filterOption.value.split(' - ').reduce(function (carry, range) {
            var trimmedValue = range.replace(/^\D*|\D*$/g, '');

            if (trimmedValue.match(/^[0-9.]+,\d+$/)) {
                var valueWithoutThousandsPoint = trimmedValue.replace(/\./g, '');
                return carry.concat([valueWithoutThousandsPoint.replace(/,/, '.')]);
            }

            if (trimmedValue.match(/^[0-9,]+\.\d+$/)) {
                var valueWithoutThousandsComma = trimmedValue.replace(/,/g, '');
                return carry.concat([valueWithoutThousandsComma]);
            }

            return carry;
        }, []);
    }

    var FilterNavigation = {
        renderLayeredNavigation: function (filterNavigationJson, parentNode) {
            if (typeof filterNavigationJson !== 'object' || ! isDomNode(parentNode)) {
                return;
            }

            Object.keys(filterNavigationJson).map(function (filterCode) {
                if (0 === filterNavigationJson[filterCode].length) {
                    return;
                }

                var options = FilterNavigation[FilterNavigation.getFilterOptionBuilderName(filterCode)](
                    filterCode,
                    filterNavigationJson[filterCode]
                );

                var filterContainer = document.createElement('DIV');
                filterContainer.className = 'filter-container';

                var optionList = document.createElement('OL');
                optionList.className = 'filter-content filter-' + filterCode;
                options.map(function (option) { optionList.appendChild(option) });

                parentNode.appendChild(createFilterHeading(filterCode));
                filterContainer.appendChild(optionList);
                parentNode.appendChild(filterContainer);

                scrollFirstSelectedFilterOptionsIntoView(filterContainer);
            });
        },

        getFilterOptionBuilderName: function (filterCode) {
            var functionName = 'create' + capitalizeFirstLetter(filterCode) + 'FilterOptions';

            if (typeof this[functionName] === 'function') {
                return functionName;
            }

            return 'createDefaultFilterOptions';
        },

        createDefaultFilterOptions: function (filterCode, filterOptions) {
            var selectedFilterOptions = getSelectedFilterValues(filterCode);
            return filterOptions.reduce(function (carry, filterOption) {
                var option = document.createElement('LI'),
                    link = document.createElement('A'),
                    newUrl = url.toggleQueryParameter(filterCode, filterOption.value),
                    count = document.createElement('SPAN');

                count.textContent = '(' + filterOption.count + ')';

                link.appendChild(document.createTextNode(filterOption.value));
                link.appendChild(count);
                link.href = url.removeQueryParameterFromUrl(newUrl, pagination.getPaginationQueryParameterName());
                option.appendChild(link);

                if (selectedFilterOptions.indexOf(filterOption.value) !== -1) {
                    option.className = 'active';
                }

                carry.push(option);
                return carry;
            }, []);
        },

        createColorFilterOptions: function (filterCode, filterOptions) {
            var selectedColors = getSelectedFilterValues(filterCode);
            return filterOptions.reduce(function (carry, filterOption) {
                var option = document.createElement('LI'),
                    link = document.createElement('A'),
                    newUrl = url.toggleQueryParameter(filterCode, filterOption.value.toString());

                link.innerHTML = selectedColors.indexOf(filterOption.value.toString()) !== -1 ? '&#x2713;' : '&nbsp;';
                link.className = filterOption.value.toLowerCase().replace(/\s/g, '-');
                link.href = url.removeQueryParameterFromUrl(newUrl, pagination.getPaginationQueryParameterName());
                option.appendChild(link);

                carry.push(option);
                return carry;
            }, []);
        },

        createPriceFilterOptions: function (filterCode, filterOptions) {
            var selectedFilterOptions = getSelectedFilterValues(filterCode);
            return filterOptions.reduce(function (carry, filterOption) {
                if (0 === filterOption.count) {
                    return carry;
                }

                var ranges = getNormalizedRanges(filterOption);

                if (ranges.length === 0) {
                    return carry;
                }

                var parameterValue = ranges.join('-'),
                    option = document.createElement('LI'),
                    link = document.createElement('A'),
                    newUrl = url.toggleQueryParameter(filterCode, parameterValue),
                    count = document.createElement('SPAN');

                count.textContent = '(' + filterOption.count + ')';

                link.appendChild(document.createTextNode(filterOption.value));
                link.appendChild(count);
                link.href = url.removeQueryParameterFromUrl(newUrl, pagination.getPaginationQueryParameterName());
                option.appendChild(link);

                if (selectedFilterOptions.indexOf(parameterValue) !== -1) {
                    option.className = 'active';
                }

                carry.push(option);
                return carry;
            }, []);
        }
    };

    return FilterNavigation;
});
