<?php
// ======================================================================
// ASIGNAR REGLAS (ADMIN)
// ======================================================================
// MODIFICADO v1.4 - [Asistente] Añadida Seguridad CSRF + SweetAlert2

// 1. Definir el título
$titulo_pagina = "Asignar Reglas";

// 2. Cargar el NÚCLEO DE SUPERVISOR
require_once 'src/init_supervisor.php';


// 3. Lógica de la Página
$mensaje_error = "";
$deposito_id_seleccionado = null;
$categorias_asignadas = [];
$categorias_no_asignadas = [];

try {
    // Leer el depósito seleccionado de la URL
    if (isset($_GET['deposito_id']) && (int)$_GET['deposito_id'] > 0) { 
        $deposito_id_seleccionado = (int)$_GET['deposito_id'];
    }

    // Usar el $pdo global
    $depositos = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
    
    // Si se seleccionó un depósito, buscar sus reglas
    if ($deposito_id_seleccionado) {
        $categorias_asignadas = obtener_categorias_asignadas($pdo, $deposito_id_seleccionado);
        $categorias_no_asignadas = obtener_categorias_no_asignadas($pdo, $deposito_id_seleccionado);
    }

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
    $depositos = [];
    $deposito_id_seleccionado = null;
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">

    <h1 class="mb-4"><i class="bi bi-diagram-3-fill me-3"></i>Asignar Reglas (Depósito <-> Categoría)</h1>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">1. Seleccione un Depósito</h2>
            <form action="reglas.php" method="GET" id="formSeleccionDeposito"> 
                <div class="row align-items-end">
                    <div class="col-md-6 mb-3">
                        <label for="deposito_select" class="form-label">Depósito:</label>
                        <select id="deposito_select" name="deposito_id" class="form-select">
                            <option value="">-- Seleccione un depósito --</option>
                            <?php foreach ($depositos as $deposito): ?>
                                <option value="<?php echo $deposito['id']; ?>" 
                                    <?php echo ($deposito_id_seleccionado == $deposito['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($deposito['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <?php if ($deposito_id_seleccionado): ?>
                            <a href="reglas.php" class="btn btn-secondary">Limpiar Selección</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($deposito_id_seleccionado): ?>
        
        <h2 class="mb-3">2. Asignar Categorías al Depósito Seleccionado</h2>
        <div class="row">
            
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="h5 mb-0">Categorías NO Asignadas</h3>
                    </div>
                    <div class="card-body">
                        <form id="formNoAsignadas" onsubmit="return false;">
                            <input type="hidden" name="deposito_id" value="<?php echo $deposito_id_seleccionado; ?>">
                            <div class="mb-3">
                                <select multiple class="form-select" id="selectNoAsignadas" name="categoria_id[]" size="10" required>
                                    <?php if (empty($categorias_no_asignadas)): ?>
                                        <option value="" disabled>-- Todas las categorías ya están asignadas --</option>
                                    <?php else: ?>
                                        <?php foreach ($categorias_no_asignadas as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button type="button" id="btnAsignar" class="btn btn-primary w-100" 
                                    <?php echo empty($categorias_no_asignadas) ? 'disabled' : ''; ?>>
                                Asignar Seleccionadas &gt;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h3 class="h5 mb-0">Categorías ASIGNADAS</h3>
                    </div>
                    <div class="card-body">
                        <form id="formAsignadas" onsubmit="return false;">
                            <input type="hidden" name="deposito_id" value="<?php echo $deposito_id_seleccionado; ?>">
                            <div class="mb-3">
                                <select multiple class="form-select" id="selectAsignadas" name="categoria_id[]" size="10" required>
                                    <?php if (empty($categorias_asignadas)): ?>
                                        <option value="" disabled>-- No hay categorías asignadas --</option>
                                    <?php else: ?>
                                        <?php foreach ($categorias_asignadas as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button type="button" id="btnQuitar" class="btn btn-danger w-100"
                                    <?php echo empty($categorias_asignadas) ? 'disabled' : ''; ?>>
                                &lt; Quitar Seleccionadas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
        
    <?php else: ?>
        <div class="alert alert-info">
            Por favor, seleccione un depósito para gestionar sus reglas.
        </div>
    <?php endif; ?>

</div> </div> <?php 
require 'src/footer.php'; 
?>

<script>
// El script se ejecuta ahora, DESPUÉS de que TomSelect se ha cargado.
    
// --- (1/2) Inicializar el selector de Depósito ---
let tomSelectDeposito = new TomSelect('#deposito_select', {
    placeholder: '-- Seleccione un depósito --',
    dropdownParent: 'body'  
});

// Añadir evento para que recargue la página al cambiar
tomSelectDeposito.on('change', function(value) {
    if (value) {
        document.getElementById('formSeleccionDeposito').submit();
    }
});

// --- (2/2) Lógica de Asignación (Solo si las cajas existen) ---
const formAsignadas = document.getElementById('formAsignadas');
if (formAsignadas) {
    
    const btnAsignar = document.getElementById('btnAsignar');
    const btnQuitar = document.getElementById('btnQuitar');
    const selectNoAsignadas = document.getElementById('selectNoAsignadas');
    const selectAsignadas = document.getElementById('selectAsignadas');
    const depositoId = formAsignadas.querySelector('input[name="deposito_id"]')?.value;
    
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
    
    if (depositoId) { 
        
        function moverOpcion(selectOrigen, selectDestino, opciones) {
            opciones.forEach(option => {
                selectDestino.appendChild(option); 
                option.selected = false;
            });
            // Re-ordenar alfabéticamente
            const optionsArray = Array.from(selectDestino.options);
            optionsArray.sort((a, b) => a.text.localeCompare(b.text));
            optionsArray.forEach(option => selectDestino.appendChild(option));
            actualizarEstadoBotones();
        }
        
        function actualizarEstadoBotones() {
            // Actualizar "No Asignadas"
            const hayNoAsignadas = Array.from(selectNoAsignadas.options).some(opt => opt.value !== "");
            btnAsignar.disabled = !hayNoAsignadas;
            if (!hayNoAsignadas && !selectNoAsignadas.querySelector('option[disabled]')) {
                 selectNoAsignadas.innerHTML = '<option value="" disabled>-- Todas las categorías ya están asignadas --</option>';
            } else if (hayNoAsignadas && selectNoAsignadas.querySelector('option[disabled]')) {
                 selectNoAsignadas.querySelector('option[disabled]').remove();
            }

            // Actualizar "Asignadas"
            const hayAsignadas = Array.from(selectAsignadas.options).some(opt => opt.value !== "");
            btnQuitar.disabled = !hayAsignadas;
             if (!hayAsignadas && !selectAsignadas.querySelector('option[disabled]')) {
                 selectAsignadas.innerHTML = '<option value="" disabled>-- No hay categorías asignadas --</option>';
            } else if (hayAsignadas && selectAsignadas.querySelector('option[disabled]')) {
                 selectAsignadas.querySelector('option[disabled]').remove();
            }
        }
        
        async function manejarAccion(accion, boton, selectOrigen, selectDestino) {
            const opcionesSeleccionadas = Array.from(selectOrigen.selectedOptions);
            const categoriaIds = opcionesSeleccionadas.map(opt => opt.value).filter(val => val !== "");
            
            if (categoriaIds.length === 0) {
                // ⭐️ CAMBIO: Usar SweetAlert2 ⭐️
                Swal.fire('Error', 'Debe seleccionar al menos una categoría.', 'error');
                return;
            }
            
            const textoOriginalBoton = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = 'Procesando...';
            
            try {
                const response = await fetch('api_reglas.php', {
                    method: 'POST',
                    // ⭐️ CAMBIO CRÍTICO: Añadir el token a las cabeceras ⭐️
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        deposito_id: depositoId,
                        categoria_ids: categoriaIds, 
                        accion: accion
                    })
                });
                const data = await response.json();
                if (data.exito) {
                    mostrarToast("Reglas actualizadas con éxito.", 'success');
                    moverOpcion(selectOrigen, selectDestino, opcionesSeleccionadas);
                } else {
                    throw new Error(data.mensaje || 'Error desconocido al actualizar reglas.');
                }
            } catch (error) {
                console.error('Error en fetch:', error);
                Swal.fire('Error de Conexión', error.message, 'error');
            } finally {
                boton.disabled = false;
                boton.innerHTML = textoOriginalBoton;
            }
        }
        
        btnAsignar.addEventListener('click', function() {
            mostrarConfirmacion(
                '¿Asignar Categorías?',
                '¿Está seguro de que desea asignar las categorías seleccionadas a este depósito?',
                () => {
                    manejarAccion('asignar', btnAsignar, selectNoAsignadas, selectAsignadas);
                }
            );
        });
        
        btnQuitar.addEventListener('click', function() {
            mostrarConfirmacion(
                '¿Quitar Categorías?',
                '¿Está seguro de que desea quitar las categorías seleccionadas de este depósito?',
                () => {
                    manejarAccion('quitar', btnQuitar, selectAsignadas, selectNoAsignadas);
                }
            );
        });

        actualizarEstadoBotones();
    }
}
</script>