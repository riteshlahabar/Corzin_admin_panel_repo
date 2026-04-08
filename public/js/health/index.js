function filterHealthRows() {
    const searchValue = (document.getElementById('healthSearch')?.value || '').toLowerCase().trim();
    const start = document.getElementById('startDate')?.value || '';
    const end = document.getElementById('endDate')?.value || '';

    document.querySelectorAll('.health-row').forEach((row) => {
        const haystack = row.dataset.search || '';
        const date = row.dataset.date || '';
        let show = true;

        if (searchValue && !haystack.includes(searchValue)) show = false;
        if (start && date && date < start) show = false;
        if (end && date && date > end) show = false;

        row.style.display = show ? '' : 'none';
    });
}

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll('tr');
    const csv = [];

    rows.forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach((col) => {
            rowData.push(`"${col.innerText.replace(/\n/g, ' ').replace(/,/g, ' ').trim()}"`);
        });
        csv.push(rowData.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
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

document.getElementById('healthSearch')?.addEventListener('input', filterHealthRows);
document.getElementById('startDate')?.addEventListener('change', filterHealthRows);
document.getElementById('endDate')?.addEventListener('change', filterHealthRows);
