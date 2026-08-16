# Access Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement centralized access control for invoice item editing across the store application.

**Architecture:** Add 3 role-based functions to core/functions.php, replace scattered hardcoded checks with these functions, and fix 2 critical security issues in orderadmin.

**Tech Stack:** PHP, MySQL, JavaScript

---

### Task 1: Add Centralized Access Control Functions

**Files:**
- Modify: `C:\wamp64\www\store\core\functions.php:427-428`

- [ ] **Step 1: Add new functions after isUserIn()**

```php
function canEditPriceAndQuantity() {
    return uid() == 1 || uid() == 47;
}

function canEditDateOnly() {
    return uid() == 60;
}

function canEditAnything() {
    return canEditPriceAndQuantity() || canEditDateOnly();
}
```

- [ ] **Step 2: Commit**

```bash
git add core/functions.php
git commit -m "feat: add centralized access control functions"
```

---

### Task 2: Fix orderadmin Security Issues

**Files:**
- Modify: `C:\wamp64\www\store\orderadmin\f.inc.php:1115-1121`
- Modify: `C:\wamp64\www\store\orderadmin\functions.php:254-258`

- [ ] **Step 1: Fix uid() spoofing in orderadmin/f.inc.php**

Replace the uid() function (lines 1115-1121):
```php
function uid(){
    if(isset($_GET['uid'])) return $_GET['uid'];
    global $get;
    if(isset($get->uid)) return $get->uid;
    return 1;
}
```

With:
```php
function uid(){
    return isset($_SESSION[APP.'_id']) ? $_SESSION[APP.'_id'] : 0;
}
```

- [ ] **Step 2: Fix isUserIn() bypass in orderadmin/functions.php**

Replace the isUserIn() function (lines 254-258):
```php
function isUserIn($users = []){return true;
    if(uid() == 1) return true;
    return in_array(strtolower(username()), $users);
}
```

With:
```php
function isUserIn($users = []){
    if(uid() == 1) return true;
    return in_array(strtolower(username()), $users);
}
```

- [ ] **Step 3: Commit**

```bash
git add orderadmin/f.inc.php orderadmin/functions.php
git commit -m "fix: resolve uid spoofing and isUserIn bypass in orderadmin"
```

---

### Task 3: Update ajax/dcollect.php

**Files:**
- Modify: `C:\wamp64\www\store\ajax\dcollect.php:11`
- Modify: `C:\wamp64\www\store\ajax\dcollect.php:392-403`

- [ ] **Step 1: Replace $restrictedUser definition**

Replace line 11:
```php
$restrictedUser = (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));
```

With:
```php
$canEditPriceQty = canEditPriceAndQuantity();
$canEditAnything = canEditAnything();
```

- [ ] **Step 2: Update price display (line 392)**

Replace the price rendering section that uses `$restrictedUser`:
```php
 ($restrictedUser ? "<span>" . ($i->price) . "</span>" : "<a data-id='$i->iid' class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . ($i->price) . "</a>")
```

With:
```php
 (!$canEditPriceQty ? "<span>" . ($i->price) . "</span>" : "<a data-id='$i->iid' class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . ($i->price) . "</a>")
```

- [ ] **Step 3: Update quantity display (line 403)**

Replace the quantity rendering section:
```php
 ($restrictedUser ? "<span>$collected</span>" : "<a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$collected</a>")
```

With:
```php
 (!$canEditPriceQty ? "<span>$collected</span>" : "<a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$collected</a>")
```

- [ ] **Step 4: Commit**

```bash
git add ajax/dcollect.php
git commit -m "refactor: use centralized access control in ajax/dcollect.php"
```

---

### Task 4: Update ajax/update_invoice_item_quantity.php

**Files:**
- Modify: `C:\wamp64\www\store\ajax\update_invoice_item_quantity.php:10`
- Modify: `C:\wamp64\www\store\ajax\update_invoice_item_quantity.php:16`

- [ ] **Step 1: Replace $restrictedUser definition**

Replace line 10:
```php
$restrictedUser = (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));
```

With:
```php
$canEditPriceQty = canEditPriceAndQuantity();
```

- [ ] **Step 2: Update access check**

Replace line 16:
```php
if ($restrictedUser) exit;
```

With:
```php
if (!$canEditPriceQty) exit;
```

- [ ] **Step 3: Commit**

```bash
git add ajax/update_invoice_item_quantity.php
git commit -m "refactor: use centralized access control in quantity update"
```

---

### Task 5: Update ajax/update_invoice_item_price.php

**Files:**
- Modify: `C:\wamp64\www\store\ajax\update_invoice_item_price.php`

- [ ] **Step 1: Check file and apply same pattern as Task 4**

Replace $restrictedUser definition with:
```php
$canEditPriceQty = canEditPriceAndQuantity();
```

Replace access check with:
```php
if (!$canEditPriceQty) exit;
```

- [ ] **Step 2: Commit**

```bash
git add ajax/update_invoice_item_price.php
git commit -m "refactor: use centralized access control in price update"
```

---

### Task 6: Update app/pages/dcollect.php

**Files:**
- Modify: `C:\wamp64\www\store\app\pages\dcollect.php:544`
- Modify: `C:\wamp64\www\store\app\pages\dcollect.php:546-555`

- [ ] **Step 1: Replace JavaScript variables**

Replace line 544:
```php
var __restrictedUser = <?php echo (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar')) ? 'true' : 'false'; ?>;
```

With:
```php
var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;
```

- [ ] **Step 2: Update setItemId function**

Replace lines 546-549:
```php
function setItemId(id) {
    if (__restrictedUser) return;
    $('#invoice_item_id').val(id);
}
```

With:
```php
function setItemId(id) {
    if (!__canEditPriceQty) return;
    $('#invoice_item_id').val(id);
}
```

- [ ] **Step 3: Update setItemIdPrice function**

Replace lines 551-555:
```php
function setItemIdPrice(id, price) {
    if (__restrictedUser) return;
    $("#new-price").val(price);
    setItemId(id);
}
```

With:
```php
function setItemIdPrice(id, price) {
    if (!__canEditPriceQty) return;
    $("#new-price").val(price);
    setItemId(id);
}
```

- [ ] **Step 4: Commit**

```bash
git add app/pages/dcollect.php
git commit -m "refactor: use centralized access control in dcollect page"
```

---

### Task 7: Update app/pages/dcollect_pending.php

**Files:**
- Modify: `C:\wamp64\www\store\app\pages\dcollect_pending.php:206`

- [ ] **Step 1: Replace JavaScript variable**

Replace the __restrictedUser variable definition with:
```php
var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;
```

- [ ] **Step 2: Update any setItemId/setItemIdPrice functions**

Apply same pattern as Task 6.

- [ ] **Step 3: Commit**

```bash
git add app/pages/dcollect_pending.php
git commit -m "refactor: use centralized access control in pending page"
```

---

### Task 8: Update remaining app/pages files

**Files:**
- Modify: `C:\wamp64\www\store\app\pages\dcollect_delivery.php:200`
- Modify: `C:\wamp64\www\store\app\pages\dcollect_delivery_status.php:298`
- Modify: `C:\wamp64\www\store\app\pages\dcollect_collect.php:447`

- [ ] **Step 1: Update dcollect_delivery.php**

Replace __restrictedUser with:
```php
var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;
```

- [ ] **Step 2: Update dcollect_delivery_status.php**

Apply same pattern.

- [ ] **Step 3: Update dcollect_collect.php**

Apply same pattern.

- [ ] **Step 4: Commit all three files**

```bash
git add app/pages/dcollect_delivery.php app/pages/dcollect_delivery_status.php app/pages/dcollect_collect.php
git commit -m "refactor: use centralized access control in delivery/collect pages"
```

---

### Task 9: Update orderadmin/ajax/dcollect.php

**Files:**
- Modify: `C:\wamp64\www\store\orderadmin\ajax\dcollect.php:7`

- [ ] **Step 1: Replace $restrictedUser definition**

Replace line 7:
```php
$restrictedUser = (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));
```

With:
```php
$canEditPriceQty = canEditPriceAndQuantity();
$canEditAnything = canEditAnything();
```

- [ ] **Step 2: Update all $restrictedUser references**

Search and replace all instances of `$restrictedUser` with appropriate variable.

- [ ] **Step 3: Commit**

```bash
git add orderadmin/ajax/dcollect.php
git commit -m "refactor: use centralized access control in orderadmin ajax"
```

---

### Task 10: Update orderadmin/pages/dcollect.php

**Files:**
- Modify: `C:\wamp64\www\store\orderadmin\pages\dcollect.php`

- [ ] **Step 1: Add JavaScript variable**

Add the centralized variables in the JavaScript section:
```php
var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;
```

- [ ] **Step 2: Update setItemId and setItemIdPrice functions**

Apply same pattern as Task 6.

- [ ] **Step 3: Commit**

```bash
git add orderadmin/pages/dcollect.php
git commit -m "refactor: add access control to orderadmin dcollect page"
```

---

### Task 11: Verify Changes

- [ ] **Step 1: Run PHP syntax check**

```bash
php -l core/functions.php
php -l ajax/dcollect.php
php -l ajax/update_invoice_item_quantity.php
php -l ajax/update_invoice_item_price.php
php -l orderadmin/f.inc.php
php -l orderadmin/functions.php
```

- [ ] **Step 2: Test each access level**

Login as each user and verify:
- superadmin (uid=1): Can edit date, quantity, price
- parvez (uid=47): Can edit date, quantity, price
- tushar (uid=60): Can edit date only
- Other users: Cannot edit anything

- [ ] **Step 3: Verify orderadmin security fixes**

Test with `?uid=1` parameter - should not spoof the uid.

- [ ] **Step 4: Final commit if needed**

```bash
git add -A
git commit -m "chore: access control implementation complete"
```
