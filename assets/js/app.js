$(function () {
  const current = new URLSearchParams(window.location.search).get('page') || 'dashboard';
  $('aside .nav-link').each(function () {
    const href = $(this).attr('href');
    if (href && href.includes('page=' + current)) {
      $(this).addClass('active bg-primary-subtle text-primary-emphasis');
    }
  });

  const employeeSearch = $('#employeeSearch');
  const employeeStatusFilter = $('#employeeStatusFilter');
  const employeeResetFilters = $('#employeeResetFilters');
  const employeeRows = $('#dataTable tbody tr');
  const employeeMeta = $('#employeeTableMeta');

  function applyEmployeeFilters() {
    if (!employeeRows.length) return;
    const keyword = (employeeSearch.val() || '').toString().trim().toLowerCase();
    const status = (employeeStatusFilter.val() || '').toString().trim().toLowerCase();
    let visible = 0;

    employeeRows.each(function () {
      const row = $(this);
      const haystack = (row.data('search') || '').toString().toLowerCase();
      const rowStatus = (row.data('status') || '').toString().toLowerCase();
      const matchKeyword = !keyword || haystack.includes(keyword);
      const matchStatus = !status || rowStatus === status;
      const show = matchKeyword && matchStatus;
      row.toggle(show);
      if (show) visible++;
    });

    if (employeeMeta.length) {
      employeeMeta.text(`Showing ${visible} employee records.`);
    }
  }

  employeeSearch.on('input', applyEmployeeFilters);
  employeeStatusFilter.on('change', applyEmployeeFilters);
  employeeResetFilters.on('click', function () {
    employeeSearch.val('');
    employeeStatusFilter.val('');
    applyEmployeeFilters();
  });

  const basicSalary = $('#basicSalary');
  const allowanceSalary = $('#allowanceSalary');
  const deductionSalary = $('#deductionSalary');
  const estimatedNetSalary = $('#estimatedNetSalary');

  function updateEstimatedSalary() {
    if (!estimatedNetSalary.length) return;
    const basic = parseFloat(basicSalary.val() || 0);
    const allowance = parseFloat(allowanceSalary.val() || 0);
    const deduction = parseFloat(deductionSalary.val() || 0);
    const net = (basic + allowance - deduction);
    const normalized = Number.isFinite(net) ? net : 0;
    estimatedNetSalary.text(
      normalized.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
    );
  }

  basicSalary.on('input', updateEstimatedSalary);
  allowanceSalary.on('input', updateEstimatedSalary);
  deductionSalary.on('input', updateEstimatedSalary);
  updateEstimatedSalary();
});
