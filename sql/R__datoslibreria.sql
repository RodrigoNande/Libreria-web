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
-- =============================================
-- PRODUCTOS ADICIONALES PARA CATEGORÍAS EXISTENTES
-- NO MODIFICA LOS PRODUCTOS YA EXISTENTES (ART001-003, ESC001-003, OFC001-002)
-- =============================================

-- =============================================
-- CATEGORÍA: ARTE_PINTURAS (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ART_P001', 'Acrílico Azul Cobalto 60ml', 'Winsor & Newton', 'Pintura', 16.75, 16.75, 18),
('ART_P002', 'Témpera Roja Bermellón 250ml', 'Pelikan', 'Pintura', 9.25, 9.25, 22),
('ART_P003', 'Acuarela Verde Hooker 14ml', 'Cotman', 'Pintura', 5.50, 5.50, 35),
('ART_P004', 'Óleo Blanco Titanio 40ml', 'Van Gogh', 'Pintura', 13.80, 13.80, 25),
('ART_P005', 'Acrílico Violeta Dioxazina 120ml', 'Liquitex', 'Pintura', 19.50, 19.50, 12),
('ART_P006', 'Témpera Amarilla 500ml', 'Giotto', 'Pintura', 12.00, 12.00, 20),
('ART_P007', 'Acuarela Siena Tostada 14ml', 'Daniel Smith', 'Pintura', 7.75, 7.75, 28),
('ART_P008', 'Óleo Verde Esmeralda 60ml', 'Rembrandt', 'Pintura', 15.25, 15.25, 15),
('ART_P009', 'Acrílico Plateado Metálico 60ml', 'Golden', 'Pintura', 20.50, 20.50, 10),
('ART_P010', 'Set Témperas 12 colores', 'Crayola', 'Pintura', 18.75, 18.75, 30);

-- =============================================
-- CATEGORÍA: ARTE_HERRAMIENTAS (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ART_H001', 'Pincel Angular #10', 'Princeton', 'Herramienta', 11.50, 11.50, 25),
('ART_H002', 'Set Pinceles Acuarela 6 pcs', 'Royal Brush', 'Herramienta', 22.75, 22.75, 15),
('ART_H003', 'Paleta Plástica Ovalada', 'Artmate', 'Herramienta', 8.50, 8.50, 40),
('ART_H004', 'Espátula Diamante #4', 'RGM', 'Herramienta', 9.25, 9.25, 20),
('ART_H005', 'Pincel Lengua de Gato #12', 'Escoda', 'Herramienta', 14.80, 14.80, 18),
('ART_H006', 'Rodillo Textura 15cm', 'Speedball', 'Herramienta', 12.50, 12.50, 22),
('ART_H007', 'Paleta Cristal Rectangular', 'Strathmore', 'Herramienta', 18.75, 18.75, 12),
('ART_H008', 'Cuchillo Paleta #6', 'Winsor & Newton', 'Herramienta', 13.25, 13.25, 28),
('ART_H009', 'Pincel Detalle #2', 'Da Vinci', 'Herramienta', 8.00, 8.00, 35),
('ART_H010', 'Limpiador Pinceles 250ml', 'Natural Touch', 'Herramienta', 6.75, 6.75, 45);

-- =============================================
-- CATEGORÍA: ARTE_LIENZOS (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ART_L001', 'Lienzo 30x40cm Preparado', 'Artmate', 'Soporte', 12.50, 12.50, 35),
('ART_L002', 'Bastidor 30x40cm Pino', 'Phoenix', 'Soporte', 8.75, 8.75, 28),
('ART_L003', 'Papel Acuarela A3 300g', 'Fabriano', 'Soporte', 18.00, 18.00, 25),
('ART_L004', 'Canvas Board 25x35cm', 'Fredrix', 'Soporte', 6.25, 6.25, 50),
('ART_L005', 'Cartón Entelado A4', 'Canson', 'Soporte', 4.50, 4.50, 60),
('ART_L006', 'Block Óleo A4 10 hojas', 'Arches', 'Soporte', 15.75, 15.75, 20),
('ART_L007', 'Lienzo Redondo 30cm', 'Arteza', 'Soporte', 14.00, 14.00, 18),
('ART_L008', 'Papel Mixta Media A3', 'Strathmore', 'Soporte', 12.50, 12.50, 30),
('ART_L009', 'Tablilla Entelada 20x30cm', 'MasterBoard', 'Soporte', 7.75, 7.75, 40),
('ART_L010', 'Papel Pastel Gris A2', 'Sadipal', 'Soporte', 9.50, 9.50, 35);

-- =============================================
-- CATEGORÍA: ESCOLAR_ESCRITURA (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ESC_E001', 'Lápiz #2 Amarillo', 'Faber-Castell', 'Escritura', 1.75, 1.75, 80),
('ESC_E002', 'Lapicero Negro BIC', 'BIC', 'Escritura', 0.85, 0.85, 120),
('ESC_E003', 'Borrador Rosa Milan', 'Milan', 'Escritura', 0.95, 0.95, 70),
('ESC_E004', 'Colores Triangulares 24 pcs', 'Prismacolor', 'Escritura', 25.50, 25.50, 20),
('ESC_E005', 'Marcador Punta Fina Azul', 'Sharpie', 'Escritura', 2.75, 2.75, 50),
('ESC_E006', 'Corrector Cinta 5mm', 'Paper Mate', 'Escritura', 4.25, 4.25, 35),
('ESC_E007', 'Portaminas 0.7mm', 'Parker', 'Escritura', 6.50, 6.50, 25),
('ESC_E008', 'Set Resaltadores 4 colores', 'Stabilo', 'Escritura', 8.75, 8.75, 30),
('ESC_E009', 'Minas 0.5mm HB', 'Pentel', 'Escritura', 2.50, 2.50, 60),
('ESC_E010', 'Sacapuntas Metálico Doble', 'Staedtler', 'Escritura', 3.25, 3.25, 45);

-- =============================================
-- CATEGORÍA: ESCOLAR_CUADERNOS (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ESC_C001', 'Cuaderno Cuadriculado 200h', 'Norma', 'Cuaderno', 5.25, 5.25, 40),
('ESC_C002', 'Libreta Pautada A4', 'Oxford', 'Cuaderno', 4.75, 4.75, 35),
('ESC_C003', 'Agenda Diaria 2025', 'Miquelrius', 'Cuaderno', 12.50, 12.50, 20),
('ESC_C004', 'Cuaderno Anillado A5', 'Rhodia', 'Cuaderno', 8.00, 8.00, 30),
('ESC_C005', 'Block Cartulina A4', 'Canson', 'Cuaderno', 6.75, 6.75, 25),
('ESC_C006', 'Libreta Puntos A5', 'Moleskine', 'Cuaderno', 15.50, 15.50, 12),
('ESC_C007', 'Cuaderno Tapa Blanda 80h', 'Scribe', 'Cuaderno', 3.25, 3.25, 50),
('ESC_C008', 'Planificador Mensual', 'Quo Vadis', 'Cuaderno', 9.75, 9.75, 25),
('ESC_C009', 'Notas Autoadhesivas Colores', 'Post-it', 'Cuaderno', 5.50, 5.50, 60),
('ESC_C010', 'Bitácora Proyectos A4', 'Leuchtturm', 'Cuaderno', 18.75, 18.75, 15);

-- =============================================
-- CATEGORÍA: ESCOLAR_GEOMETRIA (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('ESC_G001', 'Regla 40cm Aluminio', 'Maped', 'Geometría', 4.50, 4.50, 35),
('ESC_G002', 'Compás Escolar Plástico', 'Staedtler', 'Geometría', 6.75, 6.75, 30),
('ESC_G003', 'Escuadra 30-60° 20cm', 'Faber-Castell', 'Geometría', 3.75, 3.75, 40),
('ESC_G004', 'Transportador 360° Completo', 'Milan', 'Geometría', 5.25, 5.25, 25),
('ESC_G005', 'Kit Geometría Estuche', 'Maped', 'Geometría', 15.00, 15.00, 18),
('ESC_G006', 'Regla Triangular Arquitecto', 'Westcott', 'Geometría', 12.50, 12.50, 20),
('ESC_G007', 'Compás Bigotera Profesional', 'Rotring', 'Geometría', 25.00, 25.00, 10),
('ESC_G008', 'Plantilla Curvas Francesas', 'Staedtler', 'Geometría', 8.75, 8.75, 22),
('ESC_G009', 'Goniómetro Circular', 'BIC', 'Geometría', 6.50, 6.50, 28),
('ESC_G010', 'Set Plantillas Geométricas', 'Maped', 'Geometría', 9.25, 9.25, 35);

-- =============================================
-- CATEGORÍA: LIBROS_ESCOLARES (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('LIB_E001', 'Matemáticas 7mo Grado', 'Santillana', 'Libro', 26.50, 26.50, 25),
('LIB_E002', 'Ciencias Naturales 6to', 'McGraw Hill', 'Libro', 24.75, 24.75, 20),
('LIB_E003', 'Estudios Sociales 5to', 'Pearson', 'Libro', 23.00, 23.00, 22),
('LIB_E004', 'Inglés Intermedio Level 2', 'Cambridge', 'Libro', 34.50, 34.50, 15),
('LIB_E005', 'Lenguaje 8vo Grado', 'Anaya', 'Libro', 25.25, 25.25, 18),
('LIB_E006', 'Física 1ro Bachillerato', 'Oxford', 'Libro', 32.75, 32.75, 12),
('LIB_E007', 'Química Orgánica 2do Bach', 'Edebé', 'Libro', 38.00, 38.00, 10),
('LIB_E008', 'Historia de El Salvador', 'SM', 'Libro', 28.25, 28.25, 16),
('LIB_E009', 'Atlas El Salvador', 'RAE', 'Libro', 22.50, 22.50, 20),
('LIB_E010', 'Diccionario Inglés-Español', 'Larousse', 'Libro', 19.75, 19.75, 25);

-- =============================================
-- CATEGORÍA: LIBROS_LITERATURA (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('LIB_L001', 'Cien Años de Soledad', 'Sudamericana', 'Libro', 18.50, 18.50, 30),
('LIB_L002', 'El Alquimista', 'Planeta', 'Libro', 16.25, 16.25, 35),
('LIB_L003', 'Orgullo y Prejuicio', 'Alianza', 'Libro', 14.75, 14.75, 25),
('LIB_L004', '1984 George Orwell', 'Debolsillo', 'Libro', 13.50, 13.50, 40),
('LIB_L005', 'Crónica de una Muerte Anunciada', 'Diana', 'Libro', 15.25, 15.25, 28),
('LIB_L006', 'El Túnel', 'Seix Barral', 'Libro', 12.00, 12.00, 32),
('LIB_L007', 'Rayuela', 'Alfaguara', 'Libro', 19.75, 19.75, 20),
('LIB_L008', 'La Casa de los Espíritus', 'Plaza & Janés', 'Libro', 17.50, 17.50, 24),
('LIB_L009', 'Pedro Páramo', 'RM', 'Libro', 11.25, 11.25, 38),
('LIB_L010', 'Ficciones', 'Emecé', 'Libro', 16.75, 16.75, 26);

-- =============================================
-- CATEGORÍA: LIBROS_INFANTILES (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('LIB_I001', 'El Gato con Botas', 'Juventud', 'Libro', 8.50, 8.50, 45),
('LIB_I002', 'La Liebre y la Tortuga', 'Everest', 'Libro', 7.75, 7.75, 50),
('LIB_I003', 'Hansel y Gretel', 'Susaeta', 'Libro', 9.25, 9.25, 40),
('LIB_I004', 'Los Tres Cochinitos Pop-Up', 'Combel', 'Libro', 12.50, 12.50, 30),
('LIB_I005', 'Rapunzel Ilustrado', 'La Galera', 'Libro', 10.75, 10.75, 35),
('LIB_I006', 'La Sirenita Musical', 'Timun Mas', 'Libro', 15.25, 15.25, 25),
('LIB_I007', 'Bambi Clásico Disney', 'Anaya', 'Libro', 11.50, 11.50, 38),
('LIB_I008', 'Pulgarcita Troquelado', 'Bruño', 'Libro', 9.75, 9.75, 42),
('LIB_I009', 'El Soldadito de Plomo', 'SM', 'Libro', 8.25, 8.25, 48),
('LIB_I010', 'Alicia en el País Maravillas', 'Edelvives', 'Libro', 13.50, 13.50, 28);

-- =============================================
-- CATEGORÍA: OFICINA_ARCHIVO (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('OFC_A001', 'Folder Colgante Legal', 'Acco', 'Archivo', 2.85, 2.85, 60),
('OFC_A002', 'Archivador Palanca Oficio', 'Elba', 'Archivo', 12.50, 12.50, 25),
('OFC_A003', 'Carpeta 3 Anillos A4', 'Leitz', 'Archivo', 8.75, 8.75, 35),
('OFC_A004', 'Caja Archivo Definitivo', 'Bankers Box', 'Archivo', 15.25, 15.25, 20),
('OFC_A005', 'Separadores Alfabéticos A-Z', 'Avery', 'Archivo', 5.50, 5.50, 40),
('OFC_A006', 'Portadocumentos con Cierre', 'Samsonite', 'Archivo', 22.75, 22.75, 15),
('OFC_A007', 'Sobre Manila Reforzado', 'Pendaflex', 'Archivo', 1.25, 1.25, 80),
('OFC_A008', 'Organizador Mesa 6 divisiones', 'Rubbermaid', 'Archivo', 18.50, 18.50, 18),
('OFC_A009', 'Carpeta Fuelle 12 divisiones', 'Fast', 'Archivo', 9.75, 9.75, 30),
('OFC_A010', 'Etiquetas Autoadhesivas', 'Quality Park', 'Archivo', 3.25, 3.25, 70);

-- =============================================
-- CATEGORÍA: OFICINA_HERRAMIENTAS (10 productos adicionales)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('OFC_H001', 'Grapadora Heavy Duty', 'Swingline', 'Herramienta', 18.75, 18.75, 20),
('OFC_H002', 'Perforadora 3 Agujeros', 'Bostitch', 'Herramienta', 16.50, 16.50, 18),
('OFC_H003', 'Clips Jumbo #4', 'ACCO', 'Herramienta', 2.50, 2.50, 90),
('OFC_H004', 'Dispensador Tape Pesado', '3M', 'Herramienta', 12.25, 12.25, 25),
('OFC_H005', 'Tijeras Titanio 9"', 'Fiskars', 'Herramienta', 14.75, 14.75, 22),
('OFC_H006', 'Calculadora Científica', 'Casio', 'Herramienta', 28.50, 28.50, 15),
('OFC_H007', 'Plastificadora A4', 'Dahle', 'Herramienta', 45.00, 45.00, 8),
('OFC_H008', 'Engrapadora Eléctrica', 'Stanley', 'Herramienta', 35.75, 35.75, 12),
('OFC_H009', 'Guillotina Papel A3', 'Olfa', 'Herramienta', 28.25, 28.25, 10),
('OFC_H010', 'Numerador 6 Dígitos', 'Westcott', 'Herramienta', 15.50, 15.50, 16);

-- =============================================
-- CATEGORÍA: INFANTIL (10 productos)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('INF_001', 'Crayones Jumbo 12 colores', 'Crayola', 'Material Infantil', 6.50, 6.50, 45),
('INF_002', 'Plastilina Set 8 colores', 'Play-Doh', 'Material Infantil', 12.75, 12.75, 30),
('INF_003', 'Libro Colorear Princesas', 'Dover', 'Material Infantil', 4.25, 4.25, 60),
('INF_004', 'Stickers Brillantes 300 pcs', 'Melissa & Doug', 'Material Infantil', 8.50, 8.50, 40),
('INF_005', 'Tijeras Punta Roma Niños', 'Fiskars', 'Material Infantil', 5.75, 5.75, 35),
('INF_006', 'Marcadores Gruesos 12 col', 'Crayola', 'Material Infantil', 9.25, 9.25, 25),
('INF_007', 'Papel Construcción Pastel', 'Pacon', 'Material Infantil', 7.50, 7.50, 50),
('INF_008', 'Foamy Adhesivo Figuras', 'Creatividad', 'Material Infantil', 3.75, 3.75, 80),
('INF_009', 'Set Sellos Animales', 'Learning Resources', 'Material Infantil', 14.25, 14.25, 20),
('INF_010', 'Tabla Dibujo Borrable', 'Magna Doodle', 'Material Infantil', 18.50, 18.50, 15);

-- =============================================
-- CATEGORÍA: PAPELES_DERIVADOS (10 productos)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('PAP_001', 'Papel Bond Carta 500h', 'Xerox', 'Papel', 7.50, 7.50, 45),
('PAP_002', 'Cartulina Blanca A3', 'Sadipal', 'Papel', 1.25, 1.25, 150),
('PAP_003', 'Papel Fotográfico Brillante', 'Canon', 'Papel', 15.25, 15.25, 20),
('PAP_004', 'Contact Transparente 5m', 'Duck Brand', 'Papel', 8.75, 8.75, 25),
('PAP_005', 'Papel Crepé Rojo', 'Amscan', 'Papel', 2.50, 2.50, 80),
('PAP_006', 'Cartón Piedra A2', 'Pacon', 'Papel', 3.75, 3.75, 60),
('PAP_007', 'Papel Aluminio Cocina', 'Creative Converting', 'Papel', 4.50, 4.50, 40),
('PAP_008', 'Papel Vegetal A4', 'Canson', 'Papel', 12.50, 12.50, 30),
('PAP_009', 'Acetatos Transparentes A4', 'Apollo', 'Papel', 18.75, 18.75, 18),
('PAP_010', 'Papel Seda Multicolor', 'Beistle', 'Papel', 6.25, 6.25, 55);

-- =============================================
-- CATEGORÍA: TECNOLOGIA (10 productos)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('TEC_001', 'USB 64GB Alta Velocidad', 'SanDisk', 'Tecnología', 22.50, 22.50, 20),
('TEC_002', 'Cable Lightning iPhone', 'Belkin', 'Tecnología', 12.75, 12.75, 35),
('TEC_003', 'Mouse Óptico USB', 'Logitech', 'Tecnología', 18.50, 18.50, 25),
('TEC_004', 'Cargador Rápido 25W', 'Anker', 'Tecnología', 24.25, 24.25, 20),
('TEC_005', 'Audífonos Bluetooth', 'Sony', 'Tecnología', 45.75, 45.75, 15),
('TEC_006', 'Hub USB-C 7 en 1', 'Sabrent', 'Tecnología', 32.50, 32.50, 18),
('TEC_007', 'Cable HDMI 2m', 'Cable Matters', 'Tecnología', 14.25, 14.25, 30),
('TEC_008', 'Base Laptop Ventilada', 'Lamicall', 'Tecnología', 28.75, 28.75, 12),
('TEC_009', 'Protector Pantalla Cristal', 'Zagg', 'Tecnología', 16.50, 16.50, 40),
('TEC_010', 'Batería Portátil 20000mAh', 'Xiaomi', 'Tecnología', 38.50, 38.50, 10);

-- =============================================
-- CATEGORÍA: REGALOS (10 productos)
-- =============================================
INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES
('REG_001', 'Taza Cambio Color', 'Custom Print', 'Regalo', 12.50, 12.50, 25),
('REG_002', 'Llavero Foto Personalizado', 'Things Remembered', 'Accesorio', 6.75, 6.75, 40),
('REG_003', 'Marco Digital 7"', 'Umbra', 'Regalo', 35.25, 35.25, 12),
('REG_004', 'Calendario Escritorio 2025', 'At-A-Glance', 'Accesorio', 8.50, 8.50, 30),
('REG_005', 'Terrario Cactus Mini', 'Green Gifts', 'Regalo', 15.75, 15.75, 20),
('REG_006', 'Bolsa Regalo Grande', 'Hallmark', 'Accesorio', 3.25, 3.25, 60),
('REG_007', 'Vela Soja Lavanda', 'Yankee Candle', 'Regalo', 18.50, 18.50, 18),
('REG_008', 'Marcalibros Metálico Dorado', 'Troika', 'Accesorio', 9.25, 9.25, 35),
('REG_009', 'Figura Ángel Cerámica', 'Willow Tree', 'Regalo', 24.75, 24.75, 15),
('REG_010', 'Tarjetas Motivacionales Set', 'Quotable Cards', 'Accesorio', 11.50, 11.50, 28);

-- =============================================
-- ASIGNAR PRODUCTOS A CATEGORÍAS (usando las categorías exactas de tu BD)
-- =============================================

-- ARTE_PINTURAS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ART_P001', 'ARTE_PINTURAS'),
('ART_P002', 'ARTE_PINTURAS'),
('ART_P003', 'ARTE_PINTURAS'),
('ART_P004', 'ARTE_PINTURAS'),
('ART_P005', 'ARTE_PINTURAS'),
('ART_P006', 'ARTE_PINTURAS'),
('ART_P007', 'ARTE_PINTURAS'),
('ART_P008', 'ARTE_PINTURAS'),
('ART_P009', 'ARTE_PINTURAS'),
('ART_P010', 'ARTE_PINTURAS');

-- ARTE_HERRAMIENTAS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ART_H001', 'ARTE_HERRAMIENTAS'),
('ART_H002', 'ARTE_HERRAMIENTAS'),
('ART_H003', 'ARTE_HERRAMIENTAS'),
('ART_H004', 'ARTE_HERRAMIENTAS'),
('ART_H005', 'ARTE_HERRAMIENTAS'),
('ART_H006', 'ARTE_HERRAMIENTAS'),
('ART_H007', 'ARTE_HERRAMIENTAS'),
('ART_H008', 'ARTE_HERRAMIENTAS'),
('ART_H009', 'ARTE_HERRAMIENTAS'),
('ART_H010', 'ARTE_HERRAMIENTAS');

-- ARTE_LIENZOS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ART_L001', 'ARTE_LIENZOS'),
('ART_L002', 'ARTE_LIENZOS'),
('ART_L003', 'ARTE_LIENZOS'),
('ART_L004', 'ARTE_LIENZOS'),
('ART_L005', 'ARTE_LIENZOS'),
('ART_L006', 'ARTE_LIENZOS'),
('ART_L007', 'ARTE_LIENZOS'),
('ART_L008', 'ARTE_LIENZOS'),
('ART_L009', 'ARTE_LIENZOS'),
('ART_L010', 'ARTE_LIENZOS');

-- ESCOLAR_ESCRITURA
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ESC_E001', 'ESCOLAR_ESCRITURA'),
('ESC_E002', 'ESCOLAR_ESCRITURA'),
('ESC_E003', 'ESCOLAR_ESCRITURA'),
('ESC_E004', 'ESCOLAR_ESCRITURA'),
('ESC_E005', 'ESCOLAR_ESCRITURA'),
('ESC_E006', 'ESCOLAR_ESCRITURA'),
('ESC_E007', 'ESCOLAR_ESCRITURA'),
('ESC_E008', 'ESCOLAR_ESCRITURA'),
('ESC_E009', 'ESCOLAR_ESCRITURA'),
('ESC_E010', 'ESCOLAR_ESCRITURA');

-- ESCOLAR_CUADERNOS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ESC_C001', 'ESCOLAR_CUADERNOS'),
('ESC_C002', 'ESCOLAR_CUADERNOS'),
('ESC_C003', 'ESCOLAR_CUADERNOS'),
('ESC_C004', 'ESCOLAR_CUADERNOS'),
('ESC_C005', 'ESCOLAR_CUADERNOS'),
('ESC_C006', 'ESCOLAR_CUADERNOS'),
('ESC_C007', 'ESCOLAR_CUADERNOS'),
('ESC_C008', 'ESCOLAR_CUADERNOS'),
('ESC_C009', 'ESCOLAR_CUADERNOS'),
('ESC_C010', 'ESCOLAR_CUADERNOS');

-- ESCOLAR_GEOMETRIA
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('ESC_G001', 'ESCOLAR_GEOMETRIA'),
('ESC_G002', 'ESCOLAR_GEOMETRIA'),
('ESC_G003', 'ESCOLAR_GEOMETRIA'),
('ESC_G004', 'ESCOLAR_GEOMETRIA'),
('ESC_G005', 'ESCOLAR_GEOMETRIA'),
('ESC_G006', 'ESCOLAR_GEOMETRIA'),
('ESC_G007', 'ESCOLAR_GEOMETRIA'),
('ESC_G008', 'ESCOLAR_GEOMETRIA'),
('ESC_G009', 'ESCOLAR_GEOMETRIA'),
('ESC_G010', 'ESCOLAR_GEOMETRIA');

-- LIBROS_ESCOLARES
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('LIB_E001', 'LIBROS_ESCOLARES'),
('LIB_E002', 'LIBROS_ESCOLARES'),
('LIB_E003', 'LIBROS_ESCOLARES'),
('LIB_E004', 'LIBROS_ESCOLARES'),
('LIB_E005', 'LIBROS_ESCOLARES'),
('LIB_E006', 'LIBROS_ESCOLARES'),
('LIB_E007', 'LIBROS_ESCOLARES'),
('LIB_E008', 'LIBROS_ESCOLARES'),
('LIB_E009', 'LIBROS_ESCOLARES'),
('LIB_E010', 'LIBROS_ESCOLARES');

-- LIBROS_LITERATURA
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('LIB_L001', 'LIBROS_LITERATURA'),
('LIB_L002', 'LIBROS_LITERATURA'),
('LIB_L003', 'LIBROS_LITERATURA'),
('LIB_L004', 'LIBROS_LITERATURA'),
('LIB_L005', 'LIBROS_LITERATURA'),
('LIB_L006', 'LIBROS_LITERATURA'),
('LIB_L007', 'LIBROS_LITERATURA'),
('LIB_L008', 'LIBROS_LITERATURA'),
('LIB_L009', 'LIBROS_LITERATURA'),
('LIB_L010', 'LIBROS_LITERATURA');

-- LIBROS_INFANTILES
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('LIB_I001', 'LIBROS_INFANTILES'),
('LIB_I002', 'LIBROS_INFANTILES'),
('LIB_I003', 'LIBROS_INFANTILES'),
('LIB_I004', 'LIBROS_INFANTILES'),
('LIB_I005', 'LIBROS_INFANTILES'),
('LIB_I006', 'LIBROS_INFANTILES'),
('LIB_I007', 'LIBROS_INFANTILES'),
('LIB_I008', 'LIBROS_INFANTILES'),
('LIB_I009', 'LIBROS_INFANTILES'),
('LIB_I010', 'LIBROS_INFANTILES');

-- OFICINA_ARCHIVO
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('OFC_A001', 'OFICINA_ARCHIVO'),
('OFC_A002', 'OFICINA_ARCHIVO'),
('OFC_A003', 'OFICINA_ARCHIVO'),
('OFC_A004', 'OFICINA_ARCHIVO'),
('OFC_A005', 'OFICINA_ARCHIVO'),
('OFC_A006', 'OFICINA_ARCHIVO'),
('OFC_A007', 'OFICINA_ARCHIVO'),
('OFC_A008', 'OFICINA_ARCHIVO'),
('OFC_A009', 'OFICINA_ARCHIVO'),
('OFC_A010', 'OFICINA_ARCHIVO');

-- OFICINA_HERRAMIENTAS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('OFC_H001', 'OFICINA_HERRAMIENTAS'),
('OFC_H002', 'OFICINA_HERRAMIENTAS'),
('OFC_H003', 'OFICINA_HERRAMIENTAS'),
('OFC_H004', 'OFICINA_HERRAMIENTAS'),
('OFC_H005', 'OFICINA_HERRAMIENTAS'),
('OFC_H006', 'OFICINA_HERRAMIENTAS'),
('OFC_H007', 'OFICINA_HERRAMIENTAS'),
('OFC_H008', 'OFICINA_HERRAMIENTAS'),
('OFC_H009', 'OFICINA_HERRAMIENTAS'),
('OFC_H010', 'OFICINA_HERRAMIENTAS');

-- INFANTIL
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('INF_001', 'INFANTIL'),
('INF_002', 'INFANTIL'),
('INF_003', 'INFANTIL'),
('INF_004', 'INFANTIL'),
('INF_005', 'INFANTIL'),
('INF_006', 'INFANTIL'),
('INF_007', 'INFANTIL'),
('INF_008', 'INFANTIL'),
('INF_009', 'INFANTIL'),
('INF_010', 'INFANTIL');

-- PAPELES_DERIVADOS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('PAP_001', 'PAPELES_DERIVADOS'),
('PAP_002', 'PAPELES_DERIVADOS'),
('PAP_003', 'PAPELES_DERIVADOS'),
('PAP_004', 'PAPELES_DERIVADOS'),
('PAP_005', 'PAPELES_DERIVADOS'),
('PAP_006', 'PAPELES_DERIVADOS'),
('PAP_007', 'PAPELES_DERIVADOS'),
('PAP_008', 'PAPELES_DERIVADOS'),
('PAP_009', 'PAPELES_DERIVADOS'),
('PAP_010', 'PAPELES_DERIVADOS');

-- TECNOLOGIA
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('TEC_001', 'TECNOLOGIA'),
('TEC_002', 'TECNOLOGIA'),
('TEC_003', 'TECNOLOGIA'),
('TEC_004', 'TECNOLOGIA'),
('TEC_005', 'TECNOLOGIA'),
('TEC_006', 'TECNOLOGIA'),
('TEC_007', 'TECNOLOGIA'),
('TEC_008', 'TECNOLOGIA'),
('TEC_009', 'TECNOLOGIA'),
('TEC_010', 'TECNOLOGIA');

-- REGALOS
INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES
('REG_001', 'REGALOS'),
('REG_002', 'REGALOS'),
('REG_003', 'REGALOS'),
('REG_004', 'REGALOS'),
('REG_005', 'REGALOS'),
('REG_006', 'REGALOS'),
('REG_007', 'REGALOS'),
('REG_008', 'REGALOS'),
('REG_009', 'REGALOS'),
('REG_010', 'REGALOS');

-- =============================================
-- INSERTAR IMÁGENES DE PRODUCTOS NUEVOS ÚNICAMENTE
-- NO SE MODIFICAN LAS IMÁGENES EXISTENTES
-- =============================================

-- ARTE - PINTURAS (nuevos productos)
/** INSERT INTO img (idProd, ruta) VALUES
('ART_P001', 'img/acrilico_azul_cobalto.jpg'),
('ART_P002', 'img/tempera_roja_pelikan.jpg'),
('ART_P003', 'img/acuarela_verde_cotman.jpg'),
('ART_P004', 'img/oleo_blanco_vangogh.jpg'),
('ART_P005', 'img/acrilico_violeta_liquitex.jpg'),
('ART_P006', 'img/tempera_amarilla_giotto.jpg'),
('ART_P007', 'img/acuarela_siena_daniel.jpg'),
('ART_P008', 'img/oleo_esmeralda_rembrandt.jpg'),
('ART_P009', 'img/acrilico_plateado_golden.jpg'),
('ART_P010', 'img/temperas_set_crayola.jpg');

-- ARTE - HERRAMIENTAS (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('ART_H001', 'img/pincel_angular_princeton.jpg'),
('ART_H002', 'img/set_acuarela_royal.jpg'),
('ART_H003', 'img/paleta_plastica_artmate.jpg'),
('ART_H004', 'img/espatula_diamante_rgm.jpg'),
('ART_H005', 'img/pincel_lengua_escoda.jpg'),
('ART_H006', 'img/rodillo_textura_speedball.jpg'),
('ART_H007', 'img/paleta_cristal_strathmore.jpg'),
('ART_H008', 'img/cuchillo_paleta_winsor.jpg'),
('ART_H009', 'img/pincel_detalle_davinci.jpg'),
('ART_H010', 'img/limpiador_pinceles.jpg');

-- ARTE - LIENZOS (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('ART_L001', 'img/lienzo_30x40_artmate.jpg'),
('ART_L002', 'img/bastidor_pino_phoenix.jpg'),
('ART_L003', 'img/papel_acuarela_a3_fabriano.jpg'),
('ART_L004', 'img/canvas_board_fredrix.jpg'),
('ART_L005', 'img/carton_a4_canson.jpg'),
('ART_L006', 'img/block_oleo_arches.jpg'),
('ART_L007', 'img/lienzo_redondo_arteza.jpg'),
('ART_L008', 'img/papel_mixta_strathmore.jpg'),
('ART_L009', 'img/tablilla_entelada_master.jpg'),
('ART_L010', 'img/papel_pastel_sadipal.jpg');

-- ESCOLAR - ESCRITURA (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('ESC_E001', 'img/lapiz_amarillo_faber.jpg'),
('ESC_E002', 'img/lapicero_negro_bic.jpg'),
('ESC_E003', 'img/borrador_rosa_milan.jpg'),
('ESC_E004', 'img/colores_24_prismacolor.jpg'),
('ESC_E005', 'img/marcador_fino_sharpie.jpg'),
('ESC_E006', 'img/corrector_cinta_papermate.jpg'),
('ESC_E007', 'img/portaminas_parker.jpg'),
('ESC_E008', 'img/set_resaltadores_stabilo.jpg'),
('ESC_E009', 'img/minas_pentel.jpg'),
('ESC_E010', 'img/sacapuntas_doble_staedtler.jpg');

-- ESCOLAR - CUADERNOS (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('ESC_C001', 'img/cuaderno_cuadriculado_norma.jpg'),
('ESC_C002', 'img/libreta_pautada_oxford.jpg'),
('ESC_C003', 'img/agenda_diaria_miquelrius.jpg'),
('ESC_C004', 'img/cuaderno_anillado_rhodia.jpg'),
('ESC_C005', 'img/block_cartulina_canson.jpg'),
('ESC_C006', 'img/libreta_puntos_moleskine.jpg'),
('ESC_C007', 'img/cuaderno_blanda_scribe.jpg'),
('ESC_C008', 'img/planificador_mensual_quo.jpg'),
('ESC_C009', 'img/notas_colores_postit.jpg'),
('ESC_C010', 'img/bitacora_proyectos_leuchtturm.jpg');

-- ESCOLAR - GEOMETRÍA (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('ESC_G001', 'img/regla_aluminio_maped.jpg'),
('ESC_G002', 'img/compas_plastico_staedtler.jpg'),
('ESC_G003', 'img/escuadra_3060_faber.jpg'),
('ESC_G004', 'img/transportador_360_milan.jpg'),
('ESC_G005', 'img/kit_geometria_maped.jpg'),
('ESC_G006', 'img/regla_triangular_westcott.jpg'),
('ESC_G007', 'img/compas_bigotera_rotring.jpg'),
('ESC_G008', 'img/plantilla_curvas_staedtler.jpg'),
('ESC_G009', 'img/goniometro_circular_bic.jpg'),
('ESC_G010', 'img/plantillas_geometricas_maped.jpg');

-- LIBROS ESCOLARES (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('LIB_E001', 'img/matematicas_7mo_santillana.jpg'),
('LIB_E002', 'img/ciencias_6to_mcgrawhill.jpg'),
('LIB_E003', 'img/estudios_sociales_pearson.jpg'),
('LIB_E004', 'img/ingles_intermedio_cambridge.jpg'),
('LIB_E005', 'img/lenguaje_8vo_anaya.jpg'),
('LIB_E006', 'img/fisica_1bach_oxford.jpg'),
('LIB_E007', 'img/quimica_organica_edebe.jpg'),
('LIB_E008', 'img/historia_salvador_sm.jpg'),
('LIB_E009', 'img/atlas_salvador_rae.jpg'),
('LIB_E010', 'img/diccionario_ingles_larousse.jpg');

-- LIBROS LITERATURA (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('LIB_L001', 'img/cien_anos_soledad.jpg'),
('LIB_L002', 'img/el_alquimista_planeta.jpg'),
('LIB_L003', 'img/orgullo_prejuicio_alianza.jpg'),
('LIB_L004', 'img/1984_orwell_debolsillo.jpg'),
('LIB_L005', 'img/cronica_muerte_diana.jpg'),
('LIB_L006', 'img/el_tunel_seix.jpg'),
('LIB_L007', 'img/rayuela_alfaguara.jpg'),
('LIB_L008', 'img/casa_espiritus_plaza.jpg'),
('LIB_L009', 'img/pedro_paramo_rm.jpg'),
('LIB_L010', 'img/ficciones_emece.jpg');

-- LIBROS INFANTILES (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('LIB_I001', 'img/gato_botas_juventud.jpg'),
('LIB_I002', 'img/liebre_tortuga_everest.jpg'),
('LIB_I003', 'img/hansel_gretel_susaeta.jpg'),
('LIB_I004', 'img/tres_cochinitos_combel.jpg'),
('LIB_I005', 'img/rapunzel_galera.jpg'),
('LIB_I006', 'img/sirenita_musical_timun.jpg'),
('LIB_I007', 'img/bambi_disney_anaya.jpg'),
('LIB_I008', 'img/pulgarcita_bruno.jpg'),
('LIB_I009', 'img/soldadito_plomo_sm.jpg'),
('LIB_I010', 'img/alicia_maravillas_edelvives.jpg');

-- OFICINA ARCHIVO (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('OFC_A001', 'img/folder_colgante_acco.jpg'),
('OFC_A002', 'img/archivador_palanca_elba.jpg'),
('OFC_A003', 'img/carpeta_3anillos_leitz.jpg'),
('OFC_A004', 'img/caja_definitivo_bankers.jpg'),
('OFC_A005', 'img/separadores_alfabeticos_avery.jpg'),
('OFC_A006', 'img/portadocumentos_samsonite.jpg'),
('OFC_A007', 'img/sobre_reforzado_pendaflex.jpg'),
('OFC_A008', 'img/organizador_6div_rubbermaid.jpg'),
('OFC_A009', 'img/carpeta_fuelle_fast.jpg'),
('OFC_A010', 'img/etiquetas_adhesivas_quality.jpg');

-- OFICINA HERRAMIENTAS (nuevos productos)
INSERT INTO img (idProd, ruta) VALUES
('OFC_H001', 'img/grapadora_heavy_swingline.jpg'),
('OFC_H002', 'img/perforadora_3ag_bostitch.jpg'),
('OFC_H003', 'img/clips_jumbo_acco.jpg'),
('OFC_H004', 'img/dispensador_pesado_3m.jpg'),
('OFC_H005', 'img/tijeras_titanio_fiskars.jpg'),
('OFC_H006', 'img/calculadora_cientifica_casio.jpg'),
('OFC_H007', 'img/plastificadora_a4_dahle.jpg'),
('OFC_H008', 'img/engrapadora_electrica_stanley.jpg'),
('OFC_H009', 'img/guillotina_a3_olfa.jpg'),
('OFC_H010', 'img/numerador_6dig_westcott.jpg');

-- CATEGORÍAS PRINCIPALES
INSERT INTO img (idProd, ruta) VALUES
('INF_001', 'img/crayones_jumbo_inf_crayola.jpg'),
('INF_002', 'img/plastilina_8col_playdoh.jpg'),
('INF_003', 'img/libro_princesas_dover.jpg'),
('INF_004', 'img/stickers_brillantes_melissa.jpg'),
('INF_005', 'img/tijeras_roma_fiskars_inf.jpg'),
('INF_006', 'img/marcadores_gruesos_crayola.jpg'),
('INF_007', 'img/papel_pastel_pacon.jpg'),
('INF_008', 'img/foamy_figuras_creatividad.jpg'),
('INF_009', 'img/sellos_animales_learning.jpg'),
('INF_010', 'img/tabla_borrable_magna.jpg');

INSERT INTO img (idProd, ruta) VALUES
('PAP_001', 'img/papel_bond_carta_xerox.jpg'),
('PAP_002', 'img/cartulina_blanca_sadipal.jpg'),
('PAP_003', 'img/papel_foto_brillante_canon.jpg'),
('PAP_004', 'img/contact_5m_duck.jpg'),
('PAP_005', 'img/papel_crepe_rojo_amscan.jpg'),
('PAP_006', 'img/carton_piedra_pacon.jpg'),
('PAP_007', 'img/papel_aluminio_creative.jpg'),
('PAP_008', 'img/papel_vegetal_canson.jpg'),
('PAP_009', 'img/acetatos_a4_apollo.jpg'),
('PAP_010', 'img/papel_seda_multicolor_beistle.jpg');

INSERT INTO img (idProd, ruta) VALUES
('TEC_001', 'img/usb_64gb_sandisk.jpg'),
('TEC_002', 'img/cable_lightning_belkin.jpg'),
('TEC_003', 'img/mouse_optico_logitech.jpg'),
('TEC_004', 'img/cargador_25w_anker.jpg'),
('TEC_005', 'img/audifonos_bluetooth_sony.jpg'),
('TEC_006', 'img/hub_7en1_sabrent.jpg'),
('TEC_007', 'img/cable_hdmi_2m_cable.jpg'),
('TEC_008', 'img/base_laptop_lamicall.jpg'),
('TEC_009', 'img/protector_cristal_zagg.jpg'),
('TEC_010', 'img/bateria_20000_xiaomi.jpg');

INSERT INTO img (idProd, ruta) VALUES
('REG_001', 'img/taza_cambio_color_custom.jpg'),
('REG_002', 'img/llavero_foto_things.jpg'),
('REG_003', 'img/marco_digital_umbra.jpg'),
('REG_004', 'img/calendario_escritorio_at.jpg'),
('REG_005', 'img/terrario_cactus_green.jpg'),
('REG_006', 'img/bolsa_grande_hallmark.jpg'),
('REG_007', 'img/vela_soja_yankee.jpg'),
('REG_008', 'img/marcalibros_dorado_troika.jpg'),
('REG_009', 'img/angel_ceramica_willow.jpg'),
('REG_010', 'img/tarjetas_motivacionales_quotable.jpg');
*/
-- =============================================
-- RESUMEN DEL INVENTARIO ACTUALIZADO
-- =============================================
-- Total de productos NUEVOS: 130
-- ARTE: 30 productos (10 pinturas, 10 herramientas, 10 soportes)
-- ESCOLAR: 30 productos (10 escritura, 10 cuadernos, 10 geometría)  
-- LIBROS: 30 productos (10 escolares, 10 literatura, 10 infantiles)
-- OFICINA: 20 productos (10 archivo, 10 herramientas)
-- INFANTIL: 10 productos
-- PAPELES Y DERIVADOS: 10 productos
-- TECNOLOGÍA: 10 productos
-- REGALOS Y ACCESORIOS: 10 productos

-- PRODUCTOS EXISTENTES PRESERVADOS:
-- ART001, ART002, ART003 (con sus imágenes originales)
-- ESC001, ESC002, ESC003 (con sus imágenes originales)
-- OFC001, OFC002 (con sus imágenes originales)

INSERT INTO usuarios (
    IdUsuario,
    Nombre,
    Apellido,
    Correo,
    Telefono,
    Direccion,
    Usuario,
    Contrasena,
    Rol,
    email_verificado,
    fecha_registro,
    ultimo_login,
    activo
) VALUES (
    'USR_ADMIN_001',
    'Administrador',
    'Sistema',
    'hernandezrodri83@gmail.com',
    '',
    '',
    'admin',
    '$2y$10$l7Wts2OWVnEAalpCs7gWvexBIcUSrkhTZxOQPhZLq8w34Ekl5G7yC',
    'admin',
    1,
    NOW(),
    NULL,
    1
);

