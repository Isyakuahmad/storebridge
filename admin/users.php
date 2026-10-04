<?php
require_once '../includes/auth.php';
admin_required();

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $id=(int)($_POST['id']??0);
    $action=$_POST['action']??'';

    if($id!==$_SESSION['user_id']&&in_array($action,['active','suspended'],true)){
        $q=$pdo->prepare("UPDATE users SET account_status=? WHERE id=? AND role='seller'");
        $q->execute([$action,$id]);
        audit_log('seller_'.$action,'user',$id,null);
    }
}

$q=$pdo->query("SELECT u.id,u.name,u.email,u.account_status,u.created_at,s.slug,s.name AS store_name
                FROM users u
                LEFT JOIN stores s ON s.user_id=u.id
                WHERE u.role='seller'
                ORDER BY u.created_at DESC");
$us=$q->fetchAll();

$title='Seller management';
require '../includes/header.php';
?>
<h1>Seller management</h1>
<p class="text-muted">Manage seller accounts and view each seller's catalogue.</p>

<?php foreach($us as $u):?>
<div class="card mb-3">
    <div class="card-body">
        <strong><?=e($u['name'])?></strong>
        <div><?=e($u['email'])?></div>
        <?php if($u['store_name']):?>
            <div class="mt-1">Store: <?=e($u['store_name'])?></div>
        <?php endif;?>
        <div class="small text-muted mb-2">Joined <?=e($u['created_at'])?></div>

        <span class="badge <?= $u['account_status']==='active'?'bg-success':'bg-danger'?>">
            <?=e($u['account_status'])?>
        </span>

        <div class="mt-2">
            <?php if($u['slug']):?>
                <a class="btn btn-sm btn-outline-success me-1"
                   href="/admin/seller-products.php?user_id=<?=e($u['id'])?>">
                    View products
                </a>
                <?php if($u['account_status']==='active'):?>
                    <a class="btn btn-sm btn-outline-secondary me-1"
                       href="/public/store.php?slug=<?=e($u['slug'])?>" target="_blank">
                        View store
                    </a>
                <?php endif;?>
            <?php endif;?>

            <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                <input type="hidden" name="id" value="<?=e($u['id'])?>">
                <?php if($u['account_status']==='active'):?>
                    <button class="btn btn-sm btn-outline-danger" name="action" value="suspended">
                        Suspend seller
                    </button>
                <?php else:?>
                    <button class="btn btn-sm btn-success" name="action" value="active">
                        Activate seller
                    </button>
                <?php endif;?>
            </form>
        </div>
    </div>
</div>
<?php endforeach;?>

<?php require '../includes/footer.php';?>