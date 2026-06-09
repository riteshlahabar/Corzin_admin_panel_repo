function datasetValue(row, field) {
    const key = field.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    return row.dataset[key] || '';
}

function getAnimalFilters() {
    const selectedTypes = [];
    document.querySelectorAll('.animal-filter:checked').forEach((item) => selectedTypes.push(item.value));
    return {
        selectedTypes,
        searchValue: document.getElementById('animalSearch')?.value.toLowerCase().trim() || '',
        searchField: document.getElementById('animalSearchField')?.value || 'all',
        start: document.getElementById('startDate')?.value || '',
        end: document.getElementById('endDate')?.value || '',
    };
}

function rowMatchesFilters(row, filters) {
    const type = row.dataset.type || '';
    const haystack = filters.searchField === 'all'
        ? (row.dataset.all || '')
        : datasetValue(row, filters.searchField);
    const date = row.dataset.date || '';

    if (filters.selectedTypes.length && !filters.selectedTypes.includes(type)) return false;
    if (filters.searchValue && !haystack.includes(filters.searchValue)) return false;
    if (filters.start && date && date < filters.start) return false;
    if (filters.end && date && date > filters.end) return false;
    return true;
}

function getFilteredRows() {
    const filters = getAnimalFilters();
    return Array.from(document.querySelectorAll('.animal-row')).filter((row) =>
        rowMatchesFilters(row, filters),
    );
}

function filterAnimals() {
    const filters = getAnimalFilters();

    document.querySelectorAll('.animal-row').forEach((row) => {
        row.style.display = rowMatchesFilters(row, filters) ? '' : 'none';
    });

    window.CorzinTablePagination?.refresh('animalTableExport');
}

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = getFilteredRows();
    if (!rows.length) {
        alert('No matching records found for export.');
        return;
    }
    const csv = [];
    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        const headers = [];
        const headerCols = headerRow.querySelectorAll('th');
        headerCols.forEach((col, index) => {
            if (index === headerCols.length - 1) return;
            const text = (col.innerText || '').replace(/\n/g, ' ').replace(/,/g, ' ').trim();
            headers.push('"' + text + '"');
        });
        csv.push(headers.join(','));
    }

    rows.forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach((col, index) => {
            if (index === cols.length - 1) return;
            const text = (col.innerText || '').replace(/\n/g, ' ').replace(/,/g, ' ').trim();
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    const url = URL.createObjectURL(csvFile);
    downloadLink.href = url;
    downloadLink.download = filename + '.csv';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportTableToPdf(tableId, title) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = getFilteredRows();
    if (!rows.length) {
        alert('No matching records found for export.');
        return;
    }

    const headers = Array.from(table.querySelectorAll('thead th'))
        .slice(0, -1)
        .map((cell) => `<th>${cell.innerText}</th>`)
        .join('');

    const bodyRows = rows
        .map((row) => {
            const cols = Array.from(row.querySelectorAll('td'))
                .slice(0, -1)
                .map((cell) => `<td>${cell.innerText}</td>`)
                .join('');
            return `<tr>${cols}</tr>`;
        })
        .join('');

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 24px; }
                h2 { margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ccc; padding: 8px; font-size: 12px; text-align: left; }
                th:last-child, td:last-child { display: none; }
            </style>
        </head>
        <body>
            <h2>${title}</h2>
            <table>
                <thead><tr>${headers}</tr></thead>
                <tbody>${bodyRows}</tbody>
            </table>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

document.querySelectorAll('.animal-filter').forEach((item) => {
    item.addEventListener('change', function () {
        const label = document.querySelector(`label[for="${this.id}"]`);
        label?.classList.toggle('active', this.checked);
        filterAnimals();
    });
});

document.getElementById('animalSearch')?.addEventListener('input', filterAnimals);
document.getElementById('animalSearchField')?.addEventListener('change', filterAnimals);
document.getElementById('startDate')?.addEventListener('change', filterAnimals);
document.getElementById('endDate')?.addEventListener('change', filterAnimals);

document.querySelectorAll('.view-animal-image').forEach((button) => {
    button.addEventListener('click', function () {
        document.getElementById('animalImageTitle').textContent = this.dataset.animal + ' Image';
        document.getElementById('animalImagePreview').src = this.dataset.image;
        new bootstrap.Modal(document.getElementById('animalImageModal')).show();
    });
});
