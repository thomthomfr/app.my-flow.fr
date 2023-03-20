const TurboHelper = class {
    constructor() {
        document.addEventListener('turbo:render', () => {
            this.initMetronicTheme();
        });
    }

    initMetronicTheme() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTDialer.init);
        } else {
            KTDialer.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTDrawer.init);
        } else {
            KTDrawer.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTImageInput.init);
        } else {
            KTImageInput.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTMenu.init);
        } else {
            KTMenu.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTPasswordMeter.init);
        } else {
            KTPasswordMeter.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTScroll.init);
        } else {
            KTScroll.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTScrolltop.init);
        } else {
            KTScrolltop.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTSticky.init);
        } else {
            KTSticky.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTSwapper.init);
        } else {
            KTSwapper.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', KTToggle.init);
        } else {
            KTToggle.init();
        }

        window.addEventListener("load", function() {
            KTApp.initPageLoader();
        });

        KTUtil.onDOMContentLoaded(function () {
            KTLayoutAside.init();
            KTLayoutExplore.init();
            KTLayoutSearch.init();
            KTLayoutToolbar.init();
        });
    }
}

export default new TurboHelper();
