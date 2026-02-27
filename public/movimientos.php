<?php
/**
 * ======================================================================
 * REGISTRAR MOVIMIENTO (ENTRADA / SALIDA / AJUSTE)
 * ======================================================================
 * V4.1 - PREMIUM: UX Mejorada (Alertas visuales de stock)
 */

// 1. Definir el título
$titulo_pagina = "Registrar Movimiento";

// 2. Cargar el NÚCLEO
require_once 'src/init.php';

// 3. Lógica de la Página
$mensaje_exito = "";
$mensaje_error = "";
$deposito_id_seleccionado = null; 

// Ruta a la carpeta de subidas (un nivel arriba de /public/)
$directorio_subida_privado = __DIR__ . '/../uploads_privados/';

try {
    // --- Obtener datos para la VISTA (GET) ---
    $depositos = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);

    // --- Manejo del Formulario (POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // ⭐️ INICIO DE GUARDIA PARA ROL OBSERVADOR ⭐️
        if ($USUARIO_ROL === 'observador') {
            $mensaje_error = "Acción denegada. El rol 'Observador' no tiene permisos para registrar movimientos.";
        } else {
        // ⭐️ FIN DE GUARDIA ⭐️

            $deposito_id = (int)($_POST['mov_deposito_id'] ?? 0);
            $deposito_id_seleccionado = $deposito_id; // Guardar para recargar la vista
            
            $insumo_id = (int)($_POST['mov_insumo_id'] ?? 0); 
            $tipo_mov = $_POST['mov_tipo'] ?? '';
            $obs = $_POST['mov_obs'] ?? '';
            
            // Datos para ENTRADA/AJUSTE
            $cantidad = (int)($_POST['mov_cantidad'] ?? 0);
            $numero_lote = $_POST['mov_lote'] ?? null;
            $fecha_vencimiento = $_POST['mov_vencimiento'] ?? null;
            if (empty($fecha_vencimiento)) $fecha_vencimiento = null;

            // Datos para SALIDA
            $lotes_a_sacar = $_POST['lotes_a_sacar'] ?? [];
            
            $fecha_efectiva = $_POST['mov_fecha_efectiva'] ?? null;
            if (empty($fecha_efectiva)) $fecha_efectiva = null;

            if ($USUARIO_ROL !== 'admin') {
                $ids_permitidos = array_map(function($d) { return $d['id']; }, $depositos);
                if (!in_array($deposito_id, $ids_permitidos)) {
                     $mensaje_error = "Error: Usted no tiene permisos para operar en el depósito seleccionado.";
                }
            }
            
            if (empty($mensaje_error)) {
                
                // Validaciones básicas
                if (empty($deposito_id) || empty($insumo_id) || empty($tipo_mov)) {
                    $mensaje_error = "Error: Depósito, Insumo y Tipo son obligatorios.";
                } else {
                    
                    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'pdf'];
                    list($exito_subida, $nombre_archivo_recibo) = manejar_subida_movimiento(
                        'mov_recibo', 
                        $directorio_subida_privado, 
                        $tipos_permitidos
                    );

                    if ($exito_subida) {
                        
                        list($exito, $mensaje) = registrar_movimiento_db(
                            $pdo, 
                            $USUARIO_ID, 
                            $deposito_id,
                            $insumo_id,
                            $tipo_mov, 
                            $cantidad, 
                            $lotes_a_sacar,
                            $obs, 
                            $nombre_archivo_recibo,
                            $numero_lote, 
                            $fecha_vencimiento, 
                            $fecha_efectiva
                        );
                        
                        if ($exito) {
                            $mensaje_exito = $mensaje;
                            $deposito_id_seleccionado = null;
                        } else {
                            $mensaje_error = $mensaje; 
                            if ($nombre_archivo_recibo && file_exists($directorio_subida_privado . $nombre_archivo_recibo)) {
                                unlink($directorio_subida_privado . $nombre_archivo_recibo);
                            }
                        }
                    } else {
                        $mensaje_error = $nombre_archivo_recibo;
                    }
                }
            }
        
        } // ⭐️ CIERRE DE GUARDIA DE OBSERVADOR ⭐️
    }

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
    $depositos = [];
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <h1 class="text-center mb-5"><i class="bi bi-file-earmark-spreadsheet-fill me-3"></i>Registrar Movimiento</h1>

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
              Usted está en modo **Observador**. Puede ver esta página, pero no puede registrar nuevos movimientos.
            </div>
            <?php endif; ?>

            <form action="movimientos.php" method="POST" enctype="multipart/form-data">
            
                <div class="row g-4 mb-4">
                    
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header"><i class="bi bi-box-seam me-2"></i> Selección de Insumo y Depósito</div>
                            <div class="card-body">
                                
                                <div class="mb-3">
                                    <label for="mov_deposito_id" class="form-label">Depósito: <span class="text-danger">*</span></label>
                                    <select id="mov_deposito_id" name="mov_deposito_id" class="form-select" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                        <option value="">-- Seleccione un Depósito --</option>
                                        <?php foreach ($depositos as $deposito): ?>
                                            <option value="<?php echo $deposito['id']; ?>" <?php echo ($deposito_id_seleccionado == $deposito['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($deposito['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="mov_insumo_id" class="form-label mb-0">Insumo: <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-abrir-escaner" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-upc-scan me-1"></i> Escanear
                                        </button>
                                    </div>
                                    <select id="mov_insumo_id" name="mov_insumo_id" class="form-select" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                    </select>
                                    
                                    <div class="form-text mt-2" id="infoStockDisplay"></div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header"><i class="bi bi-journal-check me-2"></i> Detalles de la Operación</div>
                            <div class="card-body">
                                
                                <div class="mb-3">
                                    <label for="mov_tipo" class="form-label">Tipo de Movimiento: <span class="text-danger">*</span></label>
                                    <select class="form-select" id="mov_tipo" name="mov_tipo" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                        <option value="ENTRADA">ENTRADA (Suma al stock)</option>
                                        <option value="SALIDA">SALIDA (Resta del stock)</option>
                                        <option value="AJUSTE">AJUSTE (Define el stock final)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="campo_cantidad_container">
                                    <label for="mov_cantidad" class="form-label">Cantidad: <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="mov_cantidad" name="mov_cantidad" min="0" required <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mov_fecha_efectiva" class="form-label">Fecha del Movimiento (Opcional):</label>
                                    <input type="date" class="form-control" id="mov_fecha_efectiva" name="mov_fecha_efectiva" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                                    <small class="form-text text-muted">Deje en blanco para registrarlo ahora. Si elige una fecha futura, el movimiento se programará.</small>
                                </div>

                            </div>
                        </div>
                    </div>
                </div> 
                
                
                <div class="card shadow mb-4">
                    <div class="card-header"><i class="bi bi-box me-2"></i> Trazabilidad (Lote y Vencimiento)</div>
                    <div class="card-body">

                        <div id="campos_lote_container" class="row g-3">
                            <div class="col-md-6">
                                <label for="mov_lote" class="form-label">N° de Lote: <span class="text-danger" id="lote_requerido_asterisco">*</span></label>
                                <input type="text" class="form-control" id="mov_lote" name="mov_lote" placeholder="Ej: LOTE-A123" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label for="mov_vencimiento" class="form-label">Fecha de Vencimiento:</label>
                                <input type="date" class="form-control" id="mov_vencimiento" name="mov_vencimiento" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <div id="tabla_lotes_container" class="mt-3" style="display: none;">
                            <label class="form-label">Lotes Disponibles para **SALIDA** (FIFO): <span class="text-danger">*</span></label>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 120px;">Cant. a Sacar</th>
                                            <th scope="col">Lote</th>
                                            <th scope="col">Vencimiento</th>
                                            <th scope="col">Stock Actual</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_lotes_disponibles">
                                        </tbody>
                                </table>
                            </div>
                            <small class="form-text text-muted">Ingrese la cantidad que desea consumir de cada lote. El sistema prioriza los que vencen antes.</small>
                        </div>
                        
                        <div class="mb-3 mt-4">
                            <label for="mov_obs" class="form-label">Observaciones:</label>
                            <input type="text" class="form-control" id="mov_obs" name="mov_obs" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mov_recibo" class="form-label">Adjuntar Recibo/Factura (Opcional)</label>
                            <input class="form-control" type="file" id="mov_recibo" name="mov_recibo" accept="image/jpeg, image/png, application/pdf" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100" <?php echo ($USUARIO_ROL === 'observador') ? 'disabled' : ''; ?>>
                    <?php echo ($USUARIO_ROL === 'observador') ? 'Acción no permitida' : 'Registrar Movimiento'; ?>
                </button>
            </form>
            
        </div>
    </div>
</div> </div> 

<div class="modal fade" id="modalEscaner" tabindex="-1" aria-labelledby="modalEscanerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEscanerLabel">Escanear Código de Barras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Apunta la cámara al código de barras (SKU) del insumo.</p>
                <div id="visor-escaner" style="width: 100%; height: 250px; background-color: #222; border-radius: var(--bs-border-radius);">
                    </div>
                <div id="log-escaner" class="text-center small mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php 
// 5. Cargar el footer
require 'src/footer.php'; 
?>

<script>
// --- Variables Globales ---
const comboDeposito = document.getElementById('mov_deposito_id');
const comboInsumo = document.getElementById('mov_insumo_id');
const tipoMovSelect = document.getElementById('mov_tipo');
const infoStockDisplay = document.getElementById('infoStockDisplay'); // Referencia al div de info

const campoCantidadContainer = document.getElementById('campo_cantidad_container');
const inputCantidad = document.getElementById('mov_cantidad');

const camposLoteContainer = document.getElementById('campos_lote_container');
const inputLote = document.getElementById('mov_lote');
const asteriscoLote = document.getElementById('lote_requerido_asterisco');

const tablaLotesContainer = document.getElementById('tabla_lotes_container');
const tbodyLotes = document.getElementById('tbody_lotes_disponibles');

let tomSelectInsumo = null; 
let tomSelectDeposito = new TomSelect(comboDeposito, { 
    placeholder: '-- Seleccione un Depósito --'
});

// --- Lógica de Tom-Select (Cargar Insumos) ---
function inicializarTomSelectInsumo(placeholderText = '-- Seleccione un insumo --') {
    if (tomSelectInsumo) { tomSelectInsumo.destroy(); tomSelectInsumo = null; }
    comboInsumo.innerHTML = '';
    tomSelectInsumo = new TomSelect(comboInsumo, {
        placeholder: placeholderText,
        sortField: { field: "text", direction: "asc" }
    });
    tomSelectInsumo.on('change', function(insumoId) {
        if(insumoId) {
            // Llama a la función de stock cuando cambia el insumo
            cargarDatosInsumo(insumoId); 
        } else {
            infoStockDisplay.innerHTML = '';
        }
        
        if (tipoMovSelect.value === 'SALIDA') {
            cargarLotesDisponibles(insumoId);
        }
    });
}

// ⭐️ NUEVA FUNCIÓN: CARGAR DATOS DE STOCK CON MEJORA VISUAL ⭐️
async function cargarDatosInsumo(idInsumo) {
    const depositoId = tomSelectDeposito.getValue();
    if(!depositoId) return;

    infoStockDisplay.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" role="status"></span> Consultando stock...';
    
    try {
        const resStock = await fetch(`api_get_stock_item.php?insumo_id=${idInsumo}&deposito_id=${depositoId}`);
        const dataStock = await resStock.json();
        
        if(dataStock.exito) {
            const stock = parseInt(dataStock.stock);
            
            // --- Lógica Visual de Alerta ---
            let colorClase = stock > 0 ? 'text-success' : 'text-danger';
            let mensajeExtra = '';
            
            // Si es SALIDA y no hay stock, mostramos alerta fuerte
            if (stock === 0 && tipoMovSelect.value === 'SALIDA') {
                mensajeExtra = ' <span class="badge bg-danger ms-2">⚠️ SIN STOCK PARA SALIDA</span>';
            }
            
            infoStockDisplay.innerHTML = `Stock actual en este depósito: <strong class="${colorClase} fs-5">${stock}</strong> unidades.${mensajeExtra}`;
        }
    } catch(err) {
        console.error(err);
        infoStockDisplay.innerHTML = '<span class="text-danger small">No se pudo consultar el stock.</span>';
    }
}

function cargarInsumos(depositoId) {
    inicializarTomSelectInsumo('Cargando...');
    tomSelectInsumo.disable(); 
    if (!depositoId) {
        inicializarTomSelectInsumo('-- Primero seleccione un depósito --');
        return;
    }
    fetch('api_insumos.php?deposito_id=' + depositoId) 
        .then(response => {
            if (!response.ok) { throw new Error('Error en la red'); }
            return response.json();
        })
        .then(data => {
            tomSelectInsumo.enable();
            tomSelectInsumo.clearOptions(); 
            if (data.length === 0) {
                tomSelectInsumo.addOption({value: '', text: '-- Depósito vacío --'});
            } else {
                tomSelectInsumo.addOption({value: '', text: '-- Seleccione un insumo --'});
                data.forEach(insumo => {
                    tomSelectInsumo.addOption({ 
                        value: insumo.id, 
                        text: insumo.nombre,
                        sku: insumo.sku
                    });
                });
            }
            tomSelectInsumo.refreshOptions(false);
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            inicializarTomSelectInsumo('-- Error al cargar insumos --');
        });
}
tomSelectDeposito.on('change', function(value) { cargarInsumos(value); });
inicializarTomSelectInsumo('-- Primero seleccione un depósito --');
if (comboDeposito.value) { cargarInsumos(comboDeposito.value); }


// --- Lógica de UI ---
function actualizarVisibilidadFormulario() {
    const tipo = tipoMovSelect.value;
    const insumoId = tomSelectInsumo.getValue();

    // Actualizamos el stock visual si cambia el tipo (para mostrar la alerta de SALIDA)
    if(insumoId) cargarDatosInsumo(insumoId);

    if (tipo === 'SALIDA') {
        // --- MODO SALIDA ---
        campoCantidadContainer.style.display = 'none';
        inputCantidad.required = false;
        
        camposLoteContainer.style.display = 'none';
        inputLote.required = false;

        tablaLotesContainer.style.display = 'block';
        cargarLotesDisponibles(insumoId); // Cargar lotes si ya hay un insumo
        
    } else {
        // --- MODO ENTRADA o AJUSTE ---
        campoCantidadContainer.style.display = 'block';
        inputCantidad.required = true;
        
        camposLoteContainer.style.display = 'flex'; 
        
        tablaLotesContainer.style.display = 'none';
        tbodyLotes.innerHTML = ''; 

        if (tipo === 'ENTRADA') {
            inputLote.required = true;
            asteriscoLote.style.display = 'inline';
        } else { // Es AJUSTE
            inputLote.required = false;
            asteriscoLote.style.display = 'none';
        }
    }
}

// --- Función: Cargar Lotes (API) ---
async function cargarLotesDisponibles(insumoId) {
    const depositoId = tomSelectDeposito.getValue();
    
    if (!insumoId || !depositoId) {
        tbodyLotes.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Seleccione un insumo para ver sus lotes.</td></tr>';
        return;
    }

    tbodyLotes.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando lotes...</td></tr>';
    
    try {
        const response = await fetch(`api_get_lotes.php?insumo_id=${insumoId}&deposito_id=${depositoId}`);
        if (!response.ok) throw new Error('Error de red al cargar lotes');
        
        const data = await response.json();
        if (!data.exito) throw new Error(data.mensaje);

        if (data.lotes.length === 0) {
            tbodyLotes.innerHTML = '<tr><td colspan="4" class="text-center text-danger fw-bold">No hay lotes con stock disponible para este insumo.</td></tr>';
            return;
        }

        // Si hay lotes, construir la tabla
        tbodyLotes.innerHTML = ''; 
        data.lotes.forEach(lote => {
            const vencimiento = lote.fecha_vencimiento ? lote.fecha_vencimiento : 'N/A';
            const hoy = new Date().toISOString().split('T')[0];
            const esVencido = lote.fecha_vencimiento && lote.fecha_vencimiento < hoy;
            
            const tr = document.createElement('tr');
            if (esVencido) {
                tr.classList.add('table-danger');
            }
            
            tr.innerHTML = `
                <td>
                    <input type="number" 
                           class="form-control form-control-sm" 
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
                    <span class="badge bg-primary">${lote.cantidad_actual}</span>
                </td>
            `;
            tbodyLotes.appendChild(tr);
        });

    } catch (error) {
        console.error(error);
        tbodyLotes.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error: ${error.message}</td></tr>`;
    }
}

// --- Función: Validación de Input de Lote ---
function validarCantidadLote(input, max) {
    let valor = parseInt(input.value, 10);
    if (isNaN(valor) || valor < 0) {
        input.value = 0;
    } else if (valor > max) {
        mostrarToast(`El stock máximo para este lote es ${max}.`, 'danger');
        input.value = max;
    }
}


// --- Lógica del Escáner (sin cambios) ---
document.addEventListener('DOMContentLoaded', function() {
    const modalEscanerEl = document.getElementById('modalEscaner');
    const modalEscaner = new bootstrap.Modal(modalEscanerEl);
    const btnAbrirEscaner = document.getElementById('btn-abrir-escaner');
    const logEscaner = document.getElementById('log-escaner');
    const html5QrcodeScanner = new Html5Qrcode("visor-escaner"); 

    const onScanSuccess = (decodedText, decodedResult) => {
        logEscaner.innerHTML = `<span class="text-success">¡Éxito! Código: ${decodedText}</span>`;
        html5QrcodeScanner.stop().then(ignore => {
            modalEscaner.hide();
            const opciones = tomSelectInsumo.options;
            let insumoEncontradoId = null;
            for (const key in opciones) {
                if (opciones[key].sku === decodedText) {
                    insumoEncontradoId = opciones[key].value;
                    break;
                }
            }
            if (insumoEncontradoId) {
                tomSelectInsumo.setValue(insumoEncontradoId);
                mostrarToast(`Insumo "${decodedText}" seleccionado.`, 'success');
            } else {
                mostrarToast(`Código "${decodedText}" escaneado, pero no se encontró en el depósito actual.`, 'danger');
            }
        }).catch(err => {
            console.error("Error al detener el escáner:", err);
            modalEscaner.hide();
        });
    };
    const onScanFailure = (error) => {};

    btnAbrirEscaner.addEventListener('click', () => {
        if (!tomSelectDeposito.getValue()) {
            mostrarToast('Por favor, seleccione un depósito primero.', 'danger');
            return;
        }
        modalEscaner.show();
    });
    modalEscanerEl.addEventListener('shown.bs.modal', () => {
        logEscaner.innerHTML = "Iniciando cámara...";
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 100 } },
            onScanSuccess, 
            onScanFailure
        ).catch(err => {
            logEscaner.innerHTML = `<span class="text-danger">Error al iniciar la cámara: ${err}</span>`;
        });
    });
    modalEscanerEl.addEventListener('hidden.bs.modal', () => {
        html5QrcodeScanner.stop().catch(err => {});
        logEscaner.innerHTML = "";
    });
});

// --- Inicializar la UI al cargar la página ---
tipoMovSelect.addEventListener('change', actualizarVisibilidadFormulario);
// comboInsumo.addEventListener('change', actualizarVisibilidadFormulario); // Ya manejado por TomSelect onChange
actualizarVisibilidadFormulario(); // Llamar al inicio
</script>