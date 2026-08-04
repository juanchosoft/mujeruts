<?php
include './admin/include/head.php';
require './admin/include/generic_classes.php';

// Permisos (usa tu lógica existente)
$permissions = [
    'view' => SessionData::getPermission(70),
    'create' => SessionData::getPermission(71),
    'edit' => SessionData::getPermission(72),
];

// -----------------------------
// Datos simulados (reemplaza con tu consulta/Informes)
// -----------------------------
$alertas = [
    [
        'id' => 1,
        'created_at' => '2025-10-14 14:23:00',
        'tipo' => 'ALARMA',
        'direccion' => 'Calle 10 # 5-51, Centro, Bogotá',
        'nivel' => 'high',
        'lat' => 4.5981,
        'lng' => -74.0758,
        'foto' => 'assets/img/2 Banner.jpg',
        'nombre' => 'Usuario A'
    ],
    [
        'id' => 2,
        'created_at' => '2025-10-14 14:10:00',
        'tipo' => 'SOS',
        'direccion' => 'Carrera 80 # 39-105, Medellín',
        'nivel' => 'medium',
        'lat' => 6.2442,
        'lng' => -75.5812,
        'foto' => 'assets/img/boton.png',
        'nombre' => 'Usuario B'
    ],
    [
        'id' => 3,
        'created_at' => '2025-10-14 13:55:00',
        'tipo' => 'INCIDENTE',
        'direccion' => 'Avenida Pedro de Heredia, Centro, Cartagena',
        'nivel' => 'low',
        'lat' => 10.3910,
        'lng' => -75.4794,
        'foto' => 'assets/img/zonarural.png',
        'nombre' => 'Usuario C'
    ],
];

$kpis = [
    'alertas_activas' => count($alertas),
    'atendidas' => 1,
    'en_proceso' => 1,
    'sin_respuesta' => 1,
    'agentes_campo' => 4,
    'zonas_criticas' => 2
];

$contacto = [
    'nombre' => 'Cuadrante 13',
    'telefono' => '13-233940',
    'nota' => 'DESPACHADA'
];

// Función auxiliar de escape
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<body class="">
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg">
    <div class="loader-track">
      <div class="loader-fill"></div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->
  
  <!-- [ Header ] start -->
  <?php include './admin/include/header.php'; ?>
  <!-- [ Header ] end -->

  <div class="pcoded-main-container">
    <div class="pcoded-content">
      <!-- [ breadcrumb ] start -->
      <div class="page-header">
        <div class="page-block">
          <div class="row align-items-center">
            <div class="col-md-12">
              <div class="page-header-title">
                <h5 class="m-b-10">Dashboard - Botón de Pánico</h5>
              </div>
              <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#!">Botón de Pánico</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="row">
        <div class="col-sm-12">
          <div class="card">
            <div class="card-header">
              <h5>Panel de Control - Alertas en Tiempo Real</h5>
              <div class="card-header-right">
                <div class="btn-group card-option">
                  <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="feather icon-more-horizontal"></i>
                  </button>
                  <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                    <li class="dropdown-item full-card"><a href="#!"><span><i
                              class="feather icon-maximize"></i> maximize</span><span
                          style="display:none"><i class="feather icon-minimize"></i>
                          Restore</span></a></li>
                    <li class="dropdown-item minimize-card"><a href="#!"><span><i
                              class="feather icon-minus"></i> collapse</span><span
                          style="display:none"><i class="feather icon-plus"></i>
                          expand</span></a></li>
                    <li class="dropdown-item reload-card"><a href="#!"><i
                          class="feather icon-refresh-cw"></i> reload</a></li>
                    <li class="dropdown-item close-card"><a href="#!"><i
                          class="feather icon-trash"></i> remove</a></li>
                  </ul>
                </div>
              </div>
            </div>
            
            <div class="card-body">
              <!-- KPIs Superiores -->
              <div class="row">
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>Alertas Activas</small>
                    <h3><?php echo e($kpis['alertas_activas']); ?></h3>
                  </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>Atendidas</small>
                    <h3><?php echo e($kpis['atendidas']); ?></h3>
                  </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>En Proceso</small>
                    <h3><?php echo e($kpis['en_proceso']); ?></h3>
                  </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>Sin respuesta</small>
                    <h3><?php echo e($kpis['sin_respuesta']); ?></h3>
                  </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>Agentes en campo</small>
                    <h3><?php echo e($kpis['agentes_campo']); ?></h3>
                  </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                  <div class="kpi dashboard-card">
                    <small>Zonas críticas</small>
                    <h3><?php echo e($kpis['zonas_criticas']); ?></h3>
                  </div>
                </div>
              </div>

              <div class="row g-3 mt-2">
                <!-- llamadas / alertas -->
                <div class="col-12 col-xl-3">
                  <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 class="mb-0">Llamadas en Vivo</h5>
                      <span class="badge rounded-pill text-bg-danger">EN VIVO</span>
                    </div>

                    <div class="alerts-list">
                      <?php foreach($alertas as $a): 
                        $nivel = $a['nivel'];
                        $nivelClass = $nivel === 'high' ? 'lvl-high' : ($nivel === 'medium' ? 'lvl-medium' : 'lvl-low');
                      ?>
                        <div class="alerts-item d-flex gap-3 align-items-start mb-3 p-2 border rounded">
                          <div>
                            <div class="alert-badge <?php echo $nivelClass; ?>">
                              <i class="fa fa-exclamation"></i>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                              <div style="font-weight:700;"><?php echo e($a['tipo']); ?></div>
                              <small class="text-muted"><?php echo e(date('d/m/Y H:i', strtotime($a['created_at']))); ?></small>
                            </div>
                            <div class="small text-muted mb-1"><?php echo e($a['nombre']); ?> — <?php echo e($a['direccion']); ?></div>
                            <div class="d-flex gap-2">
                              <button class="btn btn-sm btn-outline-primary btn-ver-mapa" data-lat="<?php echo e($a['lat']); ?>" data-lng="<?php echo e($a['lng']); ?>">Ver en mapa</button>
                              <button class="btn btn-sm btn-outline-secondary btn-ver-foto" data-foto="<?php echo e($a['foto']); ?>">Ver foto</button>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>

                    <div class="mt-3">
                      <div class="dashboard-card p-3 border">
                        <div class="d-flex align-items-center gap-3">
                          <div class="me-2"><i class="fa fa-user-shield fa-2x text-primary"></i></div>
                          <div>
                            <div style="font-weight:800;"><?php echo e($contacto['nombre']); ?></div>
                            <div><?php echo e($contacto['telefono']); ?></div>
                            <div class="small text-muted"><?php echo e($contacto['nota']); ?></div>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>

                <!--mapa grande y banner alerta -->
                <div class="col-12 col-xl-6">
                  <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div class="big-alert">ALERTA</div>
                      <div class="text-end">
                        <div class="small text-muted">Ubicación</div>
                        <div style="font-weight:800; font-size:22px;">13</div>
                      </div>
                    </div>

                    <div class="map-container mb-3">
                      <div id="map" style="height: 400px; border-radius: 8px;"></div>
                    </div>

                    <div class="dashboard-card p-3 border">
                      <form id="seguimientoForm">
                        <div class="row g-2">
                          <div class="col-12 col-md-4">
                            <select class="form-select" id="estado" name="estado">
                              <option value="proceso" selected>En proceso</option>
                              <option value="resuelto">Resuelto</option>
                              <option value="escalado">Escalado</option>
                              <option value="cerrado">Cerrado</option>
                            </select>
                          </div>
                          <div class="col-12 col-md-6">
                            <input type="text" id="accion" class="form-control" placeholder="Acción tomada">
                          </div>
                          <div class="col-12 col-md-2 d-grid">
                            <button class="btn btn-success" style="border-radius: 10px; margin-left: -13px;" type="submit">Guardar</button>
                          </div>
                        </div>
                      </form>
                    </div>

                  </div>
                </div>

                <!--widgets -->
                <div class="col-12 col-xl-3 right-widgets">
                  <div class="dashboard-card widget p-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <div style="font-weight:700;">Vista de Mapa</div>
                        <div class="small text-muted">37.7749° N, 122.4194 W</div>
                      </div>
                      <div style="font-size:28px; font-weight:800; color:#2e7d32;">627</div>
                    </div>
                  </div>

                  <div class="dashboard-card widget p-0 photo-card border mb-3">
                    <?php if (!empty($alertas[0]['foto'])): ?>
                      <img src="<?php echo e($alertas[0]['foto']); ?>" alt="foto incidente" style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                      <div style="height:180px; display:flex; align-items:center; justify-content:center; color:#6b7a86; border-radius: 8px;" class="bg-light">
                        <i class="fa fa-camera fa-2x mb-2"></i>
                        <br>
                        Sin foto
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="dashboard-card widget p-3 d-flex justify-content-between align-items-center border mb-3">
                    <div>
                      <div style="font-weight:700;">Contrato Policial</div>
                      <div class="small text-muted">Responsable: <?php echo e($contacto['nombre']); ?></div>
                    </div>
                    <div>
                      <i class="fa fa-user-shield fa-2x text-primary"></i>
                    </div>
                  </div>

                  <div class="dashboard-card widget p-3 text-center border">
                    <div class="small text-muted">Hora actual</div>
                    <div id="clock" class="clock mt-2">--:--:--</div>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>

  <!-- Modal para mostrar imagen -->
  <div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Foto del Incidente</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-0 text-center">
          <img id="modalFoto" src="" alt="foto" style="max-width: 100%; height: auto;">
        </div>
      </div>
    </div>
  </div>

  <?php include 'admin/include/gerenic_script.php'; ?>
  
  <!-- Scripts específicos -->
  <script src="assets/js/vendor-all.min.js"></script>
  <script src="assets/js/plugins/bootstrap.min.js"></script>
  <script src="assets/js/pcoded.min.js"></script>

  <!-- Dependencias del mapa -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    // -----------------------
    // Reloj (tiempo real)
    // -----------------------
    function updateClock() {
      const el = document.getElementById('clock');
      const now = new Date();
      const h = String(now.getHours()).padStart(2,'0');
      const m = String(now.getMinutes()).padStart(2,'0');
      const s = String(now.getSeconds()).padStart(2,'0');
      el.textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // -----------------------
    // Inicializar mapa Leaflet
    const map = L.map('map', {scrollWheelZoom: true}).setView([4.5709, -74.2973], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Marcadores desde PHP (sanitizados)
    const alertas = <?php echo json_encode(array_map(function($a){
        return [
          'id' => $a['id'],
          'lat' => (float)$a['lat'],
          'lng' => (float)$a['lng'],
          'tipo' => $a['tipo'],
          'direccion' => $a['direccion'],
          'nivel' => $a['nivel'],
          'foto' => $a['foto'],
          'created_at' => $a['created_at'],
          'nombre' => $a['nombre']
        ];
    }, $alertas)); ?>;

    // Iconos sencillos: circle markers con color por nivel
    alertas.forEach(a => {
      const color = a.nivel === 'high' ? '#e53935' : (a.nivel === 'medium' ? '#fb8c00' : '#2e7d32');
      const marker = L.circleMarker([a.lat, a.lng], {
        radius: 12,
        color: color,
        fillColor: color,
        fillOpacity: 0.9,
        weight: 1
      }).addTo(map);

      const popupHtml = `
        <div style="font-weight:800;">${a.tipo}</div>
        <div class="small">${a.nombre} — ${a.direccion}</div>
        <div class="small text-muted">${a.created_at}</div>
        ${a.foto ? `<div style="margin-top:8px;"><img src="${a.foto}" style="width:120px;height:80px;object-fit:cover;border-radius:4px;"></div>` : ''}
      `;
      marker.bindPopup(popupHtml);
    });

    // Botones "Ver en mapa" centran el mapa en la alerta
    document.querySelectorAll('.btn-ver-mapa').forEach(b => {
      b.addEventListener('click', e => {
        const lat = parseFloat(b.dataset.lat);
        const lng = parseFloat(b.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
          map.setView([lat, lng], 16, {animate:true});
        }
      });
    });

    // Botones "Ver foto" abren modal
    document.querySelectorAll('.btn-ver-foto').forEach(b => {
      b.addEventListener('click', e => {
        const foto = b.dataset.foto;
        if (foto) {
          document.getElementById('modalFoto').src = foto;
          const myModal = new bootstrap.Modal(document.getElementById('fotoModal'));
          myModal.show();
        } else {
          alert('No hay foto disponible para esta alerta.');
        }
      });
    });

    // Envío de seguimiento (demo — reemplaza por fetch/ajax)
    document.getElementById('seguimientoForm').addEventListener('submit', function(e){
      e.preventDefault();
      // Aquí debes reemplazar con una petición fetch a tu endpoint para guardar seguimiento
      const estado = document.getElementById('estado').value;
      const accion = document.getElementById('accion').value;
      alert(`Seguimiento guardado (simulado): ${estado} - ${accion}`);
      // opcional: limpiar inputs
      document.getElementById('accion').value = '';
    });
  </script>
</body>
</html>