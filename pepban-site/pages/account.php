<?php
Auth::requireClient();
$client = Auth::client();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');

    if ($action === 'change_password') {
        $current  = post('current_password');
        $new      = post('new_password');
        $confirm  = post('confirm_password');

        if (!password_verify($current, $client->password_hash)) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash('error', 'New passwords do not match.');
        } else {
            Database::get()->update('pepban_clients', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], ['id' => $client->id]);
            flash('success', 'Password updated successfully.');
        }
        redirect('/account');
    }

    if ($action === 'change_email') {
        $password  = post('password');
        $new_email = trim(post('new_email'));

        if (!password_verify($password, $client->password_hash)) {
            flash('error', 'Password is incorrect.');
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
        } elseif (Database::get()->fetch('SELECT id FROM pepban_clients WHERE owner_email = ? AND id != ?', [$new_email, $client->id])) {
            flash('error', 'That email address is already in use.');
        } else {
            Database::get()->update('pepban_clients', ['owner_email' => $new_email], ['id' => $client->id]);
            flash('success', 'Email address updated successfully.');
        }
        redirect('/account');
    }
}

$page_title = 'Account Settings — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>

<div class="pepban-public">
<div class="pepban-portal-wrap">

<div class="pepban-portal-header">
    <div class="pepban-portal-header-left">
        <div class="pepban-portal-avatar"><?= e(strtoupper(substr($client->owner_name, 0, 1))) ?></div>
        <div class="pepban-portal-greeting">
            <strong><?= e($client->owner_name) ?></strong>
            <span><?= e($client->owner_email) ?></span>
        </div>
    </div>
    <a href="<?= url('/portal') ?>" class="pepban-btn pepban-btn-secondary">&larr; Back to Portal</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">

<div class="pepban-card">
    <div class="pepban-card-header">
        <div class="pepban-card-icon">&#128274;</div>
        <h3>Change Password</h3>
    </div>
    <div class="pepban-card-inner">
        <form method="post" class="pb-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="pb-form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required class="pb-input">
            </div>
            <div class="pb-form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" class="pb-input">
            </div>
            <div class="pb-form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="pb-input">
            </div>
            <button type="submit" class="pepban-btn pepban-btn-primary" style="width:100%">Update Password</button>
        </form>
    </div>
</div>

<div class="pepban-card">
    <div class="pepban-card-header">
        <div class="pepban-card-icon">&#9993;</div>
        <h3>Change Email</h3>
    </div>
    <div class="pepban-card-inner">
        <div class="pepban-info-row">
            <span class="pepban-info-label">Current email</span>
            <span class="pepban-info-value"><?= e($client->owner_email) ?></span>
        </div>
        <form method="post" class="pb-form" style="margin-top:16px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_email">
            <div class="pb-form-group">
                <label for="new_email">New Email Address</label>
                <input type="email" id="new_email" name="new_email" required class="pb-input">
            </div>
            <div class="pb-form-group">
                <label for="email_password">Current Password</label>
                <input type="password" id="email_password" name="password" required class="pb-input">
            </div>
            <button type="submit" class="pepban-btn pepban-btn-primary" style="width:100%">Update Email</button>
        </form>
    </div>
</div>

</div>

<div class="pepban-portal-footer">
    <span>PepBan &copy; <?= date('Y') ?></span>
    <div class="pepban-portal-footer-links">
        <a href="<?= url('/portal') ?>">My Portal</a>
        <a href="<?= url('/logout') ?>">Log out</a>
    </div>
</div>

</div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
