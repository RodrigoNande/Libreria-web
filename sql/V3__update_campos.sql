-- Agregar campo Stock a la tabla articulo
ALTER TABLE articulo
ADD COLUMN Stock INT NOT NULL AFTER Precio_Unitario;

-- Agregar campo IdCategoriaPadre a la tabla Categoria
ALTER TABLE Categoria
ADD COLUMN IdCategoriaPadre VARCHAR(100) NULL AFTER Descripcion;

-- Agregar clave foránea en IdCategoriaPadre que referencia a la misma tabla Categoria(Id)
ALTER TABLE Categoria
ADD CONSTRAINT fk_categoria_padre
FOREIGN KEY (IdCategoriaPadre) REFERENCES Categoria(Id)
ON DELETE SET NULL ON UPDATE CASCADE;
