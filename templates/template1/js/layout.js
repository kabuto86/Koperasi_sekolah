// Template 1 — muat nama sekolah dan bilangan item troli
var storeSlug = document.getElementById('store-context').getAttribute('data-school-slug');

function loadSchool() {
    Api.get('store/school', { slug: storeSlug }).then(function (res) {
        var welcome = document.querySelector('[data-school-welcome]');

        if (!res.ok) {
            var errorBox = document.getElementById('store-error');
            if (errorBox) {
                errorBox.textContent = res.error;
                errorBox.classList.remove('d-none');
            }
            if (welcome) {
                welcome.textContent = T('store.select_school');
            }
            return;
        }

        document.querySelectorAll('[data-school-name]').forEach(function (el) {
            el.textContent = res.data.school_name;
        });
        if (welcome) {
            welcome.textContent = T('store.welcome', { school: res.data.school_name });
        }
    });
}

function loadCartCount() {
    Api.get('store/cart/summary').then(function (res) {
        if (!res.ok || res.data.item_count < 1) {
            return;
        }
        document.querySelectorAll('[data-cart-count]').forEach(function (el) {
            el.textContent = res.data.item_count;
            el.classList.remove('d-none');
        });
    });
}

loadSchool();
loadCartCount();
