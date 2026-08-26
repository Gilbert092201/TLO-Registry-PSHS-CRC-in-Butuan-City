<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TLO Registry Dashboard · PSHS Caraga</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <img src="assets/img/logo.png" alt="PSHS Caraga Region Campus Logo" class="seal-svg seal-svg-sm">
    <div class="topbar-titles">
      <p class="topbar-eyebrow">Philippine Science High School &mdash; Caraga Region Campus, Butuan City</p>
      <h1>Technology Licensing Office Registry</h1>
    </div>
  </div>
  <div class="topbar-user">
    <div class="user-chip">
      <span class="user-avatar"><?= htmlspecialchars(strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1))) ?></span>
      <span class="user-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span>
    </div>
    <a href="logout.php" class="btn btn-ghost">Sign out</a>
  </div>
</header>

<main class="main">

  <!-- ============ FILTER BAR ============ -->
  <section class="card filter-card">
    <div class="card-head">
      <h2>Filters</h2>
      <button type="button" id="clearFiltersBtn" class="btn btn-text">Clear all</button>
    </div>
    <form id="filterForm" class="filter-grid">
      <label class="field field-sm">
        <span>Employee No.</span>
        <input type="text" name="employee_number" placeholder="Any">
      </label>
      <label class="field field-sm">
        <span>Month</span>
        <select name="entry_month">
          <option value="">Any</option>
          <?php foreach (range(1,12) as $m): ?>
            <option value="<?= $m ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-sm">
        <span>Day</span>
        <select name="entry_day">
          <option value="">Any</option>
          <?php foreach (range(1,31) as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-sm">
        <span>Year</span>
        <input type="number" name="entry_year" placeholder="e.g. 2026" min="2000" max="2100">
      </label>
      <label class="field field-sm">
        <span>IP Name</span>
        <input type="text" name="ip_name" placeholder="Any">
      </label>
      <label class="field field-sm">
        <span>Application No.</span>
        <input type="text" name="application_number" placeholder="Any">
      </label>
      <label class="field field-sm">
        <span>Application Code</span>
        <input type="text" name="application_code" placeholder="Any">
      </label>
      <label class="field field-sm">
        <span>Status</span>
        <select name="status_of_application">
          <option value="">Any</option>
          <?php foreach (STATUS_OPTIONS as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-sm">
        <span>Amount Paid</span>
        <div class="range-pair">
          <input type="number" step="0.01" name="amount_min" placeholder="Min">
          <input type="number" step="0.01" name="amount_max" placeholder="Max">
        </div>
      </label>
      <label class="field field-sm">
        <span>IP Type</span>
        <select name="ip_type">
          <option value="">Any</option>
          <?php foreach (IP_TYPES as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-sm">
        <span>Mode of Transfer</span>
        <select name="mode_of_transfer">
          <option value="">Any</option>
          <?php foreach (TRANSFER_MODES as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-sm field-wide">
        <span>Title of IP</span>
        <input type="text" name="title_of_ip" placeholder="Any">
      </label>
      <div class="field field-sm field-action">
        <button type="submit" class="btn btn-primary btn-block">Apply filters</button>
      </div>
    </form>
  </section>

  <!-- ============ SUMMARY STRIP ============ -->
  <section class="summary-strip" id="summaryStrip">
    <div class="summary-card">
      <span class="summary-label">Total entries</span>
      <span class="summary-value" id="statTotal">0</span>
    </div>
    <div class="summary-card">
      <span class="summary-label">Total amount paid</span>
      <span class="summary-value" id="statAmount">&#8369;0.00</span>
    </div>
    <div class="summary-card">
      <span class="summary-label">Patents on file</span>
      <span class="summary-value" id="statPatents">0</span>
    </div>
    <div class="summary-card">
      <span class="summary-label">Registered</span>
      <span class="summary-value" id="statRegistered">0</span>
    </div>
  </section>

  <!-- ============ CHARTS ============ -->
  <section class="charts-grid">
    <div class="card chart-card">
      <div class="card-head">
        <h2>Filings by Month &amp; Year</h2>
        <span class="card-tag">Line graph</span>
      </div>
      <canvas id="lineChart" height="260"></canvas>
    </div>
    <div class="card chart-card">
      <div class="card-head">
        <h2>Entries by IP Type</h2>
        <span class="card-tag">Bar graph</span>
      </div>
      <canvas id="ipTypeChart" height="260"></canvas>
    </div>
    <div class="card chart-card">
      <div class="card-head">
        <h2>Entries by Mode of Transfer</h2>
        <span class="card-tag">Bar graph</span>
      </div>
      <canvas id="transferChart" height="260"></canvas>
    </div>
  </section>

  <!-- ============ ENTRY FORM (Add / Edit) ============ -->
  <section class="card" id="entryFormCard">
    <div class="card-head">
      <h2 id="entryFormTitle">Add new entry</h2>
      <button type="button" id="resetFormBtn" class="btn btn-text hidden">Cancel edit</button>
    </div>
    <form id="entryForm" class="entry-grid">
      <input type="hidden" name="id" id="entry_id">
      <label class="field">
        <span>Employee Number</span>
        <input type="text" name="employee_number" id="employee_number" required>
      </label>
      <label class="field">
        <span>Month</span>
        <select name="entry_month" id="entry_month" required>
          <option value="" disabled selected>Select month</option>
          <?php foreach (range(1,12) as $m): ?>
            <option value="<?= $m ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Day</span>
        <select name="entry_day" id="entry_day" required>
          <option value="" disabled selected>Select day</option>
          <?php foreach (range(1,31) as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Year</span>
        <input type="number" name="entry_year" id="entry_year" min="2000" max="2100" required placeholder="e.g. 2026">
      </label>
      <label class="field">
        <span>IP Name</span>
        <input type="text" name="ip_name" id="ip_name" required placeholder="Owner / inventor name">
      </label>
      <label class="field">
        <span>Application Number</span>
        <input type="text" name="application_number" id="application_number" required>
      </label>
      <label class="field">
        <span>Application Code</span>
        <input type="text" name="application_code" id="application_code" required>
      </label>
      <label class="field">
        <span>Status of Application</span>
        <select name="status_of_application" id="status_of_application" required>
          <option value="" disabled selected>Select status</option>
          <?php foreach (STATUS_OPTIONS as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Amount Paid (&#8369;)</span>
        <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" required placeholder="0.00">
      </label>
      <label class="field">
        <span>IP Type</span>
        <select name="ip_type" id="ip_type" required>
          <option value="" disabled selected>Select IP type</option>
          <?php foreach (IP_TYPES as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Mode of Technology Transfer</span>
        <select name="mode_of_transfer" id="mode_of_transfer" required>
          <option value="" disabled selected>Select mode</option>
          <?php foreach (TRANSFER_MODES as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-wide">
        <span>Title of the IP</span>
        <input type="text" name="title_of_ip" id="title_of_ip" required placeholder="Full title as filed">
      </label>
      <div class="field field-action">
        <button type="submit" class="btn btn-primary btn-block" id="entrySubmitBtn">Save entry</button>
      </div>
    </form>
    <p class="form-msg" id="entryFormMsg"></p>
  </section>

  <!-- ============ REGISTRY TABLE ============ -->
  <section class="card table-card">
    <div class="card-head">
      <h2>Registry entries</h2>
      <div class="table-actions">
        <button type="button" class="btn btn-ghost" id="downloadCsvBtn">Download CSV</button>
        <button type="button" class="btn btn-ghost" id="downloadPdfBtn">Download PDF</button>
      </div>
    </div>
    <div class="table-scroll">
      <table id="entriesTable">
        <thead>
          <tr>
            <th>Emp. No.</th>
            <th>Date</th>
            <th>IP Name</th>
            <th>Application No.</th>
            <th>Application Code</th>
            <th>Status</th>
            <th>Amount Paid</th>
            <th>IP Type</th>
            <th>Mode of Transfer</th>
            <th>Title of IP</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="entriesTbody">
          <tr><td colspan="11" class="empty-row">Loading entries&hellip;</td></tr>
        </tbody>
      </table>
    </div>
  </section>

</main>

<footer class="page-footer">
  <p>Technology Licensing Office &middot; Philippine Science High School &mdash; Caraga Region Campus &middot; Butuan City</p>
</footer>

<script src="assets/js/dashboard.js"></script>
</body>
</html>
