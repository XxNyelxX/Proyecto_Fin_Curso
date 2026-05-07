<?php
$ruta_txt = __DIR__ . '/listado_galego.txt';
$ruta_json = __DIR__ . '/data/diccionario.json';

if (file_exists($ruta_txt)) {
    $contenido = file_get_contents($ruta_txt);
    $lineas = explode("\n", $contenido);
    $palabras = [];

    // Usamos strtr en lugar de str_replace, que es mucho más robusto para esto
    $sustituciones = array(
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u',
        'ï'=>'i', 'ü'=>'u', 'Ï'=>'i', 'Ü'=>'u'
    );

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        
        if (empty($linea) || is_numeric($linea)) {
            continue;
        }

        $partes = explode('/', $linea);
        // Forzamos a minúsculas respetando UTF-8
        $palabra = mb_strtolower($partes[0], 'UTF-8');

        // Aplicamos la limpieza de tildes blindada
        $palabra_limpia = strtr($palabra, $sustituciones);

        // Añadimos la 'u' a la expresión regular para que entienda bien el UTF-8
        if (preg_match('/^[a-zñ]{2,}$/u', $palabra_limpia)) {
            $palabras[] = $palabra_limpia;
        }
    }

    $palabras = array_values(array_unique($palabras));

    // Añadimos JSON_UNESCAPED_UNICODE para que la 'ñ' no se convierta en basura
    file_put_contents($ruta_json, json_encode($palabras, JSON_UNESCAPED_UNICODE));

    echo "¡Arreglado! Se han guardado <b>" . count($palabras) . "</b> palabras totalmente limpias.";
} else {
    echo "Error: No encuentro el archivo listado_galego.txt";
}
?>