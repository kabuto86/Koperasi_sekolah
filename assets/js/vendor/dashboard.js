// Papan pemuka vendor — U1: hanya paparkan koperasi semasa
document.addEventListener('cooperativechange', function (event) {
    var name = event.detail.cooperative.school_name;
    document.getElementById('current-coop').textContent = T('common.current_cooperative', { name: name });
});
