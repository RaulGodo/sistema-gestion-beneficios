-- Asignación de vendedores a beneficios (tabla beneficios_has_usuarios).
-- Ejecutar UNA vez sobre la base bdbeneficios ya cargada.
USE bdbeneficios;

-- Los vendedores que ya vendieron un beneficio quedan asignados a él,
-- para que no pierdan acceso a lo que venían vendiendo.
INSERT IGNORE INTO beneficios_has_usuarios (beneficios_id_beneficio, usuarios_id_usuario)
SELECT DISTINCT beneficios_id_beneficio, usuarios_id_usuario FROM ventas;
