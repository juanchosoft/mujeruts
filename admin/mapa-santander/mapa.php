<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
define("DS", DIRECTORY_SEPARATOR);
require_once __DIR__ . '/../classes/Colombia.php';
require_once __DIR__ . '/../db/colores.php';

$arr = array('codigo' => 68);
$data = Colombia::getDepartamentoByCodigo($arr);
$isvalid = isset($arr['output']['valid']) ? $arr['output']['valid'] : false;
$santander = isset($data['output']['response']) ? $data['output']['response'] : [];

$arr2021 = [];
$numeroVeredas = isset($numeroVeredas) ? $numeroVeredas : ['output' => ['valid' => false]];

if ($numeroVeredas['output']['valid']) {
    $isvalid = $numeroVeredas['output']['valid'];
    $arr = $numeroVeredas['output']['response'];
    $arr2021 = isset($numeroVeredas['output']['response2021']) ? $numeroVeredas['output']['response2021'] : [];

    // Verificar y asignar los porcentajes solo si existen
    $bajo = $numeroVeredas['output']['bajo'] ?? null;
    $medio = $numeroVeredas['output']['medio'] ?? null;
    $alto = $numeroVeredas['output']['alto'] ?? null;
    $critico = $numeroVeredas['output']['critico'] ?? null;
    $estable = $numeroVeredas['output']['estable'] ?? null;
    $bajoEstado = $numeroVeredas['output']['bajoEstado'] ?? null;
    $medioEstado = $numeroVeredas['output']['medioEstado'] ?? null;
    $altoEstado = $numeroVeredas['output']['altoEstado'] ?? null;
    $criticoEstado = $numeroVeredas['output']['criticoEstado'] ?? null;
    $estableEstado = $numeroVeredas['output']['estableEstado'] ?? null;

    // Información del departamento
    $departamento = $numeroVeredas['output']['departamento'][0] ?? [];
    $colorDepartamento = $departamento['color'] ?? '';
    $puntajeDepartamento = $departamento['puntaje'] ?? '';
}

?>

<!DOCTYPE html>
<html lang="es">


<head>
  <title>Mapa Santander</title>
  <meta charset="UTF-8">
  <meta name="title" content="">
  <meta name="description" content="">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
</head>

<body>
  <div class="content-map infoMapa">
    <div class="titles_jurisdicciones btll">
     </div>
  </div>

  <style>
	.nombres{
		font-family: "IBM Plex Sans", sans-serif!important;
	}
	.fondo {
    background-color: #FC0707;
    padding: 2px 4px; /* Añade un poco de espacio alrededor del texto */
    color: white; /* Asegura que el texto sea legible */
    display: inline-block; /* Asegura que el fondo solo cubra el texto */
}
  </style>

    <div class="content-map">
      <div id="mapa">
        
      <svg version="1.1"
        id="svg2" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:svg="http://www.w3.org/2000/svg" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:cc="http://creativecommons.org/ns#"
        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1280.6 1508"
        style="enable-background:new 0 0 1280.6 1508;" xml:space="preserve"
        class="max-w-4xl m-auto"
        stroke="#000"
        stroke-whidth="1px"
        >
        
          <style type="text/css">
            .st0{fill:#D3D3D3;}
            .st1{fill:#EFEFEF;}
            .st2{fill:#939393;}
            .st3{fill:#707070;}
            .st4{fill:#B5B5B5;}
            .st5{font-family:'ArialMT';}
            .st6{font-size:15px;}
          </style>
          <sodipodi:namedview  bordercolor="#666666" borderlayer="true" borderopacity="1.0" fit-margin-bottom="0" fit-margin-left="0" fit-margin-right="0" fit-margin-top="0" id="base" inkscape:current-layer="layer5" inkscape:cx="640.28082" inkscape:cy="754.00232" inkscape:document-units="px" inkscape:guide-bbox="true" inkscape:pageopacity="0.0" inkscape:pageshadow="2" inkscape:snap-bbox="true" inkscape:snap-page="false" inkscape:window-height="986" inkscape:window-maximized="1" inkscape:window-width="1920" inkscape:window-x="-11" inkscape:window-y="-11" inkscape:zoom="0.47745211" pagecolor="#ffffff" showgrid="false" showguides="true">
          </sodipodi:namedview>
          <g
						id="g1247"
						transform="translate(-2453.8755,-2204.8853)"
						inkscape:groupmode="layer"
						inkscape:label="Entidades"
					>
          <?php foreach ($santander as $key => $value)  : ?>
			<path
                inkscape:connector-curvature={0}
                id="<?php echo $value['path']; ?>"
                inkscape:connector-curvature="0"
                sodipodi:nodetypes="<?php echo $value['nodetypes']; ?>"
                d=" <?php echo $value['d']; ?>"
                class="carmen-del-chucuri municipios mapaClick <?php echo getClasePorcentaje($value['porcentaje_participacion']); ?>"
                data-url="<?php echo getUrl() . 'estado_municipios.php?mun=' . $value['codigo_muncipio'] . '&dep=' . $value['codigo_departamento']; ?>"
				data-sub="<?php echo isset($value['color_calculado_de_municipio']) ? getClaseColorVeredas($value['color_calculado_de_municipio']) : ''; ?>"
                data-name="<?php echo strtolower($value['municipio']); ?>"
                title="<?php echo strtoupper(str_replace("-", " ", $value['nombre_mapa'])); ?>"
                style="fill: <?php echo getColorByNum($value["num_val"])?>; ";
            />
		  </path>
        <?php endforeach; ?>
						<!--  <path
							id="path1073"
							inkscape:connector-curvature= "0"
							class="carmen-del-chucuri municipios mapaClick"
							d="M3057.2,3206.3l5.4,0.2l3.6-2.3l4.9,0.5l1.8-4.4h6.4l8.2,2.1 l3.6-3.4l5.6-0.8l0.5-3.9l2.3-3.6l6.1,5.2l5.6,1.8h9.2l4.1-2.3l0.8-3.4l-1.5-4.4l1-5.2l4.1-3.9l-0.3-4.4l2.3-3.6l-1.5-6.5l3.8-5.4 l4.1-3.4v-6.7l4.4-5.7l2.8-1.5v-6.2l3.6-4.6l8.2-11.1l3.3-6.7l0.3-5.4l-1.5-9.5l-0.5-10.3l5.1-7.2h-6.7l-4.9-1.3l-8.7,1.5l-7.4-4.4 l-6.4-1.8l-5.9-4.4l-4.6-0.3l-6.7,4.4l-1.5-3.9v-6.2l-3.1-9.3l3.6-3.1l0.8-6.2l-3.1-1.5l-4.1,2.6l-6.1,1.3l-6.4-3.9l-1.3-3.1 l-10.5-7.2l-0.8-3.1l-4.9,3.6l-4.4,1.3v2.3l-3.6,3.1l-4.1-0.3l1.8-5.2l-2.1-3.4l-3.2-1.2l1.9-6l-12.5,5.3l-6.3-4l-2.9,1.7l-14-10.2 l-3.1-1l-2.6-4.8l-6,0.9l-0.2,2.6l-3.6-1.7l-2.6,0.2l-2-2.1l-1.9,2.1l-2.7-1l9,38.7l-1.7,7.6l-4.6,4.5h-4.1l-6.7-4.8l-7-0.3l-0.7,4 l-6.1,4.3l-6.1-2.4h-6l-4.1-4.8l-2.9,0.5l-1.5-5l-4.4-0.2l-3.6-0.7l-1.9,3.8l-2.7,1l1.9,2.2l-2.7,2.2l4.3,3.4l-0.9,2.8l3.2,2.8 l-0.3,5.7l-2.4,3.4l-2.7-1.7l-2,0.9l-0.7,5.2l2.7,5.5l-3.2,3.6l4.1,4l-1.5,3.4l3.2,4.5l-1.7,3.8l1,6.9l6.8,2.1l-3.6,1.9l1,2.6 l3.4,0.7l-0.9,3.4l-6.3,2.2l0.7,12.7l9.7,5.7l-1.5,3.4l3.1,2.1l7.5-0.3l1.5-4l3.6,4.1l0.5,5l9.6,1l2.4,2.4l-3.1,1.4l0.7,3.8 l-3.9,7.6l5.8,7.1l4.3-1.4l2.6,1.9l3.4-1.4l5.1,1.9l5.5-1.9l7.2,4l7.2-5.2l5.1,3.3l4.6-1.5l5.6,1.5l3.9,2.8l4.6,0.3l4.1,4.3l7.2-1 l2.7,8.4L3057.2,3206.3L3057.2,3206.3L3057.2,3206.3z"
						/>
						<path
							id="path1075"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="san-vicente-del-chucuri municipios mapaClick"
							d=" M3163,3080.3l4.9-2.5l0.5-4.3l-2.6-4.5l1.4-5.2l-2.2-4.5l2-9.6l3.1-4.1l1.4-5.2l0.3-3.8l3.8-8.3l-0.2-10l3.4-9.8l5.1-5l0.3-7.4 l2.2-16.7l2.2-4.8l-4.1-7.4l-0.3-5.3l-5.1-2.6l-4.1,1.9l-4.4-3.8l3.1-2.8l0.7-4.5l-3.8-2.1l2.6-4.6l-11.1-0.2l-3.1-3.3l-0.9-4 l-4.8-3.1l-0.2,3.8l-4.8,2.2l-2.6-2.1l-2.9,0.9l-5.1-3.4l-6,2.8l-7.2,1.2l-8.2-2.6l-3.8-2.4l-4.3-0.3l-12.8-10.7l-3.6,0.2l-4.6-4.8 l-0.9-15.8l4.6-9.1l-0.5-10l2.6-8.8l-7.3,3.3l-1.4,3.4l-7.3,4.3l-4.6-0.2l-0.7-19.3l-1.7-6l3.4-13.4v-9.6l-2.9-7.9l-4.1-7.1 l-3.9-3.8l-96,126.2l3.8,3.9l2.7,0.3l2,4.6l4.6,4l4.4,2.2v3.1l6,2.8l-4.3,1.9l-0.3,2.4l-5,0.3l1.9,3.4l-3.1,2.4l-4.8,5.3l-3.6-2.9 l-1.9,0.5l-1.5-3.6l-2.7,3.1l-1.4-4.6l-3.8,0.5l-4.6-2.4l-5,0.3l-2-1.9l-3.4,1l0.9,3.3l-2.6,1.4l0.7,2.2l-1.7,2.1l3.9,4.1l-2.9,3.1 l0.3,4.6l-3.1-0.2l-3.1,5.3l-0.2,5l-5,2.8l0.7,6.5l-2,3.4l1.9,4.1l-4.4,3.6l2.7,6l3.1-1.5l3.4,0.9l1.7,5l-1.5,3.8l4.6,1.2l2.9,2.2 l0.2,3.6l3.8,2.2l-1,3.4l1.7,5l3.6,0.7l4.4,0.2l1.5,5l2.9-0.5l4.1,4.8h6l6.1,2.4l6.1-4.3l0.7-4l7,0.3l6.7,4.8h4.1l4.6-4.5l1.7-7.6 l-9-38.7l2.7,1l1.9-2.1l2,2.1l2.6-0.2l3.6,1.7l0.2-2.6l6-0.9l2.6,4.8l3.1,1l14,10.2l2.9-1.7l6.3,4l12.5-5.3l-1.9,6l3.2,1.2l2.1,3.4 l-1.8,5.2l4.1,0.3l3.6-3.1v-2.3l4.4-1.3l4.9-3.6l0.8,3.1l10.5,7.2l1.3,3.1l6.4,3.9l6.1-1.3l4.1-2.6l3.1,1.5l-0.8,6.2l-3.6,3.1 l3.1,9.3v6.2l1.5,3.9l6.7-4.4l4.6,0.3l5.9,4.4l6.4,1.8l7.4,4.4l8.7-1.5l4.9,1.3L3163,3080.3L3163,3080.3L3163,3080.3z"
						/>
						<path
							id="path1077"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="betulia municipios mapaClick"
							d=" M3116.5,2935.9l1.4-2.9l3.6-0.5l3.9-5.7l0.3-4.6l2.6-2.4l4.1,0.5l6.3-2.1l4.8,1l4.3-1.9l10.6-7.2l3.9,1.9l5.1-0.9l4.6,3.1l4.1,0.3 l5.5,3.4l2.9,4l-1.4,4.3l-2.7,1.5l2.2,3.8l-2.7,4.6l-3.9,7.1l-1,4.3l2.4,1.7l6.7,1.5l3.4,4.3l0.3,5.5l2.6,2.1l2.6,1.2l0.2,6.4 l3.4,3.1l0.9,9.3l4.4-0.2l5.3-2.6l-1.5,3.8l5,7.6l4.6-1l2.2,2.4l3.6,1.2l5.5,7.9l7.9-11.5l0.3-7.6l7-8.4l1.9-5.2l5.3-1.5l2.9-4 l0.7-2.9l3.9-5.7l-4.1-6l-2.6-6.5l-5.8-6.5l-7.9-2.8l-5.6-3.4l-9.9-10.5l-3.1-10l-5.3-6.9l-4.6-4.8l-5-2.1l-4.8-0.9l-4.1-3.1h-4.3 l-7.5-4l-2.7-4.6v-3.4l2.4-3.1l-3.1-4.8l-4.4,1.7l-9.7-0.5l-8.2-6.5l-6-7.6l0.3-4.5l-4.4-3.4l-5.3-1.2l-7.7-6.2l-6.5-3.3l-1.9-4.5 v-3.3l-2.6-2.6l-3.9,1.2l-7.3-5l-7-2.4l-6-0.5l-3.4-2.6l-8.7,1.7l-7.5,3.4l-6-6.2l-4.3-1.7l-5.1,0.2l3.9,3.8l4.1,7.1l2.9,7.9v9.6 l-3.4,13.4l1.7,6l0.7,19.3l4.6,0.2l7.3-4.3l1.4-3.4l7.3-3.3l-2.6,8.8l0.5,10l-4.6,9.1l0.9,15.8l4.6,4.8l3.6-0.2l12.8,10.7l4.3,0.3 L3116.5,2935.9L3116.5,2935.9L3116.5,2935.9z"
						/>
						<path
							id="path1079"
							inkscape:connector-curvature= "0"
							class="sabana-de-torres municipios mapaClick"
							d="M3062.7,2817.9l8.9-4.3l0.9-2.9l4.8-1.2l2.2,1.7l7-1.7 l6.8,3.8l4.8-4.3l3.9,2.8l3.8-3.4l1.7,4.8l4.6,1.7l1.7-5.7l3.2,2.4l4.6-0.9l-1.2-4.6l1.9-3.8l3.6-0.5l2.2-5.7l5.5-4.1l5.6-1.4 l2.7-2.9l5.6-2.2l25.3-43.5l-2.7-7.7l-0.3-5.3l-5.5-7.1l-0.3-7.7l3.2-5.5l-2.2-3.8l0.9-3.4l-3.4-3.3l2-1.9l-2.2-3.8l1.2-6.7 l-3.6,2.2l-3.6-4l4.4-7.7l3.2-1.9l-10.2-8.8l-8.7,4l-3.1-7.6l-7.3,4.6l-8.4-10.2l-2.4-6.5l-4.4-4.3l2.9-5.5l-1.4-2.2l-4.3,1.2 l-2-3.1l2-3.8l-1.5-2.8l-5,2.1l-2.9-1.9l0.5-2.6l3.8-4.6l-2.7-2.1l-4.4,1.7l-2.4-1.7l2.7-4.6l-9-8.8l-9.9-2.4l-4.8,3.3l-3.4-5.2 l-3.2-0.2l-3.6-3.3l-0.5-4.6l-3.8-4.6l-4.8-3.1l2.4-4.5l-2-1.4l-4.1,0.3l-2.7-2.6l-0.7-6.9l1.4-2.6l-2.6-2.1l-6-10.2l-11.3-1.5 l-7-2.1l-2.7-2.9h-5.1l-5.8-5l-3.8,3.6l-3.1-2.4l-2.9,2.1l-3.8-2.2l-5.5,1.2l0.3-5.5l-5.1-0.3v-5.2l3.4,0.7l1-2.6l-2.6-6.2 l-2.6,2.1l-1.9-2.8l2-4.5l-1.5-2.2l-3.8,3.8l-2.4-3.1l-4.3,1.9l0.9-5.5l-4.4,1l0.7-3.3l-3.4-0.3l-1.4-4.8l-3.2-5.2l-6.5-1.2 l-1.9-4.1l0.9-3.4l-4.4-2.8l-8.7,4l-1.9,4.3l-4.4-0.9l-3.1-2.6l-4.3,1.5l-2.9-6.5l-1.9,3.8l1.4,3.3l-4.1,6.4l4.3,4.5l-0.5,7.7 l-1.9,4.8l3.2,5.2l4.8,2.2l5.6,11.4l-1.2,4.3l11.4,12l-2.9,4.3l-6.1,3.3l-6.7,8.9l-6.5,1l-1,4l-6-1.5l0.9,7.1l-2.7,3.8l3.6,8.1 l-1.7,9.8l1.5,8.6l-2.2,5.5l1.2,13.8l-1.2,17.9l3.6,4.1l6.1,0.7l5.8,4.6v5.7l6.3,2.4l5.6-1.2l10.9,7.9l4.1,0.7l2,5.8l-3.6,4.1 l8,5.5l6.5-2.6l6.5-0.2l2,5.7l7.3,7.2l5.5,1.2l-3.1,4.6l2.9,2.2l-2.6,7.4l3.8,5.8l5-0.7l2.4,4.8l5.3,2.6l-0.7,2.9l6.1,5.8l3.6,1 l0.2,3.8l4.3,4.5l6.1,14.3l4.4,15.5l9.6,21.5l1.5,6.5l7.2,3.8L3062.7,2817.9L3062.7,2817.9z"
						/>
						<path
							id="path1081"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="landazuri municipios mapaClick"
							d=" M2911.1,3175l6.7,2.4l3.8,3.8l-1.6,2l1.7,3.6l-0.2,4.3l-2.7,11.9l1.5,4.3l-1.5,5.5l2.6,6.9l3.9-1.7l3.2-3.8l3.1,0.7l1.5,7.9 l3.6,1.2l4.6,10.5l4.4,6.5l6.8,5.5l1.2,2.6l5.7,4.6l3.8-0.2l3.7,3.7l11.1,5.4l2,4.3l-0.3,4.3l-4.3,3.3l-3.7-2.6l-4.1,13.6l-4.8,1.2 l1,4.8l-1.7,4.3l-4.3,4.3l-3.9,8.4l-4.3,3.1l-7,11.5l-1.2,7.2l-3.7,6.4l4.3,1.7l3.6,4.6l1.2-3.3l5.1-1l0.7,4.1l-2.7,2.7l-2-0.7 l-3.9,4.6l-0.5,4.5l4.1,1.9l2.9,3.4v4.8l2.7,5.5l-1,5.3l-4.7,4.5l-2.8,1l-4,4.1l-0.2,2.7l-1.4,0.9l-3.6,3.7l-6.3-0.3l-1.9-2.7 l-5.1-2.1l-3.6,0.2l-3.2-2.2l-5.8,1l-10.9,5l-15.8,14.9l-5.3,0.7l-3.6,3.3l-0.3,3.6l-2.7,1l-3.1-4.1l-0.7-7l-4.7-0.7l-2.6-4 l-5.1-1.3l-4.5-2.4l-2.2-6.5l-2-4.5v-5.8l-7-4.3l-6.1,3.8l-4.4-2.7l-9,7l-2-1.5l-3.4,1.5l-2.4-4.3l-4.6,2.4l-2.2-1.2l-9.9,6.4 l-0.5,2.4l-5.8,3.4l-6.1-1.2l-15.5,13.9l-7.7-1.9l-4.6,0.7l-2.7-2.1h-3.1l-2-3.4l-2.6-0.2l-1.9,2.4l-1.7-0.5l-0.7-2.6l-3.5,0.7 l-5.6-8.6l-0.8-5.9l3.5-0.1l3.5,4.3l4.8-0.3l7.5,3.4l2.9-4.2l2.8-2.6l5.4-1.1l9.5-0.4l4.4-3.6l3.7,0.1l4.7-3.1l5.3,0.4l2.2-4 l5.6-2.1l8.9-7.2l8.6-2.2l10.1-9.6l1-5.5l4.7,0.3l7.3-2.7l6-4.3l2.2-5.2l5,5.3l2.6-3.5l1.3-6.9l12.3,4.3l5.4-2.1l0.6-3.7l3.7-2.7 l0.1-8.8l-1.3-5.6l2.5-7.7l4.4-5.8l6.3-3.4l5.9-7.1l-1.8-4.7l6.4-10.3v-7.5l4.1-6.9l0.6-4l5.4-5.8l-1.5-4l-26.6-21.5l-12.1-15.3 l4.1-5.5l3.8,2.8l3.6-1l3.2,3.6l8.9-11.5l10.8-10l-0.2-4.6l1.7-2.8L2911.1,3175L2911.1,3175L2911.1,3175z"
						/>
						<path
							id="path1083"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="sucre municipios mapaClick"
							d=" M2917.5,3547.5l7.9,1l4.4,0.8l2.7,7.2l0.2,4l3,1.1l0.3,2.5l-0.7,4.3h-3.9l-3.4-2.9l-5.3-1.8l-2.8,1.6l-1.4,3l-2.5,2.7l-4.5,2.6 l-2.2,2.9l-5.7,1.4l3.4,4.3l1.9,5.2l-4.9,1.4l-2.6,2.2l-2.6,6.9l-3.9,2.6l-2.6,2.9l-3.7,2.2l-4.2,0.5l-2.2-2.8l-3.5-1.8v-3l-3.2-2 l-4.6,1.6l-3.3-1.5l-1.6-2.9l-3.2-1l-3.4,1.1l-3-2.6l-4.6,1l-4.3,3.7l-3,0.7l-7.2-2.2l-3.1-5.7l-8.5-4.8l-1.1-3.6l3-3.4v-3.1 l3.6-0.3l-2.8-4l1.4-2.8l2.4-0.7l1-1.8l-2.5,0.1l-3.5-3.6l3.5-2.1l-0.3-2.5l-3.9-0.9l-4.6,1.9l-3.2-0.9l-3.1,1.7h-3.9l-3.1,3.3 l-5.3-4l-5.1-1.2l-1.7-4.3l2.6-5.5l0.2-6.9l-3.2-4.8l-3.9-3.6l-5.5-0.2l-3.6-2.8l-6-1.5l-3.4-3.8l-2.2,0.7l0.2,3.4l-4.3,7.1v6.2 l-9.2,9.3l-3.8,7.7l-4.6-2.6l-4.3,2.1l-4.6-1.7l-4.1,2.6l-11.6-9.3l-3.1-2.1l6-7.2l-0.7-4.8l3.8-3.3l2.6-4.3l-2.4-4l-0.3-4.3 l-3.9-3.1l-2.9-5.3l-1.2-5.2l1.9-2.9l-3.4-1.7v-4.5l-3.4,1.7l-3.7-4.8l5.7-4.7l1.9-1.9l5.1,0.3l2.3-0.8l-0.1-1l-2.5-2.6l-1.9-1.4 l0.1-2.9l-1.1-1.9l-0.3-2l2.5-1.3l0.5-2.2l-1.5-2.2l-3-1.7l-2.2-1.8l0.5-5.2l1-2.4l2-2.2l2.7-1.7l1.5-1.9l-5.3-4.2l-1.3-1.8v-3.3 l2.2-3.7l3.8-1.2l5.7-0.4l1.7-2.2l2-1.8l-1-1.4l-2.3-1.8l-2.7-1.9l-2.3-1.9l-0.3-1.7l1.9-2l0.5-2.9l-0.1-2.4l-1.7-1l-0.5-2l2.4-0.4 l1.6,0.5l3-2.1v5.3l1.7,4l7.2-0.3l-0.4,5.4l4.2,2l5.9-1.5l5.3,1.3l4.5,3.2l2-0.4l3.9,1.9l0.2,2.5l-3.7,3.9l0.4,3.4l3.5,6l3.4,1.1 l2.8,3l-0.8,3.7l-2.4,3.7l-2.9,3.3l-0.6,3.9l2.2,3.7l3.6,1.8l5.1,1.7l4.2,0.3l1.5-2.8l6.9-1.8l4.8,0.1l2.6,4.4l0.5,2.3l3.5-1.6 l3.5-3.7l1.9,1.5l2.9-1.6l4.6,0.5l3.3,1.9l2.7-2.2l-2.7-13l4.4-4.1l3.3-4.5l5.5,5.6l0.5,4.8l1.8,3.7v4.4l5.1,4.8l5.2,3.1l7.2,7.4 l0.1,3.5l3.5,2l0.9,2.8l6.9,5.6l5.5,1.4l4.5,2.9l3.7-0.1l3.1,3.5l-1.7,6.6l1.3,2.2l-1,4l-4.8,3.8l1.7,2.8h2.2l5.1,5.7l5.5,6.9 l-0.2,3.4l6.7-2.8l2.6,2.1l4.8,1.4l3.8,3.1L2917.5,3547.5L2917.5,3547.5L2917.5,3547.5z"
						/>
						<path
							id="path1085"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="el-penon municipios mapaClick"
							d=" M2833.5,3423.8l0.2,4.1l1.7,6.2l2.4-0.4l4.4-1.2l4.6-2.4l3.6-2.4l3.6-4l4.4-1.2l2.9,2.2l4.1,0.7l2.2,3.6l0.2,5l2.3,4l4,1.6l3.9-0.4 l4.1,1l3.2,3l4,0.6l3.1-1.5l9.7-7.6l3.4,6.9l4.4,5.5v4.5l3.1,5.7l-0.5,6l3.1,4.8l4.5,3.1l1.9,4l-1.4,7.9l-0.5,7.9l-5.8,5.3 l-5.3,1.7l-4.8-0.2l-6.8,3.3l-5.5,3.2l-10.7-0.1l-3.1-3.5l-3.7,0.1l-4.5-2.9l-5.5-1.4l-6.9-5.6l-0.9-2.8l-3.5-2l-0.1-3.5l-7.2-7.4 l-5.2-3.1l-5.1-4.8v-4.4l-1.8-3.7l-0.5-4.8l-5.5-5.6l-3.3,4.5l-4.4,4.1l2.7,13l-2.7,2.2l-3.3-1.9l-4.6-0.5l-2.9,1.6l-1.9-1.5 l-3.5,3.7l-3.5,1.6l-0.5-2.3l-2.6-4.4l-4.8-0.1l-6.9,1.8l-1.5,2.8l-4.2-0.3l-5.1-1.7l-3.6-1.8l-2.2-3.7l0.6-3.9l2.9-3.3l2.4-3.7 l0.8-3.7l-2.8-3l-3.4-1.1l-3.5-6l-0.4-3.4l3.7-3.9l-0.2-2.5l-3.9-1.9l-2,0.4l-4.5-3.2l-5.3-1.3l-5.9,1.5l-4.2-2l0.4-5.4l4.4-3.4 l3.4,1.9l4.4-2.2l0.5-3.8l3.8-0.2l2.7-2.6l4.3-0.7l12.6-8.8l8.7,0.3l3.2-2.2l8.4,0.5l2.7-2.2l3.6,3.3l2.7-1.9l3.6,3.3l-1.9,4.6 l5.8,3.6l1.7,4.6v6l3.8,0.9l2.7-2.2l1.9,4.5l2.7,0.3l2.4-1.9L2833.5,3423.8L2833.5,3423.8L2833.5,3423.8z"
						/>
						<path
							id="path1087"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="santa-helena-del-opon municipios mapaClick"
							d=" M3031.1,3319.5l-2.3,0.7l-2.4-1.5l-4.2,3.5l-3.2,0.8l-1.5-1.4l-6.9,3.9l-3.2-1.1v2.2l-1.4,1l0.8,2.9l-1.4,0.1l0.2,4.3l0.3,4.3 l-3.8-0.5l-2,1.3l-4.4-0.3l-1.9,4.1l-0.3-3.2l-2.2-4l-0.7-4.8l2.3-2.9l-3.1-1.8l-2.6-0.7l-0.9-2.6l1.4-4.5l2.2-5.3l3.2-3.8v-4.6 l-3.1-4.1l-0.2-5l2.2-4l-2-9.6l-2.4-0.7l-3.4-2.2l2.9-1.9l3.2,0.5l1.2-9.6l-2-4.1l0.5-4.6l2.9-4.1l2.6-0.3l0.3-2.9l7.3-8.6 l-2.7-4.8l3.1-1.2l-4.8-2.4l-0.9-5.2l-4.4-4.6l-5,0.3l-6.3-2.1l1-2.8l4.3-3.8l2.2,2.8l9.6,0.7l5.8-8.8l-0.5-4l7.2-7.4l0.9-3.6 l6.7,3.1l4.8,4l3.9-5.5l7.2-0.5l10.2,3.8l7.5,6.2l-2.1,5.5l0.5,4.3l7.1,6.7l3.6-1.5l6.1,0.6l6.1,2.3l1.2,3.1l4.6,0.7l3.6,2.9l1.2,3 l5.2,1.4l5.7,4l6.5,0.1l7.8-3.8l-3.3,7.5l-0.9,6.6l-3.9,8.6l-1.8,19.1l2.5,4.9l-5.4,1.3l-4.1,3.3l-7.3-2.7l-3.7,1l-3.4,1.8 l-2.5,2.9l-0.1,5.1l-2,4.4l-2.1,3.4l-0.5,4.3l-2.1,3.3l-1.8,0.9l-0.1,3.1l-4.4,4l-0.9,2.9l-3.6,3.1l-3.3,1.5l-2.2-1.5l-3-0.1 l-2.6-2l0.1-2.9l-3.4-0.6l-2-2.7l-1.7,0.2l-1.2-1.5l-4.1,1.4l-1.4,1.4L3031.1,3319.5L3031.1,3319.5L3031.1,3319.5L3031.1,3319.5 L3031.1,3319.5z"
						/>
						<path
							id="path1089"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="velez municipios mapaClick"
							d=" M2956.4,3519.9l2.6,1.7l-0.2,3.7l4.8,2.1l1.1-1.3l2.6,2.5l6.9-0.3l1.9-0.2l1.4,1.9l0.2,4.3l5.3,6.7l0.8,3.6l2.4,0.6l2,2.6l-0.9,5.2 l-1,2.5l4.7,2.7l0.2-3.6l1.9-2.4l2-5.6l2.2-3.2l4.7-12.1l2.2-3.5l1.9-0.9l3.6-4l3.6-1.2l1.9-2.8l3.1-2.5l-0.9-3.6l1.2-1.7l2.3-0.3 l4.1-3.6l4-1.1l1.7-3.4l-3.2-3.7l-2.9-5.4l-4.8,2.3l-5.1,4.8l-5.2,1.6l-5.3-0.3l-9.7-6.5l-7.2,0.9l-5.6-4.3l-7-0.4l-2.3-2.4l3-4.5 l0.7-3.6l6.5-12.2l5.6-5.5l0.9-1.9l5.1-5.2l3.6-8.1l1.1-4.4l1.6-1.8l1.9-7.9l2.7,0.6l4.9-5.2l-1.3-0.7l-5.6,0.9l-4-0.7l-3.2-1.4 l-3.3-0.1l-4.9-3.9l-2.5-3.5l-3.1-0.3l-2.1-3.1l-1.4-5.8l-1.7-4.4l0.8-1.6l-0.9-7.7l0.4-4.6l2.2-6.1l1.9-4.2l-1-4.8l0.7-6.5 l-0.8-4.2l-1.4-3.8l0.8-2.5h-4.4l-1.9-4l-0.7-8.8l-3.9-1l-5.5-4.3l-2.6-7.2l-1.9,2.6l-3.6-1.7l4.3-6.2l-1-4l2.6-4.1l0.5-3.8 l2.2-0.9l2.2-5.3v-4.1l4.4-3.8l-0.2-3.1l5.3-4.3l0.5-2.6l2.4-0.3l9.4-11.4l-3.4-2.2l2.9-1.9l3.2,0.5l1.2-9.6l-2-4.1l0.5-4.6 l2.9-4.1l2.6-0.3l0.3-2.9l7.3-8.6l-2.7-4.8l3.1-1.2l-4.8-2.4l-0.9-5.2l-4.4-4.6l-5,0.3l-6.3-2.1l-3.9,2.1l1.7-7.7l-3.6-2.1l1.9-4.5 l-3.1-3.1l-2.6,1.2l-5.6-2.2l-2.4-2.8l-3.9,1.4l-0.7-6.9l-5.1,1h-7.9l0.5-4.5h-3.9l-5.1-4.5l-5.1,2.2l-3.9,0.2l0.5-7.4l2.7-6 l-2.6-0.7l-2.4,3.3l-6.7-1.9l-1.6,2l1.7,3.6l-0.2,4.3l-2.7,11.9l1.5,4.3l-1.5,5.5l2.6,6.9l3.9-1.7l3.2-3.8l3.1,0.7l1.5,7.9l3.6,1.2 l4.6,10.5l4.4,6.5l6.8,5.5l1.2,2.6l5.7,4.6l3.8-0.2l3.7,3.7l11.1,5.4l2,4.3l-0.3,4.3l-4.3,3.3l-3.7-2.6l-4.1,13.6l-4.8,1.2l1,4.8 l-1.7,4.3l-4.3,4.3l-3.9,8.4l-4.3,3.1l-7,11.5l-1.2,7.2l-3.7,6.4l4.3,1.7l3.6,4.6l1.2-3.3l5.1-1l0.7,4.1l-2.7,2.7l-2-0.7l-3.9,4.6 l-0.5,4.5l4.1,1.9l2.9,3.4v4.8l2.7,5.5l-1,5.3l-4.7,4.5l-2.8,1l-4,4.1l-0.2,2.7l-1.4,0.9l-3.6,3.7l-6.3-0.3l0.2,3.6l-1.6,6.8 l1.4,6.1l5.8,2.4l2.7,3.8l1.7,5.2l4.1,1.6l5-0.4l3.1,4.3l0.1,5.1l-0.5,4.1l1.4,2.6l0.1,4.9l-3.6,3.6l-4.2,0.2l-4.3,4.8l0.5,2.6 l-1.4,5l1,2.2l1.5-0.3l1.5,2.1l-1.4,2.6l3.2,4.5l-0.2,4.1l1.2,7.4l1.7,5l1.4,5.6l4.2,1.6l3.4,3.5l-3.7,7.1l-2.3,2.5l-2.1-0.5 l-2.5,0.9l-1.8,3.3l-5.9,3.3l1.1,2.5l8.3,1.6l6.1,4L2956.4,3519.9L2956.4,3519.9L2956.4,3519.9z"
						/>
						<path
							id="path1091"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="la-paz municipios mapaClick"
							d=" M3004.8,3434.7l0.2,1.3l2.6-0.2l3.4,0.9l-0.2,1.9l3.2,2.1l1.6-1.5l2.4,0.4l2.6,2.8l3,1l2.8,6l2.4,1.3l4.4,0.9l0.3,2.9l2.5,5.3 l4.3,1.8l4.5-0.1l2.1-2.2l2.9-0.1l-0.9-2.4l0.8-1.6l3.1,0.3l-0.3-2.1l3.1-3.1l3.3-1.3l2.4-2.2l-2.1-2.9l-1.8-3.4v-2.4l-3.6-3.2 l-0.2-1.9l2.3-4.9l-3.1-2.6l-0.7-2.2l2.1-0.7l-0.1-1.5l-3.1-2.9l-0.4-1.8l2.2-5l0.2-2l1.8-0.3l0.5-3.1l1.3-1.7l-0.1-2.5l-3-3.2 l-0.3-2.2l-1.8-1.4l-0.4-5.1l2.2-5l-0.2-6.4l-2.5-3.3l-3.7-0.6h-2.7l-1.4-5.7l-1.6-1.7l1.6-4.5l-1.1-4.4l-2.5,0.1l-3.3-2.5 l-6.3-2.2l-2.5-2.7l-3-0.9l-3.7-5.7l-2.6-0.5l-3.1-2.9l-1.7,0.3l-1.5-2.8l-3.9-1.8l-1.6,2l-3.8-0.5l-2,1.3l-4.4-0.3l-1.9,4.1 l-0.3-3.2l-2.2-4l-0.7-4.8l2.3-2.9l-3.1-1.8l-2.6-0.7l-0.9-2.6l1.4-4.5l2.2-5.3l3.2-3.8v-4.6l-3.1-4.1l-0.2-5l2.2-4l-2-9.6 l-2.4-0.7l-9.4,11.4l-2.4,0.3l-0.5,2.6l-5.3,4.3l0.2,3.1l-4.4,3.8v4.1l-2.2,5.3l-2.2,0.9l-0.5,3.8l-2.6,4.1l1,4l-4.3,6.2l3.6,1.7 l1.9-2.6l2.6,7.2l5.5,4.3l3.9,1l0.7,8.8l1.9,4h4.4l-0.8,2.5l1.4,3.8l0.8,4.2l-0.7,6.5l1,4.8l-1.9,4.2l-2,5.4l-0.7,5.3l0.9,7.7 l-0.8,1.6l1.7,4.4l1.4,5.8l2.1,3.1l3.1,0.3l2.5,3.5l4.9,3.9l3.3,0.1l3.2,1.4l4,0.7l5.6-0.9l1.3,0.7L3004.8,3434.7L3004.8,3434.7 L3004.8,3434.7L3004.8,3434.7z"
						/>
						<path
							id="path1093"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccc"
							class="chipata municipios mapaClick"
							d=" M3041.1,3465l-3.2,2.8l-0.8,3.7l-1.9,1l1.4,2.9l-4.6,5.2l-1.3,3.4l0.4,4.4l1.1,2.5l-7.8,2.5l-4.8,2.3l-5.1,4.8l-5.2,1.6l-5.3-0.3 l-9.7-6.5l-7.2,0.9l-5.6-4.3l-7-0.4l-2.3-2.4l3-4.5l0.7-3.6l6.5-12.2l5.6-5.5l0.9-1.9l5.1-5.2l3.6-8.1l1.1-4.4l1.6-1.8l1.9-7.9 l2.7,0.6l0.2,1.3l2.6-0.2l3.4,0.9l-0.2,1.9l3.2,2.1l1.6-1.5l2.4,0.4l2.6,2.8l3,1l2.8,6l2.4,1.3l4.4,0.9l0.3,2.9l2.5,5.3l4.3,1.8 L3041.1,3465L3041.1,3465L3041.1,3465z"
						/>
						<path
							id="path1095"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="jesus-maria municipios mapaClick"
							d=" M2935.3,3568.3l1.7,4.3l2.3,1.3l0.1,3.7l6.7,9.3l-3.9,1.5l0.2,3.8l-5.6,3.6l-4.1-1.7v5.5l-3.1,2.4h-5.1l-7.7,5.5l-4.4,0.3l-3.3,5.1 v4.9l-1.7,5.2l-5.2,0.7l-4.2,5.8l-4.7,3.9l-2.3,3.4l-6.1-2.6l1.3-4.5l-4.7-0.9l-2.2-2.3l-3.2,1l0.1-3l-1.5-3.5l-2.5,0.2l-5.5,3.6 l-1.4-2l-0.2-2.9l-1.6-3l0.3-3l0.2-3.3l-1.7-2.6l1.3-1.8l-0.5-3.3l1-1.8l3.6-0.9l2.7,0.8l0.6-3l4.6-1.6l3.2,2v3l3.5,1.8l2.2,2.8 l4.2-0.5l3.4-1.8l2.9-3.2l3.9-2.6l2.6-6.9l2.6-2.2l4.9-1.4l-1.9-5.2l-3.4-4.3l5.7-1.4l2.2-2.9l4.5-2.6l2.5-2.7l1.4-3l2.8-1.6 l5.3,1.8l3.4,2.9L2935.3,3568.3L2935.3,3568.3z"
						/>
						<path
							id="path1097"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="suavata municipios mapaClick"
							d=" M2939.5,3577.6l5-0.7l6.7-7.9l3.4,1l2.7-4l2.9-0.5l4.3,9.1l5.5,0.2l5-7.2l8.9-6.5l2.7,0.3l4.7-3.5l-4.9-2.6l1-2.5l0.9-5.2l-2-2.6 l-2.4-0.6l-0.8-3.6l-5.3-6.7l-0.2-4.3l-1.4-1.9l-1.9,0.2l-6.9,0.3l-2.6-2.5l-1.1,1.3l-4.8-2.1l0.2-3.7l-2.6-1.7l-1.8,0.7l-2.8-1.5 l-3.3-2.5l-8.3-1.6l-1.1-2.5l-3.7,0.4l-0.3,6.5l-1.4,3.3l1.2,4.6l0.2,4.3l3.8,7.2l-3.5,2.9l-2.1,4.8l-3.4,2.6l2.7,7.2l0.2,4l3,1.1 l0.3,2.5l-0.7,4.3l1.7,4.3l2.3,1.3L2939.5,3577.6L2939.5,3577.6z"
						/>
						<path
							id="path1099"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="aguada municipios mapaClick"
							d=" M3063.3,3365.6l3.4,1.3l2,1.9l1.6,3.1l-0.6,3.5l0.7,4.4l3,3.8l6,3.5l3.8,0.1l5.3,2.4l3.9,7l3.5,2.5l0.3,3.4l-2.3,4.4l0.7,2.3 l0.8,4.1l-1.4-0.3l-0.5,1.2l-2.1-2l-3,3.6l-3.4,2.7l-0.8,1.7l-1.5,0.4l-0.8,1.9l-3.2,2.2l-4.4-1.7l-2.2,2.9l-2.5,1.3l1.5,4.5 l0.3,3.2l-4,2.5l-1.3,2.2l-0.8,5.1l-3.9,2.7l-2.1-2.9l-1.8-3.4v-2.4l-3.6-3.2l-0.2-1.9l2.3-4.9l-3.1-2.6l-0.7-2.2l2.1-0.7l-0.1-1.5 l-3.1-2.9l-0.4-1.8l2.2-5l0.2-2l1.8-0.3l0.5-3.1l1.3-1.7l-0.1-2.5l-3-3.2l-0.3-2.2l-1.8-1.4l-0.4-5.1l2.2-5l-0.2-6.4l-2.5-3.3 l-3.7-0.6h-2.7l-1.4-5.7l-1.6-1.7l1.6-4.5l-1.1-4.4l4.7,3.5l3.8,1.3l2.2,1.5l2.4,3.6l2.5-1.5l2.4-3.8l3.6-1L3063.3,3365.6 L3063.3,3365.6L3063.3,3365.6z"
						/>
						<path
							id="path1101"
							inkscape:connector-curvature= "0"
							class="lebrija municipios mapaClick"
							d="M3286.7,2782.6l1.4,10.3l4,5l-2.3,5.6l0.2,5.6l1.5,4.7 l-5.2,6.3l-6.7,5.8l-3.4,1.8l2,6.2v6.9l-2.2,5.8l-5.5,10.2l-2.3,5.2l0.3,5.8l-2.3,2.1l1.7,4l-1.4,2.4l1,2.8l-1.1,1.7l1.1,2.4 l-3.8,2.1l-3.1-0.3l-3.8-0.9l-4.2,2.2l-4.1-0.5l-2.7-4l-4.4-2.1l-6.1-0.7l-1.1-3.8l-2.6-3.3l-0.2-3.9l-1.5-1l-0.3-6.1l-2.4,0.1 v-3.1l-7.5-4.6l-8-8.6l-3.6,0.2l1-4l-4.9-3.6l-4.6,0.2l-3.3,2.1l-0.3,4.1l-5.3-3.6l-4.9-0.9l-5.9-3.4l-3.4-4l-0.3-4.9l-1.6-1.4 l-6.3-1l-3.8-2.4l-4.1-0.1l-2.3-2.1l-4.8-2.4h-4.9l-3.1,2.1l-2.4-2.2l-2.7-4.1l-4,6.7l-5.2-2.1l1.5-3.1l-2.8-1.9l0.9-3.6l-2.6-3.4 l0.2-2.1l-2.5-2.2l2.2-5.7l5.5-4.1l5.6-1.4l2.7-2.9l5.6-2.2l25.3-43.5l-2.7-7.7l-0.3-5.3l-5.5-7.1l-0.3-7.7l3.2-5.5l-2.2-3.8 l0.9-3.4l-3.4-3.3l2-1.9l-2.2-3.8l1.2-6.7l-3.6,2.2l-3.6-4l4.4-7.7l3.2-1.9l-10.2-8.8l3.8-2.8l2.4,0.7l3.1-1.4l1-1.9l-0.7-2.2 l1.2-1.7l2.4-0.7l4.8,0.5l1.2-2.9l2.7,1.7l1.7,4.5l-0.5,6.7l0.7,4.5l2.4,1.7l1.2,4l-0.9,4.1l-2.6,3.3l-0.3,3.1l1.5,3.6l0.5,4.6 l2.7,1.7l-0.2,2.2l-2.6,2.2l-1.2,2.9l1.2,2.8l3.1,1.5l7,0.3l2.6-4l5-5l4.3-1.5l0.3-3.6l2.4-0.2l0.5,3.4l3.8-0.3l2.2,2.2l3.4-0.5 l1.7,2.1l-0.5,2.8l-3.1,1.5l0.5,2.6l3.6,1.4l1.9,2.4l0.3,5.2l2,2.2l0.2,6.2l1.9,2.1l1,5.3l3.9,4.8l4.1,1l0.9,3.3l3.1,5l-0.5,4.1 l1.5,5.7l-0.3,7.7l0.7,2.6l1.7,1l1.7-1.2l2,0.3l4.6,3.6l3.2,4.6l2.4,0.5l2.6,2.2l4.4-0.5l4.4,0.2l4.6,1.4l3.9,3.3l3.2,0.5l3.2,2.1 l3.9-3.1L3286.7,2782.6L3286.7,2782.6L3286.7,2782.6z"
						/>
						<path
							id="path1103"
							inkscape:connector-curvature= "0"
							class="matanza municipios mapaClick"
							d="M3397.2,2720.2l-3.8,4.1l-5.5,8.9l-5.3-0.8l-3.5,2.4l-5,0.6 l-7.7,7.5l-4.2,0.6l-3.5-1.6l-2.4,0.8l-3.3,4l-2.4,4.6l0.3,6l-1.3,1.7l2.3,3.8l3,2.2l-0.5,2.9l0.9,1.9l-2.3,3.2l0.7,6.8l-2.5,2.1 l-3.3,0.9l-0.9,4.4l-2.8,1.6l-1.6-2.1h-2l-7.6-5.3l-0.3-2.5l1.5-3.1l2.5-2.6l1.1-3.1l-0.5-4.7l1.1-2.1l-0.2-5.5l-2-2.9l1.7-2.2 l1.5-3.1l-0.5-5l-5.5-6.9l-3.8-1.7l-8-0.2l-1.9-1.5l3.4-4.1l-3.2-4.8l0.3-7.1l1.5-2.6v-2.4l-3.9-4.3l-0.7-2.2l-3.4-1.2l-2.7,1 l2.2-5.3l-0.5-7.2l2.7-1.9l0.3-2.8l2.6-2.9l0.7-2.9l2-2.2l0.2-5.8l5-5l4.1-7.1l0.7-3.8l4.4-6.4l2-6.4l-0.7-4.3l-3.8-3.6l-1-3.1 l0.5-3.8l4.8-3.8l2.1-1.9l0.6,2.4l1.9,0.2l0.9,3.4l2,2.2l6.1-0.5h5.8l2.6,0.9l2.2,1.2l7.2,7.7l3.2,0.7l5,4.5l2.4,0.2l1.4,3.1 l-0.2,2.4l1.9,0.3l2.4,2.4l2.6,1.4l-1.7,3.1l0.2,10l-1.9,3.8l0.7,4.1l-1.9,4.6l-2.6,4.1l-3.1,2.4l-0.3,3.3l6,5l3.8,5.5l-2,3.3 l3.2-1.7l1.5-1.9l2.6,2.2l3.2,0.5l3.1,2.9l-1.2,3.3l1,2.6L3397.2,2720.2L3397.2,2720.2L3397.2,2720.2z"
						/>
						<path
							id="path1105"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="california municipios mapaClick"
							d=" M3464.5,2683.2l-2.9,0.7l-5.8-2.4l-3.9,0.7l1.7,3.4l-1.7,2.8h-2.9l-7.3,6.7l-7.5,1.9l-4.3,3.8l-0.7,4.6l-3.2,0.3v3.4l-1.7,3.3 l1,7.6l4.1,1.7l4.1,4.1v8.3l-6.1-5.5h-1.9l-2.4-2.6l-1.9-0.3l-4.8-5l-3.2-0.5l-4.3-4.8l-4.3-4h-1.7l-6.5-8.6l0.7-1l-0.5-4l3.6-1.9 l2.7,1l5.5,2.9h6.1l3.1-1.9l4.1-7.4l1.7-5.5l4-3.1l1.7-2.4l2.2-2.6l2.2,1.2l1-2.2l3.1-3.8h3.2l2.7-2.9l2.2,0.2l1-1.4l1.5,0.7 l1.5-1.2l3.1,1.4l1.7,4.1l2.8-1l5.1,7.6l2.8,0.9L3464.5,2683.2L3464.5,2683.2z"
						/>
						<path
							id="path1107"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccc"
							class="vetas municipios mapaClick"
							d=" M3451.5,2752.5l-6.3-6.4l-3.2-1l-2.6-4.8l-2.2-0.5l-1-2.8l-2.7-2.9v-8.3l-4.1-4.1l-4.1-1.7l-1-7.6l1.7-3.3v-3.4l3.2-0.3l0.7-4.6 l4.3-3.8l7.5-1.9l7.3-6.7h2.9l1.7-2.8l-1.7-3.4l3.9-0.7l5.8,2.4l2.9-0.7l0.7-2.8l4.3,1.1l5.1,6.2l-1.1,3.8l4.9,2.4l1.8-0.7l2.2,1.8 l0.3,4l-3.5,2.7l1.8,18.6l-2.6,1.1l2.7,6.1l3.6,1.3l0.9,2.2l-5.5,3.3l-1.8,0.1l-5.1,4.7l-0.8,2.7l-2.1,1l-3.9-0.7l-4.9,4.1 l-1.1,4.9l-1.8,1.6L3451.5,2752.5z"
						/>
						<path
							id="path1109"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="charta municipios mapaClick"
							d=" M3458.1,2753.8l-7.2,3.7l-2.7-0.9l-2,3.8l-2.9,3.6l-4.8-4.3l-2.7-0.7l-6,2.2l-7.5,1.5l-6.7,4.1l-3.9,0.2l-1.9,1.2l-2.2-1.9 l-2.7-0.5l-3.4-4.3l-1.7-3.3l-5.3-0.3l-8.4,6.9l-3.2,0.7l-2.2,3.3l-0.2,2.8l-3.4,1.2l-1.2,1.4l-0.2,5.5l-2.4,6.9l-4.6,5.7l-3.1,0.3 l-3.4,5.2h-2.4l-2.7-3.6l-2.2,0.2l-3.9,4.1l-3.1-0.5l-5.3-2.4l-0.3-3.4l1.9-3.4l2.8-1.6l0.9-4.4l3.3-0.9l2.5-2.1l-0.7-6.8l2.3-3.2 l-0.9-1.9l0.5-2.9l-3-2.2l-2.3-3.8l1.3-1.7l-0.3-6l2.4-4.6l3.3-4l2.4-0.8l3.5,1.6l4.2-0.6l7.7-7.5l5-0.6l3.5-2.4l5.3,0.8l5.5-8.9 l3.8-4.1l3.8-2.6l3.1,0.3l5,3.8l2.2,2.9l1.7-0.9l3.4,1l0.5,2.1l2,1l4.6,5.3l2-0.7l1.5,1l6.3,0.5l2.7,2.9l1,2.8l2.2,0.5l2.6,4.8 l3.2,1l6.3,6.4h6.9L3458.1,2753.8z"
						/>
						<path
							id="path1111"
							inkscape:connector-curvature= "0"
							class="piedecuesta municipios mapaClick"
							d="M3383.3,3015.2l0.1-4.3l-3.2-0.3l-1.5-2.2l-1.9-3.8l-2.4-0.3 l-5.8-3.6l0.2-4l-1.7-0.5l-1.5,2.1l-3.8-1.2l-2.4-4.1l-0.7-3.3l-4.3-4l-3.6,0.2l-4.4-4.8l-5.8-2.2l-2,0.2l-2.2-1.7l-1.7,0.2 l-2.4-2.2l0.3-2.6l-3.4-2.2l-3.9-3.1l-1.9-3.3l4.4-4.3l-2-4.1l0.7-5.5l6-2.9l-2.6-2.4l-3.9-0.9l-0.7-2.6l-2.2-1.2v-5.8l-0.9-1.5 l2-3.3l-1.4-0.7l-2.9,2.2l-2.6-2.6l-0.2-4.1l3.9-5.5l-2.4-8.1l-0.2-8.8l3.9-3.4l5.3-0.3l-0.3-4l2-1.2l1.2-3.1l7.9,7.1l2.9-4.6 l-1.5-4l5.1-0.3l5.1-1.5l3.1-2.8l3.2-0.9l0.2-3.6l4.4-4l3.6-5.8l4.6-3.1l1.4-7.7l0.2-5.7l1.7-1.9l2.4,0.5l3.2-2.9l3.2,1.2h3.6 l0.2-2.6l1.7-2.4l2.2-1.9l2.2,2.4h2.2l2.4-1.5l4.8,0.2l2,2.2l3.8,1l3.2-1.2l-1.4,3.3l1.4,1.5l-0.2,5l1.2,2.8l-2,3.3l4.3,4.5h4.4 l1.2-4l-1.4-4.3l5.1-7.6l6.7,5l7.9,3.4l2.2,4.3l1.7,4.3l-0.9,4l-5.1,8.6l2.4,4.3l-1.4,4.8l0.3,9.1l-4.6,8.3l1,6.4l-8.5,13.6 l-4.8,11.9l-2.4-1.2l-6.3,4.5l-6.1,0.3l-3.9-5.2l-3.6-0.2l-3.2,8.6l-4.1,6.2l-1.5,6.2h-3.1l2,3.6l-1.9,2.6l0.3,4.6l2.4,10.2 l1.5-0.3l1.9-3.8l4.8-4.6l1.2-9.3l2.9-2.9l2.9-0.2l3.9-2.2l-0.9,3.6l1,2.2l-2.2,2.2l2.4,2.9l5.5-0.3l2,2.1l-5.3,10.2l2.2,2.2l3.1,1 l4.3,4.3l-2.2,3.3l-0.7,3.3l2.9,0.7l2.9-2.9l3.1,1.2l7-4.1l3.8-4.3l2,14.9l-3.7,8.3l-6.9,9.4l-0.8,5.9l-2.7,5.4l-5.5-4.9l-1.6,7 l-2.7,1.1l-2.5,5.8l-6.8,0.8l-8-1.5l-0.9-10.4l-6.3-3.8L3383.3,3015.2L3383.3,3015.2L3383.3,3015.2z"
						/>
						<path
							id="path1113"
							inkscape:connector-curvature= "0"
							class="los-santos municipios mapaClick"
							d="M3283.5,2981l7.9-4.7l9.9-3.4l5.1-2.9l2.9,1.2l10.6-6.5 l3.4-0.7l1.9,3.3l7.3,5.3l-0.3,2.6l2.4,2.2l1.7-0.2l2.2,1.7l2-0.2l5.8,2.2l4.4,4.8l3.6-0.2l4.3,4l0.7,3.3l2.4,4.1l3.8,1.2l1.5-2.1 l1.7,0.5l-0.2,4l5.8,3.6l2.4,0.3l1.9,3.8l1.5,2.2l3.2,0.3l-0.1,4.3l-2.3,2.6l-0.2,4l-2.8,2.4l-1.5,4.7l-4.9,9l0.1,5.2l-6.3,4.7 l-3.6,6.6l-4.3,1l-5.1,6.9l-3.7,1.1l-5.3,7.5l-15,1.5l-4.7,3.6l-4.5-2.9l-8.4,1.4l-4.1-1.2l-8.6-0.2l-13.8-4.6l-3.3-11.1l-3.6,0.3 l-2.8-4.1h-5.4l-1.8-3.9l-0.8-9.8l1.5-8.5l8.2-7l2-8.5l3.3-1.3l0.8-4.1l3.1-3.9l0.5-8.3l-1.3-7.2L3283.5,2981L3283.5,2981 L3283.5,2981z"
						/>
						<path
							id="path1115"
							inkscape:connector-curvature= "0"
							class="floridablanca municipios mapaClick"
							d="M3361.3,2827l-5.5,2.2l-2.2,1.9l0.5,2.2l-3.1,1.7l-0.2,5.2 l-4.4,0.5l-2.9-2.8l-4.3-0.5l-2.9,4.3l-0.2,4.1l-2-0.7l-7.5,8.1l-3.6,1.5l-0.7,1.9l-5,6.4l-1.5,4l-5.8,6l-3.6,0.7l-0.9,1.7l3.1,2.1 l1.6,3.4l-1.5,1.8l2.2,1l-0.7,4.6l1.6,1.6l2.4-1.3l1.8,1.3l-2.8,2.4l2.7,3.1l1.5,1.9l-0.6,1.4l1.5,2.9l3.9-3.4l5.3-0.3l-0.3-4 l2-1.2l1.2-3.1l7.9,7.1l2.9-4.6l-1.5-4l5.1-0.3l5.1-1.5l3.1-2.8l3.2-0.9l0.2-3.6l4.4-4l3.6-5.8l4.6-3.1l1.4-7.7l0.2-5.7l1.7-1.9 l2.4,0.5l3.2-2.9l3.2,1.2h3.6l0.2-2.6l1.7-2.4l-3.2-1.4l-1.5-5l-6.8-4.8l-1.9-4.1l-0.5-4.3l-4.1-1.4l-3.6-2.4l-1.2,3.4L3361.3,2827 L3361.3,2827L3361.3,2827z"
						/>
						<path
							id="path1117"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="bucaramanga municipios mapaClick"
							d=" M3291.2,2779.9l1.5,5l1.3,0.7l-0.5,3.9l1.1,0.9l-0.7,3.7l1.8,5.2l-1,2.6l0.7,2.8l2.2,5.5h1.9l-0.3,5l-6.5,7.7l0.1,4l-1.4,4 l-2.4,4.8l0.3,3.4l-2.3,3.7l-0.1,3l-4,4.2l4.8,4.6l6.7,1.9l0.9,4l4.9,1.1l4.6,2.9l3.5-2.2l3.8,0.4l-1.2,3.2l0.5,2.8l1.8,1.1 l2.7-2.8l1.5-4l5-6.4l0.7-1.9l3.6-1.5l7.5-8.1l2,0.7l0.2-4.1l2.9-4.3l4.3,0.5l2.9,2.8l4.4-0.5l0.2-5.2l3.1-1.7l-0.5-2.2l2.2-1.9 l5.5-2.2l-5.5-4l-6.1-0.5l-3.6-1.7l-0.7-7.1l0.2-6l3.2-4.3l2-5l-3.1-0.5l-5.3-2.4l-0.3-3.4l1.9-3.4l-1.6-2.1h-2l-7.6-5.3l-0.3-2.5 l1.5-3.1l2.5-2.6l1.1-3.1l-0.5-4.7l1.1-2.1l-0.2-5.5l-1.9-3.1l-2.8-0.4l-3.6,1l-8.4,8.3v3.4l-2.6,4.5l-4.4-0.2l-1.5,3.4l0.5,2.6 l-5.3,4.5h-3.1l-1.5,3.6l-2.9-0.5l-6.1-4L3291.2,2779.9L3291.2,2779.9L3291.2,2779.9z"
						/>
						<path
							id="path1119"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="giron municipios mapaClick"
							d=" M3283.5,2981l-8.7-11.4l-6.4-1l-3-2.6l-9.2-11.1l-4.1-6l-2.6-6.5l-5.8-6.5l-7.9-2.8l-5.6-3.4l-9.9-10.5l-3.1-10l-5.3-6.9l-4.6-4.8 l-5-2.1l-4.8-0.9l-4.1-3.1h-4.3l-7.5-4l-2.7-4.6v-3.4l2.4-3.1l-3.1-4.8l-4.4,1.7l-9.7-0.5l-8.2-6.5l-6-7.6l0.3-4.5l-4.4-3.4 l-5.3-1.2l-7.7-6.2l-6.5-3.3l-1.9-4.5v-3.3l-2.6-2.6l-3.9,1.2l-7.3-5l-7-2.4l-6-0.5l-3.4-2.6l-8.7,1.7l-7.5,3.4l-6-6.2l-4.3-1.7 l-5.1,0.2l8.9-4.3l0.9-2.9l4.8-1.2l2.2,1.7l7-1.7l6.8,3.8l4.8-4.3l3.9,2.8l3.8-3.4l1.7,4.8l4.6,1.7l1.7-5.7l3.2,2.4l4.6-0.9 l-1.2-4.6l1.9-3.8l3.6-0.5l2.5,2.2l-0.2,2.1l2.6,3.4l-0.9,3.6l2.8,1.9l-1.5,3.1l5.2,2.1l4-6.7l2.7,4.1l2.4,2.2l3.1-2.1h4.9l4.8,2.4 l2.3,2.1l4.1,0.1l3.8,2.4l6.3,1l1.6,1.4l0.3,4.9l3.4,4l5.9,3.4l4.9,0.9l5.3,3.6l0.3-4.1l3.3-2.1l4.6-0.2l4.9,3.6l-1,4l3.6-0.2 l8,8.6l7.5,4.6v3.1l2.4-0.1l0.3,6.1l1.5,1l0.2,3.9l2.6,3.3l1.1,3.8l6.1,0.7l4.4,2.1l2.7,4l4.1,0.5l4.2-2.2l3.8,0.9l3.1,0.3l3.8-2.1 l-1.1-2.4l1.1-1.7l-1-2.8l1.4-2.4l-1.7-4l2.3-2.1l-0.3-5.8l2.3-5.2l5.5-10.2l2.2-5.8v-6.9l-2-6.2l3.4-1.8l6.7-5.8l5.2-6.3l-1.5-4.7 l-0.2-5.6l2.3-5.6l-4-5l-1.6-10.3l4.6-2.7l1.5,5l1.3,0.7l-0.5,3.9l1.1,0.9l-0.7,3.7l1.8,5.2l-1,2.6l0.7,2.8l2.2,5.5h1.9l-0.3,5 l-6.5,7.7l0.1,4l-1.4,4l-2.4,4.8l0.3,3.4l-2.3,3.7l-0.1,3l-4,4.2l4.8,4.6l6.7,1.9l0.9,4l4.9,1.1l4.6,2.9l3.5-2.2l3.8,0.4l-1.2,3.2 l0.5,2.8l1.8,1.1l-3.1,3.2l-3.6,0.7l-0.9,1.7l3.1,2.1l1.6,3.4l-1.5,1.8l2.2,1l-0.7,4.6l1.6,1.6l2.4-1.3l1.8,1.3l-2.8,2.4l2.7,3.1 l1.5,1.9l-0.6,1.4l1.5,2.9l0.2,8.8l2.4,8.1l-3.9,5.5l0.2,4.1l2.6,2.6l2.9-2.2l1.4,0.7l-2,3.3l0.9,1.5v5.8l2.2,1.2l0.7,2.6l3.9,0.9 l2.6,2.4l-6,2.9l-0.7,5.5l2,4.1l-4.4,4.3l-3.4,0.7l-10.6,6.5l-2.9-1.2l-5.1,2.9l-9.9,3.4L3283.5,2981L3283.5,2981L3283.5,2981z"
						/>
						<path
							id="path1121"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="mogotes municipios mapaClick"
							d=" M3462.8,3133.2l-6.2,1.1l-9-2.2l1.4-5.7l-2.7-2.9l-2.6,2.2l-2.4-1.9l-0.5-2.4h-4.1l0.2,6.5l1.2,3.6l-5.5,8.3l0.3,3.4l-4.1,0.3 l-2.4,4.8l-2.7,1.4h-3.6l-2.7-2.2l0.2-4.6l-2.7-1.7l-3.1,3.6l-7.3,0.9l-3.4,1.2l-4.4,5.3l1.5,5l-0.7,2.1l1.4,2.6l-0.3,5.2l-8.2,8.1 l-0.5,4.6l1.4,2.2l-0.7,1.9l1.2,2.2l-4.6,4.3l-4.3,1l-3.1-1.2l-0.7-2.2l-2.6,1l-2.4-1.7l-2,2.6l-2-0.2l-0.2,3.4l-4.1,1l-5.1,4.8 l-0.3,3.3l-3.9,4.1l-5.3,2.2l-2.9,2.9l-1,4l-2.4,2.9l0.2,2.8l-3.1,1l2,2.6l0.5,5.5l1.9,2.8l0.3,7.4l-1.4,5.3l0.5,8.4l-2,2.2 l-1.5,5.3l-0.5,4l-2.6,2.6l-0.9,2.8l-0.6,2.4l1,4.3l1.9,1.8l0.3,4.2h3.4l2.8-1.3l2.6,0.7l3-0.4l2.9,0.6l1.7-3.2l2.2,0.9l3-1.7 l2.5-2.5l1.2,2.1l5,0.7l3.9-4.3l2-0.5l1.5,2.1l2.4-0.5l1.9,2.6l-0.2,5.2l2.6,2.4v3.4l1.4,1v3.8l5,2.4l8-0.3l2.7-2.4h2.4l2.6-2.8 l2.6-0.5l2.6-1.9l5-2.2l5.8,0.7l4.3-2.8l3.9-3.8l6,2.8l2.6-0.2l-0.3-4.1l1.4-1.9l-1-2.9l-0.5-5.3l0.9-5.5l2.2-3.4l11.3-10.7 l0.7-3.4l4.6-4.5l3.8-4.3l-1.1-4.4l1.9-6.2v-4.1l-2.7-0.9l-2.4-1l-0.3-2.9l2-2.6l0.2-3.6l-4.3-0.7l-3.8-4.5l6.3-5.2l3.2-1l3.4,1.2 l1.9-5.3l0.5-6.2l2.7-2.4l0.3-3.8l4.3-4.6l-0.3-4.6l4.4-3.6l3.9,0.7l4.3-4.1l-2.8-5.8l-0.9-6.1l-3.7-3.8l-4.7-1.2l-7.4-6.2 l-1.4-5.8L3462.8,3133.2L3462.8,3133.2L3462.8,3133.2z"
						/>
						<path
							id="path1123"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccsccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="el-guacamayo municipios mapaClick"
							d=" M3059.7,3363.4l3.6-0.9v3.1l3.4,1.3l2,1.9l1.6,3.1l-0.6,3.5l0.7,4.4l3,3.8l6,3.5l3.8,0.1l5.3,2.4l3.9,7l3.5,2.5l0,0l2.9-2.7 l1.6-0.5l0.7-2.5l4.6-5.2l0.5-1.9l2-1.8l0.7-2.1l2.4-2.1l-0.5-1.4l2.7-5.6l0.6-0.8h1.4l-0.1-1.7l-0.9-1.2l-1.6-0.5l-1.6,0.2 l-1.8,0.5h-2.3l-2-1l-1-1.6l-0.3-1.9l1.2-2.6l0.3-1.7l1.5-4.2l-0.1-1.7c0,0-0.5-1.1-0.6-1.4c-0.1-0.3-1.2-1.8-1.2-1.8l-0.1-2.1 l-3.1-0.6l-2.1-0.9l-3.4-0.7l-3.4-1.1l-1.2-1l-1.5-0.1l-1.1,1.4l-2.5,2.1l-2.6,2.4l-3.2-0.5l-2.9-1.8l-0.8-1l-0.4-1l-0.8,0.6 l-1.2-1.5l-1-0.6h-1.2l-0.2-1.2l-3.6-0.3l-0.6-1l-1.6-0.7l-1-0.8l-1-0.1l-0.5-0.6l-0.8-0.1l-2.2-1.7l-0.4-1.3l-1.1-1l-2.9-1.5 l-1.2-2.7l-0.6-3.9l-0.9,1.9l-2.6,3l-0.4,2.4l-1.8,1l-2.1,2.9l-2.8-0.2l-1.3-1.6h-2.8l-4.2-1.8l-0.2-3l-5.2-3.4l-1.9-3.5l0,0l0,0 l0,0l0,0l-0.2-2.9l0.5-3.1l-2.4-1.5l-4.2,3.5l-3.2,0.8l-1.5-1.4l-6.9,3.9l-3.2-1.1v2.2l-1.4,1l0.8,2.9l-1.4,0.1l0.2,4.3l0.2,4.3 l1.8-2l3.9,1.8l1.5,2.8l1.7-0.3l3.1,2.9l2.6,0.5l3.7,5.7l3,0.9l2.5,2.7l6.3,2.2l3.3,2.5l2.5-0.1l4.7,3.5l3.8,1.3l2.2,1.5l2.4,3.6 l2.5-1.5L3059.7,3363.4L3059.7,3363.4z"
						/>
						<path
							id="path1125"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="san-joaquin municipios mapaClick"
							d=" M3487.2,3167.4l-4.3,4.1l-3.9-0.7l-4.4,3.6l0.3,4.6l-4.3,4.6l-0.3,3.8l-2.7,2.4l-0.5,6.2l-1.9,5.3l-3.4-1.2l-3.2,1l-6.3,5.2 l3.8,4.5l4.3,0.7l-0.2,3.6l-2,2.6l0.3,2.9l2.4,1l2.7,0.9v4.1l-1.9,6.2l1.1,4.4l-3.8,4.3l-4.6,4.5l-0.7,3.4l-11.3,10.7l-2.2,3.4 l-0.9,5.5l0.5,5.3l1,2.9l-1.4,1.9l0.3,4.1l1.9,3.4l3.1-0.7l3.9,2.6l7.5-7.7l6.3-0.3l5.8-2.1l2.7,0.7l3.4,2.2l4.6,0.5l5.1-3.1 l7.9-8.6l5,0.9l1-1.5l-1.9-3.3l2-2.1v-4l-1.7-3.1l1.7-1.7l0.2-3.4l-2-3.6l1.5-4.1l-0.7-5.2v-8.3l-0.9-4.5l1.5-4.5l-1.2-2.1l1.5-5.3 l3.1-3.3l-1.9-2.8l1-2.9l-0.5-2.2l-2.2-1.7l-1.5-2.9l1.2-1.5l-1.9-2.4l0.7-2.4l-1.7-3.1l1.6-4l-5.4-4.1l-2.5-5.7L3487.2,3167.4 L3487.2,3167.4z"
						/>
						<path
							id="path1127"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="aratoca municipios mapaClick"
							d=" M3345.6,3067.6l8.4,8l6.1,2.4l5.8,5.2l-0.9,7.4l-8.7-5.3l-5.8,2.1l-4.3-2.4l-9.2,3.4l-3.8-0.9l-5.1,4l-0.9,3.1l3.8,0.5l1.9,1.5 l3.8-0.3l2,2.9l-0.5,4.3l1.5,1.9l3.4-0.3l1.4,2.9l4.3,1.9l4.6-0.9l3.2,2.6l0.9,5.7l6.7,5.7l3.9-2.1l3.2,1.7l7.7-1.9l2.9,1.2 l4.1-2.1l2.4,4l3.2,0.7l2.6-4.1l4.4-3.8l7.5-2.4l6.1-5l1.9-3.8l5.1-5.3l5.4-3.3v-8.4l-8-6.6l-6.4-2l-4.7-5l-1.8-5.4l2-6.3l-3.5-3.3 l-4.6-0.6l0.2-5.3l3.1-4.6l-5.5-4.7l-0.6-6.6l1.7-2.9l-0.9-10.4l-6.3-3.8l-6.1-5.6l-2.3,2.6l-0.2,4l-2.8,2.4l-1.5,4.7l-4.9,9 l0.1,5.2l-6.3,4.7l-3.6,6.6l-4.3,1l-5.1,6.9l-3.7,1.1L3345.6,3067.6L3345.6,3067.6L3345.6,3067.6z"
						/>
						<path
							id="path1129"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="curiti municipios mapaClick"
							d=" M3424.7,3096.7l-5.4,3.3l-5.1,5.3l-1.9,3.8l-6.1,5l-7.5,2.4l-4.4,3.8l-2.6,4.1l-3.2-0.7l-2.4-4l-4.1,2.1l-2.9-1.2l-7.7,1.9 l-3.2-1.7l-3.9,2.1l-6.7-5.7l-0.9-5.7l-3.2-2.6l-4.6,0.9l-4.3-1.9l-1.4-2.9l-3.4,0.3l-1.5-1.9l0.5-4.3l-2-2.9l-3.8,0.3l-1.9-1.5 l-3.8-0.5l-1,2.6l-3.3,1.9l-3.2-0.7l-4.1,0.5l0.2,2.8l-0.9,5.5l2.6,2.2l-2,3.1l3.8,7.6l2.9,2.6l1.5,4l2.6,2.6l1.4,4.8l-2.9,6.7 l-0.2,3.1l-1.9,1.5l1.5,3.8l-2.2,2.4l0.7,2.6l-2.6,4.1l1,7.2l2,7.6l2.4,1l3.2-2.6l6-2.9l3.1,0.5l1,4.1l4.6,5.2l6.7,0.3l4.6,5.3h3.1 l5.6,3.6l3.6-0.2l2.7,2.2l2,0.2l2-2.6l2.4,1.7l2.6-1l0.7,2.2l3.1,1.2l4.3-1l4.6-4.3l-1.2-2.2l0.7-1.9l-1.4-2.2l0.5-4.6l8.2-8.1 l0.3-5.2l-1.4-2.6l0.7-2.1l-1.5-5l4.4-5.3l3.4-1.2l7.3-0.9l3.1-3.6l2.7,1.7l-0.2,4.6l2.7,2.2h3.6l2.7-1.4l2.4-4.8l4.1-0.3l-0.3-3.4 l5.5-8.3l-1.2-3.6l-0.2-6.5h4.1l0.5,2.4l2.4,1.9l2.6-2.2l2.7,2.9l-1.4,5.7l9,2.2l6.2-1.1l-2.1-7.9l-3.3-7.3l-5.8-5l-2.9-6l-8.1-1.5 l-7.7-3l-3.3-3.9L3424.7,3096.7L3424.7,3096.7L3424.7,3096.7z"
						/>
						<path
							id="path1131"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccc"
							class="jordan municipios mapaClick"
							d=" M3310.8,3074.5l-4.1-1.2l-7.7-0.2l0.2,2.7l-2,6.1l2,6.3l1.9,1.3l3.1,4.4l8.5,3.6l4.4-0.9l-1.4,2.3l4.1-0.5l3.2,0.7l3.3-1.9l1-2.6 l0.9-3.1l5.1-4l3.8,0.9l9.2-3.4l4.3,2.4l5.8-2.1l8.7,5.3l0.9-7.4l-5.8-5.2l-6.1-2.4l-8.4-8l-2.3,3.2l-15,1.5l-4.7,3.6l-4.5-2.9 L3310.8,3074.5L3310.8,3074.5L3310.8,3074.5z"
						/>
						<path
							id="path1133"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccsccccccccccccccccccc"
							class="villanueva municipios mapaClick"
							d=" M3319.3,3120l-3.6,1.2l0.2,4.3l-4.4,4.3l-5,3.4l0.1,4.2l-2.4,1.8l-1.7,3l-3,0.6l-1.9,2.3l-0.7,3.6l-2.2,2.2l-0.7-2.4l2.7-6 l-2.4-3.1l-2.4-0.5l-4.4-4.8l-2-3.7h-2.9l-2.1-3l-2.8-0.4l-3-3.5l-2.8-1l0.5-4.9l1.7-1.5l-2.4-3.1l-2.4-0.7l2-5.9l1.9-1.7l-0.1-3 l2.1-2.5l-2.4-5.5l-5-2.6l-0.2-3.8l1-3.6l2.6-2.4l-2.6-3.1l-5.8-1.5l-3.2-1.9l-2.2-2.2h-1.7l-2.2-2.6l3.1-4.3v-5.5l2.4-2.9v-3.4 l1.2-3.1l2.7-1.4l4.2,0.2l1.8,3.9h5.4l2.8,4.1l3.6-0.3l3.3,11.1l8.5,2.7l6.1,1.9l0.2,2.7l-2,6.1l2,6.3l1.9,1.3l3.1,4.4l4.6,2 l3.9,1.5l4.4-0.9l-1.4,2.3l0.2,2.8l-0.9,5.5l2.6,2.2l-2,3.1L3319.3,3120L3319.3,3120L3319.3,3120L3319.3,3120z"
						/>
						<path
							id="path1135"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="zapatoca municipios mapaClick"
							d=" M3253.8,3070.1l-2,3.8l-3.2,2.2l-1.5,3.4l-3.6,2.1l-4.3,0.7l-4.4,3.6l-0.2,4.4l-1.4,4.3l-7.5,0.3l-2.4-2.3l-6.3-2.8l-0.9-4.4 l-5.8-4.1l-3.1-5.3l0.3-4.5l-1.5-4.3l-6.7-4.8h-4.3l-5.1-1.5l-5.6-4l-0.7-4.8l2.7-2.8l-0.2-2.2l-4.3-3.3l-4.4-2.1l-5.8-1.2l0.3-3.8 l3.8-8.3l-0.2-10l3.4-9.8l5.1-5l0.3-7.4l2.2-16.7l2.2-4.8l-4.1-7.4l-0.3-5.3l-5.1-2.6l-4.1,1.9l-4.4-3.8l3.1-2.8l0.7-4.5l-3.8-2.1 l2.6-4.6l-11.1-0.2l-3.1-3.3l-0.9-4l-4.8-3.1l-0.2,3.8l-4.8,2.2l-2.6-2.1l-2.9,0.9l-5.1-3.4l-6,2.8l-7.2,1.2l-8.2-2.6l1.4-2.9 l3.6-0.5l3.9-5.7l0.3-4.6l2.6-2.4l4.1,0.5l6.3-2.1l4.8,1l4.3-1.9l10.6-7.2l3.9,1.9l5.1-0.9l4.6,3.1l4.1,0.3l5.5,3.4l2.9,4l-1.4,4.3 l-2.7,1.5l2.2,3.8l-6.7,11.7l-1,4.3l2.4,1.7l6.7,1.5l3.4,4.3l0.3,5.5l2.6,2.1l2.6,1.2l0.2,6.4l3.4,3.1l0.9,9.3l4.4-0.2l5.3-2.6 l-1.5,3.8l5,7.6l4.6-1l2.2,2.4l3.6,1.2l5.5,7.9l7.9-11.5l0.3-7.6l7-8.4l1.9-5.2l5.3-1.5l2.9-4l0.7-2.9l3.9-5.7l9.2,11.1l3,2.6 l6.4,1l8.7,11.4l1.3,10.1l1.3,7.2l-0.5,8.3l-3.1,3.9l-0.8,4.1l-3.3,1.3l-2,8.5l-8.2,7l-1.5,8.5l0.8,9.8l-4.2-0.2l-2.7,1.4l-1.2,3.1 v3.4l-2.4,2.9v5.5L3253.8,3070.1L3253.8,3070.1L3253.8,3070.1z"
						/>
						<path
							id="path1137"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="barichara municipios mapaClick"
							d=" M3294.8,3150.9l-4.1,3.1l-3.4-0.7l-4.8,0.5l-4.8,2.4l1,4.1l-7,7.2l-3.6,1.7l-5.9,6.1l-2.3,0.8l-3.7-0.5l-2.7,1.3l-0.9-5.4l-2.6-4.8 l-1.9-4.6l-4.6-5.2l-0.5-6.2l1.4-5.5l3.4-1.9l-2.7-4.1l-3.8-0.2l-2.4-0.7l-1.7-1.9l-3.4,2.2l-5.3-4.8l2.2-3.8l-1-4.8l1.5-3.4 l-1.2-2.4l-2.9-3.4l0.5-6.9l6.1-1.2l0.9-3.2l-2.4-2.5l-1.6-5.6l2.4-2.1l1.4-4.3l0.2-4.4l4.4-3.6l4.3-0.7l3.6-2.1l1.5-3.4l3.2-2.2 l2-3.8l2.2,2.6h1.7l2.2,2.2l3.2,1.9l5.8,1.5l2.6,3.1l-2.6,2.4l-1,3.6l0.2,3.8l5,2.6l2.4,5.5l-2.1,2.5l0.1,3l-1.5,1.7l-2.4,5.8 l2.4,0.7l2.4,3.1l-1.7,1.5l-0.5,4.9l2.8,1l3,3.5l2.8,0.4l2.1,3h2.9l2,3.7l4.4,4.8l2.4,0.5l2.4,3.1l-2.7,6L3294.8,3150.9 L3294.8,3150.9L3294.8,3150.9z"
						/>
						<path
							id="path1139"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccc"
							class="valle-de-san-jose municipios mapaClick"
							d=" M3337.6,3273.4l-3.9,2.6l-2.5-0.3l-2.5-1.9l-6,4.8l-5.8,2.6l-4.1-3.1l-1.7-4l-6.3-5.7l-3.6-0.3l-2.9,4.1l-6.5-2.1l-1.2-4.3l3.8-7.9 l0.2-8.6l4.3-9.3l3.2-2.2l2.9-4.6l7.2-3.8l2-2.9l5.1-3.6l3.1,0.9l5.8-2.1l6,0.5h7.5l2,2.6l0.5,5.5l1.9,2.8l0.3,7.4l-1.4,5.3 l0.5,8.4l-2,2.2l-1.5,5.3l-0.5,4l-2.6,2.6l-0.9,2.8L3337.6,3273.4L3337.6,3273.4L3337.6,3273.4z"
						/>
						<path
							id="path1141"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="san-gil municipios mapaClick"
							d=" M3319.3,3222.8l-2.7-4.5l-1.4-5l-4.1-1.2l-2.9,0.7l-3.8,3.4l-5.8-0.3l1-4.5l0.3-5.8l2.2-4.1l-1.2-3.6l3.2-2.2l-1.7-2.8l0.9-2.6 l-2.9-2.9l-1.4-4l-3.9-2.2l-0.2-5.3l-3.6,1.7h-2.9l-2.2,4l-3.6,1.5l-3.6-0.5l-2.2,2.2h-2.2l-2.2,2.6l-4.1,0.2l-1.9-1.1l1.1-1.6 l-1.6-3l-3.3-2.1l-2.9-3.5l2.3-0.8l5.9-6.1l3.6-1.7l7-7.2l-1-4.1l4.8-2.4l4.8-0.5l3.4,0.7l4.1-3.1l2.2-2.2l0.7-3.6l1.9-2.3l3-0.6 l1.7-3l2.4-1.8l-0.1-4.2l5-3.4l4.4-4.3l-0.2-4.3l3.6-1.2l2.9,2.6l1.5,4l2.6,2.6l1.4,4.8l-2.9,6.7l-0.2,3.1l-1.9,1.5l1.5,3.8 l-2.2,2.4l0.7,2.6l-2.6,4.1l1,7.2l2,7.6l2.4,1l3.2-2.6l6-2.9l3.1,0.5l1,4.1l4.6,5.2l6.7,0.3l4.6,5.3h3.1l5.6,3.6l3.6-0.2l2.7,2.2 l-0.2,3.4l-4.1,1l-5.1,4.8l-0.3,3.3l-3.9,4.1l-5.3,2.2l-2.9,2.9l-1,4l-2.4,2.9l0.2,2.8l-3.1,1h-7.5l-6-0.5l-5.8,2.1L3319.3,3222.8 L3319.3,3222.8L3319.3,3222.8z"
						/>
						<path
							id="path1143"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="oiba municipios mapaClick"
							d=" M3248.5,3326.2h-5.1l-5.1-5.2l-3.8-1.2l-3.2-3.4l-4.3-0.9l-5.3-4.1l0.6,4.3l-3.1,2.7l-2.7,3.1l-3.4,7.6l-6.1-0.3l-1.2,4.1l-4.4,4.8 l-3.9-3.1l-4.6-0.7l1,3.8l-1.7,3.4l3.1,3.8l-4.3,1.2l-3.9-0.2l-2.2,3.6l-3.1,1.2l-1.5-2.4l-3.8-0.3l-3.6-1.2l-0.3,5.2l-3.9,5.2 l0.9,5.3l7.5,4.5l5.6,4.6l3.1,3.8l0.9,5.5l2.4,1.2l-2.7,6.5l-3.1,2.9l-3.4,7.2l-3.1,0.2l-4.3,6.9l-2.7,7.2l-4.6,9.1l3.9,2.4l4.4-1 l4.4,0.7l3.1-3.6l3.4-0.3l1.9-3.1l2.7-2.1l-0.9-2.6l4.3-3.1l3.4,2.9l0.2,2.2l1.4,2.4v3.3l-1.7,1.7l1.2,4l2.9,1.7l3.4,0.2l6,3.4 l3.9-0.3l4.6-2.9l0.5-4l3.8,2.6l2.9-1.7l2.4,3.8l3.1,3.1l0.7,2.6l4.1-1l1.9,1l5,0.7l6.5,2.2l2-2.4l-1.5-3.6l3.4-3.6l-2.9-2.8 l-3.9-7.4l6-2.2l3.4-5.7l0.3-7.4l4.6-6.5l4.4-2.9v-8.8l0.9-5.3l-2.7-3.3l2.4-3.3l0.7-9.8l4.3-6.9l-2.9-1.7l2-2.1l-1.4-2.8l0.5-4.3 l-4.3-4.8l-0.9-6.4l-5.1-5.5l-8.4,0.7L3248.5,3326.2L3248.5,3326.2L3248.5,3326.2z"
						/>
						<path
							id="path1145"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="guadalupe municipios mapaClick"
							d=" M3172.7,3346.9l-2.8-1.7l-0.4-3.3l-2.1-2.9l-6.1-2.1l-3.3-2.4l3.2-4.8l-0.4-5.2l-1.7-0.2l-1.6-1.5l-1.9,1.5h-2.3l-2.7-2.1h-2.5 l-2-1.7l-2.1,0.1l-0.6,1.6l0.2,2.5l-1.8,1.7l-4.8,2.8l-0.6,3.1l-2.5,0.2l-4.4,5l-2.7,3.6l-0.9,3.1l-2.2-0.1l-1.2,3.2l0.1,2.2 l-1.9,4.7v6l-2.8,4.1l1.6,2.1l-1.5,4.4l-2.8,1.5h-1.4l-0.6,0.8l-2.7,5.6l0.5,1.4l-2.4,2.1l-0.7,2.1l-2,1.8l-0.5,1.9l-4.6,5.2 l-0.7,2.5l-1.6,0.5l-2.9,2.7l0.3,3.4l-2.3,4.4l0.7,2.3l0.8,4.1l1.5,2.7l2.3,2.2l1.6,0.5l3.5,0.5l3.9-0.7l2.9-2.2l0.3-5l6.5-4.1 l9.4,0.9l7.2-1.7l6.1,0.7l1.5,4.5l4.8,2.2l2.6-1.7l0.6-5.6l2.4-4.8l-1.2-8.6l3.8-2.2l2.9,5.3l6.7,6.4l5.1,0.3l2.6,3.4l4.3-6.9 l3.1-0.2l3.4-7.2l3.1-2.9l2.7-6.5l-2.4-1.2l-0.9-5.5l-3.1-3.8l-5.6-4.6l-7.5-4.5l-0.9-5.3l3.9-5.2L3172.7,3346.9L3172.7,3346.9z"
						/>
						<path
							id="path1147"
							inkscape:connector-curvature= "0"
							class="guapota municipios mapaClick"
							d="M3221.6,3311.5h-5.2l-2.6-3l-3-0.3l-3.4-2.8l-2-2.9l-2-5.7 l-3.6-3l-3,2.3l-7.5-5.7l-3.9,2.9l-1.9,3.8l-3.2,2.9l-2.5,0.5l-4.1,4.9l-0.2,3.2l-3.2,5l-0.6,2.9l0.7,4.9l-3.2,4.4l-3.4-0.3l-3-0.9 l0.4,5.2l-3.2,4.8l3.3,2.4l6.1,2.1l2.1,2.9l0.4,3.3l2.8,1.7l3.6,1.2l3.8,0.3l1.5,2.4l3.1-1.2l2.2-3.6l3.9,0.2l4.3-1.2l-3.1-3.8 l1.7-3.4l-1-3.8l4.6,0.7l3.9,3.1l4.4-4.8l1.2-4.1l6.1,0.3l3.4-7.6l2.7-3.1l3.1-2.7L3221.6,3311.5L3221.6,3311.5L3221.6,3311.5z"
						/>
						<path
							id="path1149"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="contratacion municipios mapaClick"
							d=" M3143.7,3324.8l-2.8-1.5l-4.7-1.3l-3.4,3.6h-5l-3.3,4l-3.1,1.6l-8.2-5.7l-3.5-3.6l-0.5-3.8l1.1-4l-1.1-2.3l1.5-3.7l-3.1-3l-3.1-0.6 l-1.5-3l0.1-4.2l-2.2-5v-2.4l1.8-1.7v-4l-5.4,1.3l-4.1,3.3l-7.3-2.7l-3.7,1l-3.4,1.8l-2.5,2.9l-0.1,5.1l-2,4.4l-2.1,3.4l-0.5,4.3 l-2.1,3.3l-1.8,0.9l-0.1,3.1l-4.4,4l-0.9,2.9l-3.6,3.1l-3.3,1.5l-2.2-1.5l-3-0.1l-2.6-2l0.1-2.9l-3.4-0.6l-2-2.7l-1.7,0.2l-1.2-1.5 l-4.1,1.4l-1.4,1.4l-3-0.1l-2.3,0.7l-0.5,3.1l0.2,2.9l1.9,3.5l5.2,3.4l0.2,3l4.2,1.8h2.8l1.3,1.6l2.8,0.2l2.1-2.9l1.8-1l0.4-2.4 l2.6-3l0.9-1.9l0.6,3.9l1.2,2.7l2.9,1.5l1.1,1l0.4,1.3l2.2,1.7l0.8,0.1l0.5,0.6l1,0.1l1,0.8l1.6,0.7l0.6,1l3.6,0.3l0.2,1.2h1.2 l1,0.6l1.2,1.5l0.8-0.6l0.4,1l0.8,1l2.9,1.8l3.2,0.5l2.6-2.4l2.5-2.1l1.1-1.4l1.5,0.1l1.2,1l3.4,1.1l3.4,0.7l2.1,0.9l3.1,0.6 l0.1,2.1l1.2,1.8l0.6,1.4l0.1,1.7l-1.5,4.2l-0.3,1.7l-1.2,2.6l0.3,1.9l1,1.6l2,1h2.3l1.8-0.5l1.6-0.2l1.6,0.5l0.9,1.2l0.1,1.7 l2.8-1.5l1.5-4.4l-1.6-2.1l2.8-4.1v-6l1.9-4.7l-0.1-2.2l1.2-3.2l2.2,0.1l0.9-3.1l2.7-3.6l4.4-5l2.5-0.2l0.6-3.1l4.8-2.8 L3143.7,3324.8L3143.7,3324.8L3143.7,3324.8z"
						/>
						<path
							id="path1151"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="chima municipios mapaClick"
							d=" M3189.3,3290.4l1-4.2l1-3.1l-1.2-3.1l2.4-3.6l2.9-1.9l0.2-3.3l-2.2-1.4l-2-2.8l-3.4,1.9l-4.3-0.7l-5.1,2.8l-1.4,3.6l-4.8,3.8h-3.6 l-1-3.8l-1.9-1l-2.4,2.6l-2.4-0.5v-1.5l1.9-1.5l-3.2-3.3l-4.4-2.8l-2.4,0.5l-3.6,2.9l-3.6-2.4l-1.4,2.9l-3.1,0.2l-4.3-2.1l-2.2,1.5 l-2.6-0.7l1.5-2.4l-2.9-3.4l-4.4,2.1l-2-1.9l1-4.3l-1.9-2.4l-0.3-5l0.9-4.5l2.2-2.2l-0.3-6.2l-3.6-4.5l-5.2-3.4l-2.8,2.4l-3.2,0.1 l-0.7,3.8l-3.3,7.5l-0.9,6.6l-3.9,8.6l-1.8,19.1l2.5,4.9v4l-1.8,1.7v2.4l2.2,5l-0.1,4.2l1.5,3l3.1,0.6l3.1,3l-1.5,3.7l1.1,2.3 l-1.1,4l0.5,3.8l3.5,3.6l8.2,5.7l3.1-1.6l3.3-4h5l3.4-3.6l4.7,1.3l2.8,1.5l-0.2-2.5l0.6-1.6l2.1-0.1l2,1.7h2.5l2.7,2.1h2.3l1.9-1.5 l1.6,1.5l1.7,0.2l3,0.9l3.4,0.3l3.2-4.4l-0.7-4.9l0.6-2.9l3.2-5l0.2-3.2l4.1-4.9l2.5-0.5l3.2-2.9l1.9-3.8L3189.3,3290.4 L3189.3,3290.4L3189.3,3290.4z"
						/>
						<path
							id="path1153"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="simatoca municipios mapaClick"
							d=" M3117.1,3231.3l5.2,3.4l3.6,4.5l0.3,6.2l-2.2,2.2l-0.9,4.5l0.3,5l1.9,2.4l-1,4.3l2,1.9l4.4-2.1l2.9,3.4l-1.5,2.4l2.6,0.7l2.2-1.5 l4.3,2.1l3.1-0.2l1.4-2.9l3.6,2.4l3.6-2.9l2.4-0.5l4.4,2.8l3.2,3.3l-1.9,1.5v1.5l2.4,0.5l2.4-2.6l1.9,1l1,3.8h3.6l4.8-3.8l1.4-3.6 l5.1-2.8l4.3,0.7l3.4-1.9l2,2.8l2.2,1.4l0,0l2.6-4.1l0.2-4.2l-1.1-4.4l3.8-5.2l1.8-5.2l0.2-5.6l2.7-4.6l0.6-2.9l-0.9-3.1l2.3-4.7 l3.1-4.8l2-2.5l-2.6-0.1l-3.4-1.2l-2.7-2.8l-4,1.8l-3.6,0.7l-4.7,1.5l-4.1-3.6l-3.1,0.3l-4.3-4.1l-2-1l-2.8-2.5v-1.5l-4.9-3.1 l-2.4,0.8l-3.9-3.3l-0.2-2.2l-1.4-1.8l-3.9,0.5l-3.5-2.8l-2.5-0.3l-3.4-3.3l-4.1-0.2l-4.4,1.3l-4.2,3.7l-0.2,2.1l-2.8,2.1l0.2,2.3 l-5.6,3.9l-1-6.9l-2.5-4.7l-4.1,2.3h-5.5h-3.7l-5.6-1.8l-6.1-5.2l-2.3,3.6l-0.5,3.9l-5.6,0.8l-3.6,3.4l-8.2-2.1h-6.4l-1.8,4.4 l-4.9-0.5l-3.6,2.3l-5.4-0.2l0.3-8.1l-2.7-8.4l-7.2,1l-4.1-4.3l-4.6-0.3l-3.9-2.8l-5.6-1.5l-4.6,1.5l-5.1-3.3l-7.2,5.2l-7.2-4 l-5.5,1.9l-5.1-1.9l-3.4,1.4l-2.6-1.9l-4.3,1.4l-5.8-7.1l3.9-7.6l-0.7-3.8l3.1-1.4l-2.4-2.4l-9.6-1l-0.5-5l-3.6-4.1l-1.5,4 l-7.5,0.3l-3.1-2.1l1.5-3.4l-9.7-5.7l-0.7-12.7l6.3-2.2l0.9-3.4l-3.4-0.7l-1-2.6l3.6-1.9l-6.8-2.1l-1-6.9l1.7-3.8l-3.2-4.5l1.5-3.4 l-4.1-4l3.2-3.6l-2.7-5.5l0.7-5.2l2-0.9l2.7,1.7l2.4-3.4l0.3-5.7l-3.2-2.8l0.9-2.8l-4.3-3.4l2.7-2.2l-1.9-2.2l2.7-1l1.9-3.8l-1.7-5 l1-3.4l-3.8-2.2l-0.2-3.6l-2.9-2.2l-4.6-1.2l1.5-3.8l-1.7-5l-3.4-0.9l-3.1,1.5l-2.7-6l4.4-3.6l-1.9-4.1l2-3.4l-0.7-6.5l5-2.8l0.2-5 l-6.1,0.2l-3.6,2.2l-6.7-7.4l-1.9-4.8l-7.3,0.7l-3.8-4.5l-2.9-1l2.7-5.7l-4.4-0.2l-5.3,3.8l-3.4-1.9l-1.9,2.1l-2.7-1.4l-1,2.1 l-4.8-0.2l-2-3.4l3.8-5.5l-2.6-3.4l0.9-3.6l-3.8-5.8l-3.4-2.4v-4.5l1.9-4.1l-3.4-4.1l-3.2,0.3l-2.2-3.1l-2.4,1l-5.1-4.3l-1.2-3.8 l-4.4,0.2l-0.5,4l-2.2,0.7l1.9,3.8l-0.5,3.3h-5.6l-4.3,2.1l-0.9,4.5l-2.7,3.4l-3.9,0.5l-6.3,5.5l-0.9,2.9l-4.3-2.2l-1.5,1.9 l0.7,2.9l-2.6,2.6l-4.8-0.3l-4.8,5.3l5.8,4.1l5.8,3.6l-3.2,1.4l3.8,1.9l-4.3,4.6l3.8,1.5l0.7,5.8l2.7,5l-5.6,4.1l2.6,4.6l-3.2,0.5 l2.9,4.1l-4.4,0.3l-2.2,5.3l-2.7-0.5l1.2,4.6l-4.3,3.3l3.6,4.6l1.2,5.3l3.6-2.2l1.2,1.9l-3.4,2.8l1.7,2.4l2-1.4l3.4,5.3l2.6-0.2 l5.8,4.6l3.4,0.2l1.5,3.3l4.1,2.2l1.4,4.1l4.3,1.4v3.3l3.1,4.5l1.9-1.9l3.4,0.3l2.4-3.1l1.4,2.6l-0.7,4.1l1,3.8l2.4-4l1.5,4.6h3.9 l0.7,1.7l-3.8,0.7l-0.3,2.8l5.8,1.5l-3.1,2.6l6.1,2.1l0.3,3.3l3.6,1.7l-1.4,4.6l4.1,3.4l-5.1,5l6.5,4l5.8,5.8l-3.2,4l3.9,4.6 l-1,4.1l3.8,2.4l3.4,4.3l6-1.5l1.2-4.5l3.8,1.2l4.3-2.1l4.3,4l-0.7,6.5l3.9-3.4l1.2,4.6l2,2.6l-3.9,5.5l-8.7,4l3.9,5.8l0.5,5.7 l3.6-1.2l1.4,2.8l4.1,1.4h8.9l6.1,5.2l-4.4,7.2l0,0l0,0l-2.7,6l-0.5,7.4l3.9-0.2l5.1-2.2l5.1,4.5h3.9l-0.5,4.5h7.9l5.1-1l0.7,6.9 l3.9-1.4l2.4,2.8l5.6,2.2l2.6-1.2l3.1,3.1l-1.9,4.5l3.6,2.1l-1.7,7.7l3.9-2.1l1-2.8l4.3-3.8l2.2,2.8l9.6,0.7l5.8-8.8l-0.5-4 l7.2-7.4l0.9-3.6l6.7,3.1l4.8,4l3.9-5.5l7.2-0.5l10.2,3.8l7.5,6.2l-2.1,5.5l0.5,4.3l7.1,6.7l3.6-1.5l6.1,0.6l6.1,2.3l1.2,3.1 l4.6,0.7l3.6,2.9l1.2,3l5.2,1.4l5.7,4l6.5,0.1l7.8-3.8l0.7-3.8l3.2-0.1L3117.1,3231.3L3117.1,3231.3z"
						/>
						<path
							id="path1155"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="hato municipios mapaClick"
							d=" M3204,3216.1v-2l-1.8-3.9l3.3-1.2l1.4-1.8l3.6-1.8l1.3-2.3l-0.4-4.3l-1.8-7.3l-0.7-6.7l-1-3.2l0.1-5.5l-1-4.3l0.8-3.2l2.2-3.5 l0.7-3.5l-1.3-4.1l-2.9-3l-3.6-0.2l-3.4-4.1l-4.4,0.1l-4-2l-3.9-0.1l-5.2-3.4l-5.1-1.8l-2.3-0.1l-2.8-2.3l-6.2-0.6l-3.3-3.5 l-4.3-2.7l-5-0.7l-4.7-2.5l-3.6,4.6v6.2l-2.8,1.5l-4.4,5.7v6.7l-4.1,3.4l-3.8,5.4l1.5,6.5l-2.3,3.6l0.3,4.4l-4.1,3.9l-1,5.2 l1.5,4.4l-0.8,3.4l2.5,4.7l1,6.9l5.6-3.9l-0.2-2.3l2.8-2.1l0.2-2.1l4.2-3.7l4.4-1.3l4.1,0.2l3.4,3.3l2.5,0.3l3.5,2.8l3.9-0.5 l1.4,1.8l0.2,2.2l3.9,3.3l2.4-0.8l4.9,3.1v1.5l2.8,2.5l2,1l4.3,4.1l3.1-0.3l4.1,3.6l4.2-1.6l4.1-0.6L3204,3216.1L3204,3216.1 L3204,3216.1z"
						/>
						<path
							id="path1157"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="galan municipios mapaClick"
							d=" M3219.3,3171.6l1.2-4.8l0.2-5l3.8-6l0.3-4.3l-1.9-2.2l0.5-2.8l2.6-2.8l1.4-3.4l-0.3-3.6l1.5-2.8l2.2-3.8l-1-4.8l1.5-3.4l-1.2-2.4 l-2.9-3.4l0.5-6.9l6.1-1.2l0.9-3.2l-2.4-2.5l-1.6-5.6l2.4-2.1l-7.5,0.3l-2.4-2.3l-6.3-2.8l-0.9-4.4l-5.8-4.1l-3.1-5.3l0.3-4.5 l-1.5-4.3l-6.7-4.8h-4.3l-5.1-1.5l-5.6-4l-0.7-4.8l2.7-2.8l-0.2-2.2l-4.3-3.3l-4.4-2.1l-5.8-1.2l-1.4,5.2l-3.1,4.1l-2,9.6l2.2,4.5 l-1.4,5.2l2.6,4.5l-0.5,4.3l-4.9,2.5l-5.1,7.2l0.5,10.3l1.5,9.5l-0.3,5.4l-3.3,6.7l-8.2,11.1l4.7,2.5l5,0.7l4.3,2.7l3.3,3.5 l6.2,0.6l2.8,2.3l2.3,0.1l5.1,1.8l5.2,3.4l3.9,0.1l4,2l4.4-0.1l3.4,4.1l3.6,0.2l2.9,3l1.3,4.1l-0.7,3.5l-2.2,3.5l-0.8,3.2l5.7,0.3 l3,1.2L3219.3,3171.6L3219.3,3171.6L3219.3,3171.6z"
						/>
						<path
							id="path1159"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccc"
							class="cabrera municipios mapaClick"
							d=" M3223.9,3205.6l3.9-0.3l2.7,0.9l3.6-1.7l4.4,0.3l2.8-3l5.8-2.2l2.4-3.4l3.1-0.5l3.2-2.9l4.3-0.5l1-3.3l3-3.1l2.4,0.5l1.1-1.6 l-1.6-3l-3.3-2.1l-2.9-3.5l-3.7-0.5l-2.7,1.3l-0.9-5.4l-2.6-4.8l-1.9-4.6l-4.6-5.2l-0.5-6.2l1.4-5.5l3.4-1.9l-2.7-4.1l-3.8-0.2 l-2.4-0.7l-1.7-1.9l-3.4,2.2l-5.3-4.8l-1.5,2.8l0.3,3.6l-1.4,3.4l-2.6,2.8l-0.5,2.8l1.9,2.2l-0.3,4.3l-3.8,6l-0.2,5l-1.2,4.8 l0.2,9.3l3.8,6.9l0.9,5.5l-0.7,6L3223.9,3205.6L3223.9,3205.6z"
						/>
						<path
							id="path1161"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccc"
							class="pichonte municipios mapaClick"
							d=" M3298.7,3216l-0.4,4.7l-5,7l-1.7,4l-4,0.1l-5.5,1.5l-0.9-6.2l-4-3.9l-2.4-1.3l-4.2-1.1l-2.7-4.7l0.1-4.9l-1.8-2.6l-3.7-1.8 l-1.9-3.2l-2.9-2.8l-4.7-2.5l-0.2-2.4l3.2-2.9l4.3-0.5l1-3.3l3-3.1l2.4,0.5l1.9,1.1l4.1-0.2l2.2-2.6h2.2l2.2-2.2l3.6,0.5l3.6-1.5 l2.2-4h2.9l3.6-1.7l0.2,5.3l3.9,2.2l1.4,4l2.9,2.9l-0.9,2.6l1.7,2.8l-3.2,2.2l1.2,3.6l-2.2,4.1l-0.3,5.8L3298.7,3216L3298.7,3216 L3298.7,3216z"
						/>
						<path
							id="path1163"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccc"
							class="ocamonte municipios mapaClick"
							d=" M3292.3,3275.8l-1,4.2l-1.7,1.8l0.6,4l-0.7,5.6l1.5,3.7l-0.7,4.2l4.4,5.7l7,5.3l2.4,4.1v5.8l-2.4,3.6l0.7,6l-1.9,3.8l3.8,3.1l5-0.2 l3.2,0.9l3.2,2.2l1.7-1.4l-2.2-5.7l2.4-5.3l5.8-4.6l4.1-1l-0.2-5.3l3.8-2.1l3.8-5.3l0.2-4.8l1.5-4.5l2.6-4.8l3.2-7.6l1.9-3.7h-3.4 l-0.3-4.2l-1.9-1.8l-0.9-4.3l-3.9,2.6l-2.5-0.3l-2.5-1.9l-6,4.8l-5.8,2.6l-4.1-3.1l-1.7-4l-6.3-5.7l-3.6-0.3l-2.9,4.1l-6.5-2.1 L3292.3,3275.8L3292.3,3275.8L3292.3,3275.8z"
						/>
						<path
							id="path1165"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="el-socorro municipios mapaClick"
							d=" M3282.1,3233.3l-2.2,4.6l-3.7,0.1l-2.9,2l-2.4,5.4l1.5,4.5l-4.7,3.6l-2.4,3.2l0.3,4.4l-2.3,5.6l0.7,2.4l-3.2-0.4l0.2-2.6l1-2.4 l-6.3-0.7l-2.7-1.3l-1.7,3.3l-3.2-1.5h-5.3l-6.8-4.1l-4.6-1.5l-4.6,1l-3.9-2.6l-8.7-0.9l-6,2.2l-9.7,5.1l-1.1-4.4l3.8-5.2l1.8-5.2 l0.2-5.6l2.7-4.6l0.6-2.9l-0.9-3.1l2.3-4.7l3.1-4.8l2-2.5l5.7-5.3l2.2-4.5l3.2-4.6l3.9-0.3l2.7,0.9l3.6-1.7l4.4,0.3l2.8-3l5.8-2.2 l2.4-3.4l3.1-0.5l0.2,2.4l4.7,2.5l2.9,2.8l1.9,3.2l3.7,1.8l1.8,2.6l-0.1,4.9l2.7,4.7l4.2,1.1l2.4,1.3l4,3.9L3282.1,3233.3 L3282.1,3233.3L3282.1,3233.3z"
						/>
						<path
							id="path1167"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="paramo municipios mapaClick"
							d=" M3263.4,3312.7l2.2-5.8l-1.7-6.4l1.7-7.4l4.6-5.5l0.9-4.3l-2-2.9l1-3.3l-2.2-5.3l-3.9-2.5l-0.7-2.4l2.3-5.6l-0.3-4.4l2.4-3.2 l4.7-3.6l-1.5-4.5l2.4-5.4l2.9-2l3.7-0.1l2.2-4.6l5.5-1.5l4-0.1l1.7-4l5-7l0.4-4.7l5.8,0.3l3.8-3.4l2.9-0.7l4.1,1.2l1.4,5l2.7,4.5 l-5.1,3.6l-2,2.9l-7.2,3.8l-2.9,4.6l-3.2,2.2l-4.3,9.3l-0.2,8.6l-3.8,7.9l1.2,4.3l0.4,5.7l-1,4.2l-1.7,1.8l0.6,4l-0.7,5.6l1.5,3.7 l-0.7,4.2l-3.9,4.3l-7.2,0.6l-8.1,7.3l-2.7-2.8l-1.9,3.8L3263.4,3312.7L3263.4,3312.7L3263.4,3312.7z"
						/>
						<path
							id="path1169"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccc"
							class="palmas-del-socorro municipios mapaClick"
							d=" M3251.2,3265.2l0.3,5.5l-3.8,5.5l0.2,4.1l-6,7.9l-6.1,1.7l-2.4-1l-3.4,1.9l-4.4-0.2l-3.2-1.5l-3.4,1.9l-3.6-0.2l-2-1.2l-3.4,2.2 l1.5,6.4l-4,7.2l-2-2.9l-2-5.7l-3.6-3l-3,2.3l-7.5-5.7l1-4.2l1-3.1l-1.2-3.1l2.4-3.6l2.9-1.9l0.2-3.3l2.6-4.1l0.2-4.2l9.7-5.1 l6-2.2l8.7,0.9l3.9,2.6l4.6-1l4.6,1.5l6.8,4.1h5.3L3251.2,3265.2L3251.2,3265.2L3251.2,3265.2z"
						/>
						<path
							id="path1171"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccc"
							class="confines municipios mapaClick"
							d=" M3251.2,3265.3l0.3,5.3l-3.8,5.5l0.2,4.1l-6,7.9l-6.1,1.7l-2.4-1l-3.4,1.9l-4.4-0.2l-3.2-1.5l-3.4,1.9l-3.6-0.2l-2-1.2l-3.4,2.2 l1.5,6.4l-4,7.2l3.4,2.8l3,0.3l2.6,3h5.2l5.3,4.1l4.3,0.9l3.2,3.4l3.8,1.2l5.1,5.2h5.1l0.9-2.9l3.8-2.8l5.6,0.3l4.1-4.3l0.5-4 l2.2-5.8l-1.7-6.4l1.7-7.4l4.6-5.5l0.9-4.3l-2-2.9l1-3.3l-2.2-5.3l-3.9-2.5l-3.2-0.4l0.2-2.6l1-2.4l-6.3-0.7l-2.7-1.3 L3251.2,3265.3L3251.2,3265.3L3251.2,3265.3z"
						/>
						<path
							id="path1173"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccsccccccccccccccccccccccccc"
							class="palmar municipios mapaClick"
							d=" M3210.1,3220l2.6,0.1l5.7-5.3l2.2-4.5l3.2-4.6l-0.5-6.4c0,0,0.7-5.2,0.7-6c0-0.9-0.9-5.5-0.9-5.5l-3.8-6.9l-0.2-9.3l-3.6,1.7 l-3-1.2l-5.7-0.3l1,4.3l-0.1,5.5l1,3.2l0.3,2.1l0.4,4.6l1.8,7.3l0.4,4.3l-1.3,2.3l-3.6,1.8l-1.4,1.8l-3.3,1.2l1.8,3.9v2l2.7,2.8 L3210.1,3220L3210.1,3220L3210.1,3220L3210.1,3220L3210.1,3220L3210.1,3220L3210.1,3220z"
						/>
						<path
							id="path1175"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccscccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="san-andres municipios mapaClick"
							d=" M3530,3015.4l0.7,7.4l1.9,3.1v4.1l0.9,4l0.9,6.4l-2.6,3.8l-0.7,5.5l0.7,6l1.5,5.7l-9.7,10.3l-2.4,4.5l-3.8-2.9l-10.9-0.2l-3.8,2.2 l-6.5,6.7l-5.1,2.2l-3.6,2.6l-0.7,5.2l-2.6,7.6l-4.6,0.7l-2-2.9l-2.2-0.5l-4.4,3.3h-5.8l-6-5.8l-4.1-1.7l-3.6,2.6l-0.5,2.2 l-3.8,1.2l-6.6,7l2.2-7.4l-1-5.5l1.9-3.3l1.5-4.5l6.1-0.5c0,0,3.9-0.9,4.6-1c0.7-0.2,3.1-2.8,3.1-2.8l-0.3-4.5l-8.9-12.4l-0.3-8.1 l-1-1.9l3.8-4.1l1-5.8l-0.9-5l1.5-6.5l0.9-6.7l-1.4-6l-1.5-4.3l0.7-4.8l2.4-2.8l3.4-1.2l3.1,1.7l3.1-0.2l4.3,1.7l2.7,0.3l4.4,3.6 l2-0.2l0.7-4l3.6-0.3l2.6-3.1l0.3-4.1l3.1-4.3l0.3-7.7l1.4-1.7l0.3-3.6l4.8-5.8l3.1-1l7-6.4l3.9,0.2l3.8-1.4l2.6,0.5l3.9-1.9 l3.9,0.7l4.8-1.2l4.6-5.8l0.5-4.1l2.9-1.4l4.1-6.7l-0.7-5.7l-1.7-3.8l0.9-4.3l5.5,1.5l-1,3.6l1,3.1l-1.2,4.8l1.9,5.5l-1.9,4.1 l-2.4,3.3l1.4,6.9l-0.9,5.8l1.4,3.3l-0.2,5.7l-4.6,5.5l4.4,8.1l-0.3,2.6l-4.6,2.8l-4.8,6.7L3530,3015.4L3530,3015.4L3530,3015.4z"
						/>
						<path
							id="path1177"
							inkscape:connector-curvature= "0"
							class="cepita municipios mapaClick"
							d="M3440.7,3105.3l-7.9-2.8l-3.3-3.9l-4.8-1.9v-8.4l-8-6.6 l-6.4-2l-4.7-5l-1.8-5.4l2-6.3l-3.5-3.3l-4.6-0.6l0.2-5.3l3.1-4.6l-5.5-4.7l-0.6-6.6l1.7-2.9l8,1.5l6.8-0.8l2.5-5.8l2.7-1.1l1.6-7 l5.5,4.9l9.5,0.7l4.6,5l2.4,0.2l5-6.7l1.9-0.2l3.6-5.5l2.7-0.5l1.4,6l-0.9,6.7l-1.5,6.5l0.9,5l-1,5.8l-3.8,4.1l1,1.9l0.3,8.1 l8.9,12.4l0.3,4.5l-3.1,2.8l-4.6,1l-6.1,0.5l-1.5,4.5l-1.9,3.3l1,5.5L3440.7,3105.3L3440.7,3105.3L3440.7,3105.3z"
						/>
						<path
							id="path1179"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccc"
							class="malaga municipios mapaClick"
							d=" M3561.9,3078l-1,7.5l1.3,2.7l-1.1,3.5l1.3,4.4l-1.1,5.2l0.5,4.1l-5,1.3l-6-1.6l-4.5-3.2l0.8-3.5l-0.5-2.7l-9.9,3.3l-4.2-0.2l-3-1.6 l-0.8,3l-3.8,2.1l-2.2-10.3l0.5-8.9l-2-7.1l2.4-4.5l9.7-10.3l-1.5-5.7l-0.7-6l0.7-5.5l2.6-3.8l2,2.2l-1.2,3.3l2.4,2.8l2.2-0.2 l2.9,3.4l3.1,1l9.7,7.4l8.9,8.8l-2.7,5L3561.9,3078L3561.9,3078L3561.9,3078z"
						/>
						<path
							id="path1181"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccscccccccccccccccccccccccccccccccccccccccccccc"
							class="enciso municipios mapaClick"
							d=" M3610.9,3087.3l1.2,1.5l-2.7,2.2l-0.8,3.6l-0.5,6.2l-3,4.9c0,0-5.5,4.3-5.8,4.9c-0.3,0.6-3.6,6.6-3.6,6.6l-4.4,5.8l-2.2,5.5 l-0.9,4.9l-0.5,6.2l-9.1,5.4l-4.1,5.1l-3.9,0.3l-3.3,1.7l-4.2,2.4l-5.5,3.8l-0.6,2.8l-4.2,1.5l0.2-3.1l1.4-4.4l4.4-6.3l0.6-3.5 l2.7-3.9l1.1-6.9l-1.9-4.9l0.8-2.5l-0.9-2.2v-4.6l1.3-7.6l-0.8-7.3l-0.5-4.1l1.1-5.2l-1.3-4.4l1.1-3.5l-1.3-2.7l1-7.5l7,1.2 l-1.2,2.9l5.8,5.3l4.3,0.5l0.2,4l3.4,1.7l1.5-1.4l2.9-0.3h7l4.8-2.8h2.9l3.9,1.4L3610.9,3087.3L3610.9,3087.3L3610.9,3087.3z"
						/>
						<path
							id="path1183"
							inkscape:connector-curvature= "0"
							class="san-miguel municipios mapaClick"
							d="M3627.7,3173.8l-3.1,6l1.5,3.6l-2.4,4.1l0.3,3.3l-2.4,3.1 l-9.9,5.8l-5.3,5.5l-8.4-3.3l2.9-5.7l0.2-2.9l-4.6-4.5l-2.2-1.2l-0.3-8.8l-5.5-9.6l-4.4-3.6l-5.1-2.1l-3.8-0.3l-1.5-4.6l-5.1-2.9 l-1-3.7l3.3-1.7l3.9-0.3l4.1-5.1l4.6-2.7l4.8,1.1l6.5-3.8h5.6l3.8,3.6l-1,4.3l1.5,4.1l3.1,2.2l5.1,4.6l5-0.2l2.6-3.3l3.8-1.4 l1.7,4.5l0.3,7.2l-0.9,5.2L3627.7,3173.8L3627.7,3173.8L3627.7,3173.8z"
						/>
						<path
							id="path1185"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="macaravita municipios mapaClick"
							d=" M3660.8,3163.8l-0.4,5.6l-1.1,0.8l0.2,4.4l6.7,7.7l-2.5,8.6l-6.1,4.8l-1,3.4l2.9,1.9l0.2,10.5l7.9,3.2v2.5l-4.8,2.1h-3.6l-4,1.5 l-5.6,0.2l-11.9,5l-9.2,8.8l-3.6,6.9l-13.6,7.3l-2.5,0.2l-5.7,2.5l-6.8-0.1l5.8-11.1l-1-6.2l4.3-0.7l6.8-4.5l-1.4-4.3l0.7-4.6 l2.6-3.6l-2-2.4l0.2-5.7l-3.2-0.2l-2.4-3.1l5.3-5.5l9.9-5.8l2.4-3.1l-0.3-3.3l2.4-4.1l-1.5-3.6l3.1-6l4.1-3.4h6.5l5-7.6l3.4-0.5 l0.2-2.6l5.5-6l5.6,1.2l6.9,4.6L3660.8,3163.8z"
						/>
						<path
							id="path1187"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccc"
							class="capitanejo municipios mapaClick"
							d=" M3596.3,3251.6l-9.4-7.9l0.2-5.6l-5.2-5.4v-1.9l-2.7-3.1l-1.7-11.9l-2.1-2.3l0.2-3.4l-10.9-16.3l0.6-2.7l-2.5-3.4l-5-1.5l-2.1-5.2 l-5.4,1.5l-9.4-5.3l11.9-12.3l0.1-2.4l4.2-1.5l0.6-2.8l5.5-3.8l4.2-2.4l1,3.7l5.1,2.9l1.5,4.6l3.8,0.3l5.1,2.1l4.4,3.6l5.5,9.6 l0.3,8.8l2.2,1.2l4.6,4.5l-0.2,2.9l-2.9,5.7l8.5,3.4l0,0l2.2,2.9l3.2,0.2l-0.2,5.7l2,2.4l-2.6,3.6l-0.7,4.6l1.4,4.3l-6.8,4.5 l-4.3,0.7l1,6.2L3596.3,3251.6z"
						/>
						<path
							id="path1189"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccc"
							class="san-jose-de-miranda municipios mapaClick"
							d=" M3531.7,3164.3l0.8,4.7l6.6,3.3l3.2,0.6l-1.3,4.5l11.8-12.3l0.3-5.5l1.4-4.4l4.4-6.3l0.6-3.5l2.7-3.9l1.1-6.9l-1.9-4.9l0.8-2.5 l-0.9-2.2v-4.6l1.3-7.6l-0.8-7.3l0,0l-5,1.3l-6-1.6l-4.5-3.2l0.8-3.5l-0.5-2.7l-9.9,3.3l-4.2-0.2l-3-1.6l-0.8,3l-3.8,2.1v4.8 l2.2,4.1v4.1l-1.2,4.3l0.2,6.9l-1.5,7.6l0.5,8.1l3.6,4.3l-0.9,3.6l-3.4,4.8l1.5,9.1L3531.7,3164.3z"
						/>
						<path
							id="path1191"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="molagavita municipios mapaClick"
							d=" M3503,3185.8l0.7-4.3l-3-1.8l-0.1-1.5l10.2-7.4l1.5,0.5l4.7-1.1l-1.1-3l4.6-3.3l1.1,1.8h3l1.4-1.5l-1.5-9.2l3.4-4.8l0.9-3.6 l-3.6-4.3l-0.5-8.1l1.5-7.6l-0.2-6.9l1.2-4.3v-4.1l-2.2-4.1v-4.8l-2.2-10.3l0.5-8.9l-2-7.1l0,0l-3.8-2.9l-10.9-0.2l-3.8,2.2 l-6.5,6.7l-5.1,2.2l-3.6,2.6l-0.7,5.2l-2.6,7.6l-4.6,0.7l-2-2.9l-2.2-0.5l-4.4,3.3h-5.8l-6-5.8l-4.1-1.7l-3.6,2.6l-0.5,2.2 l-3.8,1.2l-6.6,7l8.1,1.5l2.9,6l5.8,5l3.3,7.3l2.1,7.9l3.6,5.4l1.4,5.8l7.4,6.2l4.7,1.2l3.7,3.8l0.9,6.1l2.8,5.8l0.9,8.3l2.5,5.7 l5.3,4.1L3503,3185.8L3503,3185.8z"
						/>
						<path
							id="path1193"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccsccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="onzaga municipios mapaClick"
							d=" M3503,3185.8l-0.2,1.4l-2.2,2.4l6.1,6l7.2-0.8c0,0,7.7,2.3,8.4,2.3s-0.4,8.9-0.4,8.9l4.3,7.4l4.3,0.9l3,4.1l2-2.2l3.5,0.5l1.2,2 l5.6-2.3l0.5,1.9l-2.2,1.9l1.6,10.3l7.9,6.8l-0.4,2.4l-5,3.1l-1.9,6.5l-2.8,8.1l0.9,14.1l2.6,2.2l-0.4,7.6l4.1,2.6l1.6,4.2 l-0.5,2.8l-3,1.9l-1.5,2.3l1.5,4.5l-9.3,3.7l-2,8.8l0.3,6.9l6.5,4.6l5.1,0.7l0.4,4.9l3.4,3.7l-0.3,3l-4.7-1.5l-5.4,3.8l-0.8,9.2 l-2.2,1.5l-1.2,12.6l3.3,9.6l-1.8,3.9l-2.6,0.7l-4.5-0.8l-7.6,5.6l-2.7,6.6l-4.3-1.2l-2.6-2.8l-10.7,0.3l0.1,4.3l-4.3,3.3l-0.7,7 l-5.6,6.9l2,3.9l-4.6,4.5l0.4,5.3l-3,5.4l-5.6,1.4l-2.6,2l-12.6-0.5l-0.1-1.9l-1.9,0.7l3.3-7.8l-0.9-4l1.7-3.4l-0.7-3.6l-2.7-3.1 l-3.4,2.1l-3.1-1.5l2.4-4.1l-2.7-4.3l5.1-3.4l-1.5-2.1l-2.6-1l0.7-4l1.4-3.8l3.9-2.1l-0.7-7.2l-2-2.8l-5-0.9l-4.6-3.4h-3.1l2.2-2.9 l0.3-7.1l-1.2-2.9l2.7-3.1l3.6,1.9l2.6-3.1l-2.4-3.8l-2.4-8.1l-2.4-5.7l-5-4.1l-5.6,0.2l-3.6-5.8l-0.2-4.3l-3.4-3.3l1-5.2l-3.6-3.4 l-1.4-4.6l5-6.2l2.9-6.5l3.1-0.7l3.9,2.6l7.5-7.7l6.3-0.3l5.8-2.1l2.7,0.7l3.4,2.2l4.6,0.5l5.1-3.1l7.9-8.6l5,0.9l1-1.5l-1.9-3.3 l2-2.1v-4l-1.7-3.1l1.7-1.7l0.2-3.4l-2-3.6l1.5-4.1l-0.7-5.2v-8.3l-0.9-4.5l1.5-4.5l-1.2-2.1l1.5-5.3l3.1-3.3l-1.9-2.8l1-2.9 l-0.5-2.2l-2.2-1.7l-1.5-2.9l1.2-1.5l-1.9-2.4l0.7-2.4l-1.7-3.1l1.5-3.9L3503,3185.8L3503,3185.8z"
						/>
						<path
							id="path1195"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="coromoro municipios mapaClick"
							d=" M3435.7,3452.8l2.3-4.8l3.7-1.8v1.2l1.8,0.1l5.1-5.3l0.3-3.3l2.8-1.4l12.1,0.7l2.6-3.4l-0.1-4.7l-1.8-2l2.2-2.6l3.4-7.9l-0.9-4 l1.7-3.4l-0.7-3.6l-2.7-3.1l-3.4,2.1l-3.1-1.5l2.4-4.1l-2.7-4.3l5.1-3.4l-1.5-2.1l-2.6-1l0.7-4l1.4-3.8l3.9-2.1l-0.7-7.2l-2-2.8 l-5-0.9l-4.6-3.4h-3.1l2.2-2.9l0.3-7.1l-1.2-2.9l2.7-3.1l3.6,1.9l2.6-3.1l-2.4-3.8l-2.4-8.1l-2.4-5.7l-5-4.1l-5.6,0.2l-3.6-5.8 l-0.2-4.3l-3.4-3.3l1-5.2l-3.6-3.4l-1.4-4.6l5-6.2l2.9-6.5l-1.9-3.4l-2.6,0.2l-6-2.8l-3.9,3.8l-4.3,2.8l-5.8-0.7l-5,2.2l-2.6,1.9 l-2.6,0.5l-2.6,2.8h-2.4l-2.7,2.4l-8,0.3l-5-2.4l-2.2,1.2l-3.1,0.2l-3.9-1.2l-1.9,1l-3.8-1.7l-2-1.9l-2.3,1.7l-0.9,2.5l-2,2.5 l-0.4,2.9l1,3l-1.5,3l0.2,3.1l-3.9,4.1l-6.1,3.3l-0.9,7.9l-5,3.4l-6.3,7.1l0.2,5.8l1.7,4.5l-7.2,4.5l-6.3,0.3l-6.8-0.9l0.9,6 l2.2,1.9l0.2,3.1l-2.7,2.4l-3.8,4.3l-1.1,3.2l-2.8,2.2l-2.6,3.6h-3.8l-1.9,8.3l-2,3.4l2.9,6.7l0.2,4.5l2,0.2l4.1-2.8l2,0.5l1.9,2.4 l1,4.8l5-4l4.3,1.5l2.4,4.6l3.3,3.6l5.1,1.9l3.1,4.2v4.8l7.2,1.7l9,0.9l1.4,3.4l6.3,2.7l3-3.6l5-1.7l1,2.6l2.9,1.2l1.7,2.9l4.4,1.9 l6,1.4l3.2-1.9h5.5l3.9,3.1l3.2-0.7l1.7-3.3l4.6,0.3l3.1,5.3l6.8,5.7l5.3-1.7l2.2,6.9L3435.7,3452.8L3435.7,3452.8z"
						/>
						<path
							id="path1197"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="encino municipios mapaClick"
							d=" M3339,3535.4l7.4,5.7l3.4-2.8v-5l7.6-7.7l1.8,0.4l6.2,1.1l3.7,3.5l11.2,0.3l0.3,1.8l3.7,1.6l0.7-3.8l3.1-1.5v-3.4l4.3-5.3l1.5,0.7 l4.2-6.4l-1.4-5.8l5.1-2.6l0.1-2.8l5.8-6.1l4.3-0.7l1.9-6l-1.9-0.8v-2.8l2.7-1.2l1.5-3.7l-1.2-2l2.4-3l-2.3-1.8l-0.1-2l6.1-1.8 l-0.8-3.7l2.2-2l2.2,0.8l2.2-1.6l-0.3-2l2-2.6l2.2,0.5l3.1-1.8l-1.4-2v-2l3.2-2.3l-5.5-3.4l-2.2-6.9l-5.3,1.7l-6.8-5.7l-3.1-5.3 l-4.6-0.3l-1.7,3.3l-3.2,0.7l-3.9-3.1h-5.5l-3.2,1.9l-6-1.4l-4.4-1.9l-1.7-2.9l-2.9-1.2l-1-2.6l-5,1.7l-3,3.6l-6.3-2.7l-1.4-3.4 l-9-0.9l-7.2-1.7v-4.8l-3.1-4.2l-5.1-1.9l-3.3-3.6l-2.4-4.6l-4.3-1.5l-5,4l-1-4.8l-1.9-2.4l-2-0.5l-4.1,2.8l-2-0.2l-0.2-4.5 l-2.9-6.7l-4.6,6.4l-2.9,5.3l-0.2,3.6l-5,4.5l-5.4,0.3l5,4.1l-2.4,8.4l0.9,6.2l-3.8,6.4l-3.1,9.8l-0.2,5.5l3.1,4.3h5.8l4.6,3.6 l4.3,0.7v7.7l2,2.9v4l-3.6,5.3l3.1,2.6l4.8,2.1l-3.4,1.4l-2.9,4.3l0.5,5.3l-1.7,3.1l1.7,6.7l2.7,2.4l-1.2,5.2l3.1,4.5l4.6,3.3h6.5 l8.9,7.6l6.1-1.9l6.7,1.5l1,4.8L3339,3535.4L3339,3535.4z"
						/>
						<path
							id="path1199"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="charala municipios mapaClick"
							d=" M3269.7,3511.6l3.4-0.9l0.5,2.6l10.8,2.7l1.6,3.4l11.2,7l-0.5,5.7l6.5,4.6l22.3,0.3l6.4-4.1l5.3-0.4l0.7,2.3l0.9,0.6l1.6-4.3 l-1-4.8l-6.7-1.5l-6.1,1.9l-8.9-7.6h-6.5l-4.6-3.3l-3.1-4.5l1.2-5.2l-2.7-2.4l-1.7-6.7l1.7-3.1l-0.5-5.3l2.9-4.3l3.4-1.4l-4.8-2.1 l-3.1-2.6l3.6-5.3v-4l-2-2.9v-7.7l-4.3-0.7l-4.6-3.6h-5.8l-3.1-4.3l0.2-5.5l3.1-9.8l3.8-6.4l-0.9-6.2l2.4-8.4l-5-4.1l5.4-0.3l5-4.5 l0.2-3.6l2.9-5.3l4.6-6.4l2-3.4l1.9-8.3h3.8l2.6-3.6l2.8-2.2l1.1-3.2l3.8-4.3l2.7-2.4l-0.2-3.1l-2.2-1.9l-0.9-6l6.8,0.9l6.3-0.3 l7.2-4.5l-1.7-4.5l-0.2-5.8l6.3-7.1l5-3.4l0.9-7.9l6.1-3.3l3.9-4.1l-0.2-3.1l1.5-3l-1-3l0.4-2.9l2-2.5l0.9-2.5l2.3-1.7l2,1.9 l3.8,1.7l1.9-1l3.9,1.2l3.1-0.2l2.2-1.2v-3.8l-1.4-1v-3.4l-2.6-2.4l0.2-5.2l-1.9-2.6l-2.4,0.5l-1.5-2.1l-2,0.5l-3.9,4.3l-5-0.7 l-1.2-2.1l-2.5,2.5l-3,1.7l-2.2-0.9l-1.7,3.2l-2.9-0.6l-3,0.4l-2.6-0.7l-2.8,1.3l-1.9,3.7l-3.2,7.6l-2.6,4.8l-1.5,4.5l-0.2,4.8 l-3.8,5.3l-3.8,2.1l0.2,5.3l-4.1,1l-5.8,4.6l-2.4,5.3l2.2,5.7l-1.7,1.4l-3.2-2.2l-3.2-0.9l-5,0.2l-3.8-3.1l1.9-3.8l-0.7-6l2.4-3.6 v-5.8l-2.4-4.1l-7-5.3l-4.4-5.7l-3.9,4.3l-7.2,0.6l-8.1,7.3l-2.7-2.8l-1.9,3.8h-3.1l-0.5,4l-4.1,4.3l-5.6-0.3l-3.8,2.8l-0.9,2.9 l0.9,2.4l8.4-0.7l5.1,5.5l0.9,6.4l4.3,4.8l-0.5,4.3l1.4,2.8l-2,2.1l2.9,1.7l-4.3,6.9l-0.7,9.8l-2.4,3.3l2.7,3.3l-0.9,5.3v8.8 l-4.4,2.9l-4.6,6.5l-0.3,7.4l-3.4,5.7l-6,2.2l3.9,7.4l2.9,2.8l-3.4,3.6l1.5,3.6l-2,2.4l3.9,4.8l0.3,6.2l-2.2,6.7l-2.6,5.3v13.4 l-0.8,8.2l3.6,0.4l4.6-1.2l1.4,4.1l5.6,5.7l3.4,5l0.9,4l6,0.9l-3.2,3.6L3269.7,3511.6L3269.7,3511.6z"
						/>
						<path
							id="path1201"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccsccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="gambita municipios mapaClick"
							d=" M3269.7,3511.6l-2.4,4.7l-10.3,7.2l0.1,8l3.4,4.7l-3.3,1.4l-1.2,2.2l2.8,1.4l-1.8,4.6c0,0-3,0.1-3.5,0.3c-0.5,0.1-1.2,6.1-1.2,6.1 l-8.1,8.3l-3.9-0.1l-5.2,6.7l-2.5,9l-2.1,1.4l-1.4,5.4l-4.6,1.9l-0.9,8.4l2.2,1l-0.7,3.2l-4.9,5.7l-8,2.3h-13.5l-0.6-1.5l-9.5,1.4 l-0.7-0.9l-5.7,2.3l-2.6,6.6l-0.9,6.1l1.6,1.3l-3.2,3.4l-2.6,12.6l-3.6,2.3l-7.6,11.6l0.1,5l-6.9,6.8l-2.5-3.6l-8.8-3.2l-0.5-1.8 l-6.2-0.5l-4,2.1l-2.5-3.8l2.7-2.2l-0.3-2.7l-2.6-1.3l1.7-2.5l-2.3-5.7l-8-2.4l-8.2-9.7l-5.7-0.9l-1.1-6.4l5-7.1l-0.2-4.7l4.3-2.2 l0.6-4l-2.2-1l0.6-1.9l4.7-3.1l-0.1-4.8l-2.8-2.8l2.1-3.4l4.9-1.1l7.5-4.9l4.3,0.8l0.6,2.3h5.3l4.3-2.3l-0.1-5l4.5-3.3l2.3-4.5 l-0.3-2.8l-1.6-1l1.9-3.6l5.2-1.9l-0.3-5.7l-1.6-0.9l1.1-3.3l3.6-2.5l0.7-7.1l3.7-2l0.1-4.5l-1.3-1.1v-5.1l2.8-3.9v-4.9l1.4-4.5 l-2-1.6l-2-0.1l-1.9-2.1l-1.2-5.5l-2.9-1.2l-1.7-1.5l0.7-4.9l-7.2-5.5l-1.5-1.9l6.3-2.2l5.6-5.2l7-1.5l5.3,2.8l7,0.3l4.8,3.1 l3.1-3.4l3.1-5.8l-0.5-5.2l1.2-4l3.6,0.3l2.6-1.4l5.6,0.9l3.9,2.2l3.9-0.5l2.7,2.4l3.6-1.4l5.8,4.5l1.2,3.3h1.9l3.9,3.8l2.7-0.3 l2.9,3.4l4.4-2.1l2.6,1.9l7.3,0.9l4.6-1.2l1.4,4.1l5.6,5.7l3.4,5l0.9,4l6,0.9l-3.2,3.6l0,0L3269.7,3511.6L3269.7,3511.6z"
						/>
						<path
							id="path1203"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccsccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="suaita municipios mapaClick"
							d=" M3089.3,3449.1l4.9,0.4l1.8,3.1l8,4.7l4.8-0.2l9.2,9l4.4,1.5l4.8,3.3l6.9,1.1l2.1-1.6l4-0.4l1.4,1.4l-1.7,2.9l7.6,7.4l-0.5,1.8 l2,2.4l6.4-2.2l5.6-5.2l7-1.5l5.3,2.8l7,0.3l4.8,3.1l3.1-3.4l3.1-5.8l-0.5-5.2l1.2-4l3.6,0.3l2.6-1.4l5.6,0.9l3.9,2.2l3.9-0.5 l2.7,2.4l3.6-1.4l5.8,4.5l1.2,3.3h1.9l3.9,3.8l2.7-0.3l2.9,3.4l4.4-2.1l2.6,1.9l3.8,0.4l0.8-8.2v-13.4l2.6-5.3l2.2-6.7l-0.3-6.2 l-3.9-4.8l-6.5-2.2l-5-0.7l-1.9-1l-4.1,1l-0.7-2.6l-3.1-3.1c0,0-1.7-3.6-2.4-3.8s-2.9,1.7-2.9,1.7l-3.8-2.6l-0.5,4l-4.6,2.9 l-3.9,0.3l-6-3.4l-3.4-0.2l-2.9-1.7l-1.2-4l1.7-1.7v-3.3l-1.4-2.4l-0.2-2.2l-3.4-2.9l-4.3,3.1l0.9,2.6l-2.7,2.1l-1.9,3.1l-3.4,0.3 l-3.1,3.6l-4.4-0.7l-4.4,1l-3.9-2.4l4.6-9.1l2.7-7.2l-2.6-3.4l-5.1-0.3l-6.7-6.4l-2.9-5.3l-3.8,2.2l1.2,8.6l-2.4,4.8l-0.6,5.6 l-2.6,1.7l-4.8-2.2l-1.5-4.5l-6.1-0.7l-7.2,1.7l-9.4-0.9l-6.5,4.1l-0.3,5l-2.9,2.2l-3.9,0.7l-3.5-0.5l-0.5,4.5l-3,4l0.4,2.4l-1,1.8 l-2.8,1.9l-2.9,2.8l0.8,3.4l-3.2,4.5L3089.3,3449.1L3089.3,3449.1z"
						/>
						<path
							id="path1205"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="san-benito municipios mapaClick"
							d=" M3068.3,3484.7l2.5-3l1.1-2.8l0.6-4.5l1.6-2.6l0.6-4.7l6.1-5.7l1.8-4.5l-1.9-2.3l1-2.1l7.7-3.6l-0.8-5.3l3.2-4.5l-0.8-3.4l2.9-2.8 l2.8-1.9l1-1.8l-0.4-2.4l3-4l0.5-4.5l-1.6-0.5l-2.3-2.2l-1.5-2.7l-1.4-0.3l-0.5,1.2l-2.1-2l-3,3.6l-3.4,2.7l-0.8,1.7l-1.5,0.4 l-0.8,1.9l-3.2,2.2l-4.4-1.7l-2.2,2.9l-2.5,1.3l1.5,4.5l0.3,3.2l-4,2.5l-1.3,2.2l0,0l0,0l0,0l-0.8,5.1l-3.9,2.7l-2.4,2.2l-3.3,1.3 l-3.1,3.1l0.3,2.1l-3.1-0.3l-0.8,1.6l0.9,2.4l-2.9,0.1l-2.1,2.2l-4.5,0.1l0.8,3.2l2.3,4.4l0.3,3.8l1.3,2.5l-0.1,1.7l6.4,3v1.9 l2.6,0.3l2.4,2.2l3.3,0.9l5-0.2l1.2-2L3068.3,3484.7L3068.3,3484.7z"
						/>
						<path
							id="path1207"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccc"
							class="guepsa municipios mapaClick"
							d=" M3037.2,3515.6l1.7-3.8l3.4-3.6l1.3-0.1l3-2.1l9.1-4.6l1.3-3.5l1.9-2.8l2.2-0.6l3.6-4l2.6-4.3l1.1-1.5l-2.2-1.1l-1.2,2l-5,0.2 l-3.3-0.9l-2.4-2.2l-2.6-0.3v-1.9l-6.4-3l0.1-1.7l-1.3-2.5l-0.3-3.8l-2.3-4.4l-3.2,2.8l-0.8,3.7l-1.9,1l1.4,2.9l-4.6,5.2l-1.3,3.4 l0.4,4.4l1.1,2.5l-7.8,2.5l2.9,5.4l3.2,3.7l-1.7,3.4l-4,1.1l-4.1,3.6l-2.3,0.3l-1.2,1.7l0.9,3.6l-3.1,2.5l2.4-0.1l4.4-0.8l3.5-1.9 l6-0.8L3037.2,3515.6z"
						/>
						<path
							id="path1209"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccc"
							class="barbosa municipios mapaClick"
							d=" M3008.3,3564.5l1-1.9l1.4,0.2l2.8-2.3l4-4.9l3.1-1.5l1.7,0.1l4.1-2.8l3-4.2l5.1-9.5l0.9-6.5l-0.4-3.5l1.3-1.9l-0.1-5.6l1.9-2.1 l-0.7-2.7l-5.8-0.5l0,0l-6,0.8l-3.5,1.9l-4.4,0.8l-2.4,0.1l-1.9,2.8l-3.6,1.2l-3.6,4l-1.9,0.9l-2.2,3.5l-4.7,12.1l-2.2,3.2l-2,5.6 l-1.9,2.4l-0.1,3.5l3.5,2.2l2.4-0.3l4.2,2.3l1.8,2.5l3.3,3.2l2.3-0.2L3008.3,3564.5z"
						/>
						<path
							id="path1211"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="puente-nacional municipios mapaClick"
							d=" M2936.7,3648.3l12-0.1l7.4,2.7l3.1-2.7l0.8-2.8l3.1-1.3l1,0.9v2.3l3.9,1.7l7.6,9.5l4,3l10.5,4.3l2.7,3.7l1.2,8.2l-0.7,2l5.6,5.3 l3.5,0.1l4-3.2l1.7-8.4l4.5-4.2l1.3-9.5l-0.6-2.7l2.4-4l0.7-6.3l-2.7-1.7l-0.9-5.3l2-3.3l2.2-8.1l1.7-10.4l6.5-10.5l3.2-8.9 l1.7-0.7l-1.2-9.4l-3.2-5.2l-6.3-0.9l-7.8-5.6l-0.3-6.3l-2.3-2.2l-0.3-0.8l-2.5,0.2l-3.3-3.2l-1.8-2.5l-4.2-2.3l-2.4,0.3l-3.5-2.2 l-4.7,3.5l-2.7-0.3l-8.9,6.5l-5,7.2l-5.5-0.2l-4.3-9.1l-2.9,0.5l-2.7,4l-3.4-1l-6.7,7.9l-5,0.7l6.7,9.3l-3.9,1.5l0.2,3.8l5.1-2.2 l1.5,2.1l-0.2,4l-3.1,2.8l0.5,4.5l2.2,3.1l0.3,4.2l-3.6,2.8l-3.9,5.5l-1.7,6.2l-0.4,4.3l-1.7,1.9l0.4,1.8l-1.9,1.8l0.7,3.3l2.8,3.4 L2936.7,3648.3L2936.7,3648.3z"
						/>
						<path
							id="path1213"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="albania municipios mapaClick"
							d=" M2813.3,3671.9l4.7-1.9l8.5-4.9h2.8v2.6l8,4.1l14.8-0.8l5.9-1.3l4.4,1.3l1.5,4.9v8.5l3.3,2.8l5.4-3l3.3-4.1l16.2-9.3l9.6,1.6 l4.4,5.1l0.7,5.8l4.7,4l2.9-2.9l-2.6-5.8l10.6-15.7l0.7-8.7l6.7-8l4.1-0.7l2.5,2.9l2.9-6.8l-2.8-3.4l-0.7-3.3l1.9-1.8l-0.4-1.8 l1.7-1.9l0.4-4.3l1.7-6.2l3.9-5.5l3.6-2.8l-0.3-4.2l-2.2-3.1l-0.5-4.5l3.1-2.8l0.2-4l-1.5-2.1l-5.1,2.2l0,0l-5.6,3.6l-4.1-1.7v5.5 l-3.1,2.4h-5.1l-7.7,5.5l-4.4,0.3l-3.3,5.1v4.9l-1.7,5.2l-5.2,0.7l-4.2,5.8l-4.7,3.9l-2.3,3.4l-0.5,5l-3.9,2.2l-5.4-0.6l-2.6-2.7 l-2.8-3.3l-3.7-0.1l1.8,5.6l-0.3,2.8l0.6,3.6l-4.8-3l-2-2.4l-4.6,1.8l-8.2-0.5l-2.2-1.8l-2.1-4.7l-3.8,1.2l-3.7-0.5l-6,0.2l1.2,4.5 l-1.5,3.7l-3.4,3.7l-1.9,5.7l-9.2,3.5l-9.2,5.7l1.7,3.6L2813.3,3671.9L2813.3,3671.9z"
						/>
						<path
							id="path1215"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="el-florian municipios mapaClick"
							d=" M2813.3,3671.9l-3.8,1.6l-2.8,4.1l-2.8-2.6l-2.8-7.2h-3.3l-6.7-6.4l-0.5-9.5l-4.9-18.2l-3.1-6.7l-5.9-4.1l-9,0.8l-2.6-3.1l-3.3-2.6 l-8.2,2.1l-0.8,2.3l-9.6-4.2l0.2-4.6l5.2-2.4l1.9-1.7l1.4-3.4l1.4,2l2.4,0.4l5-1.3l4.3-2l2.6,3.4l5.9,0.4l2.9,4.6l3.9-0.7l1.7,1.8 l5.6-0.1l1.8-2l1.7,1.2l2.8-1.8l-0.2-1.8l2.1,0.5l3.7-2.5l2.3,0.5l-0.7-1.5l2.6-0.9l2.4,1.2l2.4-2.4l3.8-0.6l1.4,1.3l5.4-0.6 l1.3,2.5l3.9,1.2l2.2,3.2l2.2-1.5h1.7l3.3,4.1l1.8,0.4l1.5-1.6l3.4-1l4.3,0.3l1.8-1.2l2-2.7l2.6,1.7l2.2,0.3l0.6,1.1l3.4-0.9 l0.4-8.8l1.7-0.1l2.3-1.7l1.9,2.2l0.5,3.3l-1.3,1.8l1.7,2.6l-0.5,6.4l1.6,3l0.2,2.9l1.4,2l5.5-3.6l2.5-0.2l1.5,3.5l-0.1,3l3.2-1 l2.2,2.3l4.7,0.9l-1.3,4.5l6.1,2.6l-0.5,5l-3.9,2.2l-5.4-0.6l-5.4-6l-3.7-0.1l1.8,5.6l-0.3,2.8l0.6,3.6l-4.8-3l-2-2.4l-4.6,1.8 l-8.2-0.5l-2.2-1.8l-2.1-4.7l-3.8,1.2l-3.7-0.5l-6,0.2l1.2,4.5l-1.5,3.7l-3.4,3.7l-1.9,5.7l-9.2,3.5l-9.2,5.7l1.7,3.6 L2813.3,3671.9L2813.3,3671.9z"
						/>
						<path
							id="path1217"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="la-belleza municipios mapaClick"
							d=" M2742.9,3618.1l-5.1-2.2l-3.3-8.5l-1-15.5l1-4.4l-2.6-2.3H2718l-8.5-1.5l-16.5-17.3v-2.8l-3.3-0.3l-5.7,4.9l-4.2-4.8l6.5-3.8 l-1.5-2.6h-3.8v-2.8l3.9-2.6l3.2-5.8l-0.5-3.6l2-5.8l-3.8-1.5l-1-1.7l5.1-1.7l2.2-2.6l-0.3-4.1l5.6-4.1l0.3-3.6l2.6-1.9l1.9-4.3 l4.1-0.3l0.3-2.6h3.8l3.2-2.9l0.2-2.9l-2.7-4.8l0.7-3.5l2.2-1.2l3.7,4.8l3.4-1.7v4.5l3.4,1.7l-1.9,2.9l1.2,5.2l2.9,5.3l3.9,3.1 l0.3,4.3l2.4,4l-2.6,4.3l-3.8,3.3l0.7,4.8l-6,7.2l3.1,2.1l11.6,9.3l4.1-2.6l4.6,1.7l4.3-2.1l4.6,2.6l3.8-7.7l9.2-9.3v-6.2l4.3-7.1 l-0.2-3.4l2.2-0.7l3.4,3.8l6,1.5l3.6,2.8l5.5,0.2l3.9,3.6l3.2,4.8l-0.2,6.9l-2.6,5.5l1.7,4.3l5.1,1.2l5.3,4l3.1-3.3h3.9l3.1-1.7 l3.2,0.9l4.6-1.9l3.9,0.9l0.3,2.5l-3.5,2.1l3.5,3.6l2.5-0.1l-1,1.8l-2.4,0.7l-1.4,2.8l2.8,4l-3.6,0.3v3.1l-3,3.4l1.1,3.6l8.5,4.8 l3.1,5.7l7.2,2.2l0,0l3-0.7l4.3-3.7l4.6-1l3,2.6l3.4-1.1l3.2,1l1.6,2.9l3.3,1.5l-0.6,3l-2.7-0.8l-3.6,0.9l-1,1.8l-1.9-2.2l-2.3,1.7 l-1.7,0.1l-0.4,8.8l-3.4,0.9l-0.6-1.1l-2.2-0.3l-2.6-1.7l-2,2.7l-1.8,1.2l-4.3-0.3l-3.4,1l-1.5,1.6l-1.8-0.4l-3.3-4.1h-1.7 l-2.2,1.5l-2.2-3.2l-3.9-1.2l-1.3-2.5l-5.4,0.6l-1.4-1.3l-3.8,0.6l-2.4,2.4l-2.4-1.2l-2.6,0.9l0.7,1.5l-2.3-0.5l-3.7,2.5l-2.1-0.5 l0.2,1.8l-2.8,1.8l-1.7-1.2l-1.8,2l-5.6,0.1l-1.7-1.8l-3.9,0.7l-2.9-4.6l-5.9-0.4l-2.6-3.4l-4.3,2l-5,1.3l-2.4-0.4l-1.4-2l-1.4,3.4 l-1.9,1.7l-5.2,2.4L2742.9,3618.1L2742.9,3618.1z"
						/>
						<path
							id="path1219"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccsccccccccccc"
							class="carcasi municipios mapaClick"
							d=" M3667.7,3159.8l6.9-6.5l0.8-11.5l3.4-5.4l8.8-2.5l1.3-1.7l-3.1-2.1l1-2.5l5.2-1.7l1-4.8l1.9-0.2v-13.4l-3.1-2.5v-3.8l3.1-1.7 l1.1-6.7l-2.3-4.6l-0.4-5.2l-7.7-5.9v-3.4l-3.4,2.1l-5.3-0.3l-4.4-3.6l-4.3,0.5l-2.7-5l-1.9-5.5l-8.2-0.3l-3.4,2.1l-6-0.5l-3.2,2.1 l-9.9-10.3l-1,7.7l1.7,4.5l-0.9,3.8l-3.8,3.4l-2.9-0.2l-3.4,2.6h-7.2v4l-2.9,0.5l-1.9,1.9v4.4l1.5,1.4l-2.7,2.2l-0.8,3.6l-0.5,6.2 l-3,4.9l-5.8,4.9l-3.6,6.6l-4.4,5.8l-2.2,5.5l-0.9,4.9l-0.5,6.2l-4.5,2.6l4.8,1.1l6.5-3.8h5.6l3.8,3.6l-1,4.3l1.5,4.1l3.1,2.2 l5.1,4.6l5-0.2l2.6-3.3l3.8-1.4l1.7,4.5c0,0,0.7,6.5,0.3,7.2c-0.3,0.7-0.9,5.2-0.9,5.2l2.4,3.3l4.1-3.4h6.5l5-7.6l3.4-0.5l0.2-2.6 l5.5-6l5.6,1.2l6.8,4.6L3667.7,3159.8z"
						/>
						<path
							id="path1221"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="barrancabermeja municipios mapaClick"
							d=" M2732,3032.1l5.9-6.2l3.3-9.5l0.8-5.4l2.5-2.8l4-13l2.3-2.8l5.5-1.3l6.6,0.2l9.1-6.9l5.9-10.1l1.7-4.4l4.5-4.8l3.3-2.3l6.5-3.4 l7.3-7l20.6-12.1l4.5,0.6l7.6-6.2l1.4-3.4l8.2-5.6h2.3l1.7-5.1l4.5-4.5l4.5-2.5l3.9-10.7v-5.6l-3.9-7.6l-1.4-11.3l-0.6-9.3 l-3.1-7.3l-4.8-2l-7.3-4.8l-0.8-7.3l-1.1-4.8l-3.4-3.9v-8.5l1.4-2.5l0.8-7.9l-1.4-7.3l1.5-5.7l-0.6-4.8l-5.5-7.9l3.9-8.7l-0.9-7.3 l10.9,17.1l6.7,2.1l11.1-1.2l5.5-6.5l6-5.7l19.1-12l8.9-1.4l1.9,2.9l-5.3,5.7v3.4l3.8,0.9l6.3-5l3.4-5.2l0.5-7.4l6.7-1.2h5.3 l3.6,2.1l3.1-1.5l2.9,1l3.6,6.2l4.6-0.3l7.3-5l8.2-8.6l6.7,0.9l6.5-1.9l5.3,6l4.6-0.3l3.4,4.5l3.6,1v5.2l1.9,1.7h4.3l5.5,5l3.4-1.5 l3.1,4.5l3.2-1l1.9,1.4l1.2,5l3.8-0.9l2.9,2.1l2.7,4.3l-1.2,2.9l5.1,2.8l2.9-1.2l6.3,3.1l3.6-0.3l4.4-2.6l4.1,3.1l1.5,6.5l7.2,3.8 l7.5,5.7l0,0l0,0l-96,126.2l3.8,3.9l2.7,0.3l2,4.6l4.6,4l4.4,2.2v3.1l6,2.8l-4.3,1.9l-0.3,2.4l-5,0.3l1.9,3.4l-3.1,2.4l-4.8,5.3 l-3.6-2.9l-1.9,0.5l-1.5-3.6l-2.7,3.1l-1.4-4.6l-3.8,0.5l-4.6-2.4l-5,0.3l-2-1.9l-3.4,1l0.9,3.3l-2.6,1.4l0.7,2.2l-1.7,2.1l3.9,4.1 l-2.9,3.1l0.3,4.6l-3.1-0.2l-3.1,5.3l-6.1,0.2l-3.6,2.2l-6.7-7.4l-1.9-4.8l-7.3,0.7l-3.8-4.5l-2.9-1l2.7-5.7l-4.4-0.2l-5.3,3.8 l-3.4-1.9l-1.9,2.1l-2.7-1.4l-1,2.1l-4.8-0.2l-2-3.4l3.8-5.5l-2.6-3.4l0.9-3.6l-3.8-5.8l-3.4-2.4v-4.5l1.9-4.1l-3.4-4.1l-3.2,0.3 l-2.2-3.1l-2.4,1l-5.1-4.3l-1.2-3.8l-4.4,0.2l-0.5,4l-2.2,0.7l1.9,3.8l-0.5,3.3h-5.6l-4.3,2.1l-0.9,4.5l-2.7,3.4l-3.9,0.5l-6.3,5.5 l-0.9,2.9l-4.3-2.2l-1.5,1.9l0.7,2.9l-2.6,2.6l-4.8-0.3l-4.8,5.3l5.8,4.1l-21.2,14.6l-14.7,8.1l-8,0.5l-2.9,2.6l-1.9,5.8l-3.2-1.5 l-2.9-1l-1.2,2.6l2.6,2.6l-6-0.5l1.9,6.7l4.1,2.4v2.2l-5.5-0.7l-3.6,3.6l-5.1,0.3l-0.5,3.3l3.2,2.6l-5.1,1.7l-3.8-3.3l3.4-4.6 l-3.4-0.9l-5.3,4.8l-8.7,2L2732,3032.1z"
						/>
						<path
							id="path1223"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="puerto-wilches municipios mapaClick"
							d=" M2836.3,2774.4l0.8-4.8l-2.8-2.5l0.8-5.1l-1.4-3.1v-11l4.5-6.8l-0.3-3.9l1.4-2l0.6-5.4l-0.3-6.8l3.4-2.3l3.1-12.2l1.1-0.7l-0.4-4.7 l1.1-0.5l0.7-5.5l-3.3-4l-1.1-7.5l1.6-2v-3.5l5.3-5.1l5.1-12.4h1.1l0.5-1.8l-5.1-6.5l-1.7-4.7l-2.4-1.9l-1.4-6.3l-2.3-5.1l1.5-3.1 l-1.7-4.3l-1.3-0.7l-0.5-3.6l1.6-1.6l1.3-8.7l2.2-2.9l5.6-0.7l10-6.9l8.2-10.7l6.7-7.8l4.1-5.5l0.7-4.8l2.9-5.5l0.6-5.4l3-3.2 l2.6-6l0.4-4.7l2-1.3h4.4l3.8-2l1.3-4.9l-2.4-4.2l-1.5-5.6l-1.3-0.7l-0.5-5.5l1.1-0.2l0.4-4l4.6-6.2l-0.3-7.6l-5.4-8.5l0.3-3 l-1.5-2.6v-5.5l2.7-2.6l0.7-5.1l5.9-8l5.5-16.7l-1-9l-3.6-6.6l-1.6-8.4l-2.7-8.1l-3.1-4.6l0.4-3.6l1.8-1.8l1.5-6.6l-3.7-7.8l-4-4.3 l-3.6-9.7l0.5-5.7l-1.9-4.6l-3.3-10l0.4-8.2l-3.3-5.4v-8.4l-5.1-9.1l-6.2-14.6l-4-9.1l-1.1-13.8h2.2l1.1-14.6l6.6-15.7l1.9-4.9 l3.5-2.1l4.8-6.5l10.9-3.6l4-4.1l1.4-4.6l1.3-4.1l5.2-4.6h1.1l1.9,1.5l2.1-1.8l2.7,1.8l4.3,1.6l-1,4.4l1.4,3.4l-2.3,3.2l-1.4,6.3 l-4,0.3l-2.6-2l-2,1.1l0.3,2l2.3,2.6l4,0.3l3.1,1.1l1.1,6.3l2.9,3.4l-0.6,2.9l2,5.5l3.7,2l2.6,0.6l2,0.9l2.5,4h4l-1.1,1.7l-3.7,1.1 l3.1,4.3l3.4,1.7l2-0.6l1.7,0.3l-0.6,5.7l-3.4,2l0.3,1.7h4.6l3.7,3.7l0.6,4.9l2.3,3.7l-2.6,4.3l0.3,2.3l-2,2l3.1,1.7l0.6,4.3 l-1.1,4.3l2.6,1.4l6-1.4h2.3v3.7l2.3,4.9l4.3,2.3l2.3,0.3l3.1,0.9l0.9,5.5l3.1,0.3l1.1,3.4l2.4,3.1l-4.9,6.2l0.7,4.2l4.5,3.6 l1.1,5.9l-3,4.4l-6.7,2.5l-2.6,4.6l-0.6,4.5l-6.2,1.8l-3.7,1.4l-3.7,5.7l-2,5.5l-3.7,3.8l-5.3,1.1l-5.1,3.7l-1.4,2.3l-3.4,3.4v3.7 l1.3,6.6l4.6,0.6l-0.4,3.8v2.6l2.3,4.9l-4,3.7l-5.7,1.1l-3.7,0.3l-0.9,2.6l1.4,4l-0.6,2.6l2.3,2.9l0.6,1.6l0.2,8.6l-3.1,3.6 l1.8,2.6l-2,3.9l2.8,2.3l-3.1,4.9v5.8l-1.9,3.8l1.4,3.3l-4.1,6.4l4.3,4.5l-0.5,7.7l-1.9,4.8l3.2,5.2l4.8,2.2l5.6,11.4l-1.2,4.3 l11.4,12l-2.9,4.3l-6.1,3.3l-6.7,8.9l-6.5,1l-1,4l-6-1.5l0.9,7.1l-2.7,3.8l3.6,8.1l-1.7,9.8l1.5,8.6l-2.2,5.5l1.2,13.8l-1.2,17.9 l3.6,4.1l6.1,0.7l5.8,4.6v5.7l6.3,2.4l5.6-1.2l10.9,7.9l4.1,0.7l2,5.8l-3.6,4.1l8,5.5l6.5-2.6l6.5-0.2l2,5.7l7.3,7.2l5.5,1.2 l-3.1,4.6l2.9,2.2l-2.6,7.4l3.8,5.8l5-0.7l2.4,4.8l5.3,2.6l-0.7,2.9l6.1,5.8l3.6,1l0.2,3.8l4.3,4.5l6.1,14.3l4.4,15.5l9.6,21.5l0,0 l0,0l-4.1-3.1l-4.4,2.6l-3.6,0.3l-6.3-3.1l-2.9,1.2l-5.1-2.8l1.2-2.9l-2.7-4.3l-2.9-2.1l-3.8,0.9l-1.2-5l-1.9-1.4l-3.2,1l-3.1-4.5 l-3.4,1.5l-5.5-5h-4.3l-1.9-1.7v-5.2l-3.6-1l-3.4-4.5l-4.6,0.3l-5.3-6l-6.5,1.9l-6.7-0.9l-8.2,8.6l-7.3,5l-4.6,0.3l-3.6-6.2l-2.9-1 l-3.1,1.5l-3.6-2.1h-5.3l-6.7,1.2l-0.5,7.4l-3.4,5.2l-6.3,5l-3.8-0.9v-3.4l5.3-5.7l-1.9-2.9l-8.9,1.4l-19.1,12l-6,5.7l-5.5,6.5 l-11.1,1.2l-6.7-2.1l-10-15.8l-1-1.5L2836.3,2774.4z"
						/>
						<path
							id="path1225"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="rionegro municipios mapaClick"
							d=" M2997.9,2462.5l3.8,5.6l2.9,0.8l2,3.4l-0.2,1.2l1.5,1l6.4,1.5l4.9,3.7l-0.2-2.5l2.7,1l5.5-0.5l5.2,2.9l2,2.2l5.9-1.2l1-2.9l4.2,3.9 v4.4l4,0.2l1,1.5l1.7,3.9h2.2l1.7,3.9l-0.7,2.2l3.2,5.9l1.7,0.2l-1.7,3.2l-3.7,0.2l-1.5,1.2l-2.5,0.7l3.2,2.7l4.7,1l3,3.9l2-0.5 l2.5,3.4l-1,1.7l3.7,3.9l5.4,2.7l1.5,3.4l-0.2,5.9h-1l-0.2,1.7l1.7,3.2l2,0.2l1.5,3.2l2.6,0.9l0.2,2.8l-2.1,1.1l1.6,1.8l0.2,3 l1.6,0.9l1.2-2.6h0.9l0.5,2.8l2.5,0.4l1.6-0.9l0.5,2.1l2.1-1.1l0.9,0.5l1.1,3.5l3.2,1.2l2.1-0.4l3.4,2.6l-0.5,2.1l6.9,1.1v1.6 l2.7,0.4l2.7-1.6l-0.2-1.2l2.5-0.7l2.8,2.5h3.2l1.2-1.8l1.8,1.8l-0.5,1.6l3,1.6l2.8-0.5l2.5,0.4l3.4,2.8l6.9-0.5l0.7-1.6l1.8,0.9 l2.7-1.1l-0.2-1.1l-3.7-2.3l0.4-1.1l3-1.6l1.8-2.1l3.6,0.4l1.4,3.3l4.3,5.1l4.1,2.6l-0.7,2.1l1.4,2.5l2.5,1.2l-0.2,1.9l-1.2,2.1 l1.6,0.9l4.1-1.2l2,0.9l0.7,2.6l2.7-1.6v-1.6l1.4-0.7l5,0.2l1.1,2.8l2-2.6l4.8-0.5l0.2-1.4h3.6l1.6,1.8h2.5v2.1l3.4-1.4l2.3-3.2 l2.7,0.7l2,2.4l-1.3,2.5l0.5,2.8l-6.9,2.1l-6.1,4.9l-2,4.9l1.5,2.1l-3.6,2.6l-1,3.4l2.4,3.7l3.9,1.5l1.9,2.2l0.2,2.4l1.9,0.2l4.1,5 l0.5,2.1l3.6,3.1l0.5,2.8l-2,1.9h-3.8l-2.6,2.4v4.8l2.2,2.6l2.2-0.2l1,3.8h4.8l4.1,1.2l1.5-1.7l3.9-0.5l2-1.2l4.4-0.3l1.5,1.4 l3.4,0.2l2.2,1.7l2-0.3l0.7-2.4l3.8-5.8l2.2-2.1l-0.7-2.9l4.1-2.6l5.1,0.5l6.1,6.7l2.7-1.5l3.6,0.5l2.9,2.2l2.6,9.3l2.7,7.9 l2.9,0.3l2-1.4l3.1,1.2l2.2-0.7l0.3-5.3l2-3.3l3.1,0.7l2.4-2.9l2.6,0.7l1.4-1.7l3.1,0.7l0.9-1.9l2.9,0.3l4.6-1l2.4-2.2l3.9-1.7 l0.9-2.8l2-0.3l3.9-7.1l2.9-1.5l2.7-3.4l3.8-1.7l3.9-0.3l3.1,1.9l1.5,1.7l-4.8,3.8l-0.5,3.8l1,3.1l3.8,3.6l0.7,4.3l-2,6.4l-4.4,6.4 l-0.7,3.8l-4.1,7.1l-5,5l-0.2,5.8l-2,2.2l-0.7,2.9l-2.6,2.9l-0.3,2.8l-2.7,1.9l0.5,7.2l-2.2,5.3l2.7-1l3.4,1.2l0.7,2.2l3.9,4.3v2.4 l-1.5,2.6l-0.3,7.1l3.2,4.8l-3.4,4.1l1.9,1.5l8,0.2l3.8,1.7l5.5,6.9l0.5,5l-1.5,3.1l-1.7,2.2l-2.7-0.5l-3.6,1l-8.4,8.3v3.4 l-2.6,4.5l-4.4-0.2l-1.5,3.4l0.5,2.6l-5.3,4.5h-3.1l-1.5,3.6l-2.9-0.5l-6.1-4l-3.2-1l-4.4,2.8l-3.9-0.5l-3.9,3.1l-3.2-2.1l-3.2-0.5 l-3.9-3.3l-4.6-1.4l-4.4-0.2l-4.4,0.5l-2.6-2.2l-2.4-0.5l-3.2-4.6l-4.6-3.6l-2-0.3l-1.7,1.2l-1.7-1l-0.7-2.6l0.3-7.7l-1.5-5.7 l0.5-4.1l-3.1-5l-0.9-3.3l-4.1-1l-3.9-4.8l-1-5.3l-1.9-2.1l-0.2-6.2l-2-2.2l-0.3-5.2l-1.9-2.4l-3.6-1.4l-0.5-2.6l3.1-1.5l0.5-2.8 l-1.7-2.1l-3.4,0.5l-2.2-2.2l-3.8,0.3l-0.5-3.4l-2.4,0.2l-0.3,3.6l-4.3,1.5l-5,5l-2.6,4l-7-0.3l-3.1-1.5l-1.2-2.8l1.2-2.9l2.6-2.2 l0.2-2.2l-2.7-1.7l-0.5-4.6l-1.5-3.6l0.3-3.1l2.6-3.3l0.9-4.1l-1.2-4l-2.4-1.7l-0.7-4.5l0.5-6.7l-1.7-4.5l-2.7-1.7l-1.2,2.9 l-4.8-0.5l-2.4,0.7l-1.2,1.7l0.7,2.2l-1,1.9l-3.1,1.4l-2.4-0.7l-3.8,2.8l-8.7,4l-3.1-7.6l-7.3,4.6l-8.4-10.2l-2.4-6.5l-4.4-4.3 l2.9-5.5l-1.4-2.2l-4.3,1.2l-2-3.1l2-3.8l-1.5-2.8l-5,2.1l-2.9-1.9l0.5-2.6l3.8-4.6l-2.7-2.1l-4.4,1.7l-2.4-1.7l2.7-4.6l-9-8.8 l-9.9-2.4l-4.8,3.3l-3.4-5.2l-3.2-0.2l-3.6-3.3l-0.5-4.6l-3.8-4.6l-4.8-3.1l2.4-4.5l-2-1.4l-4.1,0.3l-2.7-2.6l-0.7-6.9l1.4-2.6 l-2.6-2.1l-6-10.2l-11.3-1.5l-7-2.1l-2.7-2.9h-5.1l-5.8-5l-3.8,3.6l-3.1-2.4l-2.9,2.1l-3.8-2.2l-5.5,1.2l0.3-5.5l-5.1-0.3v-5.2 l3.4,0.7l1-2.6l-2.6-6.2l-2.6,2.1l-1.9-2.8l2-4.5l-1.5-2.2l-3.8,3.8l-2.4-3.1l-4.3,1.9l0.9-5.5l-4.4,1l0.7-3.3l-3.4-0.3l-1.4-4.8 l-3.2-5.2l-6.5-1.2l-1.9-4.1l0.9-3.4l-4.4-2.8l-8.7,4l-1.9,4.3l-4.4-0.9l-3.1-2.6l-4.3,1.5l-2.9-6.5v-5.8l3.1-4.9l-2.8-2.3l2-3.9 l-1.8-2.6l3.1-3.6l-0.2-8.4L2997.9,2462.5z"
						/>
						<path
							id="path1227"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="el-playon municipios mapaClick"
							d=" M3208.5,2588.8l0.5,2.8l-6.9,2.1l-6.1,4.9l-2,4.9l1.5,2.1l-3.6,2.6l-1,3.4l2.4,3.7l3.9,1.5l1.9,2.2l0.2,2.4l1.9,0.2l4.1,5l0.5,2.1 l3.6,3.1l0.5,2.8l-2,1.9h-3.8l-2.6,2.4v4.8l2.2,2.6l2.2-0.2l1,3.8h4.8l4.1,1.2l1.5-1.7l3.9-0.5l2-1.2l4.4-0.3l1.5,1.4l3.4,0.2 l2.2,1.7l2-0.3l0.7-2.4l3.8-5.8l2.2-2.1l-0.7-2.9l4.1-2.6l5.1,0.5l6.1,6.7l2.7-1.5l3.6,0.5l2.9,2.2l2.6,9.3l2.7,7.9l2.9,0.3l2-1.4 l3.1,1.2l2.2-0.7l0.3-5.3l2-3.3l3.1,0.7l2.4-2.9l2.6,0.7l1.4-1.7l3.1,0.7l0.9-1.9l2.9,0.3l4.6-1l2.4-2.2l3.9-1.7l0.9-2.8l2-0.3 l3.9-7.1l2.9-1.5l2.7-3.4l3.8-1.7l3.9-0.3l3.1,1.9l1.5,1.7l2.1-1.9l0.6,2.4l1.9,0.2l0.9,3.4l2,2.2l6.1-0.5h5.8l2.6,0.9l-0.2-5 l-1.7-2.1l-3.2-8.9l-3.8-0.2l-3.6-2.6l-15.7-0.5l3.8-2.8l6.3-2.6l4.3-7.2l-0.9-2.9l-6.7,2.1l-0.5-3.6l-5.3-6.7l-3.2-0.9l-0.5-2.6 l4.1-4.8c1.3-4.7,1-8.4,5.6-10l4.1-1.9l1.5-6.7l4.6-0.7l4.1-6.5l1.5-4.8l1.8-2.1l-2.8-0.2l-0.9-1.8l-5-1.1l-1.8,1.4l-1.2-2.6 l-6.2-0.2l-1.4,1.8l-7.1-0.4l-4.1-3.2l1.1-1.6l-3.4-2.6l-0.4,2.5l-4.4,3.5l-3.9,0.4l-1.8,0.9l-1.4,3.7l-2.8-3.3l1.1-0.7l-4.1-2.6 h-5.2l-6.6-1.9l-1.4,0.4l-3.7-1.1l0.4-2.3l-5.4-2.5l-1.8,0.9l-0.2,1.4l-6.1,3.9v-2.5l-5.9-0.9l-1.2-2.8l-5.5-0.5l-1.4,1.1l0.7,1.4 l-2,1.2l-2.8-1.8l-0.5-3.2l-3.9-1.6l-2.5,0.9l-1.2,3.7l-5.2,2.8l-0.9,2.6l0.2,1.4l-3.2,7.4l-5.3,3.5l-0.5,1.2l1.1,0.7l-0.5,1.9 l-3.6,0.4l-1.8,3.2l1.2,1.9l3.7,7.2l2.1,0.5l0.5,3l1.6,0.2l0.5,1.6l-4.3,3.5l-1.4,6l1.4,3.5l-1.4,2.5l-1.8-0.7l-0.5,2.3l-2.1,0.5 l-3.4-2.5l1.1-0.9l-1.2-3.2h-3.2l-0.5,1.2l-4.1,0.7l1.8-2.1l-2.1-4.4l-4.4-0.7l-1.1,2.1l-1.8,0.4L3208.5,2588.8z"
						/>
						<path
							id="path1229"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="surata municipios mapaClick"
							d=" M3357.6,2548.2l2.7-0.4l0.5-3l-0.7-1.6h2.7l0.5-1.4l-1.1-1.8l2-1.1l1.6,1.2l1.1-0.7l3.9,0.7l0.9,2.6l2.5-1.2l0.4-1.8l4.6,0.2 l2.8,1.8l-0.2,1.4l2,0.7l2.8-1.1l-0.4-1.4l2,0.4l1.1,1.9l2-0.4l0.9-1.8l2.1-0.7l1.6,0.9l-0.7,2.1l1.4,0.4l0.2,1.4l-2,3.2l-1.8,0.4 l-1.1,3l0.7,1.4l-2.5,0.9l0.2,1.8l-1.4,0.5l0.4,1.4l3.2,1.8v3.3l-1.2,0.7l2.5,6l1.8,2.1l-1.8,0.4l0.9,3.2l2.5,4.6l0.1,4.3l-0.7,1 l4.6,3.3l0.1,6.9l-1.1,0.5l5.3,5l4.7-0.6l7.8,3.6l1.5-1.1l3.6,4.4l-1.2,2l5.9,4l0.6,3.7l4.9-0.5l0.2-0.9l2,0.6l5.8,6l-0.6,2 l2.8,2.1l3.7-1.6l1.9,3.3l-1.9,9.6l-3.7,5.7l-0.2,4.5l6.2,11.5l5.2,2.1l3.7,7l-0.9,3.4l-2.9,1l-1.7-4.1l-3.1-1.4l-1.5,1.2l-1.5-0.7 l-1,1.4l-2.2-0.2l-2.7,2.9h-3.2l-3.1,3.8l-1,2.2l-2.2-1.2l-2.2,2.6l-1.7,2.4l-4,3.1l-1.7,5.5l-4.1,7.4l-3.1,1.9h-6.1l-3.1-1.7 l-2.4-1.2l-2.7-1l-3.6,1.9l0.5,4l-0.7,1l6.5,8.6h1.7l4.3,4l4.3,4.8l3.2,0.5l4.8,5l1.9,0.3l2.4,2.6h1.9l6.1,5.5l-6.3-0.5l-1.5-1 l-2,0.7l-4.6-5.3l-2-1l-0.5-2.1l-3.4-1l-1.7,0.9l-2.2-2.9l-5-3.8l-3.1-0.3l-3.8,2.6l0.7-4.3l-1-2.6l1.2-3.3l-3.1-2.9l-3.2-0.5 l-2.6-2.2l-1.5,1.9l-3.2,1.7l2-3.3l-3.8-5.5l-6-5l0.3-3.3l3.1-2.4l2.6-4.1l1.9-4.6l-0.7-4.1l1.9-3.8l-0.2-10l1.7-3.1l-2.6-1.4 l-2.4-2.4l-1.9-0.3l0.2-2.4l-1.4-3.1l-2.4-0.2l-5-4.5l-3.2-0.7l-7.2-7.7l-2.2-1.2l-0.2-5l-1.7-2.1l-3.2-8.9l-3.8-0.2l-3.6-2.6 l-15.7-0.5l3.8-2.8l6.3-2.6l4.3-7.2l-0.9-2.9l-6.7,2.1l-0.5-3.6l-5.3-6.7l-3.2-0.9l-0.5-2.6l4.1-4.8l2.6-8.9l3.1-1l4.1-1.9l1.5-6.7 l4.6-0.7l4.1-6.5l1.5-4.8l1.8-2.1L3357.6,2548.2z"
						/>
						<path
							id="path1231"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="tona municipios mapaClick"
							d=" M3481.5,2801.6l1.4,9.6l5.4,1.7l-0.4,1.1l-10.5-0.1l-4.9,3.8l2,6.3l-1.1,18.3l-7.5,4.4l-4,9.7l-2.3,2.5l-1.5,3.2h-8.8l0,0l-2.6,1.2 h-7.7l-2.2-4.3l-7.9-3.4l-6.7-5l-5.1,7.6l1.4,4.3l-1.2,4h-4.4l-4.3-4.5l2-3.3l-1.2-2.8l0.2-5l-1.4-1.5l1.4-3.3l-3.2,1.2l-3.8-1 l-2-2.2l-4.8-0.2l-2.4,1.5h-2.2l-2.2-2.4l-2.2,1.9l-3.2-1.4l-1.5-5l-6.8-4.8l-1.9-4.1l-0.5-4.3l-4.1-1.4l-3.6-2.4l-1.2,3.4 l-2.6,2.2l-5.5-4l-6.1-0.5l-3.6-1.7l-0.7-7.1l0.2-6l3.2-4.3l2-5l3.9-4.1l2.2-0.2l2.7,3.6h2.4l3.4-5.2l3.1-0.3l4.6-5.7l2.4-6.9 l0.2-5.5l1.2-1.4l3.4-1.2l0.2-2.8l2.2-3.3l3.2-0.7l8.4-6.9l5.3,0.3l1.7,3.3l3.4,4.3l2.7,0.5l2.2,1.9l1.9-1.2l3.9-0.2l6.7-4.1 l7.5-1.5l6-2.2l2.7,0.7l4.8,4.3l2.9-3.6l2-3.8l2.7,0.9l7.8-3.5l0.6,5.3l8.3,5.8l-0.3,3.5l-2.3,2.3l1.3,2.9l4.6,3.6l-1.3,5.6 l6.4,1.2l2.3,5l3.6,0.8l1.7-1.8l2.8,1.5l-0.8,4.2l-4,1.9L3481.5,2801.6z"
						/>
						<path
							id="path1233"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="santa-barbara municipios mapaClick"
							d=" M3474,2881.6l-4.1-2.6l-6.2-0.2l-3.2,1.1l-1.1-2.1l0.5-3.8l-2.9-4.6v-5.3l1.1-2.4h-8.9l-2.6,1.2h-7.7l0,0l0,0l1.7,4.3l-0.9,4 l-5.1,8.6l2.4,4.3l-1.4,4.8l0.3,9.1l-4.6,8.3l1,6.4l-8.5,13.6l-4.8,11.9l-2.4-1.2l-6.3,4.5l-6.1,0.3l-3.9-5.2l-3.6-0.2l-3.2,8.6 l-4.1,6.2l-1.5,6.2h-3.1l2,3.6l-1.9,2.6l0.3,4.6l2.4,10.2l1.5-0.3l1.9-3.8l4.8-4.6l1.2-9.3l2.9-2.9l2.9-0.2l3.9-2.2l-0.9,3.6l1,2.2 l-2.2,2.2l2.4,2.9l5.5-0.3l2,2.1l-5.3,10.2l2.2,2.2l3.1,1l4.3,4.3l-2.2,3.3l-0.7,3.3l2.9,0.7l2.9-2.9l3.1,1.2l7-4.1l3.8-4.3 l2.2-3.5l5.3,0.3l5.2-4.5l3.2-0.8l3.9-4.5l0.2-4.9l-0.9-5.2l4.9-3.6l3.3-5.3l0.4-8.6l-2.7-2.9l3.1-8l7.5-11.4l2.4-9l-3.9-4 l-0.7-5.4l6.4-2.8l3.7-6.3l-2.9-5.4l0.8-6.1L3474,2881.6z"
						/>
						<path
							id="path1235"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="guaca municipios mapaClick"
							d=" M3478.6,2880.6l1.5,4l2.9-0.2l2.3-1.1l4.2,0.8l5.6,7.9l9.9,1.1l3.5-2.1l6.3,4.4l-1.5,4.9l2.3,2.1l10.9,4.5l6.5,5.1l3.8,6.1l1.2,6.5 l1.7,0.2l-1,1.6l-0.9,6l0.7,4.1l0,0l0,0l-0.9,4.3l1.7,3.8l0.7,5.7l-4.1,6.7l-2.9,1.4l-0.5,4.1l-4.6,5.8l-4.8,1.2l-3.9-0.7l-3.9,1.9 l-2.6-0.5l-3.8,1.4l-3.9-0.2l-7,6.4l-3.1,1l-4.8,5.8l-0.3,3.6l-1.4,1.7l-0.3,7.7l-3.1,4.3l-0.3,4.1l-2.6,3.1l-3.6,0.3l-0.7,4 l-2,0.2l-4.4-3.6l-2.7-0.3l-4.3-1.7l-3.1,0.2l-3.1-1.7l-3.4,1.2l-2.4,2.8l-0.7,4.8l1.5,4.3l-2.7,0.5l-3.6,5.5l-1.9,0.2l-5,6.7 l-2.4-0.2l-4.6-5l-9.5-0.7l2.7-5.4l0.8-5.9l6.9-9.4l3.7-8.3l-2-14.9l2.2-3.5l5.3,0.3l5.2-4.5l3.2-0.8l3.9-4.5l0.2-4.9l-0.9-5.2 l4.9-3.6l3.3-5.3l0.4-8.6l-2.7-2.9l3.1-8l7.5-11.4l2.4-9l-3.9-4l-0.7-5.4l6.4-2.8l3.7-6.3l-2.9-5.4l0.7-6L3478.6,2880.6z"
						/>
						<path
							id="path1237"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="cerrito municipios mapaClick"
							d=" M3541.1,2924.4l4.8-6.9l5.4-1.3l2-5.9l2.7-1.7l8.4,0.2l1.3,2.1l4.6-1.1l2.3-2.9l3.8-1.5l4.6,1.7l-1.1,6.1l2.9,4.6l3.6,12.5l1.9-0.4 l2.3-6.7l1.9-3.2l2.3-5.9l4.8-0.8l6.9,5.5l8.6-0.8l0.6-2.7l3.8-0.2l5.4,1.9l4.2-0.4l5-4.8l9.2-2.6l4.6,1.3l1.6,3.3l4.9,3.7 l-1.1,2.3l1.4,3.5l-0.8,3.9l-1.3,1.7v3l3.2,2l3.5,1.4l0.8,4.9l1.9,7.2l-3,2.6l0.1,2.3l-3.8,3.4l-0.2,9.5l-1.5,2.5l2.3,1.5l1.6,5.8 l-1,5.6l-2.8,2l-0.9,6l2.4,4.1l-0.7,3.3l-2.7,1.4l1,4.1l-2,1.5l0.2,3.3l-1,2.8l1.7,4.1l-4.3,4.3l-3.6,1.2l-1,4.1l-5.6,7.6l-7.5-1.7 l-5.6,0.7l-3.2-1.7l-3.4-0.5l-4.6,6.4l-1.9-4.3l-1.2-6l-4.1-4.6l-3.6,3.8h-2.6l-1-2.9l-5-4.5l-5.5-0.7l-4.6,1l-3.8-1.9l-4.4,4 l1.7,3.6l-1.2,2.6l-3.8,1l-1,4h-3.4l-8.9-4.3l-3.2-0.7l-2.9-4.8l-1.7-4.1l-3.1-4.3l-2.7,0.2l-1.4,2.9l-3.4-3.1l-3.8,1.2l-6.3,3.4 l2.2-5.7l4.8-6.7l4.6-2.8l0.3-2.6l-4.4-8.1l4.6-5.5l0.2-5.7l-1.4-3.3l0.9-5.8l-1.4-6.9l2.4-3.3l1.9-4.1l-1.9-5.5l1.2-4.8l-1-3.1 l1-3.6l-5.5-1.5l-0.7-4.1l0.9-6l1.1-1.7L3541.1,2924.4z"
						/>
						<path
							id="path1239"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="concepcion municipios mapaClick"
							d=" M3659.2,2981.5l3.6-3.2l2.3,3.8l3.6-0.6l4.4-3.6l5,1.1l2.5-0.9l-3.6-3.8l1.7-2.9l6.7-2l2.3,3.5l0.2,7.5l1.5,1.7l-1.7,1.9l-1.9-0.6 l-3.6,5.6l4.4,4l3.8-1l2.5,2.7l0.6,9l5.9,5l-0.2,5.9l-4.6,4.4l-0.4,4l2.1,4.6l-4,0.6l-5,6.3l-1.5,2.5l1.9,1.5v15.9l5.9,2.3l-2.9,1 l2.7,12.1l-7.9,3.8l-3.4,2.1l-5.3-0.3l-4.4-3.6l-4.3,0.5l-2.7-5l-1.9-5.5l-8.2-0.3l-3.4,2.1l-6-0.5l-3.2,2.1l-9.9-10.3l-1,7.7 l1.7,4.5l-0.9,3.8l-3.8,3.4l-2.9-0.2l-3.4,2.6h-7.2v4l-2.9,0.5l-1.9,1.9l0.2,4.3l-6.5,3.3l-3.9-1.4h-2.9l-4.8,2.8h-7l-2.9,0.3 l-1.5,1.4l-3.4-1.7l-0.2-4l-4.3-0.5l-5.8-5.3l1.2-2.9l-7-1.2l-0.3-4l2.7-5l-8.9-8.8l-9.7-7.4l-3.1-1l-2.9-3.4l-2.2,0.2l-2.4-2.8 l1.2-3.3l-2-2.2l-0.9-6.4l-0.9-4v-4.1l-1.9-3.1l-0.7-7.4l6.3-3.4l3.8-1.2l3.4,3.1l1.4-2.9l2.7-0.2l3.1,4.3l1.7,4.1l2.9,4.8l3.2,0.7 l8.9,4.3h3.4l1-4l3.8-1l1.2-2.6l-1.7-3.6l4.4-4l3.8,1.9l4.6-1l5.5,0.7l5,4.5l1,2.9h2.6l3.6-3.8l4.1,4.6l1.2,6l1.9,4.3l4.6-6.4 l3.4,0.5l3.2,1.7l5.6-0.7l7.5,1.7l5.6-7.6l1-4.1l3.6-1.2l4.3-4.3l-1.7-4.1l1-2.8l-0.2-3.3l2-1.5l-1-4.1l2.7-1.4l0.7-3.3l-2.4-4.1 l0.9-6l2.8-2L3659.2,2981.5z"
						/>
						<path
							id="path1241"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="bolivar municipios mapaClick"
							d=" M2685.1,3577.8l-5.9,7.2l0.5,11.3l-3.3,3.6l-9.3,1.8l-3.6,5.4h-3.1l-3.1-3.1l-6.4-1.1l-4-5.5l0.1-5.2l1.2-3l1.7-7.6l3.6-2.2 l1.7-5.7l1.2-9.7l1.5-2.2l-0.4-3l-0.9-0.8v-2.4l-1.4-2.3l0.1-2.4l-1-1.4l-1.8-0.6l-3.1,2.6h-2.1l-0.8-2.6l-1.3-1.3v-2.8l1.5-1.5 l1-3.4l5.4-5.4l0.8-2.1h1.3l0.8-2.1l-4.1-4.4l-1.3-3.1l-4.1-1l-1.5-4.6l-5.9-2.6v-3.4l-3.9-4.4l-2.1-3.9l-1-3.4l2.3-1v-1l-2.3-1.8 v-1.5l-1.8-2.1l1.5-2.1l0.3-2.3l-1.3-1.3l0.8-2.8l1-0.5l-0.3-2.1l-1.3-0.3h-1l1.3-2.8l2.8-1.8l-0.3-4.1l-3.6-0.8l-1.8-1.5h-2.8 l-2.6-2.1l0.8-5.2l-3.6,1l-2.6-1.8h-0.8l-0.3,2.1h-2.6l-1.5-2.6h-2.1l-0.8,1l-2.3,0.3v3.4l-2.1,2.3l-0.5-2.6l-1.3,1.8l-1.3,0.3 l-1,2.1l-1.8,1.3l-1-2.4l-1.6,0.6l-0.8,2.2l-0.4-1l-2,0.6l-1.6,2.2l-2.4-1.4l-2.2,0.6l-1.8,4.4l-2.2,2.2l-1.6,8.3l-3,1l-1.4-1.8 l-0.8,1.8l-3.2,0.2l-2.4-1.6l-0.2-1.4l-0.8-2.2l-2,0.4l-1.2,1.6l-2.2,0.4l-3,2.2l-1.8-0.4l0.2-2.8l2-1.4l-1.6-1.8l-1.4-0.4 l-0.4-1.6l-5.7-2.2l-2-1.8l-0.8-3l-1.8-0.8l1-1.2l0.6-3.4l-0.8-3.6l-3-0.8l-2-3.2l-0.6-1.2l-2,0.8l-0.2,1.2l-2.4-0.2l-2.2,1.8 l-1.2-2.2h-1l-2.6,0.2l-1.4-1.8l-3.6-0.4l-2.6-2.6l-4.2-1.4l-1.8-2.8l1.4-2.4v-3.4l-2-2.4l-0.4-6.1l-1.4-0.4l0.4-2.8l2-1.6 l-1.2-2.6l-7.9-5.7l-0.2-3.4l-4.4-1.2l-2-4.7l2-4.2l2.8-5.3l-1.4-7.7l3.2-4.2l-4.4-1.8l-2,0.8h-4.4l-6.3-7.1l-5.3-0.4l-3.4-5.3 l-0.4-5.9l-2.6-1l-4-6.9l1-5.9l-1.6-3.8l-3-1.2l1.3-4.1l3.7-3.5l1.3-1.3l5.5-3.8l8.9,8.7l11.8,12.4l12.3,3.6l7.2,7.2l2.9-0.9 l1.9,3.3l2.6-0.5l0.9,3.8l9.6-0.5l2.1,2.4l-0.6,4.1l-2.1,0.8l-1.9,4.1l-0.2,3.5l-1.6,2.7l-2.7,0.6l0.4,1.8l4.3,1.8l2.3,3.3 l-1.2,4.3l-1,1l-0.8,4.7l1.8,0.6v5.3l1.4,1l2.7,5.8l-1.2,1.6v3.1l1.9,1.8l0.4,2.1h6l3.9,4.7l4.1,0.2l1.4-1l2.5,0.4l1.4,3.3l5.3,0.8 l3.3,1.2l3.9-2.7l2.5,0.6l1.9-1.4l7.2,3.9l0.6,0.6l4.1-0.2l2.3,2.5h1.4l2.5-2.5l1.9-0.4l1,0.6h4.7l2.5-3.5l6.2-5.6h1.2l3.5,3.1 l5.5-0.2l3.3,1.9l2.5,0.2l3.7,3.7l1.9,11.9l4.3,2.9l2.7-2.1l5.8,1.8l3.3,11.3l4.1,0.8l6.4-3.3l3.3-5.6l4.9-0.6l-0.4-1.8l0.8-2.1 l-0.6-4.9l2.7-0.4l-0.2-3.3l-1.6-2.5l3.3-4.5l0.4-5.5l2.1-2.1l-0.4-7.4l-2.7-2.1l-1,1.4l-4.1-1.8l-8-11.3l0.6-7.4l5.3-2.7l2.7-0.4 v-3.1l-4.9-2.3l2.5-1.6l0.8-4.3l1-1l1.9,3.3l13.4,0.6l-1.2-3.3l0.8-2.5l2.1-0.4l1.6,1.4l3.1-0.8l-0.7-4.1l9.7-6.5l8-8.4v-4.6 l2.4,2.9l2.3,2l1.9,1.4l0.9,3.5l0.8,3.2l1.4,1.3l4.1-2.7l2.4,0.9l2.9,2l1,2.7l0.8,5.9l1.3,1.8l2.7,4.2l1.7,2.6l3.5-0.7l0.7,2.6 l1.7,0.5l1.9-2.4l2.6,0.2l2,3.4h3.1l2.7,2.1l4.6-0.7l7.7,1.9l15.5-13.9l6.1,1.2l5.8-3.4l0.5-2.4l9.9-6.4l2.2,1.2l4.6-2.4l2.4,4.3 l3.4-1.5l2,1.5l9-7l4.4,2.7l6.1-3.8l7,4.3v5.8l2,4.5l2.2,6.5l4.5,2.4l5.1,1.3l2.6,4l4.7,0.7l0.7,7l3.1,4.1l2.7-1l0.3-3.6l3.6-3.3 l5.3-0.7l15.8-14.9l10.9-5l5.8-1l3.2,2.2l3.6-0.2l5.1,2.1l1.9,2.7l0.2,3.6l-1.6,6.8l0,0l0,0l1.4,6.1l5.8,2.4l2.7,3.8l1.7,5.2 l4.1,1.6l5-0.4l3.1,4.3l0.1,5.1l-0.5,4.1l1.4,2.6l0.1,4.9l-3.6,3.6l-4.2,0.2l-4.3,4.8l0.5,2.6l-1.4,5l1,2.2l1.5-0.3l1.5,2.1 l-1.4,2.6l3.2,4.5l-0.2,4.1l1.2,7.4l1.7,5l1.4,5.6l4.2,1.6l3.4,3.5l-3.7,7.1l-2.3,2.5l-2-0.9l-2.6,1.3l-1.8,3.3l-5.9,3.3l-3.7,0.4 l-0.3,6.5l-1.4,3.3l1.2,4.6l0.2,4.3l3.8,7.2l-3.5,2.9l-2.1,4.8l-3.4,2.6l-12.3-1.8l-10.4-4.1l-3.8-3.1l-4.8-1.4l-2.6-2.1l-6.7,2.8 l0.2-3.4l-5.5-6.9l-5.1-5.7h-2.2l-1.7-2.8l4.8-3.8l1-4l-1.3-2.2l1.7-6.6l10.7,0.1l5.5-3.2l6.8-3.3l4.8,0.2l5.3-1.7l5.8-5.3l0.5-7.9 l1.4-7.9l-1.9-4l-4.5-3.1l-3.1-4.8l0.5-6l-3.1-5.7v-4.5l-4.4-5.5l-3.4-6.9l-9.7,7.6l-3.1,1.5l-4-0.6l-3.2-3l-4.1-1l-3.9,0.4l-4-1.6 l-2.3-4l-0.2-5l-2.2-3.6l-4.1-0.7l-2.9-2.2l-4.4,1.2l-3.6,4l-3.4,2.2l-4.8,2.6l-4.4,1.2l-2.4,0.4l-1.7-6.2l-0.2-4.1l-1.7-1.7 l-2.4,1.9l-2.7-0.3l-1.9-4.5l-2.7,2.2c0,0-3.9-0.2-3.8-0.9s0-6,0-6l-1.7-4.6l-5.8-3.6l1.9-4.6l-3.6-3.3l-2.7,1.9l-3.6-3.3l-2.7,2.2 l-8.4-0.5l-3.2,2.2l-8.7-0.3l-12.6,8.8l-4.3,0.7l-2.7,2.6l-3.8,0.2l-0.5,3.8l-4.4,2.2l-3.4-1.9l-4.4,3.4l-7.2,0.3l-1.7-4v-5.3 l-3,2.1l-1.6-0.5l0,0l-2.4,0.4l0.5,2l1.7,1l0.1,2.4l-0.5,2.9l-1.9,2l0.3,1.7l2.3,1.9l2.7,1.9l2.3,1.8l1,1.4l-2,1.8l-1.6,2.2 l-3.4,0.4h-2.3l-3.8,1.2l-2.2,3.7v3.3l1.3,1.8l3.2,2.4l2.2,1.8l-1.5,1.9l-2.7,1.7l-2,2.2l-1,2.4l-0.3,2.7l-0.3,2.6l2.2,1.8l3,1.7 l1.5,2.2l-0.5,2.2l-2.5,1.3l0.3,2l1.1,1.9l-0.1,2.9l1.9,1.4l2.5,2.6l0.1,1l-2.3,0.8l-2.5-0.1l-2.5-0.1l-1.9,1.9l-3.4,2.9l-2.3,1.8 l-2.2,1.2l-0.7,3.5l2.7,4.8l-0.2,2.9l-3.2,2.9h-3.8l-0.3,2.6l-4.1,0.3l-1.9,4.3l-2.6,1.9l-0.3,3.6l-5.6,4.1l0.3,4.1l-2.2,2.6 l-5.1,1.7l1,1.7l3.8,1.5l-2,5.8l0.5,3.6l-3.2,5.8l-3.9,2.6v2.8h3.8l1.5,2.6l-6.5,3.8l4.2,4.8L2685.1,3577.8z"
						/>
						<path
							id="path1243"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="puerto-parra municipios mapaClick"
							d=" M2751.6,3153.1l-1-3l-1.7-4.3l-2.9-2.1l-2.1,0.2l-1.8-1.8l0.1-1.8l2.7,0.1l2.4,1.3l2.9-1.8l-0.7-6.4l1-1.4v-1.4l-2-1.3l-0.2-1.2 l2.1-1.9l-0.5-1.2l-2.7-2.5l0.5-2.3l-0.7-2.7l-2.1-1l-3.8,2.1l-2.5,2.6l-3-0.2l-1.8-0.8l-1.7,1.7l-0.8,1.8l-2.7-0.4l-2.5-2.9l1-3 l1.8-0.4l2.7,0.8l1.6-1.4l-0.2-4.7l3.2-1.6l0.7-4.2l-3.5-2.9l1-2.7l3.3-2.1l-1.6-2.7l-5-1.1l-1.2-1.4l0.8-2.6l3.9-0.5l0.8-2.9 l-1.1-1.7l-4.9,0.2l-2.4-2l0.6-3l1.1-0.8l-1.1-2l-3.9,0.2l-1.4-2.1l1-2.7l4.3-2.5l2.6,1.4l3.5-0.4l1.2-1.6l-0.1-2l-2.7-0.7 l-3.5-2.3l-3.9,0.6l-2.6,2l-2.6-1.6l1.4-2.4l3.6-0.6l1.3-1.3v-1.2l-1.8-0.7l0.1-2.3l2.4-1.7l3.2-0.2l-0.2-2.7l-3.8-0.7l-0.8-3 l-2.4-2.1l-2.6-0.2l5.4-7.6l6.2-3.4l0.6-3.3l8.8-1.9l5.3-4.8l3.4,0.9l-3.4,4.6l3.8,3.3l5.1-1.7l-3.2-2.6l0.5-3.3l5.1-0.3l3.6-3.6 l5.5,0.7v-2.2l-4.1-2.4l-1.9-6.7l6,0.5l-2.6-2.6l1.2-2.6l2.9,1l3.2,1.5l1.9-5.8l2.9-2.6l8-0.5l14.7-8.1l21.2-14.6l5.8,3.6l-3.2,1.4 l3.8,1.9l-4.3,4.6l3.8,1.5l0.7,5.8l2.7,5l-5.6,4.1l2.6,4.6l-3.2,0.5l2.9,4.1l-4.4,0.3l-2.2,5.3l-2.7-0.5l1.2,4.6l-4.3,3.3l3.6,4.6 l1.2,5.3l3.6-2.2l1.2,1.9l-3.4,2.8l1.7,2.4l2-1.4l3.4,5.3l2.6-0.2l5.8,4.6l3.4,0.2l1.5,3.3l4.1,2.2l1.4,4.1l4.3,1.4v3.3l3.1,4.5 l1.9-1.9l3.4,0.3l2.4-3.1l1.4,2.6l-0.7,4.1l1,3.8l2.4-4l1.5,4.6h3.9l0.7,1.7l-3.8,0.7l-0.3,2.8l5.8,1.5l-3.1,2.6l6.1,2.1l0.3,3.3 l3.6,1.7l-1.4,4.6l4.1,3.4l-5.1,5l6.5,4l5.8,5.8l-3.2,4l3.9,4.6l-1,4.1l3.8,2.4l3.4,4.3l6-1.5l1.2-4.5l3.8,1.2l4.3-2.1l4.3,4 l-0.7,6.5l3.9-3.4l1.2,4.6l2,2.6l-3.9,5.5l-8.7,4l3.9,5.8l0.5,5.7l3.6-1.2l1.4,2.8l4.1,1.4h8.9l6.1,5.2l-4.4,7.2l-2.6-0.7l-2.4,3.3 l-6.7-1.9l-3.8-3.8l-6.7-2.4l-7.7,1l-1.7,2.8l0.2,4.6l-10.8,10l-8.9,11.5l-3.2-3.6l-3.6,1l-3.8-2.8l-4.1,5.5l-3.2-2.7l2.5-6 l-2.5-4.1l1.9-4.3l-0.9-4.9l-4.8,0.1l-6-4.7l-2.8,0.9l-4-2.4l-5.7,4.6l-5.4,0.4l-4.1,3.5l-4.1-0.3l-3.3-4.1l0.2-5.3l-3.9-2.9 l-3.6-0.3v-3.3l-4.8-3.8l-6.3-0.9l-5.3-4.8l-5.8,0.2l-8.2-5.2l-7.2-0.5l-2.6-2.4l-8.4,2.4l-4.8-0.2l-3.4-3.1h-3.8l-2.2,2.5 L2751.6,3153.1z"
						/>
						<path
							id="path1245"
							inkscape:connector-curvature= "0"
							sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
							class="cimitarra municipios mapaClick"
							d=" M2490.9,3333.4l14-8.1l7.8-6.5l4.4-9.5l5-9.1l1.2-6.3l6.9-13.8l6.3-5.7l5.3-1.7l4.6-0.3l1.2,1.4l11-0.1l5.3-3.3l1.8-8.5l-2.6-7.3 l-10-9l-6.6-12.6l0.6-7.6l3-4.4l2.2-6l1.5-0.6l0.3-3.7l-0.9-1l-0.5-8.5l-2.1-5.5l-4.4-3.3l-2.2-7.2l-1.9-1l0.6-4.5l2.2-1.7l1.2-8.1 l-2.1-7.6l5.7-5.8l5.1-4.9l4.1-1.4l1.6-5.6l0.6-2.5l1.4-1.4l0.8,1.1l3.9-2l3.9-5.1l0.6-2.5l8.7-0.8l5.9,1.4l1.7,2.3l4.8-0.3v-3.1 l0.8-2.3l2.5-0.8l1.4-2.3v-2.3l3.9-5.1l3.1-0.8l3.7,0.6h3.7l2-2.8l5.4-1.1l3.4,0.3l5.6-3.7l4.5-1.1l6.8-9.6l3.1-7.6l9.3-8.5 l14.4-4.8h3.4l2.3,1.1l4.2-1.7l5.4-0.6l3.4-3.9l4.5-2.5l3.1-0.6l5.6-6.5l1.1-3.9l4.2-2.3l4.8-4.7l6.9-3.8l4-2.8l2.6,0.3l2.4,2 l0.8,3l3.8,0.7l0.2,2.7l-3.2,0.2l-2.4,1.7l-0.1,2.3l1.8,0.7v1.2l-1.3,1.3l-3.6,0.6l-1.4,2.4l2.6,1.6l2.6-2l3.9-0.6l3.5,2.3l2.7,0.7 l0.1,2l-1.2,1.6l-3.5,0.4l-2.6-1.4l-4.3,2.5l-1,2.7l1.4,2.1l3.9-0.2l1.1,2l-1.1,0.8l-0.6,3l2.4,2l4.9-0.2l1.1,1.7l-0.8,2.9 l-3.9,0.5l-0.8,2.6l1.2,1.4l5,1.1l1.6,2.7l-3.3,2.1l-1,2.7l3.5,2.9l-0.7,4.2l-3.2,1.6l0.2,4.7l-1.6,1.4l-2.7-0.8l-1.8,0.4l-1,3 l2.5,2.9l2.7,0.4l0.8-1.8l1.7-1.7l1.8,0.8l3,0.2l2.5-2.6l3.8-2.1l2.1,1l0.7,2.7l-0.5,2.3l2.7,2.5l0.5,1.2l-2.1,1.9l0.2,1.2l2,1.3 v1.4l-1,1.4l0.7,6.4l-2.9,1.8l-2.4-1.3l-2.7-0.1l-0.1,1.8l1.8,1.8l2.1-0.2l2.9,2.1l1.7,4.3l1,3l3.7,0.5l2.2-2.5h3.8l3.4,3.1 l4.8,0.2l8.4-2.4l2.6,2.4l7.2,0.5l8.2,5.2l5.8-0.2l5.3,4.8l6.3,0.9l4.8,3.8v3.3l3.6,0.3l3.9,2.9l-0.2,5.3l3.3,4.1l4.1,0.3l4.1-3.5 l5.4-0.4l5.7-4.6l4,2.4l2.8-0.9l6,4.7l4.8-0.1l0.9,4.9l-1.9,4.3l2.5,4.1l-2.5,6l3.2,2.7l12.1,15.3l26.6,21.5l1.5,4l-5.4,5.8l-0.6,4 l-4.1,6.9v7.5l-6.4,10.3l1.8,4.7l-5.9,7.1l-6.3,3.4l-4.4,5.8l-2.5,7.7l1.3,5.6l-0.1,8.8l-3.7,2.7l-0.6,3.7l-5.4,2.1l-12.3-4.3 l-1.3,6.9l-2.6,3.5l-5-5.3l-2.2,5.2l-6,4.3l-7.3,2.7l-4.7-0.3l-1,5.5l-10.1,9.6l-8.6,2.2l-8.9,7.2l-5.6,2.1l-2.2,4l-5.3-0.4 l-4.7,3.1l-3.7-0.1l-4.4,3.6l-9.5,0.4l-5.4,1.1l-2.8,2.6l-2.9,4.2l-7.5-3.4l-4.8,0.3l-3.5-4.3l-3.5,0.1l-1-2.7l-2.9-2l-2.4-0.9 l-4.1,2.7l-1.4-1.3l-0.8-3.2l-0.9-3.5l-1.9-1.4l-2.3-2l-2.4-2.9v4.6l-8,8.4l-9.7,6.5l0.7,4.1l-3.1,0.8l-1.6-1.4l-2.1,0.4l-0.8,2.5 l1.2,3.3l-13.4-0.6l-1.9-3.3l-1,1l-0.8,4.3l-2.5,1.6l4.9,2.3v3.1l-2.7,0.4l-5.3,2.7l-0.6,7.4l8,11.3l4.1,1.8l1-1.4l2.7,2.1l0.4,7.4 l-2.1,2.1l-0.4,5.5l-3.3,4.5l1.6,2.5l0.2,3.3l-2.7,0.4l0.6,4.9l-0.8,2.1l0.4,1.8l-4.9,0.6l-3.3,5.6l-6.4,3.3l-4.1-0.8l-3.3-11.3 l-5.8-1.8l-2.7,2.1l-4.3-2.9l-1.9-11.9l-3.7-3.7l-2.5-0.2l-3.3-1.9l-5.5,0.2l-3.5-3.1h-1.2l-6.2,5.6l-2.5,3.5h-4.7l-1-0.6l-1.9,0.4 l-2.5,2.5h-1.4l-2.3-2.5l-4.1,0.2l-0.6-0.6l-7.2-3.9l-1.9,1.4l-2.5-0.6l-3.9,2.7l-3.3-1.2l-5.3-0.8l-1.4-3.3l-2.5-0.4l-1.4,1 l-4.1-0.2l-3.9-4.7h-6l-0.4-2.1l-1.9-1.8v-3.1l1.2-1.6l-2.7-5.8l-1.4-1v-5.3l-1.8-0.6l0.8-4.7l1-1l1.2-4.3l-2.3-3.3l-4.3-1.8 l-0.4-1.8l2.7-0.6l1.6-2.7l0.2-3.5l1.9-4.1l2.1-0.8l0.6-4.1l-2.1-2.4l-9.6,0.5l-0.9-3.8l-2.6,0.5l-1.9-3.3l-2.9,0.9l-7.2-7.2 l-12.3-3.6l-11.8-12.4l0,0l-8.8-8.8L2490.9,3333.4z"
						/>  -->
					</g>

					
          <g id="layer6" inkscape:groupmode="layer" inkscape:label="Nombres">
						<text transform="matrix(1 0 0 1 733.7092 995.7245)" class="st5 st6 nombres">Palmar</text>
						<text transform="matrix(1 0 0 1 694.0212 968.894)" class="st5 st6 nombres">Hato</text>
						<text transform="matrix(1 0 0 1 759.8948 971.9391)" class="st5 st6 nombres">Cabrera</text>
						<text transform="matrix(1 0 0 1 836.4905 981.3859)" class="st5 st6 nombres">San Gil</text>
						<text transform="matrix(1 0 0 1 795.051 999.0676)" class="st5 st6 nombres fondo">Pinchote</text>
						<text transform="matrix(1 0 0 1 746.6073 1032.0092)" class="st5 st6 nombres">El Socorro</text>
						<text transform="matrix(1 0 0 1 847.0934 1037.5458)" class="st5 st6 nombres">Valle de</text>
						<text transform="matrix(1 0 0 1 847.0934 1052.5458)" class="st5 st6 nombres">San José</text>
						<text transform="matrix(1 0 0 1 826.3318 1092.3563)" class="st5 st6 nombres">Ocamonte</text>
						<text transform="matrix(1 0 0 1 798.6497 1064.6742)" class="st5 st6 nombres">Páramo</text>
						<text transform="matrix(1 0 0 1 753.251 1096.2318)" class="st5 st6 nombres">Confines</text>
						<text transform="matrix(1 0 0 1 718.3716 1061.3523)" class="st5 st6 nombres">Palmas del</text>
						<text transform="matrix(1 0 0 1 718.3716 1076.3523)" class="st5 st6 nombres">Socorro</text>
						<text transform="matrix(1 0 0 1 660.239 1085.7126)" class="st5 st6 nombres">Chimá</text>
						<text transform="matrix(1 0 0 1 925.9874 1005.9881)" class="st5 st6 nombres">Mogotes</text>
						<text transform="matrix(1 0 0 1 980.2444 1047.5112)" class="st5 st6 nombres">San</text>
						<text transform="matrix(1 0 0 1 980.2444 1062.5112)" class="st5 st6 nombres">Joaquín</text>
						<text transform="matrix(1 0 0 1 1013.463 1115.0557)" class="st5 st6 nombres">Onzaga</text>
						<text transform="matrix(1 0 0 1 907.7173 1165.9907)" class="st5 st6 nombres">Coromoro</text>
						<text transform="matrix(1 0 0 1 807.4946 1158.5704)" class="st5 st6 nombres">Charalá</text>
						<text transform="matrix(1 0 0 1 875.1703 1264.3733)" class="st5 st6 nombres">Encino</text>
						<text transform="matrix(1 0 0 1 709.7201 1353.1604)" class="st5 st6 nombres">Gámbita</text>
						<text transform="matrix(1 0 0 1 689.7747 1239.577)" class="st5 st6 nombres">Suaita</text>
						<text transform="matrix(1 0 0 1 652.0306 1174.1991)" class="st5 st6 nombres">Guadalupe</text>
						<text transform="matrix(1 0 0 1 747.7385 1175.5471)" class="st5 st6 nombres">Oiba</text>
						<text transform="matrix(1 0 0 1 703.2546 1112.8651)" class="st5 st6 nombres">Guapotá</text>
						<text transform="matrix(1 0 0 1 544.8647 1058.2711)" class="st5 st6 nombres">Santa Helena</text>
						<text transform="matrix(1 0 0 1 544.8647 1073.2711)" class="st5 st6 nombres">del Opón</text>
						<text transform="matrix(1 0 0 1 585.9786 1120.9531)" class="st5 st6 nombres">Contratación</text>
						<text transform="matrix(1 0 0 1 563.7367 1149.2611)" class="st5 st6 nombres">El Guacamayo</text>
						<text transform="matrix(1 0 0 1 590.0227 1193.0712)" class="st5 st6 nombres">Aguada</text>
						<text transform="matrix(1 0 0 1 531.3848 1182.2871)" class="st5 st6 nombres">La Paz</text>
						<text transform="matrix(1 0 0 1 574.5208 1244.9691)" class="st5 st6 nombres">San Benito</text>
						<text transform="matrix(1 0 0 1 572.4988 1281.3651)" class="st5 st6 nombres">Güepsa</text>
						<text transform="matrix(1 0 0 1 527.3407 1260.4711)" class="st5 st6 nombres">Chipatá</text>
						<text transform="matrix(1 0 0 1 477.4648 1195.0931)" class="st5 st6 nombres">Vélez</text>
						<text transform="matrix(1 0 0 1 401.9768 1145.8912)" class="st5 st6 nombres">Landázuri</text>
						<text transform="matrix(1 0 0 1 231.5428 1059.723)" class="st5 st6 nombres">Cimitarra</text>
						<text transform="matrix(1 0 0 1 191.8274 1288.6964)" class="st5 st6 nombres">Bolívar</text>
						<text transform="matrix(1 0 0 1 384.9697 1252.7321)" class="st5 st6 nombres">El Peñón</text>
						<text transform="matrix(1 0 0 1 347.6732 1309.3428)" class="st5 st6 nombres">Sucre</text>
						<text transform="matrix(1 0 0 1 282.4045 1372.6134)" class="st5 st6 nombres">La Belleza</text>
						<text transform="matrix(1 0 0 1 350.3372 1425.228)" class="st5 st6 nombres">El Florián</text>
						<text transform="matrix(1 0 0 1 400.2878 1400.5857)" class="st5 st6 nombres">Jesús María</text>
						<text transform="matrix(1 0 0 1 404.2839 1451.8684)" class="st5 st6 nombres">Albania</text>
						<text transform="matrix(1 0 0 1 495.5269 1402.5837)" class="st5 st6 nombres">Puente</text>
						<text transform="matrix(1 0 0 1 495.5269 1417.5837)" class="st5 st6 nombres">Nacional</text>
						<text transform="matrix(1 0 0 1 472.8826 1345.3071)" class="st5 st6 nombres">Guavatá</text>
						<text transform="matrix(1 0 0 1 543.4794 1332.653)" class="st5 st6 nombres">Barbosa</text>
						<text transform="matrix(1 0 0 1 312.3748 903.7441)" class="st5 st6 nombres">Puerto Parra</text>
						<text transform="matrix(1 0 0 1 398.2898 837.1433)" class="st5 st6 nombres">Simacota</text>
						<text transform="matrix(1 0 0 1 571.0319 757.9562)" class="st5 st6 nombres">San Vicente</text>
						<text transform="matrix(1 0 0 1 571.0319 772.9562)" class="st5 st6 nombres">del Chucurí</text>
						<text transform="matrix(1 0 0 1 734.5803 823.498)" class="st5 st6 nombres">Zapatoca</text>
						<text transform="matrix(1 0 0 1 714.979 904.3535)" class="st5 st6 nombres">Galán</text>
						<text transform="matrix(1 0 0 1 528.7666 908.6412)" class="st5 st6 nombres">Carmen de Chucurí</text>
						<text transform="matrix(1 0 0 1 416.6577 509.7371)" class="st5 st6 nombres">Puerto Wilches</text>
						<text transform="matrix(1 0 0 1 427.135 648.0378)" class="st5 st6 nombres">Barrancabermeja</text>
						<text transform="matrix(1 0 0 1 552.1646 481.7975)" class="st5 st6 nombres">Sabana de Torres</text>
						<text transform="matrix(1 0 0 1 774.2842 483.1945)" class="st5 st6 nombres">Rionegro</text>
						<text transform="matrix(1 0 0 1 793.1433 388.8984)" class="st5 st6 nombres">El Playón</text>
						<text transform="matrix(1 0 0 1 916.7759 421.7275)" class="st5 st6 nombres">Suratá</text>
						<text transform="matrix(1 0 0 1 969.1625 478.3052)" class="st5 st6 nombres">California</text>
						<text transform="matrix(1 0 0 1 976.1472 506.2446)" class="st5 st6 nombres">Vetas</text>
						<text transform="matrix(1 0 0 1 862.2937 494.3704)" class="st5 st6 nombres">Matanza</text>
						<text transform="matrix(1 0 0 1 726.7869 587.9679)" class="st5 st6 nombres">Lebrija</text>
						<text transform="matrix(1 0 0 1 804.2888 611.0181)" class="st5 st6 nombres">BUCARAMANGA</text>
						<text transform="matrix(1 0 0 1 946.1124 605.4302)" class="st5 st6 nombres">Tona</text>
						<text transform="matrix(1 0 0 1 905.6 543.9631)" class="st5 st6 nombres">Charta</text>
						<text transform="matrix(1 0 0 1 845.53 659.2139)" class="st5 st6 nombres">Floridablanca</text>
						<text transform="matrix(1 0 0 1 794.5403 706.7109)" class="st5 st6 nombres">Girón</text>
						<text transform="matrix(1 0 0 1 656.2394 686.4548)" class="st5 st6 nombres">Betulia</text>
						<text transform="matrix(1 0 0 1 872.771 722.0776)" class="st5 st6 nombres">Piedecuesta</text>
						<text transform="matrix(1 0 0 1 955.7261 739.0325)" class="st5 st6 nombres">Santa</text>
						<text transform="matrix(1 0 0 1 955.7261 754.0325)" class="st5 st6 nombres">Bárbara</text>
						<text transform="matrix(1 0 0 1 1019.8206 739.4541)" class="st5 st6 nombres">Guaca</text>
						<text transform="matrix(1 0 0 1 1118.0708 765.1763)" class="st5 st6 nombres">Cerrito</text>
						<text transform="matrix(1 0 0 1 994.9417 833.4877)" class="st5 st6 nombres">San Andrés</text>
						<text transform="matrix(1 0 0 1 833.8618 817.464)" class="st5 st6 nombres">Los Santos</text>
						<text transform="matrix(1 0 0 1 768.7406 931.023)" class="st5 st6 nombres">Barichara</text>
						<text transform="matrix(1 0 0 1 799.3312 893.3389)" class="st5 st6 nombres">Villanueva</text>
						<text transform="matrix(1 0 0 1 867.1625 872.5017)" class="st5 st6 nombres">Jordán</text>
						<text transform="matrix(1 0 0 1 921.2502 882.6986)" class="st5 st6 nombres">Aratoca</text>
						<text transform="matrix(1 0 0 1 947.8508 861.8616)" class="st5 st6 nombres">Cepitá</text>
						<text transform="matrix(1 0 0 1 896.4231 936.7864)" class="st5 st6 nombres">Curití</text>
						<text transform="matrix(1 0 0 1 996.175 911.8842)" class="st5 st6 nombres">Molagavita</text>
						<text transform="matrix(1 0 0 1 1119.5208 840.0955)" class="st5 st6 nombres">Concepción</text>
						<text transform="matrix(1 0 0 1 1162.5342 908.7885)" class="st5 st6 nombres">Carcasí</text>
						<text transform="matrix(1 0 0 1 1111.175 968.4935)" class="st5 st6 nombres">San Miguel</text>
						<text transform="matrix(1 0 0 1 1174.7319 999.3091)" class="st5 st6 nombres">Macaravita</text>
						<text transform="matrix(1 0 0 1 1077.7916 1002.519)" class="st5 st6 nombres">Capitanejo</text>
						<text transform="matrix(1 0 0 1 1058.5319 871.553)" class="st5 st6 nombres">Málaga</text>
						<text transform="matrix(1 0 0 1 1100.2611 911.3564)" class="st5 st6 nombres">Enciso</text>
						<text transform="matrix(1 0 0 1 1050.828 928.6902)" class="st5 st6 nombres">San José</text>
						<text transform="matrix(1 0 0 1 1050.828 943.6902)" class="st5 st6 nombres">de Miranda</text>
					</g>
        </svg>
      </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js"integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>


    <script>
    $("img").each(function(index, el) {
            $(this).attr("data-bs-toggle", "tooltip");
            $(this).attr("data-bs-placement", "left");
            tooltip = new bootstrap.Tooltip($(this)[0], {})
      
    });

    $(".mapaClick").click(function(event) {
        location.href = $(this).data("url");
    });
    </script>
</body>

</html>

<style>
  .content-map {
    background-color: #ffffff !important;
    padding: 20px 0;
  }

  
  #mapa {
    background-color: transparent;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: auto;
    margin: 0 auto;
    text-align: center;
    padding: 0.1px 0;
  }
    #mapa svg{
      max-width: 950px;

      width: 100%;
      
  }
  
  #mapa svg path{
      fill: #fff;
      transition: all .4s;
  }
  #mapa svg path:hover{
      fill: #636363
  }

  #mapa img {
    position: absolute;
  }
  
</style>