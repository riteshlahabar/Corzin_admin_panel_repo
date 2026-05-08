(function () {
    const pageSizes = [10, 25, 50, 100];
    const tableStates = new WeakMap();

    function dataRows(table) {
        const body = table.tBodies && table.tBodies[0];
        if (!body) return [];

        return Array.from(body.rows).filter((row) => {
            if (!row.cells.length) return false;
            if (row.cells.length === 1 && row.cells[0].hasAttribute('colspan')) return false;
            return true;
        });
    }

    function setServerPerPage(select) {
        const name = select.dataset.perPageName || 'per_page';
        const url = new URL(window.location.href);
        url.searchParams.set(name, select.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function buildClientControls(table, rows) {
        const wrapper = table.closest('.table-responsive') || table;
        const controls = document.createElement('div');
        controls.className = 'corzin-table-pagination d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3';
        controls.innerHTML = `
            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Show</span>
                <select class="form-select form-select-sm corzin-client-per-page" style="width: 82px;">
                    ${pageSizes.map((size) => `<option value="${size}">${size}</option>`).join('')}
                </select>
                <span>entries</span>
                <span class="corzin-table-info ms-2"></span>
            </div>
            <nav aria-label="Table pagination">
                <ul class="pagination pagination-sm mb-0 corzin-client-pages"></ul>
            </nav>
        `;

        wrapper.insertAdjacentElement('afterend', controls);

        const state = {
            rows,
            page: 1,
            perPage: 10,
            select: controls.querySelector('.corzin-client-per-page'),
            info: controls.querySelector('.corzin-table-info'),
            pages: controls.querySelector('.corzin-client-pages'),
        };

        state.select.addEventListener('change', function () {
            state.perPage = parseInt(this.value, 10) || 10;
            state.page = 1;
            applyClientPagination(table);
        });

        tableStates.set(table, state);
        rows.forEach((row) => {
            row.dataset.corzinFilterVisible = row.style.display === 'none' ? '0' : '1';
            row.dataset.corzinPageHidden = '0';
        });
    }

    function pageButton(label, disabled, active, onClick) {
        const item = document.createElement('li');
        item.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.disabled = disabled;
        if (!disabled) {
            button.addEventListener('click', onClick);
        }

        item.appendChild(button);
        return item;
    }

    function renderPageButtons(table, state, pageCount) {
        state.pages.innerHTML = '';

        state.pages.appendChild(pageButton('Previous', state.page <= 1, false, function () {
            state.page -= 1;
            applyClientPagination(table);
        }));

        const pages = new Set([1, pageCount]);
        for (let page = Math.max(1, state.page - 1); page <= Math.min(pageCount, state.page + 1); page += 1) {
            pages.add(page);
        }

        let previousPage = 0;
        Array.from(pages).sort((a, b) => a - b).forEach((page) => {
            if (previousPage && page - previousPage > 1) {
                state.pages.appendChild(pageButton('...', true, false, function () {}));
            }
            state.pages.appendChild(pageButton(String(page), false, state.page === page, function () {
                state.page = page;
                applyClientPagination(table);
            }));
            previousPage = page;
        });

        state.pages.appendChild(pageButton('Next', state.page >= pageCount, false, function () {
            state.page += 1;
            applyClientPagination(table);
        }));
    }

    function applyClientPagination(table) {
        const state = tableStates.get(table);
        if (!state) return;

        const visibleRows = state.rows.filter((row) => row.dataset.corzinFilterVisible !== '0');
        const pageCount = Math.max(1, Math.ceil(visibleRows.length / state.perPage));
        state.page = Math.min(Math.max(state.page, 1), pageCount);

        const start = (state.page - 1) * state.perPage;
        const end = start + state.perPage;
        const currentRows = new Set(visibleRows.slice(start, end));

        state.rows.forEach((row) => {
            const shouldShow = row.dataset.corzinFilterVisible !== '0' && currentRows.has(row);
            row.classList.toggle('corzin-page-hidden', !shouldShow && row.dataset.corzinFilterVisible !== '0');
            row.dataset.corzinPageHidden = shouldShow ? '0' : '1';
        });

        if (visibleRows.length === 0) {
            state.info.textContent = 'Showing 0 to 0 of 0';
        } else {
            state.info.textContent = `Showing ${start + 1} to ${Math.min(end, visibleRows.length)} of ${visibleRows.length}`;
        }

        renderPageButtons(table, state, pageCount);
    }

    function refreshClientPagination(tableOrId) {
        const table = typeof tableOrId === 'string' ? document.getElementById(tableOrId) : tableOrId;
        const state = table ? tableStates.get(table) : null;
        if (!state) return;

        state.rows.forEach((row) => {
            row.dataset.corzinFilterVisible = row.style.display === 'none' ? '0' : '1';
        });
        state.page = 1;
        applyClientPagination(table);
    }

    function shouldUseClientPagination(table) {
        if (table.dataset.corzinPagination === 'off') return false;
        if (table.closest('.modal')) return false;
        if (table.closest('.card')?.querySelector('.corzin-server-pagination')) return false;
        return dataRows(table).length > 10;
    }

    function initClientPagination() {
        document.querySelectorAll('table').forEach((table) => {
            if (tableStates.has(table)) return;
            if (!shouldUseClientPagination(table)) return;

            buildClientControls(table, dataRows(table));
            applyClientPagination(table);
        });
    }

    window.CorzinTablePagination = {
        init: initClientPagination,
        refresh: refreshClientPagination,
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.corzin-server-per-page').forEach((select) => {
            select.addEventListener('change', function () {
                setServerPerPage(this);
            });
        });

        initClientPagination();
    });
})();
