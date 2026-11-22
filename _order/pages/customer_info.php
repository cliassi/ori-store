<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$cust = R::findOne('customer', ' id = ? ', [$uid]);
if (!$cust) {
  redir('?page=home');
  return;
}

$photoUploadError = '';
$photoUploadSuccess = !empty($_SESSION['customer_photo_updated']);
unset($_SESSION['customer_photo_updated']);
if (empty($_SESSION['customer_photo_upload_token'])) {
  $_SESSION['customer_photo_upload_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['upload_photo'])) {
  $submittedToken = isset($_POST['photo_upload_token']) && is_string($_POST['photo_upload_token'])
    ? $_POST['photo_upload_token']
    : '';
  if (!hash_equals($_SESSION['customer_photo_upload_token'], $submittedToken)) {
    $photoUploadError = 'Your session expired. Please try again.';
  } elseif (!isset($_FILES['profile_photo']) || !is_array($_FILES['profile_photo'])) {
    $photoUploadError = 'Choose a photo to upload.';
  } else {
    $upload = $_FILES['profile_photo'];
    $maxPhotoSize = 5 * 1024 * 1024;
    if (
      !isset($upload['error'], $upload['tmp_name'], $upload['size'])
      || !is_int($upload['error'])
      || !is_string($upload['tmp_name'])
      || !is_int($upload['size'])
      || $upload['error'] !== UPLOAD_ERR_OK
    ) {
      $photoUploadError = 'The photo could not be uploaded. Please try again.';
    } elseif ((int)$upload['size'] <= 0 || (int)$upload['size'] > $maxPhotoSize || !is_uploaded_file($upload['tmp_name'])) {
      $photoUploadError = 'Choose an image smaller than 5 MB.';
    } else {
      $allowedImageTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
      ];
      $imageInfo = @getimagesize($upload['tmp_name']);
      $fileInfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
      $mimeType = $fileInfo ? $fileInfo->file($upload['tmp_name']) : false;
      $validDimensions = $imageInfo
        && $imageInfo[0] > 0
        && $imageInfo[1] > 0
        && ($imageInfo[0] * $imageInfo[1]) <= 25000000;
      if (!$validDimensions || !isset($allowedImageTypes[$mimeType])) {
        $photoUploadError = 'Choose a valid JPG, PNG, GIF, or WebP image.';
      } else {
        $uploadDirectory = dirname(__DIR__, 2) . '/uploads/customer-profiles';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
          $photoUploadError = 'Photo storage is unavailable. Please try again later.';
        } else {
          $fileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$mimeType];
          $destination = $uploadDirectory . '/' . $fileName;
          if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            $photoUploadError = 'The photo could not be saved. Please try again.';
          } else {
            $cust->image = 'uploads/customer-profiles/' . $fileName;
            R::store($cust);
            $_SESSION['customer_photo_updated'] = true;
            redir('?page=customer_info');
            return;
          }
        }
      }
    }
  }
}

$photo = !empty($cust->image) ? getImageOrPlaceholder($cust->image, $cust->contact) : '';
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
      <a href="?page=account_menu" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold">Customer</h1>
      <div class="w-6"></div>
    </div>
    <div class="text-center">
      <form id="customer-photo-form" method="post" enctype="multipart/form-data" class="m-0">
        <input type="hidden" name="upload_photo" value="1">
        <input type="hidden" name="photo_upload_token" value="<?php echo htmlspecialchars($_SESSION['customer_photo_upload_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input id="customer-photo-input" class="sr-only" type="file" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Upload profile photo" onchange="if (this.files.length) this.form.submit();">
        <button type="button" onclick="pickCustomerPhoto()" aria-label="Change profile photo" class="relative mx-auto mb-3 flex h-20 w-20 cursor-pointer items-center justify-center overflow-hidden rounded-full border-0 bg-white p-0" title="Tap to change profile photo">
          <?php if ($photo): ?>
            <img src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="Customer profile photo" class="h-16 w-16 rounded-full object-cover">
          <?php else: ?>
            <span class="h-16 w-16 rounded-full bg-gradient-to-br from-green-400 to-blue-500"></span>
          <?php endif; ?>
          <span class="absolute bottom-0 right-0 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white">
            <span class="material-symbols-outlined text-base">photo_camera</span>
          </span>
        </button>
      </form>
      <?php if ($photoUploadError !== ''): ?>
        <p role="alert" class="mx-auto mb-3 max-w-xs rounded-lg bg-red-100 px-3 py-2 text-sm text-red-800"><?php echo htmlspecialchars($photoUploadError, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php elseif ($photoUploadSuccess): ?>
        <p role="status" class="mx-auto mb-3 max-w-xs rounded-lg bg-green-100 px-3 py-2 text-sm text-green-800">Profile photo updated.</p>
      <?php endif; ?>
      <h2 class="text-xl font-bold"><?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?></h2>
      <p class="text-white/80"><?php echo htmlspecialchars($cust->contact, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </div>
</div>

<script>
function pickCustomerPhoto() {
  if (typeof window.FlutterInvokeMethod === 'function') {
    window.FlutterInvokeMethod('pickImage');
    return;
  }
  document.getElementById('customer-photo-input').click();
}

window.handleImagePicker = function (imageData) {
  if (!imageData || !imageData.data) return;

  const input = document.getElementById('customer-photo-input');
  const transfer = new DataTransfer();
  const file = new File([imageData.data], imageData.name || 'profile-photo.jpg', {
    type: imageData.type || 'image/jpeg'
  });
  transfer.items.add(file);
  input.files = transfer.files;
  input.dispatchEvent(new Event('change', { bubbles: true }));
};
</script>

<div class="max-w-sm mx-auto px-4 -mt-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <dl class="divide-y divide-gray-100">
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Code</dt>
        <dd class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string)$cust->code, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Shop Name</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->company, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Contact</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->contact, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Mobile</dt>
        <dd class="text-sm font-medium text-gray-800">
          <?php if (!empty($cust->mobile)): ?>
            <a href="tel:<?php echo htmlspecialchars($cust->mobile, ENT_QUOTES, 'UTF-8'); ?>" class="text-blue-600"><?php echo htmlspecialchars($cust->mobile, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endif; ?>
        </dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Area</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->city, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
    </dl>
  </div>
</div>
