<?php

/*
 * Controlador de la aplicación Repartos
 */

require '../vendor/autoload.php';

// Uso BladeOne como motor de vistas
use eftec\bladeone\BladeOne;
// Uso Dotenv para leer variables de entorno
// Las variables de entorno se definen en el fichero .env
use Dotenv\Dotenv;
use App\BD\BD;
use App\Modelo\{
    Producto,
    ListaReparto,
    Reparto
};
use App\DAO\{
    ProductoDao,
    ListaRepartoDao,
    RepartoDao
};
use App\ServicioMap;

session_start();

$views = __DIR__ . '/../views';
$cache = __DIR__ . '/../cache';
$blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

// Se configura el cliente OAuth de Google

$ficheroCredenciales = "../{$_ENV['OAUTH2_CREDENTIALS']}";

$redirect_uri = 'http://localhost:8000';
$client = new Google\Client();
$client->setApplicationName('Reparting');
$client->setAuthConfig($ficheroCredenciales);
$client->setRedirectUri($redirect_uri);
$client->setScopes([Google\Service\Tasks::TASKS]); //TASKS
$client->setAccessType('offline'); // Allows us to request a refresh token
$client->setPrompt('select_account consent');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['token'] = $token;
    $_SESSION['refresh_token'] = $token['refresh_token'];
}

if (isset($_SESSION['token'])) { // Si tengo el token de acceso guardado en la sesión
    $client->setAccessToken($_SESSION['token']);
    if ($client->isAccessTokenExpired()) { // Si el token de acceso ha expirado
        if ($client->fetchAccessTokenWithRefreshToken($_SESSION['refresh_token'])) {
            $_SESSION['token'] = $client->getAccessToken();
        } else {
// Request authorization from the user.
            unset($_SESSION['token']);
            unset($_SESSION['refresh_token']);
            $authUrl = $client->createAuthUrl();
            header("Location:$authUrl");
            die;
        }
    }
} else {
    $authUrl = $client->createAuthUrl();
    header("Location:$authUrl");
    die;
}

//si el token de acceso es válido
$servicio = new Google_Service_Tasks($client); // Creo el servicio de cliente con Google Tasks API
try {
    $host = $_ENV['DB_HOST'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $bd = BD::getConexion($host, $database, $username, $password);
} catch (PDOException $ex) {
    echo $blade->run("cnxbderror");
    die;
}
$repartoDao = new RepartoDao($servicio);
$listaRepartoDao = new ListaRepartoDao($servicio);  //si el token de acceso es válido
$productoDao = new ProductoDao($bd);
if (filter_has_var(INPUT_POST, 'valida-nombre-lista-reparto')) {
    $fechaListaRepartoInput = filter_input(INPUT_POST, 'fecha', FILTER_UNSAFE_RAW);
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaListaRepartoInput);
    $nombreListaReparto = "Repartos " . $fecha->format('d/m/Y');
    $listasRepartoExistentes = $listaRepartoDao->recuperaTodo();
    $errNombreListaReparto = in_array($nombreListaReparto, array_map(function ($listaReparto) {
                return $listaReparto->getNombre();
            }, $listasRepartoExistentes));
    header('Content-type: application/json');
    echo json_encode(compact('errNombreListaReparto'));
    die;
} elseif (filter_has_var(INPUT_GET, 'nueva-lista-repartos')) { // Si se solicita la creación de una nueva lista de repartos
    $fechaListaRepartoInput = filter_input(INPUT_GET, 'fecha', FILTER_UNSAFE_RAW);
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaListaRepartoInput);
    $nombreListaReparto = "Repartos " . $fecha->format('d/m/Y');
    $listaReparto = new ListaReparto($nombreListaReparto);
    $listaRepartoDao->crea($listaReparto);
    $listasReparto = $listaRepartoDao->recuperaTodo();
    echo $blade->run("repartos", compact('listasReparto'));
    die;
} elseif (filter_has_var(INPUT_POST, 'borra-lista-reparto')) { // Si se solicita que se borre una lista de reparto
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $listaRepartoDao->elimina($listaRepartoId);
    $listasReparto = $listaRepartoDao->recuperaTodo();
    echo $blade->run("repartos", compact('listasReparto'));
    die;
} elseif (filter_has_var(INPUT_POST, 'pet-nuevo-reparto')) { // Si se solicita el formulario para crear un reparto
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $productos = $productoDao->recuperaTodo();
    echo $blade->run("form-reparto", compact('listaRepartoId', 'productos'));
    die;
} elseif (filter_has_var(INPUT_POST, 'nuevo-reparto')) { // Si se solicita que se usen los datos del formulario para crear un reparto
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_UNSAFE_RAW);
    $producto = filter_input(INPUT_POST, 'producto', FILTER_UNSAFE_RAW);
    $lat = filter_input(INPUT_POST, 'lat', FILTER_UNSAFE_RAW);
    $lon = filter_input(INPUT_POST, 'lon', FILTER_UNSAFE_RAW);
    $reparto = new Reparto($direccion, $producto, $lat, $lon);
    $reparto->setListaRepartoId($listaRepartoId);
    $repartoDao->crea($reparto);
    $listasReparto = $listaRepartoDao->recuperaTodo();
    echo $blade->run("repartos", compact('listasReparto'));
    die;
} elseif (filter_has_var(INPUT_POST, 'borra-reparto')) { // Si se solicita que se borre un reparto
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $repartoId = filter_input(INPUT_POST, 'reparto-id', FILTER_UNSAFE_RAW);
    $repartoDao->elimina($listaRepartoId, $repartoId);
    $listasReparto = $listaRepartoDao->recuperaTodo();
    echo $blade->run("repartos", compact('listasReparto'));
    die;
} if (filter_has_var(INPUT_POST, 'mapa-reparto')) {
    $lat = filter_input(INPUT_POST, 'lat', FILTER_UNSAFE_RAW);
    $lon = filter_input(INPUT_POST, 'lon', FILTER_UNSAFE_RAW);
    echo $blade->run("mapa", compact('lat', 'lon'));
    die;
} elseif (filter_has_var(INPUT_POST, 'mapa-ruta-reparto')) {
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $listaReparto = $listaRepartoDao->recuperaPorId($listaRepartoId);
    $wp = "{$_ENV['LAT_BASE']},{$_ENV['LON_BASE']}";
    foreach ($listaReparto->getRepartos() as $reparto) {
        $wp .= "|{$reparto->getLat()},{$reparto->getLon()}";
    }
    $wp .= "|{$_ENV['LAT_BASE']},{$_ENV['LON_BASE']}";
    echo $blade->run("ruta", compact('wp'));
    die;
} elseif (filter_has_var(INPUT_POST, 'ver-coordenadas')) { // Si se solicita que se envíen las coordenadas de una dirección
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_UNSAFE_RAW);
    $servicioMap = new ServicioMap($_ENV['MAP_API_KEY']);
    $coordenadas = $servicioMap->getCoordenadas($direccion, $_ENV['PAIS'], $_ENV['CIUDAD'], $_ENV['LOCALIDAD']);
    $coordenadas['alt'] = $servicioMap->getElevation($coordenadas['lat'], $coordenadas['lon']);
    header('Content-type: application/json');
    echo json_encode($coordenadas);
    die;
} elseif (filter_has_var(INPUT_POST, 'ordenar-envios')) { // Si se solicita que se ordene la ruta de los repartos
    $listaRepartoId = filter_input(INPUT_POST, 'lista-reparto-id', FILTER_UNSAFE_RAW);
    $listaReparto = $listaRepartoDao->recuperaPorId($listaRepartoId);
    $servicioMap = new ServicioMap($_ENV['MAP_API_KEY']);
    $ordenRepartos = $listaReparto->ordena($servicioMap, $_ENV['LAT_BASE'], $_ENV['LON_BASE']);
    $listaRepartoDao->modifica($listaReparto);
    header('Content-type: application/json');
    echo json_encode(compact('listaRepartoId', 'ordenRepartos'));
    die;
} else { // En otro caso muestra el listado de las listas de reparto
    $listasReparto = $listaRepartoDao->recuperaTodo();
    echo $blade->run("repartos", compact('listasReparto'));
    die;
}
    