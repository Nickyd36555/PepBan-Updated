<?php
$page_title = 'Security';
$db = Database::get();

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = post('_action');

    // Add blocked domain
    if ($act === 'add_domain') {
        $domain = strtolower(trim(post('domain')));
        $domain = ltrim($domain, '@');
        $reason = trim(post('reason'));

        if ($domain === '') {
            admin_flash('error', 'Domain is required.');
            redirect('/admin/security');
        }

        try {
            $db->insert('pepban_blocked_domains', [
                'domain'     => $domain,
                'reason'     => $reason,
                'date_added' => date('Y-m-d H:i:s'),
            ]);
            admin_flash('success', 'Domain "' . $domain . '" has been blocked.');
        } catch (Exception $e) {
            admin_flash('error', 'Could not add domain — it may already be blocked.');
        }
        redirect('/admin/security');
    }

    // Delete blocked domain
    if ($act === 'delete_domain') {
        $did = (int) get_param('did');
        if ($did) {
            $db->delete('pepban_blocked_domains', ['id' => $did]);
            admin_flash('success', 'Domain removed from blocklist.');
        }
        redirect('/admin/security');
    }

    // Add blocked IP
    if ($act === 'add_ip') {
        $ip_address = trim(post('ip_address'));
        $reason     = trim(post('reason'));

        if ($ip_address === '') {
            admin_flash('error', 'IP address is required.');
            redirect('/admin/security');
        }

        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            admin_flash('error', 'Invalid IP address format.');
            redirect('/admin/security');
        }

        try {
            $db->insert('pepban_blocked_ips', [
                'ip_address' => $ip_address,
                'reason'     => $reason,
                'date_added' => date('Y-m-d H:i:s'),
            ]);
            admin_flash('success', 'IP "' . $ip_address . '" has been blocked.');
        } catch (Exception $e) {
            admin_flash('error', 'Could not add IP — it may already be blocked.');
        }
        redirect('/admin/security');
    }

    // Delete blocked IP
    if ($act === 'delete_ip') {
        $iid = (int) get_param('iid');
        if ($iid) {
            $db->delete('pepban_blocked_ips', ['id' => $iid]);
            admin_flash('success', 'IP address removed from blocklist.');
        }
        redirect('/admin/security');
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$blocked_domains = $db->fetchAll('SELECT * FROM pepban_blocked_domains ORDER BY date_added DESC');
$blocked_ips     = $db->fetchAll('SELECT * FROM pepban_blocked_ips ORDER BY date_added DESC');

require __DIR__ . '/../templates/admin-layout.php';
?>

<?php foreach (get_flashes() as $f): ?>
<div class="pb-alert pb-alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="pb-detail-grid">

    <!-- Blocked Domains -->
    <div class="pb-card">
        <div class="pb-card-header"><h3>Blocked Domains</h3></div>
        <div class="pb-card-body pb-card-body-flush">
            <?php if (!$blocked_domains): ?>
                <p style="padding:16px;color:#6b7280">No domains blocked yet.</p>
            <?php else: ?>
            <table class="pb-table">
                <thead>
                    <tr><th>Domain</th><th>Reason</th><th>Date Added</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($blocked_domains as $d): ?>
                <tr>
                    <td><code><?= e($d->domain) ?></code></td>
                    <td><?= e($d->reason ?: '—') ?></td>
                    <td><?= e(date('M j, Y', strtotime($d->date_added))) ?></td>
                    <td>
                        <form method="post" action="?did=<?= (int) $d->id ?>" onsubmit="return confirm('Remove this domain from the blocklist?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="delete_domain">
                            <button type="submit" class="pb-btn pb-btn-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="pb-card-body" style="border-top:1px solid #1e1e32">
            <h4 style="margin:0 0 12px">Block a Domain</h4>
            <form method="post" class="pb-form">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="add_domain">
                <div class="pb-field">
                    <label>Email Domain <small>(e.g. example.com)</small></label>
                    <input type="text" name="domain" placeholder="example.com" required>
                </div>
                <div class="pb-field">
                    <label>Reason <small>(optional)</small></label>
                    <textarea name="reason" class="pb-textarea" rows="2" placeholder="Why is this domain blocked?"></textarea>
                </div>
                <button type="submit" class="pb-btn pb-btn-primary">Block Domain</button>
            </form>
        </div>
    </div>

    <!-- Blocked IPs -->
    <div class="pb-card">
        <div class="pb-card-header"><h3>Blocked IP Addresses</h3></div>
        <div class="pb-card-body pb-card-body-flush">
            <?php if (!$blocked_ips): ?>
                <p style="padding:16px;color:#6b7280">No IP addresses blocked yet.</p>
            <?php else: ?>
            <table class="pb-table">
                <thead>
                    <tr><th>IP Address</th><th>Reason</th><th>Date Added</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($blocked_ips as $ip): ?>
                <tr>
                    <td><code><?= e($ip->ip_address) ?></code></td>
                    <td><?= e($ip->reason ?: '—') ?></td>
                    <td><?= e(date('M j, Y', strtotime($ip->date_added))) ?></td>
                    <td>
                        <form method="post" action="?iid=<?= (int) $ip->id ?>" onsubmit="return confirm('Remove this IP from the blocklist?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="delete_ip">
                            <button type="submit" class="pb-btn pb-btn-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="pb-card-body" style="border-top:1px solid #1e1e32">
            <h4 style="margin:0 0 12px">Block an IP Address</h4>
            <form method="post" class="pb-form">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="add_ip">
                <div class="pb-field">
                    <label>IP Address <small>(IPv4 or IPv6)</small></label>
                    <input type="text" name="ip_address" placeholder="192.168.1.1" required>
                </div>
                <div class="pb-field">
                    <label>Reason <small>(optional)</small></label>
                    <textarea name="reason" class="pb-textarea" rows="2" placeholder="Why is this IP blocked?"></textarea>
                </div>
                <button type="submit" class="pb-btn pb-btn-primary">Block IP</button>
            </form>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
