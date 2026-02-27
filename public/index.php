<?php
// ======================================================================
// GESTIÓN DE MAESTROS (DEPÓSITOS Y CATEGORÍAS)
// ======================================================================
// v2.2 - Vista Completa: Muestra quién reactivó los ítems activos.

$titulo_pagina = "Gestión de Maestros";
require_once 'src/init_supervisor.php';

$mensaje_error = ""; 

try {
    // true = incluir inactivos (necesario para la lista de papelera)
    // Esto también trae los datos de 'alta_por_username' y 'fecha_alta'
    $depositos = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL, true); 
    $categorias = obtener_todas_categorias($pdo, true); 

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
    $depositos = [];
    $categorias = [];
}
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">

    <h1 class="text-center mb-4"><i class="bi bi-archive-fill me-3"></i>Gestión de Maestros</h1>

    <?php if ($mensaje_error): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($mensaje_error); ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="bi bi-box-seam me-2 text-primary fs-5"></i> 
                    <h5 class="mb-0">Depósitos</h5>
                </div>
                <div class="card-body">
                    
                    <form id="form_nuevo_deposito" method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" id="nuevo_deposito_nombre" name="nombre" placeholder="Nombre del nuevo depósito" maxlength="100" required>
                            <button type="submit" class="btn btn-primary" data-action="crear_deposito">
                                <i class="bi bi-plus-lg"></i> Crear
                            </button>
                        </div>
                    </form>

                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Activos</h6>
                    <div id="lista_depositos" class="d-flex flex-wrap gap-2 mb-4">
                        <?php 
                        $hay_activos_dep = false;
                        foreach ($depositos as $deposito): 
                            $es_activo = isset($deposito['activo']) ? $deposito['activo'] : true;
                            if ($es_activo): 
                                $hay_activos_dep = true;
                                
                                // Lógica para mostrar info de reactivación (Tooltip)
                                $tooltip_alta = "";
                                $icono_recuperado = "";
                                if (!empty($deposito['alta_por_username'])) {
                                    $fecha_fmt = date('d/m/Y H:i', strtotime($deposito['fecha_alta']));
                                    $tooltip_alta = "Reactivado por: " . $deposito['alta_por_username'] . "\nFecha: " . $fecha_fmt;
                                    $icono_recuperado = '<i class="bi bi-arrow-counterclockwise text-success me-1 opacity-75"></i>';
                                }
                        ?>
                            <div class="badge badge-maestro d-flex align-items-center gap-2 py-2 px-3" 
                                 title="<?php echo htmlspecialchars($tooltip_alta); ?>" 
                                 style="cursor: help;">
                                
                                <?php echo $icono_recuperado; ?>
                                <span><?php echo htmlspecialchars($deposito['nombre']); ?></span>
                                
                                <i class="bi bi-x-circle-fill text-danger opacity-50 cursor-pointer" 
                                   style="cursor: pointer;"
                                   onclick="desactivarMaestro(<?php echo $deposito['id']; ?>, 'deposito', '<?php echo htmlspecialchars($deposito['nombre']); ?>')"
                                   title="Desactivar">
                                </i>
                            </div>
                        <?php endif; endforeach; ?>
                        
                        <?php if (!$hay_activos_dep): ?>
                            <p class="text-muted small fst-italic" id="item_vacio_deposito">No hay depósitos activos.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    $depositos_inactivos = array_filter($depositos, function($d) { return isset($d['activo']) && !$d['activo']; });
                    if (!empty($depositos_inactivos)):
                    ?>
                        <hr class="border-secondary opacity-10">
                        <h6 class="text-danger text-uppercase small fw-bold mb-3">Inactivos (Papelera)</h6>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($depositos_inactivos as $deposito): ?>
                                <li class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-decoration-line-through fw-bold text-muted">
                                            <?php echo htmlspecialchars($deposito['nombre']); ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-success rounded-pill" 
                                                onclick="reactivarMaestro(<?php echo $deposito['id']; ?>, 'deposito')">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar
                                        </button>
                                    </div>
                                    
                                    <div class="small text-muted mt-2 bg-light p-2 rounded border">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-info-circle-fill me-2 text-secondary mt-1"></i>
                                            <div>
                                                <strong>Motivo:</strong> <?php echo htmlspecialchars($deposito['motivo_baja'] ?? 'No especificado'); ?><br>
                                                <span class="text-xs">
                                                    Por: <strong><?php echo htmlspecialchars($deposito['baja_por_username'] ?? 'Desconocido'); ?></strong>
                                                    &bull; El: <?php echo htmlspecialchars($deposito['fecha_baja'] ?? '-'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="bi bi-tags me-2 text-info fs-5"></i>
                    <h5 class="mb-0">Categorías</h5>
                </div>
                <div class="card-body">
                    
                    <form id="form_nueva_categoria" method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" id="nueva_categoria_nombre" name="nombre" placeholder="Nombre de la nueva categoría" maxlength="100" required>
                            <button type="submit" class="btn btn-info text-white" data-action="crear_categoria">
                                <i class="bi bi-plus-lg"></i> Crear
                            </button>
                        </div>
                    </form>

                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Activas</h6>
                    <div id="lista_categorias" class="d-flex flex-wrap gap-2 mb-4">
                        <?php 
                        $hay_activos_cat = false;
                        foreach ($categorias as $categoria): 
                            $es_activo = isset($categoria['activo']) ? $categoria['activo'] : true;
                            if ($es_activo): 
                                $hay_activos_cat = true;

                                // Lógica para mostrar info de reactivación (Tooltip)
                                $tooltip_alta_cat = "";
                                $icono_recuperado_cat = "";
                                if (!empty($categoria['alta_por_username'])) {
                                    $fecha_fmt = date('d/m/Y H:i', strtotime($categoria['fecha_alta']));
                                    $tooltip_alta_cat = "Reactivado por: " . $categoria['alta_por_username'] . "\nFecha: " . $fecha_fmt;
                                    $icono_recuperado_cat = '<i class="bi bi-arrow-counterclockwise text-success me-1 opacity-75"></i>';
                                }
                        ?>
                            <div class="badge badge-maestro bg-info-subtle text-info-emphasis border border-info-subtle d-flex align-items-center gap-2 py-2 px-3"
                                 title="<?php echo htmlspecialchars($tooltip_alta_cat); ?>" 
                                 style="cursor: help;">
                                
                                <?php echo $icono_recuperado_cat; ?>
                                <span><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                                
                                <i class="bi bi-x-circle-fill text-danger opacity-50 cursor-pointer" 
                                   style="cursor: pointer;"
                                   onclick="desactivarMaestro(<?php echo $categoria['id']; ?>, 'categoria', '<?php echo htmlspecialchars($categoria['nombre']); ?>')"
                                   title="Desactivar">
                                </i>
                            </div>
                        <?php endif; endforeach; ?>

                        <?php if (!$hay_activos_cat): ?>
                            <p class="text-muted small fst-italic" id="item_vacio_categoria">No hay categorías activas.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    $categorias_inactivas = array_filter($categorias, function($c) { return isset($c['activo']) && !$c['activo']; });
                    if (!empty($categorias_inactivas)):
                    ?>
                        <hr class="border-secondary opacity-10">
                        <h6 class="text-danger text-uppercase small fw-bold mb-3">Inactivas</h6>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($categorias_inactivas as $categoria): ?>
                                <li class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-decoration-line-through fw-bold text-muted">
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-success rounded-pill" 
                                                onclick="reactivarMaestro(<?php echo $categoria['id']; ?>, 'categoria')">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar
                                        </button>
                                    </div>
                                    
                                    <div class="small text-muted mt-2 bg-light p-2 rounded border">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-info-circle-fill me-2 text-secondary mt-1"></i>
                                            <div>
                                                <strong>Motivo:</strong> <?php echo htmlspecialchars($categoria['motivo_baja'] ?? 'No especificado'); ?><br>
                                                <span class="text-xs">
                                                    Por: <strong><?php echo htmlspecialchars($categoria['baja_por_username'] ?? 'Desconocido'); ?></strong>
                                                    &bull; El: <?php echo htmlspecialchars($categoria['fecha_baja'] ?? '-'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php require 'src/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Función para manejar formularios de creación (AJAX)
    function manejarFormularioMaestro(formId, inputId, actionType) {
        const form = document.getElementById(formId);
        const input = document.getElementById(inputId);
        const boton = form.querySelector('button[type="submit"]');
        const textoOriginalBoton = boton.innerHTML;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const nombre = input.value.trim();
            if (nombre === "") {
                mostrarToast('El nombre no puede estar vacío.', 'danger');
                return;
            }

            boton.disabled = true;
            boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';

            try {
                const response = await fetch('api_maestros.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CSRF_TOKEN 
                    },
                    body: JSON.stringify({
                        accion: actionType,
                        nombre: nombre
                    })
                });

                const data = await response.json();

                if (data.exito) {
                    mostrarToast(data.mensaje, 'success');
                    setTimeout(() => location.reload(), 1000); 
                } else {
                    mostrarToast(data.mensaje || 'Error desconocido', 'danger');
                }

            } catch (error) {
                console.error('Error en fetch:', error);
                mostrarToast('Error de conexión.', 'danger');
            } finally {
                boton.disabled = false;
                boton.innerHTML = textoOriginalBoton;
            }
        });
    }

    // Inicializar los dos formularios
    manejarFormularioMaestro('form_nuevo_deposito', 'nuevo_deposito_nombre', 'crear_deposito');
    manejarFormularioMaestro('form_nueva_categoria', 'nueva_categoria_nombre', 'crear_categoria');
    
    // --- FUNCIÓN PARA DESACTIVAR CON MOTIVO (AUDITORÍA) ---
    window.desactivarMaestro = function(id, tipo, nombre) {
        Swal.fire({
            title: `Desactivar ${nombre}`,
            text: 'Por favor, indique el motivo de la baja (obligatorio para auditoría):',
            input: 'text',
            inputPlaceholder: 'Ej: Se cerró el sector, error de carga, duplicado...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Confirmar Baja',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe escribir un motivo para continuar.';
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('api_maestros.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.CSRF_TOKEN 
                        },
                        body: JSON.stringify({
                            accion: 'desactivar_' + tipo,
                            id: id,
                            motivo: result.value 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.exito) {
                        mostrarToast(data.mensaje, 'success');
                        setTimeout(() => location.reload(), 800); 
                    } else {
                        mostrarToast(data.mensaje, 'danger');
                    }
                } catch (error) {
                    mostrarToast('Error de conexión.', 'danger');
                }
            }
        });
    };

    // --- FUNCIÓN PARA REACTIVAR ---
    window.reactivarMaestro = function(id, tipo) {
        Swal.fire({
            title: '¿Reactivar?',
            text: 'El ítem volverá a estar disponible para su uso.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, Reactivar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('api_maestros.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.CSRF_TOKEN 
                        },
                        body: JSON.stringify({
                            accion: 'activar_' + tipo,
                            id: id
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.exito) {
                        mostrarToast(data.mensaje, 'success');
                        setTimeout(() => location.reload(), 800); 
                    } else {
                        mostrarToast(data.mensaje, 'danger');
                    }
                } catch (error) {
                    mostrarToast('Error de conexión.', 'danger');
                }
            }
        });
    };

});
</script>