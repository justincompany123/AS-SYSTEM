
/* =========================================
   ALTAR SERVERS SYSTEM
   LOADING SCREEN CONTROLLER
   Display Duration: 2 Seconds
========================================= */

(function () {

    function initializeLoadingScreen() {

        const loadingScreen = document.getElementById("loading-screen");

        if (!loadingScreen) {
            return;
        }

        // Prevent multiple timers.
        if (loadingScreen.dataset.initialized === "true") {
            return;
        }

        loadingScreen.dataset.initialized = "true";

        // Display loading screen for 2 seconds.
        setTimeout(function () {

            loadingScreen.classList.add("fade-out");

            // Remove after fade-out animation.
            setTimeout(function () {

                if (loadingScreen && loadingScreen.parentNode) {
                    loadingScreen.remove();
                }

            }, 650);

        }, 2000);
    }

    // Handle both normal and already-loaded documents.
    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            initializeLoadingScreen,
            { once: true }
        );

    } else {

        initializeLoadingScreen();

    }

})();
