function filterDoctorRows() {
    const searchValue = (document.getElementById('doctorSearch')?.value || '').toLowerCase().trim();
    const fromDate = document.getElementById('doctorDateFrom')?.value || '';
    const toDate = document.getElementById('doctorDateTo')?.value || '';
    const showApproved = document.getElementById('filterApproved')?.checked ?? true;
    const showUnapproved = document.getElementById('filterUnapproved')?.checked ?? true;

    document.querySelectorAll('.doctor-row').forEach((row) => {
        const haystack = row.dataset.search || '';
        const rowStatus = row.dataset.status || 'unapproved';
        const rowDate = row.dataset.created || '';

        const matchesSearch = haystack.includes(searchValue);
        const matchesStatus = (rowStatus === 'approved' && showApproved) || (rowStatus === 'unapproved' && showUnapproved);
        const matchesFromDate = !fromDate || !rowDate || rowDate >= fromDate;
        const matchesToDate = !toDate || !rowDate || rowDate <= toDate;

        row.style.display = matchesSearch && matchesStatus && matchesFromDate && matchesToDate ? '' : 'none';
    });
}

document.getElementById('doctorSearch')?.addEventListener('input', filterDoctorRows);
document.getElementById('doctorDateFrom')?.addEventListener('change', filterDoctorRows);
document.getElementById('doctorDateTo')?.addEventListener('change', filterDoctorRows);
document.getElementById('filterApproved')?.addEventListener('change', filterDoctorRows);
document.getElementById('filterUnapproved')?.addEventListener('change', filterDoctorRows);

const approvalToastElement = document.getElementById('doctorApprovalToast');
const approvalToast = approvalToastElement && window.bootstrap ? new bootstrap.Toast(approvalToastElement) : null;
const approvalToastMessage = document.getElementById('doctorApprovalToastMessage');
const approvalConfirmBtn = document.getElementById('doctorApprovalConfirmBtn');

let pendingApprovalForm = null;
let pendingApprovalToggle = null;

document.querySelectorAll('.doctor-approval-toggle').forEach((toggle) => {
    toggle.addEventListener('change', function (event) {
        event.preventDefault();
        const form = this.closest('.doctor-approval-form');
        const doctorName = form?.dataset.doctor || 'this doctor';
        const isApproving = this.checked;
        if (!approvalToast || !approvalToastMessage || !approvalConfirmBtn || !form) {
            this.checked = !isApproving;
            return;
        }

        pendingApprovalForm = form;
        pendingApprovalToggle = this;
        approvalToastMessage.textContent = isApproving
            ? `Approve ${doctorName}?`
            : `Mark ${doctorName} as unapproved?`;
        approvalConfirmBtn.textContent = isApproving ? 'Approve' : 'Unapprove';
        approvalConfirmBtn.className = `btn ${isApproving ? 'btn-success' : 'btn-danger'} btn-sm`;
        approvalToast.show();
    });
});

approvalConfirmBtn?.addEventListener('click', function () {
    if (pendingApprovalForm) {
        pendingApprovalForm.submit();
    }
});

approvalToastElement?.addEventListener('hidden.bs.toast', function () {
    if (pendingApprovalToggle) {
        pendingApprovalToggle.checked = !pendingApprovalToggle.checked;
    }
    pendingApprovalForm = null;
    pendingApprovalToggle = null;
});

document.getElementById('exportDoctorExcel')?.addEventListener('click', function () {
    const rows = Array.from(document.querySelectorAll('#doctorTable tr')).filter((row) => row.style.display !== 'none');
    const csv = rows
        .map((row) => Array.from(row.querySelectorAll('th, td')).map((cell) => `"${(cell.innerText || '').replace(/"/g, '""').trim()}"`).join(','))
        .join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'doctor-list.csv';
    link.click();
    URL.revokeObjectURL(link.href);
});

document.getElementById('exportDoctorPdf')?.addEventListener('click', function () {
    window.print();
});
