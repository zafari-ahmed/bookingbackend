import Alpine from 'alpinejs';

/**
 * Alpine drives only the small interactions the design system calls for:
 * the sidebar collapse/drawer, the notification dropdown, the priority
 * segmented control and the attachment drop zone. Everything else is a plain
 * server-rendered Blade form.
 */
window.Alpine = Alpine;

/** Matches the selector behind the `rail:` variant in app.css. */
const COLLAPSED_CLASS = 'sidebar-collapsed';

Alpine.store('sidebar', {
    /** Mobile: slide-out drawer over a dark scrim. */
    drawerOpen: false,

    init() {
        // The initial value is resolved by the blocking script in the layout
        // head, early enough to avoid a flash. This only keeps it right when
        // the viewport crosses into or out of the tablet range after load.
        window
            .matchMedia('(min-width: 768px) and (max-width: 1023px)')
            .addEventListener('change', (event) => {
                if (event.matches) {
                    this.setCollapsed(true);
                }
            });
    },

    /**
     * Desktop: expanded sidebar vs 64px icon rail. Held as a class on <html>
     * rather than as reactive state, so the server-rendered markup is already
     * correct on the first frame and the width transition only runs on a real
     * toggle. Nothing binds to this — the sidebar reads it through CSS.
     */
    get collapsed() {
        return document.documentElement.classList.contains(COLLAPSED_CLASS);
    },

    setCollapsed(collapsed) {
        document.documentElement.classList.toggle(COLLAPSED_CLASS, collapsed);

        try {
            window.localStorage.setItem('sidebar-collapsed', String(collapsed));
        } catch (error) {
            // Storage blocked; the choice simply will not survive the page.
        }
    },

    toggle() {
        this.setCollapsed(!this.collapsed);
    },

    openDrawer() {
        this.drawerOpen = true;
    },

    closeDrawer() {
        this.drawerOpen = false;
    },
});

Alpine.start();
