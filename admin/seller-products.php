<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';
admin_required();

$userId=(int)($_GET['user_id']??0);

$q=$pdo->prepare("SELECT u.id AS seller_id,u.name AS seller_name,u.email,u.account_status,
                         s.name AS store_name,s.slug,
                         p.*
                  FROM users u
                  LEFT JOIN stores s ON s.user_id=u.id
                  LEFT JOIN products p ON p.store_id=s.id
                  WHERE u.role='seller' AND u.id=?
                  ORDER BY p.created_at DESC");
$q->execute([$userId]);
$rows=$q->fetchAll();

if(!$rows || (int)$rows[0]['seller_id']!==$userId) exit('Seller not found');

$seller=$rows[0];
$title='Seller products';
require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1"><?=e($seller['store_name']?:$seller['seller_name'])?></h1>
        <p class="text-muted mb-0">
            Seller: <?=e($seller['seller_name'])?> · <?=e($seller['email'])?>
        </p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/users.php">Back to sellers</a>
</div>

<?php if(!$rows[0]['id']):?>
    <p class="text-muted">This seller has not added any products yet.</p>
<?php else:?>
<div class="row g-3">
<?php foreach($rows as $p):?>
    <div class="col-md-4">
        <div class="card h-100">
            <?php if($p['image']):?>
                <img class="card-img-top"
                     style="height:180px;object-fit:cover"
                     src="<?=e($uploadUrl.'/'.$p['image'])?>"
                     alt="<?=e($p['name'])?>">
            <?php endif;?>
            <div class="card-body">
                <h5><?=e($p['name'])?></h5>
                <p><?=e($p['category'])?> · <?=money($p['price'])?></p>
                <span class="badge bg-secondary"><?=e($p['moderation_status']??'pending')?></span>
                <span class="badge bg-<?=!empty($p['available'])?'success':'dark'?>">
                    <?=!empty($p['available'])?'Available':'Unavailable'?>
                </span>
                <?php if(!empty($p['moderation_note'])):?>
                    <p class="small text-muted mt-2"><?=e($p['moderation_note'])?></p>
                <?php endif;?>
            </div>
        </div>
    </div>
<?php endforeach;?>
</div>
<?php endif;?>

<?php require '../includes/footer.php';?>