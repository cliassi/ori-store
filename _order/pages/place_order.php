<?php
// Build items from POST
$items = [];
if (isset($post->product) && is_array($post->product)) {
  foreach ($post->product as $pid => $qty) {
    $safePid = htmlspecialchars($pid);
    $qty = max(0, (int)$qty);
    $name = isset($post->product_name[$pid]) ? htmlspecialchars($post->product_name[$pid]) : '';
    $price = isset($post->product_price[$pid]) ? (float)$post->product_price[$pid] : 0.0;
    $image = isset($post->product_image[$pid]) ? htmlspecialchars($post->product_image[$pid]) : '';
    if ($qty > 0) {
      $items[] = [
        'id' => $safePid,
        'name' => $name,
        'price' => $price,
        'qty' => $qty,
        'image' => $image,
      ];
    }
  }
}
?>

<style>
  html,body{max-width:100%;overflow-x:hidden;}
  *,*::before,*::after{box-sizing:border-box;}
  .po-container{max-width:720px;margin:0 auto;padding:10px 10px 90px;}
  .po-card{display:flex;align-items:center;gap:10px;background:#fff;border-radius:12px;padding:8px 10px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
  .po-row{display:grid;grid-template-columns:1fr;gap:8px;}
  .po-thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;background:#f3f4f6;flex:0 0 auto;}
  .po-thumb img{width:100%;height:100%;object-fit:cover;}
  .po-name{font-weight:600;color:#0f172a;font-size:13px;}
  .po-unit{color:#6b7280;font-size:11px;}
  .po-total{font-weight:700;color:#0f172a;font-size:13px;}
  .po-qty-pill{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;border-radius:9999px;padding:3px 8px;font-size:12px;color:#111827;cursor:pointer;}
  .po-qty{display:none;align-items:center;gap:8px;background:#fff;border:1px solid #e5e7eb;border-radius:9999px;padding:3px 6px;}
  .po-btn{width:22px;height:22px;border-radius:9999px;border:1px solid #d1d5db;background:#fff;color:#111827;display:flex;align-items:center;justify-content:center;font-weight:700;}
  .po-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;min-width:92px;}
  .po-qty-wrap{display:flex;align-items:center;gap:8px;}
  .po-footer{position:fixed;left:0;right:0;bottom:10px;transform:translateZ(0);width:calc(100% - 40px);max-width:720px;margin-left:auto;margin-right:auto;background:#ffffff;color:#0f172a;border-radius:12px;padding:10px 12px calc(12px + env(safe-area-inset-bottom));box-shadow:0 6px 16px rgba(0,0,0,.12);} 
  .po-progress{height:3px;background:#f97316;border-radius:9999px;width:28%;margin:0 4px 8px;}
  .po-foot-inner{display:flex;align-items:center;justify-content:space-between;gap:12px;}
  .po-left{display:flex;flex-direction:column;gap:2px;}
  .po-grand{font-weight:800;font-size:18px;color:#0f172a;}
  .po-breakdown{background:transparent;border:none;color:#0ea5e9;text-align:left;padding:0;margin:0;font-size:12px;}
  .po-cta{background:#22b55e;color:#ffffff;border:none;border-radius:9999px;padding:10px 18px;font-weight:700;}
  .po-back{display:inline-flex;align-items:center;gap:8px;background:#f3f4f6;border:1px solid #e5e7eb;padding:10px 12px;border-radius:9999px;color:#111827;font-weight:700;font-size:14px;line-height:1;min-width:48px;justify-content:center;}
  .po-back:active{opacity:.75;}
  .po-back .po-back-arrow{font-size:18px;line-height:0;}
  .scrollbar-hide::-webkit-scrollbar{display:none}
  .scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}
</style>

<div class="po-container">
  <div class="mb-3" style="display:flex; align-items:center; gap:10px;">
    <button type="button" style="margin-top:-10px" class="po-back" onclick="window.history.back();" aria-label="Back">
      <span class="po-back-arrow"><-</span>
    </button>
    <h2 class="text-lg font-bold mb-0 text-center" style="margin-top:20px;color:#0f172a; flex:1 1 auto;">Your Order</h2>
    <div style="width:54px"></div>
  </div>

  <form id="placeOrderForm" method="post" action="?page=invoice">
    <input type='hidden' name='UID' value='<?php echo isset($get->uid) ? htmlspecialchars($get->uid) : ''; ?>'>
    <input type='hidden' name='customer_id' value='<?php echo isset($_SESSION["UID"]) ? htmlspecialchars($_SESSION["UID"]) : ""; ?>'>

    <div id="poList" class="po-row">
      <?php foreach ($items as $it): ?>
        <div class="po-card" data-id="<?php echo $it['id']; ?>" data-price="<?php echo htmlspecialchars($it['price']); ?>">
          <div class="po-thumb">
            <?php if (!empty($it['image'])): ?>
              <img src="<?php echo $it['image']; ?>" alt="<?php echo $it['name']; ?>">
            <?php endif; ?>
          </div>
          <div style="flex:1 1 auto;min-width:0">
            <div class="po-name line-clamp-2" style="white-space:normal;overflow-wrap:anywhere;word-break:break-word;"><?php echo $it['name']; ?></div>
            <div class="po-unit">Unit: RM <?php echo number_format($it['price'], 2); ?></div>
          </div>
          <div class="po-right">
            <div class="po-line price" style="text-align:right;">
              <div class="po-total">RM <span class="po-line-total"><?php echo number_format($it['qty'] * $it['price'], 2); ?></span></div>
            </div>
            <div class="po-qty-wrap">
              <div class="po-qty-pill">Qty: <span class="po-q"><?php echo (int)$it['qty']; ?></span></div>
              <div class="po-qty">
                <button type="button" class="po-btn po-dec" aria-label="Decrease">−</button>
                <span class="po-q"><?php echo (int)$it['qty']; ?></span>
                <button type="button" class="po-btn po-inc" aria-label="Increase">+</button>
              </div>
            </div>
          </div>

          <!-- hidden inputs per item -->
          <input type="hidden" name="product[<?php echo $it['id']; ?>]" value="<?php echo (int)$it['qty']; ?>">
          <input type="hidden" name="product_name[<?php echo $it['id']; ?>]" value="<?php echo $it['name']; ?>">
          <input type="hidden" name="product_price[<?php echo $it['id']; ?>]" value="<?php echo htmlspecialchars($it['price']); ?>">
          <input type="hidden" name="price[<?php echo $it['id']; ?>]" value="<?php echo htmlspecialchars($it['price']); ?>">
          <input type="hidden" name="product_image[<?php echo $it['id']; ?>]" value="<?php echo isset($it['image']) ? $it['image'] : ''; ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
      <div class="mt-4" style="color:#6b7280">Your basket is empty. Go back and add items.</div>
    <?php endif; ?>

    <!-- Footer total and proceed -->
    <div class="po-footer" id="poFooter" style="<?php echo empty($items) ? 'display:none;' : ''; ?>">
      <div class="po-progress"></div>
      <div class="po-foot-inner">
        <div class="po-left">
          <div class="po-grand">RM<span id="poGrand">0.00</span></div>
        </div>
        <button type="submit" name="save" value="1" class="po-cta">Place Order</button>
      </div>
    </div>
  </form>
</div>

<script>
  (function(){
    const list = document.getElementById('poList');
    const footer = document.getElementById('poFooter');
    const grandEl = document.getElementById('poGrand');
    const countEl = document.getElementById('poCount');
    const removalTimers = new Map();

    function recalc(){
      let total = 0; let items = 0;
      list.querySelectorAll('.po-card').forEach(card =>{
        const price = parseFloat(card.getAttribute('data-price')||'0');
        const qEls = card.querySelectorAll('.po-q');
        const q = parseInt((qEls[0]?.textContent)||'0',10);
        const lt = card.querySelector('.po-line-total');
        lt.textContent = (price*q).toFixed(2);
        if(q>0){ total += price*q; items += q; }
        const inp = card.querySelector(`input[name^="product["]`);
        if(inp) inp.value = q;
      });
      if(countEl) countEl.textContent = `${items} ${items===1?'Item':'Items'}`;
      if(grandEl) grandEl.textContent = total.toFixed(2);
      footer.style.display = list.querySelectorAll('.po-card').length? 'block':'none';
    }

    function showControls(card){
      const pill = card.querySelector('.po-qty-pill');
      const controls = card.querySelector('.po-qty');
      if(pill && controls){ pill.style.display='none'; controls.style.display='flex'; }
    }
    function hideControls(card){
      const pill = card.querySelector('.po-qty-pill');
      const controls = card.querySelector('.po-qty');
      if(pill && controls){ pill.style.display='inline-flex'; controls.style.display='none'; }
    }

    function scheduleRemoval(card){
      const id = card.getAttribute('data-id');
      if(removalTimers.has(id)) clearTimeout(removalTimers.get(id));
      const t = setTimeout(()=>{
        if(!document.body.contains(card)) return;
        const q = parseInt(card.querySelector('.po-q').textContent||'0',10);
        if(q===0){ card.remove(); recalc(); }
        removalTimers.delete(id);
      }, 5000);
      removalTimers.set(id, t);
    }

    list.addEventListener('click', (e)=>{
      const t = e.target;
      const card = t.closest('.po-card');
      if(!card) return;
      if(t.closest('.po-qty-pill')){ showControls(card); return; }
      if(t.classList.contains('po-inc')){
        const qEls = card.querySelectorAll('.po-q');
        const q = parseInt((qEls[0]?.textContent)||'0',10)+1;
        qEls.forEach(el=> el.textContent = String(q));
        if(removalTimers.has(card.getAttribute('data-id'))) { clearTimeout(removalTimers.get(card.getAttribute('data-id'))); removalTimers.delete(card.getAttribute('data-id')); }
        recalc();
        return;
      }
      if(t.classList.contains('po-dec')){
        const qEls = card.querySelectorAll('.po-q');
        const q = Math.max(0, parseInt((qEls[0]?.textContent)||'0',10)-1);
        qEls.forEach(el=> el.textContent = String(q));
        if(q===0){ scheduleRemoval(card); }
        recalc();
        return;
      }
    });

    // Hide controls by default
    list.querySelectorAll('.po-card').forEach(hideControls);

    recalc();
  })();
</script>

