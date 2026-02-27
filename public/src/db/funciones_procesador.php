<?php
// ======================================================================
// 12. PROCESADOR DE MOVIMIENTOS PROGRAMADOS
// ======================================================================

function procesar_movimientos_programados($pdo) {
    
    $sql_find = "SELECT * FROM movimientos 
                 WHERE estado = 'PROGRAMADO' AND fecha_efectiva <= NOW() 
                 LIMIT 50";
    
    try {
        $stmt_find = $pdo->query($sql_find);
        $movimientos_a_procesar = $stmt_find->fetchAll();
        
        if (empty($movimientos_a_procesar)) {
            return [true, 0];
        }
        
        $procesados = 0;
        foreach ($movimientos_a_procesar as $mov) {
            
            $pdo->beginTransaction();
            try {
                $cantidad_stock = $mov['cantidad_movida'];
                $es_ajuste = false;
                
                if ($mov['tipo_movimiento'] === 'SALIDA') {
                    $cantidad_stock = -$mov['cantidad_movida'];
                    $stock_actual = obtener_stock_actual_insumo($pdo, $mov['insumo_id'], $mov['deposito_id']);
                    if ($stock_actual < $mov['cantidad_movida']) {
                        error_log("Movimiento programado #{$mov['id']} falló: Stock insuficiente.");
                        $pdo->rollBack();
                        continue;
                    }
                } elseif ($mov['tipo_movimiento'] === 'AJUSTE') {
                    $es_ajuste = true;
                }
                
                list($exito_stock, $msg_stock) = actualizar_stock_total_db(
                    $pdo, 
                    $mov['insumo_id'], 
                    $mov['deposito_id'], 
                    $cantidad_stock, 
                    $es_ajuste
                );
                
                if (!$exito_stock) {
                    throw new \Exception($msg_stock);
                }
                
                if ($mov['tipo_movimiento'] === 'ENTRADA') {
                     $query_lote = "INSERT INTO stock_lotes 
                        (insumo_id, deposito_id, numero_lote, fecha_vencimiento, 
                         cantidad_ingresada, cantidad_actual, movimiento_id_ingreso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($query_lote)->execute([
                        $mov['insumo_id'], $mov['deposito_id'], $mov['numero_lote'], $mov['fecha_vencimiento'],
                        $mov['cantidad_movida'], $mov['cantidad_movida'], $mov['id']
                    ]);
                } elseif ($mov['tipo_movimiento'] === 'AJUSTE') {
                     $query_wipe = "UPDATE stock_lotes SET cantidad_actual = 0 
                                    WHERE insumo_id = ? AND deposito_id = ?";
                    $pdo->prepare($query_wipe)->execute([$mov['insumo_id'], $mov['deposito_id']]);
                    
                    $query_lote = "INSERT INTO stock_lotes 
                        (insumo_id, deposito_id, numero_lote, fecha_vencimiento, 
                         cantidad_ingresada, cantidad_actual, movimiento_id_ingreso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                     $pdo->prepare($query_lote)->execute([
                        $mov['insumo_id'], $mov['deposito_id'], $mov['numero_lote'], $mov['fecha_vencimiento'],
                        $mov['cantidad_movida'], $mov['cantidad_movida'], $mov['id']
                    ]);
                }

                $sql_update = "UPDATE movimientos SET estado = 'EFECTIVO' WHERE id = ?";
                $pdo->prepare($sql_update)->execute([$mov['id']]);

                $pdo->commit();
                $procesados++;

            } catch (\Exception $e_inner) {
                $pdo->rollBack();
                error_log("Error procesando movimiento programado #{$mov['id']}: " . $e_inner->getMessage());
            }
        }
        return [true, $procesados];
        
    } catch (\PDOException $e) {
        error_log("Error en procesar_movimientos_programados: " . $e->getMessage());
        return [false, 0];
    }

}
