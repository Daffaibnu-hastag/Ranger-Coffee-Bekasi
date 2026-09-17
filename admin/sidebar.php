<!-- admin/sidebar.php -->
<div class="sidebar d-flex flex-column">
    <div class="sidebar-brand">
        <h4 class="fw-bold m-0" style="color: var(--accent-gold);">Ranger Coffee</h4>
        <small class="text-white-50" style="font-size: 0.75rem;">Unified Admin System</small>
    </div>

    <div class="sidebar-menu flex-fill" id="mainSidebarTabs" role="tablist">
        <button class="nav-link-custom active" id="tab-btn-overview" data-bs-toggle="tab" data-bs-target="#view-overview" type="button">
            <i class="fas fa-chart-line"></i> Dashboard
        </button>
        <button class="nav-link-custom" id="tab-btn-pesanan" data-bs-toggle="tab" data-bs-target="#view-pesanan" type="button">
            <i class="fas fa-history"></i> Kelola Pesanan
        </button>
        <button class="nav-link-custom" id="tab-btn-menu" data-bs-toggle="tab" data-bs-target="#view-menu" type="button">
            <i class="fas fa-coffee"></i> Kelola Menu
        </button>
        <button class="nav-link-custom" id="tab-btn-kategori" data-bs-toggle="tab" data-bs-target="#view-kategori" type="button">
            <i class="fas fa-tags"></i> Kelola Kategori
        </button>
        <button class="nav-link-custom" id="tab-btn-bahan" data-bs-toggle="tab" data-bs-target="#view-bahan" type="button">
            <i class="fas fa-boxes-stacked"></i> Bahan Baku
        </button>
        <button class="nav-link-custom" id="tab-btn-keuangan" data-bs-toggle="tab" data-bs-target="#view-keuangan" type="button">
            <i class="fas fa-money-bill-trend-up"></i> Keuangan
        </button>
    </div>

    <div class="p-3 border-top border-secondary border-opacity-25">
        <div class="d-flex align-items-center justify-content-between">
            <div class="overflow-hidden me-2">
                <div class="fw-bold small text-truncate"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?></div>
                <small class="text-white-50" style="font-size: 0.72rem;">Administrator</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-3" onclick="konfirmasiLogout();">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const isDashboard = window.location.pathname.includes('dashboard.php');
    if (!isDashboard) {
        document.querySelectorAll('#mainSidebarTabs button').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetHash = this.getAttribute('data-bs-target');
                if (targetHash) window.location.href = 'dashboard.php' + targetHash;
            });
        });
    }
});
</script>