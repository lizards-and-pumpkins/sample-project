require(
    ['lib/domReady', 'product_grid', 'filter_navigation', 'pagination', 'lib/url', 'lib/translate', 'common'],
    function (domReady, productGrid, filterNavigation, pagination, url, translate) {

        domReady(function () {
            renderContent();
            filterNavigation.renderLayeredNavigation(filterNavigationJson, '#filter-navigation');
        });

        function renderContent() {
            var container = document.querySelector('.page-content');

            if (typeof totalNumberOfResults === 'undefined' || 0 === totalNumberOfResults) {
                container.appendChild(createEmptyListingBlock());
                return;
            }

            container.appendChild(createToolbar());
            productGrid.renderGrid(productListingJson, container);
            container.appendChild(pagination.renderPagination(totalNumberOfResults, productsPerPage));
        }

        function createEmptyListingBlock() {
            var emptyListingMessage = document.createElement('P');
            emptyListingMessage.className = 'note-msg';
            emptyListingMessage.textContent = translate('There are no products matching the selection.');

            return emptyListingMessage;
        }

        function createProductsPerPageElement(numberOfProductsPerPage) {
            if (true === numberOfProductsPerPage['selected']) {
                return document.createTextNode(numberOfProductsPerPage['number']);
            }

            var link = document.createElement('A'),
                newUrl = url.updateQueryParameter('limit', numberOfProductsPerPage['number']);

            link.textContent = numberOfProductsPerPage['number'];
            link.href = url.removeQueryParameterFromUrl(newUrl, pagination.getPaginationQueryParameterName());

            return link;
        }

        function createSortingSelect() {
            var sortingSelect = document.createElement('SELECT');

            if (typeof window.availableSortOrders !== 'object' || typeof window.selectedSortOrder !== 'object') {
                return sortingSelect;
            }

            sortingSelect.addEventListener('change', function () {
                document.location.href = this.value
            }, true);

            window.availableSortOrders.map(function (sortBy) {
                sortingSelect.appendChild(createSortingSelectOption(sortBy));
            });

            return sortingSelect;
        }

        function createSortingSelectOption(sortBy) {
            var sortingOption = document.createElement('OPTION'),
                newUrl = url.updateQueryParameters({"order": sortBy['code'], "dir": sortBy['selectedDirection']});

            sortingOption.textContent = translate(sortBy['code']);
            sortingOption.value = url.removeQueryParameterFromUrl(newUrl, pagination.getPaginationQueryParameterName());
            sortingOption.selected = isSelectedSortBy(sortBy);

            return sortingOption;
        }

        function isSelectedSortBy(sortBy) {
            return sortBy['code'] === window.selectedSortOrder['code'] &&
                sortBy['selectedDirection'] === window.selectedSortOrder['selectedDirection'];
        }

        function createToolbar() {
            var toolbar = document.createElement('DIV');
            toolbar.className = 'toolbar';
            toolbar.appendChild(createTotalProductsNumberBlock());
            toolbar.appendChild(createSortingBlock());
            toolbar.appendChild(createProductsPerPageBlock());

            return toolbar;
        }

        function createTotalProductsNumberBlock() {
            var amount = document.createElement('P');
            amount.className = 'amount';
            amount.textContent = translate('%s Item(s)', totalNumberOfResults);

            return amount;
        }

        function createSortingBlock() {
            var select = createSortingSelect();

            var sortByLabel = document.createElement('LABEL');
            sortByLabel.textContent = translate('Sort By');

            var sortBy = document.createElement('DIV');
            sortBy.className = 'sort-by';

            sortBy.appendChild(sortByLabel);
            sortBy.appendChild(select);

            return sortBy;
        }

        function createProductsPerPageBlock() {
            var productPerPage = document.createElement('DIV'),
                productPerPageLabel = document.createElement('LABEL');
            productPerPage.className = 'limiter';
            productPerPageLabel.textContent = translate('Items') + ': ';
            productPerPage.appendChild(productPerPageLabel);

            window.productsPerPage.map(function (numberOfProductsPerPage, index) {
                productPerPage.appendChild(createProductsPerPageElement(numberOfProductsPerPage));

                if (index < productsPerPage.length - 1) {
                    var separator = document.createTextNode(' | ');
                    productPerPage.appendChild(separator);
                }
            });

            return productPerPage;
        }
    }
);
