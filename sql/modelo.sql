
-- 2. TABLA DEPARTAMENTO
-- Se crea sin la clave foránea 'Director_Id' todavía,
-- porque 'Usuario' aún no existe (dependencia circular).
CREATE TABLE Departamento (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(255) NOT NULL,
    Director_Id INT NULL -- Se agregará la FK después de crear la tabla Usuario
);

-- 3. TABLA USUARIO
-- Asumimos 'Id' como PK para que las relaciones INT funcionen.
-- 'Rut' se establece como UNIQUE.
CREATE TABLE Usuario (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Rut VARCHAR(12) UNIQUE NOT NULL,
    Nombre VARCHAR(255) NOT NULL,
    Email VARCHAR(255) UNIQUE NOT NULL,
    Contrasenha VARCHAR(255) NOT NULL,
    Rol VARCHAR(50), -- Tipo Asumido
    Telefono VARCHAR(20), -- Tipo Asumido
    Departamento_Id INT,
    Token VARCHAR(255), -- Tipo Asumido
    FOREIGN KEY (Departamento_Id) REFERENCES Departamento(Id)
);

-- 4. ACTUALIZAR TABLA DEPARTAMENTO
-- Ahora que 'Usuario' existe, podemos agregar la clave foránea 'Director_Id'.
ALTER TABLE Departamento
ADD CONSTRAINT fk_director
FOREIGN KEY (Director_Id) REFERENCES Usuario(Id)
ON DELETE SET NULL; -- Si se elimina el usuario, el campo Director_Id queda nulo

-- 5. TABLA ORDEN_PEDIDO
CREATE TABLE Orden_Pedido (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Solicitante_Id INT,
    Nombre_Orden VARCHAR(255), -- Tipo Asumido
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP, -- Tipo Asumido
    Tipo_Compra VARCHAR(100), -- Tipo Asumido
    Presupuesto VARCHAR(100), -- Tipo Asumido
    Subprog VARCHAR(100), -- Tipo Asumido
    Centro_Costos VARCHAR(100), -- Tipo Asumido
    Valor_neto DECIMAL(12, 2), -- Tipo Asumido (12 dígitos totales, 2 decimales)
    Plazo_maximo VARCHAR(100), -- Tipo Asumido (Podría ser DATE también)
    Iva DECIMAL(10, 2), -- Tipo Asumido
    Valor_total DECIMAL(12, 2), -- Tipo Asumido
    Estado VARCHAR(50), -- Tipo Asumido
    Motivo_Rechazo TEXT, -- Tipo Asumido
    Motivo_Compra TEXT, -- Tipo Asumido
    Cuenta_Presupuestaria VARCHAR(100), -- Tipo Asumido
    FOREIGN KEY (Solicitante_Id) REFERENCES Usuario(Id)
    ON DELETE SET NULL -- Si se borra el solicitante, la orden no se borra
);

-- 6. TABLA ORDEN_ITEM
CREATE TABLE Orden_Item (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Orden_Id INT NOT NULL,
    Nombre_producto_servicio VARCHAR(255), -- Tipo Asumido
    Cantidad INT, -- Tipo Asumido
    Valor_Unitario DECIMAL(12, 2), -- Tipo Asumido
    Valor_Total DECIMAL(12, 2), -- Tipo Asumido
    FOREIGN KEY (Orden_Id) REFERENCES Orden_Pedido(Id)
    ON DELETE CASCADE -- Si se borra la orden, se borran los items
);

-- 7. TABLA GESTION_COMPRA
CREATE TABLE Gestion_Compra (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Orden_Id INT NOT NULL,
    Fecha_Gestion DATETIME, -- Tipo Asumido
    Proveedor_Contactado VARCHAR(255), -- Tipo Asumido
    Estado_gestion VARCHAR(100), -- Tipo Asumido
    FOREIGN KEY (Orden_Id) REFERENCES Orden_Pedido(Id)
    ON DELETE CASCADE -- Si se borra la orden, se borra su gestión
);

-- 8. TABLA FIRMAS_ORDEN
CREATE TABLE Firmas_Orden (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Usuario_Id INT,
    Orden_Id INT,
    Fecha_Firma DATETIME DEFAULT CURRENT_TIMESTAMP, -- Tipo Asumido
    Decision BOOLEAN, -- Tipo Asumido (1 = Aprobado, 0 = Rechazado)
    FOREIGN KEY (Usuario_Id) REFERENCES Usuario(Id) ON DELETE SET NULL,
    FOREIGN KEY (Orden_Id) REFERENCES Orden_Pedido(Id) ON DELETE CASCADE
);

-- 1. Nueva tabla para manejar TODOS los archivos (Trato directo y adicionales)
CREATE TABLE Orden_Archivos (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Orden_Id INT NOT NULL,
    Nombre_Archivo VARCHAR(255) NOT NULL, -- Nombre guardado en disco (hash o time)
    Nombre_Original VARCHAR(255) NOT NULL, -- Nombre real que subió el usuario
    Tipo_Documento VARCHAR(50), -- Ej: 'Cotizacion', 'Memorando', 'Decreto', 'Adicional'
    Ruta_Archivo VARCHAR(255), -- Ruta relativa: 'uploads/archivo.pdf'
    FOREIGN KEY (Orden_Id) REFERENCES Orden_Pedido(Id) ON DELETE CASCADE
);

-- 2. Modificar la tabla de Ítems para agregar el ID del Producto
ALTER TABLE Orden_Item
ADD COLUMN Codigo_Producto VARCHAR(100) AFTER Nombre_producto_servicio;