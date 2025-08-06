// track_visit.js
document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/track_visit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            page: window.location.pathname,
            referrer: document.referrer
        })
    }).catch(err => console.log('Visitor tracking error:', err));
});