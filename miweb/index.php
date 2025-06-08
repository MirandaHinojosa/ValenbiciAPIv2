
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Disponibilidad de ValenBisi</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f9f9f9;
}
h1 {
    text-align: center;
    color: #333;
}
.botonMapa, .botonFiltrar, .botonLimpiar, .botonGuardar {
    display: block; 
    width: 80%; 
    margin: 10px auto; 
    padding: 10px; 
    text-align: center; 
    text-decoration: none; 
    font-size: 16px; 
    border-radius: 5px;
    cursor: pointer;
}
.botonMapa {
    background-color: #4CAF50; 
    color: white; 
}
.botonFiltrar {
    background-color: #2196F3;
    color: white;
}
.botonLimpiar {
    background-color: #ff9800;
    color: white;
}
.botonGuardar {
    display: inline-block;
    width: auto;
    padding: 5px 10px;
    background-color: #4CAF50;
    color: white;
    margin: 5px;
    font-size: 14px;
}
.filtros-container {
    width: 80%;
    margin: 20px auto;
    padding: 15px;
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.filtro-group {
    margin-bottom: 10px;
}
.filtro-group label {
    display: inline-block;
    width: 150px;
}
table {
    width: 80%;
    margin: 0 auto;
    border-collapse: collapse;
    background-color: #fff;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
th {
    background-color: #4CAF50;
    color: white;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
tr:hover {
    background-color: #ddd;
}
.estado-abierto {
    color: green;
    font-weight: bold;
}
.estado-cerrado {
    color: red;
    font-weight: bold;
}
</style>
</head>
<body>
<h1>Disponibilidad de ValenBisi (Juan Carlos Miranda)</h1>

<?php
// Configuración de la base de datos AWS (MODIFICA ESTOS VALORES)
$db_host = "";
$db_username = "";
$db_password = "";
$db_name = "";

// Conexión a la base de datos
$db_conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($db_conn->connect_error) {
    echo "<p style='color: red; text-align: center;'>Error de conexión a la base de datos: " . $db_conn->connect_error . "</p>";
}

// Función para guardar una estación en la base de datos
function guardarEstacion($conn, $station) {
    $estacion_id = $station['number'];
    $direccion = $station['address'];
    $bicis_disponibles = $station['available'];
    $anclajes_libres = $station['free'];
    $estado_operativo = $station['open'] ? 1 : 0;
    $lat = $station['lat'];
    $lon = $station['lon'];
    
    // Debug: Mostrar valores en consola (para ver en el navegador)
    echo "<script>console.log('Guardando estación:', { 
        id: $estacion_id, 
        direccion: '$direccion',
        bicis: $bicis_disponibles,
        anclajes: $anclajes_libres,
        estado: $estado_operativo,
        lat: $lat, 
        lon: $lon 
    });</script>";
    
    // Preparamos la consulta SQL corregida
    $sql = "INSERT INTO historico 
            (estacion_id, direccion, bicis_disponibles, anclajes_libres, estado_operativo, latitud, longitud) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            direccion = VALUES(direccion),
            bicis_disponibles = VALUES(bicis_disponibles),
            anclajes_libres = VALUES(anclajes_libres),
            estado_operativo = VALUES(estado_operativo),
            latitud = VALUES(latitud),
            longitud = VALUES(longitud)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return "Error al preparar la consulta: " . htmlspecialchars($conn->error);
    }
    
    // Bind de parámetros corregido
    $stmt->bind_param("isiiidd", 
        $estacion_id, 
        $direccion, 
        $bicis_disponibles, 
        $anclajes_libres, 
        $estado_operativo,
        $lat,
        $lon
    );
    
    if ($stmt->execute()) {
        return "Guardado correctamente (ID: $estacion_id)";
    } else {
        return "Error al guardar la estación: " . htmlspecialchars($stmt->error);
    }
}

// Procesar filtros del formulario
$filtro_abiertas = isset($_POST['filtro_abiertas']) ? true : false;
$filtro_bicis_min = isset($_POST['filtro_bicis_min']) ? intval($_POST['filtro_bicis_min']) : 0;

// Formulario de filtros
echo '<div class="filtros-container">';
echo '<form method="post" action="">';
echo '<h3>Filtros:</h3>';
echo '<div class="filtro-group">';
echo '<label><input type="checkbox" name="filtro_abiertas" '.($filtro_abiertas ? 'checked' : '').'> Mostrar solo estaciones abiertas</label>';
echo '</div>';
echo '<div class="filtro-group">';
echo '<label>Bicis disponibles mínimo:</label>';
echo '<input type="number" name="filtro_bicis_min" min="0" value="'.$filtro_bicis_min.'">';
echo '</div>';
echo '<button type="submit" class="botonFiltrar">Aplicar Filtros</button>';
echo '<button type="submit" class="botonLimpiar" onclick="resetForm(this.form)">Limpiar Filtros</button>';
echo '</form>';
echo '</div>';

// Script para limpiar filtros
echo '<script>
function resetForm(form) {
    form.querySelectorAll("input").forEach(input => {
        if(input.type === "checkbox") input.checked = false;
        if(input.type === "number") input.value = "0";
    });
}
</script>';

$baseUrl = "https://valencia.opendatasoft.com/api/explore/v2.1/catalog/datasets/valenbisi-disponibilitat-valenbisi-dsiponibilidad/records?";
$limit = 20;
$offset = 0;
$allStations = [];
$errorOccurred = false;

do {
    $url = $baseUrl . "limit=" . $limit . "&offset=" . $offset;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    if ($response === false) {
        echo "<p style='color: red; text-align: center;'>Error en cURL: " . curl_error($ch) . "</p>";
        $errorOccurred = true;
        break;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        echo "<p style='color: red; text-align: center;'>Error en la solicitud a la API (Código HTTP: " . $httpCode . "). URL: " . $url . "</p>";
        $errorOccurred = true;
        break;
    }
    
    curl_close($ch);
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "<p style='color: red; text-align: center;'>Error al decodificar la respuesta JSON.</p>";
        $errorOccurred = true;
        break;
    }
    
    if (isset($data["results"]) && is_array($data["results"]) && count($data["results"]) > 0) {
        foreach ($data["results"] as $station) {
            // Aplicar filtros
            $cumpleFiltros = true;
            
            if ($filtro_abiertas && $station['open'] != "T") {
                $cumpleFiltros = false;
            }
            
            if ($filtro_bicis_min > 0 && $station['available'] < $filtro_bicis_min) {
                $cumpleFiltros = false;
            }
            
            if ($cumpleFiltros) {
                $allStations[$station['number']] = [
                    'number' => $station['number'],
                    'address' => $station['address'],
                    'open' => ($station['open'] == "T"),
                    'available' => (int)$station['available'],
                    'free' => (int)$station['free'],
                    'total' => (int)$station['total'],
                    'lat' => $station['geo_point_2d']['lat'],
                    'lon' => $station['geo_point_2d']['lon']
                ];
            }
        }
        $offset += $limit;
    } else {
        break;
    }
} while (isset($data["results"]) && is_array($data["results"]) && count($data["results"]) == $limit);

// Guardar temporalmente en archivo (sin mostrar mensaje)
if (!$errorOccurred && !empty($allStations)) {
    $filePath = getcwd() . '/data.json';
    file_put_contents($filePath, json_encode($allStations));
}

if (!empty($allStations)) {
    echo "<table>";
    echo "<tr><th>Dirección</th><th>Número</th><th>Estado</th><th>Disponibles</th><th>Libres</th><th>Total</th><th>Coordenadas</th><th>Acción</th></tr>";
    
    foreach ($allStations as $number => $station) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($station['address']) . "</strong></td>";
        echo "<td>" . $station['number'] . "</td>";
        echo "<td class='" . ($station['open'] ? "estado-abierto" : "estado-cerrado") . "'>" . ($station['open'] ? "Abierta" : "Cerrada") . "</td>";
        echo "<td>" . $station['available'] . "</td>";
        echo "<td>" . $station['free'] . "</td>";
        echo "<td>" . $station['total'] . "</td>";

        echo "<td>Lon(" . $station['lon'] . "), Lat(" . $station['lat'] . ")</td>";
        
        // Botón para guardar en la base de datos
        echo "<td>";
        echo '<form method="post" action="" style="display:inline;">';
        echo '<input type="hidden" name="guardar_estacion" value="'.htmlspecialchars(json_encode($station)).'">';
        echo '<button type="submit" class="botonGuardar">Guardar en DB</button>';
        echo '</form>';
        echo "</td>";
        
        echo "</tr>";
    }
    echo "</table>";
}

// Procesar guardado de estación individual
if (isset($_POST['guardar_estacion'])) {
    $station_data = json_decode($_POST['guardar_estacion'], true);
    if ($station_data && $db_conn) {
        $resultado = guardarEstacion($db_conn, $station_data);
        
        // Mostrar mensaje de resultado con estilo según éxito/error
        $color = strpos($resultado, "Error") === false ? "green" : "red";
        echo "<p style='color: $color; text-align: center;'>" . htmlspecialchars($resultado) . "</p>";
        
        // Depuración adicional para coordenadas
        if (strpos($resultado, "Error") !== false) {
            echo "<script>console.error('Error al guardar:', " . json_encode([
                'lat' => $station_data['lat'],
                'lon' => $station_data['lon'],
                'error' => $db_conn->error
            ]) . ");</script>";
        }
    }
}

// Cerrar conexión a la base de datos
if ($db_conn) {
    $db_conn->close();
}
?>

<a class="botonMapa" href="mapearbicis.php" target="target">
    <img src="./image/ImagenMapa.png" alt="Mapa" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 10px;"> 
    Ver mapa de estaciones
</a>

</body>
</html>