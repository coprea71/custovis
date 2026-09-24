// Dispatcher board map (14.md). Registered from app.js so the component
// exists before Alpine starts; Leaflet itself is code-split by Vite and
// only fetched on the page that renders a map. Tile server URL comes from
// config (self-hosted recommended), no CDN.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dispatchMap', (points, tileUrl) => ({
        async init() {
            const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
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
