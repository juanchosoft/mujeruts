// ── Inicialización ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss del mensaje flash a los 4 segundos
    var flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(function() {
            flash.style.opacity     = '0';
            flash.style.maxHeight   = '0';
            flash.style.padding     = '0';
            flash.style.marginBottom = '0';
        }, 4000);
    }

    // Botones editar
    document.querySelectorAll('.js-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            cargarEdicion(
                this.dataset.id,
                this.dataset.nombre,
                this.dataset.usuario,
                this.dataset.correo,
                this.dataset.rol,
                this.dataset.estado
            );
        });
    });

    // Buscador
    var buscador = document.getElementById('buscadorUsuarios');
    if (buscador) {
        buscador.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var filas = document.querySelectorAll('.vs-table tbody tr.fila-usuario');
            var visibles = 0;

            filas.forEach(function(fila) {
                var texto = fila.dataset.busqueda || '';
                var coincide = texto.includes(q);
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            var sinResultados = document.getElementById('sinResultados');
            if (sinResultados) {
                sinResultados.style.display = (visibles === 0) ? '' : 'none';
            }

            var contador = document.getElementById('contadorUsuarios');
            if (contador) {
                contador.textContent = visibles;
            }
        });
    }
});

// ── Cargar datos en el formulario para editar ─────────────────────────────────
function cargarEdicion(id, nombre, usuario, correo, rol, estado) {
    // Rellenar campos
    document.getElementById('edit_id').value   = id;
    document.getElementById('f_nombre').value  = nombre;
    document.getElementById('f_usuario').value = usuario;
    document.getElementById('f_correo').value  = correo;
    document.getElementById('f_rol').value     = rol;
    document.getElementById('f_estado').value  = estado;
    document.getElementById('f_password').value = '';

    // Ajustar UI
    document.getElementById('formTitle').textContent    = 'Editar usuario';
    document.getElementById('formSubtitle').textContent = 'Modifica los datos del usuario. La contraseña es opcional, si la dejas vacía no se cambiará.';
    document.getElementById('heroCard').classList.add('modo-edicion');
    document.getElementById('btnSubmit').textContent    = 'Actualizar usuario';
    document.getElementById('labelPassword').textContent = 'Nueva contraseña (opcional)';
    document.getElementById('btnCancelarEdicion').style.display = 'inline-flex';

    // Quitar required de password en modo edición
    document.getElementById('f_password').removeAttribute('required');

    // Scroll al formulario
    document.getElementById('heroCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Cancelar edición y volver a modo creación ─────────────────────────────────
function cancelarEdicion() {
    document.getElementById('edit_id').value    = '0';
    document.getElementById('f_nombre').value   = '';
    document.getElementById('f_usuario').value  = '';
    document.getElementById('f_correo').value   = '';
    document.getElementById('f_password').value = '';
    document.getElementById('f_rol').value      = '';
    document.getElementById('f_estado').value   = 'activo';

    document.getElementById('formTitle').textContent    = 'Crear usuarios del panel';
    document.getElementById('formSubtitle').textContent = 'Registra usuarios con rol Administrador o Visor. El rol Visor ingresará directamente al visualizador de emergencias.';
    document.getElementById('heroCard').classList.remove('modo-edicion');
    document.getElementById('btnSubmit').textContent    = 'Guardar usuario';
    document.getElementById('labelPassword').textContent = 'Contraseña';
    document.getElementById('btnCancelarEdicion').style.display = 'none';

    // Restaurar required
    document.getElementById('f_password').setAttribute('required', 'required');
}

// ── Eliminar usuario con SweetAlert2 ─────────────────────────────────────────
function swalEliminar(event, form) {
    event.preventDefault();
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        background: '#1a0f28',
        color: '#e0d9f0',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#3a3550',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) form.submit();
    });
    return false;
}

// ── Activar / Inactivar con SweetAlert2 ──────────────────────────────────────
function swalEstado(event, form, tipo, titulo, texto) {
    event.preventDefault();
    var isInactivar = tipo === 'inactivar';
    Swal.fire({
        title: titulo,
        text: texto,
        icon: isInactivar ? 'warning' : 'question',
        background: '#1a0f28',
        color: '#e0d9f0',
        showCancelButton: true,
        confirmButtonColor: isInactivar ? '#ef4444' : '#22c55e',
        cancelButtonColor: '#3a3550',
        confirmButtonText: isInactivar ? 'Sí, inactivar' : 'Sí, activar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) form.submit();
    });
    return false;
}
