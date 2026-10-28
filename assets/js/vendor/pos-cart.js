// Logik troli POS — disalin & diubah suai dari KedaiPOS (kabuto86/pos-system: js/data.js makeLineId, calcUnitPrice,
// suggestCashAmounts; js/app.js getTotals). Perbezaan: wang dalam SEN (integer), satu varian setiap produk,
// diskaun = promosi (kupon / automatik) + diskaun manual, cukai dikira selepas diskaun.
// Sejak B10 pelayan mengira semula semuanya dengan logik yang sama (jobs/Pos/PosCalculateCartJob + Promotions::choose) —
// nilai dari pelayar hanya untuk paparan. Ubah kedua-duanya bersama.

// Satu baris troli bagi setiap produk + varian
function posLineId(productId, variantId) {
    return productId + '#' + (variantId || 0);
}

// Harga seunit = harga produk + beza harga varian (tidak negatif)
function posUnitPrice(product, variant) {
    return Math.max(0, product.price + (variant ? variant.price_delta : 0));
}

// Diskaun promosi untuk subtotal ini (0 jika belanja minimum tidak dicapai)
function posPromotionDiscount(promo, subtotal) {
    if (!promo || subtotal <= 0 || subtotal < (promo.min_spend || 0)) {
        return 0;
    }
    var amount = promo.type === 'percent' ? Math.round(subtotal * promo.value / 100) : promo.value;
    return Math.min(amount, subtotal);
}

// Diskaun automatik (promosi tanpa kod) yang paling menjimatkan untuk subtotal ini
function posBestAutomatic(promotions, subtotal) {
    var best = null;
    var bestAmount = 0;
    (promotions || []).forEach(function (promo) {
        var amount = posPromotionDiscount(promo, subtotal);
        if (!promo.code && amount > bestAmount) {
            best = promo;
            bestAmount = amount;
        }
    });
    return best;
}

// getTotals: subtotal → diskaun (promosi + manual, tidak melebihi subtotal) → cukai atas baki → jumlah
// lines = [{ unit_price, qty }], options = { promotion, manualDiscount, taxRate (%) }
function posTotals(lines, options) {
    var opts = options || {};
    var subtotal = lines.reduce(function (sum, line) {
        return sum + line.unit_price * line.qty;
    }, 0);
    var promotionDiscount = posPromotionDiscount(opts.promotion, subtotal);
    var manual = Math.max(0, Math.round(opts.manualDiscount || 0));
    var discount = Math.min(subtotal, promotionDiscount + manual);
    var taxable = subtotal - discount;
    var tax = Math.round(taxable * (opts.taxRate || 0) / 100);
    return {
        subtotal: subtotal,
        promotion_discount: promotionDiscount,
        manual_discount: discount - promotionDiscount,
        discount: discount,
        tax: tax,
        total: taxable + tax
    };
}

// Butang wang pantas: jumlah tepat (dibundar ke atas 5 sen) + not RM1, RM5, RM10, RM20, RM50, RM100
var POS_CASH_NOTES = [100, 500, 1000, 2000, 5000, 10000];

function posSuggestCash(total) {
    var amounts = [Math.ceil(total / 5) * 5];
    POS_CASH_NOTES.forEach(function (note) {
        var value = Math.ceil(total / note) * note;
        if (value > 0 && amounts.indexOf(value) === -1) {
            amounts.push(value);
        }
    });
    return amounts.filter(function (value) {
        return value >= total;
    }).sort(function (a, b) {
        return a - b;
    }).slice(0, 4);
}
