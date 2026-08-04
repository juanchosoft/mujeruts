<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
define("DS", DIRECTORY_SEPARATOR);
require_once __DIR__ . '/../classes/Colombia.php';
require_once __DIR__ . '/../db/colores.php';

$arr = array('codigo' => Util::getDepartamentoPrincipal());
$data = Colombia::getDepartamentoByCodigoCiudadesAccionUnificadaVisitas($arr);
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
	<!-- <div class="content-map infoMapa">
		<div class="titles_jurisdicciones btll">
		</div>
	</div> -->

	<style>
		.nombres {
			font-family: "IBM Plex Sans", sans-serif !important;
		}

		.fondo {
			background-color: #FC0707;
			padding: 2px 4px;
			/* Añade un poco de espacio alrededor del texto */
			color: white;
			/* Asegura que el texto sea legible */
			display: inline-block;
			/* Asegura que el fondo solo cubra el texto */
		}
	</style>


	<div id="contenido-mapa" class="cuerpoMapa w-12">
		<svg xmlns="http://www.w3.org/2000/svg"
     viewBox="-50 -30 950 900"
     width="100%"
     height="auto"
     preserveAspectRatio="xMidYMid meet"
     style="max-width: 100%; display: block;">


			<?php foreach ($santander as $key => $value) : ?>
				<g id="<?php echo strtoupper($value['id']); ?>">
					<path id="<?php echo strtoupper($value['path']); ?>" 
						d="<?php echo $value['d']; ?>"
						fill="<?php echo getColorByNum($value['num_val']); ?>"
						class="municipios mapaClick <?php echo getClasePorcentaje($value['porcentaje_participacion']); ?>"
						data-url="<?php echo getUrl() . 'estado_municipios.php?mun=' . $value['codigo_muncipio'] . '&dep=' . $value['codigo_departamento']; ?>"
						data-name="<?php echo strtolower($value['municipio']); ?>"
						title="<?php echo strtoupper(str_replace("-", " ", $value['nombre_mapa'])); ?>" stroke="#000000"
						stroke-miterlimit="10"
						stroke-width="0.3px"
						></path><text transform="translate(264.48 382.8)"
						font-family="IBM Plex Sans" font-size="10" font-weight="500">
					</text>
				</g>
			<?php endforeach; ?>

				<!-- Coordenadas de los nombres de los municipios de santander -->
				<?php require_once 'nombres_mapa_santander.php' ?>
		</svg>
	</div>


	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>


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

	#mapa svg {
		max-width: 950px;

		width: 100%;

	}

	#mapa svg path {
		fill: #fff;
		transition: all .4s;
	}

	#mapa svg path:hover {
		fill: #636363
	}

	#mapa img {
		position: absolute;
	}
</style>