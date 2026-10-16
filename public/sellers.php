<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';

$q=$pdo->query("SELECT s.id,s.name,s.slug,s.description,u.name AS seller_name,
                       COUNT(CASE WHEN p.available=1 AND p.moderation_status='approved' THEN p.id END) AS product_count
                FROM stores s
                JOIN users u ON u.id=s.user_id
                LEFT JOIN products p ON p.store_id=s.id
                WHERE u.role='seller' AND u.account_status='active'
                GROUP BY s.id,s.name,s.slug,s.description,u.name
                ORDER BY s.name");

$stores=$q->fetchAll();

$title='Sellers';
require '../includes/header.php';
?>
<div class="mb-4">
    <h1 class="mb-1">Sellers</h1>
    <p class="text-muted mb-0">Explore products from StoreBridge sellers.</p>
</div>

<div class="row g-3">
<?php foreach($stores as $s):?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><?=e($s['name'])?></h5>
                <p class="text-muted mb-2">Seller: <?=e($s['seller_name'])?></p>
                <?php if(!empty($s['description'])):?>
                    <p><?=e($s['description'])?></p>
                <?php endif;?>
                <div class="small text-muted mb-3">
                    <?=e($s['product_count'])?> approved product<?=((int)$s['product_count']===1?'':'s')?>
                </div>
                <a class="btn btn-success btn-sm"
                   href="/public/store.php?slug=<?=e($s['slug'])?>">
                    View store
                </a>
            </div>
        </div>
    </div>
<?php endforeach;?>
</div>

<?php if(!$stores):?>
<p class="text-muted">No sellers are currently available.</p>
<?php endif;?>

<?php require '../includes/footer.php';?>