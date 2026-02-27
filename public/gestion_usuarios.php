<?php
// ======================================================
// GESTIÓN DE USUARIOS (CENTRO DE COMANDO)
// ======================================================
// MODIFICADO v3.0 - [Asistente] Diseño Premium con Avatares y Métricas

declare(strict_types=1);

// Esta página sigue siendo SOLO para ADMINS
require_once __DIR__ . '/src/init_admin.php';

$titulo_pagina = "Gestión de Usuarios";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* Estilos específicos para esta vista (Avatar) */
    .user-avatar {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 600;
        font-size: 0.9rem;
        color: #fff;
        margin-right: 1rem;
        text-transform: uppercase;
    }
    .user-info-cell {
        display: flex;
        align-items: center;
    }
    .status-dot {
        height: 10px;
        width: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
  </style>
</head>
<body class="bg-light">

<?php 
require_once __DIR__ . '/src/header.php'; 
require_once __DIR__ . '/src/menu.php'; 
?>

<div class="content-wrapper">

    <?php require 'src/navbar.php'; ?>

    <div class="page-content-container">

      <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Gestión de Usuarios</h2>
          <a href="crear_usuario.php" class="btn btn-primary btn-lg shadow-sm">
              <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
          </a>
      </div>

      <div class="row g-4 mb-4">
          <div class="col-md-4">
              <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--brand-primary);">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-light p-3 rounded-circle me-3 text-primary">
                          <i class="bi bi-people fs-3"></i>
                      </div>
                      <div>
                          <h6 class="text-muted text-uppercase mb-1 small fw-bold">Total Usuarios</h6>
                          <h2 class="mb-0 fw-bold" id="metric_total">...</h2>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-md-4">
              <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--success);">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-light p-3 rounded-circle me-3 text-success">
                          <i class="bi bi-person-check fs-3"></i>
                      </div>
                      <div>
                          <h6 class="text-muted text-uppercase mb-1 small fw-bold">Activos</h6>
                          <h2 class="mb-0 fw-bold" id="metric_activos">...</h2>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-md-4">
              <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--danger);">
                  <div class="card-body d-flex align-items-center">
                      <div class="bg-light p-3 rounded-circle me-3 text-danger">
                          <i class="bi bi-shield-lock fs-3"></i>
                      </div>
                      <div>
                          <h6 class="text-muted text-uppercase mb-1 small fw-bold">Administradores</h6>
                          <h2 class="mb-0 fw-bold" id="metric_admins">...</h2>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-muted"><i class="bi bi-list-ul me-2"></i>Directorio de Personal</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Usuario</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Última Conexión</th>
                  <th class="text-end pe-4">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody_usuarios">
                <tr id="fila_loading">
                    <td colspan="5" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Cargando directorio...</p>
                    </td>
                </tr>
                <tr id="fila_no_resultados" style="display: none;">
                    <td colspan="5">
                        <div class="text-center p-5">
                            <i class="bi bi-person-plus empty-state-icon text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-light">No hay usuarios registrados</h5>
                            <p class="text-muted small">Comienza creando el primer usuario.</p>
                        </div>
                    </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
      
    </div> 
</div> 

<div class="modal fade" id="modalCambiarPass" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content shadow-lg border-0" onsubmit="return false;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-key-fill me-2 text-warning"></i>Cambiar Contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Estás cambiando la contraseña para el usuario seleccionado.</p>
        <div class="form-floating">
            <input type="password" id="nueva_password" class="form-control" placeholder="Nueva contraseña" required>
            <label for="nueva_password">Nueva contraseña</label>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnGuardarPass" class="btn btn-primary">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<?php 
require_once __DIR__ . '/src/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const tbody = document.getElementById('tbody_usuarios');
    const filaLoading = document.getElementById('fila_loading');
    const filaNoResultados = document.getElementById('fila_no_resultados');

    // Elementos de Métricas
    const metricTotal = document.getElementById('metric_total');
    const metricActivos = document.getElementById('metric_activos');
    const metricAdmins = document.getElementById('metric_admins');

    // --- Helper: Generar Color de Avatar basado en el nombre ---
    function getColorAvatar(str) {
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2', '#fd7e14', '#20c997'];
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }

    // --- Función de Renderizado Premium ---
    function renderizarTabla(usuarios) {
        tbody.innerHTML = '';
        if (usuarios.length === 0) {
            filaNoResultados.style.display = '';
            tbody.appendChild(filaNoResultados);
            actualizarMetricas(0,0,0);
            return;
        }

        let countActivos = 0;
        let countAdmins = 0;

        usuarios.forEach(u => {
            const tr = document.createElement('tr');
            tr.dataset.userRow = u.id;
            
            const id = u.id;
            const usuario = escapeHTML(u.username || 'N/A');
            const rol = (u.rol || 'operador').toLowerCase();
            const activo = parseInt(u.activo, 10) === 1;
            const ultima_conexion = u.ultima_conexion ? 
                `<span class="text-dark small">${escapeHTML(u.ultima_conexion)}</span>` : 
                `<span class="badge bg-light text-secondary border fw-normal">Nunca</span>`;
            const fecha_creacion = u.fecha_creacion ? escapeHTML(u.fecha_creacion.substring(0, 10)) : 'N/A';

            // Contadores
            if(activo) countActivos++;
            if(rol === 'admin') countAdmins++;

            // Avatar
            const iniciales = usuario.substring(0, 2).toUpperCase();
            const colorAvatar = getColorAvatar(usuario);

            // Badges de Rol
            let rolBadge = '';
            if (rol === 'admin') rolBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Admin</span>';
            else if (rol === 'supervisor') rolBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Supervisor</span>';
            else if (rol === 'operador') rolBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Operador</span>';
            else rolBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Observador</span>';

            // Estado
            let estadoHtml = activo ? 
                `<span class="text-success small fw-bold"><span class="status-dot bg-success"></span>Activo</span>` : 
                `<span class="text-muted small fw-bold"><span class="status-dot bg-secondary"></span>Inactivo</span>`;

            // Botones de Acción
            let accionesHtml = `
                <div class="btn-group" role="group">
                    <a href="ver_usuario_perfil.php?id=${id}" class="btn btn-sm btn-light text-primary border" title="Ver Perfil"><i class="bi bi-person-vcard"></i></a>
                    <a href="ver_usuario_movimientos.php?id=${id}" class="btn btn-sm btn-light text-info border" title="Historial"><i class="bi bi-clock-history"></i></a>
                    <button type="button" class="btn btn-sm btn-light text-dark border" data-bs-toggle="modal" data-bs-target="#modalCambiarPass" data-user-id="${id}" title="Cambiar Clave"><i class="bi bi-key"></i></button>
            `;
            
            if (activo) {
                const disabled = (id == 1) ? 'disabled' : ''; // Asumiendo ID 1 es super admin
                accionesHtml += `<button type="button" class="btn btn-sm btn-light text-danger border btn-accion-usuario" data-accion="desactivar" data-user-id="${id}" data-user-nombre="${usuario}" ${disabled} title="Desactivar"><i class="bi bi-ban"></i></button>`;
            } else {
                accionesHtml += `<button type="button" class="btn btn-sm btn-light text-success border btn-accion-usuario" data-accion="reactivar" data-user-id="${id}" data-user-nombre="${usuario}" title="Reactivar"><i class="bi bi-check-lg"></i></button>`;
            }
            accionesHtml += `</div>`;

            tr.innerHTML = `
                <td class="ps-4">
                    <div class="user-info-cell">
                        <div class="user-avatar shadow-sm" style="background-color: ${colorAvatar};">${iniciales}</div>
                        <div>
                            <div class="fw-bold text-dark">${usuario}</div>
                            <div class="small text-muted">ID: ${id} • Creado: ${fecha_creacion}</div>
                        </div>
                    </div>
                </td>
                <td>${rolBadge}</td>
                <td data-estado>${estadoHtml}</td>
                <td>${ultima_conexion}</td>
                <td class="text-end pe-4">${accionesHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        actualizarMetricas(usuarios.length, countActivos, countAdmins);
    }

    function actualizarMetricas(total, activos, admins) {
        // Efecto de conteo simple
        metricTotal.innerText = total;
        metricActivos.innerText = activos;
        metricAdmins.innerText = admins;
    }

    // --- Helper HTML ---
    function escapeHTML(str) {
        if (!str) return '';
        return str.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    
    // --- Carga de Datos ---
    async function cargarUsuarios() {
        filaLoading.style.display = '';
        filaNoResultados.style.display = 'none';
        tbody.innerHTML = '';
        tbody.appendChild(filaLoading);
        
        try {
            const response = await fetch('api_get_usuarios.php');
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            const data = await response.json();
            if (!data.exito) throw new Error(data.mensaje || 'La API devolvió un error');
            renderizarTabla(data.usuarios);
        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger p-4">Error al cargar: ${error.message}</td></tr>`;
        }
    }
    
    cargarUsuarios();


    // === Lógica de Acciones (Desactivar/Reactivar) ===
    // ... [Misma lógica de SweetAlert2 que ya tenías, adaptada al nuevo diseño] ...
    async function callUsuariosAPI(payload){
      const resp = await fetch('api_usuarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
        body: JSON.stringify(payload)
      });
      const data = await resp.json();
      if (!resp.ok || data.exito === false) throw new Error(data.mensaje || 'Error');
      return data;
    }

    document.addEventListener('click', async (e)=>{
      const btn = e.target.closest('.btn-accion-usuario');
      if (!btn) return;

      const accion  = btn.dataset.accion;
      const userId  = parseInt(btn.dataset.userId, 10);
      const userNombre = btn.dataset.userNombre;

      Swal.fire({
            title: `¿${accion === 'desactivar' ? 'Desactivar' : 'Reactivar'} Usuario?`,
            text: `Vas a cambiar el estado de "${userNombre}".`,
            icon: accion === 'desactivar' ? 'warning' : 'info',
            showCancelButton: true,
            confirmButtonColor: accion === 'desactivar' ? '#d33' : '#198754',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
      }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await callUsuariosAPI({accion:accion, usuario_id:userId});
                    mostrarToast('Estado actualizado correctamente.', 'success');
                    cargarUsuarios(); // Recargamos para actualizar métricas y tabla
                } catch(err) {
                    mostrarToast('Error: ' + err.message, 'danger');
                }
            }
      });
    });

    // === Lógica Cambiar Password ===
    const modalCambiarPass = document.getElementById('modalCambiarPass');
    const btnGuardarPass   = document.getElementById('btnGuardarPass');
    const inputNuevaPass   = document.getElementById('nueva_password');
    let usuarioIdPass = null;

    if (modalCambiarPass){
      modalCambiarPass.addEventListener('show.bs.modal', (ev)=>{
        usuarioIdPass = ev.relatedTarget?.getAttribute('data-user-id') || null;
        inputNuevaPass.value = '';
      });
      btnGuardarPass?.addEventListener('click', async ()=>{
        const pass = inputNuevaPass.value.trim();
        if (!pass) { mostrarToast('La contraseña no puede estar vacía.', 'warning'); return; }
        
        try{
          await callUsuariosAPI({
            accion: 'cambiar_password', 
            usuario_id: parseInt(usuarioIdPass||'0',10), 
            nueva_password: pass,
            csrf_token: window.CSRF_TOKEN
          });
          mostrarToast('Contraseña actualizada con éxito.', 'success');
          bootstrap.Modal.getInstance(modalCambiarPass)?.hide();
        }catch(err){
          mostrarToast('Error: ' + err.message, 'danger');
        }
      });
    }
});
</script>

<?php if (isset($body_class)) echo '</body></html>'; ?>