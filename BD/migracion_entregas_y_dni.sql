-- Para quien YA tiene cargada la base bdbeneficios (sin reimportar):
USE bdbeneficios;

ALTER TABLE ventas
  ADD COLUMN entregado tinyint NOT NULL DEFAULT 0,
  ADD COLUMN fecha_entrega date DEFAULT NULL;

-- Si hubiera DNI repetidos, este paso falla: corregirlos antes.
ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_dni (dni);
