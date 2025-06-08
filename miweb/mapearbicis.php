<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mapa de Estaciones Valenbisi JDG</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
#map { 
  height: 550px; 
  width: 100%; 
  margin-top: 20px; 
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
body { 
  margin: 0; 
  font-family: Arial, sans-serif; 
  text-align: center; 
  background-color: #f0f5ff; 
  padding: 20px;
}
h1 {
  color: #2c5fa8;
  font-size: 28px;
  margin-top: 20px;
  margin-bottom: 10px;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}
.legend {
  padding: 10px;
  background: rgba(255,255,255,0.8);
  border-radius: 5px;
  box-shadow: 0 1px 5px rgba(0,0,0,0.1);
  line-height: 1.5;
  color: #333;
}
.legend i {
  width: 18px;
  height: 18px;
  float: left;
  margin-right: 8px;
  opacity: 0.8;
  border-radius: 50%;
}
#locate-btn {
  background-color: #2c5fa8;
  color: white;
  border: none;
  padding: 10px 20px;
  font-size: 16px;
  border-radius: 5px;
  cursor: pointer;
  margin-bottom: 10px;
  transition: background-color 0.3s;
}
#locate-btn:hover {
  background-color: #1d3f7a;
}
.user-location {
  background-color: #2c5fa8 !important;
  border: 2px solid white !important;
}
</style>
</head>
<body>
<h1>Mapeo de Bicicletas en Valencia</h1>
<button id="locate-btn">Localízame</button>
<div id="map"></div>

<script>
// Inicializa el mapa centrado en Valencia
var map = L.map('map').setView([39.47, -0.37], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
}).addTo(map);

// Variable para almacenar el marcador de ubicación del usuario
var userLocationMarker = null;

// Función para definir el color del marcador según las bicicletas disponibles
function getMarkerColor(available) {
  if (available < 5) {
    return 'red'; // Rojo
  } else if (available >= 5 && available < 10) {
    return 'orange'; // Naranja
  } else if (available >= 10 && available < 20) {
    return 'yellow'; // Amarillo
  } else {
    return 'green'; // Verde
  }
}

// Función para crear un marcador con número
function createNumberedMarker(lat, lon, number, color) {
  return L.circleMarker([lat, lon], {
    color: '#333',
    weight: 1,
    fillColor: color,
    fillOpacity: 0.8,
    radius: 15
  }).bindTooltip(number.toString(), {
    permanent: true,
    direction: 'center',
    className: 'circle-label'
  });
}

// Añado una leyenda para explicar los colores de los circulos
var legend = L.control({position: 'bottomright'});
legend.onAdd = function(map) {
  var div = L.DomUtil.create('div', 'legend');
  div.innerHTML = `
    <h4>Bicis disponibles</h4>
    <div><i style="background:red"></i> 0-4</div>
    <div><i style="background:orange"></i> 5-9</div>
    <div><i style="background:yellow"></i> 10-19</div>
    <div><i style="background:green"></i> 20+</div>
  `;
  return div;
};

// Añadimos la leyenda al mapa
legend.addTo(map);

// Estilo para las etiquetas de los circulos de las bicis
var style = document.createElement('style');
style.textContent = `
  .circle-label {
    background: transparent;
    border: none;
    box-shadow: none;
    font-weight: bold;
    font-size: 10px;
    color: #333;
  }
`;
document.head.appendChild(style);

// Función para localizar al usuario
function locateUser() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        var userLat = position.coords.latitude;
        var userLng = position.coords.longitude;
        
        // Eliminar marcador anterior si existe
        if (userLocationMarker) {
          map.removeLayer(userLocationMarker);
        }
        
        // Crear nuevo marcador para la ubicación del usuario
        userLocationMarker = L.circleMarker([userLat, userLng], {
          color: 'white',
          weight: 2,
          fillColor: '#2c5fa8',
          fillOpacity: 1,
          radius: 10,
          className: 'user-location'
        }).addTo(map);
        
        // Centrar el mapa en la ubicación del usuario
        map.setView([userLat, userLng], 15);
        
        // Mostrar información de la ubicación
        userLocationMarker.bindPopup("¡Estás aquí!<br>Latitud: " + userLat.toFixed(6) + 
                                   "<br>Longitud: " + userLng.toFixed(6)).openPopup();
      },
      function(error) {
        alert("No se pudo obtener tu ubicación: " + error.message);
      },
      {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      }
    );
  } else {
    alert("La geolocalización no es soportada por tu navegador.");
  }
}

// Evento para el botón de localización
document.getElementById('locate-btn').addEventListener('click', locateUser);

// Cargar el archivo data.json
fetch('data.json')
  .then(response => {
    if (!response.ok) {
      throw new Error(`Error al cargar data.json: ${response.statusText}`);
    }
    return response.json();
  })
  .then(data => {
    // Iterar sobre las estaciones y agregar marcadores al mapa
    Object.values(data).forEach(station => {
      const { lat, lon, address, available, free, total } = station;
      if (lat && lon) {
        const color = getMarkerColor(available);
        const marker = createNumberedMarker(lat, lon, available, color);
        
        marker.addTo(map)
          .bindPopup(`
            <strong>${address}</strong><br>
            <b>Bicis disponibles:</b> ${available}<br>
            <b>Espacios libres:</b> ${free}<br>
            <b>Capacidad total:</b> ${total}
          `);
      }
    });
  })
  .catch(error => {
    console.error('Error cargando los datos:', error);
    // Mostrar mensaje de error en el mapa
    L.popup()    
      .setLatLng(map.getCenter())
      .setContent(`<div style="color:red;font-weight:bold;">Error cargando datos: ${error.message}</div>`)
      .openOn(map);
  });
</script>
</body>
</html>