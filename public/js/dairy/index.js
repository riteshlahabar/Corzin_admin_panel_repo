document.getElementById('dairySearch')?.addEventListener('input', function () {
    const value = this.value.toLowerCase().trim();
    document.querySelectorAll('.dairy-row').forEach((row) => {
        const haystack = row.dataset.search || '';
        row.style.display = haystack.includes(value) ? '' : 'none';
    });
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const csv = [];
    table.querySelectorAll('tr').forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach((col) => {
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
