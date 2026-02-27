<?php
/**
 * ======================================================================
 * GUÍA DE USUARIO (DOCUMENTACIÓN VISUAL COMPLETA)
 * ======================================================================
 * v2.1 - Diseño Premium con Acordeón Estilizado y Contenido Completo
 */

$titulo_pagina = "Centro de Ayuda";
require_once 'src/init.php';
?>

<div class="content-wrapper">
    <?php require 'src/navbar.php'; ?>
    
    <div class="bg-primary text-white py-5 mb-5" style="background: linear-gradient(135deg, var(--brand-primary) 0%, #2c3653 100%);">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3"><i class="bi bi-life-preserver me-3"></i>Centro de Ayuda</h1>
            <p class="lead mb-0 text-white-50">Documentación oficial, flujos de trabajo y guías paso a paso.</p>
        </div>
    </div>

    <div class="page-content-container container" style="max-width: 900px;">

        <style>
            .accordion-premium .accordion-item {
                border: none;
                margin-bottom: 1.5rem;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                border-radius: 1rem !important;
                overflow: hidden;
            }
            .accordion-premium .accordion-button {
                background-color: #fff;
                font-weight: 600;
                color: var(--brand-primary);
                padding: 1.5rem;
                font-size: 1.1rem;
            }
            .accordion-premium .accordion-button:not(.collapsed) {
                background-color: var(--brand-light);
                color: var(--brand-primary);
                box-shadow: none;
            }
            .accordion-premium .accordion-button:focus {
                box-shadow: none;
                border-color: rgba(0,0,0,.125);
            }
            .accordion-premium .accordion-body {
                background-color: #fff;
                padding: 2rem;
                line-height: 1.7;
                color: #4b5563;
            }
            .step-number {
                display: inline-block;
                width: 24px; height: 24px;
                background-color: var(--brand-primary);
                color: #fff;
                border-radius: 50%;
                text-align: center;
                line-height: 24px;
                font-size: 0.85rem;
                margin-right: 8px;
            }
        </style>

        <div class="accordion accordion-premium" id="guiaAcordeon">
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        <i class="bi bi-speedometer2 me-3 fs-4 text-secondary"></i> 1. Inicio (Dashboard)
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#guiaAcordeon">
                    <div class="accordion-body">
                        <p class="lead text-dark">El Dashboard es tu vista rápida del estado del inventario. Se actualiza automáticamente cada vez que accedes.</p>
                        <hr>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i><strong>Insumos con Stock Bajo:</strong> 
                                Alerta crítica. Indica la cantidad de insumos cuyo stock actual es <u>igual o menor</u> a su mínimo definido.
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-box-seam-fill text-primary me-2"></i><strong>Insumos en Catálogo:</strong> 
                                Muestra el total de productos únicos activos registrados en el sistema.
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-pie-chart-fill text-info me-2"></i><strong>Gráficos Visuales:</strong> 
                                Distribución del stock por Categorías y Depósitos. Te ayudan a identificar dónde está concentrado el inventario.
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-clock-history text-secondary me-2"></i><strong>Últimos Movimientos:</strong> 
                                Un registro en tiempo real de las últimas 5 transacciones efectivas.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                        <i class="bi bi-arrow-left-right me-3 fs-4 text-success"></i> 2. Registrar Movimiento
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#guiaAcordeon">
                    <div class="accordion-body">
                        <p>Función principal para modificar existencias en un depósito. Es vital entender los tipos de operación:</p>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border h-100">
                                    <strong class="text-success d-block mb-2"><i class="bi bi-box-arrow-in-down"></i> ENTRADA</strong>
                                    Para registrar nueva mercadería (compras). <br>
                                    <span class="badge bg-secondary">Requiere Lote</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border h-100">
                                    <strong class="text-danger d-block mb-2"><i class="bi bi-box-arrow-up"></i> SALIDA</strong>
                                    Para consumo interno. Utiliza <strong>Lógica FIFO</strong> (Primero en vencer, primero en salir).
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border h-100">
                                    <strong class="text-warning text-dark d-block mb-2"><i class="bi bi-tools"></i> AJUSTE</strong>
                                    (Solo Admin/Supervisor). Define el stock final ignorando el anterior.
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3">Flujo de Salidas (Trazabilidad):</h5>
                        <ol class="list-group list-group-numbered list-group-flush">
                            <li class="list-group-item">Selecciona el <strong>Depósito</strong> y el <strong>Insumo</strong>.</li>
                            <li class="list-group-item">Selecciona <strong>SALIDA</strong> en el tipo de movimiento.</li>
                            <li class="list-group-item">Aparecerá una tabla con los lotes disponibles, ordenados por vencimiento.</li>
                            <li class="list-group-item">Ingresa la cantidad a sacar en el lote correspondiente (el sistema sugiere los más antiguos).</li>
                            <li class="list-group-item">Confirma la operación. Se descontará del stock específico de ese lote.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                        <i class="bi bi-truck me-3 fs-4 text-info"></i> 3. Registrar Traslado
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#guiaAcordeon">
                    <div class="accordion-body">
                        <p>Un traslado es una <strong>doble transacción automática</strong>: genera una Salida en el Origen y una Entrada en el Destino, preservando la trazabilidad del lote.</p>
                        
                        <div class="alert alert-info border-0 d-flex align-items-center">
                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                            <div>
                                <strong>Importante:</strong> No se puede trasladar al mismo depósito. Debes tener permisos sobre el depósito de origen.
                            </div>
                        </div>

                        <h6>Pasos para realizar un traslado:</h6>
                        <ol>
                            <li class="mb-2">Selecciona <strong>Depósito Origen</strong> (Sale) y <strong>Depósito Destino</strong> (Entra).</li>
                            <li class="mb-2">Busca el insumo. El sistema cargará el stock del origen.</li>
                            <li class="mb-2">Indica qué cantidad mover de cada lote disponible.</li>
                            <li class="mb-2">Al confirmar, el sistema crea un nuevo lote en el destino con la misma fecha de vencimiento que el original.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                        <i class="bi bi-file-earmark-bar-graph me-3 fs-4 text-warning"></i> 4. Historial y Reportes
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#guiaAcordeon">
                    <div class="accordion-body">
                        <p>El sistema cuenta con reportes dinámicos que no requieren recargar la página para filtrar datos.</p>
                        
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong><i class="bi bi-boxes me-2"></i>Stock Actual:</strong> 
                                Vista general de existencias. Permite filtrar por depósito o categoría. Los ítems con stock bajo se resaltan en rojo.
                            </li>
                            <li class="list-group-item">
                                <strong><i class="bi bi-list-task me-2"></i>Historial de Movimientos:</strong> 
                                La bitácora completa. Permite <strong>Anular</strong> operaciones (siempre que el lote no haya sido consumido posteriormente) y ver comprobantes PDF.
                            </li>
                            <li class="list-group-item">
                                <strong><i class="bi bi-calendar-x me-2"></i>Reporte de Vencimientos:</strong> 
                                Herramienta preventiva. Filtra lotes que vencerán en los próximos 30, 60 o 90 días para tomar acción antes de perder mercadería.
                            </li>
                            <li class="list-group-item">
                                <strong><i class="bi bi-collection me-2"></i>Reporte Agrupado:</strong> 
                                Consolida el stock de diferentes marcas o presentaciones bajo una misma "Familia" o "Agrupador".
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if ($USUARIO_ROL == 'admin' || $USUARIO_ROL == 'supervisor'): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                        <i class="bi bi-gear-wide-connected me-3 fs-4 text-dark"></i> 5. Administración y Configuración
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#guiaAcordeon">
                    <div class="accordion-body">
                        <p>Herramientas exclusivas para Supervisores y Administradores.</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold text-primary">Gestión de Catálogo</h6>
                                <p class="small text-muted">Alta, baja y modificación de insumos. Definición de stock mínimo, carga de imágenes y asignación de códigos de barras (SKU).</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold text-primary">Reglas de Negocio</h6>
                                <p class="small text-muted">Control estricto. Define qué <strong>Categorías</strong> pueden almacenarse en qué <strong>Depósitos</strong> para evitar errores operativos.</p>
                            </div>
                            
                            <?php if ($USUARIO_ROL == 'admin'): ?>
                            <div class="col-12 mt-2">
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="fw-bold text-dark"><i class="bi bi-shield-lock me-2"></i>Solo Administradores</h6>
                                    <ul class="mb-0 small">
                                        <li><strong>Gestión de Usuarios:</strong> Crear cuentas, resetear contraseñas, desactivar accesos.</li>
                                        <li><strong>Auditoría:</strong> Registro de seguridad que muestra quién consultó qué stock y cuándo.</li>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div> </div>
</div>

<?php 
require 'src/footer.php'; 
?>