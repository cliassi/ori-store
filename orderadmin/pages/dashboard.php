<style>
    body { font-family: 'Lato', sans-serif; }
    .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
    /* Decorative notch on bottom-right to mimic the reference layout */
    /* .blob-shape:after{content:'';position:absolute;right:-28px;bottom:-28px;width:88px;height:88px;background:#fff;border-radius:50%} */
    /* Force icon color for Material Symbols */
    .icon-green{color:#47773f !important}
    .material-symbols-outlined{color:#47773f !important;font-variation-settings:'FILL' 0, 'wght' 400, 'opsz' 24}
    li, #app-shortcuts a{
        font-size: .8rem;
        font-weight: 700;
    }

    #app-shortcuts .shortcut-table {
        border: 1px solid #2f4f2f33;
        overflow: hidden;
        border-radius: 18px;
        background: #e7f2e3;
    }

    #app-shortcuts .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    #app-shortcuts .shortcut-cell {
        border-right: 1px solid #2f4f2f33;
        border-bottom: 1px solid #2f4f2f33;
        min-height: 92px;
    }

    #app-shortcuts .shortcut-cell:nth-child(4n) {
        border-right: 0;
    }

    #app-shortcuts .shortcut-cell:nth-last-child(-n+4) {
        border-bottom: 0;
    }

    #app-shortcuts .shortcut-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 14px 8px 10px;
        text-decoration: none;
    }

    #app-shortcuts .shortcut-label {
        width: 100%;
        text-align: center;
        line-height: 1.1;
        color: #111;
    }
    </style>

    <!-- Top Summary Panel -->
    <section class="bg-primary blob-shape ">
    <div class="max-w-sm mx-auto px-1 py-6">
        <div class="grid grid-cols-2 gap-6">
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Petty Cash. <b class="font-semibold ">500.00</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Invested Cap <b class="font-semibold ">2887480</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Bank. <b class="font-semibold ">1849870</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Customer Due <b class="font-semibold ">217890</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Supplier Due <b class="font-semibold ">54535442</b></span></li>
                </ul>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Present Cap <b class="font-semibold ">28732012</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Stock Value <b class="font-semibold ">53935560</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Expense. <b class="font-semibold ">4231</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Damage. <b class="font-semibold ">210</b></span></li>
                    <li class="flex items-start gap-2"><span class="mt-1 h-2 w-2 rounded-full bg-black/90"></span><span class="">Profit & Loss. <b class="font-semibold ">12783</b></span></li>
                </ul>
        </div>
    </div>
</section>

    <!-- App Shortcuts Grid (Mobile-first: fixed 4 columns) -->
    <section id="app-shortcuts" class="max-w-sm mx-auto px-3 mt-6">
        <div class="shortcut-table shadow-sm">
            <div class="shortcut-grid">
                <?php
                orderadminSeedAdminDashboardPermissions();
                $items = orderadminVisibleDashboardItems(uid());
                foreach ($items as $i => $it): ?>
                    <div class="shortcut-cell">
                        <?php if (!empty($it['page'])): ?>
                        <a href="?page=<?php echo htmlspecialchars($it['page']); ?>" class="shortcut-link">
                            <span class="flex items-center justify-center h-12 w-12">
                                <?php
                                $icon = orderadminDashboardIcon($it['icon']);
                                echo '<span class="material-symbols-outlined icon-green text-[42px] leading-none" style="color:#47773f !important">' . $icon . '</span>';
                                ?>
                            </span>
                            <span class="shortcut-label"><?php echo htmlspecialchars($it['label']); ?></span>
                        </a>
                        <?php else: ?>
                        <div class="shortcut-link" style="opacity:.45; cursor: not-allowed;">
                            <span class="flex items-center justify-center h-12 w-12">
                                <?php
                                $icon = orderadminDashboardIcon($it['icon']);
                                echo '<span class="material-symbols-outlined icon-green text-[42px] leading-none" style="color:#47773f !important">' . $icon . '</span>';
                                ?>
                            </span>
                            <span class="shortcut-label"><?php echo htmlspecialchars($it['label']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>