define(['../../src/lizards-and-pumpkins/pub/js/filter_navigation'], function (FilterNavigation) {
    describe('Filter Navigation', function () {

        it('makes no modifications to parent node if passed JSON is not an object', function () {
            var parentNode = document.createElement('DIV');
            FilterNavigation.renderLayeredNavigation('', parentNode);
            expect(parentNode.innerHTML).toBe('');
        });

        it('makes no modification to parent node if parent node is not a DOM element', function () {
            var parentNode = null;
            FilterNavigation.renderLayeredNavigation({}, parentNode);
            expect(parentNode).toBe(null);
        });

        it('makes no modification to parent node if no filters are defined', function () {
            var parentNode = document.createElement('DIV'),
                filtersJson = {};
            FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);
            expect(parentNode.innerHTML).toBe('');
        });

        describe('default filter', function() {
            var parentNode = document.createElement('DIV'),
                filterCode = 'foo',
                filtersJson = {};

            filtersJson[filterCode] = [{"value" : "bar", "count" : 1}, {"value" : "baz", "count" : 24}];

            FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

            it ('can be created', function () {
                expect(parentNode.childNodes.length).toBe(2);
            });

            var filterTitle = parentNode.childNodes[0],
                filterOptions = parentNode.childNodes[1];

            it ('has a title', function () {
                expect(filterTitle.tagName).toBe('DIV');
                expect(filterTitle.className).toBe('filter-title');
                expect(filterTitle.textContent).toBe(filterCode);
            });

            it ('has options', function () {
                expect(filterOptions.tagName).toBe('DIV');
                expect(filterOptions.className).toBe('filter-container');
                expect(filterOptions.childNodes.length).toBe(1);

                var filterOptionsList = filterOptions.childNodes[0];

                expect(filterOptionsList.tagName).toBe('OL');
                expect(filterOptionsList.className).toBe('filter-content filter-' + filterCode);

                expect(filterOptionsList.childNodes.length).toBe(2);

                Array.prototype.map.call(filterOptionsList.childNodes, function (filterOption, index) {
                    expect(filterOption.tagName).toBe('LI');
                    expect(filterOption.childNodes.length).toBe(1);

                    var filterOptionLink = filterOption.childNodes[0],
                        expectedValue = filtersJson[filterCode][index]['value'],
                        expectedCount = filtersJson[filterCode][index]['count'];

                    expect(filterOptionLink.tagName).toBe('A');
                    expect(filterOptionLink.href).toBe(document.location.href + '?' + filterCode + '=' + expectedValue);

                    expect(filterOptionLink.childNodes.length).toBe(2);

                    expect(filterOptionLink.childNodes[0].textContent).toBe(expectedValue);
                    expect(filterOptionLink.childNodes[1].tagName).toBe('SPAN');
                    expect(filterOptionLink.childNodes[1].textContent).toBe('(' + expectedCount + ')');
                });
            });
        });

        describe('color filter', function () {
            var parentNode = document.createElement('DIV'),
                filterCode = 'color',
                filtersJson = {};

            filtersJson[filterCode] = [{"value" : "white", "count" : 2}, {"value" : "red", "count" : 15}];

            FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

            it ('can be created', function () {
                expect(parentNode.childNodes.length).toBe(2);
            });

            var filterTitle = parentNode.childNodes[0],
                filterOptions = parentNode.childNodes[1];

            it ('has a title', function () {
                expect(filterTitle.tagName).toBe('DIV');
                expect(filterTitle.className).toBe('filter-title');
                expect(filterTitle.textContent).toBe(filterCode);
            });

            it ('has options', function () {
                expect(filterOptions.tagName).toBe('DIV');
                expect(filterOptions.className).toBe('filter-container');
                expect(filterOptions.childNodes.length).toBe(1);

                var filterOptionsList = filterOptions.childNodes[0];

                expect(filterOptionsList.tagName).toBe('OL');
                expect(filterOptionsList.className).toBe('filter-content filter-' + filterCode);

                expect(filterOptionsList.childNodes.length).toBe(2);

                Array.prototype.map.call(filterOptionsList.childNodes, function (filterOption, index) {
                    expect(filterOption.tagName).toBe('LI');
                    expect(filterOption.childNodes.length).toBe(1);

                    var filterOptionLink = filterOption.childNodes[0],
                        expectedValue = filtersJson[filterCode][index]['value'];

                    expect(filterOptionLink.tagName).toBe('A');
                    expect(filterOptionLink.className).toBe(expectedValue);
                    expect(filterOptionLink.href).toBe(document.location.href + '?' + filterCode + '=' + expectedValue);
                });
            });
        });

        describe('price filter', function () {
            var parentNode = document.createElement('DIV'),
                filterCode = 'price',
                filtersJson = {};

            filtersJson[filterCode] = [
                {"value": "0,00 € - 20,00 €", "count": 2},
                {"value": "20,00 € - 40,00 €", "count": 15}
            ];

            FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

            it ('can be created', function () {
                expect(parentNode.childNodes.length).toBe(2);
            });

            var filterTitle = parentNode.childNodes[0],
                filterOptions = parentNode.childNodes[1];

            it ('has a title', function () {
                expect(filterTitle.tagName).toBe('DIV');
                expect(filterTitle.className).toBe('filter-title');
                expect(filterTitle.textContent).toBe(filterCode);
            });

            it ('has options', function () {
                expect(filterOptions.tagName).toBe('DIV');
                expect(filterOptions.className).toBe('filter-container');
                expect(filterOptions.childNodes.length).toBe(1);

                var filterOptionsList = filterOptions.childNodes[0];

                expect(filterOptionsList.tagName).toBe('OL');
                expect(filterOptionsList.className).toBe('filter-content filter-' + filterCode);

                expect(filterOptionsList.childNodes.length).toBe(2);

                Array.prototype.map.call(filterOptionsList.childNodes, function (filterOption, index) {
                    expect(filterOption.tagName).toBe('LI');
                    expect(filterOption.childNodes.length).toBe(1);

                    var filterOptionLink = filterOption.childNodes[0],
                        expectedValue = filtersJson[filterCode][index]['value'],
                        expectedCount = filtersJson[filterCode][index]['count'];

                    expect(filterOptionLink.tagName).toBe('A');

                    expect(filterOptionLink.childNodes.length).toBe(2);

                    expect(filterOptionLink.childNodes[0].textContent).toBe(expectedValue);
                    expect(filterOptionLink.childNodes[1].tagName).toBe('SPAN');
                    expect(filterOptionLink.childNodes[1].textContent).toBe('(' + expectedCount + ')');
                });
            });

            it('ignores an options with invalid valus', function () {
                var parentNode = document.createElement('DIV'),
                    filtersJson = {
                        "price": [
                            {"value": "0,00 € - 20,00 €", "count": 2},
                            {"value": "invalid value", "count": 15}
                        ]
                    };

                FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

                var filterOptions = parentNode.querySelectorAll('div > ol.filter-price > li > a');
                expect(filterOptions.length).toBe(1);
                expect(filterOptions[0].childNodes[0].textContent).toBe('0,00 € - 20,00 €');
            });

            it('accepts prices with comma decimal separator', function () {
                var parentNode = document.createElement('DIV'),
                    filtersJson = {"price": [{"value": "0,00 € - 20,00 €", "count": 2}]};

                FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

                var filterOptions = parentNode.querySelectorAll('div > ol.filter-price > li > a');
                expect(filterOptions.length).toBe(1);
                expect(filterOptions[0].href).toBe(document.location.href + '?price=0.00-20.00');
            });

            it('accepts prices with period decimal separator', function () {
                var parentNode = document.createElement('DIV'),
                    filtersJson = {"price": [{"value": "$0.00 - $20.00", "count": 2}]};

                FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

                var filterOptions = parentNode.querySelectorAll('div > ol.filter-price > li > a');
                expect(filterOptions.length).toBe(1);
                expect(filterOptions[0].href).toBe(document.location.href + '?price=0.00-20.00');
            });

            it('accepts prices with period thousands point comma decimal separator', function () {
                var parentNode = document.createElement('DIV'),
                    filtersJson = {"price": [{"value": "¥1.000.000,00 - ¥2.000.000,00", "count": 2}]};

                FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

                var filterOptions = parentNode.querySelectorAll('div > ol.filter-price > li > a');
                expect(filterOptions.length).toBe(1);
                expect(filterOptions[0].href).toBe(document.location.href + '?price=1000000.00-2000000.00');
            });

            it('accepts prices with comma thousands point period decimal separator', function () {
                var parentNode = document.createElement('DIV'),
                    filtersJson = {"price": [{"value": "¥1,000,000.00 - ¥2,000,000.00", "count": 2}]};

                FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

                var filterOptions = parentNode.querySelectorAll('div > ol.filter-price > li > a');
                expect(filterOptions.length).toBe(1);
                expect(filterOptions[0].href).toBe(document.location.href + '?price=1000000.00-2000000.00');
            });
        });

        it('ignores filters which have no options', function () {
            var parentNode = document.createElement('DIV'),
                filtersJson = {"foo" : [], "bar" : [{"value" : "baz", "count" : 1}, {"value" : "qux", "count" : 24}]};
            FilterNavigation.renderLayeredNavigation(filtersJson, parentNode);

            expect(parentNode.querySelector('.filter-foo')).toBeNull();
            expect(parentNode.querySelector('.filter-bar')).not.toBeNull();
        });
    })
});
