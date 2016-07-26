require(
    ['product', 'lib/domReady', 'lib/zoom', 'lib/translate', 'common'],
    function (Product, domReady, zoom, translate) {

        var tabletWidth = 768,
            selectBoxIdPrefix = 'variation_',
            currentImage = document.createElement('IMG'),
            addToCartButton = document.createElement('BUTTON'),
            selectedProductId,
            product,
            currentGalleryImageIndex = 1,
            xDown = null,
            yDown = null;

        domReady(function () {
            product = new Product(window.product);

            renderContent();

            adjustToPageWidth();
            window.addEventListener('resize', adjustToPageWidth);
            window.addEventListener('orientationchange', adjustToPageWidth);
        });

        function renderContent() {
            var container = document.createElement('DIV');
            container.className = 'product-view';

            container.appendChild(createGallery());
            container.appendChild(createEssentials());
            container.appendChild(createDescription());
            container.appendChild(createAdditionalInformation());

            document.getElementById('content').appendChild(container);

            showNextSelectBox();
        }

        function createGallery() {
            currentImage.src = product.getImageUrlByNumber('large', 1);
            currentImage.addEventListener('touchstart', handleTouchStart, false);
            currentImage.addEventListener('touchmove', handleTouchMove, false);

            var currentImageLink = document.createElement('A');
            currentImageLink.href = product.getImageUrlByNumber('original', 1);
            currentImageLink.className = 'main-image-area';
            currentImageLink.appendChild(currentImage);

            var gallery = document.createElement('DIV');
            gallery.className = 'product-image col span_5';
            gallery.appendChild(currentImageLink);
            gallery.appendChild(createThumbnails());

            initializeImageGalleryArrows(currentImageLink);
            initializeZoom(currentImageLink);

            return gallery;
        }

        function createThumbnails() {
            var thumbnails = document.createElement('UL');
            thumbnails.className = 'more-views';

            if (product.getNumberOfImages() < 2) {
                return thumbnails;
            }

            for (var i = 1; i <= product.getNumberOfImages(); i++) {
                var thumbnailLi = document.createElement('LI'),
                    thumbnailLink = document.createElement('A'),
                    thumbnailImage = document.createElement('IMG');

                thumbnailLink.href = product.getImageUrlByNumber('original', i);
                thumbnailLink.setAttribute('data-image', product.getImageUrlByNumber('large', i));
                thumbnailImage.src = product.getImageUrlByNumber('small', i);

                thumbnailLink.addEventListener('click', function (event) {
                    event.preventDefault();

                    currentImage.src = this.getAttribute('data-image');
                    currentImage.parentNode.href = this.getAttribute('href');
                    initializeZoom();
                }, true);

                thumbnailLink.appendChild(thumbnailImage);
                thumbnailLi.appendChild(thumbnailLink);
                thumbnails.appendChild(thumbnailLi);
            }

            return thumbnails;
        }

        function createEssentials() {
            var essentials = document.createElement('DIV'),
                title = document.createElement('H1'),
                shortDescription = document.createElement('DIV');

            essentials.className = 'product-shop col span_7';

            title.textContent = product.getAttributeValue('name');

            shortDescription.className = 'short-description';
            shortDescription.textContent = product.getAttributeValue('short_description');

            initializeAddToCartButton();

            essentials.appendChild(title);
            essentials.appendChild(shortDescription);
            essentials.appendChild(createControls());
            essentials.appendChild(createPrices());
            essentials.appendChild(addToCartButton);

            return essentials;
        }

        function createPrices() {
            var priceContainer = document.createElement('DIV'),
                regularPrice = document.createElement('DIV'),
                oldPrice = document.createElement('DIV');

            regularPrice.className = 'regular-price';
            regularPrice.textContent = product.getFinalPrice();

            if (product.hasSpecialPrice()) {
                oldPrice.className = 'old-price';
                oldPrice.textContent = product.getPrice();
                regularPrice.className += ' special-price';
                priceContainer.appendChild(oldPrice);
            }

            priceContainer.className = 'price-box';
            priceContainer.appendChild(regularPrice);

            return priceContainer;
        }

        function createControls() {
            var selectsContainer = document.createElement('DIV');
            selectsContainer.id = 'selects';

            return selectsContainer;
        }

        function initializeAddToCartButton() {
            addToCartButton.appendChild(document.createTextNode(translate('Add to Cart')));
            addToCartButton.disabled = true;
            addToCartButton.addEventListener('click', function () {
                var qty = document.getElementById(selectBoxIdPrefix + 'qty').value;
                document.location.href = baseUrl + 'cart/cart/add/sku/' + selectedProductId + '/qty/' + qty + '/';
            }, true);
        }

        function createDescription() {
            var container = document.createElement('DIV'),
                title = document.createElement('H5');

            container.className = 'box-description';
            title.textContent = translate('Details');

            container.appendChild(title);
            container.appendChild(document.createTextNode(product.getAttributeValue('description')));

            return container;
        }

        function createAdditionalInformation() {
            var container = document.createElement('DIV'),
                title = document.createElement('H5'),
                table = document.createElement('TABLE');

            title.textContent = translate('Additional information');

            ['occasion', 'apparel_type', 'gender'].map(function (attributeCode) {
                if (! product.hasAttributeValue(attributeCode)) {
                    return;
                }

                var attributeValue = product.getAttributeValue(attributeCode);
                if (attributeValue !== '') {
                    table.appendChild(createAdditionalInformationTableRow(attributeCode, attributeValue));
                }
            });

            container.appendChild(title);
            container.appendChild(table);
            container.className = 'box-additional';

            return container;
        }

        function createAdditionalInformationTableRow(attributeCode, attributeValue) {
            var tr = document.createElement('TR'),
                tdKey = document.createElement('TD'),
                tdValue = document.createElement('TD');

            tdKey.textContent = translate(attributeCode);
            tdValue.textContent = attributeValue;

            tr.appendChild(tdKey);
            tr.appendChild(tdValue);
            return tr;
        }

        function deleteAllSelectBoxesAfter(previousBoxAttribute) {
            var selectCodes = getVariationAttributeSelectBoxesCodes(previousBoxAttribute);
            selectCodes.push('qty');

            selectCodes.map(function (code) {
                var selectBoxToDelete = document.getElementById(selectBoxIdPrefix + code);
                if (null !== selectBoxToDelete) {
                    selectBoxToDelete.parentNode.parentNode.removeChild(selectBoxToDelete.parentNode);
                }
            });
        }

        function getVariationAttributeSelectBoxesCodes(previousBoxAttribute) {
            return window.variation_attributes.slice(window.variation_attributes.indexOf(previousBoxAttribute) + 1);
        }

        function getSelectedVariationValues() {
            return window.variation_attributes.reduce(function (carry, attributeCode) {
                var selectBox = document.getElementById(selectBoxIdPrefix + attributeCode);
                if (null !== selectBox) {
                    carry[attributeCode] = selectBox.value;
                }
                return carry;
            }, {});
        }

        function getAssociatedProductsMatchingSelection() {
            var selectedAttributes = getSelectedVariationValues();

            return window.associated_products.filter(function (product) {
                return Object.keys(product['attributes']).reduce(function (carry, attributeCode) {
                    if (false === carry) {
                        return carry;
                    }
                    return Object.keys(selectedAttributes).reduce(function (carry, selectedAttributeCode) {
                        if (false === carry || selectedAttributeCode !== attributeCode) {
                            return carry;
                        }
                        return selectedAttributes[selectedAttributeCode] === product['attributes'][attributeCode];
                    }, carry);
                }, true);
            });
        }

        function isConfigurableProduct() {
            return (typeof variation_attributes === 'object') &&
                (typeof associated_products === 'object') &&
                (variation_attributes.length > 0);
        }

        function showNextSelectBox(previousBoxAttribute) {
            if (!isConfigurableProduct()) {
                showQuantityBoxForSimpleProduct();
                return;
            }

            selectedProductId = '';
            addToCartButton.disabled = true;

            if (previousBoxAttribute) {
                deleteAllSelectBoxesAfter(previousBoxAttribute);
                if ('' === document.getElementById(selectBoxIdPrefix + previousBoxAttribute).value) {
                    return;
                }
            }

            var matchingProducts = getAssociatedProductsMatchingSelection(),
                variationAttributeCode = variation_attributes[variation_attributes.indexOf(previousBoxAttribute) + 1];

            if (typeof variationAttributeCode === 'undefined') {
                var selectedProductStock = matchingProducts[0]['attributes']['stock_qty'];
                selectedProductId = matchingProducts[0]['product_id'];
                showQtyBoxAndReleaseAddToCartButton(selectedProductStock);
                return;
            }

            addVariationSelectBox(matchingProducts, variationAttributeCode);
        }

        function showQuantityBoxForSimpleProduct() {
            var stockQuantity = parseInt(stockQty, 10);
            if (stockQuantity > 0) {
                showQtyBoxAndReleaseAddToCartButton(stockQuantity);
            }
        }

        function showQtyBoxAndReleaseAddToCartButton(maxQty) {
            var select = createQtySelectBox(maxQty),
                styledSelect = decorateSelect(select);

            document.getElementById('selects').appendChild(styledSelect);
            addToCartButton.disabled = '';
        }

        function addVariationSelectBox(matchingProducts, variationAttributeCode) {
            var options = getVariationAttributeOptionValuesArray(matchingProducts, variationAttributeCode),
                select = createVariationSelect(variationAttributeCode, options);
            
            document.getElementById('selects').appendChild(decorateSelect(select));
        }

        function decorateSelect(select) {
            var wrapper = document.createElement('SPAN');
            wrapper.className = 'styled-select-wrapper';

            var textPlaceholder = document.createElement('SPAN');
            textPlaceholder.textContent = select.options[select.selectedIndex].textContent;

            wrapper.appendChild(select);
            wrapper.appendChild(textPlaceholder);

            select.addEventListener('change', function () {
                textPlaceholder.textContent = this.options[this.selectedIndex].textContent;
            });

            return wrapper;
        }

        function createQtySelectBox(maxQty) {
            var select = document.createElement('SELECT');

            select.id = selectBoxIdPrefix + 'qty';

            for (var i = 1; i <= maxQty; i++) {
                var option = document.createElement('OPTION');
                option.textContent = i;
                option.value = i;
                select.appendChild(option);
            }

            return select;
        }

        function getVariationAttributeOptionValuesArray(products, attributeCode) {
            return products.reduce(function (carry, associatedProduct) {
                var optionIsAlreadyPresent = false;

                for (var i = 0; i < carry.length; i++) {
                    if (carry[i]['value'] === associatedProduct['attributes'][attributeCode]) {
                        optionIsAlreadyPresent = true;

                        if (true === carry[i]['disabled'] && associatedProduct['attributes']['stock_qty'] > 0) {
                            carry[i]['disabled'] = false;
                        }
                    }
                }

                if (false === optionIsAlreadyPresent) {
                    carry.push({
                        'value': associatedProduct['attributes'][attributeCode],
                        'label': getVariationAttributeOptionLabel(associatedProduct['attributes'], attributeCode),
                        'disabled': 0 == associatedProduct['attributes']['stock_qty']
                    });
                }

                return carry;
            }, []);
        }

        function getVariationAttributeOptionLabel(productAttributes, attributeCode) {
            if (!productAttributes.hasOwnProperty(attributeCode)) {
                return '';
            }

            if ('size' === attributeCode) {
                return formatSizeOptionValue(productAttributes)
            }

            return productAttributes[attributeCode];
        }

        function formatSizeOptionValue(productAttributes) {
            if (!productAttributes.hasOwnProperty('size_eu') || '' === productAttributes['size_eu']) {
                return productAttributes['size'];
            }

            return 'US ' + productAttributes['size'] + ' - EU ' + productAttributes['size_eu'];
        }

        function createVariationSelect(name, options) {
            var variationSelect = document.createElement('SELECT');
            variationSelect.id = selectBoxIdPrefix + name;
            variationSelect.addEventListener('change', function () {
                showNextSelectBox(name);
            }, true);

            var translatedAttributeName = translate(name),
                defaultOption = document.createElement('OPTION');
            defaultOption.textContent = translate('Select %s', translatedAttributeName);
            defaultOption.value = '';

            variationSelect.appendChild(defaultOption);

            options.map(function (option) {
                variationSelect.appendChild(createSelectOption(option));
            });

            return variationSelect;
        }

        function createSelectOption(option) {
            var variationOption = document.createElement('OPTION');
            variationOption.textContent = option['label'];
            variationOption.value = option['value'];

            if (option['disabled']) {
                variationOption.disabled = 'disabled';
            }

            return variationOption;
        }

        function initializeZoom(container) {
            new zoom(container);
        }

        function adjustToPageWidth() {
            updateImageGalleryArrowsVisibility();
        }

        function isPhone() {
            return document.body.clientWidth < tabletWidth;
        }

        function initializeImageGalleryArrows(container) {
            container.appendChild(createArrow('swipe-prev', showPreviousGalleryImage));
            container.appendChild(createArrow('swipe-next', showNextGalleryImage));
        }

        function createArrow(className, callback) {
            var arrow = document.createElement('A');
            arrow.className = className;
            arrow.addEventListener('click', callback, true);

            return arrow;
        }

        function showPreviousGalleryImage() {
            if (currentGalleryImageIndex > 1) {
                setMainProductImageSrc(product.getImageUrlByNumber('large', --currentGalleryImageIndex));
                updateImageGalleryArrowsVisibility();
            }
        }

        function showNextGalleryImage() {
            if (currentGalleryImageIndex < product.getNumberOfImages()) {
                setMainProductImageSrc(product.getImageUrlByNumber('large', ++currentGalleryImageIndex));
                updateImageGalleryArrowsVisibility();
            }
        }

        function setMainProductImageSrc(src) {
            document.querySelector('.main-image-area img').src = src;
        }

        function updateImageGalleryArrowsVisibility() {
            document.querySelector('.main-image-area .swipe-prev').style.opacity = getPreviousImageGalleryArrowOpacity();
            document.querySelector('.main-image-area .swipe-next').style.opacity = getNextImageGalleryArrowOpacity();
        }

        function getPreviousImageGalleryArrowOpacity() {
            if (!isPhone() || 1 === currentGalleryImageIndex) {
                return 0;
            }

            return 1;
        }

        function getNextImageGalleryArrowOpacity() {
            if (!isPhone() || product.getNumberOfImages() === currentGalleryImageIndex) {
                return 0;
            }

            return 1;
        }

        function handleTouchStart(event) {
            xDown = event.touches[0].clientX;
            yDown = event.touches[0].clientY;
        }

        function handleTouchMove(event) {
            if (!xDown || !yDown) {
                return;
            }

            var xDiff = xDown - event.touches[0].clientX,
                yDiff = yDown - event.touches[0].clientY;

            if (Math.abs(xDiff) > Math.abs(yDiff)) {
                if (xDiff > 0) {
                    showNextGalleryImage();
                } else {
                    showPreviousGalleryImage();
                }
            }

            xDown = null;
            yDown = null;
        }
    }
);
