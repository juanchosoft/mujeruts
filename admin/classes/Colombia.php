<?php
require_once 'SessionData.php';
require_once 'DbConection.php';
require_once 'Util.php';
/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Colombia
{

    public function __construct() {}

    public static function getAll($rqst)
    {

        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT * FROM " . $db->getTable('tbl_colombia');
        if ($id > 0) {
            $q = "SELECT * FROM " . $db->getTable('tbl_colombia') . " WHERE id = " . $id;
        }
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
    }

    public static function getInformacionSecretariaHaciendaColoresMapa($rqst)
    {
        $default = 'Operativos Contrabando licores';
        $accion = isset($rqst['accion']) ? urldecode($rqst['accion']) : $default;

        if($accion == 'Capacitacion Fiscal y Financiera'){
            $accion = $default;
        }
        
        $secretariaId = Util::getSecretariaIdHacienda();
        $departamento = Util::getDepartamentoPrincipal();

        $accionesPermitidas = [
            'Capacitacion Fiscal y Financiera',
            'Operativos Contrabando licores',
            'Operativos Contrabando cigarrillos',
            'Operativos Contrabando cerveza',
            'Impuesto Vehicular Recaudado',
            'Recaudo del impuesto al consumo',
            'Recaudo del impuesto de registro',
            'Impuesto Estampillas Recaudado'
        ];

        if (!in_array($accion, $accionesPermitidas)) {
            return Util::error_missing_data();
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        // Puntajes secretaria
        $qPuntaje = "SELECT * FROM " . $db->getTable('tbl_puntajes_secretarias') . " WHERE tbl_secretaria_id = " . $secretariaId;
        $resultPuntajes = $pdo->query($qPuntaje);
        $puntajes = $resultPuntajes ? $resultPuntajes->fetchAll(PDO::FETCH_ASSOC) : [];

        // Query principal
        $query = "
            SELECT 
                ciudades.*,
                SUM(h.total_valor) AS total_valor
            FROM  " . $db->getTable('tbl_ciudades_accion_unificada') . " AS ciudades
            LEFT JOIN (
                SELECT
                    h.tbl_municipio_id,
                    CASE
                        WHEN h.accion = 'Operativos Contrabando licores' THEN h.incautacion_licores
                        WHEN h.accion = 'Operativos Contrabando cigarrillos' THEN (h.incautacion_cigarrillos + h.incautacion_tabaco)
                        WHEN h.accion = 'Operativos Contrabando cerveza' THEN h.incautacion_cerveza
                        WHEN h.accion = 'Capacitacion Fiscal y Financiera' THEN h.cantidad_personas
                        WHEN h.accion = 'Impuesto Vehicular Recaudado' THEN h.valor_recaudo_impuesto_vehicular
                        WHEN h.accion = 'Recaudo del impuesto al consumo' THEN (h.valor_importado + h.valor_nacional)
                        WHEN h.accion = 'Recaudo del impuesto de registro' THEN (h.valor_tramite + h.valor_recaudo)
                        WHEN h.accion = 'Impuesto Estampillas Recaudado' THEN h.valor_estampilla
                        ELSE 0
                    END AS total_valor
                FROM " . $db->getTable('tbl_hacienda') . " AS h
                WHERE h.accion = :accion
            ) AS h ON h.tbl_municipio_id = ciudades.codigo_muncipio
            WHERE ciudades.codigo_departamento = :departamento
            GROUP BY ciudades.codigo_muncipio, ciudades.municipio
            ORDER BY ciudades.codigo_muncipio ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['accion' => $accion, 'departamento' => $departamento]);  
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $arr = [];
        if ($result) {
            // 🎨 Asignar color específico por acción
            $colorPorAccion = [
                'Operativos Contrabando licores' => '#C0EDCE',
                'Operativos Contrabando cigarrillos' => '#27C8F5',
                'Operativos Contrabando cerveza'  => '#FFFF00',
                'Capacitacion Fiscal y Financiera' => '#8A2BE2',
                'Impuesto Vehicular Recaudado' => '#4169E1',
                'Recaudo del impuesto al consumo' =>  "#DC143C",
                'Recaudo del impuesto de registro' => "#FFA500",
                'Impuesto Estampillas Recaudado' => '#EDC0DF'
            ];
            foreach ($result as $valor) {
                $total = $valor['total_valor'] ?? 0;
                $color = ($total > 0) ? ($colorPorAccion[$accion] ?? '#999999') : Util::getColorNeutroMapa();
                $valor['color'] = $color;
                $arr[] = $valor;
            }

            $response = ['output' => ['valid' => true, 'response' => $arr, 'puntajes' => $puntajes]];
        } else {
            $response = Util::error_no_result();
        }

        $db->closeConect();
        return $response;
    }

    public static function getInformacionSecretariaColoresMapa($rqst)
    {
        $secretariaId = isset($rqst['secretariaId']) ? intval($rqst['secretariaId']) : 0;
        $departamento = isset($rqst['codigoMunicipio']) ? intval($rqst['codigoMunicipio']) : 0;
        $accion = isset($rqst['accion']) ? ($rqst['accion']) : 'Capacitacion+Fiscal+y+Financiera';

        // Si es Secretaria Hacienda
        if ($secretariaId == Util::getSecretariaIdHacienda()) {
            return self::getInformacionSecretariaHaciendaColoresMapa($rqst);
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        $colorDefecto = Util::getColorNeutroMapa();

        // Informacion de los puntajes de secretaria
        $qPuntaje = "SELECT * FROM " . $db->getTable('tbl_puntajes_secretarias') . " WHERE tbl_secretaria_id = " . $secretariaId;
        $resultPuntajes = $pdo->query($qPuntaje);
        $puntajes = array();
        if ($resultPuntajes) {
            foreach ($resultPuntajes as $valor) {
                $puntajes[] = $valor;
            }
        }

        $q = "
        SELECT 
            tbl_ciudades_accion_unificada.codigo_muncipio, 
            tbl_ciudades_accion_unificada.municipio, 
            tbl_ciudades_accion_unificada.d, 
            tbl_ciudades_accion_unificada.name, 
            tbl_ciudades_accion_unificada.class, 
            SUM(CASE 
                WHEN tbl_factores.tbl_secretaria_id = :secretariaId THEN tbl_ingreso_informacion.valor 
                ELSE 0 
            END) AS suma,
            tbl_ingreso_informacion.codigo_departamento,
            COALESCE(MAX(CASE 
                WHEN tbl_factores.tbl_secretaria_id = :secretariaId THEN tbl_factores.tbl_secretaria_id 
                ELSE 0 
            END), 0) AS tbl_secretaria_id
        FROM 
            " . $db->getTable('tbl_ciudades_accion_unificada') . " 
        LEFT JOIN 
            " . $db->getTable('tbl_ingreso_informacion') . "  
            ON tbl_ingreso_informacion.codigo_municipio = tbl_ciudades_accion_unificada.codigo_muncipio 
        LEFT JOIN 
            " . $db->getTable('tbl_factores') . "  
            ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id 
            AND tbl_factores.tbl_secretaria_id = :secretariaId
        WHERE 
            tbl_ciudades_accion_unificada.codigo_departamento = $departamento 
        GROUP BY 
            tbl_ciudades_accion_unificada.codigo_muncipio,
            tbl_ciudades_accion_unificada.municipio, 
            tbl_ciudades_accion_unificada.d, 
            tbl_ciudades_accion_unificada.name, 
            tbl_ciudades_accion_unificada.class,
            tbl_ingreso_informacion.codigo_departamento
        ORDER BY 
            tbl_ciudades_accion_unificada.municipio;
        ";


        $stmt = $pdo->prepare($q);
        $stmt->bindParam(':secretariaId', $secretariaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $arr = array();

        if ($result) {
            foreach ($result as $valor) {
                $color = null;
                $suma = floatval($valor['suma']);

                if (!empty($puntajes)) {

                    if ($valor['tbl_secretaria_id'] > 0) {
                        foreach ($puntajes as $p) {
                            if (
                                $suma >= floatval($p['rango_desde']) &&
                                $suma <= floatval($p['rango_hasta'])
                            ) {
                                $color = $p['color'];
                                break;
                            }
                        }
                    }
                }

                // Si no se encontró color, usar color por defecto
                if (empty($color)) {
                    $color = $colorDefecto;
                }

                $valor['color'] = $color;
                $arr[] = $valor;
            }

            $arrjson = array('output' => array('valid' => true, 'response' => $arr, 'puntajes' => $puntajes));
        } else {
            $arrjson = Util::error_no_result();
        }

        $db->closeConect();
        return $arrjson;
    }

    /**
     * Metodo para mostrar mapa, segun los proyectos ingresados
     * Ingreso Información Proyectos Alcaldías con ayuda de Secretarías Gobernación
     * Ingreseo de tbl_ministerios_proyectos en ruta proyectos_alcaldias.php
     */
    public static function getInformacionResumenAlcaldiasBySecretariaColoresMapa($rqst)
    {
        $secretariaId = isset($rqst['secretariaId']) ? intval($rqst['secretariaId']) : 0;
        $departamento = isset($rqst['codigoMunicipio']) ? intval($rqst['codigoMunicipio']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $tablaCiudades = $db->getTable('tbl_ciudades_accion_unificada');
        $tablaNotificaciones = $db->getTable('tbl_notificaciones_secretaria');

        // Obtenemos todos los municipios del departamento
        // y contaremos total de proyectos y no leídos por separado con subconsultas individuales

        $sql = "
        SELECT 
            c.id,
            c.codigo_departamento,
            c.codigo_muncipio,
            c.path,
            c.name,
            c.class,
            c.d,
            c.latitud,
            c.longitud,
            c.municipio,
            c.porcentaje_participacion,
            c.puntaje,
            c.color,
            c.carpeta_mapa,
            c.carpeta_svg,
            c.nombre_mapa,
            c.mostrar_barrio,
            c.viewbox_svg,

            (
                SELECT COUNT(*) 
                FROM $tablaNotificaciones 
                WHERE codigo_municipio = c.codigo_muncipio 
                AND tbl_secretaria_id = :secretariaId
            ) AS total_proyectos,

            (
                SELECT COUNT(*) 
                FROM $tablaNotificaciones 
                WHERE codigo_municipio = c.codigo_muncipio 
                AND tbl_secretaria_id = :secretariaId 
                AND leido = 'no'
            ) AS proyectos_no_leidos

        FROM $tablaCiudades c

        WHERE c.codigo_departamento = :departamento
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':secretariaId', $secretariaId, PDO::PARAM_INT);
        $stmt->bindParam(':departamento', $departamento, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];

        foreach ($result as $fila) {
            $noLeidos = intval($fila['proyectos_no_leidos']);
            $total = intval($fila['total_proyectos']);

            if ($total > 0 && $noLeidos === 0) {
                $fila['color'] = "#62af0a"; // Verde: todos leídos
            } elseif ($noLeidos > 0) {
                $fila['color'] = "#DC143C"; // Rojo: hay sin leer
            } else {
                $fila['color'] = Util::getColorNeutroMapa(); // Neutro: sin proyectos
            }

            $data[] = $fila;
        }

        $db->closeConect();

        if (!empty($data)) {
            return ['output' => ['valid' => true, 'response' => $data]];
        } else {
            return Util::error_no_result();
        }
    }




    // Acá en esta consulta devuelve ese número 
    public static function getDepartamentoByCodigo($rqst)
    {

        $codigo = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $qSub = " (SELECT Count(tbVisita.tbl_municipio_id) AS CuentaDetbl_municipio_id FROM " . $db->getTable('tbl_visitas') . " as tbVisita WHERE tbVisita.tbl_municipio_id = tbl_ciudades.codigo_muncipio ) as num_val";

        $q = "SELECT tbl_ciudades.*, $qSub  FROM " . $db->getTable('tbl_ciudades') . " WHERE codigo_departamento = " . $codigo;

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
    }

    /**
     * Metodo para mostrar las visitas del gobernador de las ciudades de accion unificada
     */
    public static function getDepartamentoByCodigoCiudadesAccionUnificadaVisitas($rqst)
    {

        $codigo = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $qSub = " (SELECT Count(tbVisita.tbl_municipio_id) AS num_val 
        FROM " . $db->getTable('tbl_visitas') . " as tbVisita WHERE tbVisita.tbl_municipio_id = tbl_ciudades_accion_unificada.codigo_muncipio ) as num_val";

        $q = "SELECT tbl_ciudades_accion_unificada.*, $qSub  FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " WHERE codigo_departamento = " . $codigo;

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
    }

    public static function getInformacionParaMapaGestoraSocial($rqst)
    {
        $codigo = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        if ($codigo === 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        $query = "
            SELECT 
                tbl_ciudades_accion_unificada.*,  
                SUM(tbl_gestora.poblacion) AS num_val
            FROM 
                " . $db->getTable('tbl_gestora') . " 
            RIGHT JOIN 
                " . $db->getTable('tbl_ciudades_accion_unificada') . "  
            ON 
                tbl_gestora.tbl_municipio_id = tbl_ciudades_accion_unificada.codigo_muncipio 
            WHERE 
                codigo_departamento = :codigo
            GROUP BY 
                tbl_ciudades_accion_unificada.codigo_muncipio";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':codigo', $codigo, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = !empty($result)
                ? ['output' => ['valid' => true, 'response' => $result]]
                : Util::error_no_result();
        } catch (PDOException $e) {
            $response = Util::error_general($e->getMessage());
        } finally {
            $db->closeConect();
        }

        return $response;
    }


    public static function getInformacionParaMapaPae($rqst)
    {
        $codigo = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;

        if ($codigo === 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        $query = " SELECT 
            tbl_pae.tbl_municipio_id,
            tbl_ciudades_accion_unificada.municipio,
            tbl_ciudades_accion_unificada.d,
            tbl_ciudades_accion_unificada.path,
            tbl_ciudades_accion_unificada.name,
            tbl_ciudades_accion_unificada.class,
            tbl_ciudades_accion_unificada.codigo_muncipio,
            tbl_ciudades_accion_unificada.codigo_departamento,
            COUNT(CASE WHEN tbl_pae.estado_sede = 'Antiguo_Activo' THEN 1 END) AS total
        FROM 
            " . $db->getTable('tbl_pae') . "
        RIGHT JOIN 
            " . $db->getTable('tbl_ciudades_accion_unificada') . "    
            ON tbl_pae.tbl_municipio_id = tbl_ciudades_accion_unificada.codigo_muncipio
        WHERE 
            tbl_ciudades_accion_unificada.codigo_departamento = :codigo
        GROUP BY 
            tbl_pae.tbl_municipio_id,
            tbl_ciudades_accion_unificada.municipio,
            tbl_ciudades_accion_unificada.d,
            tbl_ciudades_accion_unificada.path,
            tbl_ciudades_accion_unificada.name,
            tbl_ciudades_accion_unificada.class,
            tbl_ciudades_accion_unificada.codigo_muncipio,
            tbl_ciudades_accion_unificada.codigo_departamento
        ORDER BY 
            tbl_ciudades_accion_unificada.municipio";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':codigo', $codigo, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = !empty($result)
                ? ['output' => ['valid' => true, 'response' => $result]]
                : Util::error_no_result();
        } catch (PDOException $e) {
            $response = Util::error_general($e->getMessage());
        } finally {
            $db->closeConect();
        }

        return $response;
    }

    public static function getInformacionParaMapaGestoraSocialAspas($rqst)
    {

        $codigo = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT tbl_ciudades_accion_unificada.*,  Sum(tbl_gestora_aspas.poblacion) AS num_val
        FROM " . $db->getTable('tbl_gestora_aspas') . " RIGHT JOIN " . $db->getTable('tbl_ciudades_accion_unificada') . "  
        ON tbl_gestora_aspas.tbl_municipio_id = tbl_ciudades_accion_unificada.codigo_muncipio 
        WHERE codigo_departamento = $codigo
        GROUP BY tbl_ciudades_accion_unificada.codigo_muncipio";
        try {
            $stmt = $pdo->prepare($q);
            $stmt->execute();
            $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $arrjson = (!empty($arr))
                ? array('output' => array('valid' => true, 'response' => $arr))
                : Util::error_no_result();
        } catch (PDOException $e) {
            $arrjson = Util::error_general($e->getMessage());
        }

        $db->closeConect();
        return $arrjson;
    }

    public static function getInformacionParaMapaSecretarias($rqst)
    {

        $codigo = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT tbl_ciudades.*,  Sum(tbl_gestora.poblacion) AS num_val
        FROM " . $db->getTable('tbl_gestora') . " RIGHT JOIN " . $db->getTable('tbl_ciudades') . "  ON tbl_gestora.tbl_municipio_id = tbl_ciudades.codigo_muncipio 
        WHERE codigo_departamento = $codigo
        GROUP BY tbl_ciudades.codigo_muncipio";
        try {
            $stmt = $pdo->prepare($q);
            $stmt->execute();
            $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $arrjson = (!empty($arr))
                ? array('output' => array('valid' => true, 'response' => $arr))
                : Util::error_no_result();
        } catch (PDOException $e) {
            $arrjson = Util::error_general($e->getMessage());
        }

        $db->closeConect();
        return $arrjson;
    }

    /**
     * Metodo para listar la informacion de las cuidades para pintar el mapa, de accion unificada
     */
    public static function getInformacionParaMapaAccionUnificada($rqst)
    {
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $codigoDepartamento = isset($rqst['codigo']) ? intval($rqst['codigo']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $baseQuery = "
        SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " 
        WHERE 
            codigo_departamento = :codigoDepartamento";

        if ($codigoMunicipio > 0) {
            $baseQuery .= " AND codigo_muncipio = :codigoMunicipio";
        }

        $baseQuery .= " GROUP BY tbl_ciudades_accion_unificada.codigo_muncipio";

        try {
            $stmt = $pdo->prepare($baseQuery);

            // Asignar parámetros a la consulta
            $stmt->bindValue(':codigoDepartamento', $codigoDepartamento, PDO::PARAM_INT);

            // Para mostrar informacion de un municipio en especial
            if ($codigoMunicipio > 0) {
                $stmt->bindValue(':codigoMunicipio', $codigoMunicipio, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Construir respuesta
            $arrjson = !empty($result)
                ? ['output' => ['valid' => true, 'response' => $result]]
                : Util::error_no_result();
        } catch (PDOException $e) {
            $arrjson = Util::error_general($e->getMessage());
        }

        $db->closeConect();
        return $arrjson;
    }
    /**
     * Metodo para obtener la información del municipio con sus veredas.
     */
    public static function getInformacionParaMapaAccionUnificadaMunicipio($rqst)
    {
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $veredaId = isset($rqst['veredaId']) ? intval($rqst['veredaId']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $baseQuery = "
        SELECT * FROM " . $db->getTable('tbl_vereda') . " 
        WHERE 
            departamento_id = :codigoDepartamento AND municipio_id = :codigoMunicipio  ";

        if ($veredaId > 0) {
            $baseQuery .= " AND id = :veredaId";
        }

        $baseQuery .= " GROUP BY tbl_vereda.id";

        try {
            $stmt = $pdo->prepare($baseQuery);

            // Asignar parámetros a la consulta
            $stmt->bindValue(':codigoDepartamento', $codigoDepartamento, PDO::PARAM_INT);
            $stmt->bindValue(':codigoMunicipio', $codigoMunicipio, PDO::PARAM_INT);

            // Para mostrar informacion de una vereda en especial
            if ($veredaId > 0) {
                $stmt->bindValue(':veredaId', $veredaId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Construir respuesta
            $arrjson = !empty($result)
                ? ['output' => ['valid' => true, 'response' => $result]]
                : Util::error_no_result();
        } catch (PDOException $e) {
            $arrjson = Util::error_general($e->getMessage());
        }

        $db->closeConect();
        return $arrjson;
    }

    /**
     * Información de consolidado por municipio de pilar, factor, eje municipios
     * Aqui sacamos los datos dinamicos por pilar y muestra la informacion en los tab que se muestran en municpios 
     */
    public static function consultarConsolidadPilaresFactores($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $pilar = isset($rqst['pilar']) ? intval($rqst['pilar']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $q = "SELECT 
            tbl_ciudades_accion_unificada.*,
            tbl_vereda.codigo_vereda, 
            tbl_vereda.nombre_vereda,
            tbl_ingreso_informacion.valor  as total_cantidad, 
            tbl_ingreso_informacion.longitud, 
            tbl_ingreso_informacion.latitud, 
            tbl_factores.tec_pilar_id,
            tbl_factores.tipo AS factor, 
            tbl_factores.id AS factor_id, 
            tbl_ingreso_informacion.tbl_factor_id AS tbl_factor_id,
            tbl_factores.icono, 
            tbl_factores.tec_area_id AS area_id,
            tbl_factores.tipo_medicion,
            tbl_ingreso_informacion.dtcreate as fecha_ingreso
            FROM " . $db->getTable('tbl_ciudades_accion_unificada') . "
            INNER JOIN " . $db->getTable('tbl_vereda') . " 
                ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            INNER JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
            INNER JOIN " . $db->getTable('tbl_factores') . " 
                ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
            WHERE tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento  
                AND tbl_ciudades_accion_unificada.codigo_muncipio = $codigoMunicipio 
                AND tbl_factores.tec_pilar_id = $pilar
                ORDER BY  tbl_vereda.nombre_vereda";

            $consolidados = Util::sb_db_get($q, false);

            // Convertimos los resultados en un array de salida AND tbl_factores.tec_pilar_id = $pilar
            $resultado = [];
            foreach ($consolidados as $cantidad) {
                $resultado[] = $cantidad;
            }

            // Obtener IDs de pilares presentes en response
            $areasIds = array_filter(array_unique(array_column($consolidados, 'area_id')), function ($id) {
                return $id > 0; // Filtrar valores inválidos
            });

            // Obtener información de los pilares
            $tabs = [];
            if (!empty($areasIds)) {
                $qPilares = "SELECT *
                            FROM " . $db->getTable('tbl_area') . " 
                            WHERE id IN (" . implode(',', $areasIds) . ")"; // Solo incluir pilares presentes en response

                $tabs = Util::sb_db_get($qPilares, false);
            }

            $arrjson = [
                'output' => [
                    'valid' => true,
                    'response' => $resultado,
                    'tabs' => $tabs,
                ]
            ];
            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    public static function consultarConsolidadTodosLosPilaresFactores($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $q = "SELECT 
                tbl_ciudades_accion_unificada.*,
                tbl_vereda.codigo_vereda, 
                tbl_vereda.nombre_vereda,
                tbl_ingreso_informacion.valor AS total_cantidad, 
                tbl_ingreso_informacion.longitud, 
                tbl_ingreso_informacion.latitud, 
                tbl_factores.tec_pilar_id,
                tbl_factores.tipo AS factor, 
                tbl_factores.id AS factor_id, 
                tbl_ingreso_informacion.tbl_factor_id AS tbl_factor_id,
                tbl_factores.icono, 
                tbl_factores.tec_area_id AS area_id,
                tbl_factores.tipo_medicion,
                tbl_ingreso_informacion.dtcreate AS fecha_ingreso
            FROM " . $db->getTable('tbl_ciudades_accion_unificada') . "
            INNER JOIN " . $db->getTable('tbl_vereda') . " 
                ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            INNER JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
            INNER JOIN " . $db->getTable('tbl_factores') . " 
                ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
            WHERE 
                tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento  
                AND tbl_ciudades_accion_unificada.codigo_muncipio = $codigoMunicipio 
            ORDER BY tbl_vereda.nombre_vereda";


            $consolidados = Util::sb_db_get($q, false);

            $resultado = [];
            foreach ($consolidados as $cantidad) {
                $resultado[] = $cantidad;
            }

            // Obtener IDs de pilares (áreas) únicos presentes
            $areasIds = array_filter(array_unique(array_column($consolidados, 'area_id')), function ($id) {
                return $id > 0;
            });

            $pilares = [];
            if (!empty($areasIds)) {
                $qPilares = "SELECT id, nombre, descripcion, enable, icono 
                             FROM " . $db->getTable('tbl_area') . " 
                             WHERE id IN (" . implode(',', $areasIds) . ")";

                $pilares = Util::sb_db_get($qPilares, false);
            }
            return [
                'output' => [
                    'valid' => true,
                    'response' => $resultado,
                    'tabs' => $pilares,
                ]
            ];
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }


    /**
     * Información de consolidado por veredas de pilar, factor, eje
     */
    public static function consultarConsolidadPilaresFactoresByVeredaId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $veredaId = isset($rqst['veredaId']) ? intval($rqst['veredaId']) : 0;
        $pilar = isset($rqst['pilar']) ? intval($rqst['pilar']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0 || $veredaId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            // Información de consolidado de pilar y factor
            $q = "SELECT 
            tbl_factores.id as tbl_factor_id,
            tbl_factores.tipo AS factor, tbl_factores.icono, tbl_pilar.nombre as pilar, 
            tbl_vereda.municipio_id, tbl_vereda.nombre_vereda, tbl_vereda.codigo_vereda, 
            tbl_ingreso_informacion.longitud, tbl_ingreso_informacion.latitud, 
            tbl_ingreso_informacion.valor as total_cantidad, 
            tbl_factores.puntaje, tbl_pilar.id as pilar_id, 
            tbl_factores.tipo_medicion,
            tbl_area.id as area_id, tbl_area.nombre as area
            FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " 
            INNER JOIN " . $db->getTable('tbl_vereda') . " 
                ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            INNER JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id     
            INNER JOIN " . $db->getTable('tbl_factores') . " 
                ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id 
            INNER JOIN " . $db->getTable('tbl_pilar') . " 
                ON tbl_factores.tec_pilar_id = tbl_pilar.id 
            INNER JOIN " . $db->getTable('tbl_area') . " 
                ON tbl_factores.tec_area_id = tbl_area.id
            WHERE tbl_vereda.municipio_id = $codigoMunicipio  
            AND tbl_vereda.departamento_id = $codigoDepartamento 
            AND tbl_pilar.id = $pilar
            AND tbl_vereda.id = $veredaId
            AND tbl_ingreso_informacion.valor > 0
            ORDER BY tbl_factores.tec_area_id, tbl_pilar.id";
            $consolidados = Util::sb_db_get($q, false);

            // Convertir resultados a array de salida
            $resultado = [];
            foreach ($consolidados as $cantidad) {
                $resultado[] = $cantidad;
            }

            // Obtener IDs de pilares presentes en response
            $areasIds = array_filter(array_unique(array_column($consolidados, 'area_id')), function ($id) {
                return $id > 0; // Filtrar valores inválidos
            });

            // Obtener información de los pilares
            $pilares = [];
            if (!empty($areasIds)) {
                $qPilares = "SELECT id, nombre, descripcion, enable, icono 
                             FROM " . $db->getTable('tbl_area') . " 
                             WHERE id IN (" . implode(',', $areasIds) . ")"; // Solo incluir pilares presentes en response

                $pilares = Util::sb_db_get($qPilares, false);
            }

            $arrjson = [
                'output' => [
                    'valid' => true,
                    'response' => $resultado,
                    'pilares' => $pilares
                ]
            ];

            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    /**
     * Funcion para calcular el color por todos los pilares en departamentos
     */
    public static function calcularColorDelDepartamentoTodosLosPilares($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : Util::getDepartamentoPrincipal();

        if ($codigoDepartamento == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);
            $colorDefecto = Util::getColorNeutroMapa();

            $q = "SELECT 
                    tbl_factores.tec_pilar_id AS pilar_id,
                    COALESCE(SUM(tbl_factores.puntaje), 0) AS cantidad,
                    tbl_ciudades_accion_unificada.*
                FROM 
                    " . $db->getTable('tbl_ciudades_accion_unificada') . " AS tbl_ciudades_accion_unificada
                LEFT JOIN 
                    " . $db->getTable('tbl_ingreso_informacion') . " AS tbl_ingreso_informacion 
                    ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_ingreso_informacion.codigo_municipio
                LEFT JOIN 
                    " . $db->getTable('tbl_factores') . " AS tbl_factores 
                    ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id 
                WHERE
                    tbl_ciudades_accion_unificada.codigo_departamento =  $codigoDepartamento
                GROUP BY
                    tbl_ciudades_accion_unificada.id, 
                    tbl_ciudades_accion_unificada.path, 
                    tbl_ciudades_accion_unificada.name, 
                    tbl_ciudades_accion_unificada.class, 
                    tbl_ciudades_accion_unificada.d, 
                    tbl_ciudades_accion_unificada.codigo_departamento, 
                    tbl_factores.tec_pilar_id
                ORDER BY
                    cantidad ASC";
            $municipios = Util::sb_db_get($q, false);
            $resultado = [];

            foreach ($municipios as $municipio) {
                $cantidad = $municipio['cantidad'];
                $color = $colorDefecto;

                foreach ($puntajes as $puntaje) {
                    if ($cantidad >= $puntaje['rango_desde'] && $cantidad <= $puntaje['rango_hasta']) {
                        $color = $puntaje['color'];
                        break;
                    }
                }

                $municipio['color_calculado'] = $color;
                $resultado[] = $municipio;
            }

            return ['output' => ['valid' => true, 'response' => $resultado]];
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }




    /**
     * Información de consolidado por veredas de pilar
     */
    public static function calcularColorDelDepartamentoByPilarId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : Util::getDepartamentoPrincipal();
        $pilarId = isset($rqst['pilarId']) ? intval($rqst['pilarId']) : 0;
        $colores = isset($rqst['colores']) ? ($rqst['colores']) : 'no';

        if ($codigoDepartamento == 0 || $pilarId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {

            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);

            // Información de las cantidades actuales por Pilar Id


            $q = "SELECT 
                    tbl_factores.tec_pilar_id AS pilar_id,
                    COALESCE(SUM(tbl_factores.puntaje), 0) AS cantidad,
                    tbl_ciudades_accion_unificada.*
                FROM 
                    " . $db->getTable('tbl_ciudades_accion_unificada') . " AS tbl_ciudades_accion_unificada
                LEFT JOIN 
                    " . $db->getTable('tbl_ingreso_informacion') . " AS tbl_ingreso_informacion 
                    ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_ingreso_informacion.codigo_municipio
                LEFT JOIN 
                    " . $db->getTable('tbl_factores') . " AS tbl_factores 
                    ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id 
                    AND tbl_factores.tec_pilar_id = $pilarId
                WHERE
                    tbl_ciudades_accion_unificada.codigo_departamento =  $codigoDepartamento
                GROUP BY
                    tbl_ciudades_accion_unificada.id, 
                    tbl_ciudades_accion_unificada.path, 
                    tbl_ciudades_accion_unificada.name, 
                    tbl_ciudades_accion_unificada.class, 
                    tbl_ciudades_accion_unificada.d, 
                    tbl_ciudades_accion_unificada.codigo_departamento, 
                    tbl_factores.tec_pilar_id
                ORDER BY
                    cantidad ASC";
            $municipios = Util::sb_db_get($q, false);

            // Inicializar color neutro por defecto
            $colorDefecto = Util::getColorNeutroMapa();

            // Array para los resultados finales
            $resultado = [];
            // Inicializar un array para contar colores
            $colorCount = [];

            foreach ($municipios as $municipio) {
                // Calcular el color basado en la cantidad y los puntajes
                $color = $colorDefecto;
                foreach ($puntajes as $puntaje) {
                    if ($municipio['pilar_id'] > 0 && $municipio['cantidad'] >= $puntaje['rango_desde'] && $municipio['cantidad'] <= $puntaje['rango_hasta']) {
                        $color = $puntaje['color'];
                        break;
                    }
                }

                $municipio['color_calculado'] = $color;
                $resultado[] = $municipio;

                if ($color != Util::getColorNeutroMapa()) {
                    if (!isset($colorCount[$color])) {
                        $colorCount[$color] = 0;
                    }
                    $colorCount[$color]++;
                }
            }

            // Retornar la respuesta en formato JSON
            if ($colores == 'no') {
                $arrjson = ['output' => ['valid' => true, 'response' => $resultado]];
            } else {
                $arrjson = ['output' => ['valid' => true, 'cantidad_colores' => $colorCount]];
            }
            return $arrjson;
        } catch (Exception $e) {


            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    /**
     * Información de consolidado por veredas de pilar, factor, eje
     * Version Mejorada ya que se implementa diferente, ya que se obtiene el mayor de la vereda de cada municipio
     */
    public static function calcularColorDelDepartamentoByPilarIdMALAAA($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $pilarId = isset($rqst['pilarId']) ? intval($rqst['pilarId']) : 0;

        if ($codigoDepartamento == 0 || $pilarId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            // Obtener todos los municipios
            $municipios = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " WHERE codigo_departamento = $codigoDepartamento", false);

            // Consultamos la configuración de puntaje
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);

            // Información de Configuración
            $configuracionAplicacion = Util::getInformacionConfiguracion();
            if (!empty($configuracionAplicacion) && isset($configuracionAplicacion[0]['tipo_configuracion_colores'])) {
                $tipo_configuracion_colores = $configuracionAplicacion[0]['tipo_configuracion_colores'];
            } else {
                $tipo_configuracion_colores = 'Rango';
            }

            // Información de las cantidades actuales por Pilar Id y todos los campos de tbl_vereda
            $q = "SELECT 
                    tbl_ciudades_accion_unificada.*,
                    tbl_factores.tec_area_id AS tec_area_id,
                    COALESCE(tbl_pilar.id, 0) AS pilar_id,
                    tbl_factores.tipo AS factor,
                    tbl_factores.icono,
                    COALESCE(tbl_pilar.nombre, 'Sin Pilar') AS pilar,
                    SUM(tbl_ingreso_informacion.valor) AS cantidad
                FROM 
                    " . $db->getTable('tbl_vereda') . " tbl_vereda
                INNER JOIN 
                    " . $db->getTable('tbl_ciudades_accion_unificada') . " tbl_ciudades_accion_unificada 
                    ON tbl_vereda.municipio_id = tbl_ciudades_accion_unificada.codigo_muncipio
                INNER JOIN 
                    " . $db->getTable('tbl_ingreso_informacion') . " tbl_ingreso_informacion 
                    ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
                    AND tbl_ingreso_informacion.codigo_departamento = $codigoDepartamento 
                INNER JOIN 
                    " . $db->getTable('tbl_factores') . " tbl_factores 
                    ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
                INNER JOIN 
                    " . $db->getTable('tbl_pilar') . " tbl_pilar 
                    ON tbl_factores.tec_pilar_id = tbl_pilar.id
                WHERE  
                    tbl_vereda.departamento_id = $codigoDepartamento 
                    AND tbl_pilar.id = $pilarId 
                GROUP BY 
                    tbl_ciudades_accion_unificada.codigo_muncipio, 
                    tbl_factores.tec_area_id, 
                    tbl_pilar.id, 
                    tbl_pilar.nombre
                ORDER BY 
                    tbl_ciudades_accion_unificada.codigo_muncipio, 
                    tbl_factores.tec_area_id, 
                    tbl_pilar.id";

            $cantidades = Util::sb_db_get($q, false);

            // Inicializar color neutro por defecto
            $colorDefecto = Util::getColorNeutroMapa();

            // Verificar si $puntajes tiene datos o está vacío
            $puntajesVacios = isset($puntajes['output']['response']['code']) && $puntajes['output']['response']['code'] == 104;
            $puntajesValidos = !$puntajesVacios && is_array($puntajes);

            // Convertir cantidades en un array asociativo para acceso rápido por código de municipio
            $cantidadesPorMunicipio = [];
            foreach ($cantidades as $valueDataCantidad) {

                // Información de la cantidad maxima que tiene el municipio
                if ($tipo_configuracion_colores == 'Rango') {
                    $qCantMaximaMuncipio = "
                    SELECT 
                        COALESCE(ingreso.valor, 0) AS cantidad
                    FROM " . $db->getTable('tbl_ingreso_informacion') . " ingreso
                    LEFT JOIN " . $db->getTable('tbl_vereda') . " vereda 
                        ON vereda.id = ingreso.tbl_vereda_id  
                    WHERE ingreso.codigo_departamento = " . (int) $codigoDepartamento . "  
                        AND ingreso.codigo_municipio = " . (int) $valueDataCantidad['codigo_muncipio'] . "  
                        AND vereda.municipio_id = " . (int) $valueDataCantidad['codigo_muncipio'] . "  
                        AND vereda.departamento_id = " . (int) $codigoDepartamento . "  
                    ORDER BY ingreso.valor DESC  
                    LIMIT 1";
                }

                if ($tipo_configuracion_colores == 'Puntaje') {

                    $qCantMaximaMuncipio = "
                    SELECT 
                        SUM(total_puntaje_pilar) AS cantidad
                    FROM (
                        SELECT 
                            i.id, 
                            i.codigo_departamento, 
                            i.codigo_municipio, 
                            i.tbl_vereda_id, 
                            f.tbl_eje_id, 
                            f.tec_area_id, 
                            f.tec_pilar_id, 
                            SUM(f.puntaje) AS total_puntaje_pilar
                        FROM 
                            " . $db->getTable('tbl_ingreso_informacion') . " i
                        LEFT JOIN 
                            " . $db->getTable('tbl_factores') . " f 
                            ON i.tbl_factor_id = f.id
                        GROUP BY 
                            i.id, 
                            i.codigo_departamento, 
                            i.codigo_municipio, 
                            i.tbl_vereda_id, 
                            f.tbl_eje_id, 
                            f.tec_area_id, 
                            f.tec_pilar_id
                    ) AS subquery";
                }


                $cantidadMaxima = Util::sb_db_get($qCantMaximaMuncipio, false);

                $valueDataCantidad['cantidad'] = isset($cantidadMaxima[0]['cantidad']) ? (int) $cantidadMaxima[0]['cantidad'] : 0;

                $cantidadesPorMunicipio[$valueDataCantidad['codigo_muncipio']] = $valueDataCantidad;
            }

            // Array para los resultados finales
            $resultado = [];

            // Recorrer todos los municipios y combinar con las cantidades de la consulta $q
            foreach ($municipios as $municipio) {
                $codigoMunicipio = $municipio['codigo_muncipio'];

                // Si el municipio tiene datos en las cantidades, usar esos datos; si no, asignar valores predeterminados
                if (isset($cantidadesPorMunicipio[$codigoMunicipio])) {
                    $veredaData = $cantidadesPorMunicipio[$codigoMunicipio];
                } else {
                    // Valores predeterminados para municipios que no tienen datos en $q
                    $veredaData = $municipio;
                    $veredaData['tec_area_id'] = null;
                    $veredaData['pilar_id'] = 0;
                    $veredaData['factor'] = null;
                    $veredaData['icono'] = null;
                    $veredaData['pilar'] = 'Sin Pilar';
                    $veredaData['cantidad'] = 0;
                }

                // Calcular el color basado en la cantidad y los puntajes
                $color = $colorDefecto;
                if ($puntajesValidos) {
                    foreach ($puntajes as $puntaje) {
                        if ($veredaData['cantidad'] >= $puntaje['rango_desde'] && $veredaData['cantidad'] <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }

                $veredaData['color_calculado'] = $color;
                $resultado[] = $veredaData;
            }

            // Retornar la respuesta en formato JSON
            $arrjson = ['output' => ['valid' => true, 'response' => $resultado]];
            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }
    /**
     * Metodo para calcular los colores de todas las veredes que tiene una vereda, por TODO LOS PILARES
     * Verifica la información de todas sus veredas y calcula cada color de todas las veredas
     */
    public static function consultarConsolidadTodosLosPilaresFactoresByVeredaId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $veredaId = isset($rqst['veredaId']) ? intval($rqst['veredaId']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0 || $veredaId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $q = "SELECT 
                    tbl_factores.id as tbl_factor_id,
                    tbl_factores.tipo AS factor, 
                    tbl_factores.icono, 
                    tbl_pilar.nombre as pilar, 
                    tbl_vereda.municipio_id, 
                    tbl_vereda.nombre_vereda, 
                    tbl_vereda.codigo_vereda, 
                    tbl_ingreso_informacion.longitud, 
                    tbl_ingreso_informacion.latitud, 
                    tbl_ingreso_informacion.valor as total_cantidad, 
                    tbl_factores.puntaje, 
                    tbl_pilar.id as pilar_id, 
                    tbl_factores.tipo_medicion,
                    tbl_area.id as area_id, 
                    tbl_area.nombre as area
                FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " 
                INNER JOIN " . $db->getTable('tbl_vereda') . " 
                    ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
                INNER JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                    ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id     
                INNER JOIN " . $db->getTable('tbl_factores') . " 
                    ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id 
                INNER JOIN " . $db->getTable('tbl_pilar') . " 
                    ON tbl_factores.tec_pilar_id = tbl_pilar.id 
                INNER JOIN " . $db->getTable('tbl_area') . " 
                    ON tbl_factores.tec_area_id = tbl_area.id
                WHERE tbl_vereda.municipio_id = $codigoMunicipio  
                AND tbl_vereda.departamento_id = $codigoDepartamento 
                AND tbl_vereda.id = $veredaId
                AND tbl_ingreso_informacion.valor > 0
                ORDER BY tbl_factores.tec_area_id, tbl_pilar.id";

            $consolidados = Util::sb_db_get($q, false);


            $resultado = [];
            foreach ($consolidados as $cantidad) {
                $resultado[] = $cantidad;
            }

            $areasIds = array_filter(array_unique(array_column($consolidados, 'area_id')), fn($id) => $id > 0);

            $pilares = [];
            if (!empty($areasIds)) {
                $qPilares = "SELECT id, nombre, descripcion, enable, icono 
                                FROM " . $db->getTable('tbl_area') . " 
                                WHERE id IN (" . implode(',', $areasIds) . ")";
                $pilares = Util::sb_db_get($qPilares, false);
            }

            return [
                'output' => [
                    'valid' => true,
                    'response' => $resultado,
                    'pilares' => $pilares
                ]
            ];
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    /**
     * Metodo para calcular los colores de todas las veredes que tiene un Municipio, por TODO LOS PILARES
     * Verifica la información de todas sus veredas y calcula cada color de todas las veredas
     */
    public static function calcularColorPorMunicipioTodosPilares($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);

            $qIngresoInformacionCantidad = "
            SELECT 
                tbl_ciudades_accion_unificada.id, 
                tbl_ciudades_accion_unificada.codigo_departamento, 
                tbl_ciudades_accion_unificada.path, 
                tbl_ciudades_accion_unificada.name, 
                tbl_ciudades_accion_unificada.class, 
                tbl_ciudades_accion_unificada.d, 
                tbl_vereda.*, 
                SUM(COALESCE(tbl_factores.puntaje, 0)) AS cantidad
            FROM 
                " . $db->getTable('tbl_ciudades_accion_unificada') . " 
            LEFT JOIN 
                " . $db->getTable('tbl_vereda') . " ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            LEFT JOIN 
                " . $db->getTable('tbl_ingreso_informacion') . " ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
            LEFT JOIN 
                " . $db->getTable('tbl_factores') . " ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
            WHERE 
                tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento  
                AND tbl_ciudades_accion_unificada.codigo_muncipio = $codigoMunicipio 
            GROUP BY 
                tbl_vereda.id, 
                tbl_ciudades_accion_unificada.id, 
                tbl_ciudades_accion_unificada.codigo_departamento, 
                tbl_ciudades_accion_unificada.path, 
                tbl_ciudades_accion_unificada.name, 
                tbl_ciudades_accion_unificada.class, 
                tbl_ciudades_accion_unificada.d, 
                tbl_vereda.nombre_vereda
            ORDER BY 
                tbl_vereda.nombre_vereda, cantidad ASC
        ";

            $cantidades = Util::sb_db_get($qIngresoInformacionCantidad, false);

            $colorDefecto = Util::getColorNeutroMapa();
            $puntajesVacios = isset($puntajes['output']['response']['code']) && $puntajes['output']['response']['code'] == 104;
            $puntajesValidos = !$puntajesVacios && is_array($puntajes);

            $resultado = [];
            foreach ($cantidades as $cantidad) {
                $color = $colorDefecto;
                $valorCantidad = isset($cantidad['cantidad']) ? (int)$cantidad['cantidad'] : 0;

                if ($puntajesValidos) {
                    foreach ($puntajes as $puntaje) {
                        if ($valorCantidad >= $puntaje['rango_desde'] && $valorCantidad <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }

                $veredaData = $cantidad;
                $veredaData['color_calculado'] = $color;
                $resultado[] = $veredaData;
            }

            $arrjson = array('output' => array('valid' => true, 'response' => $resultado));
            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores (todos los pilares): " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }



    /**
     * Metodo para calcular los colores de todas las veredes que tiene un Municipio, según un pilar seleccionado
     * Verifica la información de todas sus veredas y calcula cada color de todas las veredas
     */
    public static function calcularColorPorMunicipioByPilarId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : 0;
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $pilarId = isset($rqst['pilar']) ? intval($rqst['pilar']) : 0;
        $veredaId = isset($rqst['veredaId']) ? intval($rqst['veredaId']) : 0;

        if ($codigoDepartamento == 0 || $codigoMunicipio == 0 || $pilarId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);

            $qIngresoInformacionCantidad = "SELECT 
                        tbl_ciudades_accion_unificada.id, 
                        tbl_ciudades_accion_unificada.codigo_departamento, 
                        tbl_ciudades_accion_unificada.path, 
                        tbl_ciudades_accion_unificada.name, 
                        tbl_ciudades_accion_unificada.class, 
                        tbl_ciudades_accion_unificada.d, 
                        tbl_vereda.*, 
                        tbl_factores.tec_pilar_id as pilar_id, 
                        SUM(tbl_factores.puntaje) AS cantidad
                    FROM 
                        " . $db->getTable('tbl_ciudades_accion_unificada') . " 
                    LEFT JOIN 
                        " . $db->getTable('tbl_vereda') . "  ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
                    LEFT JOIN 
                        " . $db->getTable('tbl_ingreso_informacion') . "   ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
                    LEFT JOIN 
                        " . $db->getTable('tbl_factores') . " ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
                            AND tbl_factores.tec_pilar_id = $pilarId
                    WHERE 
                        tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento  
                        AND tbl_ciudades_accion_unificada.codigo_muncipio = $codigoMunicipio 
                    GROUP BY 
                        tbl_vereda.id, 
                        tbl_ciudades_accion_unificada.id, 
                        tbl_ciudades_accion_unificada.codigo_departamento, 
                        tbl_ciudades_accion_unificada.path, 
                        tbl_ciudades_accion_unificada.name, 
                        tbl_ciudades_accion_unificada.class, 
                        tbl_ciudades_accion_unificada.d, 
                        tbl_vereda.nombre_vereda, 
                        tbl_factores.tec_pilar_id
                    ORDER BY 
                        tbl_vereda.nombre_vereda, cantidad ASC";

            $cantidades = Util::sb_db_get($qIngresoInformacionCantidad, false);

            // Cuando estemos buscando información de la vereda y no halla datos , se busca la información completa de la vereda 
            // 104 Codigo - Sin resultados
            if ($cantidades['output']['response']['code'] == 104 && $veredaId > 0) {
                $qVeredaInfo = "SELECT * FROM " . $db->getTable('tbl_vereda') . " WHERE departamento_id = $codigoDepartamento AND municipio_id = $codigoMunicipio   AND id = $veredaId";
                $cantidades = Util::sb_db_get($qVeredaInfo, false);
            }

            // Inicializar color neutro por defecto
            $colorDefecto = Util::getColorNeutroMapa();

            // Verificar si $puntajes tiene datos o está vacío
            $puntajesVacios = isset($puntajes['output']['response']['code']) && $puntajes['output']['response']['code'] == 104;
            $puntajesValidos = !$puntajesVacios && is_array($puntajes);

            $resultado = [];
            foreach ($cantidades as $cantidad) {

                // Asignar color neutro por defecto inicialmente
                $color = $colorDefecto;

                // Obtener la cantidad, asegurándose de que es un valor numérico
                $valorCantidad = isset($cantidad['cantidad']) ? (int)$cantidad['cantidad'] : 0;

                // Recorrer los rangos para encontrar el adecuado
                foreach ($puntajes as $puntaje) {
                    // Mostrar el rango actual en el que estamos iterando
                    $color = $colorDefecto;
                    // Verificar si la cantidad está dentro del rango
                    if ($puntajesValidos) {
                        if ($cantidad['pilar_id'] > 0 && $valorCantidad >= $puntaje['rango_desde'] && $valorCantidad <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }
                $veredaData = $cantidad;
                $veredaData['color_calculado'] = $color;
                $resultado[] = $veredaData;
            }
            $arrjson = array('output' => array('valid' => true, 'response' => $resultado));
            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }


    /**
     * Metodo para calcular el color de la vereda TODOS LOS PILARES
     */
    public static function calcularColorPorVeredaByTodosLosPilares($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? (int) $rqst['codigo_departamento'] : 0;
        $pilarId = isset($rqst['pilar']) ? (int) $rqst['pilar'] : 0;
        $veredaId = isset($rqst['veredaId']) ? (int) $rqst['veredaId'] : 0;

        if ($codigoDepartamento === 0 || $veredaId === 0 || $pilarId === 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false) ?: [];

            $configuracionAplicacion = Util::getInformacionConfiguracion();
            $tipo_configuracion_colores = $configuracionAplicacion[0]['tipo_configuracion_colores'] ?? 'Rango';

            $qIngresoInformacionCantidad = "SELECT tbl_vereda.*, 
                tbl_ingreso_informacion.valor, 
                tbl_ingreso_informacion.longitud, 
                tbl_ingreso_informacion.latitud, 
                tbl_factores.tec_pilar_id AS pilar_id,
                COALESCE(SUM(tbl_factores.puntaje), 0) AS cantidad
            FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " 
            LEFT JOIN " . $db->getTable('tbl_vereda') . " 
                ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            LEFT JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
            LEFT JOIN " . $db->getTable('tbl_factores') . " 
                ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
            WHERE tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento
                AND tbl_vereda.id = $veredaId 
                AND tbl_factores.tec_pilar_id IS NOT NULL

            GROUP BY tbl_vereda.id, tbl_ciudades_accion_unificada.codigo_departamento";

            $cantidades = Util::sb_db_get($qIngresoInformacionCantidad, false);

            if ($cantidades['output']['response']['code']  == 104) {
                $qVeredaInfo = "SELECT * FROM " . $db->getTable('tbl_vereda') . " WHERE departamento_id = $codigoDepartamento AND id = $veredaId";
                $cantidades = Util::sb_db_get($qVeredaInfo, false);
            }

            $colorDefecto = Util::getColorNeutroMapa();
            $puntajesValidos = !empty($puntajes) && is_array($puntajes);

            $resultado = array_map(function ($cantidad) use ($puntajes, $puntajesValidos, $colorDefecto) {
                $valorCantidad = (int) ($cantidad['cantidad'] ?? 0);
                $color = $colorDefecto;

                if ($puntajesValidos && $cantidad['pilar_id'] > 0) {
                    foreach ($puntajes as $puntaje) {
                        if ($valorCantidad >= $puntaje['rango_desde'] && $valorCantidad <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }
                $cantidad['cantidad_mostrar'] = $valorCantidad;
                $cantidad['color_calculado'] = $color;
                return $cantidad;
            }, $cantidades);

            return ['output' => ['valid' => true, 'response' => $resultado]];
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    /**
     * Metodo para calcular el color de la vereda por medio su Id y pilar Id 
     */
    public static function calcularColorPorVeredaByPilarId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? (int) $rqst['codigo_departamento'] : 0;
        $pilarId = isset($rqst['pilar']) ? (int) $rqst['pilar'] : 0;
        $veredaId = isset($rqst['veredaId']) ? (int) $rqst['veredaId'] : 0;

        if ($codigoDepartamento === 0 || $veredaId === 0 || $pilarId === 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false) ?: [];

            $configuracionAplicacion = Util::getInformacionConfiguracion();
            $tipo_configuracion_colores = $configuracionAplicacion[0]['tipo_configuracion_colores'] ?? 'Rango';

            $qIngresoInformacionCantidad = "SELECT tbl_vereda.*, 
                tbl_ingreso_informacion.valor, 
                tbl_ingreso_informacion.longitud, 
                tbl_ingreso_informacion.latitud, 
                tbl_factores.tec_pilar_id AS pilar_id,
                COALESCE(SUM(tbl_factores.puntaje), 0) AS cantidad
            FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " 
            LEFT JOIN " . $db->getTable('tbl_vereda') . " 
                ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
            LEFT JOIN " . $db->getTable('tbl_ingreso_informacion') . " 
                ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
            LEFT JOIN " . $db->getTable('tbl_factores') . " 
                ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
            WHERE tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento
                AND tbl_vereda.id = $veredaId 
                AND tbl_factores.tec_pilar_id = $pilarId
            GROUP BY tbl_vereda.id, tbl_ciudades_accion_unificada.codigo_departamento";

            $cantidades = Util::sb_db_get($qIngresoInformacionCantidad, false);

            if ($cantidades['output']['response']['code']  == 104) {
                $qVeredaInfo = "SELECT * FROM " . $db->getTable('tbl_vereda') . " WHERE departamento_id = $codigoDepartamento AND id = $veredaId";
                $cantidades = Util::sb_db_get($qVeredaInfo, false);
            }

            $colorDefecto = Util::getColorNeutroMapa();
            $puntajesValidos = !empty($puntajes) && is_array($puntajes);

            $resultado = array_map(function ($cantidad) use ($puntajes, $puntajesValidos, $colorDefecto) {
                $valorCantidad = (int) ($cantidad['cantidad'] ?? 0);
                $color = $colorDefecto;

                if ($puntajesValidos && $cantidad['pilar_id'] > 0) {
                    foreach ($puntajes as $puntaje) {
                        if ($valorCantidad >= $puntaje['rango_desde'] && $valorCantidad <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }

                $cantidad['cantidad_mostrar'] = $valorCantidad;
                $cantidad['color_calculado'] = $color;
                return $cantidad;
            }, $cantidades);

            return ['output' => ['valid' => true, 'response' => $resultado]];
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    /**
     * Metodo para calcular los colores de todas las veredes en general del departamento Principal
     * Obtiene la cantidad de veredas por color , solamente el dato , no retorna informacion general de las veredas como mapa y patht points
     */
    public static function calcularColorPorVeredasGeneralByPilarId($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : Util::getDepartamentoPrincipal();
        $pilarId = isset($rqst['pilarId']) ? intval($rqst['pilarId']) : 0;

        if ($codigoDepartamento == 0 || $pilarId == 0) {
            return Util::error_missing_data();
        }

        $db = new DbConection();

        try {
            $puntajes = Util::sb_db_get("SELECT * FROM " . $db->getTable('tbl_puntajes'), false);

            $qIngresoInformacionCantidad = "SELECT 
                    tbl_ciudades_accion_unificada.id, 
                    tbl_ciudades_accion_unificada.codigo_departamento, 
                    tbl_ciudades_accion_unificada.path, 
                    tbl_ciudades_accion_unificada.name, 
                    tbl_ciudades_accion_unificada.class, 
                    tbl_ciudades_accion_unificada.d, 
                    tbl_vereda.*, 
                    tbl_factores.tec_pilar_id as pilar_id, 
                    SUM(tbl_factores.puntaje) AS cantidad
                FROM 
                    " . $db->getTable('tbl_ciudades_accion_unificada') . " 
                LEFT JOIN 
                    " . $db->getTable('tbl_vereda') . "  ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_vereda.municipio_id 
                LEFT JOIN 
                    " . $db->getTable('tbl_ingreso_informacion') . "   ON tbl_vereda.id = tbl_ingreso_informacion.tbl_vereda_id 
                LEFT JOIN 
                    " . $db->getTable('tbl_factores') . " ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
                        AND tbl_factores.tec_pilar_id = $pilarId
                WHERE 
                    tbl_ciudades_accion_unificada.codigo_departamento = $codigoDepartamento
                GROUP BY 
                    tbl_vereda.id, 
                    tbl_ciudades_accion_unificada.id, 
                    tbl_ciudades_accion_unificada.codigo_departamento, 
                    tbl_ciudades_accion_unificada.path, 
                    tbl_ciudades_accion_unificada.name, 
                    tbl_ciudades_accion_unificada.class, 
                    tbl_ciudades_accion_unificada.d, 
                    tbl_vereda.nombre_vereda, 
                    tbl_factores.tec_pilar_id
                ORDER BY 
                    tbl_vereda.nombre_vereda, cantidad ASC";

            $cantidades = Util::sb_db_get($qIngresoInformacionCantidad, false);

            // Inicializar color neutro por defecto
            $colorDefecto = Util::getColorNeutroMapa();

            // Verificar si $puntajes tiene datos o está vacío
            $puntajesVacios = isset($puntajes['output']['response']['code']) && $puntajes['output']['response']['code'] == 104;
            $puntajesValidos = !$puntajesVacios && is_array($puntajes);

            $resultado = [];
            foreach ($cantidades as $cantidad) {

                // Asignar color neutro por defecto inicialmente
                $color = $colorDefecto;

                // Obtener la cantidad, asegurándose de que es un valor numérico
                $valorCantidad = isset($cantidad['cantidad']) ? (int)$cantidad['cantidad'] : 0;

                // Recorrer los rangos para encontrar el adecuado
                foreach ($puntajes as $puntaje) {
                    // Mostrar el rango actual en el que estamos iterando
                    $color = $colorDefecto;
                    // Verificar si la cantidad está dentro del rango
                    if ($puntajesValidos) {
                        if ($cantidad['pilar_id'] > 0 && $valorCantidad >= $puntaje['rango_desde'] && $valorCantidad <= $puntaje['rango_hasta']) {
                            $color = $puntaje['color'];
                            break;
                        }
                    }
                }
                $veredaData = $cantidad;
                $veredaData['color_calculado'] = $color;
                $resultado[] = $veredaData;

                if ($color != Util::getColorNeutroMapa()) {
                    if (!isset($colorCount[$color])) {
                        $colorCount[$color] = 0;
                    }
                    $colorCount[$color]++;
                }
            }
            $arrjson = array('output' => array('valid' => true, 'cantidad_colores' => $colorCount));
            return $arrjson;
        } catch (Exception $e) {
            return Util::error_general("Error generando los colores: " . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    public static function getInformacionFactoresGeneralesPorMunicipio($rqst)
    {
        $codigoDepartamento = isset($rqst['codigo_departamento']) ? intval($rqst['codigo_departamento']) : Util::getDepartamentoPrincipal();
        $codigoMunicipio = isset($rqst['codigo_municipio']) ? intval($rqst['codigo_municipio']) : 0;
        $ejeId = isset($rqst['ejeId']) ? intval($rqst['ejeId']) : 0;

        if ($codigoDepartamento === 0 || $codigoMunicipio === 0) {
            return Util::error_missing_data();
        }

        try {
            $db = new DbConection();
            $pdo = $db->openConect();

            $query = "
                SELECT 
                    tbl_ciudades_accion_unificada.codigo_departamento, 
                    tbl_ciudades_accion_unificada.municipio, 
                    tbl_ciudades_accion_unificada.codigo_muncipio, 
                    tbl_vereda.nombre_vereda, 
                    tbl_vereda.codigo_vereda, 
                    tbl_ingreso_informacion.valor, 
                    tbl_factores.tipo, 
                    tbl_factores.tipo_medicion, 
                    tbl_pilar.nombre,
                    tbl_ejes.nombre AS eje
                FROM " . $db->getTable('tbl_ciudades_accion_unificada') . "  
                INNER JOIN " . $db->getTable('tbl_ingreso_informacion') . "    
                    ON tbl_ciudades_accion_unificada.codigo_muncipio = tbl_ingreso_informacion.codigo_municipio
                INNER JOIN " . $db->getTable('tbl_vereda') . "    
                    ON tbl_ingreso_informacion.tbl_vereda_id = tbl_vereda.id
                INNER JOIN " . $db->getTable('tbl_factores') . "     
                    ON tbl_ingreso_informacion.tbl_factor_id = tbl_factores.id
                INNER JOIN " . $db->getTable('tbl_pilar') . "  
                    ON tbl_factores.tec_pilar_id = tbl_pilar.id
                INNER JOIN " . $db->getTable('tbl_ejes') . "     
                    ON tbl_factores.tbl_eje_id = tbl_ejes.id
                WHERE 
                    tbl_ciudades_accion_unificada.codigo_departamento = :codigoDepartamento
                    AND tbl_ciudades_accion_unificada.codigo_muncipio = :codigoMunicipio
                ORDER BY
                    tbl_ciudades_accion_unificada.municipio, tbl_ejes.nombre ASC";

            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':codigoDepartamento', $codigoDepartamento, PDO::PARAM_INT);
            $stmt->bindValue(':codigoMunicipio', $codigoMunicipio, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db->closeConect();

            return !empty($result)  ? ['output' => ['valid' => true, 'response' => $result]] : Util::error_no_result();
        } catch (Exception $e) {
            return Util::error_general($e->getMessage());
        }
    }
}
