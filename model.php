<?php

use maxh\Nominatim\Nominatim;

require_once 'LeafletMaphp.php';

class Model {
    

    public function __construct() {}

    //obtiene la info de guada rutas csv
    public function obtener_rutas_csv($zona) {
        $rutas = [];
        $rutaArchivo = 'recursos/turismo_guada_rutas.csv';
    
        try {
            if (!file_exists($rutaArchivo)) {
                echo "no existe archivo";
                return [];
            }
            
            $archivo = fopen($rutaArchivo, 'a+'); // Abre el archivo en modo lectura/escritura ('a+'), crea el archivo si no existe
            if (!$archivo) {
                throw new Exception("Error al abrir el archivo");
            }
            
            rewind($archivo);
            fgets($archivo); // Mueve el puntero una línea más adelante para saltar cabecera
    
            while (($datos = fgetcsv($archivo, 0, "\t")) !== FALSE) { // Separa los datos por tabulación \t en lugar de comas
                if ($datos[1] == strtoupper($zona)) {
                    // Array asociativo de cada fila
                    $ruta = [
                        'id' => isset($datos[0]) ? trim($datos[0]) : '',
                        'zona' => isset($datos[1]) ? trim($datos[1]) : '',
                        'tipo' => isset($datos[2]) ? trim($datos[2]) : '',
                        'similar' => isset($datos[3]) ? trim($datos[3]) : '',
                        'municipo' => isset($datos[4]) ? trim($datos[4]) : '',
                        'nombre' => isset($datos[5]) ? trim($datos[5]) : '',
                        'web' => isset($datos[6]) ? trim($datos[6]) : ''
                    ];
    
                    $rutas[] = $ruta; // Añadimos rutas
                }
            }
            fclose($archivo);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
        
        return $rutas;
    }
    

    //saca las coordenadas nombre e id de las rutas
    public function coordenadas_rutas(array $info_rutas) : array {
        $rutas_puntos_completo = [];
        $rutaArchivo = 'recursos/turismo_guada_puntos.csv';
    
        try {
            if (!file_exists($rutaArchivo)) {
                echo "no existe archivo";
                return [];
            }
            
            $archivo = fopen($rutaArchivo, 'a+'); // Abre el archivo en modo lectura/escritura ('a+'), crea el archivo si no existe
            if (!$archivo) {
                throw new Exception("Error al abrir el archivo");
            }
            
            rewind($archivo);
            fgets($archivo); // Mueve el puntero una línea más adelante
    
            // Línea por línea
            while (($datos = fgetcsv($archivo, 0, "\t")) !== FALSE) { // Separa los datos por tabulación \t en lugar de comas
                foreach ($info_rutas as $ruta) {
                    if ($ruta['id'] == $datos[0]) { // Verifica que la ruta esté en la lista enviada
                        $ruta_puntos = [ 
                            'id' => isset($datos[0]) ? trim($datos[0]) : '',
                            'tipo' => isset($datos[1]) ? trim($datos[1]) : '',
                            'latitud' => isset($datos[2]) ? trim($datos[2]) : '',
                            'longitud' => isset($datos[3]) ? trim($datos[3]) : '',
                            'nombre_punto' => isset($datos[4]) ? trim($datos[4]) : '',
                            'altura' => isset($datos[5]) ? trim($datos[5]) : '', // Puede estar vacío
                            'nombre_ruta' => isset($ruta['nombre']) ? trim($ruta['nombre']) : '',
                            'web' => isset($ruta['web']) ? trim($ruta['web']) : '',
                            'tiporuta' => isset($ruta['tipo']) ? trim($ruta['tipo']) : ''
                        ];
                        $rutas_puntos_completo[] = $ruta_puntos; // Añadimos rutas
                    }
                }
            }
            fclose($archivo);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
        
        return $rutas_puntos_completo;
    }

    //Refactorizar
    public function coordenadas_ruta_especifica_completa(string $idruta) : array {
        $rutas_puntos_completo = [];
        $rutaArchivo = 'recursos/turismo_guada_puntos.csv';

        try {
            if (!file_exists($rutaArchivo)) {
                echo "no existe archivo";
            }
            
            $archivo = fopen($rutaArchivo, 'a+'); // Abre el archivo en modo lectura/escritura ('a+'), crea el archivo si no existe
            if (!$archivo) {
                throw new Exception("Error al abrir el archivo");
            }
            
            rewind($archivo); // Puntero a inicio
            fgets($archivo); // Mueve el puntero una línea más adelante

            // Línea por línea
            while (($datos = fgetcsv($archivo, 0, "\t")) !== FALSE) { // Separa los datos por tabulación (\t) en lugar de comas
                if ($idruta == $datos[0]) { // Verifica que la ruta coincida con la enviada
                    $ruta_puntos = [ 
                        'id' => isset($datos[0]) ? trim($datos[0]) : '',
                        'tipo' => isset($datos[1]) ? trim($datos[1]) : '',
                        'latitud' => isset($datos[2]) ? trim($datos[2]) : '',
                        'longitud' => isset($datos[3]) ? trim($datos[3]) : '',
                        'nombre_punto' => isset($datos[4]) ? trim($datos[4]) : '',
                        'altura' => isset($datos[5]) ? trim($datos[5]) : '' // Puede estar vacío
                    ];
                    $rutas_puntos_completo[] = $ruta_puntos; // Añadimos rutas
                }
            }
            fclose($archivo);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    
        return $rutas_puntos_completo;
    }


    public function consultar_api_el_tiempo(string $latitud, string $longitud) : array {
        $apiKey = "eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJhbmdlbC5tYXphcmlhcy5zYWxnYWRvQGdtYWlsLmNvbSIsImp0aSI6ImZhNTUzYzk2LTI1M2EtNDE3YS05ZGE4LTk0NWMxNzUyZTJmZiIsImlzcyI6IkFFTUVUIiwiaWF0IjoxNzM4NTc3Mjk1LCJ1c2VySWQiOiJmYTU1M2M5Ni0yNTNhLTQxN2EtOWRhOC05NDVjMTc1MmUyZmYiLCJyb2xlIjoiIn0.0ZP0joCvtR6apjq9gRqhVVCZrQQ8YPMBsBMSVRULobw";
        $urlBase = "https://opendata.aemet.es/opendata/api";
        $estacionCercana = "";
        $datos_tiempo_estacion = [];
    
        try {
            // Obtener la estación más cercana a la latitud y longitud proporcionadas
            $endpointEstaciones = "/valores/climatologicos/inventarioestaciones/todasestaciones";
            $urlEstaciones = "{$urlBase}{$endpointEstaciones}?api_key={$apiKey}";
            
            $estaciones = $this->ejecutarCurl($urlEstaciones);
    
            // if (!$estaciones) {
            //     throw new Exception("Error obteniendo las estaciones.");
            // }
            // print_r($estaciones);
            // Buscar estación más cercana
            $estacionCercana = $this->buscarEstacionMasCercana($estaciones, $latitud, $longitud);
    
            // if (!$estacionCercana) {
            //     throw new Exception("No se encontró una estación cercana.");
            // }
            
            $datos_tiempo_estacion = $this->obtener_datos_estacion($urlBase, $estacionCercana['indicativo'], $apiKey);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        
        return [$datos_tiempo_estacion, $estacionCercana];
    }
    
    //Obtiene los datos del tiempo de una estacion
    private function obtener_datos_estacion(string $url_api_aemet, string $id_estacion, string $api_key) : array {
        $resultado = [];
        try {
            $endpoint_datos_estacion = "/observacion/convencional/datos/estacion/$id_estacion";
            $url = "$url_api_aemet$endpoint_datos_estacion/?api_key=$api_key";
            
            // Solicitud cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $respuesta = curl_exec($curl);
            
            if (curl_errno($curl)) {
                throw new Exception("Error en la solicitud cURL: " . curl_error($curl));
            }
            curl_close($curl);
            $array_respuesta = json_decode($respuesta, true);

            if(isset($array_respuesta["datos"])) {
                // Obtener la URL real de los datos
                $url_datos = $array_respuesta["datos"];
                
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url_datos);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $datos = curl_exec($curl);
                
                if (curl_errno($curl)) {
                    throw new Exception("Error en la solicitud cURL: " . curl_error($curl));
                }
                curl_close($curl);
                
                $datos = mb_convert_encoding($datos, 'UTF-8', 'ISO-8859-1'); //para los municipios con caracteres especiales
                $resultado = json_decode($datos, true);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        
        if($resultado == null) { //SI no se encontrasen datos de la estacion, paso resultado a array
            $resultado = [];
        }
        return $resultado;
    }
    
     //Ejecuta una solicitud cURL y devuelve la respuesta en formato JSON.
    private function ejecutarCurl($url) {
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["cache-control: no-cache"],
        ]);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            echo "Error cURL: $err";
            return null;
        }

        $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');

        $decoded = json_decode($response, true);

        if (!$decoded || json_last_error()) {
            echo "Error en json_decode: " . json_last_error_msg();
            return null;
        }
    
        // Si la respuesta contiene una URL con los datos reales, hacer una segunda petición
        if (isset($decoded["datos"])) {
            return $this->ejecutarCurl($decoded["datos"]);
        }
    
        return $decoded;
    }
    
    /**
     * Encuentra la estación meteorológica más cercana a la latitud y longitud dadas.
     */
    private function buscarEstacionMasCercana($estaciones, $latitud, $longitud) {
        $estacionMasCercana = null;
        $distanciaMinima = PHP_FLOAT_MAX;
    
        // Convertir latitud y longitud de entrada a float
        $latitud = (float)$latitud;
        $longitud = (float)$longitud;
    
        foreach ($estaciones as $estacion) {
            if (isset($estacion['latitud']) && isset($estacion['longitud'])) {
                // Convertir coordenadas de la estación a valores float
                $latitudEstacion = $this->convertirCoordenadas($estacion['latitud']);
                $longitudEstacion = $this->convertirCoordenadas($estacion['longitud']);
    
                if ($latitudEstacion !== null && $longitudEstacion !== null) {
                    // esta formula es 100% GPT: calcula distancia euclidiana entre las coordenadas
                    $distancia = sqrt(pow($latitud - $latitudEstacion, 2) + pow($longitud - $longitudEstacion, 2));
                    if ($distancia < $distanciaMinima) {
                        $distanciaMinima = $distancia;
                        $estacionMasCercana = $estacion;
                    }
                }
            }
        }
        return $estacionMasCercana; //devuelve null o el array con la info de estacion mas cercana.
    }
    
     // Convierte coordenadas del formato "393621N" y "024224E" a decimal.posiblemente deveria ir en controller.
    private function convertirCoordenadas($coordenada) {
        // Extraer grados, minutos y segundos
        preg_match('/(\d{2})(\d{2})(\d{2})([NSEW])/', $coordenada, $matches);
        
        if (!$matches) {
            return PHP_FLOAT_MAX;
        }
    
        $grados = (float)$matches[1];
        $minutos = (float)$matches[2] / 60;
        $segundos = (float)$matches[3] / 3600;
        $direccion = $matches[4];
    
        // Convertir a formato decimal
        $decimal = $grados + $minutos + $segundos;
    
        // Negar el valor para coordenadas S y W
        if ($direccion == 'S' || $direccion == 'W') {
            $decimal *= -1;
        }
    
        return $decimal;
    }
    
    

    
}
?>