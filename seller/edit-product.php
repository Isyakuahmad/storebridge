<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';
login_required();

$s=store();
if(!$s)go('/seller/store.php');

$id=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare('SELECT * FROM products WHERE id=? AND store_id=?');
$q->execute([$id,$s['id']]);
$p=$q->fetch();

if(!$p)exit('Product not found');

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();

    $name=trim($_POST['name']??'');
    $price=filter_var($_POST['price']??'',FILTER_VALIDATE_FLOAT);
    $cat=trim($_POST['category']??'');
    $description=trim($_POST['description']??'');
    $variations=trim($_POST['variations']??'');
    $available=isset($_POST['available'])?1:0;
    $img=$p['image'];
    $error=null;

    if(!$name||$price===false||$price<0||!$cat){
        $error='Name, valid price and category are required.';
    }

    if(!$error&&!empty($_FILES['image']['name'])){
        $f=$_FILES['image'];
        $m=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ok=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

        if($f['error']||$f['size']>2097152||!isset($ok[$m])){
            $error='Image must be JPG, PNG or WebP under 2MB.';
        }else{
            $newImg=bin2hex(random_bytes(12)).'.'.$ok[$m];
            if(!move_uploaded_file($f['tmp_name'],rtrim($uploadDir,'/\\').DIRECTORY_SEPARATOR.$newImg)){
                $error='Image upload failed.';
            }else{
                $img=$newImg;
            }
        }
    }

    if(!$error){
        try{
            $q=$pdo->prepare('UPDATE products SET name=?,description=?,price=?,category=?,image=?,variations=?,available=?,moderation_status=?,moderation_note=NULL,moderated_by=NULL,moderated_at=NULL WHERE id=? AND store_id=?');
            $q->execute([$name,$description,$price,$cat,$img,$variations,$available,'pending',$id,$s['id']]);

            if($img!==$p['image']&&!empty($p['image'])){
                $oldPath=rtrim($uploadDir,'/\\').DIRECTORY_SEPARATOR.$p['image'];
                if(is_file($oldPath))@unlink($oldPath);
            }

            go('/seller/products.php');
        }catch(PDOException $x){
            if($img!==$p['image']&&!empty($img)){
                $newPath=rtrim($uploadDir,'/\\').DIRECTORY_SEPARATOR.$img;
                if($img!==$p['image']&&is_file($newPath))@unlink($newPath);
            }
            $error='Unable to save this product. Please try again.';
        }
    }
}

$title='Edit product';
require '../includes/header.php';
?>
<h1>Edit product</h1>
<p class="text-muted">Changes to a product are reviewed again before the product returns to the public catalogue.</p>

<?php if(!empty($error)):?>
<div class="alert alert-danger"><?=e($error)?></div>
<?php endif;?>

<form method="post" enctype="multipart/form-data" class="card card-body col-lg-8">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="id" value="<?=e($p['id'])?>">

    <?php if($p['image']):?>
        <img class="rounded mb-3" style="width:160px;height:160px;object-fit:cover" src="<?=e($uploadUrl.'/'.$p['image'])?>" alt="<?=e($p['name'])?>">
    <?php endif;?>

    <input class="form-control mb-2" name="name" placeholder="Name" value="<?=e($p['name'])?>" required>
    <textarea class="form-control mb-2" name="description" placeholder="Description"><?=e($p['description'])?></textarea>
    <input class="form-control mb-2" type="number" step="0.01" min="0" name="price" placeholder="Price in naira" value="<?=e($p['price'])?>" required>
    <input class="form-control mb-2" name="category" placeholder="Category" value="<?=e($p['category'])?>" required>
    <input class="form-control mb-2" name="variations" placeholder="Variations e.g. Small, Medium, Large" value="<?=e($p['variations'])?>">
    <input class="form-control mb-1" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <div class="form-text mb-2">Leave empty to keep the current image. JPG, PNG or WebP · maximum 2 MB.</div>

    <label><input type="checkbox" name="available" <?= $p['available']?'checked':''?>> Available</label>

    <div class="mt-3">
        <button class="btn btn-success">Save changes</button>
        <a class="btn btn-outline-secondary ms-2" href="/seller/products.php">Cancel</a>
    </div>
</form>

<?php require '../includes/footer.php';?>