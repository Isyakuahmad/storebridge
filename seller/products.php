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
        <div class="card h-100">
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

                <div class="d-flex gap-2 mt-3">
                    <a class="btn btn-outline-success btn-sm" href="/seller/edit-product.php?id=<?=e($p['id'])?>">Edit</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                        <input type="hidden" name="id" value="<?=e($p['id'])?>">
                        <button class="btn btn-outline-danger btn-sm" name="action" value="delete">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach;?>
</div>

<?php if(!$ps):?>
<p class="text-muted">You have not added any products yet.</p>
<?php endif;?>

<?php require '../includes/footer.php';?>