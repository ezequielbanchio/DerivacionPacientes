<?php
/* session_start();

// Obtener parámetros
$metodo = isset($_REQUEST["metodo"]) ? $_REQUEST["metodo"] : null;
$usuario = isset($_REQUEST["usuario"]) ? $_REQUEST["usuario"] : null;
$hash = isset($_REQUEST["hash"]) ? $_REQUEST["hash"] : null;

// Incluir clave y métodos
require("./llave.php");
require("./validacion.php");

$key = HASHKEY;
$response = [];
$response["metodo"] = $metodo;

// Validar hash o método "validacion"
if ($hash === md5($usuario . $key) || $metodo === "validacion") {

    if (function_exists($metodo)) {
        $metodo(); // Ejecuta validacion()
        http_response_code(200); // OK
    } else {
        http_response_code(400); // Bad Request
        $response["variables"] = ["error" => "El método no existe"];
        die(json_encode($response));
    }

} else {
    http_response_code(401); // Unauthorized
    $response["variables"] = ["error" => "No tienes acceso"];
    $_SESSION["usuario"] = false;
    $_SESSION["autentica"] = false;
    die(json_encode($response));
}

// Devolver la respuesta final en JSON
echo json_encode($response); */

session_start();
$metodo = $_REQUEST["metodo"];
$usuario = $_REQUEST["usuario"];
$hash = isset($_REQUEST["hash"]) ? $_REQUEST["hash"] : null;


require("./llave.php");
$key = HASHKEY;
$response["metodo"] = $metodo;
if ($hash === md5($usuario . $key) || $metodo === "validacion") {
    if (function_exists($metodo)) {
        $metodo();
    } else {
        $response["variables"] = 'El método no existe';
        die();
    }
} else {
    $response["variables"] = ["error" => "no tenes acceso"];
    $_SESSION["usuario"] = false;
    $_SESSION["autentica"] = false;
}

echo json_encode($response);

function usuarioPerteneceAGrupo($ldapconn, $userDn, $nombreGrupoBuscado) {
    // Normalizar el nombre del grupo buscado (sin espacios y minúsculas)
    $nombreGrupoBuscado = strtolower(trim($nombreGrupoBuscado));
    
    // 1. Obtener todos los grupos del usuario
    $search = ldap_read($ldapconn, $userDn, "(objectclass=*)", ["memberof"]);
    if (!$search) {
        error_log("Error LDAP: " . ldap_error($ldapconn));
        return false;
    }

    $entry = ldap_first_entry($ldapconn, $search);
    if (!$entry) {
        return false;
    }

    $grupos = ldap_get_values($ldapconn, $entry, "memberof");
    if (!$grupos) {
        return false;
    }

    // 2. Verificar cada grupo
    foreach ($grupos as $grupoCompleto) {
        if (empty($grupoCompleto)) continue;
        
        // Extraer solo el CN del DN del grupo
        if (preg_match('/^cn=([^,]+)/i', $grupoCompleto, $matches)) {
            $cnGrupo = strtolower($matches[1]);
            
            // Comparar con el nombre que buscamos
            if ($cnGrupo === $nombreGrupoBuscado) {
                return true;
            }
        }
    }

    // 3. Si necesitas verificar grupos anidados (recursivo)
    foreach ($grupos as $grupoCompleto) {
        if (empty($grupoCompleto)) continue;
        
        if (usuarioPerteneceAGrupo($ldapconn, $grupoCompleto, $nombreGrupoBuscado)) {
            return true;
        }
    }

    return false;
}
function validacion()
{
    $respuesta = [];
    error_reporting(0);
    global $response;
    global $key;
    $content = trim(file_get_contents("php://input"));
    $_arr = json_decode($content, true);
    $usuario = $_REQUEST['usuario'];
    $contraseña = imap_utf8($_arr['contraseña']);
    $_SESSION["autentica"] = false;

    require("config.php");
    $ldappuerto = 389;
    $ldapcontraseña = $contraseña;
    $ldap_grupo_facturacion = "GR_Derivaciones_APP";
    $respuesta['existe'] = false;
    $ldapbasedn = OU . "," . DN;
    $ldaphost = DOMAINHOST;
    $ldaprdn = $usuario . "@" . DOMINIO;

    $ldapconn = ldap_connect($ldaphost, $ldappuerto) or die("Could not connect to $ldapconn");
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    if (ldap_bind($ldapconn, $ldaprdn, $ldapcontraseña) == true) {
        $respuesta['existe'] = true;
        $filtro = "(samAccountName=$usuario)";
        $attr = ["cn", "givenname", "sn", "dn"];
        $resultado = ldap_search($ldapconn, $ldapbasedn, $filtro, $attr) or exit("Error en búsqueda LDAP");
        $entradas = ldap_get_entries($ldapconn, $resultado);
        if ($entradas["count"] > 0) {
            $userDn = $entradas[0]["dn"];
            if (usuarioPerteneceAGrupo($ldapconn, $userDn, $ldap_grupo_facturacion)) {
                $_SESSION["usuario"] = $usuario;
                $respuesta['user'] = $usuario;
                $_SESSION["autentica"] = true;
                $respuesta['hash'] = md5($usuario . $key);
            }
        }
        ldap_unbind($ldapconn);
    }
    $respuesta['validacion'] = $_SESSION["autentica"];
    $response["variables"] = $respuesta;
}

function cerrarSesion() {
    session_start();
    $_SESSION = array();
    session_destroy();
    echo json_encode(["success" => true]);
}

