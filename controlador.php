<?php

use maxh\Nominatim\Nominatim;

include_once "model.php";
include_once "vista.php";
require_once 'LeafletMaphp.php'; // https://github.com/amontalvot/LeafletMaphp
require "vendor/autoload.php";

class Controller
{

    public function __construct() {}

    public function main()
    {
        $vista = new View(null, "main");
        $vista->header();
        $opciones = [
            ["value" => "A", "option" => "Alcarria"],
            ["value" => "C", "option" => "Campiña"],
            ["value" => "T", "option" => "Molina/Alto Tajo"],
            ["value" => "N", "option" => "Sierra Norte"]
        ];
        $vista->select($opciones, "control_mapa");
        $vista->footer();
        return $vista->getPhtml();
    }

    //Action que se encargara de crear el mapa de la zona seleccionada.
    public function control_mapa() {  
        
        // if (isset($_GET['id'])) {
        //     $zona = $_GET['id'];
        //     $coordenadas = $this->obtenerCoordenadas($zona);

        //     $vista = new View(null, "Primer mapa");
        //     [$mapa, $head, $div_texto_mapa] = $this->crear_mapa($coordenadas, $zona);

        //     $vista->header($head);
        //     $vista->mostrarElementoCompleto($mapa);
        //     $vista->mostrarElementoCompleto($div_texto_mapa);
        //     $vista->footer();

        //     return $vista->getPhtml();
        // } else {
        //     echo "control_mapa control de error";
        // }

        try {
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Exception("ID de zona inválido o no especificado");
            }
            $zona = $_GET['id'];
            $coordenadas = $this->obtenerCoordenadas($zona);
    
            $vista = new View(null, "Primer mapa");
            [$mapa, $head, $div_texto_mapa] = $this->crear_mapa($coordenadas, $zona);

            $vista->header($head);
            $vista->mostrarElementoCompleto($mapa);
            $vista->mostrarElementoCompleto($div_texto_mapa);
            $vista->footer();


        } catch (Exception $e) {
            $vista = new View(null, "Primer mapa");
            $vista->header();
            $vista->error($e->getMessage()); // Muestra un mensaje de error en la vista
        }
        return $vista->getPhtml();


    }

    private function crear_mapa(array $coordenadas, string $idZona): array {
        $mapa = new LeafletMaphp("map", 500, 500, "margin: auto;", map::ES_IGN_BASE);

        $model = new Model();

        $info_rutas = $model->obtener_rutas_csv($idZona);
        $info_rutas = $this->eliminar_rutas_duplicadas($info_rutas);
        $coordenadas_rutas = $model->coordenadas_rutas($info_rutas);

        $mapa = $this->añadir_inicios_rutas($coordenadas_rutas, $mapa);

        $mapa->setCenter($coordenadas["lat"], $coordenadas["lon"]);
        // $mapa->addMarker(40.4168, -3.7038);

        $head = $mapa->showHeadTags();
        $map2 = $mapa->show();
        $div_texto_mapa = $mapa->showOnClickDiv();

        return [$map2, $head, $div_texto_mapa];
    }

    //Creo el mapa y los marcadores con los inicios de ruta. Tambien creo las URL usando el tiempo de la estacion mas cercana.
    private function añadir_inicios_rutas(array $coordenadas_rutas, object $mapa): object {
        $rutas_añadidas = [];
        foreach ($coordenadas_rutas as $ruta) {
            $url = "index.php?aemetnotfound=a&action=cargar_el_tiempo";
            if ($ruta['tipo'] == 'I') {
                if (!in_array($ruta['id'], $rutas_añadidas)) {

                [$link_info_aemet, $estacionCercana] = $this->informacionPuntoAemet($ruta['latitud'], $ruta['longitud']);
                // if($link_info_aemet) {print_r($link_info_aemet);}

                $idMarcador = $mapa->addMarker($ruta['latitud'], $ruta['longitud'], "red", 500); // Guardamos el ID del marcador

                $url_enlaces_ruta = "index.php?idruta=" . urlencode($ruta['id'] ?? 'No encontrado') . "&tipo_ruta=" . urlencode($ruta['tiporuta'] ?? '') . "&action=pantalla_ruta_completa";

                $enlace_puntos_ruta = "<a href=$url_enlaces_ruta>Enlace a ruta</a>";

                $enlace_web = "<a href=\"{$ruta['web']}\">WEB</a>";

                $enlace_tiempo_aemet = "";

                foreach ($link_info_aemet as $tiempo_ruta) {
                    if ($tiempo_ruta['idema'] == $estacionCercana['indicativo']) {

                        $url = "index.php?idema=" . urlencode($tiempo_ruta['idema'] ?? "No encontrado") .
                            "&prec=" . urlencode($tiempo_ruta['prec'] ?? "No encontrado") .
                            "&fint=" . urlencode($tiempo_ruta['fint'] ?? "No encontrado") .
                            "&ubi=" . urlencode($tiempo_ruta['ubi'] ?? "No encontrado") .
                            "&tamin=" . urlencode($tiempo_ruta['tamin'] ?? "No encontrado") .
                            "&tamax=" . urlencode($tiempo_ruta['tamax'] ?? "No encontrado") .
                            "&action=cargar_el_tiempo";
                    }
                }
                $enlace_tiempo_aemet = "<a href=$url>EL TIEMPO</a>";

                $mapa->addTooltip(figure::MARKER, $idMarcador, $ruta["nombre_ruta"]); //Mostrar nombre
                $mapa->addOnClickText(figure::MARKER, $idMarcador, $enlace_puntos_ruta . " ---- " . $enlace_web . " ---- " . $enlace_tiempo_aemet);  
                
                $rutas_añadidas[] = $ruta['id'];
                // $mapa->addPopup(figure::MARKER, $idMarcador, $enlace_puntos_ruta . "<br>" . $enlace_web . "<br>" . $enlace_tiempo_aemet);
            }}
        }
        return $mapa;
    }

    public function pantalla_ruta_completa() {
        $vista = new View();
        $model = new Model();
    
        try {
            if (!isset($_GET['idruta']) || !isset($_GET['tipo_ruta'])) {
                throw new Exception("Falta el ID de la ruta o el tipo de ruta en la URL.");
            }
    
            $idruta = $_GET['idruta'];
            $tiporuta = $_GET['tipo_ruta'];
    
    
            // Obtener los datos de la ruta
            $info_puntos_ruta = $model->coordenadas_ruta_especifica_completa($idruta);
    
            // Si no hay información, lanzar un error
            if (!$info_puntos_ruta) {
                throw new Exception("No se encontraron datos para la ruta especificada.");
            }
    
            [$mapa_ruta, $div_texto_mapa, $head] = $this->crear_mapa_ruta_completa($info_puntos_ruta, $tiporuta);
            $vista->header($head);
            $vista->mostrarElementoCompleto($div_texto_mapa);
            $vista->mostrarElementoCompleto($mapa_ruta);
            $vista->footer();
    
        } catch (Exception $e) {
            $vista->error($e->getMessage()); // Muestra un mensaje de error en la vista
        }
    
        return $vista->getPhtml();
    }
    

    private function crear_mapa_ruta_completa(array $info_puntos_ruta, $tiporuta): array {
        //     [id] => SPG-11
        //     [tipo] => I
        //     [latitud] => 40.65880
        //     [longitud] => -3.11281
        //     [nombre_punto] => Subida a Peña Hueva
        //     [altura] => 
        $mapa = new LeafletMaphp("map", 500, 500, "margin: auto;", map::ES_IGN_BASE);
        $div_texto_mapa = $mapa->showOnClickDiv();

        $array_coordenadas = [];
        foreach ($info_puntos_ruta as $punto_ruta) { //deberia ordenar el array para que quede bien?
            $lat = $punto_ruta['latitud'];
            $lon = $punto_ruta['longitud'];

            if ($punto_ruta["tipo"] === "R") { // R = restaurante

                $enlace_restaurante = '<a href="index.php?action=pantalla_restaurante&lat=' . $lat . '&lon=' . $lon . '">Ver información del marcador</a>';
                $marcador_restaurante = $mapa->addMarker($lat, $lon, "red", 200);
                //AÑADIR enlace_restaurante AL MARCADOR

                $mapa->addOnClickText(figure::MARKER, $marcador_restaurante, $enlace_restaurante);
            } else {
                switch ($punto_ruta["tipo"]) {
                    case "I":
                        $color = "green";
                        break;
                    case "F": //final
                        $color = "red";
                        break;
                    case "G": //giro
                        $color = "blue";
                        break;
                    case "D": //desvio
                        $color = "gray";
                        break;
                }

                //Juntar por orden las coordenadas de todos los puntos de ruta para luego unirlos con una línea
                // $coordenadas[] = array($lon, $lat);
                $array_coordenadas[] = [$lon, $lat]; //que no se olvide el [] para hacer arrays sin inicializar. lon primero para no aparecer en somalia

                $circulo = $mapa->addCircle($lat, $lon, $color, 100);

                if($punto_ruta['nombre_punto']) {
                    $mapa->addTooltip(figure::CIRCLE, $circulo, $punto_ruta["nombre_punto"]); //Mostrar nombre
                }
                if (!empty($punto_ruta["altura"])) { //Añadir cima si hay
                    $mapa->addPopUp(figure::CIRCLE, $circulo, $punto_ruta["altura"]);
                }
            }
        }
        if ($tiporuta == "C") { //si la ruta es circular, el I es el final
            foreach ($info_puntos_ruta as $punto_rutaX) {
                if ($punto_rutaX["tipo"] === "I") {
                    $array_coordenadas[] = [$punto_rutaX['longitud'], $punto_rutaX['latitud']];
                }
            }
        }


        $mapa->addPolyline($array_coordenadas, "rgba(0, 0, 0, 0.3)");

        $head = $mapa->showHeadTags();
        $mapa_mostrar = $mapa->show();
        return [$mapa_mostrar, $div_texto_mapa, $head];
    }


    public function pantalla_restaurante() {
        try {
            if (isset($_GET['lon']) && (isset($_GET['lat']))) {
                $lat = $_GET['lon'];
                $lon = $_GET['lat'];
                [$mapa, $headtags, $cabecera] = $this->crear_mapa_restaurante($lon, $lat);

                if(isset($mapa)) {
                    $vista = new View(null, $cabecera);
                    $vista->header($headtags);
                    $vista->mostrarDivConTexto($cabecera);
                    $vista->mostrarElementoCompleto($mapa);
                    $vista->footer();
                } 
            }
        }
        catch (Throwable $e) {
            $vista = new View();
            $vista->error($e->getMessage());
        }
        return $vista->getPhtml();
    }


    private function crear_mapa_restaurante(string $lon, string $lat) : array{

        //https://nominatim.openstreetmap.org/reverse?format=json&lat=41.0833227&lon=-3.0031063

        $url_api_nominatim = "http://nominatim.openstreetmap.org/";

        //Crear instancia de nominatim (el user-agent????)
        $nominatim = new Nominatim($url_api_nominatim, ["User-Agent" => "rutas (ej@email.com)"]);

        //consulta inversa para obtener información del lugar basado en latitud y longitud. ?
        $reverse = $nominatim->newReverse()->latlon((float)$lon, (float)$lat);


        //Encontrar el lugar en nominatim
        $array_datos_restaurante = $nominatim->find($reverse);
        [$nombre_restaurante, $localidad, $cp_restaurante] = $this->obtenerDatosRestaurante($array_datos_restaurante);
        $cabecera = "$nombre_restaurante __ $localidad __ $cp_restaurante";

        $mapa = new LeafletMaphp("map", 500, 500, "margin: auto;", map::ES_IGN_BASE);
        $marcador_restaurante = $mapa->addMarker($lon, $lat, "red", 200);
        $mapa->addPopUp(figure::MARKER, $marcador_restaurante, $nombre_restaurante);
        $headtags = $mapa->showHeadTags();
        $mapa_mostrar = $mapa->show();

       
        return [$mapa_mostrar,$headtags, $cabecera];
        // Tanto encima del mapa como en el título de la página hay que mostrar 
        //solo el nombre del restaurante y el nombre de la ciudad, pueblo o aldea donde se encuentre, 
        //junto al código postal.
    }

    private function obtenerDatosRestaurante(array $datos_restaurante): array {
        // Nombre del restaurante
        if (isset($datos_restaurante['name'])) {
            $nombre_restaurante = $datos_restaurante['name'];
        } elseif (isset($datos_restaurante['display_name'])) {
            $nombre_restaurante = $datos_restaurante['display_name'];
        }    else {
            $nombre_restaurante = "Coordenadas sin ningun local asignado";
        }
        // Localidad: ciudad, pueblo o aldea
        if (isset($datos_restaurante['address'])) {
            if (isset($datos_restaurante['address']['city'])) {
                $localidad = 'Ciudad: ' . $datos_restaurante['address']['city'];
            } elseif (isset($datos_restaurante['address']['town'])) {
                $localidad = 'Pueblo: ' . $datos_restaurante['address']['town'];
            } elseif (isset($datos_restaurante['address']['village'])) {
                $localidad = 'Aldea: ' . $datos_restaurante['address']['village'];
            } elseif (isset($datos_restaurante['address']['hamlet'])) {
                $localidad = 'Aldea: ' . $datos_restaurante['address']['hamlet'];
            } else {
                $localidad = 'Localidad no disponible';
            }
        } else {
            $localidad = 'Localidad no disponible';
        }
        
    
        // Código postal
        if (isset($datos_restaurante['address']['postcode'])) {
            $cp_restaurante = 'Código Postal: ' . $datos_restaurante['address']['postcode'];
        } else {
            $cp_restaurante = 'Código Postal no disponible';
        }    
        // Retornar los datos como un array
        return [$nombre_restaurante, $localidad, $cp_restaurante];
    }

    public function cargar_el_tiempo(){
        $vista = new View();

        if (isset($_GET['idema']) && isset($_GET['fint']) && isset($_GET['prec']) && isset($_GET['ubi']) && isset($_GET['tamin']) && isset($_GET['tamax'])) {
            $datos = [
                'Ubicacion' => $_GET['ubi'],
                'Codigo estaicon' => $_GET['idema'],
                'Fecha y hora' => $_GET['fint'],
                'precipitaciones' => $_GET['prec'],
                'Tª minima' => $_GET['tamin'],
                'Tª maxima' => $_GET['tamax'],
            ];

            $vista->header();
            $vista->mostrarTablaTiempo($datos);
            $vista->footer();
        } else {
            if (isset($_GET['aemetnotfound'])) {
                $vista->error('No he encontrado los datos de una estacion cercana');
            } else {
                $vista->error('Falta algun get');
            }
        }
        return $vista->getPhtml();
    }

    //consulta al modelo para obtener datos de aemet
    //Busco el tiempo de la estacion mas cercana, en vez del municipio, esto hace que solo tenga el tiempo actual 
    //y no la prediccion del tiempo de la semana.
    private function informacionPuntoAemet(string $latitud, string $longitud): array{
        $model = new Model();
        [$datos_tiempo, $estacionCercana] = $model->consultar_api_el_tiempo($latitud, $longitud);
        return [$datos_tiempo, $estacionCercana];
    }

    //Elimina las rutas marcadas como similar a otras
    private function eliminar_rutas_duplicadas($info_rutas){ 
        foreach ($info_rutas as $key1 => $ruta) {
            if (!empty($ruta['similar'])) {
                foreach ($info_rutas as $key2 => $rutabucle2) {
                    if ($rutabucle2['id'] == $ruta['similar']) {
                        unset($info_rutas[$key1]); // Elimina la que tiene columna CSV con datos en "similar"
                    }
                }
            }
        }

        // Reindexar el array para evitar huecos en los índices
        $info_rutas = array_values($info_rutas);

        return $info_rutas;
    }

    //Las cordenadas son constantes
    private function obtenerCoordenadas($zona)
    {
        switch (strtolower($zona)) { // minúsculas para evitar errores de mayúsculas
            case "a":
                return ["lat" => 40.6499, "lon" => -2.6072];

            case "c":
                return ["lat" => 40.6284, "lon" => -3.1650];

            case "t":
                return ["lat" => 40.8571, "lon" => -1.8892];

            case "n":
                return ["lat" => 41.1111, "lon" => -3.1256];

            default:
                return ["lat" => 0, "lon" => 0]; // Caso por defecto si la zona no es válida
        }
    }




    public function showMethodError() {
        $action = $_GET['action'] ?? '';
        $v = new View(null, "Método $action");
        $v->header();
        $v->no_action($action);
        $v->footer();
        return $v->getPhtml();
    }
}
