<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';
login_required();
$s=store();
if(!$s)go('/seller/store.php');

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $id=(int)($_POST['id']??0);
    $action=$_POST['action']??'';

    if($id>0&&$action==='delete'){
        $q=$pdo->prepare('SELECT image FROM products WHERE id=? AND store_id=?');
        $q->execute([$id,$s['id']]);
        $p=$q->fetch();

        if($p){
            $q=$pdo->prepare('DELETE FROM products WHERE id=? AND store_id=?');
            $q->execute([$id,$s['id']]);

            if($q->rowCount()>0&&!empty($p['image'])){
                $path=rtrim($uploadDir,'/\\').DIRECTORY_SEPARATOR.$p['image'];
                if(is_file($path))@unlink($path);
            }
        }
        go('/seller/products.php');
    }
}

$q=$pdo->prepare('SELECT * FROM products WHERE store_id=? ORDER BY created_at DESC');
$q->execute([$s['id']]);
$ps=$q->fetchAll();

$title='Products';
require '../includes/header.php';
?>
<style>
.product-card{position:relative;}
.product-menu{position:absolute;top:10px;right:10px;z-index:2;}
.product-menu summary{list-style:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.95);box-shadow:0 .125rem .25rem rgba(0,0,0,.15);cursor:pointer;}
.product-menu summary::-webkit-details-marker{display:none;}
.product-menu[open] summary{background:#f8f9fa;}
.product-menu-panel{position:absolute;right:0;top:42px;min-width:140px;padding:.35rem;background:#fff;border:1px solid rgba(0,0,0,.12);border-radius:.5rem;box-shadow:0 .5rem 1rem rgba(0,0,0,.15);}
.product-menu-panel a,.product-menu-panel button{display:block;width:100%;padding:.5rem .7rem;border:0;background:transparent;text-align:left;text-decoration:none;border-radius:.35rem;color:#212529;font-size:.875rem;}
.product-menu-panel a:hover,.product-menu-panel button:hover{background:#f8f9fa;}
.product-menu-panel .text-danger{color:#dc3545!important;}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">Products</h1>
        <p class="text-muted mb-0">Manage your catalogue products, prices, images and availability.</p>
    </div>
    <a class="btn btn-success" href="/seller/add-product.php">Add product</a>
</div>

<div class="row g-3">
<?php foreach($ps as $p):?>
    <div class="col-md-4">
        <div class="card h-100 product-card">
            <details class="product-menu">
                <summary aria-label="More options for <?=e($p['name'])?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="5" cy="12" r="2"></circle>
                        <circle cx="12" cy="12" r="2"></circle>
                        <circle cx="19" cy="12" r="2"></circle>
                    </svg>
                </summary>
                <div class="product-menu-panel">
                    <a href="/seller/edit-product.php?id=<?=e($p['id'])?>">Edit</a>
                    <form method="post" class="m-0" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                        <input type="hidden" name="id" value="<?=e($p['id'])?>">
                        <button class="text-danger" name="action" value="delete" type="submit">Delete</button>
                    </form>
                </div>
            </details>

            <?php if($p['image']):?>
                <img class="card-img-top" style="height:180px;object-fit:cover" src="<?=e($uploadUrl . '/' . $p['image'])?>" alt="<?=e($p['name'])?>">
            <?php endif;?>

            <div class="card-body">
                <h5><?=e($p['name'])?></h5>
                <p><?=e($p['category'])?> · <?=money($p['price'])?></p>
                <span class="badge bg-secondary"><?=e($p['moderation_status']??'pending')?></span>
                <span class="badge bg-success"><?= $p['available']?'Available':'Unavailable'?></span>

                <?php if(!empty($p['moderation_note'])):?>
                    <p class="small text-muted mt-2"><?=e($p['moderation_note'])?></p>
                <?php endif;?>
            </div>
        </div>
    </div>
<?php endforeach;?>
</div>

<?php if(!$ps):?>
<p class="text-muted">You have not added any products yet.</p>
<?php endif;?>

<?php require '../includes/footer.php';?>