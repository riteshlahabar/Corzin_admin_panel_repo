function filterLifecycleRows() {
    const searchValue = (document.getElementById('lifecycleSearch')?.value || '').toLowerCase().trim();
    const start = document.getElementById('startDate')?.value || '';
    const end = document.getElementById('endDate')?.value || '';

    document.querySelectorAll('.lifecycle-row').forEach((row) => {
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

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];

        cols.forEach((col) => {
            const text = col.innerText.replace(/\n/g, ' ').replace(/,/g, ' ').trim();
            rowData.push(`"${text}"`);
        });

        csv.push(rowData.join(','));
    });

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    const url = URL.createObjectURL(csvFile);

    downloadLink.href = url;
    downloadLink.download = filename + '.csv';
    downloadLink.style.display = 'none';
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

document.getElementById('lifecycleSearch')?.addEventListener('input', filterLifecycleRows);
document.getElementById('startDate')?.addEventListener('change', filterLifecycleRows);
document.getElementById('endDate')?.addEventListener('change', filterLifecycleRows);
