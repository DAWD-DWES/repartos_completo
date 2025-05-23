<?php

namespace App;

class ServicioMap {

    private float $lat;
    private float $lon;
    private string $map_api_key;
    
    
    public function __construct(string $map_api_key) {
        $this->map_api_key = $map_api_key;
    }

    /**
     * Obtiene las coordenadas (latitud y longitud) a partir de una dirección
     * 
     * @param string $dir Dirección para buscar las coordenadas
     * 
     * @returns array con la latitud y longitud de la dirección
     */
    
    
    
    public function getCoordenadas(string $dir, string $pais, string $ciudad, string $localidad): array {
        $mapApiUrl = "http://dev.virtualearth.net/REST/v1/Locations/" . $pais . "/" . $ciudad . "/" . $localidad . "/" . $dir . "?include=ciso2&maxResults=1&c=es&strictMatch=1&key=" . $this->map_api_key;
        $dirUrl = str_replace(" ", "%20", $mapApiUrl);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $dirUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta = curl_exec($ch);
        curl_close($ch);
        $datos = json_decode($respuesta, true);
        $coordenadas['lat'] = $datos["resourceSets"][0]["resources"][0]["point"]["coordinates"][0];
        $coordenadas['lon'] = $datos["resourceSets"][0]["resources"][0]["point"]["coordinates"][1];
        return $coordenadas;
    }

    /**
     * Obtiene la altitud a partir de las coordenadas de latitud y longitud
     * 
     * @param float $lat Latitud
     * @param float $lon Longitud
     * 
     * @returns float Altitud a la que se encuentra el punto
     */
    public function getElevation(float $lat, float $lon): float {
        $mapApiUrl = "http://dev.virtualearth.net/REST/v1/Elevation/List?points=$lat,$lon&key=" . $this->map_api_key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $mapApiUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta = curl_exec($ch);
        curl_close($ch);
        $datos = json_decode($respuesta, true);
        $alt = $datos["resourceSets"][0]["resources"][0]["elevations"][0];
        return $alt;
    }

    /**
     * Obtiene la ruta óptima para realizar los repartos
     * 
     * @param string $dato Parejas de coordenadas de los destinos del reparto separados por |
     * 
     * @returns array con el orden de entrega a seguir en el reparto
     */
    public function ordenarRuta(string $dato): array {
        $mapApiUrl = "http://dev.virtualearth.net/REST/v1/Routes/Driving?c=es";
        $puntos = explode("|", $dato);
        $trozo = '&waypoint.0=' . $puntos[0] . "&";
        for ($i = 1; $i < count($puntos); $i++) {
            $trozo .= "waypoint." . $i . "=" . $puntos[$i] . "&";
        }
        $trozo .= "optimize=distance&optWp=true&routeAttributes=routePath&key=" . $this->map_api_key;
        $mapApiUrl .= $trozo;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $mapApiUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta = curl_exec($ch);
        curl_close($ch);
        $datos = json_decode($respuesta, true);
        $ruta = $datos["resourceSets"][0]["resources"][0]['waypointsOrder'];
        array_shift($ruta);
        array_pop($ruta);
        for ($i = 0; $i < count($ruta); $i++) {
            $resp[] = substr(strstr($ruta[$i], '.'), 1);
        }
        return $resp;
    }

}
