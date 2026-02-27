<?php
// 1. Cargar el NÚCLEO DE SUPERVISOR
require_once 'src/init_supervisor.php';

// 2. Lógica de la Página: Obtener datos del insumo
$mensaje_error = $_GET['mensaje_error'] ?? ''; 
if (!isset($_GET['id']) || empty((int)$_GET['id'])) {
    header("Location: gestion_insumos.php?mensaje_error=ID de insumo no válido.");
    exit;
}
$insumo_id = (int)$_GET['id'];
try {
    $insumo_data = obtener_insumo_por_id($pdo, $insumo_id);
    if (!$insumo_data) {
        header("Location: gestion_insumos.php?mensaje_error=El insumo no existe.");
        exit;
    }
    $categorias = obtener_todas_categorias($pdo, true);
    $proveedores = obtener_todos_proveedores($pdo, false);
} catch (\PDOException $e) {
    header("Location: gestion_insumos.php?mensaje_error=" . urlencode("Error de Conexión: " . $e->getMessage()));
    exit;
}

// 3. Definir el título
$titulo_pagina = "Editar Insumo: " . htmlspecialchars($insumo_data['nombre']);

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <h1 class="text-center mb-5"><i class="bi bi-pencil-square me-3"></i>Editar Insumo</h1>

            <?php if ($mensaje_error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje_error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form id="form_editar_insumo" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="insumo_id" value="<?php echo $insumo_data['id']; ?>">
                <input type="hidden" name="imagen_path_actual" value="<?php echo htmlspecialchars($insumo_data['imagen_path'] ?? ''); ?>">
                
                <div class="row g-4">
                    
                    <div class="col-md-8">
                        <div class="card shadow h-100">
                            <div class="card-header"><i class="bi bi-card-text me-2"></i> Información General</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nombre" class="form-label">Nombre del Insumo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                               value="<?php echo htmlspecialchars($insumo_data['nombre']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                        <select id="categoria_id" name="categoria_id" class="form-select" required>
                                            <option value="">-- Seleccione una --</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>"
                                                    <?php echo ($categoria['id'] == $insumo_data['categoria_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                    <?php echo ($categoria['activo'] == 0) ? ' (Inactiva)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="sku" class="form-label">Código (SKU)</label>
                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="Ej: HER-001"
                                               value="<?php echo htmlspecialchars($insumo_data['sku'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="proveedor_id" class="form-label">Proveedor</label>
                                        <select id="proveedor_id" name="proveedor_id" class="form-select">
                                            <option value="">-- Opcional --</option>
                                            <?php foreach ($proveedores as $p): ?>
                                                <option value="<?php echo $p['id']; ?>"
                                                    <?php echo ($p['id'] == $insumo_data['proveedor_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                                    <?php echo ($p['activo'] == 0) ? ' (Inactivo)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="agrupador" class="form-label">Agrupador (Opcional)</label>
                                        <input type="text" class="form-control" id="agrupador" name="agrupador" placeholder="Ej: MUEBLES-OFICINA"
                                               value="<?php echo htmlspecialchars($insumo_data['agrupador'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow h-100">
                            <div class="card-header"><i class="bi bi-boxes me-2"></i> Inventario y Detalles</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="unidad_medida" class="form-label">Unidad</label>
                                        <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" placeholder="unidades"
                                               value="<?php echo htmlspecialchars($insumo_data['unidad_medida'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stock_minimo" class="form-label">Mínimo</label>
                                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0"
                                               value="<?php echo (int)$insumo_data['stock_minimo']; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="ubicacion" class="form-label">Ubicación Física</label>
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej: Estante A-3"
                                               value="<?php echo htmlspecialchars($insumo_data['ubicacion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="descripcion" class="form-label">Descripción Corta</label>
                                        <input type="text" class="form-control" id="descripcion" name="descripcion"
                                               value="<?php echo htmlspecialchars($insumo_data['descripcion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="notas" class="form-label">Notas Adicionales</label>
                                        <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Información interna..."><?php echo htmlspecialchars($insumo_data['notas'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <img src="ver_archivo.php?insumo_id=<?php echo $insumo_data['id']; ?>" 
                                             alt="Imagen Actual" 
                                             class="img-thumbnail rounded" 
                                             style="width: 100px; height: 100px; object-fit: cover;"
                                             onerror="this.src='https://placehold.co/100x100/e9ecef/6c757d?text=N/A';">
                                        <div class="small text-muted mt-1">Actual</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="imagen_producto" class="form-label">Cambiar Imagen (JPG, PNG)</label>
                                        <input class="form-control" type="file" id="imagen_producto" name="imagen_producto" accept="image/jpeg, image/png">
                                        <small class="form-text text-muted">Dejar en blanco para conservar la actual.</small>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-center justify-content-md-center mt-3 mt-md-0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                                                   <?php echo ($insumo_data['activo'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="activo">Insumo Activo</label>
                                            <small class="form-text text-muted d-block">Desmarque para desactivar.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <div class="d-flex justify-content-between mt-5 mb-5">
                    <a href="gestion_insumos.php" class="btn btn-secondary btn-lg px-4">Cancelar</a>
                    
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="btn_actualizar_insumo">
                        <i class="bi bi-check-lg me-2"></i>Actualizar Insumo
                    </button>
                </div>
                
            </form>
            
        </div>
    </div> 
</div> </div> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const formEditarInsumo = document.getElementById('form_editar_insumo');
    const btnActualizarInsumo = document.getElementById('btn_actualizar_insumo');

    if (formEditarInsumo) {
        formEditarInsumo.addEventListener('submit', async function(e) {
            e.preventDefault(); // ¡Prevenimos el envío tradicional!

            const btn = btnActualizarInsumo;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

            const formData = new FormData(formEditarInsumo);
            formData.append('accion', 'actualizar'); // Añadimos la acción para la API

            try {
                const response = await fetch('api_gestion_insumos.php', {
                    method: 'POST',
                    // ⭐️ SOLUCIÓN DE SEGURIDAD: Inyectar Token CSRF en la cabecera ⭐️
                    headers: {
                        'X-CSRF-Token': window.CSRF_TOKEN 
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.exito) {
                    throw new Error(data.mensaje || 'Error desconocido al actualizar.');
                }

                // ¡Éxito!
                mostrarToast('Insumo actualizado correctamente.', 'success');
                
                // Redirigimos después de un breve momento para que se vea el toast
                setTimeout(() => {
                    const url = new URL('gestion_insumos.php', location.origin + location.pathname.substring(0, location.pathname.lastIndexOf('/')) + '/');
                    url.searchParams.append('mensaje_exito', data.mensaje);
                    
                    // Conservar el filtro de 'inactivos' si el item se desactivó
                    const checkActivo = document.getElementById('activo');
                    if (checkActivo && !checkActivo.checked) {
                         url.searchParams.append('inactivos', '1');
                    }
                    location.href = url.toString();
                }, 1000);

            } catch (error) {
                mostrarToast(error.message, 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
});
</script>
<?php 
// 5. Cargar el footer
require 'src/footer.php'; 
?>