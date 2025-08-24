-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql
-- Tiempo de generación: 24-08-2025 a las 18:32:21
-- Versión del servidor: 8.0.42
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `app_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `IdProducto` varchar(50) NOT NULL,
  `NomProducto` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `TipoProducto` varchar(100) NOT NULL,
  `Stock` int NOT NULL,
  `Precio` decimal(5,2) NOT NULL,
  `Precio_Unitario` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`IdProducto`, `NomProducto`, `Marca`, `TipoProducto`, `Stock`, `Precio`, `Precio_Unitario`) VALUES
('0001', 'Bolígrafo', 'Bic', 'Utiles escolares', 0, 10.62, 3.20),
('0002', 'Caja de Papel Bond', 'PaperLine', 'Papelería', 0, 52.34, 5.10),
('0003', 'adsa', 'ada', 'ddd', 0, 22.30, 2.30),
('ART001', 'Acrílico Profesional 60ml', 'Winsor & Newton', 'Pintura', 0, 15.50, 15.50),
('ART002', 'Pincel Redondo #8', 'Princeton', 'Herramienta', 0, 8.75, 8.75),
('ART003', 'Lienzo 30x40cm', 'Artmate', 'Soporte', 0, 12.00, 12.00),
('ESC001', 'Lápiz HB', 'Faber-Castell', 'Escritura', 0, 1.25, 1.25),
('ESC002', 'Cuaderno 100 hojas', 'Norma', 'Cuaderno', 0, 3.50, 3.50),
('ESC003', 'Regla 30cm', 'Maped', 'Geometría', 0, 2.75, 2.75),
('OFC001', 'Carpeta Manila', 'Acco', 'Archivo', 0, 1.50, 1.50),
('OFC002', 'Grapadora Pequeña', 'Swingline', 'Herramienta', 0, 8.50, 8.50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Categoria`
--

CREATE TABLE `Categoria` (
  `Id` varchar(100) NOT NULL,
  `Nombre_Categoria` varchar(100) NOT NULL,
  `Descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `IdCategoriaPadre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `Categoria`
--

INSERT INTO `Categoria` (`Id`, `Nombre_Categoria`, `Descripcion`, `IdCategoriaPadre`) VALUES
('ARTE', 'Arte', 'Productos para arte y manualidades', NULL),
('ARTE_HERRAMIENTAS', 'Herramientas de Arte', 'Pinceles, espátulas, paletas', 'ARTE'),
('ARTE_LIENZOS', 'Lienzos y Soportes', 'Lienzos, bastidores, papeles especiales', 'ARTE'),
('ARTE_PINTURAS', 'Pinturas', 'Acrílicos, témperas, acuarelas, óleos', 'ARTE'),
('ESCOLAR', 'Escolar', 'Útiles y materiales escolares', NULL),
('ESCOLAR_CUADERNOS', 'Cuadernos y Libretas', 'Cuadernos, libretas, agendas', 'ESCOLAR'),
('ESCOLAR_ESCRITURA', 'Escritura', 'Lápices, lapiceros, borradores', 'ESCOLAR'),
('ESCOLAR_GEOMETRIA', 'Geometría', 'Reglas, compás, escuadras, transportador', 'ESCOLAR'),
('INFANTIL', 'Infantil', 'Productos especializados para niños', NULL),
('LIBROS', 'Libros', 'Literatura y textos educativos', NULL),
('LIBROS_ESCOLARES', 'Textos Escolares', 'Libros de texto y educativos', 'LIBROS'),
('LIBROS_INFANTILES', 'Cuentos y Fábulas', 'Literatura infantil', 'LIBROS'),
('LIBROS_LITERATURA', 'Literatura Juvenil', 'Novelas y literatura para jóvenes', 'LIBROS'),
('OFICINA', 'Oficina', 'Artículos de oficina y trabajo', NULL),
('OFICINA_ARCHIVO', 'Archivo', 'Carpetas, archivadores, organizadores', 'OFICINA'),
('OFICINA_HERRAMIENTAS', 'Herramientas', 'Grapadoras, perforadoras, clips', 'OFICINA'),
('PAPELES_DERIVADOS', 'Papeles y Derivados', 'Todo tipo de papeles y productos derivados', NULL),
('REGALOS', 'Regalos y Accesorios', 'Artículos de regalo y decorativos', NULL),
('TECNOLOGIA', 'Tecnología', 'Artículos tecnológicos básicos', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `IdDetalle` int NOT NULL,
  `IdVenta` varchar(100) NOT NULL,
  `IdProducto` varchar(100) NOT NULL,
  `Cantidad` int NOT NULL,
  `PrecioUnitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `flyway_schema_history`
--

CREATE TABLE `flyway_schema_history` (
  `installed_rank` int NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `description` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `script` varchar(1000) NOT NULL,
  `checksum` int DEFAULT NULL,
  `installed_by` varchar(100) NOT NULL,
  `installed_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `execution_time` int NOT NULL,
  `success` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `flyway_schema_history`
--

INSERT INTO `flyway_schema_history` (`installed_rank`, `version`, `description`, `type`, `script`, `checksum`, `installed_by`, `installed_on`, `execution_time`, `success`) VALUES
(1, '1', 'crear tablas', 'SQL', 'V1__crear_tablas.sql', -1350077460, 'app_user', '2025-08-19 03:08:08', 515, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `img`
--

CREATE TABLE `img` (
  `idProd` varchar(50) NOT NULL,
  `ruta` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `img`
--

INSERT INTO `img` (`idProd`, `ruta`) VALUES
('0001', 'img/BoligrafoBic.webp'),
('0002', 'img/CajadePapelBond.webp'),
('0003', 'img/cuaderno1.webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_categoria`
--

CREATE TABLE `producto_categoria` (
  `Id_Producto` varchar(100) NOT NULL,
  `Id_Categoria` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `producto_categoria`
--

INSERT INTO `producto_categoria` (`Id_Producto`, `Id_Categoria`) VALUES
('ART001', 'ARTE_PINTURAS'),
('ART002', 'ARTE_HERRAMIENTAS'),
('ART003', 'ARTE_LIENZOS'),
('ESC001', 'ESCOLAR_ESCRITURA'),
('ESC002', 'ESCOLAR_CUADERNOS'),
('ESC003', 'ESCOLAR_GEOMETRIA'),
('OFC001', 'OFICINA_ARCHIVO'),
('OFC002', 'OFICINA_HERRAMIENTAS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `IdUsuario` varchar(100) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Telefono` varchar(20) NOT NULL,
  `Direccion` varchar(100) NOT NULL,
  `Usuario` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('cliente','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `IdVenta` varchar(100) NOT NULL,
  `IdUsuario` varchar(100) NOT NULL,
  `Fecha` datetime NOT NULL,
  `Total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`IdProducto`);

--
-- Indices de la tabla `Categoria`
--
ALTER TABLE `Categoria`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `IdCategoriaPadre` (`IdCategoriaPadre`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`IdDetalle`),
  ADD KEY `IdProducto` (`IdProducto`),
  ADD KEY `IdVenta` (`IdVenta`);

--
-- Indices de la tabla `flyway_schema_history`
--
ALTER TABLE `flyway_schema_history`
  ADD PRIMARY KEY (`installed_rank`),
  ADD KEY `flyway_schema_history_s_idx` (`success`);

--
-- Indices de la tabla `img`
--
ALTER TABLE `img`
  ADD KEY `idProd` (`idProd`);

--
-- Indices de la tabla `producto_categoria`
--
ALTER TABLE `producto_categoria`
  ADD KEY `Id_Producto` (`Id_Producto`),
  ADD KEY `Id_Categoria` (`Id_Categoria`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`IdUsuario`),
  ADD UNIQUE KEY `Correo` (`Correo`),
  ADD UNIQUE KEY `Usuario` (`Usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`IdVenta`),
  ADD KEY `IdUsuario` (`IdUsuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `IdDetalle` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Categoria`
--
ALTER TABLE `Categoria`
  ADD CONSTRAINT `Categoria_ibfk_1` FOREIGN KEY (`IdCategoriaPadre`) REFERENCES `Categoria` (`Id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`IdVenta`) REFERENCES `ventas` (`IdVenta`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`IdProducto`) REFERENCES `articulo` (`IdProducto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `img`
--
ALTER TABLE `img`
  ADD CONSTRAINT `img_ibfk_1` FOREIGN KEY (`idProd`) REFERENCES `articulo` (`IdProducto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `producto_categoria`
--
ALTER TABLE `producto_categoria`
  ADD CONSTRAINT `producto_categoria_ibfk_1` FOREIGN KEY (`Id_Categoria`) REFERENCES `Categoria` (`Id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `producto_categoria_ibfk_2` FOREIGN KEY (`Id_Producto`) REFERENCES `articulo` (`IdProducto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuarios` (`IdUsuario`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
