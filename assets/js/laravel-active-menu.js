(function () {
    if (typeof window.jQuery === 'undefined') {
        return;
    }

    const normalize = (path) => {
        if (!path) return '/';
        const cleaned = path.replace(/\/+$/, '');
        return cleaned === '' ? '/' : cleaned;
    };

    const currentPath = normalize(window.location.pathname);

    const $links = window.jQuery('#sidebar-menu a[href]').filter(function () {
        const href = this.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return false;
        }

        try {
            const linkPath = normalize(new URL(this.href, window.location.origin).pathname);
            return linkPath === currentPath;
        } catch (e) {
            return false;
        }
    });

    if ($links.length === 0) {
        return;
    }

    const $active = $links.first();
    $active.addClass('active');

    $active.parents('li.submenu, li.submenu-two').each(function () {
        const $li = window.jQuery(this);
        $li.children('a').addClass('subdrop active');
        $li.children('ul').show();
    });
})();
