# Access Control Design Spec

## Overview

Implement centralized access control for invoice item editing (date, quantity, price) across the store application.

## Requirements

| User | uid | Edit Date | Edit Quantity | Edit Price |
|------|-----|-----------|---------------|------------|
| superadmin | 1 | Yes | Yes | Yes |
| parvez | 47 | Yes | Yes | Yes |
| tushar | 60 | Yes | No | No |
| anowar | - | No | No | No |
| user 53 | 53 | No | No | No |
| All others | - | No | No | No |

## Design: Role-Based Functions

### New Functions in `core/functions.php`

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

### Server-Side Usage (PHP)

Replace all instances of:
```php
$restrictedUser = (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));
```

With:
```php
$canEditPriceQty = canEditPriceAndQuantity();
$canEditDate = canEditAnything();
```

### Client-Side Usage (JavaScript)

Replace JavaScript variables:
```php
var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
var __canEditDate = <?php echo canEditAnything() ? 'true' : 'false'; ?>;
```

## Files to Modify

### Core Files
1. `core/functions.php` - Add new centralized functions
2. `orderadmin/f.inc.php` - Fix uid() spoofing (remove $_GET['uid'])
3. `orderadmin/functions.php` - Fix isUserIn() bypass (remove `return true;`)

### Ajax Files (app/)
4. `ajax/dcollect.php` - Update $restrictedUser usage
5. `ajax/update_invoice_item_quantity.php` - Update access check
6. `ajax/update_invoice_item_price.php` - Update access check

### Page Files (app/pages/)
7. `app/pages/dcollect.php` - Update PHP and JavaScript
8. `app/pages/dcollect_pending.php` - Update PHP and JavaScript
9. `app/pages/dcollect_delivery.php` - Update PHP and JavaScript
10. `app/pages/dcollect_delivery_status.php` - Update PHP and JavaScript
11. `app/pages/dcollect_collect.php` - Update PHP and JavaScript

### OrderAdmin Files
12. `orderadmin/ajax/dcollect.php` - Update access check

## Security Fixes

### Fix 1: uid() Spoofing in orderadmin/f.inc.php

**Before:**
```php
function uid(){
    if(isset($_GET['uid'])) return $_GET['uid'];
    global $get;
    if(isset($get->uid)) return $get->uid;
    return 1;
}
```

**After:**
```php
function uid(){
    return isset($_SESSION[APP.'_id']) ? $_SESSION[APP.'_id'] : 0;
}
```

### Fix 2: isUserIn() Bypass in orderadmin/functions.php

**Before:**
```php
function isUserIn($users = []){return true;
    if(uid() == 1) return true;
    return in_array(strtolower(username()), $users);
}
```

**After:**
```php
function isUserIn($users = []){
    if(uid() == 1) return true;
    return in_array(strtolower(username()), $users);
}
```

## Testing

1. Login as superadmin (uid=1) - verify can edit date, quantity, price
2. Login as parvez (uid=47) - verify can edit date, quantity, price
3. Login as tushar (uid=60) - verify can edit date only, not quantity/price
4. Login as other user - verify cannot edit anything
5. Test orderadmin with ?uid=1 - verify uid spoofing is fixed
