<section class="content" style="<?php echo isset($_GET["route_map"]) ? 'padding: 0rem !important' : ''; ?>">
                    <div class="container-fluid">
                        <!-- Filtro oculto -->
                        <div class="row d-none">
                            <div class="col-sm-4">
                                <input id="filtro" name="filtro" value="municipio">
                                <div class="form-group">
                                    <label class="bmd-label-floating">Departamento<b class="errLbl">*</b></label>
                                    <select class="form-control select2" id="tbl_departamento_id" name="tbl_departamento_id">
                                        <option value=""></option>
                                        <option value="<?php echo $santander["id"]; ?>"
                                            <?php echo (!is_null($code) && $_GET["depto_id"] == Util::getIdentificadorDepartamentoPrincipal()) ? "selected" : ""; ?>>
                                            Santander
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalGeocalizacion">
                            Geolocalización
                        </button>

                        <!-- Contenedor del mapa centrado -->
                        <div class="row justify-content-center">
                            <div class="col-md-12 text-center">
                                <div id="contenidoTransformado" class="contenido-transformado">
                                    <div class="cuerpoMapa">
                                        <div class="santander munis">
                                            <?php require_once "admin/mapa-santander/mapa_pae.php"; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
<style>
    .contenido-transformado {
        overflow-x: auto;
        overflow-y: auto;
        max-width: 100%;
    }

    .contenido-transformado svg,
    .contenido-transformado img {
        max-width: 100%;
        height: auto;
        display: block;
    }

    .cuerpoMapa {
        max-width: 100%;
        overflow-x: auto;
    }

    .santander {
        width: 100%;
        overflow: hidden;
    }

    .card {
        overflow: hidden;
    }
</style>