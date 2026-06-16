(function () {
    var searchEl = document.getElementById('filter-search');
    if (!searchEl) {
        return;
    }

    var manufacturerEl = document.getElementById('filter-manufacturer');
    var sortEl = document.getElementById('filter-sort');
    var list = document.getElementById('products-list');
    if (!list) {
        return;
    }
    var emptyMsg = document.getElementById('no-products');

    function applyFilters() {
        var query = (searchEl.value || '').trim().toLowerCase();
        var manufacturer = manufacturerEl.value;
        var sort = sortEl.value;
        var rows = Array.prototype.slice.call(list.querySelectorAll('.product-card'));

        rows.forEach(function (row) {
            var matchSearch = !query || row.dataset.search.indexOf(query) !== -1;
            var matchManufacturer = !manufacturer || row.dataset.manufacturer === manufacturer;
            row.classList.toggle('hidden', !(matchSearch && matchManufacturer));
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
            var ad = parseInt(a.dataset.discount, 10);
            var bd = parseInt(b.dataset.discount, 10);
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
            if (sort === 'discount_asc') {
                return ad - bd;
            }
            if (sort === 'discount_desc') {
                return bd - ad;
            }
            return 0;
        });

        visible.forEach(function (row) {
            list.appendChild(row);
        });

        if (emptyMsg) {
            emptyMsg.classList.toggle('hidden', visible.length > 0);
        }
    }

    searchEl.addEventListener('input', applyFilters);
    manufacturerEl.addEventListener('change', applyFilters);
    sortEl.addEventListener('change', applyFilters);
})();
