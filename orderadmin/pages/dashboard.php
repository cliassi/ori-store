<style>
    body {
        font-family: 'Lato', sans-serif;
        background: #f5f5f7;
        padding-top: 68px;
    }

    .menu-header {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px 20px;
        background: #fff;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .menu-header h1 {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }

    .menu-content {
        max-width: 480px;
        margin: 0 auto;
        padding: 16px;
    }

    .menu-card {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .section-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #111;
        margin: 0 0 16px;
    }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .menu-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #333;
    }

    .menu-item-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }

    .menu-item-icon .material-symbols-outlined {
        font-size: 32px;
        color: #555;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'opsz' 24;
    }

    .menu-item-label {
        font-size: 0.7rem;
        text-align: center;
        line-height: 1.2;
        color: #555;
        font-weight: 400;
    }
</style>

<div class="menu-header">
    <h1>Dashboard</h1>
</div>


<?php
orderadminSeedAdminDashboardPermissions();
$dashboardSections = [
    'main-services' => [
        'title' => 'Main Services',
        'pages' => ['dashboard', 'customer_due', 'customer', 'expenses', 'customer_order', 'order', 'collect', 'cnr', 'pending_order', 'delivery_status', 'permission'],
        'items' => [],
    ],
    'product-sales' => [
        'title' => 'Product & Sales',
        'pages' => ['product_add', 'daily_order', 'daily_sales', 'file'],
        'items' => [],
    ],
    'supplier' => [
        'title' => 'Supplier',
        'pages' => ['supplier_add', 'supplier_due'],
        'items' => [],
    ],
];
$sectionByPage = [];
foreach ($dashboardSections as $sectionId => $section) {
    foreach ($section['pages'] as $page) {
        $sectionByPage[$page] = $sectionId;
    }
}
foreach (orderadminVisibleDashboardItems(uid()) as $item) {
    $sectionId = $sectionByPage[$item['page']] ?? 'main-services';
    $dashboardSections[$sectionId]['items'][] = $item;
}
$dashboardSections = array_filter($dashboardSections, function ($section) {
    return !empty($section['items']);
});
?>

<div class="menu-content">
    <?php foreach ($dashboardSections as $section): ?>
        <section class="menu-card">
            <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
            <div class="menu-grid">
                <?php foreach ($section['items'] as $item): ?>
                    <a href="?page=<?php echo htmlspecialchars($item['page']); ?>" class="menu-item">
                        <span class="menu-item-icon">
                            <span class="material-symbols-outlined"><?php echo htmlspecialchars(orderadminDashboardIcon($item['icon'])); ?></span>
                        </span>
                        <span class="menu-item-label"><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>