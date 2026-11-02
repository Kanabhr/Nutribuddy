<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const isOpen = sidebar.classList.toggle('open');
        document.querySelectorAll('[aria-controls="sidebar"]').forEach(button => {
            button.setAttribute('aria-expanded', String(isOpen));
        });
        if (document.getElementById('overlay')) {
            document.getElementById('overlay').classList.toggle('show', isOpen);
        }
    }

    function closeSidebar() {
        if (document.getElementById('sidebar')) {
            document.getElementById('sidebar').classList.remove('open');
        }
        document.querySelectorAll('[aria-controls="sidebar"]').forEach(button => {
            button.setAttribute('aria-expanded', 'false');
        });
        if (document.getElementById('overlay')) {
            document.getElementById('overlay').classList.remove('show');
        }
    }

    function setActive(el) {
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        if (window.innerWidth <= 900) closeSidebar();
    }

    document.querySelectorAll('[data-sidebar-group-toggle]').forEach(button => {
        button.addEventListener('click', function () {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            const group = document.getElementById(this.getAttribute('aria-controls'));
            this.setAttribute('aria-expanded', String(!expanded));
            if (group) group.hidden = expanded;
        });
    });

    const mobileNavTrigger = document.querySelector('[data-user-panel-nav-trigger]');
    if (mobileNavTrigger) {
        if (!document.querySelector('.ud-panel-content .sidebar-toggle')) {
            mobileNavTrigger.classList.add('is-visible');
        }
        mobileNavTrigger.addEventListener('click', toggleSidebar);
    }
</script>
