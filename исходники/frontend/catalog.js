(function () {
    var searchEl = document.getElementById('filter-search');
    if (!searchEl) {
        return;
    }

    var supplierEl = document.getElementById('filter-supplier');
    var sortEl = document.getElementById('filter-sort');
    var tbody = document.querySelector('#products-table tbody');
    var emptyMsg = document.getElementById('no-products');

    function applyFilters() {
        var query = (searchEl.value || '').trim().toLowerCase();
        var supplier = supplierEl.value;
        var sort = sortEl.value;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        rows.forEach(function (row) {
            var matchSearch = !query || row.dataset.search.indexOf(query) !== -1;
            var matchSupplier = !supplier || row.dataset.supplier === supplier;
            row.classList.toggle('hidden', !(matchSearch && matchSupplier));
        });

        var visible = rows.filter(function (row) {
            return !row.classList.contains('hidden');
        });

        visible.sort(function (a, b) {
            if (!sort) {
                return 0;
            }
            var ap = parseFloat(a.dataset.price);
            var bp = parseFloat(b.dataset.price);
            var as = parseInt(a.dataset.stock, 10);
            var bs = parseInt(b.dataset.stock, 10);
            if (sort === 'price_asc') {
                return ap - bp;
            }
            if (sort === 'price_desc') {
                return bp - ap;
            }
            if (sort === 'stock_asc') {
                return as - bs;
            }
            if (sort === 'stock_desc') {
                return bs - as;
            }
            return 0;
        });

        visible.forEach(function (row) {
            tbody.appendChild(row);
        });

        if (emptyMsg) {
            emptyMsg.classList.toggle('hidden', visible.length > 0);
        }
    }

    searchEl.addEventListener('input', applyFilters);
    supplierEl.addEventListener('change', applyFilters);
    sortEl.addEventListener('change', applyFilters);
})();
