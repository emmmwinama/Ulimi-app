/* AgriVault maps — Leaflet init for the field-boundary editor and farm overview.
   Reads a JSON config from <script id="map-config" type="application/json">. */
(function () {
  "use strict";
  var el = document.getElementById("map");
  var cfgEl = document.getElementById("map-config");
  if (!el || !cfgEl || typeof L === "undefined") return;

  var cfg;
  try { cfg = JSON.parse(cfgEl.textContent || "{}"); } catch (e) { return; }

  L.Icon.Default.imagePath = cfg.imagePath || "/assets/vendor/leaflet/images/";

  var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19, attribution: "&copy; OpenStreetMap"
  });
  var sat = L.tileLayer(
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
    { maxZoom: 19, attribution: "Imagery &copy; Esri" }
  );

  var center = cfg.center || [-13.9, 33.6];
  var map = L.map(el, { layers: [cfg.satellite ? sat : osm] }).setView(center, cfg.zoom || 13);
  L.control.layers({ "Streets": osm, "Satellite": sat }, {}, { position: "topright" }).addTo(map);

  var bounds = null;
  function extend(layer) {
    try {
      var b = layer.getBounds ? layer.getBounds() : L.latLngBounds([layer.getLatLng()]);
      bounds = bounds ? bounds.extend(b) : b;
    } catch (e) {}
  }

  /* ---- render existing geometry ---- */
  function addGeoJson(geo, style, popup) {
    if (!geo) return null;
    var layer = L.geoJSON(geo, { style: style });
    if (popup) layer.bindPopup(popup);
    layer.addTo(map);
    extend(layer);
    return layer;
  }

  (cfg.boundaries || []).forEach(function (b) {
    addGeoJson(b.geo_json, { color: "#0284C7", weight: 2, fillOpacity: 0.12 },
      "<strong>" + esc(b.field_name) + "</strong>" + (b.area_ha ? "<br>" + b.area_ha + " ha" : ""));
  });
  (cfg.zones || []).forEach(function (z) {
    addGeoJson(z.geo_json, { color: z.colour || "#0D9488", weight: 1, dashArray: "4", fillOpacity: 0.15 },
      esc(z.name) + (z.field_name ? " — " + esc(z.field_name) : ""));
  });
  (cfg.markers || []).forEach(function (m) {
    var mk = L.marker([m.lat, m.lng]).addTo(map).bindPopup(
      "<strong>" + esc(m.label) + "</strong><br>" + esc(m.type));
    extend(mk);
  });
  (cfg.fieldPoints || []).forEach(function (f) {
    var mk = L.circleMarker([f.location_lat, f.location_lng], { radius: 6, color: "#0F172A", fillColor: "#0284C7", fillOpacity: 1 })
      .addTo(map).bindPopup(esc(f.name));
    extend(mk);
  });

  if (cfg.geometry) {
    addGeoJson(cfg.geometry, { color: "#0284C7", weight: 2, fillOpacity: 0.15 });
  }

  if (bounds && bounds.isValid()) map.fitBounds(bounds.pad(0.2));

  /* ---- boundary / zone drawing (field map) ---- */
  if (cfg.mode === "field" && cfg.canEdit && typeof L.Control.Draw !== "undefined") {
    var drawn = new L.FeatureGroup();
    map.addLayer(drawn);

    var drawControl = new L.Control.Draw({
      position: "topleft",
      draw: { polygon: { allowIntersection: false, showArea: true }, polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false },
      edit: { featureGroup: drawn, remove: true }
    });
    map.addControl(drawControl);

    var target = null;              // which hidden input to write to
    var areaOut = null;

    function beginDraw(inputId, areaId) {
      target = document.getElementById(inputId);
      areaOut = areaId ? document.getElementById(areaId) : null;
      drawn.clearLayers();
      new L.Draw.Polygon(map, drawControl.options.draw.polygon).enable();
    }

    Array.prototype.forEach.call(document.querySelectorAll("[data-begin-draw]"), function (btn) {
      btn.addEventListener("click", function () {
        beginDraw(btn.getAttribute("data-target"), btn.getAttribute("data-area"));
      });
    });

    map.on(L.Draw.Event.CREATED, function (e) {
      drawn.clearLayers();
      drawn.addLayer(e.layer);
      writeGeometry(e.layer);
    });
    map.on(L.Draw.Event.EDITED, function (e) {
      e.layers.eachLayer(writeGeometry);
    });
    map.on(L.Draw.Event.DELETED, function () {
      if (target) target.value = "";
    });

    function writeGeometry(layer) {
      if (!target) return;
      var gj = layer.toGeoJSON();
      target.value = JSON.stringify(gj.geometry);
      if (areaOut) {
        var ha = roughAreaHa(gj.geometry);
        areaOut.textContent = ha ? "≈ " + ha.toFixed(3) + " ha" : "";
      }
    }
  }

  /* ---- click-to-place marker (farm map) ---- */
  if (cfg.mode === "farm") {
    var latI = document.getElementById("marker_lat");
    var lngI = document.getElementById("marker_lng");
    var hint = document.getElementById("marker-hint");
    var picking = false;
    var pin = null;
    var toggle = document.getElementById("pick-location");
    if (toggle && latI && lngI) {
      toggle.addEventListener("click", function () {
        picking = !picking;
        toggle.textContent = picking ? "Click the map…" : "Pick location on map";
        if (hint) hint.hidden = !picking;
      });
      map.on("click", function (e) {
        if (!picking) return;
        latI.value = e.latlng.lat.toFixed(6);
        lngI.value = e.latlng.lng.toFixed(6);
        if (pin) map.removeLayer(pin);
        pin = L.marker(e.latlng).addTo(map);
        picking = false;
        toggle.textContent = "Pick location on map";
        if (hint) hint.hidden = true;
      });
    }
  }

  function roughAreaHa(geom) {
    try {
      var ring = geom.type === "Polygon" ? geom.coordinates[0] : geom.coordinates[0][0];
      if (!ring || ring.length < 3) return 0;
      var R = 6378137, meanLat = 0;
      ring.forEach(function (p) { meanLat += p[1]; });
      meanLat = (meanLat / ring.length) * Math.PI / 180;
      var mLat = Math.PI / 180 * R, mLng = mLat * Math.cos(meanLat), a = 0;
      for (var i = 0; i < ring.length; i++) {
        var p1 = ring[i], p2 = ring[(i + 1) % ring.length];
        a += (p1[0] * mLng) * (p2[1] * mLat) - (p2[0] * mLng) * (p1[1] * mLat);
      }
      return Math.abs(a) / 2 / 10000;
    } catch (e) { return 0; }
  }

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
})();
