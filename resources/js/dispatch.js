import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Dispatcher board map (14.md). Leaflet is bundled via Vite (no CDN); the
// tile server URL comes from config (self-hosted recommended).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dispatchMap', (points, tileUrl) => ({
        init() {
            const map = L.map(this.$el, { scrollWheelZoom: false });
            L.tileLayer(tileUrl, { maxZoom: 18, attribution: '&copy; OpenStreetMap-Mitwirkende' }).addTo(map);

            const markers = points.map((point) =>
                L.circleMarker([point.lat, point.lng], {
                    radius: point.type === 'technician' ? 7 : 9,
                    color: point.type === 'technician' ? '#1D4ED8' : '#3D5E50',
                    fillOpacity: 0.8,
                }).bindTooltip(point.label),
            );

            markers.forEach((marker) => marker.addTo(map));

            if (markers.length) {
                map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2), { maxZoom: 13 });
            } else {
                map.setView([51.1657, 10.4515], 6); // Germany
            }
        },
    }));
});
