define(function () {
    return function (productSourceData) {
        var product = productSourceData;

        function isDate(dateString) {
            return dateString.match(/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/);
        }

        function getRawPrice() {
            return parseInt(product['attributes']['raw_price']);
        }

        function getRawSpecialPrice() {
            return parseInt(product['attributes']['raw_special_price']);
        }

        this.getSku = function () {
            return product['product_id'];
        };

        this.hasAttributeValue = function (attributeCode) {
            return product['attributes'].hasOwnProperty(attributeCode);
        };

        this.getAttributeValue = function (attributeCode) {
            return product['attributes'][attributeCode];
        };

        this.getMainImage = function () {
            return product['images']['medium'][0];
        };

        this.hasSpecialPrice = function () {
            if (! this.hasAttributeValue('raw_special_price')) {
                return false;
            }

            var rawSpecialPrice = getRawSpecialPrice(this);

            return rawSpecialPrice > 0 && getRawPrice(this) > rawSpecialPrice;
        };

        this.getPrice = function () {
            return product['attributes']['price'];
        };

        this.getSpecialPrice = function () {
            return product['attributes']['special_price'];
        };

        this.isNew = function () {
            if ((! this.hasAttributeValue('news_from_date') || ! isDate(product['attributes']['news_from_date'])) &&
                (! this.hasAttributeValue('news_to_date') || ! isDate(product['attributes']['news_to_date']))
            ) {
                return false;
            }

            var currentDate = new Date();

            if (this.hasAttributeValue('news_from_date')) {
                var newsFromDate = new Date(product['attributes']['news_from_date'].replace(/\s/, 'T'));

                if (newsFromDate > currentDate) {
                    return false;
                }
            }

            if (this.hasAttributeValue('news_to_date')) {
                var newsToDate = new Date(product['attributes']['news_to_date'].replace(/\s/, 'T'));

                if (newsToDate < currentDate) {
                    return false;
                }
            }

            return true;
        };

        this.getDiscountPercentage = function () {
            return 100 - Math.round(getRawSpecialPrice(this) * 100 / getRawPrice(this));
        };

        this.getImageUrlByNumber = function (size, number) {
            if (typeof product['images'][size] === 'undefined' ||
                typeof product['images'][size][number - 1] === 'undefined'
            ) {
                return null;
            }

            return product['images'][size][number - 1]['url'];
        };

        this.getNumberOfImages = function () {
            return product['images']['original'].length;
        };

        this.getFinalPrice = function () {
            if (this.hasSpecialPrice() && getRawSpecialPrice() < getRawPrice()) {
                return this.getSpecialPrice();
            }

            return this.getPrice();
        };
    }
});
