document.addEventListener('DOMContentLoaded', function () {
    var hamburger = document.querySelector('.hamburger-menu');
    var sidebar = document.querySelector('.sidebar');
    var closeBtn = document.querySelector('.close-sidebar');

    hamburger.addEventListener('click', function () {
        sidebar.classList.add('open');
    });

    closeBtn.addEventListener('click', function () {
        sidebar.classList.remove('open');
    });
});
