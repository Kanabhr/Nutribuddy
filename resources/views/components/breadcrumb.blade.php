<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24 nb-admin-breadcrumb">
    <div>
        <span class="nb-admin-breadcrumb-kicker">Admin Workspace</span>
        <h6 class="fw-semibold mb-0"><?php echo $title;?></h6>
    </div>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="{{ route('admin.index') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium"><?php echo $subTitle;?></li>
    </ul>
</div>
