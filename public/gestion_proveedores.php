<?php
// ======================================================================
// VISTA: GESTIÓN DE PROVEEDORES
// ======================================================================
// MODIFICADO v2.1 - [Asistente] Refactor a Carga con AJAX + Seguridad CSRF

$titulo_pagina = "Gestión de Proveedores";
require_once 'src/init_supervisor.php'; // Solo Admin/Supervisor

// ⭐️ CAMBIO: Ya no cargamos los datos aquí, lo hace la API
?>

<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-building me-3"></i>Proveedores</h1>
        <button class="btn btn-primary" onclick="abrirModal()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Proveedor
        </button>
    </div>
    
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form action="#" method="POST" id="form_filtros" class="d-flex justify-content-between">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="checkInactivos" name="inactivos" value="1">
                    <label class="form-check-label" for="checkInactivos">Mostrar proveedores inactivos</label>
                </div>
            </form>
        </div>
    </div>


    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Teléfono / Email</th>
                            <th>Estado</th>
                            <th class="text-end" style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_proveedores">
                        <tr id="fila_loading">
                            <td colspan="5" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando proveedores...</p>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados" style="display: none;">
                            <td colspan="5">
                                <div class="text-center p-5">
                                    <i class="bi bi-search empty-state-icon"></i>
                                    <h4 class="mt-3 fw-light">No se encontraron proveedores</h4>
                                    <p class="text-muted">No hay proveedores registrados que coincidan con el filtro.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav aria-label="Navegación de proveedores" id="nav_paginacion" style="display: none;">
                <ul class="pagination justify-content-center" id="ul_paginacion">
                    </ul>
            </nav>
            
        </div>
    </div>

</div> </div>

<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">Nuevo Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formProveedor">
                    <input type="hidden" id="prov_id" name="id">
                    <input type="hidden" name="accion" value="guardar">
                    
                    <div class="mb-3">
                        <label for="prov_nombre" class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prov_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="prov_contacto" class="form-label">Nombre de Contacto</label>
                        <input type="text" class="form-control" id="prov_contacto" name="contacto" placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="prov_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="prov_telefono" name="telefono">
                        </div>
                        <div class="col-6">
                            <label for="prov_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="prov_email" name="email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="prov_direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="prov_direccion" name="direccion" rows="2"></textarea>
                    </div>
                    
                    <div class="form-check form-switch" id="campo_activo_container" style="display: none;">
                        <input class="form-check-input" type="checkbox" role="switch" id="prov_activo" name="activo" value="1">
                        <label class="form-check-label" for="prov_activo">Proveedor Activo</label>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardar()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?php require 'src/footer.php'; ?>

<script>
// --- Variables de Referencia ---
const modalEl = document.getElementById('modalProveedor');
const modal = new bootstrap.Modal(modalEl);
const form = document.getElementById('formProveedor');
const campoActivoContainer = document.getElementById('campo_activo_container');
const checkActivo = document.getElementById('prov_activo');

const formFiltros = document.getElementById('form_filtros');
const checkInactivos = document.getElementById('checkInactivos');
const tbody = document.getElementById('tbody_proveedores');
const filaLoading = document.getElementById('fila_loading');
const filaNoResultados = document.getElementById('fila_no_resultados');
const navPaginacion = document.getElementById('nav_paginacion');
const ulPaginacion = document.getElementById('ul_paginacion');

// --- Función Principal de Carga de Datos (AJAX) ---
async function cargarProveedores(pagina = 1) {
    mostrarLoading(true);
    
    const params = new URLSearchParams();
    params.append('pagina', pagina);
    if (checkInactivos.checked) {
        params.append('inactivos', '1');
    }
    
    const url = `api_get_proveedores.php?${params.toString()}`;

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        const data = await response.json();

        if (!data.exito) {
            throw new Error(data.mensaje || 'La API devolvió un error');
        }

        renderizarTabla(data.proveedores);
        renderizarPaginacion(data.paginacion.pagina_actual, data.paginacion.total_paginas);
        
        const paramsURL = new URLSearchParams(params);
        paramsURL.delete('pagina'); 
        const urlNavegador = `gestion_proveedores.php?${paramsURL.toString()}&pagina=${pagina}`;
        history.pushState({pagina: pagina}, '', urlNavegador);

    } catch (error) {
        console.error('Error al cargar proveedores:', error);
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger p-4">Error al cargar datos: ${error.message}</td></tr>`;
    } finally {
        mostrarLoading(false);
    }
}

// --- Funciones de Renderizado ---
function mostrarLoading(mostrar) {
    filaLoading.style.display = mostrar ? '' : 'none';
    if (mostrar) {
        filaNoResultados.style.display = 'none';
        tbody.innerHTML = ''; 
        tbody.appendChild(filaLoading);
    }
}

function renderizarTabla(proveedores) {
    tbody.innerHTML = ''; 
    if (proveedores.length === 0) {
        filaNoResultados.style.display = '';
        tbody.appendChild(filaNoResultados);
        return;
    }

    proveedores.forEach(p => {
        const tr = document.createElement('tr');
        tr.id = `fila-prov-${p.id}`;

        const contacto = escapeHTML(p.contacto || '-');
        const telefono = p.telefono ? `<div><i class="bi bi-telephone me-1"></i> ${escapeHTML(p.telefono)}</div>` : '';
        const email = p.email ? `<div><i class="bi bi-envelope me-1"></i> ${escapeHTML(p.email)}</div>` : '';
        
        const estadoHtml = p.activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';

        let accionesHtml = `
            <a href="ver_proveedor_detalle.php?id=${p.id}" 
               class="btn btn-sm btn-info" 
               target="_blank" 
               title="Ver Detalle/Imprimir">
                <i class="bi bi-printer"></i>
            </a>
            <button class="btn btn-sm btn-warning ms-1" 
                    onclick="editarProveedor(${p.id})"
                    title="Editar">
                <i class="bi bi-pencil-fill"></i>
            </button>
        `;
        
        if (p.activo) {
            accionesHtml += `
                <button class="btn btn-sm btn-danger ms-1" 
                        onclick="desactivarProveedor(${p.id}, '${escapeHTML(addslashes(p.nombre))}')"
                        title="Desactivar">
                    <i class="bi bi-trash-fill"></i>
                </button>`;
        } else {
            accionesHtml += `
                <button class="btn btn-sm btn-success ms-1" 
                        onclick="activarProveedor(${p.id}, '${escapeHTML(addslashes(p.nombre))}')"
                        title="Activar">
                    <i class="bi bi-check-circle-fill"></i>
                </button>`;
        }

        tr.innerHTML = `
            <td class="fw-bold">${escapeHTML(p.nombre)}</td>
            <td>${contacto}</td>
            <td>${telefono}${email}</td>
            <td>${estadoHtml}</td>
            <td class="text-end">${accionesHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarPaginacion(paginaActual, totalPaginas) {
    ulPaginacion.innerHTML = '';
    if (totalPaginas <= 1) {
        navPaginacion.style.display = 'none';
        return;
    }
    navPaginacion.style.display = 'block';
    
    const params = new URLSearchParams();
    if (checkInactivos.checked) {
        params.append('inactivos', '1');
    }
    const urlBase = `gestion_proveedores.php?${params.toString()}`;

    ulPaginacion.innerHTML += `
        <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual - 1}">Anterior</a>
        </li>`;
    
    for (let i = 1; i <= totalPaginas; i++) {
        ulPaginacion.innerHTML += `
            <li class="page-item ${i == paginaActual ? 'active' : ''}">
                <a class="page-link" href="#" data-pagina="${i}">${i}</a>
            </li>`;
    }

    ulPaginacion.innerHTML += `
        <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual + 1}">Siguiente</a>
        </li>`;
}

// --- Funciones Helper ---
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return str.toString().replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
function addslashes(str) {
     return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

// --- ⭐️ NUEVO: Helper de Confirmación (SweetAlert2) ⭐️ ---
function mostrarConfirmacion(titulo, texto, callback) {
    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, ¡hazlo!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// --- EVENT LISTENERS Y LÓGICA DE ACCIONES (CORE) ---

// 1. Al cambiar el check de "inactivos"
checkInactivos.addEventListener('change', function() {
    cargarProveedores(1);
});

// 2. Al hacer clic en un link de paginación
ulPaginacion.addEventListener('click', function(e) {
    e.preventDefault();
    const link = e.target.closest('.page-link');
    if (link && !e.target.closest('.disabled')) {
        const pagina = parseInt(link.dataset.pagina, 10);
        if (pagina) {
            cargarProveedores(pagina);
        }
    }
});

// 3. Carga inicial: Leer parámetros de la URL e iniciar
const paramsURL = new URLSearchParams(window.location.search);
if (paramsURL.get('inactivos') === '1') {
    checkInactivos.checked = true;
}

window.cargarProveedores = cargarProveedores;
cargarProveedores(parseInt(paramsURL.get('pagina') || '1', 10));

// --- SCRIPT PARA MODALES Y ACCIONES (CON CSRF y SweetAlert2) ---

function abrirModal() {
    form.reset();
    document.getElementById('prov_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Nuevo Proveedor';
    campoActivoContainer.style.display = 'none'; 
    checkActivo.checked = true; 
    modal.show();
}

async function guardar() {
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.set('activo', checkActivo.checked ? '1' : '0');
    const data = Object.fromEntries(formData.entries());
    
    try {
        const res = await fetch('api_proveedores.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN 
            },
            body: JSON.stringify(data)
        });
        const resp = await res.json();
        
        if (resp.exito) {
            mostrarToast(resp.mensaje, 'success');
            modal.hide();
            const paramsURL = new URLSearchParams(window.location.search);
            cargarProveedores(parseInt(paramsURL.get('pagina') || '1', 10));
        } else {
            mostrarToast(resp.mensaje, 'danger');
        }
    } catch (e) {
        console.error(e);
        mostrarToast('Error de conexión.', 'danger');
    }
}

async function editarProveedor(id) {
    try {
        const res = await fetch('api_proveedores.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify({accion: 'obtener', id: id})
        });
        const resp = await res.json();
        
        if (resp.exito) {
            const d = resp.datos;
            document.getElementById('prov_id').value = d.id;
            document.getElementById('prov_nombre').value = d.nombre;
            document.getElementById('prov_contacto').value = d.contacto;
            document.getElementById('prov_telefono').value = d.telefono;
            document.getElementById('prov_email').value = d.email;
            document.getElementById('prov_direccion').value = d.direccion;
            
            campoActivoContainer.style.display = 'block';
            checkActivo.checked = (d.activo == 1); 
            
            document.getElementById('modalTitulo').innerText = 'Editar Proveedor';
            modal.show();
        } else {
            mostrarToast(resp.mensaje, 'danger');
        }
    } catch (e) {
        mostrarToast('Error al cargar datos.', 'danger');
    }
}

// ⭐️ CAMBIO: Funciones ahora usan SweetAlert2 ⭐️
function desactivarProveedor(id, nombre) {
    mostrarConfirmacion(
        '¿Desactivar Proveedor?',
        `Estás a punto de desactivar a "${nombre}". No se eliminará, pero no aparecerá en nuevas listas.`,
        () => {
            enviarAccionProveedor('desactivar', id);
        }
    );
}

function activarProveedor(id, nombre) {
    mostrarConfirmacion(
        '¿Activar Proveedor?',
        `Estás a punto de activar a "${nombre}".`,
        () => {
            enviarAccionProveedor('activar', id);
        }
    );
}
// ⭐️ FIN DEL CAMBIO ⭐️

async function enviarAccionProveedor(accion, id) {
     try {
        const res = await fetch('api_proveedores.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify({accion: accion, id: id})
        });
        const resp = await res.json();
        
        if (resp.exito) {
            mostrarToast(resp.mensaje, 'success');
            const paramsURL = new URLSearchParams(window.location.search);
            cargarProveedores(parseInt(paramsURL.get('pagina') || '1', 10));
        } else {
            mostrarToast(resp.mensaje, 'danger');
        }
    } catch (e) {
        mostrarToast('Error de conexión.', 'danger');
    }
}
</script>