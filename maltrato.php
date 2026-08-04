<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once './admin/classes/DbConection.php';

$pageTitle       = 'Aliada UTS | Protección Universitaria';
$pageDescription = 'Plataforma Aliada UTS para proteger, orientar y acompañar a las mujeres de la comunidad universitaria.';
$currentYear     = date('Y');

$nombreUsuario = '';
$usuarioId = 0;
$contactoEmergencia = '';
$contactoEmergenciaTel = '';
$nombreContacto = '';

if (!empty($_SESSION['nombre_completo'])) {
    $nombreUsuario = trim((string)$_SESSION['nombre_completo']);
} elseif (!empty($_SESSION['nombre'])) {
    $nombreUsuario = trim((string)$_SESSION['nombre']);
} elseif (!empty($_SESSION['session_user']['nombre'])) {
    $nombre = trim((string)($_SESSION['session_user']['nombre'] ?? ''));
    $apellido = trim((string)($_SESSION['session_user']['ape'] ?? ''));
    $nombreUsuario = trim($nombre . ' ' . $apellido);
} elseif (!empty($_SESSION['nickname'])) {
    $nombreUsuario = trim((string)$_SESSION['nickname']);
}

if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuaria';
}

if (!empty($_SESSION['id'])) {
    $usuarioId = (int)$_SESSION['id'];
} elseif (!empty($_SESSION['usuario_id'])) {
    $usuarioId = (int)$_SESSION['usuario_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $usuarioId = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['session_user']['id'])) {
    $usuarioId = (int)$_SESSION['session_user']['id'];
}

try {
    if ($usuarioId > 0) {
        $db = new DbConection();
        $pdo = $db->openConect();

        $sql = "SELECT contacto_emergencia, nombre_contacto
                FROM tbl_usuarios
                WHERE id = :id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $contactoEmergencia = trim((string)($row['contacto_emergencia'] ?? ''));
            $nombreContacto = trim((string)($row['nombre_contacto'] ?? ''));
            $contactoEmergenciaTel = preg_replace('/[^0-9+]/', '', $contactoEmergencia);
        }
    }
} catch (Throwable $e) {
    $contactoEmergencia = '';
    $contactoEmergenciaTel = '';
    $nombreContacto = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/maltratonew.css">
</head>

<body>

<div class="permission-modal" id="permissionModal">
    <div class="permission-card">
        <div class="permission-head">
            <div class="permission-head-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <h2>Activa los permisos de protección</h2>
            <p>
                Para guardar evidencia y activar apoyo inmediato, Aliada UTS necesita acceso a ubicación,
                cámara y micrófono.
            </p>
        </div>

        <div class="permission-list">
            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div>
                    <strong>Ubicación</strong>
                    <span>Permite guardar dónde se activó la alerta.</span>
                </div>
            </div>

            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <strong>Cámara</strong>
                    <span>Permite tomar fotos o grabar video como evidencia.</span>
                </div>
            </div>

            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-microphone-lines"></i>
                </div>
                <div>
                    <strong>Micrófono</strong>
                    <span>Permite grabar audio o video con sonido.</span>
                </div>
            </div>
        </div>

        <div class="permission-actions">
            <button type="button" class="btn-permission-main" id="btnActivarPermisos">
                <i class="fa-solid fa-lock-open"></i>
                Activar permisos ahora
            </button>

            <button type="button" class="btn-permission-secondary" id="btnOmitirPermisos">
                Continuar y pedirlos después
            </button>
        </div>
    </div>
</div>

<main class="mobile-app">
    <div class="mobile-shell">

        <header class="top-card">
            <div class="top-card-inner">
                <div class="brand-row">
                    <div class="brand-logo">
                        <img src="assets/img/utslog.png" alt="Aliada UTS">
                    </div>

                    <div class="brand-copy">
                        <p class="brand-eyebrow">Universitaria Tecnológica de Santander</p>
                        <p class="brand-eyebrow">Aliada UTS</p>
                        <h1 class="brand-title">
                            Protección <span>Universitaria</span>
                        </h1>
                        <p class="brand-subtitle">
                            Botones rápidos para guardar evidencia y activar apoyo inmediato.
                        </p>
                    </div>
                </div>

                <div class="top-actions">
                    <button type="button" class="top-btn white" onclick="window.history.back();">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                    </button>

                    <a href="./mis_evidencias.php" class="top-btn">
                        <i class="fa-solid fa-folder-open"></i>
                        Evidencias
                    </a>

                    <button type="button" class="top-btn" onclick="window.location.href='logoutusuaria.php';">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <section class="hero-card">
            <div class="hero-badge">
                <i class="fa-solid fa-shield-heart"></i>
                Atención inmediata
            </div>

            <h2 class="hero-title">
                <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>, Aliada UTS está contigo.
            </h2>

            <p class="hero-text">
                Usa estas opciones para guardar evidencia o comunicarte rápidamente con apoyo de emergencia.
            </p>

            <div class="permission-status">
                <div class="permission-pill" id="pillLocation">
                    <i class="fa-solid fa-location-dot"></i>
                    Ubicación
                </div>
                <div class="permission-pill" id="pillCamera">
                    <i class="fa-solid fa-camera"></i>
                    Cámara
                </div>
                <div class="permission-pill" id="pillMicrophone">
                    <i class="fa-solid fa-microphone-lines"></i>
                    Micrófono
                </div>
            </div>
        </section>

        <section class="actions-card">
            <div class="actions-head">
                <h2 class="section-title">Acciones rápidas</h2>
                <p class="section-text">Toca una opción. La evidencia se guardará automáticamente con ubicación.</p>
            </div>

            <div class="action-grid">

                <button type="button" class="action-btn microfono" id="btnMicrofono">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-microphone-lines"></i></div>
                        <div>
                            <div class="btn-title">Grabar audio</div>
                            <div class="btn-text">Toca para iniciar y vuelve a tocar para detener.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn foto" id="btnFoto">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-camera"></i></div>
                        <div>
                            <div class="btn-title">Tomar foto</div>
                            <div class="btn-text">Captura evidencia rápida y segura.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn camara" id="btnVideo">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-video"></i></div>
                        <div>
                            <div class="btn-title">Grabar video</div>
                            <div class="btn-text">Registra lo ocurrido con audio.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn foto" id="btnPermisos">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <div>
                            <div class="btn-title">Revisar permisos</div>
                            <div class="btn-text">Activa cámara, micrófono y ubicación.</div>
                        </div>
                    </div>
                </button>

                <a href="tel:123" class="action-btn policia action-btn-priority" id="btnPolicia">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>
                            <div class="btn-title">Llamar Policía</div>
                            <div class="btn-text">Guarda alerta y llama al 123.</div>
                        </div>
                    </div>
                </a>

                <?php if ($contactoEmergenciaTel !== ''): ?>
                    <a href="tel:<?php echo htmlspecialchars($contactoEmergenciaTel, ENT_QUOTES, 'UTF-8'); ?>" class="action-btn emergencia action-btn-priority" id="btnEmergencia">
                        <div class="action-btn-inner">
                            <div class="btn-icon"><i class="fa-solid fa-phone-volume"></i></div>
                            <div>
                                <div class="btn-title">Contacto de emergencia</div>
                                <div class="btn-text">
                                    <?php echo htmlspecialchars($nombreContacto !== '' ? $nombreContacto : 'Llamar contacto', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <button type="button" class="action-btn emergencia action-btn-priority" id="btnEmergenciaSinNumero">
                        <div class="action-btn-inner">
                            <div class="btn-icon"><i class="fa-solid fa-phone-volume"></i></div>
                            <div>
                                <div class="btn-title">Contacto de emergencia</div>
                                <div class="btn-text">No tienes un contacto registrado.</div>
                            </div>
                        </div>
                    </button>
                <?php endif; ?>

            </div>

            <div class="status-msg" id="statusMsg"></div>
        </section>

        <section class="preview-card" id="previewCard" style="display:none;">
            <div class="preview-head">
                <h2 class="section-title">Evidencia capturada</h2>
                <p class="section-text">Aquí puedes revisar lo que acabas de registrar.</p>
            </div>

            <div class="preview-grid">
                <div class="preview-box" id="boxAudio" style="display:none;">
                    <h3><i class="fa-solid fa-microphone-lines"></i> Audio</h3>
                    <div class="preview-area" id="audioPreviewText">Aún no se ha grabado audio.</div>
                    <audio id="audioPlayer" controls style="display:none;"></audio>
                </div>

                <div class="preview-box" id="boxCamara" style="display:none;">
                    <h3><i class="fa-solid fa-camera"></i> Vista previa</h3>
                    <div class="preview-area" id="videoPreviewText">Aún no se ha activado la cámara.</div>
                    <video id="videoPreview" autoplay playsinline muted style="display:none;"></video>
                </div>

                <div class="preview-box" id="boxFoto" style="display:none;">
                    <h3><i class="fa-solid fa-image"></i> Foto</h3>
                    <div class="preview-area" id="fotoPreviewText">Aún no se ha tomado ninguna foto.</div>
                    <img id="fotoPreview" alt="Foto capturada" style="display:none;">
                </div>

                <div class="preview-box" id="boxVideoGrabado" style="display:none;">
                    <h3><i class="fa-solid fa-video"></i> Video grabado</h3>
                    <div class="preview-area" id="videoGrabadoText">Aún no se ha grabado ningún video.</div>
                    <video id="videoGrabadoPlayer" controls playsinline style="display:none;"></video>
                </div>
            </div>
        </section>

        <footer class="footer-mobile">
            <p>
                © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
                Acompañamiento, orientación y protección universitaria.
            </p>
        </footer>

    </div>
</main>

<script>
'use strict';

const USER_ID = <?php echo (int)$usuarioId; ?>;
const USER_NAME = <?php echo json_encode($nombreUsuario, JSON_UNESCAPED_UNICODE); ?>;

const PERMISSIONS_KEY = 'aliada_permissions_requested_v1';
const PERMISSIONS_OK_KEY = 'aliada_permissions_ok_v1';

let currentLat = null;
let currentLng = null;
let currentAccuracy = null;

let audioStream = null;
let audioRecorder = null;
let audioChunks = [];
let isRecordingAudio = false;

let cameraStream = null;
let videoRecorder = null;
let videoChunks = [];
let isRecordingVideo = false;

const statusMsg = document.getElementById('statusMsg');
const previewCard = document.getElementById('previewCard');

const btnMicrofono = document.getElementById('btnMicrofono');
const btnFoto = document.getElementById('btnFoto');
const btnVideo = document.getElementById('btnVideo');
const btnPolicia = document.getElementById('btnPolicia');
const btnEmergencia = document.getElementById('btnEmergencia');
const btnEmergenciaSinNumero = document.getElementById('btnEmergenciaSinNumero');
const btnPermisos = document.getElementById('btnPermisos');

const permissionModal = document.getElementById('permissionModal');
const btnActivarPermisos = document.getElementById('btnActivarPermisos');
const btnOmitirPermisos = document.getElementById('btnOmitirPermisos');

const pillLocation = document.getElementById('pillLocation');
const pillCamera = document.getElementById('pillCamera');
const pillMicrophone = document.getElementById('pillMicrophone');

const boxAudio = document.getElementById('boxAudio');
const boxCamara = document.getElementById('boxCamara');
const boxFoto = document.getElementById('boxFoto');
const boxVideoGrabado = document.getElementById('boxVideoGrabado');

const audioPlayer = document.getElementById('audioPlayer');
const videoPreview = document.getElementById('videoPreview');
const fotoPreview = document.getElementById('fotoPreview');
const videoGrabadoPlayer = document.getElementById('videoGrabadoPlayer');

const audioPreviewText = document.getElementById('audioPreviewText');
const videoPreviewText = document.getElementById('videoPreviewText');
const fotoPreviewText = document.getElementById('fotoPreviewText');
const videoGrabadoText = document.getElementById('videoGrabadoText');

function showStatus(message, type = 'info') {
    if (!statusMsg) return;
    statusMsg.textContent = message || '';
    statusMsg.className = 'status-msg ' + type;
}

function showPreviewCard() {
    if (previewCard) previewCard.style.display = 'block';
}

function mostrarSoloPreview(box) {
    showPreviewCard();

    [boxAudio, boxCamara, boxFoto, boxVideoGrabado].forEach(item => {
        if (item) item.style.display = 'none';
    });

    if (box) box.style.display = 'block';

    setTimeout(() => {
        previewCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 150);
}

function abrirModalPermisos() {
    if (permissionModal) {
        permissionModal.classList.add('show');
    }
}

function cerrarModalPermisos() {
    if (permissionModal) {
        permissionModal.classList.remove('show');
    }
}

function marcarPill(pill, ok) {
    if (!pill) return;

    if (ok) {
        pill.classList.add('ok');
    } else {
        pill.classList.remove('ok');
    }
}

function guardarEstadoPermisos({ location = false, camera = false, microphone = false } = {}) {
    const estado = {
        location,
        camera,
        microphone,
        updated_at: new Date().toISOString()
    };

    localStorage.setItem(PERMISSIONS_OK_KEY, JSON.stringify(estado));
    localStorage.setItem(PERMISSIONS_KEY, '1');

    actualizarPillsDesdeEstado(estado);
}

function leerEstadoPermisosGuardado() {
    try {
        return JSON.parse(localStorage.getItem(PERMISSIONS_OK_KEY) || '{}');
    } catch (e) {
        return {};
    }
}

function actualizarPillsDesdeEstado(estado = null) {
    const saved = estado || leerEstadoPermisosGuardado();

    marcarPill(pillLocation, !!saved.location);
    marcarPill(pillCamera, !!saved.camera);
    marcarPill(pillMicrophone, !!saved.microphone);
}

async function consultarPermissionAPI(name) {
    if (!navigator.permissions || !navigator.permissions.query) return null;

    try {
        const result = await navigator.permissions.query({ name });
        return result.state;
    } catch (e) {
        return null;
    }
}

async function refrescarEstadoPermisosReal() {
    const saved = leerEstadoPermisosGuardado();

    const geoState = await consultarPermissionAPI('geolocation');
    const cameraState = await consultarPermissionAPI('camera');
    const microphoneState = await consultarPermissionAPI('microphone');

    const estado = {
        location: geoState ? geoState === 'granted' : !!saved.location,
        camera: cameraState ? cameraState === 'granted' : !!saved.camera,
        microphone: microphoneState ? microphoneState === 'granted' : !!saved.microphone,
        updated_at: new Date().toISOString()
    };

    localStorage.setItem(PERMISSIONS_OK_KEY, JSON.stringify(estado));
    actualizarPillsDesdeEstado(estado);
}

function getLocationSilent() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve(null);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                currentLat = position.coords.latitude;
                currentLng = position.coords.longitude;
                currentAccuracy = position.coords.accuracy;

                const saved = leerEstadoPermisosGuardado();
                guardarEstadoPermisos({
                    ...saved,
                    location: true
                });

                resolve({
                    lat: currentLat,
                    lng: currentLng,
                    accuracy: currentAccuracy
                });
            },
            () => resolve(null),
            {
                enableHighAccuracy: true,
                timeout: 7000,
                maximumAge: 30000
            }
        );
    });
}

async function solicitarPermisosIniciales() {
    let locationOk = false;
    let cameraOk = false;
    let microphoneOk = false;

    showStatus('Solicitando permisos de protección...', 'info');

    try {
        const location = await getLocationSilent();
        locationOk = !!location;
    } catch (e) {
        locationOk = false;
    }

    try {
        const mediaStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });

        cameraOk = mediaStream.getVideoTracks().length > 0;
        microphoneOk = mediaStream.getAudioTracks().length > 0;

        mediaStream.getTracks().forEach(track => track.stop());
    } catch (e) {
        cameraOk = false;
        microphoneOk = false;
    }

    guardarEstadoPermisos({
        location: locationOk,
        camera: cameraOk,
        microphone: microphoneOk
    });

    cerrarModalPermisos();

    if (locationOk && cameraOk && microphoneOk) {
        showStatus('Permisos activados correctamente. Ya puedes guardar evidencia de forma segura.', 'success');
    } else {
        showStatus('Algunos permisos no fueron activados. Puedes revisarlos desde el botón “Revisar permisos”.', 'warning');
    }

    await refrescarEstadoPermisosReal();
}

async function guardarAlerta(tipo, archivo = null, extra = {}) {
    try {
        await getLocationSilent();

        const formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('usuario_id', USER_ID);
        formData.append('nombre_usuario', USER_NAME);
        formData.append('latitud', currentLat ?? '');
        formData.append('longitud', currentLng ?? '');
        formData.append('precision_gps', currentAccuracy ?? '');
        formData.append('descripcion', extra.descripcion ?? '');

        if (archivo) {
            formData.append('archivo', archivo, extra.nombreArchivo || 'evidencia.webm');
        }

       const response = await fetch('admin/ajax/guardar_alerta.php', {
    method: 'POST',
    body: formData
});

        const text = await response.text();
        let data = null;

        try {
            data = JSON.parse(text);
        } catch (e) {
            data = { valid: response.ok, message: text };
        }

        if (!response.ok || data.valid === false) {
            throw new Error(data.message || 'No fue posible guardar la alerta.');
        }

        showStatus(data.message || 'Información guardada correctamente.', 'success');
        return true;
    } catch (error) {
        showStatus(error.message || 'No fue posible guardar la información.', 'error');
        return false;
    }
}

function resetAudioButton() {
    if (!btnMicrofono) return;

    btnMicrofono.classList.remove('recording');
    btnMicrofono.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-microphone-lines"></i></div>
            <div>
                <div class="btn-title">Grabar audio</div>
                <div class="btn-text">Toca para iniciar y vuelve a tocar para detener.</div>
            </div>
        </div>
    `;
}

function setAudioStopButton() {
    if (!btnMicrofono) return;

    btnMicrofono.classList.add('recording');
    btnMicrofono.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-stop"></i></div>
            <div>
                <div class="btn-title">Detener audio</div>
                <div class="btn-text">Finalizar grabación y guardar.</div>
            </div>
        </div>
    `;
}

function resetVideoButton() {
    if (!btnVideo) return;

    btnVideo.classList.remove('recording');
    btnVideo.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-video"></i></div>
            <div>
                <div class="btn-title">Grabar video</div>
                <div class="btn-text">Registra lo ocurrido con audio.</div>
            </div>
        </div>
    `;
}

function setVideoStopButton() {
    if (!btnVideo) return;

    btnVideo.classList.add('recording');
    btnVideo.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-stop"></i></div>
            <div>
                <div class="btn-title">Detener video</div>
                <div class="btn-text">Finalizar grabación y guardar.</div>
            </div>
        </div>
    `;
}

async function iniciarAudio() {
    try {
        audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            microphone: true
        });

        audioChunks = [];
        audioRecorder = new MediaRecorder(audioStream);

        audioRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        audioRecorder.onstop = async () => {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const url = URL.createObjectURL(blob);

            mostrarSoloPreview(boxAudio);

            audioPreviewText.style.display = 'none';
            audioPlayer.style.display = 'block';
            audioPlayer.src = url;

            if (audioStream) {
                audioStream.getTracks().forEach(track => track.stop());
                audioStream = null;
            }

            await guardarAlerta('audio', blob, {
                nombreArchivo: 'audio_evidencia.webm',
                descripcion: 'Audio guardado desde Aliada UTS.'
            });
        };

        audioRecorder.start();
        isRecordingAudio = true;

        setAudioStopButton();
        mostrarSoloPreview(boxAudio);

        audioPreviewText.style.display = 'flex';
        audioPreviewText.textContent = 'Grabando audio... toca nuevamente para detener.';
        audioPlayer.style.display = 'none';

        showStatus('Grabando audio de evidencia...', 'info');
    } catch (error) {
        showStatus('No fue posible activar el micrófono. Revisa los permisos del navegador.', 'error');
        resetAudioButton();
    }
}

function detenerAudio() {
    if (audioRecorder && audioRecorder.state !== 'inactive') {
        audioRecorder.stop();
    }

    isRecordingAudio = false;
    resetAudioButton();
    showStatus('Procesando audio...', 'info');
}

async function tomarFoto() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false
        });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            camera: true
        });

        const video = document.createElement('video');
        video.srcObject = stream;
        video.setAttribute('playsinline', 'true');
        video.muted = true;

        await video.play();

        await new Promise(resolve => setTimeout(resolve, 300));

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 1280;
        canvas.height = video.videoHeight || 720;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        stream.getTracks().forEach(track => track.stop());

        canvas.toBlob(async (blob) => {
            if (!blob) {
                showStatus('No fue posible tomar la foto.', 'error');
                return;
            }

            const url = URL.createObjectURL(blob);

            mostrarSoloPreview(boxFoto);

            fotoPreviewText.style.display = 'none';
            fotoPreview.style.display = 'block';
            fotoPreview.src = url;

            await guardarAlerta('foto', blob, {
                nombreArchivo: 'foto_evidencia.jpg',
                descripcion: 'Foto guardada desde Aliada UTS.'
            });
        }, 'image/jpeg', 0.92);

        showStatus('Foto capturada. Guardando evidencia...', 'info');
    } catch (error) {
        showStatus('No fue posible activar la cámara para tomar foto. Revisa los permisos.', 'error');
    }
}

async function iniciarVideo() {
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: true
        });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            camera: true,
            microphone: true
        });

        videoChunks = [];

        videoPreview.srcObject = cameraStream;
        videoPreview.style.display = 'block';
        videoPreviewText.style.display = 'none';

        mostrarSoloPreview(boxCamara);

        videoRecorder = new MediaRecorder(cameraStream);

        videoRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                videoChunks.push(event.data);
            }
        };

        videoRecorder.onstop = async () => {
            const blob = new Blob(videoChunks, { type: 'video/webm' });
            const url = URL.createObjectURL(blob);

            if (videoPreview) {
                videoPreview.pause();
                videoPreview.srcObject = null;
                videoPreview.style.display = 'none';
            }

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }

            mostrarSoloPreview(boxVideoGrabado);

            videoGrabadoText.style.display = 'none';
            videoGrabadoPlayer.style.display = 'block';
            videoGrabadoPlayer.src = url;

            await guardarAlerta('video', blob, {
                nombreArchivo: 'video_evidencia.webm',
                descripcion: 'Video guardado desde Aliada UTS.'
            });
        };

        videoRecorder.start();
        isRecordingVideo = true;

        setVideoStopButton();

        showStatus('Grabando video de evidencia... toca nuevamente para detener.', 'info');
    } catch (error) {
        showStatus('No fue posible activar cámara y micrófono para video. Revisa los permisos.', 'error');
        resetVideoButton();
    }
}

function detenerVideo() {
    if (videoRecorder && videoRecorder.state !== 'inactive') {
        videoRecorder.stop();
    }

    isRecordingVideo = false;
    resetVideoButton();
    showStatus('Procesando video...', 'info');
}

async function guardarAntesDeLlamar(event, tipo, descripcion) {
    event.preventDefault();

    const href = event.currentTarget.getAttribute('href');

    showStatus('Guardando alerta antes de realizar la llamada...', 'info');

    await guardarAlerta(tipo, null, { descripcion });

    setTimeout(() => {
        window.location.href = href;
    }, 450);
}

if (btnMicrofono) {
    btnMicrofono.addEventListener('click', () => {
        if (isRecordingAudio) {
            detenerAudio();
        } else {
            iniciarAudio();
        }
    });
}

if (btnFoto) {
    btnFoto.addEventListener('click', tomarFoto);
}

if (btnVideo) {
    btnVideo.addEventListener('click', () => {
        if (isRecordingVideo) {
            detenerVideo();
        } else {
            iniciarVideo();
        }
    });
}

if (btnPolicia) {
    btnPolicia.addEventListener('click', (event) => {
        guardarAntesDeLlamar(event, 'policia', 'Se activó llamada a Policía 123 desde Aliada UTS.');
    });
}

if (btnEmergencia) {
    btnEmergencia.addEventListener('click', (event) => {
        guardarAntesDeLlamar(event, 'contacto_emergencia', 'Se activó llamada a contacto de emergencia desde Aliada UTS.');
    });
}

if (btnEmergenciaSinNumero) {
    btnEmergenciaSinNumero.addEventListener('click', () => {
        showStatus('No tienes un contacto de emergencia registrado. Actualiza tu información en el sistema.', 'warning');
    });
}

if (btnPermisos) {
    btnPermisos.addEventListener('click', abrirModalPermisos);
}

if (btnActivarPermisos) {
    btnActivarPermisos.addEventListener('click', solicitarPermisosIniciales);
}

if (btnOmitirPermisos) {
    btnOmitirPermisos.addEventListener('click', () => {
        localStorage.setItem(PERMISSIONS_KEY, '1');
        cerrarModalPermisos();
        showStatus('Puedes continuar. Los permisos se pedirán cuando uses cámara, micrófono o ubicación.', 'warning');
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    actualizarPillsDesdeEstado();
    await refrescarEstadoPermisosReal();

    const yaSolicitados = localStorage.getItem(PERMISSIONS_KEY) === '1';

    if (!yaSolicitados) {
        setTimeout(() => {
            abrirModalPermisos();
        }, 450);
    } else {
        getLocationSilent();
    }
});
</script>

</body>
</html>