// Si se recarga fuera
if (performance.getEntriesByType("navigation")[0].type === "reload") {
    // lo mandamos forzosamente a la función de Abandonar para que limpie la BD
    window.location.replace("?c=partida&a=Abandonar&id=<?php echo $partida['id_partida']; ?>");
}