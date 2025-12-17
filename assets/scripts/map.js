
function initLeafletMap(id) {
    const el = document.getElementById(id);
    if (!el) return;

    const chartData = JSON.parse(el.dataset.chart || "{}");
    const colorData = JSON.parse(el.dataset.colors || "{}");
    const options = JSON.parse(el.dataset.options || "{}");

    const map = L.map(id, {
        center: options.center || [46.8, 2.5],
        zoomSnap: 0.1,
        zoomControl: options.zoomControl ?? false,
        dragging: options.draggable ?? false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        tap: false,
        attributionControl: false,
    });

    const defaultBounds = options.bounds || [[41.2, -5], [51.3, 10.5]];

    function getPaddingPoint() {
        const width = el.clientWidth || 0;
        const height = el.clientHeight || 0;
        const horizontal = Math.max(10, Math.round(width * 0.04));
        const vertical = Math.max(10, Math.round(height * 0.04));
        return L.point(horizontal, vertical);
    }

    function fitMapToContainer() {
        if (!defaultBounds) return;
        map.fitBounds(defaultBounds, { padding: getPaddingPoint(), animate: false });
    }

    fitMapToContainer();

    const values = Object.values(chartData).filter(v => !isNaN(v));
    const min = Math.min(...values);
    const max = Math.max(...values);

    const startHsl = colorData.start_hsl;
    const endHsl = colorData.end_hsl;

    const start = `hsl(${startHsl[0]}, ${startHsl[1]}%, ${startHsl[2]}%)`;
    const end = `hsl(${endHsl[0]}, ${endHsl[1]}%, ${endHsl[2]}%)`;

    // Style par région
    function style(feature) {
        return {
            fillColor: colorData[feature.properties.nom] || "#fff",
            weight: 0.5,
            opacity: 1,
            color: "#ccc",
            fillOpacity: 0.9
        };
    }

    // Tooltip
    function onEachFeature(feature, layer) {
        const value = chartData[feature.properties.nom] ?? 0;

        layer.bindTooltip(
            `${feature.properties.nom} : ${value}`,
            {
                permanent: false,
                direction: "center",
                className: "region-tooltip"
            }
        );
    }

    // Charger GeoJSON local
    fetch("/geo/regions.geojson")
        .then(r => r.json())
        .then(geojson => {
            L.geoJSON(geojson, { style, onEachFeature }).addTo(map);
        });

    // Légende
    const legend = L.control({ position: "topright" });

    legend.onAdd = function () {
        const div = L.DomUtil.create("div", "map-legend");

        div.innerHTML = `
            <div class="map-legend__value map-legend__value--max">${max.toFixed(2)} ${colorData.mode === "pourcent" ? "%" : ""}</div>
            <div class="map-legend-bar" style="--gradient: linear-gradient(to top, ${start}, ${end})"></div>
            <div class="map-legend__value map-legend__value--min">${min.toFixed(2)} ${colorData.mode === "pourcent" ? "%" : ""}</div>
        `;

        return div;
    };

    legend.addTo(map);

    function handleResize() {
        map.invalidateSize();
        fitMapToContainer();
    }

    if (typeof ResizeObserver !== 'undefined') {
        const resizeObserver = new ResizeObserver(handleResize);
        resizeObserver.observe(el);
    } else if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResize);
    }
}

if (typeof window !== 'undefined') {
    window.initLeafletMap = initLeafletMap;
}
