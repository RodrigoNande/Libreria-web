-- =============================================
-- R__datos_libreria.sql
-- Migración repeatable de datos para Flyway
-- =============================================

-- Limpiar datos anteriores
DELETE FROM producto_categoria;
DELETE FROM articulo;
DELETE FROM Categoria;

-- =============================================
-- INSERTAR CATEGORÍAS PRINCIPALES
-- =============================================
INSERT INTO Categoria (Id, Nombre_Categoria, Descripcion, IdCategoriaPadre) VALUES
('ARTE', 'Arte', 'Productos para arte y manualidades', NULL),
('PAPELES_DERIVADOS', 'Papeles y Derivados', 'Todo tipo de papeles y productos derivados', NULL),
('ESCOLAR', 'Escolar', 'Útiles y materiales escolares', NULL),
('OFICINA', 'Oficina', 'Artículos de oficina y trabajo', NULL),
('INFANTIL', 'Infantil', 'Productos especializados para niños', NULL),
('LIBROS', 'Libros', 'Literatura y textos educativos', NULL),
('TECNOLOGIA', 'Tecnología', 'Artículos tecnológicos básicos', NULL),
('REGALOS', 'Regalos y Accesorios', 'Artículos de regalo y decorativos', NULL);

-- Subcategorías ARTE
INSERT INTO Categoria (Id, Nombre_Categoria, Descripcion, IdCategoriaPadre) VALUES
('ARTE_PINTURAS', 'Pinturas', 'Acrílicos, témperas, acuarelas, óleos', 'ARTE'),
('ARTE_HERRAMIENTAS', 'Herramientas de Arte', 'Pinceles, espátulas, paletas', 'ARTE'),
('ARTE_LIENZOS', 'Lienzos y Soportes', 'Lienzos, bastidores, papeles especiales', 'ARTE');

-- Subcategorías ESCOLAR
INSERT INTO Categoria (Id, Nombre_Categoria, Descripcion, IdCategoriaPadre) VALUES
('ESCOLAR_ESCRITURA', 'Escritura', 'Lápices, lapiceros, borradores', 'ESCOLAR'),
('ESCOLAR_CUADERNOS', 'Cuadernos y Libretas', 'Cuadernos, libretas, agendas', 'ESCOLAR'),
('ESCOLAR_GEOMETRIA', 'Geometría', 'Reglas, compás, escuadras, transportador', 'ESCOLAR');

-- Subcategorías LIBROS
INSERT INTO Categoria (Id, Nombre_Categoria, Descripcion, IdCategoriaPadre) VALUES
('LIBROS_ESCOLARES', 'Textos Escolares', 'Libros de texto y educativos', 'LIBROS'),
('LIBROS_LITERATURA', 'Literatura Juvenil', 'Novelas y literatura para jóvenes', 'LIBROS'),
('LIBROS_INFANTILES', 'Cuentos y Fábulas', 'Literatura infantil', 'LIBROS');

-- Subcategorías OFICINA
INSERT INTO Categoria (Id, Nombre_Categoria, Descripcion, IdCategoriaPadre) VALUES
('OFICINA_ARCHIVO', 'Archivo', 'Carpetas, archivadores, organizadores', 'OFICINA'),
('OFICINA_HERRAMIENTAS', 'Herramientas', 'Grapadoras, perforadoras, clips', 'OFICINA');

-- =============================================
-- PRODUCTOS DE EJEMPLO
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ART001', 'Acrílico Profesional 60ml', 'Winsor & Newton', 'Pintura', 15.50, 15.50, 20),
('ART002', 'Pincel Redondo #8', 'Princeton', 'Herramienta', 8.75, 8.75, 4),
('ART003', 'Lienzos', 'Artmate', 'Soporte', 12.00, 12.00, 65),
('ESC001', 'Lápiz HB', 'Faber-Castell', 'Escritura', 1.25, 1.25, 86),
('ESC002', 'Cuaderno 100 hojas', 'Norma', 'Cuaderno', 3.50, 3.50, 23),
('ESC003', 'Regla 30cm', 'Maped', 'Geometría', 2.75, 2.75, 44),
('OFC001', 'Carpeta Manila', 'Acco', 'Archivo', 1.50, 1.50, 69),
('OFC002', 'Grapadora Pequeña', 'Swingline', 'Herramienta', 8.50, 8.50, 33);

-- =============================================
-- ASIGNAR PRODUCTOS A CATEGORÍAS
-- =============================================
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ART001', 'ARTE_PINTURAS'),
('ART002', 'ARTE_HERRAMIENTAS'),
('ART003', 'ARTE_LIENZOS'),
('ESC001', 'ESCOLAR_ESCRITURA'),
('ESC002', 'ESCOLAR_CUADERNOS'),
('ESC003', 'ESCOLAR_GEOMETRIA'),
('OFC001', 'OFICINA_ARCHIVO'),
('OFC002', 'OFICINA_HERRAMIENTAS');

INSERT INTO img (idProd, ruta) VALUES
("ART001", "img/acrilicoP.jpg"),
("ART002", "img/pincelPrinceton.webp"),
("ART003", "img/lienzoArtMate.jpg"),
("ESC001", "img/lapizFaber.jpg"),
("ESC002", "img/cuadernoNorma.webp"),
("ESC003", "img/reglaMaped.jpg"),
("OFC001", "img/carpetamanilaAmpo.jpg"),
("OFC002", "img/grapadoraSwingline.jpg"); 