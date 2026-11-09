<?php
require_once '../includes/auth.php';
require_once '../config/phone.php';
seller_required();

$s = store();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = trim((string)($_POST['name'] ?? ''));
    $slugBase = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($n)), '-');
    $w = normalize_nigerian_whatsapp($_POST['whatsapp'] ?? '');
    $description = trim((string)($_POST['description'] ?? ''));
    $deliveryNote = trim((string)($_POST['delivery_note'] ?? ''));
    $country = trim((string)($_POST['business_country'] ?? 'Nigeria'));
    $state = trim((string)($_POST['business_state'] ?? ''));
    $city = trim((string)($_POST['business_city'] ?? ''));
    $area = trim((string)($_POST['business_area'] ?? ''));
    $address = trim((string)($_POST['business_address'] ?? ''));

    if (!$n || !$slugBase) $error = 'Enter a valid store name.';
    elseif ($w === false) $error = 'Enter a valid Nigerian mobile WhatsApp number, e.g. 08012345678 or +2348012345678.';
    elseif ($country === '' || $state === '' || $city === '') $error = 'Country, state/province and city are required.';
    elseif (strlen($country)>100 || strlen($state)>100 || strlen($city)>100 || strlen($area)>120 || strlen($address)>255) $error = 'One or more location fields are too long.';
    else {
        $slug = $slugBase; $i = 2;
        while (true) {
            $q = $pdo->prepare('SELECT id FROM stores WHERE slug=?' . ($s ? ' AND id<>?' : ''));
            $q->execute($s ? [$slug,$s['id']] : [$slug]);
            if (!$q->fetch()) break;
            $slug = $slugBase . '-' . $i++;
        }
        try {
            if ($s) {
                $q = $pdo->prepare("UPDATE stores SET name=?,slug=?,description=?,whatsapp_number=?,delivery_note=?,business_country=?,business_state=?,business_city=?,business_area=?,business_address=?,verification_status='pending',verification_submitted_at=NOW(),verification_reviewed_by=NULL,verification_reviewed_at=NULL,verification_note=NULL WHERE id=? AND user_id=?");
                $q->execute([$n,$slug,$description,$w,$deliveryNote,$country,$state,$city,$area ?: null,$address ?: null,$s['id'],$_SESSION['user_id']]);
            } else {
                $q = $pdo->prepare('INSERT INTO stores(user_id,name,slug,description,whatsapp_number,delivery_note,business_country,business_state,business_city,business_area,business_address,verification_status,verification_submitted_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
                $q->execute([$_SESSION['user_id'],$n,$slug,$description,$w,$deliveryNote,$country,$state,$city,$area ?: null,$address ?: null,'pending']);
            }
            go('/seller/dashboard.php');
        } catch (PDOException $x) {
            error_log('Choosery store settings database error: ' . $x->getMessage());
            $error = 'Unable to save store settings. Please try again.';
        }
    }
}

$s = $s ?: ['name'=>'','description'=>'','whatsapp_number'=>'','delivery_note'=>'','business_country'=>'Nigeria','business_state'=>'','business_city'=>'','business_area'=>'','business_address'=>'','verification_status'=>'unsubmitted','verification_note'=>null];
$title = 'Store settings';
require '../includes/header.php';
?>
<h1>Store settings</h1>
<p class="text-muted">Manage your storefront, contact details and business location. City and state help customers discover nearby sellers.</p>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if ($s['verification_status']==='verified'): ?><div class="alert alert-success">Your business profile has been reviewed by Choosery. If you change these details, it will need another review.</div>
<?php elseif ($s['verification_status']==='pending'): ?><div class="alert alert-warning">Your business profile is awaiting admin review.</div>
<?php elseif ($s['verification_status']==='rejected' && $s['verification_note']): ?><div class="alert alert-danger">Previous submission not approved: <?=e($s['verification_note'])?>. Update your details and submit again.</div><?php endif; ?>
<form method="post" class="card card-body col-lg-8">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<h2 class="h5">Store information</h2>
<label class="form-label" for="store-name">Store name</label>
<input class="form-control mb-2" id="store-name" name="name" value="<?=e($_POST['name'] ?? $s['name'])?>" required maxlength="160">
<label class="form-label" for="store-description">Business description</label>
<textarea class="form-control mb-3" id="store-description" name="description"><?=e($_POST['description'] ?? $s['description'])?></textarea>
<label class="form-label" for="store-whatsapp">WhatsApp number</label>
<input class="form-control mb-3" id="store-whatsapp" type="tel" name="whatsapp" value="<?=e($_POST['whatsapp'] ?? $s['whatsapp_number'])?>" required inputmode="tel" autocomplete="tel">
<hr><h2 class="h5">Business location</h2>
<p class="small text-muted">Country, state and city are used for discovery. Street address is optional and is not displayed publicly.</p>
<label class="form-label" for="business-country">Country</label>
<input class="form-control mb-2" id="business-country" name="business_country" value="<?=e($_POST['business_country'] ?? $s['business_country'])?>" required maxlength="100">
<label class="form-label" for="business-state">State / Province</label>
<input class="form-control mb-2" id="business-state" name="business_state" value="<?=e($_POST['business_state'] ?? $s['business_state'])?>" required maxlength="100">
<label class="form-label" for="business-city">City / Town</label>
<input class="form-control mb-2" id="business-city" name="business_city" value="<?=e($_POST['business_city'] ?? $s['business_city'])?>" required maxlength="100">
<label class="form-label" for="business-area">Area / District (optional)</label>
<input class="form-control mb-2" id="business-area" name="business_area" value="<?=e($_POST['business_area'] ?? $s['business_area'])?>" maxlength="120">
<label class="form-label" for="business-address">Street address (private)</label>
<input class="form-control mb-3" id="business-address" name="business_address" value="<?=e($_POST['business_address'] ?? $s['business_address'])?>" maxlength="255" autocomplete="street-address">
<label class="form-label" for="delivery-note">Delivery note</label>
<textarea class="form-control mb-3" id="delivery-note" name="delivery_note"><?=e($_POST['delivery_note'] ?? $s['delivery_note'])?></textarea>
<button class="btn btn-success" type="submit">Save business profile</button>
</form>
<?php require '../includes/footer.php'; ?>
