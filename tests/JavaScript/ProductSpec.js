define(['../../src/lizards-and-pumpkins/pub/js/product'], function (Product) {

    describe('Product', function () {
        var product;

        it('SKU is returned', function () {
            product = new Product({"product_id": 'foo', "attributes": {}});
            expect(product.getSku()).toBe('foo');
        });

        it('false is return if required attribute is not defined', function () {
            product = new Product({"attributes": {}});
            expect(product.hasAttributeValue('foo')).toBe(false);
        });

        it('true is return if required attribute is defined', function () {
            product = new Product({"attributes": {"foo": 'bar'}});
            expect(product.hasAttributeValue('foo')).toBe(true);
        });

        it('attribute value is returned', function () {
            product = new Product({"attributes": {"foo": 'bar'}});
            expect(product.getAttributeValue('foo')).toBe('bar');
        });

        it('first image is returned', function () {
            product = new Product({"images": {"medium": [{"url": 'foo'}]}});
            expect(product.getMainImage()).toEqual({"url": 'foo'});
        });

        it('has no special price if no raw special price is specified', function () {
            product = new Product({"attributes": {"raw_price": '500'}});
            expect(product.hasSpecialPrice()).toBe(false);
        });

        it('has no special price if raw special price is greater than raw regular price', function () {
            product = new Product({"attributes": {"raw_price": '500', "raw_special_price": '1000'}});
            expect(product.hasSpecialPrice()).toBe(false);
        });

        it('has no special price if raw special price is equal to raw regular price', function () {
            product = new Product({"attributes": {"raw_price": '500', "raw_special_price": '500'}});
            expect(product.hasSpecialPrice()).toBe(false);
        });

        it('has a special price if raw special price is lower than raw regular price', function () {
            product = new Product({"attributes": {"raw_price": '1000', "raw_special_price": '500'}});
            expect(product.hasSpecialPrice()).toBe(true);
        });

        it('price is returned', function () {
            product = new Product({"attributes": {"price": '107,94 €'}});
            expect(product.getPrice()).toBe('107,94 €');
        });

        it('special price is returned', function () {
            product = new Product({"attributes": {"special_price": '107,94 €'}});
            expect(product.getSpecialPrice()).toBe('107,94 €');
        });

        it('is not new if neither "new from" nor "new to" date is specified', function () {
            product = new Product({"attributes": {}});
            expect(product.isNew()).toBe(false);
        });
        
        it('is not new if "new from" date is in a future', function () {
            product = new Product({"attributes": {"news_from_date": '3000-01-01 00:00:00'}});
            expect(product.isNew()).toBe(false);
        });

        it('is not new if "new to" date is in a past', function () {
            product = new Product({"attributes": {"news_to_date": '2000-01-01 00:00:00'}});
            expect(product.isNew()).toBe(false);
        });

        it('is new if "new from" date is in a past and "new to" date is not set', function () {
            product = new Product({"attributes": {"news_from_date": '2000-01-01 00:00:00'}});
            expect(product.isNew()).toBe(true);
        });

        it('is new if "new to" date is in a future and "new from" date is not set', function () {
            product = new Product({"attributes": {"news_to_date": '3000-01-01 00:00:00'}});
            expect(product.isNew()).toBe(true);
        });

        it('is new if "new from" date is in a past and "new to" date is in a future', function () {
            product = new Product({
                "attributes": {
                    "news_from_date": '2000-01-01 00:00:00',
                    "news_to_date": '3000-01-01 00:00:00'
                }
            });
            expect(product.isNew()).toBe(true);
        });

        it('discount percentage is returned', function () {
            product = new Product({"attributes": {"raw_price": '1800', "raw_special_price": '1700'}});
            expect(product.getDiscountPercentage()).toBe(100 - Math.round(1700 * 100 / 1800));
        });

        it('null is returned if non existing product size is requested', function () {
           product = new Product({"images": {"large": []}});
           expect(product.getImageUrlByNumber('huge', 1)).toBeNull();
        });

        it('null is returned if non existing product number is requested', function () {
           product = new Product({"images": {"large": []}});
           expect(product.getImageUrlByNumber('large', 1)).toBeNull();
        });

        it('image URL of an image with a given size and number is returned', function () {
            product = new Product({"images": {"large": [{"url": 'foo'}, {"url": 'bar'}]}});
            expect(product.getImageUrlByNumber('large', 2)).toBe('bar');
        });

        it('number of product images is returned', function () {
            product = new Product({"images": {"original": [{"url": 'foo'}]}});
            expect(product.getNumberOfImages()).toBe(1);
        });

        it('final price is equal to original price if product does not have special price', function () {
            product = new Product({"attributes": {"price": '107,94 €'}});
            expect(product.getFinalPrice()).toBe('107,94 €');
        });

        it('final price is equal to original price if special price is not less than original price', function () {
            product = new Product({
                "attributes": {
                    "price": '107,94 €',
                    "raw_price": 10794,
                    "raw_special_price": 10794
                }
            });
            expect(product.getFinalPrice()).toBe('107,94 €');
        });

        it('final price is equal to special price if it is less than original price', function () {
            product = new Product({
                "attributes": {
                    "price": '107,94 €',
                    "special_price": '100,00 €',
                    "raw_price": 10794,
                    "raw_special_price": 10000
                }
            });
            expect(product.getFinalPrice()).toBe('100,00 €');
        });
    });
});
