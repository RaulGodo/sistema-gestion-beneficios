# Sistema de Gestión de Beneficios

## Instalación
1. Copiar la carpeta `sistema_beneficios` en `htdocs` (XAMPP) o equivalente.
2. Crear la base `bdbeneficios` e importar `BD/bdbeneficios.sql` (incluye datos de prueba).
   - Si ya tenías la base cargada: ejecutar `BD/migracion_entregas_y_dni.sql` y luego `BD/migracion_asignaciones.sql`
     (esta última asigna a cada vendedor los beneficios que ya había vendido).
3. Revisar usuario y clave de MySQL en `conexion.php`.
4. Abrir `http://localhost/sistema_beneficios/`

## Usuarios de prueba
| Perfil        | DNI      | Clave        |
|---------------|----------|--------------|
| Administrador | 10000000 | 12345678   
| Vendedor      | 11111111 | 123456789

Los vendedores también pueden registrarse solos desde `registro.php`.

## Cambios de esta versión
- Registro de vendedores voluntarios (`registro.php`).
- El vendedor registra sus cobros (`vendedor/registrar_pago.php`).
- Registro de entrega por el admin (`admin/registrar_entrega.php`); el vendedor ve la fecha.
- Stock: el ajuste (+/-) ya no pisa el stock inicial; no se vende más de lo disponible.
- Seguridad: consultas preparadas, escape de salida (XSS), tokens CSRF, eliminaciones por POST.
- Pagos y rendiciones con validación de monto y transacciones.
- Ventas abiertas: estado 0 hasta "Guardar Venta"; se retoman si se cierra la sesión.
- Vendedores/productos con historial se dan de baja o se bloquean en lugar de romper por FK.
- Beneficios asignados a vendedores (`admin/asignar_vendedores.php`): cada vendedor solo ve y vende los beneficios que el admin le asignó.
  - Se valida en el servidor al crear la venta y al listar productos (no alcanza con filtrar el combo).
  - Al crear un beneficio se pasa directo a elegir sus vendedores; la lista muestra cuántos tiene asignados.
  - Si le quitan un beneficio a un vendedor con una venta abierta, puede guardarla pero no agregar más productos.
  - El historial (ventas, pagos, rendiciones) no se toca al quitar una asignación.
- CSRF completo: todos los formularios que modifican datos (altas, ediciones, registro de vendedores) llevan token y se validan en el servidor. `editar_beneficio.php` además valida nombre, fechas y estado.
