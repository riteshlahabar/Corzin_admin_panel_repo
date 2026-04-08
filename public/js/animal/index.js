function filterAnimals() {
    const selectedTypes = [];
    document.querySelectorAll('.animal-filter:checked').forEach((item) => selectedTypes.push(item.value));
    const searchValue = document.getElementById('animalSearch')?.value.toLowerCase().trim() || '';
    const start = document.getElementById('startDate')?.value || '';
    const end = document.getElementById('endDate')?.value || '';

    document.querySelectorAll('.animal-row').forEach((row) => {
        const type = row.dataset.type || '';
        const haystack = row.dataset.search || '';
        const date = row.dataset.date || '';
        let show = true;

        if (selectedTypes.length && !selectedTypes.includes(type)) show = false;
        if (searchValue && !haystack.includes(searchValue)) show = false;
        if (start && date && date < start) show = false;
        if (end && date && date > end) show = false;

        row.style.display = show ? '' : 'none';
    });
}

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const csv = [];
    table.querySelectorAll('tr').forEach((row) => {
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
            ${table.outerHTML}
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
document.getElementById('startDate')?.addEventListener('change', filterAnimals);
document.getElementById('endDate')?.addEventListener('change', filterAnimals);

document.querySelectorAll('.view-animal-image').forEach((button) => {
    button.addEventListener('click', function () {
        document.getElementById('animalImageTitle').textContent = this.dataset.animal + ' Image';
        document.getElementById('animalImagePreview').src = this.dataset.image;
        new bootstrap.Modal(document.getElementById('animalImageModal')).show();
    });
});
