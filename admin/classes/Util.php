<?php
class Util
{

  public const SECRETARIO_DESPACHO = "Secretario_Despacho";
  public const ALCALDE = "Alcalde";
  public const AUXILIAR = "Auxiliar";
  public const AUXILIAR_ALCALDE = "Auxiliar_Alcalde";
  public const ADMINISTRADOR = "Administrador";

  // Botón "Hablar con [otra área]" en los chats de IA (api/chat_asistente.php).
  // Aun activo, nunca se muestra en riesgo nivel 3-4.
  public const MOSTRAR_BOTON_DERIVACION = false;

  // Para enviar la peticion de la DIAN
  public $SYNC_API = true;

  public $THE_KEY = '8da48890b622b4d9a340d2a5c261b1b1';
  /**
   * Valor de un KB = 1024 bytes
   * @var int
   */
  public $KB_BYTE = 1024;

  /**
   * Valor de un MB = 1024 KB
   * @var int
   */
  public $MB_BYTE = 1048576;

  /**
   * Url de la raiz de la aplicación
   * @var string
   */
  public static function URL_ROOT_HOST()
  {
    $URL_ROOT_HOST = "";
    return $URL_ROOT_HOST;
  }

  public function __construct()
  {
    //contructor que no tiene ninguna funcion, por ahora
  }

  public static function getUrl()
  {
    $port = $_SERVER["SERVER_PORT"];
    $nameServer = $port != "80" ? $_SERVER['SERVER_NAME'] . ":" . $port : $_SERVER['SERVER_NAME'];
    $url = sprintf(
      "%s://%s%s",
      isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
      $nameServer,
      $_SERVER['REQUEST_URI']
    );
    return str_replace(basename($_SERVER["SCRIPT_FILENAME"], '.php') . ".php", "", $url);
  }

  // Todo esto relacionado al Departamento SANTANDER
  public static function getSemanaActual(): ?int
  {
    try {
      $inicio = new DateTimeImmutable('2024-01-07');
      $fin = new DateTimeImmutable('2027-12-31');
      $hoy = new DateTimeImmutable();

      if ($hoy < $inicio || $hoy > $fin) {
        return null; // Fuera del rango especificado
      }

      return intdiv($inicio->diff($hoy)->days, 7) + 1;
    } catch (Exception $e) {
      error_log("Error en getSemanaActual: " . $e->getMessage());
      return null;
    }
  }
  public static function getColorNeutroMapa()
  {
    return "#f7f3f2";
  }
  public static function codigoTodos()
  {
    return 10000;
  }
  public static function getDepartamentoPrincipal()
  {
    return "68";
  }
  public static function getOpcionPrincipalTIC()
  {
    return "robotica";
  }
  public static function getSecretariaIdTIC()
  {
    return 10;
  }
  public static function getSecretariaIdHacienda()
  {
    return 4;
  }
  public static function getCodigoMunicipioPrincipal()
  {
    return "68001";
  }
  public static function getCodigoVeredadMunicipioPrincipal()
  {
    return "9581";
  }
  public static function getIdentificadorPilarPrincipal()
  {
    return "1";
  }
  public static function getAnioActual()
  {
    return date('Y');
  }
  public static function getIdentificadorDepartamentoPrincipal()
  {
    return "21";
  }
  public static function getSecretariaPrincipal()
  {
    return "2";
  }
  public static function getSecretariaAdministrativa()
  {
    return "3";
  }

  public static function Secretario_Despacho()
  {
    return "Secretario_Despacho";
  }
  public static function Alcalde()
  {
    return "Alcalde";
  }
  public static function Auxiliar()
  {
    return "Auxiliar";
  }
  public static function Auxiliar_Alcalde()
  {
    return "Auxiliar_Alcalde";
  }
  public static function Administrador()
  {
    return "Administrador";
  }
  public static function SuperAdministrador()
  {
    return "SuperAdministrador";
  }

  public static function get_app_id()
  {
    return '8da48890b622b4d9a340d2a5c261b1b1';
  }

  public static function sb_db_get($query, $single = true)
  {
    $db     = new DbConection();
    $pdo    = $db->openConect();
    $value  = ($single ? '' : []);

    $sql    = $pdo->prepare($query);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($result)) {
      foreach ($result as $key => $row) {
        if ($single) {
          $value = $row;
        } else {
          array_push($value, $row);
        }
      }
    } else {
      return Util::error_no_result();;
    }
    $db->closeConect();
    return $value;
  }
  public static function getInformacionConfiguracion()
  {
    $db = new DbConection();
    $q = "SELECT * FROM " . $db->getTable('tbl_configuracion') . " ORDER BY id DESC LIMIT 1";
    return Util::sb_db_get($q, false);
  }

  /**
   * Método para capturar la Ip del cliente
   * @return string Ip del cliente
   */
  public static function get_real_ipaddress()
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP']; //check ip from share internet
    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; //to check ip is pass from proxy
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  /**
   * Método para hacer POST desde PHP
   * @param string $url
   * @param array $data
   * @param string $referer
   * @return array ['status', 'header', 'content']
   */
  public static function post_request($url, $data, $referer = '')
  {
    // Convert the data array into URL Parameters like a=b&foo=bar etc.
    $data = http_build_query($data);
    // parse the given URL
    $url = parse_url($url);
    if ($url['scheme'] != 'http') {
      die('Error: Only HTTP request are supported !');
    }
    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];
    //		echo '<br/>';
    //		echo '<br/>'.$host;
    //		echo '<br/>'.$path;
    //		echo '<br/>';
    if (function_exists('fsockopen')) {
      //echo 'open a socket connection on port 80 - timeout: 30 sec';
      $fp = fsockopen($host, 80, $errno, $errstr, 30);
      if ($fp) {
        // send the request headers:
        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");

        if ($referer != '') {
          fputs($fp, "Referer: $referer\r\n");
        }

        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: " . strlen($data) . "\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        $result = '';
        while (!feof($fp)) {
          // receive the results of the request
          $result .= fgets($fp, 128);
        }
      } else {
        return array(
          'status' => 'err',
          'error' => '$errstr ($errno)'
        );
      }
    } else {
      echo "No fsockopen, please config php.ini <br />\n";
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);

    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as structured array:
    return array(
      'status' => 'ok',
      'header' => $header,
      'content' => $content
    );
  }

  /**
   * Metodo que implementa la funcion <i>post_request</i> cuando se utiliza de la siguiente manera:
   * <code>
      $post_data = array('param1' => 'some1','param2' => $some2 );
      $result = $util->post_request($util->URL_BLAST24WS, $post_data);
      if ($result['status'] == 'ok'){
      ___$json_decoded = json_decode($result['content']);
      ___if ($json_decoded->output->valid) {
      ______$_SESSION['okSessionVarName'] = $json_decoded->output->response;
      ______$_SESSION['location'] = 'okLocation';
      ___} else {
      ______$_SESSION['json_error'] = $json_decoded->output->response;
      ______$_SESSION['location'] = 'errorLocation';
      ______}
      ___} else {echo 'A error occured: ' . $result['error']; }
      </code>
   * @param array $post_data datos para hacer post
   * @param string $okLocation ubicacion a setear en $_SESSION['location'] si la peticion es la esperada
   * @param string $errorLocation ubicacion a setear en $_SESSION['location'] si la peticion devuelve error
   * @param string $okSessionVarName nombre de la variable de sesion que se inicia
   * @return array resultado del post
   */
  public function post_request_common($post_data, $okLocation = "", $errorLocation = "", $okSessionVarName = "")
  {
    $result = $this->post_request($this->URL_BLAST24WS, $post_data);
    if ($result['status'] == 'ok') {
      $json_decoded = json_decode($result['content']);
      if ($json_decoded->output->valid) {
        if ($okLocation != "") {
          $_SESSION['location'] = $okLocation;
        }
        if ($okSessionVarName != "") {
          $_SESSION[$okSessionVarName] = $json_decoded->output->response;
        }
      } else {
        if ($errorLocation != "") {
          $_SESSION['location'] = $errorLocation;
        }
        $_SESSION['json_error'] = $json_decoded->output->response;
      }
    } else {
      echo 'A error occured: ' . $result['error'];
    }
    return $result;
  }

  /**
   * Mètodo para eliminar caracteres especiales que puedan modificar las consultas SQL.
   * Una función para evitar SQL Injection.
   * @param string $str
   * @return string Cadena de carateres segura
   */
  public static function remove_special_char($str)
  {
    if ($str == null || count($str) <= 0) {
      return $str;
    }
    $realstr = str_replace("'", "", $str);
    $realstr = str_replace("&", "", $realstr);
    //$realstr = str_replace("\n","",$realstr);
    //$realstr = str_replace("\r","",$realstr);
    $realstr = str_replace("<", "", $realstr);
    $realstr = str_replace(">", "", $realstr);
    $realstr = str_replace("\"", "", $realstr);
    $realstr = str_replace("drop", "", $realstr);
    $realstr = str_replace("DROP", "", $realstr);
    $realstr = str_replace("delete", "", $realstr);
    $realstr = str_replace("DELETE", "", $realstr);
    // ESTOS SE INHABILITAN PARA PODER ALMACENAR DIRECCIONES EN LA BASE DE DATOS
    // $realstr = str_replace("/","",$realstr);
    // $realstr = str_replace("/\/","",$realstr);
    //$realstr = str_replace("|","",$realstr);
    return $realstr;
  }

  public static function remove_weird_char($str)
  {
    if ($str == null || count($str) <= 0) {
      return $str;
    }
    $realstr = str_replace("Ã¡", "a", $str);
    $realstr = str_replace("Ã©", "e", $realstr);
    $realstr = str_replace("Ã­", "i", $realstr);
    $realstr = str_replace("Ã³", "o", $realstr);
    $realstr = str_replace("Ãº", "u", $realstr);
    return $realstr;
  }

  public static function convert_special_char($str)
  {
    if ($str == null || count($str) <= 0) {
      return $str;
    }
    $realstr = htmlspecialchars($str, ENT_QUOTES);
    return $realstr;
  }

  public static function convert_pathtourl($str)
  {
    if ($str == null || count($str) <= 0) {
      return $str;
    }
    $realstr = str_replace(DIRECTORY_SEPARATOR, "/", $str);
    return $realstr;
  }

  public static function remove_repeatslash($str)
  {
    if ($str == null || count($str) <= 0) {
      return $str;
    }
    $realstr = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $str);
    for ($i = 0; $i < 2; $i++) {
      $realstr = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $realstr);
    }
    return $realstr;
  }

  /**
   * Metodo para generar el hash de un password en una serie de encriptaciones del Blast24
   * @param string $type
   * @param string $data1
   * @param string $data2
   * @return string hash del password
   */
  public static function create_passhash($type = "", $data1 = "", $data2 = "")
  {
    if ($type == 'send') {
      $hash = sha1($data1 . $data2);
      //		echo '<br/>send '.$data1.' - '.$data2;
    } else if ($type == 'receive') {
      $hash = strtoupper(sha1($data1 . "unabobada"));
      //		echo '<br/>receive '.$data1;
    } else if ($type == 'store') {
      $hash = strtoupper(sha1(sha1($data1 . $data2) . "unabobada"));
      //echo '<br/>store '.$data1.' - '.$data2;
    }
    return $hash;
  }

  /**
   * Metodo para escribir sobre archivos.
   * @param string $data El dato a escribir en el archivo.
   * @param string $pathFile La ubicacion fisica del archivo.
   * @param int $isNew 0 es para escribir sobre un archivo existente. 1 para crear uno nuevo.
   */
  public static function make_file($data, $pathFile, $isNew = 0)
  {
    $filesize = 0;
    if (file_exists($pathFile)) {
      if ($isNew) {
        unlink($pathFile);
      }
      $filesize = filesize($pathFile); //bytes
    }
    //$maxSize = 1 * 1024;//KB
    $maxSize = 1 * 1048576; //MB
    if ($filesize > $maxSize) {
      rename($pathFile, $pathFile . date("YmdHis"));
    }
    $fh = fopen($pathFile, 'a+') or die("Can't use file.<BR/>Need to apply read-write permissions.<BR/>$ sudo chmod 777 /var/www/s24/blast24/web/log/debug_file.txt or " . $pathFile);
    $arrStr = explode(";", $data);
    foreach ($arrStr as $str) {
      $str = date("Y-m-d H:i:s") . " # " . $str . "\n";
      fwrite($fh, $str);
    }
    fclose($fh);
  }

  /**
   * Metodo para escribir sobre archivos.
   * @param string $str El dato a escribir en el archivo.
   * @param int $isNew 0 es para escribir sobre un archivo existente. 1 para crear uno nuevo.
   * @param string $pathFile La ubicacion fisica del archivo.
   */
  public static function make_debug_file($str, $file, $line, $isNew = 0, $pathFile = "log/debug_file.txt")
  {
    $filesize = 0;
    if (file_exists($pathFile)) {
      if ($isNew) {
        unlink($pathFile);
      }
      $filesize = filesize($pathFile); //bytes
    }
    //$maxSize = 1 * 1024;//KB
    $maxSize = 1 * 1048576; //MB
    if ($filesize > $maxSize) {
      rename($pathFile, $pathFile . date("YmdHis"));
    }
    $fh = fopen($pathFile, 'a+') or die("Can't use file.<BR/>Need to apply read-write permissions.<BR/>$ sudo chmod 777 /var/www/s24/blast24/web/log/debug_file.txt or " . $pathFile);
    //	    $str = date("Y-m-d H:i:s")." # ".__FILE__." Linea: ".__LINE__."\n".$str."\n";
    //$str = date("H:i:s.m")." # ".__FILE__." Linea: ".__LINE__."\n--->".$str."\n\n";
    $str = date("H:i:s.m") . " # Linea: " . $line . " # " . $file . "\n--->" . $str . "\n\n";
    fwrite($fh, $str);
    fclose($fh);
  }

  public static function session_chainstring($nameSessionVar, $str)
  {
    $_SESSION[$nameSessionVar] .= $str . '*';
  }


  /**
   * Metodo para construir un UPDATE.
   * @param string $table nombre de la tabla a escribir
   * @param string $where condicion para actualizar
   * @param array $arrfieldscomma campos y valores tipo STRING, que requieren comma
   * @param array $arrfieldsnocomma campos y valores que no requieren comma
   * @return string consulta construida
      <code>
      include 'classes/Util.php';
      $table = "mi_tabla";
      $where = "(id = 0) and (tipo='cadena')";
      $arrfieldscomma = array('campo1' => 'valor1', 'campo2' => 'valor2', 'campo3' => 'valor3');
      $arrfieldsnocomma = array('campoA' => 'NOW()', 'campoB' => '2', 'campoC' => 'GET');
      echo Util::make_query_insert($table, $arrfieldscomma, $arrfieldsnocomma);
      </code>
   *
   */
  public static function make_query_update($table, $where, $arrfieldscomma, $arrfieldsnocomma)
  {
    $query = "UPDATE ";
    if ($table == null || strlen($table) < 1) {
      return "***Falta nombre de la tabla***";
    }
    if ($where == null || strlen($where) < 1) {
      return "***Falta WHERE id=?? del registro***";
    }
    $query .= $table . " SET ";
    $fields = "";
    foreach ($arrfieldscomma as $f => $v) {
      if (strlen($v) >= 1) {
        $fields .= " " . $f . " = '" . $v . "',";
      }
    }
    foreach ($arrfieldsnocomma as $f2 => $v2) {
      if ($v2 > 0) {
        $fields .= " " . $f2 . " = " . $v2 . ",";
      }
    }
    $fields = rtrim($fields, ",");
    $query .= $fields . " WHERE " . $where;
    return $query;
  }

  /**
   * Metodo para encriptar password
   */
  public static function make_hash_pass($pass)
  {
    $r = strtoupper(md5(md5($pass) . sha1($pass) . md5(sha1('---Mede--2020**llin'))));
    return $r;
  }

  public static function validate_key($key1, $random = '', $param = '')
  {
    $key2 = '';
    $response = false;
    if (strlen($param) > 0) {
      $key2 = sha1($param . $this->THE_KEY . $random);
      if ($key1 == $key2) {
        $response = true;
      }
    } else {
      $key2 = sha1($this->THE_KEY . $random);
      if ($key1 == $key2) {
        $response = true;
      }
    }
    return $response;
  }

  /** Checks is the provided email address is formally valid
   *  @param string $email email address to be checked
   *  @return true if the email is valid, false otherwise
   */
  public static function validate_email($email)
  {
    $regexp = "/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
    if (preg_match($regexp, $email)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Metodo para generar un codigo aleatorio
   * @param int longitud del codigo
   * @return string Codigo generado
   */
  // public static function generate_code($l) {
  //     $key = '';
  //     $pat = '1234567890abcdefghijklmnopqrstuvwxyz';
  //     $max = strlen($pat) - 1;
  //     for ($i = 0; $i < $l; $i++) {
  //         $key .= $pat{mt_rand(0, $max)};
  //     }
  //     return $key;
  // }

  public static function rounding($numero, $decimales)
  {
    $factor = pow(10, $decimales);
    return (round($numero * $factor) / $factor);
  }

  public static function error_invalid_method_called()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '101', 'content' => ' ❌ Metodo no existe.')));
  }

  public static function error_invalid_authorization()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '102', 'content' => ' No se encuentra autorizado para ejecutar la operación.')));
  }

  public static function error_missing_data()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '103', 'content' => ' ❌ Faltan datos que son requeridos.')));
  }

  public static function error_missing_data_description($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '103', 'content' => ' ❌ Faltan datos que son requeridos. ' . $description)));
  }

  public static function error_general($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '000', 'content' => ' ❌ Ha ocurrido un error. ' . $description)));
  }

  public static function error_general2()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '0000', 'content' => ' ❌ Ha ocurrido un error 2. ')));
  }

  public static function info_general($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '0000', 'content' => ' Importante a tener en cuenta. ' . $description)));
  }

  public static function error_no_result()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '104', 'content' => ' ❌ Sin resultados.')));
  }

  public static function error_no_credits()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '105', 'content' => ' ❌ Creditos insuficientes.')));
  }

  public static function error_user_already_exist()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '106', 'content' => ' ❌ El correo ingresado ya lo utiliza otro usuario.')));
  }

  public static function error_wrong_data_login()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '107', 'content' => ' ❌ Usuario o Contraseña Incorrectos.')));
  }

  public static function error_wrong_email()
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '108', 'content' => ' ❌ Email incorrecto.')));
  }

  public static function error_sending_email($content = NULL)
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '109', 'content' => $content)));
  }

  public static function error_subirarchivo($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '112', 'content' => ' ❌ Ha ocurrido un error al subir el archivo. ' . $description)));
  }

  public static function error_registroduplicado($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '116', 'content' => ' ❌ La información a ingresar ya fue registrada anteriormente. ' . $description)));
  }

  public static function error_generaldelete($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '117', 'content' => ' ❌ No se puede eliminar el item seleccionado, ya que ha sido relacionado con otros registros previamente. ' . $description)));
  }

  public static function error_subirarchivoTam($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '118', 'content' => ' ❌ El peso del archivo exece al limite especifico. ' . $description)));
  }

  public static function error_existencia($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '119', 'content' => 'Lo sentimos, no contamos en el inventario con la cantidad que requieres. ' . $description)));
  }

  public static function cart_empty($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '120', 'content' => ' ❌ El carrito de compra está vacío.' . $description)));
  }

  public static function error_telefonoduplicado($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '121', 'content' => ' ❌ El número de teléfono ingresado ya existe en el sistema ' . $description)));
  }

  public static function error_documentoduplicado($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '122', 'content' => ' ❌ El número de documento ingresado ya existe en el sistema ' . $description)));
  }

  public static function error_estadooduplicado($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '123', 'content' => ' ❌ El estado que desea registrar ya ha sido guardado previamente, favor selecionar otro estado ' . $description)));
  }
  public static function error_prodcantidadnovalidad($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '124', 'content' => ' ❌ La cantidad y/o producto  no fue solicitado por el cliente. ' . $description)));
  }

  public static function error_documentonoexiste($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '125', 'content' => ' ❌ El número ingresado no existe por favor verifique ' . $description)));
  }

  public static function error_session_finished($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '126', 'content' => ' ❌ La sessión ha caducado. ' . $description)));
  }

  public static function error_cantidad_no_valida($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '127', 'content' => $description)));
  }

  public static function error_general_dian($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '999', 'content' => ' Ha ocurrido un error con la factura. ' . $description)));
  }

  public static function error_general_dian_campos_erroneos($description = '')
  {
    return array('output' => array('valid' => false, 'response' => array('code' => '1000', 'content' => ' Validación contiene errores en campos mandatorios. ' . $description)));
  }


  public static function date_now_server()
  {
    return 'DATE_ADD(NOW(),INTERVAL 0 HOUR)';
  }

  public static function date()
  {
    return date('Y-m-d H:i:m', time());
  }

  public static function getDateCurrently()
  {
    return date('Y-m-d');
  }


    /**
     * Método para versionar archivos estáticos (CSS, JS, imágenes) agregando un timestamp basado en la fecha de modificación del archivo.
     * @param string $path Ruta relativa del archivo desde la raíz del servidor (ej. '/assets/css/style.css').
     * @return string Ruta con el parámetro de versión (ej. '/assets/css/style.css?v=1697043200').
     */
    public static function versionar($path) {
        // 1. Definimos la ruta física en el servidor
        // $_SERVER['DOCUMENT_ROOT'] nos da la carpeta base (ej. /var/www/html)
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');

        // 2. Verificamos si el archivo existe físicamente
        if (file_exists($fullPath)) {
            $v = filemtime($fullPath);
        } else {
            // Si no existe (error de dedo o ruta mal), usamos el tiempo actual 
            // para que al menos no rompa el diseño y se note el error en consola
            $v = time(); 
        }

        // 3. Retornamos la ruta con el timestamp
        return $path . "?v=" . $v;
    }
    
  public static function verify_user_app_access()
  {
    //se valida que pueda utilizar este tipo de acceso
    //para una futura conversion en un API solo se nececida validar daots de usuario
    if (!isset($_SESSION['session_user'])) {
      echo 'OPERACION NO DISPONIBLE. CODIGO 001. USUARIO SIN PRIVILEGIOS';
      die();
    }
    //se pregunta si tiene acceso a esta aplicacion
    if (isset($_SESSION['session_user']['application'])) {
      if (!(in_array(Util::get_app_id(), $_SESSION['session_user']['application']))) {
        echo 'OPERACION NO DISPONIBLE. CODIGO 002. APLICACION NO ASIGNADA';
        die();
      }
    } else {
      echo 'OPERACION NO DISPONIBLE. CODIGO 003. APLICACION NO AUTORIZADA';
      die();
    }
  }
  public static function verify_user_session()
  {
    return isset($_SESSION['session_user']) ? TRUE : FALSE;
  }


  /**
   * Funcion para el ingreso de la foto o archivo
   * @param type $id
   * @param type $field
   * @param type $table
   * @return type
   */
  public static function upload($id, $field, $table)
  {
    //Informacion del Archivo.
    $file_name = isset($_SESSION['pms_archivo']['nombrearchivo']) ? ($_SESSION['pms_archivo']['nombrearchivo']) : '';
    $file_type = isset($_SESSION['pms_archivo']['tipoarchivo']) ? ($_SESSION['pms_archivo']['tipoarchivo']) : '';
    $contenido = isset($_SESSION['pms_archivo']['contenidooarchivo']) ? ($_SESSION['pms_archivo']['contenidooarchivo']) : '';
    $file_size = isset($_SESSION['pms_archivo']['tamanio']) ? ($_SESSION['pms_archivo']['tamanio']) : '';
    $file_error = isset($_SESSION['pms_archivo']['error']) ? ($_SESSION['pms_archivo']['error']) : '';

    $peso = 1000000; //1MB

    $db = new DbConection();
    $pdo = $db->openConect();

    //Verifico si tiene foto asociada
    $q_1 = "SELECT COUNT(*)  FROM " . $db->getTable($table) . " WHERE $field = " . $id;
    $result_1 = $pdo->query($q_1);
    $c = $result_1->fetchColumn();
    if ($c == 0) {
      if ($file_name != '') {
        //Si va a actualizar y no tiene foto asociada y selecciona una imagen se procede a ingresar el registro
        if ($file_error == 0 && $file_size > 0 && $file_size < $peso) {
          $q1 = "INSERT INTO " . $db->getTable($table) . " ($field, images_nombre,images_tipo,images_contenido) VALUES ('" . $id . "', '" . $file_name . "', '" . $file_type . "', '" . $contenido . "')";
          $result = $pdo->query($q1);
          if ($result) {
            $_SESSION['pms_archivo']["nombrearchivo"] = "";
            $_SESSION['pms_archivo']["tipoarchivo"] = "";
            $_SESSION['pms_archivo']["contenidooarchivo"] = "";
            $_SESSION['pms_archivo']["tamanio"] = "";
            $_SESSION['pms_archivo']["error"] = "";
            $arrjson = array('output' => array('valid' => true, 'response' => $pdo->lastInsertId()));
          } else {
            $_SESSION['session_user'] = NULL;
            $arrjson = Util::error_subirarchivo();
          }
        } else {
          $_SESSION['session_user'] = NULL;
          $arrjson = Util::error_subirarchivoTam();
        }
      }
    } else {
      if ($file_name != '') {
        //Aqui se actualizan los archivos que suben
        $q1 = "UPDATE  " . $db->getTable($table) . " SET images_nombre='" . $file_name . "' ,images_tipo='" . $file_type . "',images_contenido='" . $contenido . "' WHERE $field=" . $id;
        $result = $pdo->query($q1);
        if ($file_error == 0 && $file_size > 0 && $file_size < $peso) {
          if ($result) {
            $_SESSION['pms_archivo']["nombrearchivo"] = "";
            $_SESSION['pms_archivo']["tipoarchivo"] = "";
            $_SESSION['pms_archivo']["contenidooarchivo"] = "";
            $_SESSION['pms_archivo']["tamanio"] = "";
            $_SESSION['pms_archivo']["error"] = "";
            $arrjson = array('output' => array('valid' => true, 'response' => $pdo->lastInsertId()));
          } else {
            $_SESSION['session_user'] = NULL;
            $arrjson = Util::error_subirarchivo();
          }
        } else {
          $_SESSION['session_user'] = NULL;
          $arrjson = Util::error_subirarchivoTam();
        }
      } else {
        $arrjson = array('output' => array('valid' => true));
      }
    }
    $db->closeConect();
    return $arrjson;
  }

  /**
   * metodo para eliminar un archivo
   */
  public static function unlinkFile($file)
  {
    unlink($file);
  }

  /**
   * Metodo para obtener el valor maximo de la tabla
   */
  public static function getMaxIdFromTable($table)
  { // Fecha actual

    $db = new DbConection();
    $pdo = $db->openConect();

    $q = "SELECT MAX(id) as max FROM " . $db->getTable($table) . " WHERE YEAR(dtcreate) = YEAR(CURRENT_DATE()) ";
    $result = $pdo->query($q);
    $id = 0;
    foreach ($result as $valor) {
      $id = $valor['max'];
    }
    $db->closeConect();
    return $id;
  }


  public static function trace_log($rqst, $controlador = '')
  {
    $db = new DbConection();
    $pdo = $db->openConect();
    $op = isset($rqst['op']) ? $rqst['op'] : '';
    $usuario_id = 0;
    $administrador = '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $phpsessionid = isset($_REQUEST['PHPSESSID']) ? $_REQUEST['PHPSESSID'] : '';
    if (isset($_SESSION['session_user'])) {
      $usuario_id = $_SESSION['session_user']['id'];
      $administrador = $_SESSION['session_user']['tipo'];
    }
    $q = "INSERT INTO " . $db->getTable('tec_log_auditoria') . "  (dtcreate, ip, usuario_id, administrador, op, controlador, rqst, user_agent, phpsessionid) VALUES (" . Util::date_now_server() . ", '" . Util::get_real_ipaddress() . "', '" . $usuario_id . "', '" . $administrador . "', '" . $op . "', '" . $controlador . "', '" .  json_encode($rqst) . "', '" . $user_agent . "', '" . $phpsessionid . "') ";
    $pdo->query($q);
    $db->closeConect();
  }

  public static function trace_session_user($rqst)
  {
    $db = new DbConection();
    $pdo = $db->openConect();

    // Obtener los valores necesarios
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $usuario_id = isset($rqst['usuarioId']) ? intval($rqst['usuarioId']) : 0;
    $ip_address = Util::get_real_ipaddress();

    // Preparar la consulta
    $q = "INSERT INTO " . $db->getTable('tbl_historial_session') . " 
            (dtcreate, ip, navegador, tec_usuario_id) 
            VALUES (:dtcreate, :ip, :navegador, :tec_usuario_id)";

    try {
      // Usar prepared statements para mayor seguridad
      $stmt = $pdo->prepare($q);
      $stmt->execute([
        ':dtcreate' => Util::date(),
        ':ip' => $ip_address,
        ':navegador' => $user_agent,
        ':tec_usuario_id' => $usuario_id,
      ]);
    } catch (PDOException $e) {
      error_log("Error al insertar en tbl_historial_session: " . $e->getMessage());
    } finally {
      $db->closeConect();
    }
  }


  /*   public static function trace_log_error($rqst, $controlador = '', $error)
  {
    $db = new DbConection();
    $pdo = $db->openConect();

    $error = json_encode(UTIL::remove_special_char($error), true);

    $op = isset($rqst['op']) ? $rqst['op'] : '';
    $usuario_id = 0;
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $phpsessionid = isset($_REQUEST['PHPSESSID']) ? $_REQUEST['PHPSESSID'] : '';
    if (isset($_SESSION['session_user'])) {
      $usuario_id = $_SESSION['session_user']['id'];
    }
    $q = "INSERT INTO " . $db->getTable('tec_log_errores') . " (dtcreate, ip, usuario_id, op, controlador, rqst, user_agent, phpsessionid, error) VALUES (" . Util::date_now_server() . ", '" . Util::get_real_ipaddress() . "', '" . $usuario_id . "', '" . $op . "', '" . $controlador . "', '" .  json_encode($rqst) . "', '" . $user_agent . "', '" . $phpsessionid . "', '" . $error . "') ";
    $pdo->query($q);
    $db->closeConect();
  } */
  public static function isValidHr($hr)
  {
    if ($hr == "") {
      return true;
    }
    if ($hr != "" && strlen($hr) > 0 && strlen($hr) == 5) {
      return true;
    }
    return false;
  }

  /**
   * Calcular la fecha actual con una fecha que se quiera validar el numero de dias trasncurridos
   */
  public static function getCalcularDiasEntreFechaActual($fechaFin)
  {
    date_default_timezone_set('America/Mexico_City');
    $fecha = Date("Y-m-d"); // Fecha actual

    $dt = new DateTime($fechaFin);
    $fechaFinal =  $dt->format('Y-m-d');

    $dias    = (strtotime($fecha) - strtotime($fechaFinal)) / 86400;
    $dias = abs($dias);
    $dias = floor($dias);

    return $dias;
  }

  public static function getCurrentYear()
  {
    return Date("Y");;
  }

  /**
   * Obtener el listado de niveles y colores
   * @return [array] array de niveles y colores
   */
  public static function getListColors()
  {
    $db = new DbConection();
    $pdo = $db->openConect();

    $q = "SELECT * FROM " . $db->getTable('tbl_valores');;
    $result = $pdo->query($q);
    $colores = [];
    foreach ($result as $valor) {
      $colores[] = $valor;
    }
    $db->closeConect();
    return $colores;
  }


  /**
   * Obtener la infomración de los colores de los puntales
   */
  public static function getColorByPuntaje($puntaje)
  {
    $color = "";
    if ($puntaje >= 0 && $puntaje <= 180) { // Estable	0	180	#387905
      $color = "#387905";
    } elseif ($puntaje >= 181 && $puntaje <= 360) { // Bajo	181	360	#0041FE
      $color = "#0041FE";
    } elseif ($puntaje >= 361 && $puntaje <= 540) { // Medio	361	540	  #FEE300
      $color = "#FEE300";
    } elseif ($puntaje >= 541 && $puntaje <= 720) { // Alto	541	720	 #F2860D
      $color = "#F2860D";
    } elseif ($puntaje >= 721 && $puntaje <= 1000) { // 	Critico	721	1000	#FC0707
      $color = "#FC0707";
    }
    return $color;
  }

  public static function validarEstadoColor($valor)
  {
    $estado = "Igual";
    if ($valor > 0) {
      $estado = "Aumento";
    }
    if ($valor < 0) {
      $estado = "Disminuyo";
    }
    if ($valor == 0) {
      $estado = "Igual";
    }
    return $estado;
  }

  public static function validarEstadoColorV2($puntajeAnterior, $puntajeActual)
  {
    $estado = "Igual";
    if ($puntajeActual > $puntajeAnterior) {
      $estado = "Aumentó";
    }
    if ($puntajeActual < $puntajeAnterior) {
      $estado = "Disminuyó";
    }
    if ($puntajeAnterior == $puntajeActual) {
      $estado = "Igual";
    }
    if ($puntajeActual == 0) {
      $estado = "Disminuyó";
    }
    return $estado;
  }

  /**
   * Metodo para calcular Color del Municipio NUEVO
   */
  public static function getCalcularColorMunicipio($codeCity)
  {
    $color = "";

    $db = new DbConection();
    $pdo = $db->openConect();

    // Informacion de los colores del Municipio
    $q = "SELECT tbl_vereda.municipio_id, tbl_vereda.color, tbl_colores.orden  FROM  " . $db->getTable('tbl_vereda') . " INNER JOIN  " . $db->getTable('tbl_colores') . "  ON tbl_vereda.color = tbl_colores.color
        WHERE tbl_vereda.municipio_id = '$codeCity' GROUP BY tbl_vereda.color ORDER BY tbl_colores.orden ASC limit 1";
    $result = $pdo->query($q);
    foreach ($result as $valor1) {
      $color = $valor1['color'];
    }
    $db->closeConect();
    return $color;
  }

  /**
   * Calcular puntaje por factores y puntaje
   */
  public static function calcularPuntajeByFactor($rqst)
  {

    $factor = isset($rqst['factor']) ? ($rqst['factor']) : '';
    $puntaje = isset($rqst['puntaje']) ? floatval($rqst['puntaje']) : 0;
    if ($factor != "" && $puntaje >= 0) {

      switch ($factor) {
        case 'armado':
          // Puntaje Maximo es de 400 puntos
          $puntaje = $puntaje > 400 ? 400 : $puntaje;
          break;

        case 'social':
          // Puntaje Maximo es de 100 puntos
          $puntaje = $puntaje > 100 ? 100 : $puntaje;

          break;

        case 'economico':
          // Puntaje Maximo es de 500 puntos
          $puntaje = $puntaje > 500 ? 500 : $puntaje;
          break;
      }

      return $puntaje;
    }

    return null;
  }

  /**
   * Meotodo para obtener el resultados final por factor segun un municipio
   */
  public static function getResultFinalByMunByFactor($rqst)
  {

    $factor = isset($rqst['factor']) ? ($rqst['factor']) : '';
    $tbl_municipio_id =  isset($rqst['tbl_municipio_id']) ? intval($rqst['tbl_municipio_id']) : 0;
    $tbl_vereda_id =  isset($rqst['tbl_vereda_id']) ? intval($rqst['tbl_vereda_id']) : 0;
    $tbl_social_id =  isset($rqst['tbl_social_id']) ? intval($rqst['tbl_social_id']) : 0;
    $tbl_economico_id =  isset($rqst['tbl_economico_id']) ? intval($rqst['tbl_economico_id']) : 0;
    $tbl_armado_id =  isset($rqst['tbl_armado_id']) ? intval($rqst['tbl_armado_id']) : 0;

    if ($factor != "" && $tbl_municipio_id >= 0) {

      $db = new DbConection();
      $pdo = $db->openConect();

      $array = array();
      $q = "";
      switch ($factor) {
        case 'armado':
          $q = "SELECT id, resultado FROM " . $db->getTable('tbl_resultados_armado_final') . "
              WHERE municipio_id = $tbl_municipio_id AND vereda_id = $tbl_vereda_id AND tbl_armado_id = $tbl_armado_id limit 1";
          break;
        case 'social':
          $q = "SELECT id, resultado FROM " . $db->getTable('tbl_resultados_social_final') . "
              WHERE municipio_id = $tbl_municipio_id AND vereda_id = $tbl_vereda_id AND tbl_social_id = $tbl_social_id limit 1";
          break;

        case 'economico':
          $q = "SELECT id, resultado FROM " . $db->getTable('tbl_resultados_economico_final') . "
            WHERE municipio_id = $tbl_municipio_id AND vereda_id = $tbl_vereda_id AND tbl_economico_id = $tbl_economico_id  limit 1";
          break;
      }
      if ($q != "") {
        $result = $pdo->query($q);
        foreach ($result as $valor) {
          $array[] = $valor;
        }
      }
      $db->closeConect();
      return $array;
    }

    return null;
  }

  /**
   * Metodo para actualizar el puntaje y color de una Vereda
   */
  public static function updatePuntajeColorVereda($rqst)
  {

    $tbl_vereda_id =  isset($rqst['tbl_vereda_id']) ? intval($rqst['tbl_vereda_id']) : 0;
    $color =  isset($rqst['color']) ? ($rqst['color']) : '';
    $puntajeVereda =  isset($rqst['puntaje_vereda']) ? intval($rqst['puntaje_vereda']) : 0;

    if ($tbl_vereda_id > 0) {

      $db = new DbConection();
      $pdo = $db->openConect();

      $q0 = "UPDATE  " . $db->getTable('tbl_vereda') . "
        SET color= '' ,
        puntaje ='" .  $puntajeVereda . "'
        WHERE id = $tbl_vereda_id ";
      $result0 = $pdo->query($q0);

      $q1 = "UPDATE  " . $db->getTable('tbl_vereda') . "
                  SET color='" . $color . "' ,
                  puntaje ='" .  $puntajeVereda . "'
                  WHERE id = $tbl_vereda_id ";
      $result = $pdo->query($q1);

      if ($result) {
        //Ingresamos el historial
        $q = "INSERT INTO " . $db->getTable('tbl_veredas_historial_puntaje') . " (fecha, tbl_vereda_id, color, puntaje) VALUES ( " . Util::date_now_server() . ", :tbl_vereda_id, :color, :puntaje)";
        $result = $pdo->prepare($q);
        $arrparam = array(
          ':tbl_vereda_id' => $tbl_vereda_id,
          ':color' => $color,
          ':puntaje' => $puntajeVereda
        );

        if ($result->execute($arrparam)) {
          $arrjson = array('output' => array('valid' => true, 'response' => $tbl_vereda_id));
        } else {
          $arrjson = Util::error_general('Ingresando historial de vereda');
        }

        $db->closeConect();
        return $arrjson;
      } else {
        $db->closeConect();
        return Util::error_general('Actualizando puntaje y color de la vereda');
      }
    } else {
      return Util::error_no_result();
    }
  }

  /**
   * Metodo para actualizar el puntaje y color de una Municipio
   */
  public static function updatePuntajeColorMunicipio($rqst)
  {

    $tbl_ciudad_id =  isset($rqst['tbl_ciudad_id']) ? intval($rqst['tbl_ciudad_id']) : 0;
    $color =  isset($rqst['color']) ? ($rqst['color']) : '';
    $puntaje =  isset($rqst['puntaje']) ? intval($rqst['puntaje']) : 0;

    if ($tbl_ciudad_id > 0) {

      $db = new DbConection();
      $pdo = $db->openConect();

      $q0 = "UPDATE  " . $db->getTable('tbl_ciudades') . "
                  SET color ='' ,
                  puntaje ='" .  $puntaje . "'
                  WHERE id = $tbl_ciudad_id ";
      $result0 = $pdo->query($q0);

      $q1 = "UPDATE  " . $db->getTable('tbl_ciudades') . "
                  SET color ='" . $color . "' ,
                  puntaje ='" .  $puntaje . "'
                  WHERE id = $tbl_ciudad_id ";
      $result = $pdo->query($q1);

      if ($result) {
        //Ingresamos el historial
        $q = "INSERT INTO " . $db->getTable('tbl_ciudades_historial_puntaje') . " (fecha, tbl_ciudad_id, color, puntaje) VALUES ( " . Util::date_now_server() . ", :tbl_ciudad_id, :color, :puntaje)";
        $resultInsert = $pdo->prepare($q);
        $arrparam = array(
          ':tbl_ciudad_id' => $tbl_ciudad_id,
          ':color' => $color,
          ':puntaje' => $puntaje
        );
        if ($resultInsert->execute($arrparam)) {
          $arrjson = array('output' => array('valid' => true, 'response' => $tbl_ciudad_id));
        } else {
          $arrjson = Util::error_general('Ingresando historial de vereda');
        }

        $db->closeConect();
        return $arrjson;
      } else {
        $db->closeConect();
        return Util::error_general('Actualizando puntaje y color de la vereda');
      }
    } else {
      return Util::error_no_result();
    }
  }

  /**
   * Metodo para obtener el numero de colores por las Veredas de un Municipio Id
   */
  public static function numeroVeredasByMunicipioId($rqst)
  {

    $codigo_municipio =  isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;

    if ($codigo_municipio != "") {

      $db = new DbConection();
      $pdo = $db->openConect();

      $q = "SELECT  Count(tbl_vereda.color) AS CuentaDecolor, tbl_vereda.color,
        tbl_vereda.departamento_id, tbl_vereda.tbl_batallon_id, tbl_vereda.tbl_brigada_id
        FROM " . $db->getTable('tbl_vereda') . "
        WHERE tbl_vereda.municipio_id  = $codigo_municipio GROUP BY tbl_vereda.color ORDER BY CuentaDecolor ASC";
      $result = $pdo->query($q);
      $arr = array();
      if ($result) {
        foreach ($result as $valor) {
          $arr[] = $valor;
        }
        $arrjson = array('output' => array('valid' => true, 'response' => $arr));
      } else {
        $arrjson = Util::error_no_result();
      }

      $db->closeConect();
      return $arrjson;
    } else {
      return Util::error_no_result();
    }
  }
  /**
   * Metodo para obtener el numero de colores por las Veredas de un Municipio Id AÑO 2021
   */
  public static function numeroVeredasByMunicipioId2021($rqst)
  {

    $codigo_municipio =  isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;

    if ($codigo_municipio != "") {

      $db = new DbConection();
      $pdo = $db->openConect();

      $q = "SELECT  Count(tbl_vereda.color2021) AS CuentaDecolor, tbl_vereda.color2021,
        tbl_vereda.departamento_id, tbl_vereda.tbl_batallon_id, tbl_vereda.tbl_brigada_id
        FROM " . $db->getTable('tbl_vereda') . "
        WHERE tbl_vereda.municipio_id  = $codigo_municipio GROUP BY tbl_vereda.color2021 ORDER BY CuentaDecolor ASC";
      $result = $pdo->query($q);
      $arr = array();
      if ($result) {
        foreach ($result as $valor) {
          $arr[] = $valor;
        }
        $arrjson = array('output' => array('valid' => true, 'response' => $arr));
      } else {
        $arrjson = Util::error_no_result();
      }

      $db->closeConect();
      return $arrjson;
    } else {
      return Util::error_no_result();
    }
  }

  public static function getClasePorColor($color)
  {
    $critico = "#FC0707";
    $alto = "#F2860D";
    $medio = "#FEE300";
    $bajo = "#0041FE";
    $estable = "#387905";
    $clase = "";

    // Critico
    if ($color == $critico) {
      $clase = 'critico';
    }
    // Alto
    if ($color == $alto) {
      $clase = 'alto';
    }
    // Medio
    if ($color == $medio) {
      $clase = 'medio';
    }
    // Bajo
    if ($color == $bajo) {
      $clase = 'bajo';
    }
    // Estable
    if ($color == $estable) {
      $clase = 'estable';
    }
    return $clase;
  }

  /**
   * Metodo para obtener el numero de colores por las Veredas de un Codigo de departamento
   */
  public static function numeroVeredasByCodigoDepartamento($rqst)
  {

    $codigo_departamento =  isset($rqst['codigo_departamento']) ? ($rqst['codigo_departamento']) : '';

    if ($codigo_departamento != "") {

      $critico = 0;
      $alto = 0;
      $medio = 0;
      $bajo = 0;
      $estable = 0;

      $criticoEstado = "Igual";
      $altoEstado = "Igual";
      $medioEstado = "Igual";
      $bajoEstado = "Igual";
      $estableEstado = "Igual";

      $db = new DbConection();
      $pdo = $db->openConect();

      $qDep = "SELECT * FROM " . $db->getTable('tbl_departamentos') . " WHERE codigo_departamento = '$codigo_departamento' ";
      $resultDep = $pdo->query($qDep);
      $arrDep = array();
      if ($resultDep) {
        foreach ($resultDep as $valor) {
          $arrDep[] = $valor;
        }
      }

      $q = "SELECT  Count(tbl_vereda.color) AS CuentaDecolor, tbl_vereda.color,
        tbl_vereda.departamento_id, tbl_vereda.tbl_batallon_id, tbl_vereda.tbl_brigada_id
        FROM " . $db->getTable('tbl_vereda') . "
        WHERE tbl_vereda.departamento_id  = $codigo_departamento AND NOT tbl_vereda.color = '' GROUP BY tbl_vereda.color ORDER BY CuentaDecolor ASC";
      $result = $pdo->query($q);
      $arr = array();
      $arr2021 = array();
      if ($result) {

        // Año Actual
        foreach ($result as $valor) {
          if ($valor['color'] != "") {
            $arr[] = $valor;
          }
        }

        // Año 2021
        $q2021 = "SELECT  Count(tbl_vereda.color2021) AS CuentaDecolor, tbl_vereda.color2021,
            tbl_vereda.departamento_id, tbl_vereda.tbl_batallon_id, tbl_vereda.tbl_brigada_id
            FROM " . $db->getTable('tbl_vereda') . "
            WHERE tbl_vereda.departamento_id  = $codigo_departamento AND NOT tbl_vereda.color2021 = '' GROUP BY tbl_vereda.color2021 ORDER BY CuentaDecolor ASC";
        $result2021 = $pdo->query($q2021);
        if ($result2021) {
          foreach ($result2021 as $valor2021) {
            if ($valor2021['color2021'] != "") {
              $arr2021[] = $valor2021;
            }
          }
        }

        // Se valida el porcentaje de por color del Año 2021 y 2022
        foreach ($arr2021 as $valor2021) {
          foreach ($arr as $valor2022) {

            $color2021 = $valor2021['color2021'];
            $color2022 = $valor2022['color'];

            // Estable
            if ($color2021 == "#387905" && $color2021 == $color2022) {
              $estable = round((($valor2021['CuentaDecolor'] - $valor2022['CuentaDecolor']) / $valor2022['CuentaDecolor']) * 100, 2);
              $estableEstado = Util::validarEstadoColorV2($valor2021['CuentaDecolor'], $valor2022['CuentaDecolor']);
            }
            // Bajo
            if ($color2021 == "#0041FE" && $color2021 == $color2022) {
              $bajo = round((($valor2021['CuentaDecolor'] - $valor2022['CuentaDecolor']) / $valor2022['CuentaDecolor']) * 100, 2);
              $bajoEstado = Util::validarEstadoColorV2($valor2021['CuentaDecolor'], $valor2022['CuentaDecolor']);
            }
            // Medio
            if ($color2021 == "#FEE300" &&  $color2021 == $color2022) {
              $medio = round((($valor2021['CuentaDecolor'] - $valor2022['CuentaDecolor']) / $valor2022['CuentaDecolor']) * 100, 2);
              $medioEstado = Util::validarEstadoColorV2($valor2021['CuentaDecolor'], $valor2022['CuentaDecolor']);
            }
            // Alto
            if ($color2021 == "#F2860D" &&  $color2021 == $color2022) {
              $alto = round((($valor2021['CuentaDecolor'] - $valor2022['CuentaDecolor']) / $valor2022['CuentaDecolor']) * 100, 2);
              $altoEstado = Util::validarEstadoColorV2($valor2021['CuentaDecolor'], $valor2022['CuentaDecolor']);
            }
            // Critico
            if ($color2021 == "#FC0707" && $color2021 == $color2022) {
              $critico = round((($valor2021['CuentaDecolor'] - $valor2022['CuentaDecolor']) / $valor2022['CuentaDecolor']) * 100, 2);
              $criticoEstado = Util::validarEstadoColorV2($valor2021['CuentaDecolor'], $valor2022['CuentaDecolor']);
            }
          }
        }
        // Fin Se valida el porcentaje de por color del Año 2021 y 2022

        $arrjson = array('output' => array(
          'valid' => true,
          'response' => $arr,
          'response2021' => $arr2021,
          'departamento' => $arrDep,
          'estable' => $estable,
          'bajo' => $bajo,
          'medio' => $medio,
          'alto' => $alto,
          'critico' => $critico,
          'estableEstado' => $estableEstado,
          'bajoEstado' => $bajoEstado,
          'medioEstado' => $medioEstado,
          'altoEstado' => $altoEstado,
          'criticoEstado' => $criticoEstado
        ));
      } else {
        $arrjson = Util::error_no_result();
      }

      $db->closeConect();
      return $arrjson;
    } else {
      return Util::error_no_result();
    }
  }

  public static function fechaVotacion()
  {
    return '2022-03-10';
  }

  public static function getDiasEntreDosFechas($f1, $f2)
  {
    if ($f1 != "" && $f2 != "") {
      $fecha1 = new DateTime($f1); // 2017-08-01
      $fecha2 = new DateTime($f2);
      $diff = $fecha1->diff($fecha2);
      return $diff->days;
    }
    return null;
  }

  public static function idDepartamentoObjetivo()
  {
    return 21;
  }

  public static function validateParameters($params)
  {
    foreach ($params as $param) {
      if (!isset($_REQUEST[$param]) || empty(($_REQUEST[$param]))) {
        return false;
      }
    }
    return true;
  }

  /**
   * Metodo para validar los campos obligatorios en las Clases
   * @param requiredFields Lista de campos a validar, Ejemplo ['ejeId', 'pilarId', 'areaId', 'puntaje', 'tipo', 'tipo_medicion', 'icono'];
   * @param rqst Request que se envia a la clase
   */
  public static function validarCamposObligatorios($requiredFields, $rqst)
  {
    foreach ($requiredFields as $field) {
      if (empty($rqst[$field]) && $rqst[$field] !== '0' && $rqst[$field] !== 'seleccione') {
        return Util::error_missing_data();
      }
    }
  }

  public static function consultarNotificaciones()
  {
    if (!isset($_SESSION['session_user']['tipo'], $_SESSION['session_user']['tbl_secretarias_id'])) {
      return ['output' => ['valid' => false, 'response' => [], 'cantidad' => 0]];
    }

    $tipoUsuario = $_SESSION['session_user']['tipo'];
    $secretariaUsuarioId = $_SESSION['session_user']['tbl_secretarias_id'];

    if ($tipoUsuario === "Secretario_Despacho" || $tipoUsuario === 'Auxiliar') {
      $db = new DbConection();
      $pdo = $db->openConect();

      $query = "SELECT * FROM " . $db->getTable('tbl_ministerios_proyectos') . " WHERE tbl_secretaria_id = :secretariaUsuarioId";
      $stmt = $pdo->prepare($query);
      $stmt->bindValue(':secretariaUsuarioId', $secretariaUsuarioId, PDO::PARAM_INT);
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $db->closeConect();
      return [
        'output' => [
          'valid' => true,
          'response' => $data,
          'cantidad' => count($data)
        ]
      ];
    }

    return [
      'output' => [
        'valid' => true,
        'response' => [],
        'cantidad' => 0
      ]
    ];
  }
}
