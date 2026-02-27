<?php
// ======================================================================
// REGISTRAR TRASLADO (ENTRE DEPÓSITOS)
// ======================================================================
// v2.1 - [Asistente] Rediseño UI: Tarjetas de Origen/Destino y Tabla Limpia

// 1. Definir el título
$titulo_pagina = "Registrar Traslado";

// 2. Cargar el NÚCLEO
require_once 'src/init.php';

// 3. Lógica de la Página
$mensaje_exito = "";
$mensaje_error = "";

$origen_seleccionado = null;
$destino_seleccionado = null;

try {
    $depositos_origen = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
    $depositos_destino = $depositos_origen; 

    // --- Manejo del Formulario (POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if ($USUARIO_ROL === 'observador') {
            $mensaje_error = "Acción denegada. El rol 'Observador' no tiene permisos para registrar traslados.";
        } else {
        
            $dep_origen = (int)($_POST['dep_origen'] ?? 0);
            $dep_destino = (int)($_POST['dep_destino'] ?? 0);
            $insumo_id = (int)($_POST['insumo_id'] ?? 0);
            $obs = trim($_POST['observaciones'] ?? '');
            
            // Leer el array de lotes
            $lotes_a_sacar = $_POST['lotes_a_sacar'] ?? [];
            
            $fecha_efectiva = $_POST['fecha_efectiva'] ?? null;
            if (empty($fecha_efectiva)) $fecha_efectiva = null;

            $origen_seleccionado = $dep_origen;
            $destino_seleccionado = $dep_destino;

            if ($dep_origen <= 0 || $dep_destino <= 0 || $insumo_id <= 0) {
                $mensaje_error = "Debe seleccionar origen, destino e insumo.";
            } elseif ($dep_origen === $dep_destino) {
                $mensaje_error = "El depósito de origen y destino no pueden ser el mismo.";
            } else {
                
                if ($USUARIO_ROL !== 'admin') {
                    $ids_permitidos = array_map(function($d) { return $d['id']; }, $depositos_origen);
                    if (!in_array($dep_origen, $ids_permitidos)) {
                        $mensaje_error = "No tienes permiso para retirar stock del depósito de origen.";
                    }
                }

                if (empty($mensaje_error)) {
                    
                    list($exito, $msg) = registrar_traslado_db(
                        $pdo, 
                        $USUARIO_ID, 
                        $dep_origen, 
                        $dep_destino, 
                        $insumo_id, 
                        $lotes_a_sacar, 
                        $obs,
                        $fecha_efectiva
                    );

                    if ($exito) {
                        $mensaje_exito = $msg;
                        $origen_seleccionado = null;
                        $destino_seleccionado = null;
                    } else {
                        $mensaje_error = $msg;
                    }
                }
            }
        
        } // Fin guardia observador
    }

} catch (\PDOException $e) {
    $mensaje_error = "Error de sistema: " . $e->getMessage();
    $depositos_origen = [];
    $depositos_destino = [];
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <h1 class="text-center mb-5"><i class="bi bi-arrow-left-right me-3"></i>Registrar Traslado</h1>
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($mensaje_error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje_error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($USUARIO_ROL === 'observador'): ?>
            <div class="alert alert-warning" role="alert">
              <i class="bi bi-eye-fill me-2"></i>
              Usted está en modo **Observador**. Puede ver esta página, pero no puede registrar traslados.
            </div>
            <?php endif; ?>

            <form action="traslados.php" method="POST">
                
                <div class="row g-4 mb-4">
                    
                    <div class="col-md-6">
                        <div class="card h-100 shadow card-metric" style="border-left-color: var(--danger);">
                            <div class="card-header text-danger fw-bold">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Desde (Origen)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="dep_origen" class="form-label">Depósito de Salida <span class="text-danger">*</span></label>
                                    <select id="dep_origen" name="dep_origen" class="form-select" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                        <option value="">-- Seleccione Origen --</option>
                                        <?php foreach ($depositos_origen as $d): ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo ($origen_seleccionado == $d['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="insumo_id" class="form-label">Insumo a Mover <span class="text-danger">*</span></label>
                                    <select id="insumo_id" name="insumo_id" class="form-select" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                    </select>
                                </div>
                                
                                <div id="stock_info_display" class="mt-4 text-center p-3 rounded bg-light border" style="display: none;">
                                    </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 shadow card-metric" style="border-left-color: var(--success);">
                            <div class="card-header text-success fw-bold">
                                <i class="bi bi-box-arrow-in-down-left me-2"></i>Hacia (Destino)
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <label for="dep_destino" class="form-label">Depósito de Entrada <span class="text-danger">*</span></label>
                                    <select id="dep_destino" name="dep_destino" class="form-select" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                        <option value="">-- Seleccione Destino --</option>
                                        <?php foreach ($depositos_destino as $d): ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo ($destino_seleccionado == $d['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info d-flex align-items-start mt-auto" role="alert">
                                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                                    <div>
                                        <strong>Información:</strong><br>
                                        Los lotes seleccionados se moverán a este depósito manteniendo sus fechas de vencimiento originales.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tabla_lotes_container" class="card shadow mb-4" style="display: none;">
                    <div class="card-header fw-bold">
                        <i class="bi bi-layers-half me-2"></i> Selección de Lotes (FIFO)
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">El sistema sugiere los lotes más antiguos primero. Ingrese la cantidad a mover de cada uno.</p>
                        
                        <div class="table-responsive border rounded">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 130px;">Cant. a Mover</th>
                                        <th scope="col">N° Lote</th>
                                        <th scope="col">Vencimiento</th>
                                        <th scope="col">Stock Actual</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_lotes_disponibles">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="fecha_efectiva" class="form-label">Fecha del Traslado (Opcional)</label>
                                <input type="date" class="form-control" id="fecha_efectiva" name="fecha_efectiva" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                <div class="form-text">Dejar vacío para registrar con fecha y hora actual.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="observaciones" class="form-label">Motivo / Observaciones</label>
                                <input type="text" class="form-control" id="observaciones" name="observaciones" placeholder="Ej: Reabastecimiento mensual..." <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                        <i class="bi bi-send-fill me-2"></i> 
                        <?php echo ($USUARIO_ROL === 'observador') ? 'Acción no permitida' : 'Confirmar Traslado'; ?>
                    </button>
                </div>

            </form>
            
        </div>
    </div>

</div> </div> 

<?php require 'src/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Variables Globales ---
    const comboOrigen = document.getElementById('dep_origen');
    const comboInsumo = document.getElementById('insumo_id');
    const tablaLotesContainer = document.getElementById('tabla_lotes_container');
    const tbodyLotes = document.getElementById('tbody_lotes_disponibles');

    new TomSelect('#dep_origen', { placeholder: 'Seleccione origen' });
    new TomSelect('#dep_destino', { placeholder: 'Seleccione destino' });
    let tomSelectInsumo = null; 

    // --- Lógica de Stock (UX Mejorada) ---
    async function gestionarVistaDeStock(insumo_id) {
        const display = document.getElementById('stock_info_display');
        const deposito_id = comboOrigen.value;
        if (!insumo_id || !deposito_id) {
            display.style.display = 'none';
            return;
        }
        display.style.display = 'block';
        display.innerHTML = '<div class="spinner-border spinner-border-sm text-secondary" role="status"></div> Consultando stock...';
        
        try {
            const response = await fetch(`api_get_stock_item.php?insumo_id=${insumo_id}&deposito_id=${deposito_id}`);
            if (!response.ok) { throw new Error('Error API'); }
            const data = await response.json();
            
            if (data.exito) {
                // Diseño moderno del contador de stock
                display.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <span class="text-muted text-uppercase small fw-bold mb-1">Stock Disponible</span>
                        <span class="display-4 fw-bold text-dark" style="line-height: 1;">${data.stock}</span>
                        <span class="badge bg-light text-dark border mt-2">Unidades</span>
                    </div>
                `;
            } else { throw new Error(data.mensaje); }
        } catch (error) {
            console.error(error);
            display.innerHTML = '<span class="text-danger small">No se pudo consultar el stock.</span>';
        }
    }

    // --- Lógica de Tom-Select ---
    function inicializarTomSelectInsumo(placeholderText) {
        if (tomSelectInsumo) { tomSelectInsumo.destroy(); tomSelectInsumo = null; }
        comboInsumo.innerHTML = '';
        tomSelectInsumo = new TomSelect(comboInsumo, {
            placeholder: placeholderText,
            sortField: { field: "text", direction: "asc" }
        });
        
        tomSelectInsumo.on('change', function(insumoId) {
            gestionarVistaDeStock(insumoId);
            cargarLotesDisponibles(insumoId);
        }); 
    }

    function cargarInsumos(depositoId) {
        gestionarVistaDeStock(null);
        cargarLotesDisponibles(null);
        
        inicializarTomSelectInsumo('Cargando...');
        tomSelectInsumo.disable(); 
        if (!depositoId) {
            inicializarTomSelectInsumo('-- Primero elija origen --');
            return;
        }
        fetch('api_insumos.php?deposito_id=' + depositoId) 
            .then(response => response.json())
            .then(data => {
                tomSelectInsumo.enable();
                tomSelectInsumo.clearOptions(); 
                if (data.length === 0) {
                    tomSelectInsumo.addOption({value: '', text: '-- Depósito vacío --'});
                } else {
                    tomSelectInsumo.addOption({value: '', text: '-- Seleccione un insumo --'});
                    data.forEach(insumo => {
                        tomSelectInsumo.addOption({ value: insumo.id, text: insumo.nombre });
                    });
                }
                tomSelectInsumo.refreshOptions(false);
            })
            .catch(error => {
                console.error('Error:', error);
                inicializarTomSelectInsumo('-- Error al cargar --');
            });
    }

    comboOrigen.addEventListener('change', function() {
        cargarInsumos(this.value);
    });

    // --- Lógica de Carga de Lotes ---
    async function cargarLotesDisponibles(insumoId) {
        const depositoId = comboOrigen.value;
        
        if (!insumoId || !depositoId) {
            tablaLotesContainer.style.display = 'none';
            tbodyLotes.innerHTML = '';
            return;
        }

        tablaLotesContainer.style.display = 'block';
        tbodyLotes.innerHTML = '<tr><td colspan="4" class="text-center text-muted p-3">Cargando lotes...</td></tr>';
        
        try {
            const response = await fetch(`api_get_lotes.php?insumo_id=${insumoId}&deposito_id=${depositoId}`);
            if (!response.ok) throw new Error('Error de red');
            
            const data = await response.json();
            if (!data.exito) throw new Error(data.mensaje);

            if (data.lotes.length === 0) {
                tbodyLotes.innerHTML = '<tr><td colspan="4" class="text-center text-danger fw-bold p-3">No hay lotes con stock disponible para trasladar.</td></tr>';
                return;
            }

            tbodyLotes.innerHTML = ''; 
            data.lotes.forEach(lote => {
                const vencimiento = lote.fecha_vencimiento ? lote.fecha_vencimiento : 'N/A';
                const hoy = new Date().toISOString().split('T')[0];
                const esVencido = lote.fecha_vencimiento && lote.fecha_vencimiento < hoy;
                
                const tr = document.createElement('tr');
                if (esVencido) {
                    tr.classList.add('table-danger'); // Visualmente indicar vencidos
                }
                
                tr.innerHTML = `
                    <td>
                        <input type="number" 
                               class="form-control" 
                               name="lotes_a_sacar[${lote.lote_id}]" 
                               min="0" 
                               max="${lote.cantidad_actual}" 
                               placeholder="0"
                               oninput="validarCantidadLote(this, ${lote.cantidad_actual})">
                    </td>
                    <td><span class="badge bg-secondary">${lote.numero_lote}</span></td>
                    <td>
                        ${vencimiento}
                        ${esVencido ? '<span class="badge bg-danger ms-1">Vencido</span>' : ''}
                    </td>
                    <td>
                        <span class="badge bg-primary fs-6">${lote.cantidad_actual}</span>
                    </td>
                `;
                tbodyLotes.appendChild(tr);
            });

        } catch (error) {
            console.error(error);
            tbodyLotes.innerHTML = `<tr><td colspan="4" class="text-center text-danger p-3">Error: ${error.message}</td></tr>`;
        }
    }

    // --- Helper de Validación ---
    window.validarCantidadLote = function(input, max) {
        let valor = parseInt(input.value, 10);
        if (isNaN(valor) || valor < 0) {
            input.value = 0;
        } else if (valor > max) {
            mostrarToast(`Máximo disponible en este lote: ${max}`, 'warning');
            input.value = max;
        }
    }
    
    // --- Inicialización ---
    inicializarTomSelectInsumo('-- Primero elija origen --');
    if (comboOrigen.value) {
        cargarInsumos(comboOrigen.value);
    }
});
</script>