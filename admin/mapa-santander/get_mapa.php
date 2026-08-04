<?php
	include_once './../classes/DbConection.php';
	include_once './../classes/Util.php';


  function getUrl(){

    $port = $_SERVER["SERVER_PORT"];
  
    $nameServer = $port != "80" ? $_SERVER['SERVER_NAME'].":".$port: $_SERVER['SERVER_NAME'];
  
    $url = sprintf(
      "%s://%s%s",
      isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
      $nameServer,
      $_SERVER['REQUEST_URI']
    );
  
    return str_replace(basename($_SERVER["SCRIPT_FILENAME"], '.php').".php", "", $url);
  }


  $webroot = getUrl($_REQUEST["url"]);
	include ("../../".$_REQUEST["url"]);
?>

