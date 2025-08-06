// js/track_visit.js - Enhanced Version
document.addEventListener('DOMContentLoaded', function() {
    // Only track if not in local development
    if (window.location.hostname === 'localhost' || 
        window.location.hostname === '127.0.0.1') {
        console.log('Visitor tracking skipped on localhost');
        return;
    }

    const trackingData = {
        page: window.location.pathname,
        referrer: document.referrer,
        screen_width: window.screen.width,
        screen_height: window.screen.height,
        language: navigator.language,
        timestamp: new Date().toISOString()
    };

    // Add debug info
    console.log('Sending tracking data:', trackingData);

    fetch('track_visit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(trackingData),
        keepalive: true // Ensures request completes even if page closes
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Tracking successful:', data);
    })
    .catch(error => {
        console.error('Tracking error:', error);
        // Fallback to image pixel if fetch fails
        const fallbackImg = document.createElement('img');
        fallbackImg.src = 'track_visit.php?page=' + encodeURIComponent(trackingData.page) + 
                         '&referrer=' + encodeURIComponent(trackingData.referrer);
        fallbackImg.style.display = 'none';
        document.body.appendChild(fallbackImg);
    });
});