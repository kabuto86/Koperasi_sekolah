// Butiran produk → pilih varian, kuantiti, anak → troli

var productData = null;
var productChoice = { variant_id: null, qty: 1, child_id: null };
var productStepper = null;

function selectedVariant() {
    return productData.variants.filter(function (variant) { return variant.id === productChoice.variant_id; })[0] || null;
}

// Harga, baki & had ikut varian dipilih
function refreshProductPrice() {
    var variant = selectedVariant();
    var price = variant ? variant.price : productData.price;
    var regular = variant ? variant.regular_price : productData.regular_price;
    var priceBox = document.getElementById('product-price');
    priceBox.replaceChildren(priceEl(price, regular, !variant && productData.price_from));

    var available = variant ? variant.available : (productData.has_variants ? null : productData.available);
    var badges = document.getElementById('product-badges');
    badges.replaceChildren();
    if (productData.on_sale) {
        badges.appendChild(createEl('span', 'badge text-bg-danger me-1', T('store.clearance.badge')));
    }
    var stock = stockBadge(available);
    if (stock) {
        badges.appendChild(stock);
    }
    var max = variant ? variant.max_per_order : productData.max_per_order;
    var limit = Math.min(max || 99, available === null ? 99 : Math.max(1, available));
    productStepper.qtyInput.max = String(limit);
    document.getElementById('product-limit').textContent = max ? T('store.product.max_per_order', { n: max }) : '';
    document.getElementById('product-add').disabled = available === 0;
}

function renderVariants() {
    if (!productData.has_variants) {
        return;
    }
    var box = document.getElementById('product-variants');
    box.replaceChildren();
    productData.variants.forEach(function (variant) {
        var id = 'variant-' + variant.id;
        var input = createEl('input', 'btn-check');
        input.type = 'radio';
        input.name = 'variant_id';
        input.id = id;
        input.value = variant.id;
        input.disabled = variant.available <= 0;
        input.addEventListener('change', function () {
            productChoice.variant_id = variant.id;
            document.getElementById('product-variant-error').classList.add('d-none');
            refreshProductPrice();
        });
        var label = createEl('label', 'btn btn-outline-brand store-variant-option');
        label.htmlFor = id;
        label.appendChild(createEl('span', 'd-block fw-semibold', variant.label));
        label.appendChild(createEl('span', 'd-block small', variant.available <= 0 ? T('store.stock.out') : formatRM(variant.price)));
        box.appendChild(input);
        box.appendChild(label);
    });
    document.getElementById('product-variant-box').classList.remove('d-none');
}

function renderProductChildren() {
    var box = document.getElementById('product-children');
    if (!Store.customer) {
        box.replaceChildren(createEl('p', 'small text-body-secondary mb-1', T('store.product.login_to_buy')));
        box.appendChild(createLink(T('btn.login'), '#', 'btn btn-outline-brand'));
        box.lastChild.addEventListener('click', function (event) {
            event.preventDefault();
            storeGoLogin();
        });
        return;
    }
    storeGet('store/children/list').then(function (res) {
        var here = res.ok ? res.data.filter(function (child) { return child.school_slug === Store.slug; }) : [];
        if (here.length === 0) {
            emptyState(box, 'bi-person-plus', T('store.product.no_child'), T('store.children.add'), storeUrl('children', { add: 1 }));
            return;
        }
        var selected = queryParam('child') || (here.length === 1 ? here[0].id : null);
        productChoice.child_id = selected ? Number(selected) : null;
        childRadios(box, here, 'child_id', productChoice.child_id, function (child) {
            productChoice.child_id = child.id;
            document.getElementById('product-child-error').classList.add('d-none');
        });
    });
}

function addProductToCart(event) {
    event.preventDefault();
    if (!Store.customer) {
        storeGoLogin();
        return;
    }
    var missing = false;
    if (productData.has_variants && !productChoice.variant_id) {
        document.getElementById('product-variant-error').classList.remove('d-none');
        missing = true;
    }
    if (!productChoice.child_id) {
        document.getElementById('product-child-error').classList.remove('d-none');
        missing = true;
    }
    if (missing) {
        return;
    }
    var button = document.getElementById('product-add');
    button.disabled = true;
    storePost('store/cart/add', { product_id: productData.id, variant_id: productChoice.variant_id, qty: Number(productStepper.qtyInput.value), child_id: productChoice.child_id })
        .then(function (res) {
            button.disabled = false;
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            setCartCount(res.data.cart_count);
            showToast(T('store.product.added', { name: res.data.child_name }));
        });
}

onStoreReady(function () {
    storeGet('store/product', { id: queryParam('id') }).then(function (res) {
        if (!res.ok) {
            emptyState(document.getElementById('product-view'), 'bi-box-seam', res.error, T('store.nav.catalog'), storeUrl('catalog'));
            return;
        }
        productData = res.data;
        document.title = productData.name + ' · ' + Store.school.school_name;
        document.getElementById('product-image').replaceChildren(productImage(productData.image_url, 'store-thumb-lg'));
        document.getElementById('product-category').textContent = productData.category_name;
        document.getElementById('product-name').textContent = productData.name;
        document.getElementById('product-description').textContent = productData.description || '';
        productStepper = qtyStepper(1, 99, function (n) { productChoice.qty = n; });
        document.getElementById('product-qty').replaceChildren(productStepper);
        renderVariants();
        refreshProductPrice();
        renderProductChildren();
        document.getElementById('product-form').addEventListener('submit', addProductToCart);
    });
});
