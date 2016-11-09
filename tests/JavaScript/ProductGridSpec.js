define(['../../src/lizards-and-pumpkins/pub/js/product_grid'], function (ProductGrid) {
    var gridPlaceholder,
        testProductName = 'foo',
        testProductUrlKey = 'foo.html',
        testProductBrand = 'bar',
        testProductGenders = ['male'],
        testProductImageUrl = 'http://example.com/foo.png',
        testProductImageLabel = 'foo',
        testProductPrice = '$18.00';

    function getTestProductData() {
        return {
            "attributes": {
                "name": testProductName,
                "url_key": testProductUrlKey,
                "gender": testProductGenders,
                "brand": [testProductBrand],
                "price": testProductPrice,
                "raw_price": '1800',
                "price_base_unit": '100'
            },
            "images": {
                "medium": [
                    {
                        "url": testProductImageUrl,
                        "label": testProductImageLabel
                    }
                ]
            }
        };
    }

    function getTestProductDataWithSpecialPrice(specialPrice, rawSpecialPrice) {
        var productData = getTestProductData();
        productData['attributes']['special_price'] = specialPrice;
        productData['attributes']['raw_special_price'] = rawSpecialPrice;

        return productData;
    }

    function getTestProductDataWithProductNewInformation() {
        var productData = getTestProductData();
        productData['attributes']['news_from_date'] = '2000-01-01 00:00:00';
        productData['attributes']['news_to_date'] = '3000-01-01 00:00:00';

        return productData;
    }

    describe('Product grid', function () {
        beforeEach(function () {
            gridPlaceholder = document.createElement('DIV');
            baseUrl = 'http://example.com/';
        });

        it('is not rendered if grid placeholder is not a DOM node', function () {
            var productGridJson = [],
                placeholder = [],
                result = ProductGrid.renderGrid(productGridJson, placeholder);

            expect(result).toBeUndefined();
        });

        it('is an empty unordered list if there are no products', function () {
            var productGridJson = [];
            ProductGrid.renderGrid(productGridJson, gridPlaceholder);
            expect(gridPlaceholder.innerHTML).toMatch(/^<ul[^>]*><\/ul>$/);
        });

        it('has a "products-grid" class', function () {
            var productGridJson = [];
            ProductGrid.renderGrid(productGridJson, gridPlaceholder);
            expect(gridPlaceholder.querySelector('ul').className).toMatch(/\bproducts-grid\b/);
        });

        it('contains a product', function () {
            var productGridJson = [getTestProductData()];
            ProductGrid.renderGrid(productGridJson, gridPlaceholder);
            expect(gridPlaceholder.querySelectorAll('ul > li').length).toBe(1);
        });

        describe('product', function () {
            it('is wrapped into link', function () {
                var productGridJson = [getTestProductData()];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);
                var links = gridPlaceholder.querySelectorAll('ul > li > a');

                Array.prototype.map.call(links, function (link) {
                    expect(link.href).toBe(baseUrl + testProductUrlKey);
                });
            });

            it('has a "new" badge if it is new', function () {
                var productGridJson = [getTestProductDataWithProductNewInformation(), getTestProductData()];

                ProductGrid.renderGrid(productGridJson, gridPlaceholder);
                var gridItemContainers = gridPlaceholder.querySelectorAll('ul > li'),
                    gridItemContainersArray = Array.prototype.slice.call(gridItemContainers),
                    newBadgeHtml = '<div class="label new"><span>NEW</span></div>';

                expect(gridItemContainersArray[0].innerHTML).toContain(newBadgeHtml);
                expect(gridItemContainersArray[1].innerHTML).not.toContain(newBadgeHtml);
            });

            it('has an image', function () {
                var productGridJson = [getTestProductData()];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                var image = gridPlaceholder.querySelector('ul > li > a > img');
                expect(image.src).toBe(testProductImageUrl);
                expect(image.alt).toBe(testProductImageLabel);
            });

            it('has a title wrapped into H2 tag', function () {
                var productGridJson = [getTestProductData()];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                var title = gridPlaceholder.querySelector('ul > li > a > h2');
                expect(title.textContent).toBe(testProductName);
            });

            it('has a price but no special price', function () {
                var productGridJson = [getTestProductData()];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                var price = gridPlaceholder.querySelector('ul > li > a > div.regular-price');
                expect(price.textContent).toContain(testProductPrice);
                expect(gridPlaceholder.querySelector('ul > li > a > div.special-price')).toBeNull();
            });

            it('has a price and a special price', function () {
                var specialPrice = '$17.00',
                    rawSpecialPrice = '1700',
                    productGridJson = [getTestProductDataWithSpecialPrice(specialPrice, rawSpecialPrice)];

                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                var price = gridPlaceholder.querySelector('ul > li > a > div.old-price');
                expect(price.textContent).toContain(testProductPrice);

                var specialPriceElement = gridPlaceholder.querySelector('ul > li > a > div.special-price');
                expect(specialPriceElement.textContent).toContain(specialPrice);
            });

            it('has no saving label if there is no special price', function () {
                var productGridJson = [getTestProductData()];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                expect(gridPlaceholder.querySelector('ul > li > a > div.label.save')).toBeNull();
            });

            it('has no saving label if discount is less than 5%', function () {
                var productGridJson = [getTestProductDataWithSpecialPrice('%17.99', '1799')];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                expect(gridPlaceholder.querySelector('ul > li > a > div.label.save')).toBeNull();
            });


            it('has a saving label if saving is greater or equals to 5%', function () {
                var productGridJson = [getTestProductDataWithSpecialPrice('%17.00', '1700')];
                ProductGrid.renderGrid(productGridJson, gridPlaceholder);

                var savingLabel = gridPlaceholder.querySelector('ul > li > a > div.label.sale > span');
                expect(savingLabel.textContent).toBe('-6%');
            });
        });
    });
});
