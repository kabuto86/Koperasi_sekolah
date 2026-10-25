// Checkout: semak → siapa ambil → kaedah bayaran → buat pesanan

var checkoutQuote = null;
var checkoutCoupon = '';

function renderReview(groups) {
    var box = document.getElementById('checkout-groups');
    box.replaceChildren();
    groups.forEach(function (group) {
        var section = createEl('div', 'mb-3');
        section.appendChild(childGroupHeader(group));
        var list = createEl('ul', 'list-unstyled small mb-0 ms-4');
        group.items.forEach(function (item) {
            var row = createEl('li', 'd-flex justify-content-between gap-2 py-1 border-bottom');
            row.appendChild(createEl('span', 'min-w-0', item.qty + ' × ' + item.name + (item.variant_label ? ' — ' + item.variant_label : '')));
            row.appendChild(createEl('span', 'text-nowrap', formatRM(item.line_total)));
            list.appendChild(row);
        });
        section.appendChild(list);
        box.appendChild(section);
    });
}

function renderMethods(data) {
    var form = document.getElementById('checkout-form');
    ['online', 'cash'].forEach(function (method) {
        var allowed = data.payment_methods.indexOf(method) !== -1;
        document.querySelector('label[for="method-' + method + '"]').classList.toggle('d-none', !allowed);
        form.querySelector('#method-' + method).disabled = !allowed;
    });
    document.getElementById('method-online-hint').textContent = T('store.checkout.method_hint.online', { gateway: data.gateway_name || '' });
    document.getElementById('method-cash-hint').textContent = T('store.checkout.method_hint.cash', { days: data.cash_expiry_days });
    if (data.payment_methods.length === 1) {
        form.querySelector('#method-' + data.payment_methods[0]).checked = true;
    }
}

function renderCodeField(data) {
    var box = document.getElementById('checkout-code');
    var input = document.getElementById('checkout-code-input');
    box.classList.toggle('d-none', !data.requires_code);
    input.required = data.requires_code;
    document.getElementById('checkout-code-help').textContent = data.code_required_above > 0
        ? T('store.checkout.code_help_above', { amount: formatRM(data.code_required_above) })
        : T('store.checkout.code_help');
}

function renderPickupHints(data) {
    var names = data.groups.map(function (group) { return group.child_name; }).join(', ');
    document.querySelector('[data-pickup-hint="student"]').textContent = T('store.checkout.pickup_hint.student_names', { names: names });
    document.querySelector('[data-pickup-hint="parent"]').textContent = data.customer_name;
}

function renderQuote(data) {
    checkoutQuote = data;
    if (data.item_count === 0) {
        storeGo('cart');
        return;
    }
    if (data.has_problem) {
        showToast(T('store.checkout.fix_cart'), 'warning');
        storeGo('cart');
        return;
    }
    renderReview(data.groups);
    renderTotals(document.getElementById('checkout-totals'), data);
    renderMethods(data);
    renderCodeField(data);
    renderPickupHints(data);
    var coupon = document.getElementById('checkout-coupon');
    coupon.classList.toggle('is-invalid', Boolean(data.coupon_error));
    document.getElementById('checkout-coupon-error').textContent = data.coupon_error || '';
    document.getElementById('checkout-verify').classList.toggle('d-none', data.email_verified);
    document.getElementById('checkout-submit').disabled = !data.email_verified;
}

function loadQuote() {
    return storeGet('store/checkout/quote', { coupon_code: checkoutCoupon }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderQuote(res.data);
    });
}

function bindPickup() {
    var form = document.getElementById('checkout-form');
    form.querySelectorAll('[name="pickup_by_type"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var proxy = form.pickup_by_type.value === 'proxy';
            document.getElementById('checkout-proxy').classList.toggle('d-none', !proxy);
            document.getElementById('checkout-proxy-name').required = proxy;
            if (proxy) {
                document.getElementById('checkout-proxy-name').focus();
            }
        });
    });
}

function bindCoupon() {
    var input = document.getElementById('checkout-coupon');
    function apply() {
        checkoutCoupon = input.value.trim().toUpperCase();
        input.value = checkoutCoupon;
        loadQuote().then(function () {
            if (checkoutQuote && checkoutCoupon && !checkoutQuote.coupon_error) {
                showToast(T('store.checkout.coupon_applied'));
            }
        });
    }
    document.getElementById('checkout-coupon-apply').addEventListener('click', apply);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            apply();
        }
    });
}

function placeOrder(event) {
    event.preventDefault();
    var form = document.getElementById('checkout-form');
    var methodChosen = Boolean(form.querySelector('[name="payment_method"]:checked'));
    document.getElementById('checkout-method-error').classList.toggle('d-none', methodChosen);
    if (!validateForm(form) || !methodChosen) {
        return;
    }
    var payload = formToObject(form);
    payload.coupon_code = checkoutCoupon;
    payload.expected_total = checkoutQuote.total;
    var button = document.getElementById('checkout-submit');
    button.disabled = true;
    storePost('store/checkout/place', payload).then(function (res) {
        button.disabled = false;
        if (!res.ok) {
            showFormErrors(form, res.errors);
            showToast(res.error, 'danger');
            loadQuote();
            return;
        }
        setCartCount(0);
        if (res.data.payment) {
            storeStartPayment(res.data.order_id, res.data.payment);
        } else {
            storeGo('order-success', { order: res.data.order_id });
        }
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    bindPickup();
    bindCoupon();
    document.getElementById('checkout-form').addEventListener('submit', placeOrder);
    loadQuote();
});
