/* ==========================================================================
   TLO Registry Dashboard - front-end logic
   Talks to api/entries.php (CRUD) and api/chart_data.php (aggregates).
   ========================================================================== */

const MONTH_NAMES = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

const filterForm   = document.getElementById('filterForm');
const entryForm     = document.getElementById('entryForm');
const entryFormMsg  = document.getElementById('entryFormMsg');
const entryFormTitle = document.getElementById('entryFormTitle');
const resetFormBtn  = document.getElementById('resetFormBtn');
const entrySubmitBtn = document.getElementById('entrySubmitBtn');
const entriesTbody  = document.getElementById('entriesTbody');
const clearFiltersBtn = document.getElementById('clearFiltersBtn');
const downloadCsvBtn = document.getElementById('downloadCsvBtn');
const downloadPdfBtn = document.getElementById('downloadPdfBtn');

let lineChart, ipTypeChart, transferChart;
let editingId = null;

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------
function currentFilterParams() {
  const fd = new FormData(filterForm);
  const params = new URLSearchParams();
  for (const [key, val] of fd.entries()) {
    if (val !== '') params.append(key, val);
  }
  return params;
}

function money(n) {
  return '\u20B1' + Number(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function pillClass(status) {
  return 'pill pill-status-' + status.replace(/\s+/g, '-');
}

// -------------------------------------------------------------------------
// Load table + charts together (both respect current filters)
// -------------------------------------------------------------------------
async function refreshAll() {
  const params = currentFilterParams();
  await Promise.all([loadEntries(params), loadCharts(params)]);
}

async function loadEntries(params) {
  entriesTbody.innerHTML = '<tr><td colspan="11" class="empty-row">Loading entries&hellip;</td></tr>';
  try {
    const res = await fetch('api/entries.php?' + params.toString());
    const json = await res.json();
    renderTable(json.data || []);
  } catch (e) {
    entriesTbody.innerHTML = '<tr><td colspan="11" class="empty-row">Could not load entries. Check your database connection.</td></tr>';
  }
}

function renderTable(rows) {
  if (!rows.length) {
    entriesTbody.innerHTML = '<tr><td colspan="11" class="empty-row">No entries match the current filters.</td></tr>';
    return;
  }
  entriesTbody.innerHTML = rows.map(r => `
    <tr>
      <td>${escapeHtml(r.employee_number)}</td>
      <td>${MONTH_NAMES[r.entry_month].slice(0,3)} ${r.entry_day}, ${r.entry_year}</td>
      <td>${escapeHtml(r.ip_name)}</td>
      <td class="amount-cell">${escapeHtml(r.application_number)}</td>
      <td class="amount-cell">${escapeHtml(r.application_code)}</td>
      <td><span class="${pillClass(r.status_of_application)}">${escapeHtml(r.status_of_application)}</span></td>
      <td class="amount-cell">${money(r.amount_paid)}</td>
      <td>${escapeHtml(r.ip_type)}</td>
      <td>${escapeHtml(r.mode_of_transfer)}</td>
      <td>${escapeHtml(r.title_of_ip)}</td>
      <td>
        <div class="row-actions">
          <button class="btn btn-ghost btn-sm" data-action="edit" data-id="${r.id}">Edit</button>
          <button class="btn btn-danger btn-sm" data-action="delete" data-id="${r.id}">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, s => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[s]));
}

// -------------------------------------------------------------------------
// Charts
// -------------------------------------------------------------------------
async function loadCharts(params) {
  try {
    const res = await fetch('api/chart_data.php?' + params.toString());
    const json = await res.json();
    renderLineChart(json.line);
    renderBarChart('ipTypeChart', ipTypeChart, json.ip_type, ['#0B2545','#13315C','#00B4A6','#F4A300','#5A6B82']).then(c => ipTypeChart = c);
    renderBarChart('transferChart', transferChart, json.mode_of_transfer, ['#00B4A6','#0B2545','#F4A300','#5A6B82']).then(c => transferChart = c);
    renderSummary(json.summary);
  } catch (e) {
    // Silently ignore chart errors so the table still works
  }
}

function renderLineChart(data) {
  const ctx = document.getElementById('lineChart');
  const cfg = {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [{
        label: 'Filings',
        data: data.data,
        borderColor: '#00B4A6',
        backgroundColor: 'rgba(0,180,166,0.12)',
        tension: 0.35,
        fill: true,
        pointBackgroundColor: '#0B2545',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E1E7EF' } },
        x: { grid: { display: false } }
      }
    }
  };
  if (lineChart) { lineChart.data = cfg.data; lineChart.update(); }
  else { lineChart = new Chart(ctx, cfg); }
}

function renderBarChart(canvasId, existing, data, colors) {
  return new Promise(resolve => {
    const ctx = document.getElementById(canvasId);
    const cfg = {
      type: 'bar',
      data: {
        labels: data.labels,
        datasets: [{
          label: 'Entries',
          data: data.data,
          backgroundColor: colors,
          borderRadius: 6,
          maxBarThickness: 46,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E1E7EF' } },
          x: { grid: { display: false } }
        }
      }
    };
    if (existing) { existing.data = cfg.data; existing.update(); resolve(existing); }
    else { resolve(new Chart(ctx, cfg)); }
  });
}

function renderSummary(s) {
  document.getElementById('statTotal').textContent = s.total;
  document.getElementById('statAmount').textContent = money(s.total_amount);
  document.getElementById('statPatents').textContent = s.patents;
  document.getElementById('statRegistered').textContent = s.registered;
}

// -------------------------------------------------------------------------
// Filter form
// -------------------------------------------------------------------------
filterForm.addEventListener('submit', e => {
  e.preventDefault();
  refreshAll();
});

clearFiltersBtn.addEventListener('click', () => {
  filterForm.reset();
  refreshAll();
});

// -------------------------------------------------------------------------
// Entry form (add / edit)
// -------------------------------------------------------------------------
entryForm.addEventListener('submit', async e => {
  e.preventDefault();
  entryFormMsg.textContent = '';
  entryFormMsg.className = 'form-msg';

  const fd = new FormData(entryForm);
  const payload = Object.fromEntries(fd.entries());

  const isEdit = !!editingId;
  const url = 'api/entries.php';
  const method = isEdit ? 'PUT' : 'POST';
  if (isEdit) payload.id = editingId;

  entrySubmitBtn.disabled = true;
  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (!res.ok || json.errors) {
      entryFormMsg.textContent = (json.errors || ['Something went wrong.']).join(' ');
      entryFormMsg.className = 'form-msg error';
    } else {
      entryFormMsg.textContent = isEdit ? 'Entry updated successfully.' : 'Entry added successfully.';
      entryFormMsg.className = 'form-msg success';
      exitEditMode();
      entryForm.reset();
      refreshAll();
    }
  } catch (err) {
    entryFormMsg.textContent = 'Network error. Please try again.';
    entryFormMsg.className = 'form-msg error';
  } finally {
    entrySubmitBtn.disabled = false;
  }
});

resetFormBtn.addEventListener('click', () => {
  exitEditMode();
  entryForm.reset();
  entryFormMsg.textContent = '';
});

function enterEditMode(row) {
  editingId = row.id;
  entryFormTitle.textContent = 'Edit entry #' + row.id;
  resetFormBtn.classList.remove('hidden');
  entrySubmitBtn.textContent = 'Update entry';

  document.getElementById('employee_number').value = row.employee_number;
  document.getElementById('entry_month').value = row.entry_month;
  document.getElementById('entry_day').value = row.entry_day;
  document.getElementById('entry_year').value = row.entry_year;
  document.getElementById('ip_name').value = row.ip_name;
  document.getElementById('application_number').value = row.application_number;
  document.getElementById('application_code').value = row.application_code;
  document.getElementById('status_of_application').value = row.status_of_application;
  document.getElementById('amount_paid').value = row.amount_paid;
  document.getElementById('ip_type').value = row.ip_type;
  document.getElementById('mode_of_transfer').value = row.mode_of_transfer;
  document.getElementById('title_of_ip').value = row.title_of_ip;

  document.getElementById('entryFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function exitEditMode() {
  editingId = null;
  entryFormTitle.textContent = 'Add new entry';
  resetFormBtn.classList.add('hidden');
  entrySubmitBtn.textContent = 'Save entry';
}

// -------------------------------------------------------------------------
// Table row actions (edit / delete) - event delegation
// -------------------------------------------------------------------------
entriesTbody.addEventListener('click', async e => {
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;
  const id = btn.dataset.id;

  if (btn.dataset.action === 'delete') {
    if (!confirm('Delete this registry entry? This cannot be undone.')) return;
    try {
      const res = await fetch('api/entries.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
      });
      const json = await res.json();
      if (json.success) refreshAll();
      else alert((json.errors || ['Could not delete entry.']).join(' '));
    } catch (err) {
      alert('Network error while deleting.');
    }
  }

  if (btn.dataset.action === 'edit') {
    try {
      const params = currentFilterParams();
      const res = await fetch('api/entries.php?' + params.toString());
      const json = await res.json();
      const row = (json.data || []).find(r => String(r.id) === String(id));
      if (row) enterEditMode(row);
    } catch (err) {
      alert('Could not load entry for editing.');
    }
  }
});

// -------------------------------------------------------------------------
// Downloads (CSV / PDF) - honor current filters
// -------------------------------------------------------------------------
downloadCsvBtn.addEventListener('click', () => {
  const params = currentFilterParams();
  window.location.href = 'api/export_csv.php?' + params.toString();
});

downloadPdfBtn.addEventListener('click', () => {
  const params = currentFilterParams();
  window.location.href = 'api/export_pdf.php?' + params.toString();
});

// -------------------------------------------------------------------------
// Init
// -------------------------------------------------------------------------
refreshAll();
