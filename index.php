<?php
//EN MODELO COGER LOS DATOS QUE QUEREMOS
//EN CONTROLADOR LOS COMPARAREMOS
//EN VISTA, MOSTRAMOS EL RESULTADO

include "controlador.php";  // Incluye el archivo del controlador para manejar la lógica de la aplicación.

// Comprueba si se ha proporcionado el parámetro `action` en la URL.
if (!isset($_GET['action'])) {
    $action = "main";  // Si no se proporciona, utiliza "main" como acción predeterminada.
} else {
    $action = $_GET['action'];  // Si se proporciona, lo asigna a `$action`.
}

// Crea una instancia del controlador.
$controller = new Controller();

// Comprueba si el método correspondiente a la acción existe en el controlador.
if (method_exists($controller, $action)) {
    // Si el método existe, lo ejecuta y almacena el resultado (HTML generado) en `$phtml`.
    $phtml = $controller->$action();
} else {
    // Si el método no existe, llama al método `showMethodError` para mostrar un mensaje de error.
    $phtml = $controller->showMethodError();
}

// Muestra el contenido HTML generado.
echo $phtml;
?>