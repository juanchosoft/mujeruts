<?php
include './admin/include/head.php';

require './admin/include/generic_classes.php';
include './admin/classes/Usuario.php';
include './admin/classes/Secretarias.php';
include './admin/classes/Departamento.php';

// Permisos
$view = SessionData::getPermission(1);
$create = SessionData::getPermission(2);
$edit = SessionData::getPermission(3);
$permits = SessionData::getPermission(4);
/* if (!$view) {
    require 'permiso_denegado.php';
} */
$userType = SessionData::getUserType();
$isAdmin = ($userType === Util::Administrador() || $userType === Util::SuperAdministrador());
/* if (!$isAdmin) {
    require 'permiso_denegado.php';
} */


//Información de Secretarias
$arrSecretarias = Secretarias::getAll(null);
$isvalidProv = $arrSecretarias['output']['valid'];
$arrSecretarias = $arrSecretarias['output']['response'];
$option = '<option value="Seleccione" selected>Seleccione</option>';
foreach ($arrSecretarias as $val) {
    $option .= "<option value='" . $val['id'] . "'>" . $val['secretaria'] . "</option>";
}

// Información de Departamentos
$arrDep = Departamento::getAll(null);
$isvalid = $arrDep['output']['valid'];
$arrDep = $arrDep['output']['response'];
$optionDep = Util::getDepartamentoPrincipal();
foreach ($arrDep as $val) {
    $optionDep .= "<option " . ($val["codigo_departamento"] == Util::getDepartamentoPrincipal() ? "selected" : "") . " value='" . $val['codigo_departamento'] . "'>" . $val['codigo_departamento'] . " - " . $val['departamento'] . "</option>";
}
?>
<style>
    #preview {
        max-width: 50%;
        height: auto;
        display: none;
        margin-top: 10px;
    }
</style>

<body class="">

    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ navigation menu ] start -->
    <?php
    include './admin/include/navbar.php';
    ?>
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    <?php
    include './admin/include/header.php';
    ?>

    <!-- [ Main Content ] start -->
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="m-b-10">Usuarios </h5>
                                <?php include './admin/include/btn_back.php'; ?>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a>
                                </li>
                                <li class="breadcrumb-item"><a href="#!">Configuración General / Usuarios</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home" type="button"
                        role="tab" aria-controls="home" aria-selected="true">Ingresar usuario</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile" type="button"
                        role="tab" aria-controls="profile" aria-selected="false" onclick="USUARIO.cargaData()">Listado de usuarios</button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                    <br>
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-center w-100">Formulario usuario</h5>
                            <div class="card-header-right ml-auto">
                                <div class="btn-group card-option">
                                    <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="feather icon-more-horizontal"></i>
                                    </button>
                                    <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                                        <li class="dropdown-item full-card">
                                            <a href="#!">
                                                <span><i class="feather icon-maximize"></i> Maximizar</span>
                                                <span style="display:none"><i class="feather icon-minimize"></i> Restaurar</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item minimize-card">
                                            <a href="#!">
                                                <span><i class="feather icon-minus"></i> Colapsar</span>
                                                <span style="display:none"><i class="feather icon-plus"></i> Expandir</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item reload-card">
                                            <a href="#!"><i class="feather icon-refresh-cw"></i> Recargar</a>
                                        </li>
                                        <li class="dropdown-item close-card">
                                            <a href="#!"><i class="feather icon-trash"></i> Eliminar</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card-body m-4">
                            <form id="formusuarios" role="form" autocomplete="false" enctype="multipart/form-data">
                                <input type="hidden" name="op" id="op" />
                                <input type="hidden" name="id" id="id" />
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="nombre">Nombres<span
                                                class="text-danger mb-1">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ingrese nombres" value="" required>

                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="apellido">Apellidos<span
                                                class="text-danger mb-1">*</span></label>
                                        <input type="text" class="form-control" id="apellido" name="apellido"
                                            placeholder="Ingrese apellidos" value="" required>

                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="tipo">Tipo de Usuario<span
                                                class="text-danger mb-1">*</span></label>
                                        <select class="form-control" id="tipo" name="tipo">
                                            <option value="">Seleccione</option>
                                            <option value="Secretario_Despacho">Secretario Despacho</option>
                                            <option value="Auxiliar">Auxiliar Secretario Despacho</option>
                                            <option value="Alcalde">Alcalde</option>
                                            <option value="Auxiliar_Alcalde">Auxiliar Alcalde</option>
                                            <option value="Administrador">Administrador</option>
                                        </select>

                                    </div>
                                </div>

                                <div class="form-row">

                                    <div class="form-group col-md-4">
                                        <label for="tbl_secretarias_id">Secretaria o Dependencia<span
                                                class="text-danger mb-1">*</span></label>
                                        <select class="form-control" id="tbl_secretarias_id" name="tbl_secretarias_id">
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4" style="display: none;">
                                        <label for="tbl_departamento_id">Departamento<span
                                                class="text-danger mb-1">*</span></label>
                                        <select onchange="DEPARTAMENTO.getMunicipios();" class="form-control"
                                            id="tbl_departamento_id" name="tbl_departamento_id">
                                            <?php echo $optionDep; ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="tbl_municipio_id">Alcaldía<span
                                                class="text-danger mb-1">*</span></label>
                                        <select class="form-control" id="tbl_municipio_id" name="tbl_municipio_id">
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="nickname">Usuario</label>
                                        <span class="text-danger mb-1">*</span>
                                        <small class="text-muted">(ej: xxx@correo.com)</small>
                                        <input type="email" class="form-control" id="nickname" name="nickname"
                                            placeholder="Ingrese un formato de usuario valido" value="" required>

                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="email">Correo electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Ingrese su correo electrónico" value="" required>

                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="validationCustom05">Habilitado<span
                                                class="text-danger mb-1">*</span></label>
                                        <select class="form-control" id="habilitado" name="habilitado">
                                            <option value="">Seleccione</option>
                                            <option value="si">Sí</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>

                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="hashpass">Contraseña <span class="text-danger mb-1">*</span></label>
                                        <div class="input-group" style="margin: 0;">
                                            <input type="password" class="form-control" id="hashpass" name="hashpass" placeholder="Ingrese una contraseña" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text" onclick="togglePassword('hashpass', this)">
                                                    <i class="feather icon-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="hashpass1">Repita la Contraseña <span class="text-danger mb-1">*</span></label>
                                        <div class="input-group" style="margin: 0;">
                                            <input type="password" class="form-control" id="hashpass1" name="hashpass1" placeholder="Ingrese nuevamente la contraseña" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text" onclick="togglePassword('hashpass1', this)">
                                                    <i class="feather icon-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group col-md-4">
                                        <label for="img">Subir imagen</label>
                                        <input type="file" class="form-control-file" id="img" name="img" accept="image/*">
                                        <div id="previewImage" class="mt-2">
                                        </div>
                                    </div>

                                </div>

                                <div class="form-row pt-3">
                                    <div class="col text-center">
                                        <button type="button" onclick="UTIL.clearForm('formusuarios');" class="btn btn-danger mr-2">
                                            Cancelar
                                        </button>
                                        <button type="button" id="createUser" onclick="USUARIO.validateData();" class="btn btn-primary">
                                            Guardar
                                        </button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <br>
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-center w-100">Listado usuario</h5>
                            <div class="card-header-right ml-auto">
                                <div class="btn-group card-option">
                                    <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="feather icon-more-horizontal"></i>
                                    </button>
                                    <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                                        <li class="dropdown-item full-card">
                                            <a href="#!">
                                                <span><i class="feather icon-maximize"></i> Maximizar</span>
                                                <span style="display:none"><i class="feather icon-minimize"></i> Restaurar</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item minimize-card">
                                            <a href="#!">
                                                <span><i class="feather icon-minus"></i> Colapsar</span>
                                                <span style="display:none"><i class="feather icon-plus"></i> Expandir</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item reload-card">
                                            <a href="#!"><i class="feather icon-refresh-cw"></i> Recargar</a>
                                        </li>
                                        <li class="dropdown-item close-card">
                                            <a href="#!"><i class="feather icon-trash"></i> Eliminar</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body table-border-style">
                            <div class="navbar-form buscador-2">
                                <div class="input-group input-primary">
                                    <input type="text" id="customSearch" class="form-control" placeholder="Buscar usuario">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="feather icon-edit"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de datos -->
                            <div class="table-responsive tabla-informacion tabla-scroll">
                                <table class="table table-hover mb-0" id="dynamictable">
                                    <thead>
                                        <tr class="border-1">
                                            <th>Editar</th>
                                            <th>Nombre</th>
                                            <th>Apellido</th>
                                            <th>Tipo</th>
                                            <th>Secretaria o entidad</th>
                                            <th>Usuario</th>
                                            <th>Habilitado</th>
                                            <th>Foto</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="modalImagen" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body">
                    <img id="imagenGrande" src="" class="img-fluid rounded" alt="Foto">
                </div>
            </div>
        </div>
    </div>

    <!--     modal editar -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="exampleModalLongTitle">Editar Usuario</h5>
                    <button onclick="UTIL.clearForm('formpermission');" type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>

                <div class="modal-body p-4">
                    <form id="editFormUser" role="form" autocomplete="false" class="w-100">
                        <input type="hidden" name="editId" id="editId" />

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="editNombre">Nombres <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNombre" name="editNombre" placeholder="Ingrese nombres" required>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editApellido">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editApellido" name="editApellido" placeholder="Ingrese apellidos" required>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editTipo">Tipo de Usuario <span class="text-danger">*</span></label>
                                <select class="form-control" id="editTipo" name="editTipo">
                                    <option value="">Seleccione</option>
                                    <option value="Secretario_Despacho">Secretario Despacho</option>
                                    <option value="Auxiliar">Auxiliar Secretario Despacho</option>
                                    <option value="Alcalde">Alcalde</option>
                                    <option value="Auxiliar_Alcalde">Auxiliar Alcalde</option>
                                    <option value="Administrador">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="editTbl_secretarias_id">Secretaría o Dependencia <span class="text-danger">*</span></label>
                                <select class="form-control" id="editTbl_secretarias_id" name="editTbl_secretarias_id">

                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editEmail">Correo electrónico</label>
                                <input type="email" class="form-control" id="editEmail" name="editEmail" placeholder="Correo electrónico" required>
                            </div>

                            <div class="form-group col-md-4" style="display:none">
                                <label for="editTbl_departamento_id">Departamento <span class="text-danger">*</span></label>
                                <select onchange="DEPARTAMENTO.getMunicipios();" class="form-control" id="editTbl_departamento_id" name="editTbl_departamento_id">

                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editTbl_municipio_id_copy">Alcaldía <span class="text-danger">*</span></label>
                                <select class="form-control" id="editTbl_municipio_id_copy" name="editTbl_municipio_id_copy"></select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="editNickname">Usuario <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="editNickname" name="editNickname" placeholder="Formato válido de usuario" required>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editHashpass">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group w-100" style="margin: 0;">
                                    <input type="password" class="form-control" id="editHashpass" name="editHashpass" placeholder="Ingrese una contraseña" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePassword('editHashpass', this)">
                                            <i class="feather icon-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editHashpass1">Repita la Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group w-100" style="margin: 0;">
                                    <input type="password" class="form-control" id="editHashpass1" name="editHashpass1" placeholder="Repita la contraseña" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePassword('editHashpass1', this)">
                                            <i class="feather icon-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editHabilitado">Habilitado <span class="text-danger">*</span></label>
                                <select class="form-control" id="editHabilitado" name="editHabilitado">
                                    <option value="">Seleccione</option>
                                    <option value="si">Sí</option>
                                    <option value="no">No</option>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="editImg">Imagen</label>
                                <input type="file" class="form-control-file" id="editImg" name="editImg" accept="image/*">
                                <div id="editPreviewImage" class="mt-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" id="btnGuardarEditar" class="btn btn-primary" onclick="USUARIO.editUserSave();">
                        Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Permisos -->
    <div class="modal fade" id="myModalPermisos" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="    background-color: #388e3c;">
                    <h5 class="modal-title" id="exampleModalLongTitle">Permisos de Usuario</h5>
                    <button onclick="UTIL.clearForm('formpermission');" type="button" class="close"
                        data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body" style="    padding: 40px;">
                    <form id="formpermission" autocomplete="off">
                        <table class="table table-striped table-bordered table-DecoPopup"
                            style="width:100%; margin:0px">
                            <thead class=" text-primary">
                                <th>
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input class="form-check-input" onChange="PERMISOS.checkAll();"
                                                name="check_permisos" id="check_permisos" type="checkbox"
                                                value="">
                                            <span class="form-check-sign">
                                                <span class="check"></span>
                                            </span>
                                        </label>
                                    </div>
                                </th>
                                <th>Permisos</th>
                            </thead>
                            <tbody id="permission">
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" onclick="PERMISOS.savepermission();"
                        class="btn btn-success">Asignar</button>

                </div>
            </div>
        </div>
    </div>

    <!-- Warning Section Ends -->
    <!-- Required Js -->
    <?php include 'admin/include/gerenic_script.php'; ?>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>

    <script type="text/javascript" src="admin/js/departamento.js"></script>
    <script type="text/javascript" src="./admin/js/lib/data-md5.js"></script>

    <script type="text/javascript" src="admin/js/permisos.js"></script>
    <script type="text/javascript" src="./admin/js/datatables/jquery.dataTables.min.js"></script>
    <link href="./admin/js/datatables/jquery.dataTables.min.css" rel="stylesheet" />
    <script type="text/javascript" src="admin/js/usuario.js"></script>
    <script>
        /* setTimeout(function() {
            DEPARTAMENTO.getMunicipios();
        }, 1000); */
    </script>
    <script>
        function togglePassword(fieldId, el) {
            const input = document.getElementById(fieldId);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('icon-eye');
                icon.classList.add('icon-eye-off');
            } else {
                input.type = 'password';
                icon.classList.remove('icon-eye-off');
                icon.classList.add('icon-eye');
            }
        }
    </script>


</body>

</html>