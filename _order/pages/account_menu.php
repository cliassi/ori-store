<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$items = [
  ['label' => 'Customer',   'icon' => 'person',        'page' => 'customer_info', 'group' => 'Account'],
  ['label' => 'Statement',  'icon' => 'receipt_long',  'page' => 'statement',     'group' => 'Account'],
  ['label' => 'Last Order', 'icon' => 'shopping_bag',  'page' => 'last_order',    'group' => 'Account'],
  ['label' => 'Payment',    'icon' => 'payments',      'page' => 'payment',       'group' => 'Account'],
];
$groups = [];
foreach ($items as $it) {
  $groups[$it['group']][] = $it;
}
?>
<style>
  .view-all-page { background: #f2f2f2; min-height: 100vh; }
  .view-all-header {
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 20;
  }
  .view-all-header .title {
    font-size: 18px;
    font-weight: 700;
    color: #111;
  }
  .view-all-header a, .view-all-header button {
    color: #222;
    text-decoration: none;
    background: none;
    border: 0;
    padding: 0;
    line-height: 0;
  }
  .view-all-header .material-symbols-outlined {
    font-size: 24px;
    color: #222;
  }
  .view-all-tabs {
    background: #fff;
    border-bottom: 1px solid #eee;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
  }
  .view-all-tabs a {
    display: inline-block;
    padding: 10px 16px 12px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #b0b0b0;
    text-decoration: none;
  }
  .view-all-tabs a.active {
    color: #111;
  }
  .view-all-card {
    background: #fff;
    border-radius: 16px;
    padding: 16px 12px 8px;
    margin-bottom: 12px;
  }
  .view-all-card h2 {
    font-size: 16px;
    font-weight: 700;
    color: #111;
    margin: 0 4px 12px;
  }
  .view-all-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px 4px;
  }
  .view-all-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    text-decoration: none;
    color: #222;
    padding: 10px 4px 14px;
  }
  .view-all-item .material-symbols-outlined {
    font-size: 32px;
    color: #444;
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  }
  .view-all-item span.label {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.25;
    color: #222;
  }
  #viewAllSearchWrap {
    display: none;
    padding: 0 16px 12px;
    background: #fff;
  }
  #viewAllSearchWrap.open { display: block; }
  #viewAllSearch {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    padding: 8px 14px;
    font-size: 14px;
  }
</style>

<div class="view-all-page">
  <header class="view-all-header">
    <div class="flex items-center justify-between px-3 py-3">
      <a href="?page=home" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="title m-0">View All</h1>
      <button type="button" id="viewAllSearchBtn" aria-label="Search">
        <span class="material-symbols-outlined">search</span>
      </button>
    </div>
    <div id="viewAllSearchWrap">
      <input type="search" id="viewAllSearch" placeholder="Search" autocomplete="off">
    </div>
    <nav class="view-all-tabs" aria-label="Categories">
      <?php foreach (array_keys($groups) as $i => $group): ?>
        <a href="#group-<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"><?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <div class="px-3 py-3">
    <?php $gi = 0; foreach ($groups as $groupName => $groupItems): ?>
      <section class="view-all-card" id="group-<?php echo $gi; ?>">
        <h2><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="view-all-grid">
          <?php foreach ($groupItems as $it): ?>
            <a class="view-all-item" href="?page=<?php echo htmlspecialchars($it['page'], ENT_QUOTES, 'UTF-8'); ?>" data-label="<?php echo htmlspecialchars(strtolower($it['label']), ENT_QUOTES, 'UTF-8'); ?>">
              <span class="material-symbols-outlined"><?php echo htmlspecialchars($it['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="label"><?php echo htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php $gi++; endforeach; ?>
  </div>
</div>

<script>
  (function () {
    const btn = document.getElementById('viewAllSearchBtn');
    const wrap = document.getElementById('viewAllSearchWrap');
    const input = document.getElementById('viewAllSearch');
    const items = document.querySelectorAll('.view-all-item');
    const tabs = document.querySelectorAll('.view-all-tabs a');

    btn && btn.addEventListener('click', () => {
      wrap.classList.toggle('open');
      if (wrap.classList.contains('open')) input.focus();
    });

    input && input.addEventListener('input', () => {
      const q = (input.value || '').trim().toLowerCase();
      items.forEach((el) => {
        const label = el.getAttribute('data-label') || '';
        el.style.display = (!q || label.indexOf(q) !== -1) ? '' : 'none';
      });
    });

    tabs.forEach((tab) => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        tabs.forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');
        const target = document.querySelector(tab.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  })();
</script>
