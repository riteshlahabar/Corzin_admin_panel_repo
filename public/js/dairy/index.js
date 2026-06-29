function datasetValue(row, field) {
    const key = field.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    return row.dataset[key] || '';
}

function filterDairyRows() {
    const value = document.getElementById('dairySearch')?.value.toLowerCase().trim() || '';
    const searchField = document.getElementById('dairySearchField')?.value || 'all';
    document.querySelectorAll('.dairy-row').forEach((row) => {
        const haystack = searchField === 'all'
            ? (row.dataset.all || '')
            : datasetValue(row, searchField);
        row.style.display = haystack.includes(value) ? '' : 'none';
    });

    window.CorzinTablePagination?.refresh('dairyTableExport');
}

document.getElementById('dairySearch')?.addEventListener('input', filterDairyRows);
document.getElementById('dairySearchField')?.addEventListener('change', filterDairyRows);

const LOCATION_API_BASE = '/api/doctor/locations';
const locationRequestCache = new Map();

function normalizeLocationValue(value) {
    return (value || '').toString().trim();
}

function buildOption(value, selectedValue) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = value;
    option.selected = normalizeLocationValue(value) === normalizeLocationValue(selectedValue);
    return option;
}

function resetSelect(select, placeholder) {
    if (!select) return;
    select.innerHTML = '';
    const option = document.createElement('option');
    option.value = '';
    option.textContent = placeholder;
    select.appendChild(option);
    select.value = '';
}

async function fetchLocationOptions(endpoint, params = {}) {
    const url = new URL(`${window.location.origin}${LOCATION_API_BASE}/${endpoint}`);
    Object.entries(params).forEach(([key, value]) => {
        const normalized = normalizeLocationValue(value);
        if (normalized !== '') {
            url.searchParams.set(key, normalized);
        }
    });

    const cacheKey = url.toString();
    if (locationRequestCache.has(cacheKey)) {
        return locationRequestCache.get(cacheKey);
    }

    const response = await fetch(cacheKey, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to load ${endpoint}`);
    }

    const payload = await response.json();
    const values = Array.isArray(payload?.data) ? payload.data : [];
    locationRequestCache.set(cacheKey, values);
    return values;
}

function populateSelect(select, values, placeholder, selectedValue = '') {
    resetSelect(select, placeholder);
    values.forEach((value) => {
        const normalized = normalizeLocationValue(value);
        if (normalized !== '') {
            select.appendChild(buildOption(normalized, selectedValue));
        }
    });

    if (normalizeLocationValue(selectedValue) !== '') {
        const hasSelected = Array.from(select.options).some(
            (option) => normalizeLocationValue(option.value) === normalizeLocationValue(selectedValue),
        );
        if (!hasSelected) {
            select.appendChild(buildOption(selectedValue, selectedValue));
        }
        select.value = selectedValue;
    }
}

function createLocationBinder(container) {
    const stateSelect = container.querySelector('[data-location-role="state"]');
    const districtSelect = container.querySelector('[data-location-role="district"]');
    const talukaSelect = container.querySelector('[data-location-role="taluka"]');

    if (!stateSelect || !districtSelect || !talukaSelect) return null;

    let requestId = 0;

    const loadDistricts = async (selectedDistrict = '', preserveTaluka = false) => {
        const stateValue = normalizeLocationValue(stateSelect.value || stateSelect.dataset.selected);
        resetSelect(districtSelect, 'Select district');
        resetSelect(talukaSelect, 'Select taluka/subdistrict');

        if (!preserveTaluka) {
            talukaSelect.dataset.selected = '';
        }

        if (stateValue === '') return;

        const currentRequestId = ++requestId;
        const districts = await fetchLocationOptions('districts', { state: stateValue });
        if (currentRequestId !== requestId) return;

        populateSelect(districtSelect, districts, 'Select district', selectedDistrict);
        if (normalizeLocationValue(selectedDistrict) !== '') {
            await loadTalukas(talukaSelect.dataset.selected || '', true);
        }
    };

    const loadTalukas = async (selectedTaluka = '', preserveSelected = false) => {
        const stateValue = normalizeLocationValue(stateSelect.value || stateSelect.dataset.selected);
        const districtValue = normalizeLocationValue(districtSelect.value || districtSelect.dataset.selected);
        resetSelect(talukaSelect, 'Select taluka/subdistrict');

        if (!preserveSelected) {
            talukaSelect.dataset.selected = '';
        }

        if (stateValue === '' || districtValue === '') return;

        const currentRequestId = ++requestId;
        const talukas = await fetchLocationOptions('talukas', {
            state: stateValue,
            district: districtValue,
        });
        if (currentRequestId !== requestId) return;

        populateSelect(talukaSelect, talukas, 'Select taluka/subdistrict', selectedTaluka);
    };

    stateSelect.addEventListener('change', async () => {
        stateSelect.dataset.selected = stateSelect.value;
        districtSelect.dataset.selected = '';
        talukaSelect.dataset.selected = '';
        await loadDistricts();
    });

    districtSelect.addEventListener('change', async () => {
        districtSelect.dataset.selected = districtSelect.value;
        talukaSelect.dataset.selected = '';
        await loadTalukas();
    });

    talukaSelect.addEventListener('change', () => {
        talukaSelect.dataset.selected = talukaSelect.value;
    });

    return {
        async init() {
            const selectedState = normalizeLocationValue(stateSelect.dataset.selected);
            const selectedDistrict = normalizeLocationValue(districtSelect.dataset.selected);
            const selectedTaluka = normalizeLocationValue(talukaSelect.dataset.selected);

            const states = await fetchLocationOptions('states');
            populateSelect(stateSelect, states, 'Select state', selectedState);

            if (selectedState !== '') {
                await loadDistricts(selectedDistrict, true);
            }

            if (selectedDistrict !== '') {
                districtSelect.dataset.selected = selectedDistrict;
            }

            if (selectedTaluka !== '') {
                talukaSelect.dataset.selected = selectedTaluka;
                await loadTalukas(selectedTaluka, true);
            }
        },
    };
}

async function initDairyLocationForms() {
    const containers = document.querySelectorAll('[data-location-form="dairy"]');
    for (const container of containers) {
        const binder = createLocationBinder(container);
        if (!binder) continue;
        try {
            await binder.init();
        } catch (error) {
            console.error('Failed to initialize dairy location fields', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initDairyLocationForms();
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
