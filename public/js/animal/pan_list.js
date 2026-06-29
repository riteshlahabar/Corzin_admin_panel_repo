function filterPanRows() {
    const searchValue = (document.getElementById('panSearch')?.value || '').toLowerCase().trim();
    document.querySelectorAll('.pan-row').forEach((row) => {
        const haystack = (row.dataset.search || '').toLowerCase();
        row.style.display = !searchValue || haystack.includes(searchValue) ? '' : 'none';
    });

    window.CorzinTablePagination?.refresh('panTableExport');
}

function getVisiblePanRows() {
    return Array.from(document.querySelectorAll('#panTableExport tbody .pan-row')).filter((row) => row.style.display !== 'none');
}

function exportPanTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = getVisiblePanRows();
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
            headers.push(`"${(col.innerText || '').replace(/\n/g, ' ').replace(/,/g, ' ').trim()}"`);
        });
        csv.push(headers.join(','));
    }

    rows.forEach((row) => {
        const cols = row.querySelectorAll('td');
        const rowData = [];
        cols.forEach((col, index) => {
            if (index === cols.length - 1) return;
            rowData.push(`"${(col.innerText || '').replace(/\n/g, ' ').replace(/,/g, ' ').trim()}"`);
        });
        csv.push(rowData.join(','));
    });

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    const url = URL.createObjectURL(csvFile);
    downloadLink.href = url;
    downloadLink.download = `${filename}.csv`;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportPanTableToPdf(tableId, title) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = getVisiblePanRows();
    if (!rows.length) {
        alert('No matching records found for export.');
        return;
    }

    const headers = Array.from(table.querySelectorAll('thead th'))
        .slice(0, -1)
        .map((cell) => `<th>${cell.innerText}</th>`)
        .join('');

    const bodyRows = rows.map((row) => {
        const cols = Array.from(row.querySelectorAll('td'))
            .slice(0, -1)
            .map((cell) => `<td>${cell.innerText}</td>`)
            .join('');
        return `<tr>${cols}</tr>`;
    }).join('');

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

function populateAssignableAnimals() {
    const farmerId = document.getElementById('createPanFarmer')?.value || '';
    const panType = document.getElementById('createPanType')?.value || 'milking';
    const container = document.getElementById('createPanAnimals');
    if (!container) return;

    container.innerHTML = '';
    if (!farmerId) {
        container.innerHTML = '<div class="text-muted small">Select farmer first.</div>';
        return;
    }

    const rawList = (window.assignableAnimalsMap && window.assignableAnimalsMap[farmerId]) || [];
    const list = rawList.filter((animal) => {
        const isMilking = Boolean(animal.is_milking);
        return panType === 'non_milking' ? !isMilking : isMilking;
    });

    if (!Array.isArray(list) || list.length === 0) {
        container.innerHTML = panType === 'non_milking'
            ? '<div class="text-muted small">No unassigned non-milking cows available.</div>'
            : '<div class="text-muted small">No unassigned milking cows available.</div>';
        return;
    }

    list.forEach((animal) => {
        const row = document.createElement('div');
        row.className = 'form-check mb-2';

        const input = document.createElement('input');
        input.className = 'form-check-input';
        input.type = 'checkbox';
        input.name = 'animal_ids[]';
        input.value = animal.id;
        input.id = `create-pan-animal-${animal.id}`;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = input.id;
        label.textContent = `${animal.name || '-'} (${animal.tag || '-'})${animal.type ? ` - ${animal.type}` : ''}`;

        row.appendChild(input);
        row.appendChild(label);
        container.appendChild(row);
    });
}

function toggleCreatePanShift() {
    const panType = document.getElementById('createPanType')?.value || 'milking';
    const wrap = document.getElementById('createPanShiftWrap');
    if (!wrap) return;
    wrap.style.display = panType === 'non_milking' ? 'none' : '';
}

function openTransferModal(button) {
    const panId = String(button.dataset.panId || '');
    const panName = button.dataset.panName || '';
    const farmerId = String(button.dataset.farmerId || '');
    if (!panId || !farmerId) return;

    document.getElementById('transferFromPanId').value = panId;
    document.getElementById('transferFromPanName').value = panName;

    const animalSelect = document.getElementById('transferAnimalId');
    const destinationSelect = document.getElementById('transferToPanId');
    animalSelect.innerHTML = '';
    destinationSelect.innerHTML = '';

    const animals = (window.panAnimalsMap && window.panAnimalsMap[panId]) || [];
    animals.forEach((animal) => {
        const option = document.createElement('option');
        option.value = animal.id;
        option.textContent = `${animal.name || '-'} (${animal.tag || '-'})${animal.type ? ` - ${animal.type}` : ''}`;
        animalSelect.appendChild(option);
    });

    const destinations = ((window.panDestinationsMap && window.panDestinationsMap[farmerId]) || [])
        .filter((pan) => String(pan.id) !== panId);
    destinations.forEach((pan) => {
        const option = document.createElement('option');
        option.value = pan.id;
        option.textContent = pan.name || `PAN #${pan.id}`;
        destinationSelect.appendChild(option);
    });

    if (!animals.length || !destinations.length) {
        alert('Transfer is not possible: animals or destination PAN missing.');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('transferPanModal'));
    modal.show();
}

document.getElementById('panSearch')?.addEventListener('input', filterPanRows);
document.getElementById('createPanFarmer')?.addEventListener('change', populateAssignableAnimals);
document.getElementById('createPanType')?.addEventListener('change', function () {
    toggleCreatePanShift();
    populateAssignableAnimals();
});

document.querySelectorAll('.open-transfer-pan').forEach((button) => {
    button.addEventListener('click', function () {
        openTransferModal(this);
    });
});

populateAssignableAnimals();
toggleCreatePanShift();
