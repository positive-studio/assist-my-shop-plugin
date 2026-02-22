(function(){
    const ajaxUrl = (typeof AmsAdmin !== 'undefined' && AmsAdmin.ajax_url) ? AmsAdmin.ajax_url : '/wp-admin/admin-ajax.php';
    const nonce = (typeof AmsAdmin !== 'undefined' && AmsAdmin.nonce) ? AmsAdmin.nonce : '';

    function setStatus(msg) {
        const el = document.getElementById('sync-status');
        if (el) el.innerHTML = msg;
    }

    function updateProgress(progress) {
        const container = document.getElementById('ams-sync-progress-container');
        const bar = document.getElementById('ams-sync-progress');
        const text = document.getElementById('ams-sync-progress-text');
        if (!container || !bar || !text) return;
        if (!progress) {
            container.style.display = 'none';
            bar.style.width = '0%';
            text.innerText = '';
            return;
        }

        container.style.display = 'block';
        const overall_total = progress.overall_total || 0;
        const overall_processed = progress.overall_processed || 0;
        const percent = overall_total > 0 ? Math.min(100, Math.round((overall_processed / overall_total) * 100)) : 0;
        bar.style.width = percent + '%';
        let txt = `Overall: ${overall_processed} of ${overall_total} items (${percent}%)`;
        if (progress.current_post_type) {
            txt += ` — Currently syncing: ${progress.current_post_type} (${progress.current_processed} of ${progress.current_total})`;
        }
        text.innerText = txt;
    }

    let pollHandle = null;
    function pollProgressUntilDone(onDone) {
        if (pollHandle) clearInterval(pollHandle);
        pollHandle = setInterval(() => {
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'ams_get_sync_progress' })
            })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) {
                        setStatus('Failed to fetch progress');
                        clearInterval(pollHandle);
                        return;
                    }
                    const progress = d.progress;
                    if (progress) {
                        updateProgress(progress);
                        setStatus('Sync in progress...');
                    } else {
                        updateProgress(null);
                        setStatus('Sync complete — last sync: ' + (d.last_sync || 'Unknown'));
                        clearInterval(pollHandle);
                        if (typeof onDone === 'function') onDone();
                    }
                })
                .catch(() => {
                    setStatus('Error polling sync progress');
                    clearInterval(pollHandle);
                });
        }, 2000);
    }

    function updateConnectionUI(connected, message) {
        const indicator = document.getElementById('ams-connection-indicator');
        const text = document.getElementById('ams-connection-status-text');
        if (!indicator || !text) return;

        indicator.style.background = connected ? '#16a34a' : '#dc2626';
        text.textContent = connected
            ? 'Connected'
            : `Not connected${message ? ': ' + message : ''}`;
    }

    function checkConnectionStatus() {
        const indicator = document.getElementById('ams-connection-indicator');
        const text = document.getElementById('ams-connection-status-text');
        if (!indicator || !text) return;

        indicator.style.background = '#9ca3af';
        text.textContent = 'Checking connection...';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'ams_check_connection',
                nonce: nonce
            })
        })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success || !data.data) {
                    updateConnectionUI(false, 'Unexpected response');
                    return;
                }
                updateConnectionUI(!!data.data.connected, data.data.message || '');
            })
            .catch(() => updateConnectionUI(false, 'Request failed'));
    }

    document.addEventListener('DOMContentLoaded', function(){
        checkConnectionStatus();

        const btn = document.getElementById('ams-sync-now');
        if (!btn) return;
        btn.addEventListener('click', function(){
            setStatus('Scheduling background sync...');
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'ams_sync_now', nonce: nonce })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setStatus('Background sync scheduled — polling progress...');
                    pollProgressUntilDone(function(){
                        setTimeout(() => location.reload(), 1200);
                    });
                } else {
                    setStatus('Sync failed: ' + (data.data && data.data.message ? data.data.message : (data.message || 'Unknown')));
                }
            })
            .catch(() => setStatus('Failed to schedule sync'));
        });
    });
})();
