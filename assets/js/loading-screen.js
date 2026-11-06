
/* =========================================
   ALTAR SERVERS SYSTEM - LOADING SCREEN
   Display duration: 2 seconds
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const loadingScreen = document.getElementById("loading-screen");

    if (!loadingScreen) {
        return;
    }

    // Display the loading screen for 2 seconds
    setTimeout(function () {

        loadingScreen.classList.add("fade-out");

        // Remove the loading screen after animation
        setTimeout(function () {
            loadingScreen.remove();
        }, 600);

    }, 2000);

});
