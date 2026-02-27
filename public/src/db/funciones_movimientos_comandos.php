<?php
/**
 * MÓDULO: FUNCIONES DE MOVIMIENTOS (COMANDOS)
 * (Separado de funciones_movimientos.php v5.2)
 *
 * Contiene:
 * 1. registrar_movimiento_db
 * 2. registrar_traslado_db
 * 3. anular_movimiento_db
 * 4. eliminar_movimiento_programado_db
 *
 * Depende de: src/db/funciones_stock.php (para los helpers)
 */

// ======================================================================
// 5C. FUNCIONES DE REGISTRO DE MOVIMIENTOS
// ======================================================================

function registrar_movimiento_db(
    $pdo, $usuario_id, $deposito_id, $insumo_id, $tipo_mov,
    $cantidad, // Usado por ENTRADA/AJUSTE
    $lotes_a_sacar, // Usado por SALIDA
    $obs, $recibo_path = null,
    $numero_lote, // Usado por ENTRADA/AJUSTE
    $fecha_vencimiento, // Usado por ENTRADA/AJUSTE
    $fecha_efectiva = null
) {
    
    // --- 2. Determinar si es Programado o Efectivo ---
    $estado_movimiento = 'EFECTIVO';
    $fecha_efectiva_sql = date('Y-m-d H:i:s'); // Por defecto, AHORA
    $fecha_efectiva_dt_obj = new DateTime();
    
    if (!empty($fecha_efectiva)) {
        try {
            $fecha_efectiva_dt = new DateTime($fecha_efectiva . ' 00:00:00');
            $fecha_efectiva_sql = $fecha_efectiva_dt->format('Y-m-d H:i:s');
            $fecha_efectiva_dt_obj = $fecha_efectiva_dt;

            $hoy_dt = new DateTime('today 00:00:00');

            if ($fecha_efectiva_dt > $hoy_dt) {
                $estado_movimiento = 'PROGRAMADO';
            } else if ($fecha_efectiva_dt < $hoy_dt) {
                $estado_movimiento = 'EFECTIVO';
            } else {
                $estado_movimiento = 'EFECTIVO';
                $fecha_efectiva_sql = date('Y-m-d H:i:s'); // Usar AHORA
            }

        } catch (Exception $e) {
            return [false, "La fecha del movimiento no es válida."];
        }
    }
    
    // --- 3. Obtener Categoría (común a todos) ---
    $stmt_cat = $pdo->prepare("SELECT categoria_id FROM insumos WHERE id = ?");
    $stmt_cat->execute([$insumo_id]);
    $categoria_id = $stmt_cat->fetchColumn();
    if (!$categoria_id) {
        return [false, "Error: Insumo no encontrado."];
    }

    // ==========================================================
    // LÓGICA DE SALIDA (POR LOTES)
    // ==========================================================
    if ($tipo_mov === 'SALIDA') {
        
        if (!is_array($lotes_a_sacar) || empty($lotes_a_sacar)) {
            return [false, "No se especificaron lotes para la salida."];
        }

        $total_sacado = 0;
        $lotes_afectados_info = [];

        $pdo->beginTransaction();
        try {
            foreach ($lotes_a_sacar as $lote_id => $cantidad_a_sacar) {
                $lote_id = (int)$lote_id;
                $cantidad_a_sacar = (int)$cantidad_a_sacar;

                if ($cantidad_a_sacar <= 0) {
                    continue; // Saltar si no se saca nada de este lote
                }

                $total_sacado += $cantidad_a_sacar;

                // 1. Obtener y bloquear el lote específico
                $stmt_lote = $pdo->prepare(
                    "SELECT numero_lote, cantidad_actual FROM stock_lotes 
                     WHERE id = ? AND insumo_id = ? AND deposito_id = ? FOR UPDATE"
                );
                $stmt_lote->execute([$lote_id, $insumo_id, $deposito_id]);
                $lote = $stmt_lote->fetch();

                // 2. Validar stock del lote
                if (!$lote || $cantidad_a_sacar > $lote['cantidad_actual']) {
                    $pdo->rollBack();
                    return [false, "Stock insuficiente en el lote " . htmlspecialchars($lote['numero_lote'] ?? 'ID:'.$lote_id) . ". Disponible: " . ($lote['cantidad_actual'] ?? 0) . "."];
                }
                
                // 3. Registrar el movimiento individual para este lote
                $query_movimiento = "INSERT INTO movimientos 
                    (usuario_id, deposito_id, insumo_id, tipo_movimiento, cantidad_movida, 
                     observaciones, recibo_path, categoria_id, numero_lote, fecha_vencimiento,
                     fecha_efectiva, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $pdo->prepare($query_movimiento)->execute([
                    $usuario_id, $deposito_id, $insumo_id, 'SALIDA', $cantidad_a_sacar, 
                    $obs, $recibo_path, $categoria_id, $lote['numero_lote'], null,
                    $fecha_efectiva_sql, $estado_movimiento
                ]);

                // 4. Si es efectivo, actualizar stock (lote y total)
                if ($estado_movimiento === 'EFECTIVO') {
                    // 4a. Actualizar el lote específico
                    $pdo->prepare("UPDATE stock_lotes SET cantidad_actual = cantidad_actual - ? WHERE id = ?")
                        ->execute([$cantidad_a_sacar, $lote_id]);
                    
                    // 4b. Actualizar el stock total
                    list($exito_stock, $msg_stock) = actualizar_stock_total_db($pdo, $insumo_id, $deposito_id, -$cantidad_a_sacar, false);
                    if (!$exito_stock) {
                        throw new Exception($msg_stock); // Forzar rollback
                    }
                }
                $lotes_afectados_info[] = "Lote " . htmlspecialchars($lote['numero_lote']) . " (Cant: $cantidad_a_sacar)";
            }

            if ($total_sacado == 0) {
                $pdo->rollBack();
                return [false, "No se especificó una cantidad mayor a 0."];
            }

            $pdo->commit();
            
            if ($estado_movimiento === 'EFECTIVO') {
                return [true, "Salida de $total_sacado registrada. Lotes afectados: " . implode(', ', $lotes_afectados_info)];
            } else {
                $fecha_formateada = $fecha_efectiva_dt_obj ? $fecha_efectiva_dt_obj->format('d/m/Y') : 'la fecha indicada';
                return [true, "Salida de $total_sacado PROGRAMADA para el $fecha_formateada. Lotes afectados: " . implode(', ', $lotes_afectados_info)];
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error en registrar_movimiento_db (SALIDA): " . $e->getMessage());
            return [false, "Error de base de datos. La transacción fue revertida."];
        }
    }
    
    // ==========================================================
    // LÓGICA DE ENTRADA / AJUSTE
    // ==========================================================
    if ($tipo_mov === 'ENTRADA' || $tipo_mov === 'AJUSTE') {
        
        // --- Validaciones ---
        if ($cantidad < 0 || ($cantidad == 0 && $tipo_mov != 'AJUSTE')) {
            return [false, "La cantidad debe ser 0 o mayor."];
        }
        if ($tipo_mov == 'ENTRADA' && empty($numero_lote)) {
            return [false, "El N° de Lote es obligatorio para las ENTRADAS."];
        }
        if ($tipo_mov === 'AJUSTE' && empty($numero_lote)) {
            $numero_lote = "AJUSTE-" . date('Ymd-His');
            $fecha_vencimiento = null;
        }

        $pdo->beginTransaction();
        try {
            // --- Insertar el Movimiento ---
            $query_movimiento = "INSERT INTO movimientos 
                (usuario_id, deposito_id, insumo_id, tipo_movimiento, cantidad_movida, 
                 observaciones, recibo_path, categoria_id, numero_lote, fecha_vencimiento,
                 fecha_efectiva, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_mov = $pdo->prepare($query_movimiento);
            $stmt_mov->execute([
                $usuario_id, $deposito_id, $insumo_id, $tipo_mov, $cantidad, 
                $obs, $recibo_path, $categoria_id, $numero_lote, $fecha_vencimiento,
                $fecha_efectiva_sql, $estado_movimiento
            ]);
            $movimiento_id = $pdo->lastInsertId();

            // --- Lógica de Stock (Solo si es EFECTIVO) ---
            if ($estado_movimiento === 'EFECTIVO') {
                $cantidad_stock = $cantidad; 
                $es_ajuste = ($tipo_mov === 'AJUSTE');

                list($exito_stock, $mensaje_stock) = actualizar_stock_total_db($pdo, $insumo_id, $deposito_id, $cantidad_stock, $es_ajuste);
                if (!$exito_stock) {
                    $pdo->rollBack();
                    return [false, $mensaje_stock];
                }

                if ($tipo_mov === 'ENTRADA') {
                    $query_lote = "INSERT INTO stock_lotes 
                        (insumo_id, deposito_id, numero_lote, fecha_vencimiento, 
                         cantidad_ingresada, cantidad_actual, movimiento_id_ingreso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($query_lote)->execute([
                        $insumo_id, $deposito_id, $numero_lote, $fecha_vencimiento,
                        $cantidad, $cantidad, $movimiento_id
                    ]);

                } elseif ($tipo_mov === 'AJUSTE') {
                    $query_wipe = "UPDATE stock_lotes SET cantidad_actual = 0 
                                   WHERE insumo_id = ? AND deposito_id = ?";
                    $pdo->prepare($query_wipe)->execute([$insumo_id, $deposito_id]);
                    
                    $query_lote = "INSERT INTO stock_lotes 
                        (insumo_id, deposito_id, numero_lote, fecha_vencimiento, 
                         cantidad_ingresada, cantidad_actual, movimiento_id_ingreso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($query_lote)->execute([
                        $insumo_id, $deposito_id, $numero_lote, $fecha_vencimiento,
                        $cantidad, $cantidad, $movimiento_id
                    ]);
                }
                
                $pdo->commit();
                return [true, "Movimiento de " . $tipo_mov . " registrado con éxito."];

            } else {
                // --- Es PROGRAMADO ---
                $pdo->commit();
                $fecha_formateada = $fecha_efectiva_dt_obj ? $fecha_efectiva_dt_obj->format('d/m/Y') : 'la fecha indicada';
                return [true, "Movimiento de " . $tipo_mov . " PROGRAMADO para el " . $fecha_formateada . "."];
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error en registrar_movimiento_db (ENTRADA/AJUSTE): " . $e->getMessage());
            return [false, "Error de base de datos. La transacción fue revertida."];
        }
    }

    return [false, "Tipo de movimiento no reconocido."];
}


function registrar_traslado_db(
    $pdo, $usuario_id, $dep_origen, $dep_destino, $insumo_id, 
    $lotes_a_sacar, // ⭐️ Acepta array multi-lote
    $obs, 
    $fecha_efectiva = null
) {
    // --- 1. Validaciones ---
    if ($dep_origen == $dep_destino) return [false, "El depósito de origen y destino deben ser diferentes."];
    if (!is_array($lotes_a_sacar) || empty($lotes_a_sacar)) {
        return [false, "No se especificaron lotes para el traslado."];
    }
    
    // --- 2. Determinar si es Programado o Efectivo ---
    $estado_movimiento = 'EFECTIVO';
    $fecha_efectiva_sql = date('Y-m-d H:i:s');
    $fecha_efectiva_dt_obj = new DateTime();
    
    if (!empty($fecha_efectiva)) {
        try {
            $fecha_efectiva_dt = new DateTime($fecha_efectiva . ' 00:00:00');
            $fecha_efectiva_sql = $fecha_efectiva_dt->format('Y-m-d H:i:s');
            $fecha_efectiva_dt_obj = $fecha_efectiva_dt;
            $hoy_dt = new DateTime('today 00:00:00');

            if ($fecha_efectiva_dt > $hoy_dt) $estado_movimiento = 'PROGRAMADO';
            else if ($fecha_efectiva_dt < $hoy_dt) $estado_movimiento = 'EFECTIVO';
            else { $estado_movimiento = 'EFECTIVO'; $fecha_efectiva_sql = date('Y-m-d H:i:s'); }
        } catch (Exception $e) {
            return [false, "La fecha del traslado no es válida."];
        }
    }

    // --- 3. Obtener Categoría ---
    $stmt_cat = $pdo->prepare("SELECT categoria_id FROM insumos WHERE id = ?");
    $stmt_cat->execute([$insumo_id]);
    $categoria_id = $stmt_cat->fetchColumn();
    if (!$categoria_id) {
        return [false, "Error: Insumo no encontrado."];
    }

    // --- 4. Iniciar Transacción ---
    $pdo->beginTransaction();
    try {
        $total_trasladado = 0;
        $lotes_afectados_info = [];

        foreach ($lotes_a_sacar as $lote_id => $cantidad_a_sacar) {
            $lote_id = (int)$lote_id;
            $cantidad_a_sacar = (int)$cantidad_a_sacar;

            if ($cantidad_a_sacar <= 0) continue;
            
            $total_trasladado += $cantidad_a_sacar;
            
            // 1. Obtener y bloquear el lote de ORIGEN
            $stmt_lote_origen = $pdo->prepare(
                "SELECT numero_lote, fecha_vencimiento, cantidad_actual 
                 FROM stock_lotes WHERE id = ? AND insumo_id = ? AND deposito_id = ? FOR UPDATE"
            );
            $stmt_lote_origen->execute([$lote_id, $insumo_id, $dep_origen]);
            $lote_origen = $stmt_lote_origen->fetch();

            if (!$lote_origen || $cantidad_a_sacar > $lote_origen['cantidad_actual']) {
                $pdo->rollBack();
                return [false, "Stock insuficiente en el lote " . htmlspecialchars($lote_origen['numero_lote'] ?? 'ID:'.$lote_id) . ". Disponible: " . ($lote_origen['cantidad_actual'] ?? 0) . "."];
            }
            
            $numero_lote = $lote_origen['numero_lote'];
            $fecha_vencimiento = $lote_origen['fecha_vencimiento'];
            $lotes_afectados_info[] = "Lote " . htmlspecialchars($numero_lote) . " (Cant: $cantidad_a_sacar)";

            // 2. Insertar Movimiento de SALIDA (Origen)
            $sql_salida = "INSERT INTO movimientos (usuario_id, deposito_id, insumo_id, tipo_movimiento, cantidad_movida, observaciones, categoria_id, numero_lote, fecha_efectiva, estado) VALUES (?, ?, ?, 'SALIDA', ?, ?, ?, ?, ?, ?)";
            $obs_salida = "Traslado hacia depósito ID: $dep_destino. $obs";
            $pdo->prepare($sql_salida)->execute([$usuario_id, $dep_origen, $insumo_id, $cantidad_a_sacar, $obs_salida, $categoria_id, $numero_lote, $fecha_efectiva_sql, $estado_movimiento]);
            
            // 3. Insertar Movimiento de ENTRADA (Destino)
            $sql_entrada = "INSERT INTO movimientos (usuario_id, deposito_id, insumo_id, tipo_movimiento, cantidad_movida, observaciones, categoria_id, numero_lote, fecha_vencimiento, fecha_efectiva, estado) VALUES (?, ?, ?, 'ENTRADA', ?, ?, ?, ?, ?, ?, ?)";
            $obs_entrada = "Traslado desde depósito ID: $dep_origen. $obs";
            $stmt_ent = $pdo->prepare($sql_entrada);
            $stmt_ent->execute([$usuario_id, $dep_destino, $insumo_id, $cantidad_a_sacar, $obs_entrada, $categoria_id, $numero_lote, $fecha_vencimiento, $fecha_efectiva_sql, $estado_movimiento]);
            $mov_id_entrada = $pdo->lastInsertId();

            // 4. Afectar Stock (Solo si es EFECTIVO)
            if ($estado_movimiento === 'EFECTIVO') {
                // 4a. Descontar del Lote de Origen
                $pdo->prepare("UPDATE stock_lotes SET cantidad_actual = cantidad_actual - ? WHERE id = ?")
                    ->execute([$cantidad_a_sacar, $lote_id]);
                // 4b. Descontar del Total de Origen
                actualizar_stock_total_db($pdo, $insumo_id, $dep_origen, -$cantidad_a_sacar, false);
                
                // 4c. Aumentar en Destino (Total)
                actualizar_stock_total_db($pdo, $insumo_id, $dep_destino, $cantidad_a_sacar, false);

                // 4d. Crear el nuevo lote en Destino
                $sql_lote = "INSERT INTO stock_lotes (insumo_id, deposito_id, numero_lote, fecha_vencimiento, cantidad_ingresada, cantidad_actual, movimiento_id_ingreso) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql_lote)->execute([$insumo_id, $dep_destino, $numero_lote, $fecha_vencimiento, $cantidad_a_sacar, $cantidad_a_sacar, $mov_id_entrada]);
            }
        } // Fin del bucle foreach

        if ($total_trasladado == 0) {
            $pdo->rollBack();
            return [false, "No se especificó una cantidad mayor a 0."];
        }
        
        $pdo->commit();
        
        if ($estado_movimiento === 'EFECTIVO') {
            return [true, "Traslado de $total_trasladado realizado con éxito. Lotes afectados: " . implode(', ', $lotes_afectados_info)];
        } else {
            $fecha_formateada = $fecha_efectiva_dt_obj ? $fecha_efectiva_dt_obj->format('d/m/Y') : 'la fecha indicada';
            return [true, "Traslado de $total_trasladado PROGRAMADO para el $fecha_formateada. Lotes afectados: " . implode(', ', $lotes_afectados_info)];
        }

    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("Error Traslado: " . $e->getMessage());
        return [false, "Error de base de datos en traslado."];
    }
}


// ======================================================================
// 5E. FUNCIONES DE ANULACIÓN DE MOVIMIENTOS
// ======================================================================

function anular_movimiento_db($pdo, $movimiento_id, $usuario_id_anula) {
    $pdo->beginTransaction();
    try {
        $query_get_mov = "SELECT * FROM movimientos WHERE id = ?";
        $stmt_get_mov = $pdo->prepare($query_get_mov);
        $stmt_get_mov->execute([$movimiento_id]);
        $movimiento = $stmt_get_mov->fetch();
        
        if (!$movimiento) {
            $pdo->rollBack();
            return [false, "Movimiento no encontrado."];
        }
        if ($movimiento['anulado_por_id'] !== null) {
            $pdo->rollBack();
            return [false, "Este movimiento ya fue anulado."];
        }

        if ($movimiento['estado'] === 'PROGRAMADO') {
            $pdo->rollBack();
            return [false, "No se puede ANULAR un movimiento PROGRAMADO. Use la opción 'Eliminar'."];
        }
        
        $cantidad_original = (int)$movimiento['cantidad_movida'];
        $insumo_id = $movimiento['insumo_id'];
        $deposito_id = $movimiento['deposito_id'];
        $cantidad_revertir = 0;
        $es_ajuste = false;

        if ($movimiento['tipo_movimiento'] === 'ENTRADA') {
            $cantidad_revertir = -$cantidad_original;
            
            $stmt_lote = $pdo->prepare("SELECT * FROM stock_lotes WHERE movimiento_id_ingreso = ?");
            $stmt_lote->execute([$movimiento_id]);
            $lote_asociado = $stmt_lote->fetch();
            
            if ($lote_asociado) {
                if ((int)$lote_asociado['cantidad_actual'] < (int)$lote_asociado['cantidad_ingresada']) {
                    $pdo->rollBack();
                    return [false, "No se puede anular la ENTRADA. El lote N° " . htmlspecialchars($lote_asociado['numero_lote']) . " ya fue parcialmente consumido."];
                }
                $pdo->prepare("DELETE FROM stock_lotes WHERE id = ?")->execute([$lote_asociado['id']]);
            }
            
        } elseif ($movimiento['tipo_movimiento'] === 'SALIDA') {
            $cantidad_revertir = $cantidad_original;
            $numero_lote_afectado = $movimiento['numero_lote'];

            if (!empty($numero_lote_afectado)) {
                
                // Intentamos devolver el stock al lote
                $stmt_lote_upd = $pdo->prepare(
                    "UPDATE stock_lotes SET cantidad_actual = cantidad_actual + ? 
                     WHERE insumo_id = ? AND deposito_id = ? AND numero_lote = ? LIMIT 1"
                );
                $stmt_lote_upd->execute([$cantidad_revertir, $insumo_id, $deposito_id, $numero_lote_afectado]);
                
                // ---------------------------------------------------------
                // CORRECCIÓN APLICADA: Verificar si el lote existía
                // ---------------------------------------------------------
                if ($stmt_lote_upd->rowCount() == 0) {
                    // Si llegamos aquí, el lote ya no existe en la DB.
                    // Opción A: Recrearlo (Complejo y riesgoso sin fecha original).
                    // Opción B: Bloquear la anulación (Más seguro).
                    $pdo->rollBack();
                    return [false, "Error crítico de integridad: El lote original ($numero_lote_afectado) ya no existe en el sistema. No se puede restaurar el stock automáticamente."];
                }
                // ---------------------------------------------------------

            } else {
                $pdo->rollBack();
                return [false, "Error al anular: El movimiento de salida no tiene un N° de Lote asociado."];
            }
            
        } elseif ($movimiento['tipo_movimiento'] === 'AJUSTE') {
            $pdo->rollBack();
            return [false, "Los AJUSTES no se pueden anular. Realice un nuevo ajuste para corregir."];
        }

        if ($cantidad_revertir < 0) {
            $stock_actual = obtener_stock_actual_insumo($pdo, $insumo_id, $deposito_id);
            if ($stock_actual < abs($cantidad_revertir)) {
                 $pdo->rollBack();
                return [false, "Stock insuficiente para revertir la operación. Stock actual: " . $stock_actual . " unidades."];
            }
        }
        
        list($exito_stock, $mensaje_stock) = actualizar_stock_total_db($pdo, $insumo_id, $deposito_id, $cantidad_revertir, $es_ajuste);
        if (!$exito_stock) {
            $pdo->rollBack();
            return [false, $mensaje_stock];
        }
        
        $query_anular = "UPDATE movimientos SET anulado_por_id = ? WHERE id = ?";
        $stmt_anular = $pdo->prepare($query_anular);
        $stmt_anular->execute([$usuario_id_anula, $movimiento_id]);
        
        $pdo->commit();
        return [true, "Movimiento N°" . $movimiento_id . " anulado con éxito. El stock total y el stock del lote fueron revertidos."];
        
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("Error en anular_movimiento_db: " . $e->getMessage());
        return [false, "Error de base de datos. La transacción fue revertida."];
    }
}

function eliminar_movimiento_programado_db($pdo, $movimiento_id, $usuario_rol) {
    if ($usuario_rol !== 'admin' && $usuario_rol !== 'supervisor') {
         return [false, "Acceso denegado."];
    }
    $pdo->beginTransaction();
    try {
        $query_get_mov = "SELECT * FROM movimientos WHERE id = ?";
        $stmt_get_mov = $pdo->prepare($query_get_mov);
        $stmt_get_mov->execute([$movimiento_id]);
        $movimiento = $stmt_get_mov->fetch();
        
        if (!$movimiento) {
            $pdo->rollBack();
            return [false, "Movimiento no encontrado."];
        }
        if ($movimiento['estado'] !== 'PROGRAMADO') {
            $pdo->rollBack();
            return [false, "Solo se pueden eliminar movimientos PROGRAMADOS."];
        }
        
        $query_delete = "DELETE FROM movimientos WHERE id = ?";
        $stmt_delete = $pdo->prepare($query_delete);
        $stmt_delete->execute([$movimiento_id]);
        
        $pdo->commit();
        return [true, "Movimiento programado N°" . $movimiento_id . " eliminado con éxito."];
        
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("Error en eliminar_movimiento_programado_db: " . $e->getMessage());
        return [false, "Error de base de datos. La transacción fue revertida."];
    }
}