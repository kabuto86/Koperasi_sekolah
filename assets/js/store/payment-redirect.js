// Hantar borang gateway (iPay88) secara automatik. Butang dalam borang kekal jika JS lambat / disekat.
(function () {
    var form = document.getElementById('gateway-redirect-form');
    if (form) {
        form.submit();
    }
}());
