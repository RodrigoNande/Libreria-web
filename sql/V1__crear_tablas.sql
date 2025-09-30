SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `app_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `app_db`;

-- Tabla articulo
CREATE TABLE `articulo` (
  `IdProducto` varchar(50) NOT NULL,
  `NomProducto` varchar(100) NOT NULL,
  `Marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `TipoProducto` varchar(100) NOT NULL,
  `Precio` decimal(5,2) NOT NULL,
  `Precio_Unitario` decimal(3,2) NOT NULL,
  PRIMARY KEY (`IdProducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla Categoria
CREATE TABLE `Categoria` (
  `Id` varchar(100) NOT NULL,
  `Nombre_Categoria` varchar(100) NOT NULL,
  `Descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla usuarios
CREATE TABLE `usuarios` (
  `IdUsuario` INT NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Telefono` varchar(20) NOT NULL,
  `Direccion` varchar(100) NOT NULL,
  `Usuario` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('cliente','admin') NOT NULL,
  PRIMARY KEY (`IdUsuario`),
  UNIQUE KEY `Correo` (`Correo`),
  UNIQUE KEY `Usuario` (`Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla producto_categoria
CREATE TABLE `producto_categoria` (
  `Id_Producto` varchar(100) NOT NULL,
  `Id_Categoria` varchar(100) NOT NULL,
  KEY `Id_Producto` (`Id_Producto`),
  KEY `Id_Categoria` (`Id_Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla ventas
CREATE TABLE `ventas` (
  `IdVenta` varchar(100) NOT NULL,
  `IdUsuario` INT NOT NULL,
  `Fecha` datetime NOT NULL,
  `Total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`IdVenta`),
  KEY `IdUsuario` (`IdUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla detalle_venta
CREATE TABLE `detalle_venta` (
  `IdDetalle` int NOT NULL AUTO_INCREMENT,
  `IdVenta` varchar(100) NOT NULL,
  `IdProducto` varchar(100) NOT NULL,
  `Cantidad` int NOT NULL,
  `PrecioUnitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`IdDetalle`),
  KEY `IdProducto` (`IdProducto`),
  KEY `IdVenta` (`IdVenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Foreign Keys
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`IdVenta`) REFERENCES `ventas` (`IdVenta`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`IdProducto`) REFERENCES `articulo` (`IdProducto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `producto_categoria`
  ADD CONSTRAINT `producto_categoria_ibfk_1` FOREIGN KEY (`Id_Categoria`) REFERENCES `Categoria` (`Id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `producto_categoria_ibfk_2` FOREIGN KEY (`Id_Producto`) REFERENCES `articulo` (`IdProducto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuarios` (`IdUsuario`) ON DELETE RESTRICT ON UPDATE RESTRICT;

COMMIT;