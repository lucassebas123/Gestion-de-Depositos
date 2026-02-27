-- ======================================================================
-- RESPALDO DE BASE DE DATOS GESTOR DE INSUMOS v2.0
-- ======================================================================
-- - Sincronizado con todas las funciones del código (Agrupador, Auditoría, Lotes, Proveedores)
-- - Contraseñas de usuario por defecto: '1234'
-- ======================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;

--
-- Base de datos: `gestor_insumos`
--
CREATE DATABASE IF NOT EXISTS `gestor_insumos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestor_insumos`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_consultas_stock`
--
DROP TABLE IF EXISTS `auditoria_consultas_stock`;
CREATE TABLE `auditoria_consultas_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_consulta` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `stock_consultado` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_deposito_id` (`deposito_id`),
  KEY `idx_insumo_id` (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--
INSERT INTO `categorias` (`id`, `nombre`, `activo`) VALUES
(1, 'Papelería y Útiles', 1),
(2, 'Insumos de Limpieza', 1),
(3, 'Componentes de PC', 1),
(4, 'Tóner e Impresión', 1),
(5, 'Herramientas y Mantenimiento', 1),
(6, 'Cafetería', 1),
(7, 'Mobiliario de Oficina', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `depositos`
--
DROP TABLE IF EXISTS `depositos`;
CREATE TABLE `depositos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `depositos`
--
INSERT INTO `depositos` (`id`, `nombre`, `activo`) VALUES
(1, 'Depósito Central (Economato)', 1),
(2, 'Area de Informática (Anexo)', 1),
(3, 'Mantenimiento (Edificio Principal)', 1),
(4, 'Tesorería (Palacio)', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deposito_categoria_link`
--
DROP TABLE IF EXISTS `deposito_categoria_link`;
CREATE TABLE `deposito_categoria_link` (
  `deposito_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  PRIMARY KEY (`deposito_id`,`categoria_id`),
  KEY `fk_dcl_categoria` (`categoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `deposito_categoria_link`
--
INSERT INTO `deposito_categoria_link` (`deposito_id`, `categoria_id`) VALUES
(1, 1),
(2, 1),
(4, 1),
(1, 2),
(3, 2),
(2, 3),
(1, 4),
(2, 4),
(3, 5),
(1, 6),
(1, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--
DROP TABLE IF EXISTS `insumos`;
CREATE TABLE `insumos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `agrupador` varchar(100) DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `ubicacion` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `categoria_id` (`categoria_id`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `idx_agrupador` (`agrupador`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--
INSERT INTO `insumos` (`id`, `nombre`, `descripcion`, `agrupador`, `categoria_id`, `sku`, `proveedor_id`, `unidad_medida`, `stock_minimo`, `ubicacion`, `notas`, `imagen_path`, `activo`) VALUES
(1, 'Resma de Papel A4 75g (Librería El Estudiante)', 'Resma x 500 hojas, alta blancura', 'RESMA-A4', 1, 'PAP-A4-75', 1, 'unidades', 100, 'Estante A-1', '', NULL, 1),
(2, 'Resma de Papel Oficio 75g', 'Resma x 500 hojas', 'RESMA-OFICIO', 1, 'PAP-OFI-75', 1, 'unidades', 50, 'Estante A-1', '', NULL, 1),
(3, 'Birome Azul BIC', 'Caja x 50 unidades', 'BIROME-AZUL', 1, 'UTI-BIC-AZ', 1, 'cajas', 20, 'Estante A-2', '', NULL, 1),
(4, 'Birome Negra BIC', 'Caja x 50 unidades', 'BIROME-NEGRA', 1, 'UTI-BIC-NE', 1, 'cajas', 10, 'Estante A-2', '', NULL, 1),
(5, 'Lápiz Negro HB', 'Caja x 12 unidades', 'LAPIZ-HB', 1, 'UTI-LAP-HB', 2, 'cajas', 10, 'Estante A-2', '', NULL, 1),
(6, 'Grapadora Mit 24/6', 'Metálica, para 30 hojas', 'GRAPADORA-24-6', 1, 'UTI-GRAP-246', 2, 'unidades', 5, 'Estante A-3', '', NULL, 1),
(7, 'Grapas Mit 24/6', 'Caja x 1000 grapas', 'GRAPAS-24-6', 1, 'UTI-GRAP-246-R', 2, 'cajas', 20, 'Estante A-3', '', NULL, 1),
(8, 'Cinta Adhesiva 12mm', 'Transparente, pack x 8 rollos', 'CINTA-12MM', 1, 'UTI-CINTA-12', 2, 'packs', 10, 'Estante A-3', '', NULL, 1),
(9, 'Notas Autoadhesivas 76x76mm', 'Taco x 100 hojas, amarillo', 'POSTIT-76X76', 1, 'UTI-POSTIT-AM', 1, 'unidades', 30, 'Estante A-2', '', NULL, 1),
(10, 'Carpeta Oficio 3 Anillos', 'Color azul, lomo ancho', 'CARPETA-OF-ANILLO', 1, 'UTI-CARP-OF-AZ', 2, 'unidades', 15, 'Estante B-1', '', NULL, 1),
(11, 'Bibliorato Oficio Lomo Ancho', 'Color negro, PVC', 'BIBLIORATO-OF', 1, 'UTI-BIB-OF-NE', 1, 'unidades', 20, 'Estante B-1', '', NULL, 1),
(12, 'Folios Oficio U', 'Paquete x 100 unidades', 'FOLIOS-OF', 1, 'UTI-FOL-OF', 2, 'paquetes', 30, 'Estante B-2', '', NULL, 1),
(13, 'Clips 28mm', 'Caja x 100 unidades', 'CLIPS-28MM', 1, 'UTI-CLIP-28', 2, 'cajas', 20, 'Estante A-3', '', NULL, 1),
(14, 'Sobres Oficio Blanco', 'Paquete x 250 unidades', 'SOBRE-OFICIO', 1, 'PAP-SOB-OF', 1, 'paquetes', 10, 'Estante B-2', '', NULL, 1),
(15, 'Tóner HP 85A (CE285A)', 'Para HP P1102w', 'TONER-85A', 4, 'TON-HP-85A', 3, 'unidades', 10, 'Rack T-1', 'Original', NULL, 1),
(16, 'Tóner HP 12A (Q2612A)', 'Para HP 1020', 'TONER-12A', 4, 'TON-HP-12A', 3, 'unidades', 5, 'Rack T-1', 'Original', NULL, 1),
(17, 'Tóner Samsung 111L', 'Para M2020W', 'TONER-SAM-111L', 4, 'TON-SAM-111L', 4, 'unidades', 15, 'Rack T-1', 'Original', NULL, 1),
(18, 'Tóner Brother TN-1060', 'Para HL-1212W', 'TONER-BRO-1060', 4, 'TON-BRO-1060', 4, 'unidades', 8, 'Rack T-2', 'Alternativo', NULL, 1),
(19, 'Cartucho HP 664XL Negro', 'Para HP 2135, 3635', 'CART-HP-664B', 4, 'CART-HP-664B', 3, 'unidades', 20, 'Rack T-2', '', NULL, 1),
(20, 'Cartucho HP 664XL Color', 'Para HP 2135, 3635', 'CART-HP-664C', 4, 'CART-HP-664C', 3, 'unidades', 15, 'Rack T-2', '', NULL, 1),
(21, 'Lavandina Ayudín 1L', 'Botella 1 Litro', 'LAVANDINA-1L', 2, 'LIMP-LAV-1L', 5, 'unidades', 50, 'Sector L-1', '', NULL, 1),
(22, 'Detergente Magistral 750ml', 'Botella 750ml', 'DETERGENTE-750ML', 2, 'LIMP-DET-750', 5, 'unidades', 30, 'Sector L-1', '', NULL, 1),
(23, 'Papel Higiénico Elegante', 'Bolsón x 30 rollos', 'PAPEL-HIG-30R', 2, 'LIMP-PH-30R', 5, 'bolsones', 40, 'Sector L-2', 'Doble hoja', NULL, 1),
(24, 'Rollos de Cocina Sussexx', 'Paquete x 3 rollos', 'ROLLO-COCINA-3R', 2, 'LIMP-ROLLO-3R', 5, 'paquetes', 30, 'Sector L-2', '', NULL, 1),
(25, 'Bolsas de Residuos 45x60', 'Paquete x 100 unidades', 'BOLSA-RES-45X60', 2, 'LIMP-BOLS-45', 2, 'paquetes', 20, 'Sector L-3', '', NULL, 1),
(26, 'Bolsas de Residuos 90x120', 'Consorcio, paquete x 50', 'BOLSA-RES-90X120', 2, 'LIMP-BOLS-90', 5, 'paquetes', 20, 'Sector L-3', '', NULL, 1),
(27, 'Trapo de Piso Gris', 'Unidad', 'TRAPO-PISO', 2, 'LIMP-TRAPO-P', 5, 'unidades', 50, 'Sector L-1', '', NULL, 1),
(28, 'Escoba Plástica', 'Unidad, sin cabo', 'ESCOBA', 2, 'LIMP-ESCOBA', 5, 'unidades', 10, 'Sector L-4', '', NULL, 1),
(29, 'Secador de Piso Goma 40cm', 'Unidad, sin cabo', 'SECADOR-40CM', 2, 'LIMP-SEC-40', 5, 'unidades', 10, 'Sector L-4', '', NULL, 1),
(30, 'Mouse USB Logitech M90', 'Óptico, con cable', 'MOUSE-USB', 3, 'INF-MOU-M90', 4, 'unidades', 30, 'Rack I-1', '', NULL, 1),
(31, 'Teclado USB Logitech K120', 'Español, con cable', 'TECLADO-USB', 3, 'INF-TEC-K120', 4, 'unidades', 30, 'Rack I-1', '', NULL, 1),
(32, 'Mouse Inalámbrico Logitech M170', 'Receptor USB', 'MOUSE-INAL', 3, 'INF-MOU-M170', 4, 'unidades', 15, 'Rack I-1', '', NULL, 1),
(33, 'Teclado Inalámbrico Logitech K270', 'Receptor USB', 'TECLADO-INAL', 3, 'INF-TEC-K270', 4, 'unidades', 10, 'Rack I-1', '', NULL, 1),
(34, 'Cable HDMI 1.8m', 'Mallado, 4K', 'CABLE-HDMI-1.8M', 3, 'INF-CAB-HDMI', 3, 'unidades', 20, 'Rack I-2', '', NULL, 1),
(35, 'Cable de Red UTP Cat 5e 2m', 'Patch cord, azul', 'CABLE-RED-2M', 3, 'INF-CAB-RED2M', 3, 'unidades', 40, 'Rack I-2', '', NULL, 1),
(36, 'Cable de Red UTP Cat 5e 5m', 'Patch cord, gris', 'CABLE-RED-5M', 3, 'INF-CAB-RED5M', 3, 'unidades', 20, 'Rack I-2', '', NULL, 1),
(37, 'Disco Sólido SSD 240GB', 'Kingston A400', 'SSD-240GB', 3, 'INF-SSD-240', 4, 'unidades', 10, 'Rack I-3', 'Actualización de equipos', NULL, 1),
(38, 'Disco Sólido SSD 480GB', 'Kingston A400', 'SSD-480GB', 3, 'INF-SSD-480', 4, 'unidades', 5, 'Rack I-3', '', NULL, 1),
(39, 'Memoria RAM 8GB DDR4 2666Mhz', 'Kingston Notebook', 'RAM-8GB-DDR4-NB', 3, 'INF-RAM-8D4N', 4, 'unidades', 10, 'Rack I-3', '', NULL, 1),
(40, 'Memoria RAM 8GB DDR4 3200Mhz', 'Kingston PC', 'RAM-8GB-DDR4-PC', 3, 'INF-RAM-8D4P', 4, 'unidades', 10, 'Rack I-3', '', NULL, 1),
(41, 'Destornillador Phillips', 'Mediano, punta imantada', 'DEST-PHILLIPS', 5, 'HER-DES-PH', 6, 'unidades', 15, 'Taller-P1', '', NULL, 1),
(42, 'Destornillador Plano', 'Mediano', 'DEST-PLANO', 5, 'HER-DES-PL', 6, 'unidades', 15, 'Taller-P1', '', NULL, 1),
(43, 'Pinza Universal 7"', 'Mango aislado', 'PINZA-UNI-7', 5, 'HER-PIN-UNI', 6, 'unidades', 5, 'Taller-P1', '', NULL, 1),
(44, 'Martillo de Uña 20oz', 'Mango de madera', 'MARTILLO-UNA', 5, 'HER-MART-UNA', 6, 'unidades', 3, 'Taller-P2', '', NULL, 1),
(45, 'Cinta Aisladora Negra 10m', 'PVC', 'CINTA-AISL-NEG', 5, 'HER-CINTA-A', 6, 'unidades', 30, 'Taller-P2', '', NULL, 1),
(46, 'Foco LED 12W Luz Fría', 'E27', 'FOCO-LED-12W', 5, 'HER-FOCO-12F', 7, 'unidades', 50, 'Taller-L1', '', NULL, 1),
(47, 'Tubo LED 18W 120cm', 'Luz Fría, SICA', 'TUBO-LED-18W', 5, 'HER-TUBO-18F', 7, 'unidades', 30, 'Taller-L1', '', NULL, 1),
(48, 'Café Molido La Virginia 500g', 'Paquete', 'CAFE-500G', 6, 'CAF-CAF-500', 5, 'paquetes', 20, 'Cocina-C1', '', NULL, 1),
(49, 'Yerba Mate Playadito 1kg', 'Paquete', 'YERBA-1KG', 6, 'CAF-YERBA-1K', 5, 'paquetes', 30, 'Cocina-C1', '', NULL, 1),
(50, 'Azúcar Ledesma 1kg', 'Paquete', 'AZUCAR-1KG', 6, 'CAF-AZU-1K', 5, 'paquetes', 40, 'Cocina-C2', '', NULL, 1),
(51, 'Edulcorante Hileret 250ml', 'Líquido', 'EDULCORANTE-250ML', 6, 'CAF-EDU-250', 5, 'unidades', 15, 'Cocina-C2', '', NULL, 1),
(52, 'Silla de Oficina Ergonómica', 'Negra, con ruedas', 'SILLA-OFICINA-ERG', 7, 'MOB-SILLA-ERG', 8, 'unidades', 5, 'Pasillo Mob', '', NULL, 1),
(53, 'Escritorio 1.20m Melamina (Muebles SRL)', 'Melamina blanca', 'ESCRITORIO-1.20M', 7, 'MOB-ESCR-120', 8, 'unidades', 3, 'Pasillo Mob', '', NULL, 1),
(54, 'Escritorio de madera negro (Pepito)', 'Escritorio pintado de negro 1.20m', 'ESCRITORIO-1.20M', 7, '779000000001A', 9, 'unidades', 0, 'Depo Pepito', 'Comprado a Pepito', NULL, 1),
(55, 'Escritorio de madera negro (Menchito)', 'Escritorio pintado de negro 1.20m, otra marca', 'ESCRITORIO-1.20M', 7, '779000000002B', 10, 'unidades', 0, 'Depo Menchito', 'Comprado a Menchito', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--
DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `insumo_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','AJUSTE') NOT NULL,
  `cantidad_movida` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `recibo_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `anulado_por_id` int(11) DEFAULT NULL,
  `numero_lote` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_efectiva` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('EFECTIVO','PROGRAMADO') NOT NULL DEFAULT 'EFECTIVO',
  PRIMARY KEY (`id`),
  KEY `insumo_id` (`insumo_id`),
  KEY `deposito_id` (`deposito_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `fk_movimiento_usuario` (`usuario_id`),
  KEY `idx_estado_fecha` (`estado`,`fecha_efectiva`),
  KEY `fk_movimiento_anulador` (`anulado_por_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos`
--
INSERT INTO `movimientos` (`id`, `fecha`, `insumo_id`, `deposito_id`, `tipo_movimiento`, `cantidad_movida`, `observaciones`, `categoria_id`, `recibo_path`, `usuario_id`, `anulado_por_id`, `numero_lote`, `fecha_vencimiento`, `fecha_efectiva`, `estado`) VALUES
(1, '2025-10-20 13:00:00', 1, 1, 'ENTRADA', 1000, 'Compra inicial', 1, NULL, 1, NULL, 'LOTE-PAPEL-001', NULL, '2025-10-20 13:00:00', 'EFECTIVO'),
(2, '2025-10-20 13:01:00', 3, 1, 'ENTRADA', 50, 'Compra inicial', 1, NULL, 1, NULL, 'LOTE-BIC-001', NULL, '2025-10-20 13:01:00', 'EFECTIVO'),
(3, '2025-10-20 13:02:00', 15, 2, 'ENTRADA', 20, 'Compra para Informática', 4, NULL, 2, NULL, 'LOTE-HP85A-001', '2026-10-20', '2025-10-20 13:02:00', 'EFECTIVO'),
(4, '2025-10-20 13:03:00', 30, 2, 'ENTRADA', 50, 'Stock para recambio', 3, NULL, 2, NULL, 'LOTE-MOUSE-001', NULL, '2025-10-20 13:03:00', 'EFECTIVO'),
(5, '2025-10-20 13:04:00', 31, 2, 'ENTRADA', 50, 'Stock para recambio', 3, NULL, 2, NULL, 'LOTE-TECLADO-001', NULL, '2025-10-20 13:04:00', 'EFECTIVO'),
(6, '2025-10-20 13:05:00', 21, 1, 'ENTRADA', 100, 'Stock Limpieza', 2, NULL, 1, NULL, 'LOTE-LAVANDINA-001', '2026-04-20', '2025-10-20 13:05:00', 'EFECTIVO'),
(7, '2025-10-20 13:06:00', 23, 1, 'ENTRADA', 50, 'Stock Limpieza', 2, NULL, 1, NULL, 'LOTE-PAPELHIG-001', NULL, '2025-10-20 13:06:00', 'EFECTIVO'),
(8, '2025-10-20 13:07:00', 41, 3, 'ENTRADA', 20, 'Stock Mantenimiento', 5, NULL, 3, NULL, 'LOTE-HERRAM-001', NULL, '2025-10-20 13:07:00', 'EFECTIVO'),
(9, '2025-10-20 13:08:00', 46, 3, 'ENTRADA', 100, 'Stock Mantenimiento', 5, NULL, 3, NULL, 'LOTE-FOCOS-001', NULL, '2025-10-20 13:08:00', 'EFECTIVO'),
(10, '2025-10-21 12:00:00', 1, 4, 'ENTRADA', 50, 'Envío a Tesorería', 1, NULL, 4, NULL, 'LOTE-PAPEL-001-T', NULL, '2025-10-21 12:00:00', 'EFECTIVO'),
(11, '2025-10-21 12:01:00', 3, 4, 'ENTRADA', 10, 'Envío a Tesorería', 1, NULL, 4, NULL, 'LOTE-BIC-001-T', NULL, '2025-10-21 12:01:00', 'EFECTIVO'),
(12, '2025-10-21 14:00:00', 30, 2, 'SALIDA', 5, 'Recambio oficina 105', 3, NULL, 2, NULL, NULL, NULL, '2025-10-21 14:00:00', 'EFECTIVO'),
(13, '2025-10-21 14:01:00', 31, 2, 'SALIDA', 3, 'Recambio oficina 105', 3, NULL, 2, NULL, NULL, NULL, '2025-10-21 14:01:00', 'EFECTIVO'),
(14, '2025-10-22 11:30:00', 49, 1, 'ENTRADA', 50, 'Stock cafetería', 6, NULL, 1, NULL, 'LOTE-YERBA-001', '2026-11-22', '2025-10-22 11:30:00', 'EFECTIVO'),
(15, '2025-10-22 11:31:00', 50, 1, 'ENTRADA', 100, 'Stock cafetería', 6, NULL, 1, NULL, 'LOTE-AZUCAR-001', NULL, '2025-10-22 11:31:00', 'EFECTIVO'),
(16, '2025-10-23 13:00:00', 46, 3, 'SALIDA', 10, 'Recambio luces Pasillo Sur', 5, NULL, 3, NULL, NULL, NULL, '2025-10-23 13:00:00', 'EFECTIVO'),
(17, '2025-10-24 14:00:00', 15, 2, 'SALIDA', 2, 'Impresora Contable', 4, NULL, 2, NULL, NULL, NULL, '2025-10-24 14:00:00', 'EFECTIVO'),
(18, '2025-10-25 15:00:00', 1, 1, 'SALIDA', 200, 'Entrega a Comisiones', 1, NULL, 1, NULL, NULL, NULL, '2025-10-25 15:00:00', 'EFECTIVO'),
(19, '2025-10-25 15:00:00', 10, 1, 'ENTRADA', 30, 'Compra carpetas', 1, NULL, 1, NULL, 'LOTE-CARPETAS-001', NULL, '2025-10-25 15:00:00', 'EFECTIVO'),
(20, '2025-10-26 13:00:00', 52, 1, 'ENTRADA', 10, 'Compra Sillas', 7, NULL, 1, NULL, 'LOTE-SILLAS-001', NULL, '2025-10-26 13:00:00', 'EFECTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--
DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `telefono` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--
INSERT INTO `proveedores` (`id`, `nombre`, `contacto`, `telefono`, `email`, `direccion`, `activo`) VALUES
(1, 'Librería El Estudiante', 'Raúl Gómez', '3794-111111', 'ventas@estudiante.com', 'Junín 1234', 1),
(2, 'Proveedor Oficina', 'Marta Diaz', '3794-222222', 'info@proveedoroficina.com', 'Av. 3 de Abril 500', 1),
(3, 'Insumos Corrientes', 'Carlos Vera', '3794-333333', 'admin@insumoscorrientes.com', 'Salta 850', 1),
(4, 'Tecno Global', 'Ana María', '3794-444444', 'soporte@tecnoglobal.com', '9 de Julio 1500', 1),
(5, 'Distribuidora Limpieza', 'Horacio', '3794-555555', 'pedidos@distrilimpieza.com', 'Av. Maipú 2500', 1),
(6, 'Ferretería El Martillo', 'Jorge', '3794-666666', 'ferreteria@martillo.com', 'Av. Independencia 4000', 1),
(7, 'Electricidad Corrientes', 'Antonio', '3794-777777', 'luz@electricidadcorrientes.com', 'Moreno 900', 1),
(8, 'Muebles de Oficina SRL', 'Sra. Laura', '3794-888888', 'ventas@mueblessrl.com', 'Ruta 12 km 1024', 1),
(9, 'Pepito', 'José Perez', '3794-999999', 'pepito@mail.com', 'Calle Falsa 123', 1),
(10, 'Menchito', 'Roberto González', '3794-000000', 'menchito@mail.com', 'Av. Siempre Viva 742', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock`
--
DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `insumo_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `insumo_deposito` (`insumo_id`,`deposito_id`),
  KEY `deposito_id` (`deposito_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `stock`
--
INSERT INTO `stock` (`id`, `insumo_id`, `deposito_id`, `cantidad`, `fecha_actualizacion`) VALUES
(1, 1, 1, 800, '2025-10-25 18:00:00'),
(2, 3, 1, 50, '2025-10-20 16:01:00'),
(3, 15, 2, 18, '2025-10-24 17:00:00'),
(4, 30, 2, 45, '2025-10-21 17:00:00'),
(5, 31, 2, 47, '2025-10-21 17:01:00'),
(6, 21, 1, 100, '2025-10-20 16:05:00'),
(7, 23, 1, 50, '2025-10-20 16:06:00'),
(8, 41, 3, 20, '2025-10-20 16:07:00'),
(9, 46, 3, 90, '2025-10-23 16:00:00'),
(10, 1, 4, 50, '2025-10-21 15:00:00'),
(11, 3, 4, 10, '2025-10-21 15:01:00'),
(12, 49, 1, 50, '2025-10-22 14:30:00'),
(13, 50, 1, 100, '2025-10-22 14:31:00'),
(14, 10, 1, 30, '2025-10-25 18:00:00'),
(15, 52, 1, 10, '2025-10-26 16:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_lotes`
--
DROP TABLE IF EXISTS `stock_lotes`;
CREATE TABLE `stock_lotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `insumo_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `numero_lote` varchar(100) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_ingresada` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `movimiento_id_ingreso` int(11) NOT NULL,
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_movimiento_id` (`movimiento_id_ingreso`),
  KEY `idx_insumo_deposito` (`insumo_id`,`deposito_id`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `stock_lotes`
--
INSERT INTO `stock_lotes` (`id`, `insumo_id`, `deposito_id`, `numero_lote`, `fecha_vencimiento`, `cantidad_ingresada`, `cantidad_actual`, `movimiento_id_ingreso`, `fecha_ingreso`) VALUES
(1, 1, 1, 'LOTE-PAPEL-001', NULL, 1000, 1000, 1, '2025-10-20 16:00:00'),
(2, 3, 1, 'LOTE-BIC-001', NULL, 50, 50, 2, '2025-10-20 16:01:00'),
(3, 15, 2, 'LOTE-HP85A-001', '2026-10-20', 20, 20, 3, '2025-10-20 16:02:00'),
(4, 30, 2, 'LOTE-MOUSE-001', NULL, 50, 50, 4, '2025-10-20 16:03:00'),
(5, 31, 2, 'LOTE-TECLADO-001', NULL, 50, 50, 5, '2025-10-20 16:04:00'),
(6, 21, 1, 'LOTE-LAVANDINA-001', '2026-04-20', 100, 100, 6, '2025-10-20 16:05:00'),
(7, 23, 1, 'LOTE-PAPELHIG-001', NULL, 50, 50, 7, '2025-10-20 16:06:00'),
(8, 41, 3, 'LOTE-HERRAM-001', NULL, 20, 20, 8, '2025-10-20 16:07:00'),
(9, 46, 3, 'LOTE-FOCOS-001', NULL, 100, 100, 9, '2025-10-20 16:08:00'),
(10, 1, 4, 'LOTE-PAPEL-001-T', NULL, 50, 50, 10, '2025-10-21 15:00:00'),
(11, 3, 4, 'LOTE-BIC-001-T', NULL, 10, 10, 11, '2025-10-21 15:01:00'),
(12, 49, 1, 'LOTE-YERBA-001', '2026-11-22', 50, 50, 14, '2025-10-22 14:30:00'),
(13, 50, 1, 'LOTE-AZUCAR-001', NULL, 100, 100, 15, '2025-10-22 14:31:00'),
(14, 10, 1, 'LOTE-CARPETAS-001', NULL, 30, 30, 19, '2025-10-25 18:00:00'),
(15, 52, 1, 'LOTE-SILLAS-001', NULL, 10, 10, 20, '2025-10-26 16:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','operador','supervisor','observador') NOT NULL DEFAULT 'operador',
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(100) DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultima_conexion` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `creado_por_admin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_usuario_creador` (`creado_por_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--
INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `rol`, `email`, `telefono`, `domicilio`, `activo`, `ultima_conexion`, `fecha_creacion`, `creado_por_admin_id`) VALUES
(1, 'admin', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'admin', 'admin@sistema.com', '111', 'Oficina 1', 1, NULL, '2025-11-15 18:00:00', NULL),
(2, 'operador_inf', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'operador', 'info@sistema.com', '222', 'Oficina 2', 1, NULL, '2025-11-15 18:01:00', 1),
(3, 'operador_mant', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'operador', 'mant@sistema.com', '333', 'Oficina 3', 1, NULL, '2025-11-15 18:01:00', 1),
(4, 'operador_econ', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'operador', 'econ@sistema.com', '444', 'Oficina 4', 1, NULL, '2025-11-15 18:01:00', 1),
(5, 'supervisor_uno', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'supervisor', 'super@sistema.com', '555', 'Oficina 5', 1, NULL, '2025-11-15 18:02:00', 1),
(6, 'observador_uno', '$2y$10$TebivP.q.CP.s42uEdp4buF6v2n.f.J/oVnwc.biT0OAF8s1.6J0O', 'observador', 'obs@sistema.com', '666', 'Oficina 6', 1, NULL, '2025-11-15 18:02:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_deposito_link`
--
DROP TABLE IF EXISTS `usuario_deposito_link`;
CREATE TABLE `usuario_deposito_link` (
  `usuario_id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`deposito_id`),
  KEY `fk_udl_deposito` (`deposito_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_deposito_link`
--
INSERT INTO `usuario_deposito_link` (`usuario_id`, `deposito_id`) VALUES
(4, 1),
(5, 1),
(2, 2),
(5, 2),
(3, 3),
(5, 3),
(4, 4),
(5, 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria_consultas_stock`
--
ALTER TABLE `auditoria_consultas_stock`
  ADD CONSTRAINT `fk_auditoria_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_auditoria_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `deposito_categoria_link`
--
ALTER TABLE `deposito_categoria_link`
  ADD CONSTRAINT `fk_dcl_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dcl_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `fk_insumo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insumo_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `fk_movimiento_anulador` FOREIGN KEY (`anulado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `fk_stock_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `stock_lotes`
--
ALTER TABLE `stock_lotes`
  ADD CONSTRAINT `fk_lote_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lote_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lote_movimiento` FOREIGN KEY (`movimiento_id_ingreso`) REFERENCES `movimientos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_creador` FOREIGN KEY (`creado_por_admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_deposito_link`
--
ALTER TABLE `usuario_deposito_link`
  ADD CONSTRAINT `fk_udl_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_udl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;