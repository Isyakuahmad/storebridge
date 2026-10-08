<?php
require_once '../includes/auth.php';
login_required();

$referralCode = ensure_referral_code($_SESSION['user_id']);
$referralUrl = referral_url($referralCode);

$q = $pdo->prepare('SELECT COUNT(*) FROM users WHERE referred_by_user_id=?');
$q->execute([$_SESSION['user_id']]);
$totalReferrals = (int)$q->fetchColumn();

$q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by_user_id=? AND account_status='active'");
$q->execute([$_SESSION['user_id']]);
$activeReferrals = (int)$q->fetchColumn();

$q = $pdo->prepare("SELECT name,email,account_status,created_at FROM users WHERE referred_by_user_id=? ORDER BY created_at DESC");
$q->execute([$_SESSION['user_id']]);
$referrals = $q->fetchAll();

$title='Referrals';
require '../includes/header.php';
?>
<h1>Refer a Seller</h1>
<p class="text-muted">Invite another business owner to join Choosery. Referral rewards apply when the referred seller becomes a qualifying paid customer.</p>

<div class="card card-body mb-4">
    <h2 class="h5">Your referral link</h2>
    <div class="input-group">
        <input id="referralLink" class="form-control" value="<?=e($referralUrl)?>" readonly>
        <button type="button" class="btn btn-success" id="copyReferral">Copy</button>
    </div>
    <div class="mt-3">
        <a class="btn btn-success me-2" target="_blank" rel="noopener" href="https://wa.me/?text=<?=rawurlencode('Join me on Choosery: '.$referralUrl)?>">Share on WhatsApp</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card card-body"><div class="text-muted small">Total referrals</div><div class="display-6"><?=e($totalReferrals)?></div></div></div>
    <div class="col-md-6"><div class="card card-body"><div class="text-muted small">Active referrals</div><div class="display-6"><?=e($activeReferrals)?></div></div></div>
</div>

<div class="card card-body">
    <h2 class="h5">People you referred</h2>
    <?php if(!$referrals): ?>
        <p class="text-muted mb-0">No referrals yet. Share your link to invite your first seller.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach($referrals as $r): ?>
                    <tr>
                        <td><?=e($r['name'])?></td>
                        <td><?=e($r['email'])?></td>
                        <td><span class="badge text-bg-<?=($r['account_status']==='active'?'success':'secondary')?>"><?=e(ucfirst($r['account_status']))?></span></td>
                        <td><?=e(date('M j, Y',strtotime($r['created_at'])))?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('copyReferral')?.addEventListener('click', async function () {
    const input=document.getElementById('referralLink');
    try {
        await navigator.clipboard.writeText(input.value);
        this.textContent='Copied!';
        setTimeout(()=>this.textContent='Copy',1600);
    } catch (e) {
        input.select();
        document.execCommand('copy');
        this.textContent='Copied!';
        setTimeout(()=>this.textContent='Copy',1600);
    }
});
</script>
<?php require '../includes/footer.php'; ?>
