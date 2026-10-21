(function () {
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  var mapRoot = document.querySelector('[data-map]');
  if (!mapRoot) return;

  var latInput = document.getElementById('latitude');
  var lngInput = document.getElementById('longitude');
  var locStatus = document.getElementById('location-status');
  var geoBtn = document.getElementById('use-location');
  var canvas = mapRoot.querySelector('canvas');
  if (!canvas || !latInput || !lngInput) return;

  var state = {
    lat: parseFloat(latInput.value) || 20.5937,
    lng: parseFloat(lngInput.value) || 78.9629,
    zoom: latInput.value ? 14 : 4,
    hasPin: Boolean(latInput.value && lngInput.value)
  };

  function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }

  function lngToX(lng, z) {
    return ((lng + 180) / 360) * Math.pow(2, z);
  }
  function latToY(lat, z) {
    var rad = lat * Math.PI / 180;
    return (1 - Math.log(Math.tan(rad) + 1 / Math.cos(rad)) / Math.PI) / 2 * Math.pow(2, z);
  }
  function xToLng(x, z) {
    return x / Math.pow(2, z) * 360 - 180;
  }
  function yToLat(y, z) {
    var n = Math.PI - 2 * Math.PI * y / Math.pow(2, z);
    return 180 / Math.PI * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
  }

  function setStatus(text) {
    if (locStatus) locStatus.textContent = text;
  }

  function setCoords(lat, lng, announce) {
    state.lat = clamp(lat, -85, 85);
    state.lng = ((lng + 180) % 360 + 360) % 360 - 180;
    state.hasPin = true;
    latInput.value = state.lat.toFixed(7);
    lngInput.value = state.lng.toFixed(7);
    if (announce) setStatus('Location set to ' + state.lat.toFixed(5) + ', ' + state.lng.toFixed(5));
    draw();
  }

  function draw() {
    var w = canvas.clientWidth || 640;
    var h = canvas.clientHeight || 280;
    canvas.width = w;
    canvas.height = h;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#c5d4e4';
    ctx.fillRect(0, 0, w, h);

    var z = state.zoom;
    var cx = lngToX(state.lng, z);
    var cy = latToY(state.lat, z);
    var tileSize = 256;
    var startX = Math.floor(cx - w / 2 / tileSize);
    var startY = Math.floor(cy - h / 2 / tileSize);
    var tilesX = Math.ceil(w / tileSize) + 1;
    var tilesY = Math.ceil(h / tileSize) + 1;
    var n = Math.pow(2, z);

    for (var ty = 0; ty <= tilesY; ty++) {
      for (var tx = 0; tx <= tilesX; tx++) {
        var tileX = ((startX + tx) % n + n) % n;
        var tileY = startY + ty;
        if (tileY < 0 || tileY >= n) continue;
        var img = new Image();
        img.crossOrigin = 'anonymous';
        (function (image, dx, dy) {
          image.onload = function () {
            ctx.drawImage(image, dx, dy, tileSize, tileSize);
            if (state.hasPin) drawPin(ctx, w, h);
          };
        })(img, (startX + tx - cx) * tileSize + w / 2, (tileY - cy) * tileSize + h / 2);
        img.src = 'https://tile.openstreetmap.org/' + z + '/' + tileX + '/' + tileY + '.png';
      }
    }
    if (state.hasPin) drawPin(ctx, w, h);
  }

  function drawPin(ctx, w, h) {
    ctx.save();
    ctx.fillStyle = '#0b1f3a';
    ctx.beginPath();
    ctx.arc(w / 2, h / 2 - 8, 8, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.moveTo(w / 2 - 7, h / 2 - 6);
    ctx.lineTo(w / 2, h / 2 + 10);
    ctx.lineTo(w / 2 + 7, h / 2 - 6);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  canvas.addEventListener('click', function (ev) {
    var rect = canvas.getBoundingClientRect();
    var x = ev.clientX - rect.left;
    var y = ev.clientY - rect.top;
    var z = state.zoom;
    var cx = lngToX(state.lng, z);
    var cy = latToY(state.lat, z);
    var tileSize = 256;
    var worldX = cx + (x - canvas.width / 2) / tileSize;
    var worldY = cy + (y - canvas.height / 2) / tileSize;
    setCoords(yToLat(worldY, z), xToLng(worldX, z), true);
    if (state.zoom < 15) {
      state.zoom = Math.min(16, state.zoom + 2);
      draw();
    }
  });

  ['latitude', 'longitude'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
      var lat = parseFloat(latInput.value);
      var lng = parseFloat(lngInput.value);
      if (!isNaN(lat) && !isNaN(lng)) {
        state.zoom = Math.max(state.zoom, 12);
        setCoords(lat, lng, true);
      }
    });
  });

  if (geoBtn) {
    geoBtn.addEventListener('click', function () {
      if (!navigator.geolocation) {
        setStatus('Geolocation is not available in this browser. Enter coordinates or click the map.');
        return;
      }
      setStatus('Finding your position…');
      navigator.geolocation.getCurrentPosition(function (pos) {
        state.zoom = 15;
        setCoords(pos.coords.latitude, pos.coords.longitude, true);
      }, function () {
        setStatus('Could not read your position. Click the map or type coordinates instead.');
      }, { enableHighAccuracy: true, timeout: 8000 });
    });
  }

  window.addEventListener('resize', draw);
  draw();
})();
