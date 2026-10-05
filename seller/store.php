<?php
require_once '../includes/auth.php';
login_required();

$s=store();

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();

    $n=trim($_POST['name']??'');
    $slugBase=preg_replace('/[^a-z0-9]+/','-',strtolower($n));
    $slugBase=trim($slugBase,'-');

    $w=preg_replace('/\D+/','',$_POST['whatsapp']??'');
    if(str_starts_with($w,'0')){
        $w='234'.substr($w,1);
    }elseif(str_starts_with($w,'234')){
        $w='234'.substr($w,3);
    }
    $w='234'.$w;
    $w=preg_replace('/^234234/','234',$w);

    if($n&&$w&&$slugBase){
        $slug=$slugBase;
        $i=2;

        while(true){
            $q=$pdo->prepare('SELECT id FROM stores WHERE slug=?'.($s?' AND id<>?':''));
            $params=$s?[$slug,$s['id']]:[$slug];
            $q->execute($params);

            if(!$q->fetch())break;
            $slug=$slugBase.'-'.$i++;
        }

        try{
            if($s){
                $q=$pdo->prepare('UPDATE stores SET name=?,slug=?,description=?,whatsapp_number=?,delivery_note=? WHERE id=? AND user_id=?');
                $q->execute([$n,$slug,trim($_POST['description']??''),$w,trim($_POST['delivery_note']??''),$s['id'],$_SESSION['user_id']]);
            }else{
                $q=$pdo->prepare('INSERT INTO stores(user_id,name,slug,description,whatsapp_number,delivery_note)VALUES(?,?,?,?,?,?)');
                $q->execute([$_SESSION['user_id'],$n,$slug,trim($_POST['description']??''),$w,trim($_POST['delivery_note']??'')]);
            }

            go('/seller/dashboard.php');
        }catch(PDOException $x){
            $error='Unable to save store settings. Please try again.';
        }
    }

    if(empty($error))$error='Store name and WhatsApp number are required.';
}

$s=$s?:['name'=>'','description'=>'','whatsapp_number'=>'','delivery_note'=>''];
$title='Store settings';
require '../includes/header.php';
?>
<h1>Store settings</h1>
<p class="text-muted">Update your store information and catalogue contact details.</p>

<?php if(!empty($error)):?>
<div class="alert alert-danger"><?=e($error)?></div>
<?php endif;?>

<form method="post" class="card card-body col-lg-8">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input class="form-control mb-2" name="name" placeholder="Store name" value="<?=e($s['name'])?>" required>
    <textarea class="form-control mb-2" name="description" placeholder="Description"><?=e($s['description'])?></textarea>
    <input class="form-control mb-2" name="whatsapp" placeholder="08012345678 or 2348012345678" value="<?=e($s['whatsapp_number'])?>" required>
    <div class="form-text mb-2">You can enter a Nigerian number starting with 0 or +234. It will be converted automatically for WhatsApp.</div>
    <textarea class="form-control mb-2" name="delivery_note" placeholder="Delivery note"><?=e($s['delivery_note'])?></textarea>
    <button class="btn btn-success">Save changes</button>
</form>

<?php require '../includes/footer.php';?>