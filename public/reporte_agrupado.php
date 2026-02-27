<?php
// ======================================================================
// REPORTE DE STOCK AGRUPADO
// ======================================================================
// v2.0 - [Asistente] Rediseño Premium con Métricas y Filtros

// 1. Definir el título
$titulo_pagina = "Reporte Agrupado";

// 2. Cargar el NÚCLEO ESTÁNDAR
require_once 'src/init.php'; 

// 3. Lógica de la Página
$mensaje_error = "";
$stock_agrupado = [];
$total_grupos = 0;
$total_stock_global = 0;

try {
    // Usar el $pdo global y la función existente
    $stock_agrupado = obtener_stock_agrupado($pdo);
    
    // Calcular métricas en PHP para las tarjetas
    $total_grupos = count($stock_agrupado);
    foreach ($stock_agrupado as $grupo) {
        $total_stock_global += (int)$grupo['stock_total'];
    }

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
    $stock_agrupado = [];
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-collection-fill me-2 text-primary"></i>Reporte Agrupado</h1>
            <p class="text-muted mb-0 small">Consolidación de stock por familias o agrupadores.</p>
        </div>
        <button onclick="exportTableToCSV('reporte_agrupado.csv')" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill me-2"></i>Exportar Excel
        </button>
    </div>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger shadow-sm border-0"><?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-metric shadow-sm h-100 card-metric-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fs-2 text-primary mb-0"><?php echo number_format($total_grupos); ?></h5>
                        <p class="card-text text-muted mb-0">Grupos / Familias</p>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-3">
                        <i class="bi bi-tags-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-metric shadow-sm h-100 card-metric-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fs-2 text-success mb-0"><?php echo number_format($total_stock_global); ?></h5>
                        <p class="card-text text-muted mb-0">Unidades Totales en Stock</p>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-3">
                        <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Detalle por Agrupador</h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 bg-light" placeholder="Filtrar por grupo o insumo...">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaReporte">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Agrupador / Familia</th>
                            <th style="width: 15%;" class="text-center">Stock Total</th>
                            <th style="width: 60%;">Desglose de Insumos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock_agrupado)): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="text-center p-5">
                                        <i class="bi bi-inbox-fill empty-state-icon text-muted opacity-25" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3 fw-light text-muted">No hay datos agrupados</h4>
                                        <p class="text-muted small">Asegúrese de asignar un "Agrupador" a sus insumos al editarlos.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stock_agrupado as $grupo): ?>
                                <tr>
                                    <td class="fw-bold text-primary align-top pt-3">
                                        <i class="bi bi-folder2-open me-2"></i>
                                        <?php echo htmlspecialchars($grupo['agrupador']); ?>
                                    </td>
                                    <td class="text-center align-top pt-3">
                                        <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2 rounded-pill border border-primary-subtle">
                                            <?php echo $grupo['stock_total']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                                // Convertimos la cadena separada por || en un array y creamos etiquetas bonitas
                                                $insumos = explode('||', $grupo['insumos_incluidos']);
                                                foreach($insumos as $insumo_str):
                                            ?>
                                                <span class="badge bg-light text-dark border fw-normal p-2 d-flex align-items-center">
                                                    <i class="bi bi-box-seam me-2 text-muted"></i>
                                                    <?php echo htmlspecialchars($insumo_str); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white small text-muted text-end">
            Mostrando <?php echo count($stock_agrupado); ?> grupos
        </div>
    </div>

</div> </div> 

<?php require 'src/footer.php'; ?>

<script>
// 1. Script de Búsqueda en Tiempo Real
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById('tablaReporte');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let tds = tr[i].getElementsByTagName('td');
        let found = false;
        // Buscar en todas las columnas de la fila
        for (let j = 0; j < tds.length; j++) {
            if (tds[j]) {
                let txtValue = tds[j].textContent || tds[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break; 
                }
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
});

// 2. Script simple para Exportar a CSV
function exportTableToCSV(filename) {
    let csv = [];
    let rows = document.querySelectorAll("#tablaReporte tr");
    
    // Solo filas visibles (respeta el filtro de búsqueda)
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].style.display !== 'none') {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                // Limpiar el texto de saltos de linea y espacios dobles
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
    }

    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    let csvFile;
    let downloadLink;
    csvFile = new Blob([csv], {type: "text/csv"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>