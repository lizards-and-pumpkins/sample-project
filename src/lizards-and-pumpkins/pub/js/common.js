define(
    ['lib/domReady', 'magento_data', 'lib/translate', 'search_autosuggestion'],
    function (domReady, magentoData, translate) {

        var prevPos = document.body.scrollTop;

        domReady(function () {
            processAccountMeta();
            processCartMeta();
            addMobileSearchEventListeners();
            addMobileMenuEventListeners();
            addLanguageSwitchEventListeners();
            addHeaderHidingOnPageScrolling();
        });

        function processAccountMeta() {
            var container = document.getElementById('account-meta');

            if (null === container) {
                return;
            }

            if (magentoData.isCustomerLoggedIn()) {
                var welcomeMessage = magentoData.getCustomerName() ?
                    translate('Hi, %s', magentoData.getCustomerName()) :
                    translate('Hi!');
                container.appendChild(createTextLink('customer/account', welcomeMessage));
                container.appendChild(createTextLink('customer/account/logout/', translate('Logout')));
                return;
            }

            container.appendChild(createTextLink('customer/account/login/', translate('Login / Register')));
        }

        function processCartMeta() {
            var container = document.getElementById('cart-meta-info');

            if (null === container) {
                return;
            }

            var numItemsInCart = magentoData.getCartItems().length;

            if (numItemsInCart > 0) {
                container.textContent = numItemsInCart + ' | ' + magentoData.getCartTotal();
                return;
            }

            container.textContent = translate('Your cart is empty');
        }

        function addLanguageSwitchEventListeners() {
            var header = document.getElementsByTagName('header')[0],
                content = document.getElementById('content');

            document.getElementById('language-switcher').addEventListener('click', function () {
                toggleClassName(header, 'pushed');
                toggleClassName(content, 'pushed');
            });

            document.querySelector('.language-switch-close').addEventListener('click', function () {
                header.className = header.className.replace(/\bpushed\b/ig, ' ');
                content.className = content.className.replace(/\bpushed\b/ig, ' ');
            });
        }

        function addHeaderHidingOnPageScrolling() {
            window.addEventListener('scroll', function () {
                var header = document.getElementsByTagName('header')[0],
                    content = document.getElementById('content'),
                    isScrolledDown = document.body.scrollTop > prevPos,
                    headerScrolledOutOfView = document.body.scrollTop > header.offsetHeight;

                prevPos = document.body.scrollTop;
                content.className = content.className.replace(/\bpushed\b/ig, ' ');

                if (isScrolledDown && headerScrolledOutOfView) {
                    if (!header.className.match(/\bhidden\b/)) {
                        header.className += ' hidden';
                    }
                    return;
                }

                header.className = header.className.replace(/\bpushed\b|\bhidden\b/ig, ' ');
            });
        }

        function addMobileSearchEventListeners() {
            document.getElementById('mobile-search').addEventListener('click', function () {
                toggleClassName(document.getElementById('search_mini_form'), 'active');
            });
        }

        function addMobileMenuEventListeners() {
            document.getElementById('mobile-menu').addEventListener('click', function () {
                toggleClassName(document.getElementById('page'), 'pushed');
                toggleClassName(document.querySelector('body'), 'no-flow');
                toggleClassName(document.querySelector('.scroll-wrapper'), 'visible');
            });
        }

        function createTextLink(urlPath, linkText) {
            var loginLink = document.createElement('A');
            loginLink.href = baseUrl + urlPath;
            loginLink.textContent = linkText;

            return loginLink;
        }

        function toggleClassName(element, className) {
            var classNameRegExp = new RegExp('\\b' + className + '\\b', 'ig');

            if (element.className.match(classNameRegExp)) {
                element.className = element.className.replace(classNameRegExp, ' ');
                return;
            }

            element.className += ' ' + className;
        }
    }
);
