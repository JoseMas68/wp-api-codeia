# Plan de Implementación - WP API Codeia

**Estrategia de Branching:**
```
main (intocable)
  ↑
  │ (solo con tag + release)
  │
development (integración)
  ↑
  │ (merge al finalizar sprint)
  │
sprint/1, sprint/2, sprint/3, sprint/4
  ↑
  │ (merge durante desarrollo)
  │
feature/xxxx (ramas temporales)
```

---

## Sprint 1: MVP - Fundamentos del Plugin

**Objetivo:** Plugin funcional con características esenciales para validar la arquitectura.

**Rama:** `sprint/1`
**Duración estimada:** 4-6 semanas

### Tareas del Sprint 1

#### 1.1 Estructura Base y Bootstrapping
- [ ] Crear estructura modular de carpetas
- [ ] Implementar autoloader de clases
- [ ] Configurar constants globales (version, paths, urls)
- [ ] Implementar hooks de activación/desactivación
- [ ] Crear sistema de configuración (wp_options)

#### 1.2 Detección de Custom Post Types
- [ ] Implementar `CPT_Detector`
- [ ] Registrar hook `init` (priority 999)
- [ ] Detectar post types nativos y custom
- [ ] Extraer labels, capabilities, supports
- [ ] Cachear resultados en transients (12h)
- [ ] Invalidar cache al registrar/desregistrar CPT

#### 1.3 Sistema de Configuración JSON
- [ ] Definir esquema JSON de configuración
- [ ] Implementar `Config_Manager`
- [ ] Crear interfaz para guardar/recuperar config
- [ ] Validar configuración con JSON Schema
- [ ] Sistema de versioning de config
- [ ] Migraciones automáticas de config

#### 1.4 Gestión de Endpoints Básicos
- [ ] Implementar `Endpoint_Manager`
- [ ] Crear `Endpoint_Factory`
- [ ] Sistema de rewrite rules dinámicas
- [ ] Registrar query vars custom
- [ ] Routing básico de requests
- [ ] Response formatter (JSON)

#### 1.5 Autenticación API Key
- [ ] Crear tabla `wp_api_codeia_auth_keys`
- [ ] Implementar `API_Key_Strategy`
- [ ] Generación de API keys (32 chars, prefijo wpck_)
- [ ] Hash de keys con `password_hash()`
- [ ] Validación de headers Authorization
- [ ] Interfaz admin para gestionar keys

#### 1.6 Permisos Básicos
- [ ] Implementar `Permission_Manager`
- [ ] Matriz de permisos por rol
- [ ] Validación por endpoint
- [ ] Validación por método HTTP
- [ ] Integración con `current_user_can()`

#### 1.7 Dashboard Admin (Básico)
- [ ] Menú principal en admin
- [ ] Listado de endpoints
- [ ] Crear/Editar endpoint simple
- [ ] Selector de post types
- [ ] Selector de campos básicos
- [ ] Configuración de auth method

#### 1.8 Generación OpenAPI Básica
- [ ] Implementar `OpenAPI_Generator`
- [ ] Generar esquema desde config
- [ ] Endpoint `/api/v1/docs` (JSON)
- [ ] Actualización automática al guardar config

#### 1.9 Logging Básico
- [ ] Crear tabla `wp_api_codeia_logs`
- [ ] Log de requests (endpoint, método, status)
- [ ] Log de errores
- [ ] Visor de logs en admin
- [ ] Retención de 30 días

### Entregables Sprint 1
- Plugin activable con estructura modular
- Detección automática de CPTs
- Endpoints GET básicos para post types
- Autenticación vía API Key
- Permisos por rol
- OpenAPI docs auto-generadas
- Dashboard admin funcional
- Logs de requests

---

## Sprint 2: Características Core

**Objetivo:** Funcionalidades adicionales importantes para producción.

**Rama:** `sprint/2` (branch desde `development` después de sprint/1)
**Duración estimada:** 4-6 semanas

### Tareas del Sprint 2

#### 2.1 Detección Avanzada de Campos
- [ ] `ACF_Detector` - Detectar field groups y campos
- [ ] `JetEngine_Detector` - Detectar campos de JetEngine
- [ ] `Meta_Field_Detector` - Meta fields nativos
- [ ] `Taxonomy_Detector` - Taxonomías asociadas
- [ ] `Field_Registry` - Registro consolidado
- [ ] Invalidación de cache al cambiar campos

#### 2.2 JWT Authentication
- [ ] Instalar/librería JWT (firebase/php-jwt)
- [ ] Implementar `JWT_Strategy`
- [ ] Generar secret key en instalación
- [ ] Payload estándar (iss, aud, iat, exp, sub, scope)
- [ ] Endpoints: `/api/v1/auth/login`, `/refresh`, `/logout`
- [ ] Blacklist de tokens revocados
- [ ] Configuración de expiración

#### 2.3 Field-Level Permissions
- [ ] Sistema de permisos por campo
- [ ] Configuración de campos sensibles
- [ ] Filtro de response basado en permisos
- [ ] Herencia de permisos
- [ ] Override de permisos
- [ ] Integración con matriz de permisos

#### 2.4 Rate Limiting
- [ ] Implementar `Rate_Limiter`
- [ ] Ventana deslizante (sliding window)
- [ ] Almacenamiento en transients/object cache
- [ ] Rate limit por endpoint/API key/user/IP
- [ ] Headers: X-RateLimit-Limit, -Remaining, -Reset
- [ ] Configuración de límites
- [ ] Response 429 cuando excede

#### 2.5 Caching System
- [ ] Implementar `Cache_Manager`
- [ ] Cachear responses GET
- [ ] TTL configurable por endpoint
- [ ] Invalidation automática:
  - `save_post` → invalidar post
  - `delete_post` → invalidar post
  - `acf/update_value` → invalidar campos
- [ ] Integración con object cache (Redis/Memcached)
- [ ] Lazy loading de campos pesados

#### 2.6 CORS Manager
- [ ] Implementar `CORS_Manager`
- [ ] Configuración por endpoint
- [ ] Headers: Allow-Origin, Allow-Methods, Allow-Headers
- [ ] Soporte de credenciales
- [ ] Preflight OPTIONS
- [ ] Wildcards y dominios específicos

#### 2.7 Media Upload
- [ ] Endpoint `POST /api/v1/media`
- [ ] Validación MIME types
- [ ] Validación de tamaño
- [ ] Generación de nombres únicos
- [ ] `wp_handle_upload()`
- [ ] Asociación automática a post
- [ ] Response con URLs de tamaños

#### 2.8 Query Params Custom
- [ ] Definir params permitidos por endpoint
- [ ] Mapeo a meta_query
- [ ] Mapeo a tax_query
- [ ] Validación de valores
- [ ] Params por defecto
- [ ] Sanitización

#### 2.9 Dashboard Avanzado
- [ ] Gestor de API Keys (CRUD)
- [ ] Gestor de permisos (matriz visual)
- [ ] Configuración de rate limiting
- [ ] Configuración de CORS
- [ ] Visor de cache
- [ ] Configuración de media
- [ ] Swagger UI integrada

### Entregables Sprint 2
- Detección completa de campos (ACF, JetEngine, nativos)
- Autenticación JWT con tokens
- Permisos a nivel de campo
- Rate limiting configurables
- Sistema de caching robusto
- Control de CORS
- Subida de media vía API
- Query params customizables
- Dashboard completo de gestión

---

## Sprint 3: Características Avanzadas

**Objetivo:** Funcionalidades enterprise y mejora de UX.

**Rama:** `sprint/3` (branch desde `development` después de sprint/2)
**Duración estimada:** 6-8 semanas

### Tareas del Sprint 3

#### 3.1 Versionado de Endpoints
- [ ] Sistema de versionado (v1, v2, v3)
- [ ] Coexistencia de versiones
- [ ] Headers de deprecación
- [ ] Guía de migración v1→v2
- [ ] Backward compatibility
- [ ]生命周期管理 (12 meses)

#### 3.2 Application Passwords (WP Nativo)
- [ ] Integración con `wp_get_application_passwords()`
- [ ] Validación de app passwords
- [ ] Interfaz de gestión
- [ ] Logs de uso
- [ ] Revocación

#### 3.3 OAuth2 (Conceptual/Preparación)
- [ ] Estructura para OAuth2
- [ ] Tablas: clients, access_tokens, refresh_tokens
- [ ] Endpoints: `/oauth/authorize`, `/token`, `/revoke`
- [ ] Authorization Code flow
- [ ] Client Credentials flow
- [ ] Preparado para versión PRO

#### 3.4 Documentation Avanzada
- [ ] Swagger UI completa
- [ ] Examples en OpenAPI
- [ ] Schemas detallados
- [ ] Actualización en tiempo real
- [ ] Export de especificación
- [ ] Integración en admin

#### 3.5 Logging Avanzado
- [ ] Log de intentos fallidos de auth
- [ ] Log de errores con stack trace
- [ ] Log de performance (response time)
- [ ] Métricas: requests por día, top endpoints
- [ ] Export de logs (CSV)
- [ ] Dashboard analítico

#### 3.6 Sanitization Avanzada
- [ ] Schema validation (JSON Schema)
- [ ] Validación antes de WP_Query
- [ ] Sanitización por tipo de dato
- [ ] Response 400 con detalles de error
- [ ] Integración con `sanitize_*` functions

#### 3.7 Enumeración Protection
- [ ] 404 genérico para endpoints no existentes
- [ ] No exponer lista de usuarios
- [ ] No exponer lista de CPTs sin auth
- [ ] Mensajes de error genéricos
- [ ] Rate limiting para enumeración

#### 3.8 Performance Optimization
- [ ] Object cache para config
- [ ] Object cache para field registry
- [ ] Lazy loading de campos pesados
- [ ] Optimización de queries
- [ ] Bulk operations
- [ ] Response compression

#### 3.9 Dashboard Mejoras UX
- [ ] React o alternativa ligera para UI
- [ ] Wizard de configuración inicial
- [ ] Visualizador de endpoints (interactivo)
- [ ] Builder de permisos visual
- [ ] Preview de responses
- [ ] Import/Export de config
- [ ] Health check del plugin

### Entregables Sprint 3
- Versionado completo de endpoints
- Application Passwords integrado
- OAuth2 preparado (estructura)
- Documentación Swagger UI completa
- Logging avanzado con métricas
- Sanitización robusta
- Protección contra enumeración
- Optimización de performance
- Dashboard con UX mejorada

---

## Sprint 4: Enterprise y PRO

**Objetivo:** Funcionalidades premium y preparación para SaaS.

**Rama:** `sprint/4` (branch desde `development` después de sprint/3)
**Duración estimada:** 8+ semanas

### Tareas del Sprint 4

#### 4.1 OAuth2 Completo
- [ ] Implementación completa de OAuth2
- [ ] Integration con librería OAuth2
- [ ] Authorization Code flow
- [ ] Implicit flow
- [ ] Client Credentials flow
- [ ] Refresh Token flow
- [ ] Scope management

#### 4.2 Webhooks
- [ ] Sistema de webhooks
- [ ] Eventos: post.created, post.updated, user.created, etc.
- [ ] Configuración de endpoints webhook
- [ ] Reintentos automáticos
- [ ] Logs de delivery
- [ ] Signature verification

#### 4.3 Multi-Tenant Avanzado
- [ ] Configuración por-site en multisite
- [ ] API Keys globales vs por-site
- [ ] Rate limiting compartido vs aislado
- [ ] Logs segregados
- [ ] Dashboard de red

#### 4.4 Analytics
- [ ] Métricas detalladas de uso
- [ ] Gráficos de tendencias
- [ ] Top usuarios, endpoints, errores
- [ ] Bandwidth utilizado
- [ ] Export de datos
- [ ] Alertas configurables

#### 4.5 GraphQL (Opcional)
- [ ] Integración con GraphQL API
- [ ] Schema generator
- [ ] Query/Mutation builders
- [ ] Integración con permisos
- [ ] Documentación auto-generada

#### 4.6 API Gateway (Opcional)
- [ ] Proxy para múltiples instancias WP
- [ ] Rate limiting global
- [ ] Analytics centralizados
- [ ] Load balancing
- [ ] Caching distribuido

#### 4.7 Monetización (Preparación)
- [ ] Sistema de licencias
- [ ] Tier de funcionalidades
- [ ] Integración con WooCommerce/EDD
- [ ] API de licenciamiento
- [ - Validación de licencias

#### 4.8 Testing Suite Completa
- [ ] Unit tests (PHPUnit)
- [ ] Integration tests (WP test suite)
- [ ] E2E tests (Playwright)
- [ ] Load tests (k6)
- [ ] CI/CD pipeline
- [ ] Code coverage >80%

#### 4.9 Documentación Pública
- [ ] Docs para desarrolladores
- [ ] Guías de inicio rápido
- [ ] Ejemplos de uso
- [ ] Referencia de API
- [ ] Video tutoriales

### Entregables Sprint 4
- OAuth2 completamente funcional
- Sistema de webhooks
- Multi-tenant avanzado
- Analytics y métricas
- Preparación para SaaS
- Suite completa de tests
- Documentación pública
- Listo para producción enterprise

---

## Plan de Testing

### Testing al Final de Cada Sprint

#### Sprint 1 Tests
- [ ] Unit tests de detectores
- [ ] Unit tests de auth API Key
- [ ] Integration tests de endpoints
- [ ] Integration tests de permisos
- [ ] E2E tests de dashboard básico
- [ ] Manual testing de OpenAPI docs
- [ ] Load testing básico (100 req/s)

#### Sprint 2 Tests
- [ ] Unit tests de detectores ACF/JetEngine
- [ ] Unit tests de JWT auth
- [ ] Integration tests de field permissions
- [ ] Integration tests de rate limiting
- [ ] Integration tests de caching
- [ ] Integration tests de CORS
- [ ] E2E tests de dashboard completo
- [ ] Load testing (500 req/s)

#### Sprint 3 Tests
- [ ] Unit tests de versioning
- [ ] Integration tests de migración v1→v2
- [ ] Integration tests de OAuth2 (parcial)
- [ ] E2E tests de dashboard avanzado
- [ ] Security testing (enumeración, XSS, SQLi)
- [ ] Performance testing (response time <200ms)
- [ ] Load testing (1000 req/s)

#### Sprint 4 Tests
- [ ] Unit tests completos (>80% coverage)
- [ ] Integration tests completos
- [ ] E2E tests completos
- [ ] Load testing exhaustivo
- [ ] Security audit
- [ ] Penetration testing
- [ ] Multi-site testing
- [ ] Stress testing

---

## Resumen de Entregables por Versión

### v1.0.0 (Sprint 1 → Development → Main)
- MVP funcional
- Detección de CPTs
- API Key auth
- Permisos básicos
- OpenAPI docs
- Dashboard básico

### v1.1.0 (Sprint 2 → Development → Main)
- ACF/JetEngine detection
- JWT auth
- Field-level permissions
- Rate limiting
- Caching
- CORS
- Media upload

### v1.2.0 (Sprint 3 → Development → Main)
- Versioning
- Application Passwords
- OAuth2 (prep)
- Swagger UI
- Advanced logging
- Performance optimized

### v2.0.0 (Sprint 4 → Development → Main)
- OAuth2 completo
- Webhooks
- Multi-tenant avanzado
- Analytics
- Enterprise ready
- SaaS ready

---

## Convenciones de Desarrollo

### Branching
```bash
# Crear rama de sprint
git checkout development
git pull origin development
git checkout -b sprint/1

# Crear rama de feature
git checkout sprint/1
git checkout -b feature/cpt-detector

# Merge feature a sprint
git checkout sprint/1
git merge feature/cpt-detector
git branch -d feature/cpt-detector

# Al finalizar sprint
git checkout development
git merge sprint/1
git push origin development
git branch -d sprint/1

# Crear release (solo cuando lo pida el usuario)
git checkout development
git checkout -b release/v1.0.0
# [bump version, changelog]
git checkout main
git merge release/v1.0.0 --no-ff
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin main --tags
git branch -d release/v1.0.0
```

### Commits
```
feat: añadir detector de CPTs
fix: corregir validación de API keys
docs: actualizar README de instalación
refactor: optimizar cache manager
test: añadir tests de auth
chore: actualizar dependencias
```

### Code Review
- Todo código pasa por PR
- Requerida aprobación para merge a sprint
- Requerida aprobación para merge a development
- Tests deben pasar antes de merge

---

## Próximos Pasos

1. **Iniciar Sprint 1:** Crear rama `sprint/1` desde `main`
2. **Primera Feature:** `feature/estructura-base` con bootstrapping
3. **Secuencia:** Desarrollar features → merge a sprint → tests → merge a development

¿Deseas comenzar con el Sprint 1?
