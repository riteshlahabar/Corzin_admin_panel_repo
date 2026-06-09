function updateMilkSummaryCards() {
    let morningTotal = 0;
    let afternoonTotal = 0;
    let eveningTotal = 0;
    let fatTotal = 0;
    let fatCount = 0;

    document.querySelectorAll('.milk-row').forEach((row) => {
        if (row.style.display === 'none') {
            return;
        }

        morningTotal += parseFloat(row.dataset.morningValue || '0') || 0;
        afternoonTotal += parseFloat(row.dataset.afternoonValue || '0') || 0;
        eveningTotal += parseFloat(row.dataset.eveningValue || '0') || 0;

        const fatValue = parseFloat(row.dataset.fatValue || '');
        if (!Number.isNaN(fatValue)) {
            fatTotal += fatValue;
            fatCount += 1;
        }
    });

    const morningElement = document.getElementById('summaryMorning');
    const afternoonElement = document.getElementById('summaryAfternoon');
    const eveningElement = document.getElementById('summaryEvening');
    const fatElement = document.getElementById('summaryFat');

    if (morningElement) {
        morningElement.textContent = `${morningTotal.toFixed(1)} L`;
    }
    if (afternoonElement) {
        afternoonElement.textContent = `${afternoonTotal.toFixed(1)} L`;
    }
    if (eveningElement) {
        eveningElement.textContent = `${eveningTotal.toFixed(1)} L`;
    }
    if (fatElement) {
        fatElement.textContent = `${(fatCount > 0 ? fatTotal / fatCount : 0).toFixed(1)} %`;
    }
}

function filterMilkRows() {
    const selectedShifts = [];
    document.querySelectorAll('.shift-filter:checked').forEach((item) => selectedShifts.push(item.value));
    const search = document.getElementById('milkSearch')?.value.toLowerCase().trim() || '';
    const searchField = document.getElementById('milkSearchField')?.value || 'all';
    const start = document.getElementById('startDate')?.value || '';
    const end = document.getElementById('endDate')?.value || '';

    document.querySelectorAll('.milk-row').forEach((row) => {
        const haystack = searchField === 'all'
            ? (row.dataset.search || '')
            : (row.dataset[searchField] || '');
        const date = row.dataset.dateRaw || row.dataset.date || '';
        let shiftMatch = selectedShifts.length === 0;
        selectedShifts.forEach((shift) => {
            if (row.dataset[shift] === '1') shiftMatch = true;
        });
        let show = shiftMatch;
        if (search && !haystack.includes(search)) show = false;
        if (start && date && date < start) show = false;
        if (end && date && date > end) show = false;
        row.style.display = show ? '' : 'none';
    });

    updateMilkSummaryCards();
    window.CorzinTablePagination?.refresh('milkTableExport');
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

document.querySelectorAll('.shift-filter').forEach((checkbox) => {
    checkbox.addEventListener('change', function () {
        const label = document.querySelector(`label[for="${this.id}"]`);
        label?.classList.toggle('active', this.checked);
        filterMilkRows();
    });
});

document.getElementById('milkSearch')?.addEventListener('input', filterMilkRows);
document.getElementById('milkSearchField')?.addEventListener('change', filterMilkRows);
document.getElementById('startDate')?.addEventListener('change', filterMilkRows);
document.getElementById('endDate')?.addEventListener('change', filterMilkRows);

document.addEventListener('DOMContentLoaded', updateMilkSummaryCards);


