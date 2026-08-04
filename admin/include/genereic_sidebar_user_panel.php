<div class="user-panel mt-3 pb-3 mb-3">
  <div class="image">
    <img src="<?php echo SessionData::getAvatar(); ?>"  class="img-circle elevation-2" width="40px">
  </div>
  <div class="info">
    <a href="#" class="d-block"><?php echo $_SESSION['session_user']['nombre']; ?></a>
  </div>
  <br>
  <div class="info">
    <a href="logout.php" title="salir"><i class="fa fa-power-off"></i> Cerrar Sesion</a>
  </div>
  <div class="float-right btnmenu" style="display: none;">
    <i class="nav-icon fas fa-bars"></i>
  </div>
</div>
