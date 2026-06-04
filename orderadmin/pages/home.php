<style>
  .border-brandBlue {
    border-color: #2563eb !important;
  }

  .bg-gradient-to-b{
    bottom: 3.5rem !important;
    right: 1.2rem !important;
  }
  .text-xsm {
    font-size: .7rem;
  }

  .text-xxl {
    font-size: 1.4rem;
  }

  /* Compact quantity select for mobile */
  select.cart-item {
    font-size: 16px;
    padding: 1px 4px;
    height: 22px;
    line-height: 20px;
    width: 60px;
  }

  /* Fallback modal styling when Bootstrap JS is not available */
  .modal.custom-fallback {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    display: none;
    /* default hidden */
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    /* darker backdrop */
    z-index: 9999;
  }

  .modal.custom-fallback.show {
    display: flex;
  }

  .modal.custom-fallback .modal-dialog {
    margin: 0;
    width: 92%;
    max-width: 420px;
  }

  .modal.custom-fallback .modal-content {
    border-radius: 8px;
    overflow: hidden;
    background: #ffffff;
    /* solid white panel */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }

  .modal.custom-fallback .modal-header,
  .modal.custom-fallback .modal-body,
  .modal.custom-fallback .modal-footer {
    background: #ffffff;
  }

  .modal.custom-fallback .modal-body {
    padding: 16px;
  }

  body.modal-open {
    overflow: hidden;
  }
</style>
<?php
// Helper function to check if image exists and return placeholder if not

// Product categories data - ready for database integration
$categories = select('*', 'product', '1=1 ORDER BY sort_order');
// Product variants data - ready for database integration

// Pre-load customer-specific prices if a customer is selected
$customerPriceMap = [];
if (isset($get->customer) && (int)$get->customer > 0) {
  $customerId = (int)$get->customer;
  $cpResult = select('product_variance_id, price', 'customer_product_variance', "customer_id = $customerId");
  while ($cp = mysqli_fetch_object($cpResult)) {
    $customerPriceMap[(int)$cp->product_variance_id] = (float)$cp->price;
  }
}

$variance = select('*', 'product_variance', 'visible=1 ORDER BY sort_order');
$variants = [];
while ($v = mysqli_fetch_object($variance)) {
  $price = isset($customerPriceMap[$v->id]) ? $customerPriceMap[$v->id] : $v->price;
  $v = [
    'id' => $v->id,
    'product_id' => $v->product_id,
    'name' => $v->particulars,
    'price' => $price,
    'size' => $v->size,
    'pack' => $v->unit,
    'image' => getImageOrPlaceholder($v->image, $v->particulars)
  ];
  array_push($variants, $v);
}

$products = [];

foreach ($categories as $category) {
  $category_variants = [
    'category_id' => $category['id'],
    'category_name' => $category['name'],
    'variants' => array_filter($variants, function ($v) use ($category) {
      return $v['product_id'] == $category['id'];
    })
  ];
  array_push($products, $category_variants);
}
?>

<!-- Header Section -->
<div class="bg-gradient-to-br from-primary to-secondary text-white">
  <!-- Search Bar -->
  <div class="px-4 pt-8 pb-4">
    <div class="relative max-w-md mx-auto">
      <input type="text" id="searchInput" placeholder="Search products..." class="w-full px-4 py-3 rounded-full text-gray-800 pl-12 focus:outline-none focus:ring-2 focus:ring-white">
      <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
    </div>
  </div>

  <!-- Welcome Section -->
  <div class="text-center">
    <div class="w-20 h-20 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
      <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-blue-500 rounded-full"></div>
    </div>
    <h2 class="text-xl text-black font-semibold">Good Morning</h2>
    <p class="text-lg text-black">Sagor</p>
  </div>

</div>

<!-- Product Categories Row (circular badges with blue ring) -->
<?php
// Build badge text using the first variant name per category (e.g., 500ml X 12)
$categoryBadges = [];
foreach ($products as $pg) {
  $cid = $pg['category_id'];
  if (!isset($categoryBadges[$cid]) && !empty($pg['variants']) && isset($pg['variants'][0]['particulars'])) {
    $categoryBadges[$cid] = strtoupper($pg['variants'][0]['particulars']);
  }
}
?>
<div class="bg-white px-1 py-1">
  <div class="flex items-center justify-between px-2 py-2">
    <h3 class="font-bold text-brandBlue text-base">Categories</h3>
    <div class="flex items-center gap-2">
      <button class="category-prev rounded-full w-8 h-8 border border-gray-300 text-gray-600 flex items-center justify-center hover:bg-gray-50" aria-label="Previous">‹</button>
      <button class="category-next rounded-full w-8 h-8 border border-gray-300 text-gray-600 flex items-center justify-center hover:bg-gray-50" aria-label="Next">›</button>
    </div>
  </div>
  <!-- Category Slider Viewport -->
  <div class="category-viewport overflow-hidden px-1 relative">
    <div class="category-track flex gap-4 will-change-transform">
      <?php foreach ($categories as $category): ?>
        <div class="category-card flex-shrink-0 text-center cursor-pointer" style="width: calc(20.5% - 2.25px); min-width: calc(18.5% - 2.25px); flex: 0 0 calc(18.5% - 2.25px);" onclick="scrollToCategory('<?php echo strtolower(str_replace(' ', '-', $category['name'])); ?>')">
          <div class="relative w-18 h-18 rounded-full flex items-center justify-center">
            <img src="<?php echo getImageOrPlaceholder($category['image'], $category['name']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="w-16 h-16 rounded-full object-cover">
          </div>
          <p class="mt-1 text-gray-800" style="font-size: .67rem;"><?php echo $category['name']; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Product Variants Rows (horizontal sliders) -->
<div class="space-y-2">
  <?php foreach ($products as $productGroup): ?>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden product-section" id="<?php echo strtolower(str_replace(' ', '-', $productGroup['category_name'])); ?>" data-category="<?php echo strtolower($productGroup['category_name']); ?>">
      <!-- Category Header -->
      <div class="px-2 bg-white flex items-center justify-between">
        <h3 class="font-bold text-brandBlue text-base"><?php echo $productGroup['category_name']; ?></h3>
        <div class="flex items-center gap-0">
          <button class="slider-prev rounded-full w-8 h-8 border border-gray-300 text-gray-600 flex items-center justify-center hover:bg-gray-50" aria-label="Previous">‹</button>
          <button class="slider-next rounded-full w-8 h-8 border border-gray-300 text-gray-600 flex items-center justify-center hover:bg-gray-50" aria-label="Next">›</button>
        </div>
      </div>
      <!-- Slider Viewport -->
      <div class="relative -ml-4" style='overflow: auto'>
        <div class="slider-track flex gap-0 will-change-transform pb-2 pl-4 pr-4 -mx-6">
          <?php foreach ($productGroup['variants'] as $product): ?>
            <div class="slider-card overflow-visible product-item bg-white flex-shrink-0 relative" style="width: calc(36%); min-width: calc(36%);" data-name="<?php echo strtolower($product['name']); ?>" data-category="<?php echo strtolower($productGroup['category_name']); ?>" data-size="<?php echo strtolower($product['size']); ?>">
              <!-- Floating Quantity Badge (Right) -->
              <div class="absolute z-10" style="top: 0.5rem;right: 0.8rem;">
                <div class="qty-badge bg-white text-black rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs cursor-pointer border border-gray-300 hover:border-gray-400 transition-colors" data-product="<?php echo $product['id']; ?>" style="display: none;">0</div>
              </div>
              <!-- Close Button (Left) -->
              <div class="absolute z-10 qty-close-btn" style="top: 0.5rem;left: 0.8rem; display: none;">
                <div class="bg-white text-gray-500 rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs cursor-pointer border border-gray-300 hover:border-gray-400 transition-colors" data-product="<?php echo $product['id']; ?>">✕</div>
              </div>
              <!-- Hidden select for form submission -->
              <select class="cart-item hidden" data-product="<?php echo $product['id']; ?>" aria-label="Quantity">
                <?php for ($counter = 0; $counter <= 10; $counter++): ?>
                  <option value="<?php echo $counter; ?>"><?php echo $counter; ?></option>
                <?php endfor; ?>
                <option value="__custom__">Enter quantity...</option>
              </select>
              <!-- Image Area -->
              <div class="px-1.5 py-0.25">
                <div class="bg-gradient-to-b from-blue-50 to-white flex items-end justify-center overflow-hidden" style="aspect-ratio: 1; border-radius: 15px; border: solid 1px #efefef;">
                  <?php
                  $imageSrc = getImageOrPlaceholder($product['image'], $product['name']);
                  if (file_exists($product['image'])):
                  ?>
                    <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-full object-contain rounded-md">
                  <?php else: ?>
                    <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="object-contain opacity-80 rounded-md">
                  <?php endif; ?>
                </div>
              </div>
              <!-- Product Name -->
              <div class="text-left text-[10px] text-black px-2 py-2 pb-0 line-clamp-2" style="white-space: nowrap"><?php echo htmlspecialchars($product['name']); ?></div>
              <!-- Blue Footer with Category -->
              <!-- <div class="text-center bg-brandBlue text-black text-xxl font-semibold"><?php echo $productGroup['category_name']; ?></div> -->
              <!-- Bottom Row: price only (select moved to top) -->
              <div class="flex items-center justify-center px-2">
                <div class="font-lexend text-[16px] text-bold" style="color:red"><span class="text-[8px]">RM</span> <?php echo number_format($product['price'], 2); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Bottom Spacing -->
<div class="h-8"></div>

<!-- Hidden Order Form and Floating Action Button -->
<form method="post" action="?page=invoice" id="form-order"></form>
<input type='hidden' name='UID' value='<?php print $get->uid; ?>' >
<button id="proceedToInvoice"
  class="fixed bottom-4 right-4 z-40 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg w-14 h-14 flex items-center justify-center focus:outline-none active:opacity-90 border-2 border-blue-700"
  type="button"
  aria-label="Open Cart / Proceed to Invoice">
  <!-- Cart Icon (inline SVG to avoid external deps) -->
  <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 9m12-9l-2 9m-6 0h8M7 22a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z" />
  </svg>
</button>

<!-- Customer Select Modal (Bootstrap compatible) -->
<div class="modal fade custom-fallback" id="orderModalHome" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Select Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class='form-group'>
          <label class="form-label">Customer</label>
          <select class="form-select border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" id="customerSelectHome">
            <option value=''>Please select</option>
            <?php
            // Uses same DB tables as other pages
            $customers = R::find('customer', 'id > 0 order by company');
            foreach ($customers as $key => $customer) {
              $selected = (isset($get->customer) && $get->customer == $customer->id) ? 'selected' : '';
              print "<option value='$customer->id' $selected>$customer->company</option>";
            }
            ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary border border-gray-300 rounded-md px-4 py-2 bg-white hover:bg-gray-50" data-bs-dismiss="modal">Close</button>
        <button type="button" id="confirmCustomerHome" class="btn btn-primary border border-blue-700 rounded-md px-4 py-2 bg-blue-600 text-white hover:bg-blue-700">Continue</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade custom-fallback" id="qtyModalHome" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Enter Quantity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class='form-group'>
          <label class="form-label">Quantity</label>
          <input type="tel" pattern="[0-9]*" class="form-control border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" id="qtyInputHome" inputmode="numeric" autocomplete="off">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary border border-gray-300 rounded-md px-4 py-2 bg-white hover:bg-gray-50" data-bs-dismiss="modal" id="qtyCancelHome">Cancel</button>
        <button type="button" class="btn btn-primary border border-blue-700 rounded-md px-4 py-2 bg-blue-600 text-white hover:bg-blue-700" id="qtyConfirmHome">OK</button>
      </div>
    </div>
  </div>
</div>

<style>
  .scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }

  .scrollbar-hide::-webkit-scrollbar {
    display: none;
  }

  /* Slider sizing: show ~2.5 items on all screens */
  .slider-card {
    flex: 0 0 40% !important;
  }

  @media (max-width: 479px) {
    .slider-card {
      aspect-ratio: 4 / 5;
    }
  }

  @media (min-width: 480px) {
    .slider-card {
      flex-basis: 40% !important;
    }
  }

  .slider-track {
    transition: transform .35s ease;
    margin-left: .4rem !important;
  }

  .slider-viewport {
    touch-action: pan-y;
    cursor: grab;
  }

  .cursor-grabbing {
    cursor: grabbing;
  }

  /* Category slider sizing: show ~4.5 items on small screens, 5 on >=480px */
  .category-card {
    flex: 0 0 var(--item-width, calc(100%/4.5));
  }

  @media (min-width: 480px) {
    .category-card {
      flex-basis: calc(100%/5);
    }
  }

  .category-track {
    transition: transform .35s ease;
  }

  .category-viewport {
    touch-action: pan-y;
    cursor: grab;
  }
</style>

<script>
  // Helpers to read current translateX from computed matrix
  function getTranslateX(el) {
    const style = getComputedStyle(el);
    const tr = style.transform;
    if (!tr || tr === 'none') return 0;
    // matrix(a,b,c,d,tx,ty)
    const m = tr.match(/matrix\(([^)]+)\)/);
    if (m) {
      const parts = m[1].split(',').map(parseFloat);
      return parts[4] || 0;
    }
    // matrix3d(..., tx, ty, tz)
    const m3 = tr.match(/matrix3d\(([^)]+)\)/);
    if (m3) {
      const parts = m3[1].split(',').map(parseFloat);
      return parts[12] || 0;
    }
    return 0;
  }

  // Category slider logic: infinite loop, prev/next, 4.5–5 items visible
  function initCategorySlider() {
    const viewport = document.querySelector('.category-viewport');
    const track = document.querySelector('.category-track');
    if (!viewport || !track) return;

    const prevBtn = document.querySelector('.category-prev');
    const nextBtn = document.querySelector('.category-next');

    // Clone edges for infinite loop
    const original = Array.from(track.children);
    const CLONES = Math.min(5, original.length);
    if (CLONES === 0) return; // nothing to show
    original.slice(-CLONES).forEach(n => track.insertBefore(n.cloneNode(true), track.firstChild));
    original.slice(0, CLONES).forEach(n => track.appendChild(n.cloneNode(true)));

    let index = CLONES; // start after prepended clones

    function scrollToIndex(animate = true) {
      track.style.transition = animate ? 'transform .35s ease' : 'none';
      const cards = Array.from(track.children);
      const target = cards[index];
      const offsetLeft = target.offsetLeft - cards[0].offsetLeft;
      track.style.transform = `translateX(${-offsetLeft}px)`;
    }

    function normalize() {
      const total = original.length;
      if (index >= CLONES + total) {
        index -= total;
        scrollToIndex(false);
      }
      if (index < CLONES) {
        index += total;
        scrollToIndex(false);
      }
    }

    prevBtn && prevBtn.addEventListener('click', () => {
      index -= 1;
      scrollToIndex(true);
      setTimeout(normalize, 370);
    });
    nextBtn && nextBtn.addEventListener('click', () => {
      index += 1;
      scrollToIndex(true);
      setTimeout(normalize, 370);
    });

    // Drag/Swipe to scroll (mouse + touch)
    let isDragging = false,
      startX = 0,
      startTX = 0;

    const onDown = (clientX) => {
      isDragging = true;
      startX = clientX;
      startTX = getTranslateX(track);
      track.style.transition = 'none';
      viewport.classList.add('cursor-grabbing');
    };
    const onMove = (clientX) => {
      if (!isDragging) return;
      const dx = clientX - startX;
      track.style.transform = `translateX(${startTX + dx}px)`;
    };
    const onUp = () => {
      if (!isDragging) return;
      isDragging = false;
      viewport.classList.remove('cursor-grabbing');
      const cards = Array.from(track.children);
      const step = (cards[index + 1] ? (cards[index + 1].offsetLeft - cards[index].offsetLeft) : cards[index].offsetWidth);
      const threshold = step * 0.12;
      const drag = getTranslateX(track) - startTX;

      if (drag <= -threshold) {
        index = Math.min(index + 1, cards.length - 1);
      } else if (drag >= threshold) {
        index = Math.max(index - 1, 0);
      } else {
        // snap to nearest card
        let nearest = index;
        let minDist = Infinity;
        const base = cards[0].offsetLeft;
        const currentTX = -getTranslateX(track);
        cards.forEach((card, i) => {
          const dist = Math.abs((card.offsetLeft - base) - currentTX);
          if (dist < minDist) {
            minDist = dist;
            nearest = i;
          }
        });
        index = nearest;
      }
      scrollToIndex(true);
      setTimeout(normalize, 370);
    };

    // Mouse events
    viewport.addEventListener('mousedown', e => {
      if (e.target && e.target.closest && e.target.closest('select.cart-item')) return; // allow select to open
      e.preventDefault();
      onDown(e.clientX);
    });
    window.addEventListener('mousemove', e => onMove(e.clientX));
    window.addEventListener('mouseup', onUp);

    // Touch events
    viewport.addEventListener('touchstart', e => {
      if (e.target && e.target.closest && e.target.closest('select.cart-item')) return; // allow select to open
      if (e.touches[0]) onDown(e.touches[0].clientX);
    }, {
      passive: true
    });
    viewport.addEventListener('touchmove', e => {
      if (e.touches[0]) onMove(e.touches[0].clientX);
    }, {
      passive: true
    });
    window.addEventListener('touchend', onUp);

    // Initial position
    requestAnimationFrame(() => scrollToIndex(false));
    window.addEventListener('resize', () => scrollToIndex(false));
  }

  // Slider logic: infinite loop, prev/next, 3.5–4 items visible
  function initSliders() {
    document.querySelectorAll('.product-section').forEach(section => {
      const viewport = section.querySelector('.slider-viewport');
      const track = section.querySelector('.slider-track');
      if (!viewport || !track) return;

      const prevBtn = section.querySelector('.slider-prev');
      const nextBtn = section.querySelector('.slider-next');

      // Clone edges for infinite loop
      const original = Array.from(track.children);
      const CLONES = Math.min(4, original.length);
      if (CLONES === 0) return; // nothing to show
      original.slice(-CLONES).forEach(n => track.insertBefore(n.cloneNode(true), track.firstChild));
      original.slice(0, CLONES).forEach(n => track.appendChild(n.cloneNode(true)));

      let index = CLONES; // start after prepended clones

      function scrollToIndex(animate = true) {
        track.style.transition = animate ? 'transform .35s ease' : 'none';
        const cards = Array.from(track.children);
        const target = cards[index];
        const offsetLeft = target.offsetLeft - cards[0].offsetLeft;
        track.style.transform = `translateX(${-offsetLeft}px)`;
      }

      function normalize() {
        const total = original.length;
        if (index >= CLONES + total) {
          index -= total;
          scrollToIndex(false);
        }
        if (index < CLONES) {
          index += total;
          scrollToIndex(false);
        }
      }

      prevBtn && prevBtn.addEventListener('click', () => {
        index -= 1;
        scrollToIndex(true);
        setTimeout(normalize, 370);
      });
      nextBtn && nextBtn.addEventListener('click', () => {
        index += 1;
        scrollToIndex(true);
        setTimeout(normalize, 370);
      });

      // Drag/Swipe to scroll (mouse + touch)
      let isDragging = false,
        startX = 0,
        startTX = 0;

      const onDown = (clientX) => {
        isDragging = true;
        startX = clientX;
        startTX = getTranslateX(track);
        track.style.transition = 'none';
        viewport.classList.add('cursor-grabbing');
      };
      const onMove = (clientX) => {
        if (!isDragging) return;
        const dx = clientX - startX;
        track.style.transform = `translateX(${startTX + dx}px)`;
      };
      const onUp = () => {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove('cursor-grabbing');
        const cards = Array.from(track.children);
        const step = (cards[index + 1] ? (cards[index + 1].offsetLeft - cards[index].offsetLeft) : cards[index].offsetWidth);
        const threshold = step * 0.12;
        const drag = getTranslateX(track) - startTX;

        if (drag <= -threshold) {
          index = Math.min(index + 1, cards.length - 1);
        } else if (drag >= threshold) {
          index = Math.max(index - 1, 0);
        } else {
          // snap to nearest card
          let nearest = index;
          let minDist = Infinity;
          const base = cards[0].offsetLeft;
          const currentTX = -getTranslateX(track);
          cards.forEach((card, i) => {
            const dist = Math.abs((card.offsetLeft - base) - currentTX);
            if (dist < minDist) {
              minDist = dist;
              nearest = i;
            }
          });
          index = nearest;
        }
        scrollToIndex(true);
        setTimeout(normalize, 370);
      };

      // Mouse events
      viewport.addEventListener('mousedown', e => {
        if (e.target && e.target.closest && e.target.closest('select.cart-item')) return; // allow select to open
        e.preventDefault();
        onDown(e.clientX);
      });
      window.addEventListener('mousemove', e => onMove(e.clientX));
      window.addEventListener('mouseup', onUp);

      // Touch events
      viewport.addEventListener('touchstart', e => {
        if (e.target && e.target.closest && e.target.closest('select.cart-item')) return; // allow select to open
        if (e.touches[0]) onDown(e.touches[0].clientX);
      }, {
        passive: true
      });
      viewport.addEventListener('touchmove', e => {
        if (e.touches[0]) onMove(e.touches[0].clientX);
      }, {
        passive: true
      });
      window.addEventListener('touchend', onUp);

      // Initial position
      requestAnimationFrame(() => scrollToIndex(false));
      window.addEventListener('resize', () => scrollToIndex(false));
    });
  }

  // Add smooth scrolling behavior (existing horizontal drags)
  document.querySelectorAll('.overflow-x-auto').forEach(container => {
    let isDown = false;
    let startX;
    let scrollLeft;

    container.addEventListener('mousedown', (e) => {
      isDown = true;
      startX = e.pageX - container.offsetLeft;
      scrollLeft = container.scrollLeft;
    });

    container.addEventListener('mouseleave', () => {
      isDown = false;
    });

    container.addEventListener('mouseup', () => {
      isDown = false;
    });

    container.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - container.offsetLeft;
      const walk = (x - startX) * 2;
      container.scrollLeft = scrollLeft - walk;
    });
  });

  // Search functionality
  document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const productSections = document.querySelectorAll('.product-section');
    const productItems = document.querySelectorAll('.product-item');

    if (searchTerm === '') {
      // Show all sections and items
      productSections.forEach(section => {
        section.style.display = 'block';
      });
      productItems.forEach(item => {
        item.style.display = 'block';
      });
      return;
    }

    // Hide all sections first
    productSections.forEach(section => {
      section.style.display = 'none';
    });

    // Show sections that have matching products
    productItems.forEach(item => {
      const productName = item.dataset.name;
      const categoryName = item.dataset.category;
      const productSize = item.dataset.size;

      if (productName.includes(searchTerm) ||
        categoryName.includes(searchTerm) ||
        productSize.includes(searchTerm)) {
        item.style.display = 'block';
        // Show the parent section
        const section = item.closest('.product-section');
        if (section) {
          section.style.display = 'block';
        }
      } else {
        item.style.display = 'none';
      }
    });
  });

  // Scroll to category function
  function scrollToCategory(categoryId) {
    const element = document.getElementById(categoryId);
    if (element) {
      element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });

      // Add a highlight effect
      element.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
      setTimeout(() => {
        element.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
      }, 2000);
    }
  }
  // Initialize sliders when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initCategorySlider();
      initSliders();
      initInvoiceFlow();
    });
  } else {
    initCategorySlider();
    initSliders();
    initInvoiceFlow();
  }

  // Order collection and submission
  function initInvoiceFlow() {
    const proceedBtn = document.getElementById('proceedToInvoice');
    const formOrder = document.getElementById('form-order');
    const customerSelect = document.getElementById('customerSelectHome');
    const confirmCustomerBtn = document.getElementById('confirmCustomerHome');

    const qtyModalEl = document.getElementById('qtyModalHome');
    const qtyInputEl = document.getElementById('qtyInputHome');
    const qtyConfirmBtn = document.getElementById('qtyConfirmHome');
    const qtyCancelBtn = document.getElementById('qtyCancelHome');
    let qtyTargetSelect = null;

    qtyInputEl && qtyInputEl.addEventListener('input', () => {
      qtyInputEl.value = qtyInputEl.value.replace(/\D+/g, '');
    });

    qtyInputEl && qtyInputEl.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        qtyConfirmBtn && qtyConfirmBtn.click();
      }
    });

    qtyModalEl && qtyModalEl.addEventListener('shown.bs.modal', () => {
      qtyInputEl && qtyInputEl.focus();
    });

    function openQtyModal(targetSelect) {
      qtyTargetSelect = targetSelect;
      if (qtyInputEl) qtyInputEl.value = '';

      try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          const m = bootstrap.Modal.getOrCreateInstance(qtyModalEl);
          m.show();
          setTimeout(() => qtyInputEl && qtyInputEl.focus(), 50);
        } else {
          qtyModalEl.classList.add('custom-fallback', 'show');
          qtyModalEl.style.display = 'flex';
          document.body.classList.add('modal-open');
          setTimeout(() => qtyInputEl && qtyInputEl.focus(), 50);
        }
      } catch (e) {
        qtyModalEl.classList.add('custom-fallback', 'show');
        qtyModalEl.style.display = 'flex';
        document.body.classList.add('modal-open');
        setTimeout(() => qtyInputEl && qtyInputEl.focus(), 50);
      }
    }

    function closeQtyModal() {
      try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          const m = bootstrap.Modal.getOrCreateInstance(qtyModalEl);
          m.hide();
        } else {
          qtyModalEl.classList.remove('show');
          qtyModalEl.style.display = 'none';
          qtyModalEl.classList.remove('custom-fallback');
          document.body.classList.remove('modal-open');
        }
      } catch (e) {
        qtyModalEl.classList.remove('show');
        qtyModalEl.style.display = 'none';
        qtyModalEl.classList.remove('custom-fallback');
        document.body.classList.remove('modal-open');
      }
    }

    function applyQtyToSelect(sel, qty) {
      const num = parseInt(String(qty).trim(), 10);
      if (!Number.isFinite(num) || num < 0) {
        sel.value = '0';
        return;
      }

      const val = String(num);
      let opt = Array.from(sel.options).find(o => o.value === val);
      if (!opt) {
        opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val;
        sel.appendChild(opt);
      }
      sel.value = val;
    }

    // Update all badges for a product
    function updateAllBadges(productId, sel) {
      const qty = parseInt(sel.value || '0', 10);
      document.querySelectorAll(`.qty-badge[data-product="${productId}"]`).forEach(badge => {
        badge.textContent = qty;
        badge.style.display = qty > 0 ? 'flex' : 'none';
      });
      // Show/hide close button
      const card = sel.closest('.product-item');
      if (card) {
        const closeBtn = card.querySelector('.qty-close-btn');
        if (closeBtn) {
          closeBtn.style.display = qty > 0 ? 'block' : 'none';
        }
      }
    }

    // Handle badge click to increment quantity
    document.querySelectorAll('.qty-badge').forEach(badge => {
      badge.addEventListener('click', (e) => {
        e.stopPropagation();
        const productId = badge.getAttribute('data-product');
        const sel = document.querySelector(`select.cart-item[data-product="${productId}"]`);
        if (!sel) return;
        let qty = parseInt(sel.value || '0', 10) + 1;
        if (qty > 10) {
          const opt = document.createElement('option');
          opt.value = String(qty);
          opt.textContent = String(qty);
          sel.appendChild(opt);
        }
        sel.value = String(qty);
        updateAllBadges(productId, sel);
      });
    });

    // Handle close button click to reset quantity to 0
    document.querySelectorAll('.qty-close-btn').forEach(closeBtn => {
      const closeIcon = closeBtn.querySelector('div');
      closeIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        const productId = closeIcon.getAttribute('data-product');
        const sel = document.querySelector(`select.cart-item[data-product="${productId}"]`);
        if (!sel) return;
        sel.value = '0';
        updateAllBadges(productId, sel);
      });
    });

    // Update badge when select changes
    document.querySelectorAll('select.cart-item').forEach(sel => {
      const productId = sel.getAttribute('data-product');
      
      sel.addEventListener('change', () => {
        updateAllBadges(productId, sel);
        if (sel.value !== '__custom__') return;
        openQtyModal(sel);
      });
    });

    qtyConfirmBtn && qtyConfirmBtn.addEventListener('click', () => {
      if (!qtyTargetSelect) {
        closeQtyModal();
        return;
      }
      applyQtyToSelect(qtyTargetSelect, qtyInputEl ? qtyInputEl.value : '0');
      const productId = qtyTargetSelect.getAttribute('data-product');
      updateAllBadges(productId, qtyTargetSelect);
      qtyTargetSelect = null;
      closeQtyModal();
    });

    qtyCancelBtn && qtyCancelBtn.addEventListener('click', () => {
      if (qtyTargetSelect) qtyTargetSelect.value = '0';
      const productId = qtyTargetSelect.getAttribute('data-product');
      updateAllBadges(productId, qtyTargetSelect);
      qtyTargetSelect = null;
      closeQtyModal();
    });

    // Click card to increment quantity (mobile-friendly)
    document.querySelectorAll('.product-item').forEach(card => {
      card.addEventListener('click', (e) => {
        // avoid increment when clicking on badge or select
        if (e.target && (e.target.tagName === 'SELECT' || e.target.closest('select') || e.target.closest('.qty-badge'))) return;
        const sel = card.querySelector('select.cart-item');
        if (!sel) return;
        let qty = parseInt(sel.value || '0', 10) + 1;
        if (qty > 10) {
          const opt = document.createElement('option');
          opt.value = String(qty);
          opt.textContent = String(qty);
          sel.appendChild(opt);
        }
        sel.value = String(qty);
        const productId = sel.getAttribute('data-product');
        updateAllBadges(productId, sel);
      });
    });

    function buildHiddenInputs() {
      // Clear previous inputs
      while (formOrder.firstChild) formOrder.removeChild(formOrder.firstChild);
      const selects = document.querySelectorAll('select.cart-item');
      let count = 0;
      selects.forEach(sel => {
        const qty = parseInt(sel.value || '0', 10);
        if (qty > 0) {
          const id = sel.getAttribute('data-product');
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = `product[${id}]`;
          inp.value = String(qty);
          formOrder.appendChild(inp);
          count++;
        }
      });
      return count;
    }

    function submitWithOptionalCustomer() {
      // If customer chosen in modal, append it
      if (customerSelect && customerSelect.value) {
        const c = document.createElement('input');
        c.type = 'hidden';
        c.name = 'customer_id';
        c.value = customerSelect.value;
        formOrder.appendChild(c);
      }
      formOrder.submit();
    }

    proceedBtn && proceedBtn.addEventListener('click', () => {
      const count = buildHiddenInputs();
      if (count === 0) {
        // Lightweight feedback for mobile
        if (window.alert) alert('Please select at least one item. Tap a product to increase its quantity or use the dropdown.');
        return;
      }

      // Always open customer selection modal before submit (like sell3.php)
      const modalEl = document.getElementById('orderModalHome');
      try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          const m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else {
          // Minimal fallback to show modal without Bootstrap JS
          modalEl.classList.add('custom-fallback', 'show');
          modalEl.style.display = 'flex';
          document.body.classList.add('modal-open');
        }
      } catch (e) {
        // Last resort fallback
        modalEl.classList.add('custom-fallback', 'show');
        modalEl.style.display = 'flex';
        document.body.classList.add('modal-open');
      }
    });

    confirmCustomerBtn && confirmCustomerBtn.addEventListener('click', () => {
      if (customerSelect && !customerSelect.value) {
        if (window.alert) alert('Please select a customer to continue.');
        return;
      }
      // Close modal
      try {
        const modalEl = document.getElementById('orderModalHome');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          const m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.hide();
        } else {
          modalEl.classList.remove('show');
          modalEl.style.display = 'none';
          modalEl.classList.remove('custom-fallback');
          document.body.classList.remove('modal-open');
        }
      } catch (e) {
        /* ignore */
      }
      submitWithOptionalCustomer();
    });
  }
</script>