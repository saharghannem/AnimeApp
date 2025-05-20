document.addEventListener('DOMContentLoaded', function() {
    console.log('Debug script loaded');
    
    // Manual init of set-bg elements
    const setBgElements = document.querySelectorAll('.set-bg');
    console.log('Found ' + setBgElements.length + ' .set-bg elements');
    
    setBgElements.forEach(function(element) {
        const bg = element.getAttribute('data-setbg');
        console.log('Setting background for element: ' + bg);
        element.style.backgroundImage = 'url(' + bg + ')';
    });
    
    // Try to initialize owl carousel manually if it exists
    if (typeof $.fn.owlCarousel !== 'undefined') {
        console.log('Owl carousel found, initializing manually');
        $(".hero__slider").owlCarousel({
            loop: true,
            margin: 0,
            items: 1,
            dots: true,
            nav: true,
            navText: ["<span class='arrow_carrot-left'></span>", "<span class='arrow_carrot-right'></span>"],
            animateOut: 'fadeOut',
            animateIn: 'fadeIn',
            smartSpeed: 1200,
            autoHeight: false,
            autoplay: true,
            mouseDrag: false
        });
    } else {
        console.log('Owl carousel not found');
    }
});
