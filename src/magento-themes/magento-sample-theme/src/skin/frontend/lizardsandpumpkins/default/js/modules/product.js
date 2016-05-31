( function () {
    'use strict';

    var image = document.getElementById('image'),
        galleryImage = document.getElementsByClassName('gallery-image'),
        popupImage = document.getElementById('popup-image'),
        popup = document.getElementById('product-image-popup'),
        imageSrc = document.getElementById('image'),
        imageZoomSrc = document.getElementById('zoom-image'),
        cartBtn = document.querySelector('.product button.cart'),
        buttonOffset = cartBtn.getBoundingClientRect().top,
        stickyBuyBox = document.getElementById('sticky-buy-box'),
        header = document.querySelector('header'),
        headerHeight = header.offsetHeight;

    [].forEach.call(galleryImage, function (i) {
        var dataSrc = i.getAttribute('data-src'),
            dataZoom = i.getAttribute('data-zoom');

        i.addEventListener('click', function () {
            [].forEach.call(galleryImage, function (el) {
                el.className = el.className.replace(/\bactive\b/ig, ' ')
            });

            imageSrc.setAttribute('src', dataSrc);
            imageZoomSrc.setAttribute('data-zoom', dataZoom);
            popupImage.setAttribute('data-src', dataZoom);
            this.className += ' active';
        });
    });

    image.addEventListener('click', function () {
        popupImage.setAttribute('src', popupImage.getAttribute('data-src'));
        popup.style.display = 'block';
    });

    popup.addEventListener('click', function (e) {
        if (e.target !== popupImage) {
            this.style.display = 'none';
        }
    });

    window.addEventListener('scroll', function () {
        if (document.body.scrollTop < headerHeight) {
            header.style.top = '-145px';
            stickyBuyBox.style.top = '-80px';
            return;
        }

        if (document.body.scrollTop > headerHeight && document.body.scrollTop < buttonOffset) {
            header.style.top = '-283px';
            stickyBuyBox.style.top = '-80px';
            return;
        }

        if (document.body.scrollTop > buttonOffset) {
            header.style.top = '-283px';
            stickyBuyBox.style.top = '0px';
        }
    });
})();
