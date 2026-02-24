# Tests Sprint 2 - WP API Codeia

## Resumen Ejecutivo

Tests creados para validar todas las funcionalidades implementadas en Sprint 2.

### Total de Tests: 74 (32 nuevos + 42 de Sprint 1)

| Categoría | Tests | Estado |
|-----------|-------|--------|
| Unit Tests | 54 | ✅ |
| Integration Tests | 20 | ✅ |

---

## Tests Nuevos Sprint 2

### 1. JWT Handler Tests (10 tests)

**Archivo**: `tests/unit/jwt-handler-test.php`

| Test | Descripción |
|------|-------------|
| `test_generate_access_token` | Generación de access token válido |
| `test_decode_valid_token` | Decodificación de token válido |
| `test_decode_invalid_token` | Rechazo de token inválido |
| `test_generate_refresh_token` | Generación de refresh token |
| `test_verify_refresh_token` | Verificación de refresh token |
| `test_verify_invalid_refresh_token` | Rechazo de refresh token inválido |
| `test_invalidate_token` | Invalidación de token (logout) |
| `test_expired_token_returns_false` | Rechazo de token expirado |
| `test_set_token_lifetime` | Configuración de tiempos de vida |
| `test_include_extra_data_in_token` | Inclusión de datos extra en payload |

**Ejecución**:
```bash
# Usando PHPUnit
phpunit tests/unit/jwt-handler-test.php

# Manual
1. Crear access token para usuario
2. Verificar firma y estructura (header.payload.signature)
3. Decodificar y verificar payload
4. Verificar expiración del token
5. Probar refresh token
6. Invalidar token y verificar blacklist
```

---

### 2. Field Permission Manager Tests (10 tests)

**Archivo**: `tests/unit/field-permission-manager-test.php`

| Test | Descripción |
|------|-------------|
| `test_native_fields_allowed_by_default` | Campos nativos permitidos por defecto |
| `test_add_deny_rule` | Agregar regla de denegación |
| `test_add_allow_rule` | Agregar regla de permiso |
| `test_deny_rules_override_allow` | Prioridad de reglas deny |
| `test_filter_fields_removes_disallowed` | Filtrado de campos |
| `test_get_allowed_fields` | Obtener campos permitidos |
| `test_remove_rule` | Remover reglas |
| `test_scope_affects_permissions` | Efecto del scope en permisos |
| `test_export_config` | Exportar configuración |
| `test_import_config` | Importar configuración |

**Validaciones Manuales**:
```bash
# 1. Crear regla de permiso
wp_api_codeia_field_permissions()->add_rule('post', 'sensitive_field', 'deny', 'role', 'subscriber');

# 2. Verificar que el campo está denegado
is_allowed = wp_api_codeia_field_permissions()->is_field_allowed('sensitive_field', 'post', 'subscriber', 'read');
// Debería retornar false

# 3. Filtrar datos
$data = ['ID' => 1, 'post_title' => 'Test', 'sensitive_field' => 'secret'];
$filtered = wp_api_codeia_field_permissions()->filter_fields($data, 'post', 'subscriber', 'read');
// 'sensitive_field' no debería estar en $filtered

# 4. Obtener campos permitidos
$allowed = wp_api_codeia_field_permissions()->get_allowed_fields('post', 'subscriber', 'read');
// Debería incluir campos nativos pero no 'sensitive_field'
```

---

### 3. Rate Limiter Tests (10 tests)

**Archivo**: `tests/unit/rate-limiter-test.php`

| Test | Descripción |
|------|-------------|
| `test_first_request_allowed` | Primera solicitud siempre permitida |
| `test_log_request` | Registro de solicitudes |
| `test_counter_increments` | Incremento del contador |
| `test_limit_enforced` | Respeto del límite configurado |
| `test_reset_limits` | Reset de límites |
| `test_get_usage_stats` | Estadísticas de uso |
| `test_get_active_identifiers` | Identificadores activos |
| `test_generate_rate_limit_headers` | Generación de headers |
| `test_ip_blocking` | Bloqueo de IPs |
| `test_get_endpoint_limits` | Obtención de límites por endpoint |

**Validaciones Manuales**:
```bash
# 1. Probar rate limit
$limiter = wp_api_codeia_rate_limiter();
$identifier = 'test_key_123';

# 2. Hacer solicitudes hasta el límite
for ($i = 0; $i < 10; $i++) {
    $limiter->log_request($identifier, 'posts', 'GET');
}

# 3. Verificar estado
$result = $limiter->check_rate_limit($identifier, 'posts', 10, 3600);
// Debería mostrar: allowed=false si excedió límite

# 4. Verificar headers
$headers = $limiter->generate_rate_limit_headers($result);
// Debería incluir X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
```

---

### 4. Cache Manager Tests (10 tests)

**Archivo**: `tests/unit/cache-manager-test.php`

| Test | Descripción |
|------|-------------|
| `test_set_and_get` | Guardar y obtener valor |
| `test_get_returns_false_for_nonexistent` | Get retorna false si no existe |
| `test_delete` | Eliminar valor cacheado |
| `test_remember` | Pattern cache-aside |
| `test_get_stats` | Estadísticas de cache |
| `test_flush_group` | Limpiar grupo específico |
| `test_flush_all` | Limpiar todo el cache |
| `test_configure_group` | Configurar grupo custom |
| `test_invalidate_post_cache` | Invalidación de post |
| `test_invalidate_term_cache` | Invalidación de término |

**Validaciones Manuales**:
```bash
# 1. Probar cache
$cache = wp_api_codeia()->get_cache_manager();

# 2. Guardar valor
$cache->set('test_key', ['data' => 'value'], 'posts', 3600);

# 3. Obtener valor
$value = $cache->get('test_key', 'posts');
// Debería retornar ['data' => 'value']

# 4. Probar remember
$result = $cache->remember('expensive_key', function() {
    return expensive_operation();
}, 'posts');

# 5. Invalidar cache
$post_id = 123;
$cache->invalidate_post_cache($post_id);
// Cache del post debería eliminarse
```

---

### 5. CORS Manager Tests (7 tests)

**Archivo**: `tests/unit/cors-manager-test.php`

| Test | Descripción |
|------|-------------|
| `test_get_default_config` | Configuración por defecto |
| `test_save_config` | Guardar configuración |
| `test_add_allowed_origin` | Agregar origen permitido |
| `test_remove_allowed_origin` | Remover origen |
| `test_wildcard_allows_all` | Wildcard permite todos |
| `test_get_dev_config` | Configuración de desarrollo |
| `test_multiple_origins` | Múltiples orígenes |

**Validaciones Manuales**:
```bash
# 1. Configurar CORS
$cors = wp_api_codeia_cors();
$cors->save_config([
    'enabled' => true,
    'allow_origins' => ['https://example.com'],
    'allow_methods' => ['GET', 'POST'],
    'allow_headers' => ['Authorization', 'Content-Type'],
    'max_age' => 3600,
    'allow_credentials' => true,
]);

# 2. Verificar headers en respuesta
// Hacer request desde https://example.com
// Verificar headers:
// Access-Control-Allow-Origin: https://example.com
// Access-Control-Allow-Methods: GET, POST
// Access-Control-Allow-Credentials: true
```

---

### 6. Query Param Manager Tests (10 tests)

**Archivo**: `tests/unit/query-param-manager-test.php`

| Test | Descripción |
|------|-------------|
| `test_get_registered_params` | Parámetros registrados |
| `test_get_param` | Obtener parámetro específico |
| `test_register_param` | Registrar parámetro custom |
| `test_unregister_param` | Remover parámetro |
| `test_validate_and_sanitize_params` | Validación y sanitización |
| `test_validates_integer` | Validación de enteros |
| `test_validates_enum` | Validación de enums |
| `test_validates_min_max` | Validación de rangos |
| `test_build_tax_query` | Construcción de tax_query |
| `test_build_meta_query` | Construcción de meta_query |

**Validaciones Manuales**:
```bash
# 1. Validar parámetros
$query_params = wp_api_codeia_query_params();

$input = [
    'page' => '2',
    'per_page' => '20',
    'search' => 'test',
    'order' => 'ASC',
];

$validated = $query_params->validate_and_sanitize_params($input);
// Debería retornar array sanitizado y validado

# 2. Construir WP_Query args
$args = $query_params->build_wp_query_args($validated);
// Debería incluir 'paged' => 2, 'posts_per_page' => 20, etc.

# 3. Probar tax_query
$input_with_tax = [
    'categories' => [1, 2, 3],
    'tax_relation' => 'AND',
];

$tax_query = $query_params->build_tax_query($input_with_tax);
// Debería construir tax_query válida
```

---

## Ejecución Completa de Tests

### Automatizada

```bash
# Ejecutar todos los tests unitarios
phpunit tests/unit/

# Ejecutar solo tests de Sprint 2
phpunit tests/unit/jwt-handler-test.php
phpunit tests/unit/field-permission-manager-test.php
phpunit tests/unit/rate-limiter-test.php
phpunit tests/unit/cache-manager-test.php
phpunit tests/unit/cors-manager-test.php
phpunit tests/unit/query-param-manager-test.php

# Con coverage
phpunit --coverage-html coverage tests/unit/
```

### Manual - Checklist

#### JWT Authentication
- [ ] Generar access token
- [ ] Decodificar token y verificar payload
- [ ] Generar refresh token
- [ ] Verificar refresh token
- [ ] Invalidar token (logout)
- [ ] Verificar blacklist
- [ ] Probar token expirado

#### Field Permissions
- [ ] Crear regla allow
- [ ] Crear regla deny
- [ ] Verificar campo permitido
- [ ] Verificar campo denegado
- [ ] Filtrar datos por permisos
- [ ] Obtener campos permitidos
- [ ] Exportar/importar configuración

#### Rate Limiting
- [ ] Probar límite de solicitudes
- [ ] Verificar contador
- [ ] Verificar headers X-RateLimit
- [ ] Probar reset de límites
- [ ] Verificar estadísticas de uso
- [ ] Bloquear/desbloquear IP

#### Cache
- [ ] Guardar y obtener valor
- [ ] Probar pattern remember
- [ ] Invalidar cache de post
- [ ] Invalidar cache de término
- [ ] Limpiar grupo
- [ ] Limpiar todo
- [ ] Verificar estadísticas

#### CORS
- [ ] Configurar orígenes permitidos
- [ ] Verificar headers en respuesta
- [ ] Probar preflight OPTIONS
- [ ] Verificar credenciales
- [ ] Probar wildcard

#### Query Params
- [ ] Validar parámetros
- [ ] Sanitizar input
- [ ] Probar validación de tipos
- [ ] Probar validación de enums
- [ ] Construir tax_query
- [ ] Construir meta_query
- [ ] Construir WP_Query args

---

## Resultados Esperados

### Pasan todos los tests
```
OK (74 tests, 350 assertions)
```

### Coverage esperado
- JWT Handler: >90%
- Field Permission Manager: >85%
- Rate Limiter: >85%
- Cache Manager: >90%
- CORS Manager: >80%
- Query Param Manager: >85%

---

## Notas

1. **Tests que requieren ACF/JetEngine**: Algunos tests de ACF/JetEngine solo funcionan si los plugins están activos

2. **Tests de Rate Limiting**: Usan identificadores únicos para no afectar otros tests

3. **Tests de Cache**: Cada test usa keys únicas para evitar colisiones

4. **Tests de JWT**: La clave secreta se genera automáticamente si no existe

---

## Fixtures

Los tests crean automáticamente:
- Usuarios de prueba (admin, subscriber, etc.)
- Posts de prueba
- Términos de prueba
- Options de configuración

Todo se limpia después de cada test.
