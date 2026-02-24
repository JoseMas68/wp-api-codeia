# WP API Codeia - Documentación Arquitectónica

**Versión:** 1.0.0
**Fecha:** 2025-02-24
**Nivel:** Senior/Arquitecto
**Stack:** WordPress 6.x | PHP 8.2+ | REST API | OpenAPI 3.0

---

## 1. Visión General del Plugin

### 1.1 Propósito y Propuesta de Valor

WP API Codeia es un plugin de arquitectura enterprise que transforma WordPress en una APIheadless configurable, versionada y completamente gobernable desde un dashboard administrativo. A diferencia de la REST API nativa de WordPress, este plugin ofrece:

- **Configuración visual sin código:** Exposición selectiva de datos mediante interfaz gráfica
- **Detección automática de esquemas:** Reconocimiento de CPTs, taxonomías, meta fields, ACF y JetEngine
- **Versionado de endpoints:** Soporte para v1, v2, etc. con backward compatibility
- **Autenticación múltiple:** API Keys, JWT, OAuth2, Application Passwords, Basic Auth
- **Permisos granulares:** Control a nivel de rol, capability, endpoint, método y campo
- **Documentación automática:** Generación de OpenAPI 3.0 actualizada en tiempo real
- **Gobernanza centralizada:** Dashboard unificado para gestión de API

### 1.2 Problema que Resuelve

**Problemas actuales del ecosistema WordPress:**

1. **REST API nativo es todo-o-nada:** Expone todos los campos o requiere código personalizado para filtrar
2. **No hay versionado:** Cambios en la estructura rompen clientes existentes
3. **Autenticación limitada:** Solo cookies o Application Passwords (sin API Keys nativas)
4. **Sin documentación dinámica:** Especificación OpenAPI requiere mantenimiento manual
5. **Detección manual de CPTs:** Desarrolladores deben mapear manualmente cada post type
6. **ACF/JetEngine invisibles:** Campos de estos plugins no están expuestos estructuralmente
7. **Sin gobernanza centralizada:** Cada modificación requiere código en functions.php o plugins custom

**Solución WP API Codeia:**

Una capa de abstracción entre WordPress y el consumidor de la API que permite:
- Definir contratos API explícitos y versionados
- Controlar finamente qué datos se exponen
- Documentar automáticamente las especificaciones
- Gestionar autenticación y autorización centralizadamente
- Monitorear y auditar el uso de la API

### 1.3 Diferenciadores vs REST API Nativo de WordPress

| Aspecto | REST API Nativo | WP API Codeia |
|---------|----------------|---------------|
| **Exposición de datos** | Todo o nada (con filtros PHP) | Selección visual por campo |
| **Versionado** | No implementado | v1, v2, etc. |
| **Autenticación** | Cookies, App Passwords | API Keys, JWT, OAuth2, etc. |
| **Documentación** | Manual o plugins externos | Auto-generada OpenAPI 3.0 |
| **Detección ACF/JetEngine** | Requiere código | Automática |
| **Field-level permissions** | No disponible | Por campo y rol |
| **Rate limiting** | No nativo | Configurable |
| **Gobernanza** | Dispersa en código | Dashboard centralizado |
| **CORS** | Requiere código | Configurable por endpoint |
| **Query params** | Fijos | Configurables |

---

## 2. Casos de Uso

### 2.1 Headless CMS

**Escenario:** Empresa con frontend en Next.js/React/Vue que consume WordPress como backend.

**Necesidades:**
- Endpoints optimizados con solo campos necesarios
- Versionado para no romper el frontend en cambios
- Autenticación vía API Keys para servicios internos
- Documentación para equipo frontend

**Solución WP API Codeia:**
- Definir endpoints /api/v1/products con solo título, precio, stock
- Versionado permite migrar a v2 sin afectar frontend actual
- API Key para lectura pública
- Documentación auto-generada para desarrolladores

### 2.2 Integraciones con Terceros

**Escenario:** ERP o CRM que necesita sincronizar datos con WordPress.

**Necesidades:**
- Endpoint POST/PUT para recibir datos
- Autenticación segura (JWT o OAuth2)
- Validación de esquema
- Logs de auditoría

**Solución WP API Codeia:**
- Endpoint /api/v1/erp-sync con autenticación JWT
- Validación de campos requeridos
- Logs de requests y errores
- Rate limiting para prevenir abusos

### 2.3 Mobile Apps

**Escenario:** App iOS/Android que consume contenido de WordPress.

**Necesidades:**
- Endpoints optimizados para mobile (payload mínimo)
- Imágenes con múltiples tamaños
- Autenticación de usuarios
- Caching agresivo

**Solución WP API Codeia:**
- Endpoints con solo campos necesarios para mobile
- Field-level permissions para datos sensibles
- Caching con transients
- Rate limiting por usuario

### 2.4 Microservicios

**Escenario:** Arquitectura de microservicios donde WordPress es uno más.

**Necesidades:**
- Contratos API estrictos
- Versionado controlado
- Documentación OpenAPI
- Autenticación servicio-a-servicio

**Solución WP API Codeia:**
- Endpoints versionados y documentados
- API Keys para servicio-a-servicio
- Esquemas validados
- Logs centralizados

### 2.5 SaaS Basado en WordPress

**Escenario:** Multi-tenant donde cada cliente tiene su data aislada.

**Necesidades:**
- Endpoints por sitio (multisite)
- Cuotas y rate limiting
- Permisos por cliente
- Métricas de uso

**Solución WP API Codeia:**
- Soporte multisite nativo
- Rate limiting configurable
- API Keys por cliente
- Dashboard de métricas

---

## 3. Arquitectura Modular

### 3.1 Patrones de Diseño Principales

#### 3.1.1 Strategy Pattern
**Uso:** Sistema de autenticación y permisos.

**Razón:** Permite intercambiar métodos de autenticación (API Key, JWT, OAuth2) sin modificar el core del plugin.

**Implementación:**
- Interface `Auth_Strategy_Interface` con método `authenticate()`
- Clases concretas: `API_Key_Strategy`, `JWT_Strategy`, `OAuth2_Strategy`
- Context: `Auth_Manager` que selecciona la estrategia basado en config

#### 3.1.2 Factory Pattern
**Uso:** Creación de endpoints y detectores de campos.

**Razón:** Abstracte la creación de objetos complejos (endpoints con diferentes configuraciones).

**Implementación:**
- `Endpoint_Factory` crea endpoints basados en configuración JSON
- `Field_Detector_Factory` retorna detector apropiado (ACF, JetEngine, nativo)

#### 3.1.3 Repository Pattern
**Uso:** Abstracción de acceso a datos.

**Razón:** Desacoplar lógica de negocio de acceso directo a base de datos WP.

**Implementación:**
- `Endpoint_Repository`: CRUD de configuración de endpoints
- `Auth_Repository`: Gestión de API Keys, tokens
- `Log_Repository`: Almacenamiento y consulta de logs

#### 3.1.4 Observer Pattern
**Uso:** Actualización de documentación y cache invalidation.

**Razón:** Múltiples reacciones a un evento (guardar endpoint → actualizar docs, limpiar cache, regenerar rewrite rules).

**Implementación:**
- Hooks de WordPress: `wp_api_codeia_endpoint_saved`
- Observadores: `Docs_Generator`, `Cache_Manager`, `Rewrite_Rules_Manager`

#### 3.1.5 Middleware Pattern
**Uso:** Pipeline de procesamiento de requests.

**Razón:** Cadena de responsabilidades (auth → permissions → rate limiting → sanitization → execution).

**Implementación:**
- `Request_Middleware_Pipeline`
- Middlewares: `Auth_Middleware`, `Permission_Middleware`, `Rate_Limit_Middleware`, etc.

### 3.2 Módulos y Responsabilidades

```
WP API Codeia Core
│
├── Bootstrapper
│   └── Inicialización del plugin, registro de hooks, carga de módulos
│
├── Config
│   ├── Config_Manager: Gestión de configuración global
│   ├── Config_Storage: Persistencia (wp_options o custom tables)
│   └── Config_Validator: Validación de esquemas de configuración
│
├── Detector
│   ├── CPT_Detector: Detección de Custom Post Types
│   ├── Taxonomy_Detector: Detección de taxonomías
│   ├── Meta_Field_Detector: Detección de meta fields nativos
│   ├── ACF_Detector: Detección de campos ACF
│   ├── JetEngine_Detector: Detección de campos JetEngine
│   └── Field_Registry: Registro consolidado de todos los campos
│
├── Endpoint
│   ├── Endpoint_Manager: Registro y gestión de endpoints
│   ├── Endpoint_Factory: Creación de endpoints desde config
│   ├── Endpoint_Version_Manager: Gestión de versiones
│   ├── Rewrite_Rules_Manager: Gestión de rewrite rules dinámicas
│   └── Query_Param_Manager: Validación de query params
│
├── Auth
│   ├── Auth_Manager: Orquestador de autenticación
│   ├── Strategies
│   │   ├── Auth_Strategy_Interface
│   │   ├── API_Key_Strategy
│   │   ├── JWT_Strategy
│   │   ├── OAuth2_Strategy
│   │   ├── Basic_Auth_Strategy
│   │   └── App_Password_Strategy
│   └── Token_Manager: Gestión de tokens JWT/OAuth
│
├── Permission
│   ├── Permission_Manager: Validación de permisos
│   ├── Role_Permission: Permisos por rol
│   ├── Capability_Permission: Permisos por capability
│   ├── Endpoint_Permission: Permisos por endpoint
│   ├── Method_Permission: Permisos por método HTTP
│   └── Field_Permission: Permisos a nivel de campo
│
├── Media
│   ├── Media_Uploader: Gestión de subida vía API
│   ├── MIME_Validator: Validación de tipos MIME
│   ├── Size_Validator: Validación de tamaños
│   └── Media_Associator: Asociación automática a posts
│
├── Documentation
│   ├── OpenAPI_Generator: Generación de especificación OpenAPI 3.0
│   ├── Swagger_UI: Interfaz de documentación
│   └── Docs_AutoUpdater: Actualización automática
│
├── Security
│   ├── Rate_Limiter: Rate limiting por endpoint/API key
│   ├── Sanitization_Manager: Sanitización de inputs
│   ├── Nonce_Manager: Gestión de nonces
│   ├── CORS_Manager: Control de CORS
│   └── Enumeration_Protection: Protección contra enumeración
│
├── Performance
│   ├── Cache_Manager: Gestión de cache (transients, object cache)
│   ├── Cache_Invalidation: Limpieza automática de cache
│   └── Lazy_Loader: Lazy loading de campos pesados
│
├── Logging
│   ├── Request_Logger: Log de requests
│   ├── Error_Logger: Log de errores
│   ├── Auth_Logger: Log de intentos de autenticación
│   └── Log_Viewer: Visor de logs en admin
│
└── Admin
    ├── Dashboard: Dashboard principal
    ├── Endpoint_Admin: Gestión de endpoints
    ├── Auth_Admin: Gestión de autenticación
    ├── Permission_Admin: Gestión de permisos
    ├── Media_Admin: Configuración de media
    ├── Docs_Admin: Visor de documentación
    ├── Logs_Admin: Visor de logs
    └── Settings_Admin: Configuración general
```

### 3.3 Diagrama de Arquitectura (Texto)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLIENTE API                                    │
│                     (Web, Mobile, Terceros)                             │
└────────────────────────────┬────────────────────────────────────────────┘
                             │
                             │ HTTP Request
                             ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        WORDPRESS CORE                                    │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │                         WP API CODEIA                             │  │
│  │                                                                   │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  REQUEST MIDDLEWARE PIPELINE                                │  │  │
│  │  │  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────┐  │  │  │
│  │  │  │   CORS    │→ │Rate Limit │→ │  Auth     │→ │Permission│  │  │  │
│  │  │  │  Check    │  │  Check    │  │  Check    │  │  Check   │  │  │  │
│  │  │  └───────────┘  └───────────┘  └───────────┘  └─────────┘  │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  │                              │                                      │  │
│  │                              ▼                                      │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  ENDPOINT ROUTER                                            │  │  │
│  │  │  /api/v1/{endpoint} ────────────────────┐                   │  │  │
│  │  │  /api/v2/{endpoint} ─────────────┐      │                   │  │  │
│  │  │  /docs ────────────────┐         │      │                   │  │  │
│  │  └────────────────────────│─────────│──────│───────────────────┘  │  │
│  │                           │         │      │                     │  │
│  │  ┌────────────────────────▼─────────▼──────▼───────────────────┐ │  │
│  │  │  REQUEST PROCESSOR                                         │ │  │
│  │  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │ │  │
│  │  │  │   Query     │  │   Field     │  │     Response        │  │ │  │
│  │  │  │  Builder    │→ │  Selector   │→ │     Formatter       │  │ │  │
│  │  │  └─────────────┘  └─────────────┘  └─────────────────────┘  │ │  │
│  │  └─────────────────────────────────────────────────────────────┘ │  │
│  │                              │                                      │  │
│  │                              ▼                                      │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  DATA LAYER                                                 │  │  │
│  │  │  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────┐  │  │  │
│  │  │  │ WP_Query  │  │  ACF      │  │JetEngine  │  │  Cache  │  │  │  │
│  │  │  │   Data    │  │  Data     │  │   Data    │  │  Layer  │  │  │  │
│  │  │  └───────────┘  └───────────┘  └───────────┘  └─────────┘  │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  │                              │                                      │  │
│  │                              ▼                                      │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  RESPONSE PROCESSOR                                         │  │  │
│  │  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │  │  │
│  │  │  │   Field     │  │   Schema    │  │     Headers         │  │  │  │
│  │  │  │  Filter     │→ │  Validator  │→ │     Setter          │  │  │  │
│  │  │  └─────────────┘  └─────────────┘  └─────────────────────┘  │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  │                                                                   │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  ASYNC PROCESSES                                            │  │  │
│  │  │  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────┐  │  │  │
│  │  │  │   Logging │  │   Docs    │  │   Cache   │  │ Metrics │  │  │  │
│  │  │  │   Update  │  │   Update  │  │   Refresh │  │Collect  │  │  │  │
│  │  │  └───────────┘  └───────────┘  └───────────┘  └─────────┘  │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  │                                                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │  WP ADMIN DASHBOARD                                               │ │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌────────┐  │ │
│  │  │Endpoint │  │  Auth   │  │Permission│  │  Docs   │  │  Logs  │  │ │
│  │  │ Manager │  │ Manager │  │ Manager  │  │ Viewer  │  │ Viewer │  │ │
│  │  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └────────┘  │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Flujo Completo de Request

### 4.1 Secuencia de Request (Paso a Paso)

```
1. CLIENTE INICIA REQUEST
   │
   ├─ Método: GET/POST/PUT/DELETE/PATCH
   ├─ URL: /api/v1/custom-endpoint
   ├─ Headers: Authorization, Content-Type, Origin
   └─ Body: JSON (para POST/PUT)

2. WORDPRESS BOOTSTRAP
   │
   ├─ wp-load.php
   ├─ wp-settings.php
   └─ Plugins_loaded hook

3. WP API CODEIA BOOTSTRAP
   │
   ├─ Carga de configuración
   ├─ Registro de rewrite rules
   └─ Inicialización de módulos

4. MIDDLEWARE PIPELINE (Ejecución secuencial)
   │
   ├─ 4.1 CORS CHECK
   │   ├─ Verifica header Origin
   │   ├─ Compara con configuración permitida
   │   ├─ Setea headers CORS si válido
   │   └─ Retorna 403 si origen no autorizado
   │
   ├─ 4.2 RATE LIMITING CHECK
   │   ├─ Identifica cliente (IP, API Key, User ID)
   │   ├─ Consulta contador en transients/object cache
   │   ├─ Compara con límite configurado
   │   ├─ Retorna 429 si excede
   │   └─ Incrementa contador si válido
   │
   ├─ 4.3 AUTHENTICATION CHECK
   │   ├─ Detecta método de auth desde config endpoint
   │   ├─ Ejecuta estrategia apropiada:
   │   │   ├─ API Key: Valida contra DB
   │   │   ├─ JWT: Valida firma y expiración
   │   │   ├─ OAuth2: Valida access token
   │   │   ├─ Basic: Valida credenciales (dev only)
   │   │   └─ App Password: Valida contra WP users
   │   ├─ Establece contexto de autenticación
   │   └─ Retorna 401 si falla
   │
   └─ 4.4 PERMISSION CHECK
       ├─ Obtiene rol/capability del usuario autenticado
       ├─ Valida acceso al endpoint específico
       ├─ Valida método HTTP permitido
       ├─ Valida field-level permissions
       └─ Retorna 403 si no autorizado

5. ENDPOINT ROUTING
   │
   ├─ Parsea URL: /api/v1/{endpoint}
   ├─ Extrae versión (v1, v2)
   ├─ Busca configuración del endpoint
   ├─ Valida que el endpoint existe
   └─ Retorna 404 si no existe

6. REQUEST VALIDATION
   │
   ├─ Valida método HTTP permitido
   ├─ Valida query params permitidos
   ├─ Valida content-type
   ├─ Valida body contra esquema (si POST/PUT)
   └─ Retorna 400 si validación falla

7. QUERY BUILDING
   │
   ├─ Construye WP_Query args desde config endpoint
   ├─ Aplica filtros de post_type
   ├─ Aplica filtros de tax_query
   ├─ Aplica filtros de meta_query
   └─ Incluye solo campos seleccionados

8. DATA RETRIEVAL
   │
   ├─ Ejecuta WP_Query
   ├─ Obtiene posts
   ├─ Obtiene meta fields nativos
   ├─ Obtiene campos ACF (si activo)
   ├─ Obtiene campos JetEngine (si activo)
   └─ Aplica field-level permissions

9. RESPONSE PROCESSING
   │
   ├─ Filtra campos según selección
   ├─ Aplica field-level permissions
   ├─ Aplica transformaciones (formatting)
   ├─ Construye respuesta JSON
   └─ Incluye metadata (pagination, counts)

10. ASYNC PROCESSES (Background)
    │
    ├─ Logging de request
    ├─ Actualización de métricas
    ├─ Cache invalidation (si necesario)
    └─ Actualización de documentación (si config cambió)

11. RESPONSE SENDING
    │
    ├─ Setea headers HTTP
    ├─ Setea status code
    ├─ Envía body JSON
    └─ Finaliza request
```

### 4.2 Decisiones en Cada Punto del Pipeline

| Punto | Decisión | Si True | Si False |
|-------|----------|---------|----------|
| **CORS** | ¿Origen permitido? | Continuar | Retornar 403 |
| **Rate Limit** | ¿Dentro del límite? | Continuar | Retornar 429 |
| **Auth** | ¿Credenciales válidas? | Continuar | Retornar 401 |
| **Permission** | ¿Tiene acceso? | Continuar | Retornar 403 |
| **Endpoint** | ¿Existe endpoint? | Continuar | Retornar 404 |
| **Method** | ¿Método permitido? | Continuar | Retornar 405 |
| **Validation** | ¿Request válido? | Continuar | Retornar 400 |
| **Data** | ¿Datos encontrados? | Retornar 200 | Retornar 404 |

---

## 5. Modelo de Configuración Interna

### 5.1 Estructura JSON de Configuración

```json
{
  "version": "1.0.0",
  "global_settings": {
    "namespace": "wp-api-codeia",
    "default_version": "v1",
    "enable_logs": true,
    "log_retention_days": 30,
    "default_rate_limit": {
      "requests": 1000,
      "window": "hour"
    },
    "cors": {
      "enabled": true,
      "allowed_origins": ["*"],
      "allowed_methods": ["GET", "POST", "PUT", "DELETE"],
      "allowed_headers": ["Authorization", "Content-Type"],
      "max_age": 86400
    }
  },
  "endpoints": {
    "posts": {
      "slug": "posts",
      "name": "Posts Endpoint",
      "description": "Publicación de posts",
      "versions": {
        "v1": {
          "post_types": ["post"],
          "methods": ["GET", "POST"],
          "auth": "api_key",
          "permissions": {
            "read": ["read", "subscriber"],
            "write": ["edit_posts", "editor"]
          },
          "fields": {
            "include": ["id", "title", "content", "excerpt", "date", "author"],
            "exclude": [],
            "custom_fields": {
              "acf": ["hero_image", "gallery"],
              "jetengine": ["metadata"],
              "native": ["views_count"]
            }
          },
          "query_params": {
            "allowed": ["page", "per_page", "search", "orderby", "order"],
            "defaults": {
              "per_page": 10
            }
          },
          "rate_limit": {
            "read": 1000,
            "write": 100
          },
          "cache": {
            "enabled": true,
            "ttl": 300
          }
        }
      }
    }
  },
  "auth": {
    "api_keys": {
      "enabled": true,
      "storage": "custom_table",
      "key_prefix": "wpck_",
      "key_length": 32
    },
    "jwt": {
      "enabled": true,
      "secret": "generated_on_install",
      "algorithm": "HS256",
      "expiration": 3600,
      "refresh_expiration": 604800
    },
    "oauth2": {
      "enabled": false,
      "provider": "custom"
    },
    "basic_auth": {
      "enabled": true,
      "environments": ["development", "staging"]
    },
    "app_passwords": {
      "enabled": true,
      "integration": "native"
    }
  },
  "permissions": {
    "roles": {
      "administrator": ["all"],
      "editor": ["read:all", "write:posts", "write:pages"],
      "author": ["read:all", "write:posts"],
      "subscriber": ["read:all"]
    },
    "field_level": {
      "sensitive_fields": {
        "email": ["administrator"],
        "phone": ["administrator", "editor"]
      }
    }
  },
  "media": {
    "upload_enabled": true,
    "allowed_mime_types": ["image/jpeg", "image/png", "image/webp"],
    "max_file_size": 5242880,
    "auto_associate": true,
    "image_sizes": ["thumbnail", "medium", "large", "full"]
  },
  "documentation": {
    "enabled": true,
    "format": "openapi_3_0",
    "endpoint": "/docs",
    "auto_update": true,
    "ui_theme": "default"
  }
}
```

### 5.2 Persistencia de Configuración

#### 5.2.1 Opción A: wp_options (Recomendado para MVP)

**Ventajas:**
- Fácil de implementar
- Integración nativa con WP
- Autoload optimization

**Desventajas:**
- Límite de tamaño (~4MB en algunas configuraciones)
- No escalable para configuraciones masivas

**Implementación:**
- `wp-api-codeia-config`: Configuración JSON completa
- `wp-api-codeia-version`: Versión actual del esquema
- `wp-api-codeia-cache`: Hash para cache invalidation

#### 5.2.2 Opción B: Custom Tables (Recomendado para Escalabilidad)

**Ventajas:**
- Escalable
- Queries optimizadas
- Indexación personalizada

**Desventajas:**
- Mayor complejidad
- Migraciones necesarias

**Tablas Propuestas:**
```sql
- wp_api_codeia_endpoints
- wp_api_codeia_endpoint_fields
- wp_api_codeia_auth_keys
- wp_api_codeia_permissions
- wp_api_codeia_logs
- wp_api_codeia_rate_limits
```

### 5.3 Versioning de Configuración

**Estrategia:**
- Versión del esquema en raíz del JSON
- Migraciones automáticas al actualizar plugin
- Backward compatibility para 2 versiones anteriores
- Config version hash para cache invalidation

**Migración:**
```json
{
  "config_version": "2.0.0",
  "previous_version": "1.0.0",
  "migrations_applied": ["migrate_1_to_2"]
}
```

---

## 6. Estrategia de Detección Automática

### 6.1 Detección de Custom Post Types

**Hooks de WordPress:**
- `init`: Todos los post types están registrados
- `registered_post_types`: Filtro para modificar lista

**Estrategia:**
1. Esperar a que `init` se ejecute con prioridad 999
2. Iterar sobre `get_post_types()`
3. Filtrar post types nativos (post, page, attachment)
4. Obtener metadatos:
   - Labels: `get_post_type_object()->labels`
   - Public: `get_post_type_object()->public`
   - Supports: `get_post_type_object()->supports`
   - Taxonomies: `get_object_taxonomies()`

**Cache:**
- Transient: `wp_api_codeia_cpt_cache` (12 horas)
- Invalidación: `registered_post_type` hook

**Consideraciones:**
- Post types de otros plugins (WooCommerce, etc.)
- Post types registrados vía código
- Post types con `publicly_queryable = false`

### 6.2 Detección de Taxonomías

**Hooks:**
- `registered_taxonomy`: Se dispara al registrar taxonomía
- `init`: Todas las taxonomías están registradas

**Estrategia:**
1. `get_taxonomies()` después de `init`
2. Filtrar taxonomías nativas (category, post_tag)
3. Obtener:
   - Tipos asociados: `get_taxonomy()->object_type`
   - Hierárquica: `get_taxonomy()->hierarchical`
   - Labels: `get_taxonomy()->labels`

**Cache:**
- Transient: `wp_api_codeia_taxonomies_cache` (12 horas)

### 6.3 Detección de Meta Fields Nativos

**Hooks:**
- `init`: Meta keys registradas
- `registered_meta_key`: Registro de meta keys

**Estrategia:**
1. `get_registered_meta_keys()` por post type
2. Filtrar meta keys del plugin (prefijo `_wp_api_codeia`)
3. Obtener:
   - Tipo: `show_in_rest`
   - Single: `single`
   - Type: `type` (string, integer, etc.)

**Cache:**
- Transient: `wp_api_codeia_meta_keys_cache` (12 horas)

### 6.4 Detección de Campos ACF

**Prerequisitos:**
- Plugin ACF activo: `function_exists('acf_get_field_groups')`

**Hooks:**
- `acf/update_field_group`: Al guardar field group
- `acf/delete_field_group`: Al eliminar field group

**Estrategia:**
1. `acf_get_field_groups()`: Obtener todos los grupos
2. Para cada grupo, `acf_get_fields()`
3. Filtrar por ubicación:
   - Post types: `acf_get_field_group()->location`
   - Taxonomías: `acf_get_field_group()->location`
4. Extraer:
   - Field name: `['name']`
   - Field type: `['type']`
   - Field label: `['label']`
   - Required: `['required']`
5. Mapear field types a tipos OpenAPI:
   - text → string
   - number → number/integer
   - true_false → boolean
   - gallery/wysiwyg → object

**Cache:**
- Transient: `wp_api_codeia_acf_cache` (6 horas)
- Invalidación: Hooks de ACF

**Consideraciones:**
- Fields condicionales (logic)
- Fields en repeaters
- Fields en flexible content
- Fields en options pages

### 6.5 Detección de Campos JetEngine

**Prerequisitos:**
- Plugin JetEngine activo

**Hooks:**
- No hay hooks documentados para detección
- Se debe consultar directamente a DB

**Estrategia:**
1. Query a tabla `wp_jet_post_types` para CPTs
2. Query a tabla `wp_jet_engine_meta` para meta fields
3. Mapear field types:
   - text → string
   - number → number
   - checkbox → boolean/array
   - media → object (ID)
   - posts → object (relationship)
4. Obtener configuración de cada field

**Cache:**
- Transient: `wp_api_codeia_jetengine_cache` (6 horas)
- Invalidación: Manual o por tiempo

**Consideraciones:**
- JetEngine no tiene hooks de actualización
- Requiere polling periódico
- Estructura de datos menos documentada

### 6.6 Registro Consolidado de Campos

**Estructura del Field Registry:**

```json
{
  "post_types": {
    "post": {
      "native_fields": {
        "ID": {"type": "integer", "description": "Post ID"},
        "post_title": {"type": "string", "description": "Title"},
        "post_content": {"type": "string", "description": "Content"}
      },
      "meta_fields": {
        "views_count": {"type": "integer", "source": "native"}
      },
      "acf_fields": {
        "hero_image": {"type": "object", "source": "acf"},
        "related_posts": {"type": "array", "source": "acf"}
      },
      "jetengine_fields": {
        "metadata": {"type": "object", "source": "jetengine"}
      },
      "taxonomies": {
        "category": {"hierarchical": true},
        "post_tag": {"hierarchical": false}
      }
    }
  }
}
```

**Invalidation Strategy:**
- Eventos que invalidan cache:
  - `save_post`: Post actualizado
  - `acf/update_field_group`: ACF actualizado
  - `registered_post_type`: CPT registrado
  - `acf_update_setting`: Settings de ACF
- TTL fallback por seguridad

---

## 7. Sistema de Endpoints Dinámicos

### 7.1 Namespace Strategy

**Propuesta:**
```
/api/{version}/{endpoint_slug}
```

**Ejemplos:**
```
/api/v1/posts
/api/v1/products
/api/v2/posts (versión mejorada)
/api/v1/users/me
```

**Razones:**
- Separación clara de versión
- Compatible con REST API nativo (`/wp-json/wp/v2/...`)
- Fácil routing

**Configuración:**
```json
{
  "global_settings": {
    "namespace": "wp-api-codeia",
    "base_path": "/api",
    "current_version": "v1",
    "supported_versions": ["v1", "v2"]
  }
}
```

### 7.2 Versioning Approach

**Tipos de Versioning:**

#### 7.2.1 URL Versioning (Recomendado)
```
/api/v1/posts
/api/v2/posts
```
- Pros: Claro, separado, compatible con OpenAPI
- Contras: Duplicación de endpoints

#### 7.2.2 Header Versioning
```
GET /api/posts
Api-Version: v2
```
- Pros: URLs limpias
- Contras: No estándar en OpenAPI, más complejo

**Estrategia de Backward Compatibility:**
- Versiones anteriores soportadas por 12 meses
- Deprecation warnings en headers
- Documentación de migración

**Estrategia de Breaking Changes:**
- Cambios en estructura de respuesta → v2
- Remoción de campos → v2
- Cambios en autenticación → v2
- Agregar campos (backward compatible) → v1

### 7.3 Generación de Rewrite Rules Dinámicas

**Hook:**
- `rewrite_rules_array`: Filtra reglas de rewrite

**Estrategia:**
1. Al guardar/actualizar endpoint en admin
2. Generar rewrite rule:
   ```
   ^api/v1/([^/]+)/?$
   → index.php?wp-api-codeia=1&version=v1&endpoint=$matches[1]
   ```
3. Agregar a `rewrite_rules_array`
4. `flush_rewrite_rules()` al guardar

**Query Vars:**
- `wp-api-codeia`: Flag de activación
- `version`: Versión de la API
- `endpoint`: Slug del endpoint
- `id`: ID del recurso (opcional)

**Ejemplo:**
```php
// Query var registration
add_filter('query_vars', function($query_vars) {
    $query_vars[] = 'wp-api-codeia';
    $query_vars[] = 'wp-api-codeia-version';
    $query_vars[] = 'wp-api-codeia-endpoint';
    return $query_vars;
});
```

**Invalidation:**
- `flush_rewrite_rules()` al actualizar endpoint
- Solo en admin, no en cada request

### 7.4 Query Params Management

**Estrategia:**
1. Definir params permitidos en configuración endpoint
2. Validar params contra whitelist
3. Sanitizar valores
4. Pasar a WP_Query

**Params Nativos Soportados:**
- `page`: Paginación
- `per_page`: Items por página
- `search`: Búsqueda
- `orderby`: Ordenación
- `order`: ASC/DESC
- `tax_query`: Filtro por taxonomía
- `meta_query`: Filtro por meta

**Params Custom:**
- Definidos en configuración endpoint
- Mapeados a meta_query o tax_query

**Ejemplo de Configuración:**
```json
{
  "query_params": {
    "allowed": [
      "page",
      "per_page",
      "search",
      "category",
      "price_min",
      "price_max"
    ],
    "defaults": {
      "per_page": 10,
      "orderby": "date"
    },
    "custom_mappings": {
      "category": {
        "type": "taxonomy",
        "taxonomy": "category"
      },
      "price_min": {
        "type": "meta",
        "key": "price",
        "compare": ">=",
        "value_type": "numeric"
      }
    }
  }
}
```

---

## 8. Sistema de Autenticación

### 8.1 Patrón Strategy para Autenticación

**Interface:**
```
Auth_Strategy_Interface
├─ authenticate(): Auth_Result
├─ get_credentials(): array
└─ validate(): bool
```

**Implementaciones:**
- `API_Key_Strategy`
- `JWT_Strategy`
- `OAuth2_Strategy`
- `Basic_Auth_Strategy`
- `App_Password_Strategy`

**Auth Manager:**
- Selecciona estrategia basado en config endpoint
- Ejecuta `authenticate()`
- Retorna `Auth_Result` con usuario o error

### 8.2 API Key Authentication

**Estrategia:**
```
Header: Authorization: Bearer {api_key}
Query Param: ?api_key={key} (no recomendado)
```

**Storage:**
- Custom Table: `wp_api_codeia_auth_keys`
- Campos:
  - id (PK)
  - api_key (hashed)
  - user_id (FK)
  - name (descriptivo)
  - scope (permisos)
  - created_at
  - expires_at
  - last_used_at
  - is_revoked

**Generación:**
- Longitud: 32 caracteres
- Prefijo: `wpck_`
- Hash: `password_hash()` (WordPress compatible)

**Validación:**
1. Extraer key del header/query param
2. Hash y buscar en DB
3. Verificar no revocada
4. Verificar expiración
5. Retornar user_id asociado

**Scope:**
- `read`: Lectura
- `write`: Escritura
- `admin`: Administración

### 8.3 JWT Authentication

**Estrategia:**
```
Header: Authorization: Bearer {jwt_token}
```

**Implementación:**
- Librería: `firebase/php-jwt` o implementación propia
- Secret key: Generada en instalación
- Algoritmo: HS256 (recomendado) o RS256 (para enterprise)

**Payload:**
```json
{
  "iss": "wp-api-codeia",
  "aud": "site-url.com",
  "iat": 1234567890,
  "exp": 1234571490,
  "sub": "user_id",
  "scope": ["read", "write"],
  "context": {
    "user_id": 1,
    "roles": ["administrator"]
  }
}
```

**Endpoints:**
- `POST /api/v1/auth/login`: Generar token
- `POST /api/v1/auth/refresh`: Renovar token
- `POST /api/v1/auth/logout`: Invalidar token

**Storage de Tokens (Opcional):**
- Blacklist para logout: Custom table o transients
- Token hash como clave
- TTL: Igual a expiración del token

### 8.4 OAuth2 (Conceptual)

**Estrategia:**
- Flujo Authorization Code
- Client credentials para servicio-a-servicio

**Endpoints:**
- `/oauth/authorize`: Autorización
- `/oauth/token`: Obtener token
- `/oauth/revoke`: Revocar token

**Storage:**
- Clients: Custom table
- Access tokens: Custom table o transients
- Refresh tokens: Custom table

**Notas:**
- Preparado para versión PRO
- Requiere librería OAuth2
- Compatible con RFC 6749

### 8.5 Basic Authentication (Dev Only)

**Estrategia:**
```
Header: Authorization: Basic {base64(username:password)}
```

**Implementación:**
- `wp_authenticate()` de WordPress
- Solo activo en WP_DEBUG = true
- Nunca en producción

**Validación:**
1. Decodificar base64
2. Extraer username:password
3. `wp_signon()`
4. Retornar usuario si válido

### 8.6 Application Passwords (Nativo WP)

**Estrategia:**
- Integración con `wp_get_application_passwords()`
- Disponible desde WP 5.6

**Implementación:**
- Header: Authorization: Basic {base64(username:app_password)}
- Validación nativa de WP
- Integración con user_id

### 8.7 Contexto de Autenticación

**Establecimiento:**
- `wp_set_current_user($user_id)`
- `wp_set_auth_cookie($user_id)` (opcional)
- `current_user_can()` funciona nativamente

**Cleanup:**
- `wp_set_current_user(0)` al finalizar request
- Evitar contaminación entre requests

---

## 9. Sistema de Permisos

### 9.1 Matriz de Permisos

**Estructura:**
```
Permiso = Rol + Endpoint + Método + Campo
```

**Dimensiones:**
1. **Rol WordPress:** Administrator, Editor, Author, Contributor, Subscriber, Custom Roles
2. **Endpoint:** /api/v1/posts, /api/v1/products, etc.
3. **Método:** GET, POST, PUT, DELETE, PATCH
4. **Campo:** title, content, meta_field, etc.

### 9.2 Validación de Permisos

**Pipeline:**
1. Obtener usuario autenticado
2. Obtener roles/capabilities del usuario
3. Consultar matriz de permisos
4. Validar acceso al endpoint
5. Validar método HTTP
6. Validar field-level permissions
7. Retornar permitido/denegado

**Ejemplo de Configuración:**
```json
{
  "permissions": {
    "by_role": {
      "administrator": {
        "endpoints": ["*"],
        "methods": ["*"],
        "fields": ["*"]
      },
      "editor": {
        "endpoints": ["posts", "pages", "media"],
        "methods": ["GET", "POST", "PUT"],
        "fields": ["*"],
        "exceptions": {
          "posts": {
            "DELETE": false,
            "fields": {
              "meta_field_sensitive": false
            }
          }
        }
      },
      "subscriber": {
        "endpoints": ["posts", "pages"],
        "methods": ["GET"],
        "fields": ["id", "title", "excerpt"]
      }
    },
    "by_capability": {
      "edit_posts": {
        "endpoints": ["posts"],
        "methods": ["POST", "PUT"]
      }
    }
  }
}
```

### 9.3 Field-Level Permissions

**Estrategia:**
- Lista de campos permitidos por rol/endpoint
- Campos no listados son excluidos
- Wildcard `*` para todos los campos

**Implementación:**
1. Obtener lista de campos permitidos
2. Filtrar response antes de enviar
3. Campos sensibles ocultos por defecto

**Ejemplo:**
```json
{
  "field_permissions": {
    "subscriber": {
      "posts": {
        "allowed": ["id", "title", "excerpt", "date"],
        "denied": ["content", "author", "meta_*"]
      }
    },
    "sensitive_fields": {
      "email": ["administrator"],
      "phone": ["administrator", "editor"],
      "internal_notes": ["administrator"]
    }
  }
}
```

### 9.4 Integración con WordPress Capabilities

**Uso de `current_user_can()`:**
- Compatible con capabilities nativas
- Compatible con capabilities custom
- Compatible con roles custom

**Ejemplo:**
```json
{
  "permissions": {
    "by_capability": {
      "edit_posts": {
        "endpoints": ["posts"],
        "methods": ["POST", "PUT", "DELETE"]
      },
      "publish_posts": {
        "endpoints": ["posts"],
        "methods": ["POST"],
        "conditions": {
          "post_status": "publish"
        }
      }
    }
  }
}
```

---

## 10. Gestión de Media

### 10.1 Subida Vía API

**Endpoint:**
```
POST /api/v1/media
Content-Type: multipart/form-data
```

**Proceso:**
1. Validar autenticación
2. Validar permisos (`upload_files`)
3. Validar MIME type
4. Validar tamaño
5. Generar nombre único
6. `wp_handle_upload()`
7. Crear attachment post
8. Asociar a post (opcional)
9. Retornar metadata

**Response:**
```json
{
  "id": 123,
  "url": "https://example.com/wp-content/uploads/2025/02/image.jpg",
  "sizes": {
    "thumbnail": "https://example.com/wp-content/uploads/2025/02/image-150x150.jpg",
    "medium": "https://example.com/wp-content/uploads/2025/02/image-300x200.jpg",
    "large": "https://example.com/wp-content/uploads/2025/02/image-1024x683.jpg"
  },
  "mime_type": "image/jpeg",
  "size": 524288,
  "width": 1920,
  "height": 1280
}
```

### 10.2 Validación de MIME

**Estrategia:**
- Whitelist de tipos permitidos
- Validación real de MIME (no solo extensión)
- `wp_check_filetype_and_ext()`
- `finfo_file()` para validación real

**Configuración:**
```json
{
  "media": {
    "allowed_mime_types": [
      "image/jpeg",
      "image/png",
      "image/webp",
      "image/gif",
      "application/pdf"
    ],
    "forbidden_mime_types": [
      "application/x-php",
      "text/html",
      "application/javascript"
    ]
  }
}
```

### 10.3 Restricción de Tamaño

**Niveles:**
1. **Global:** `upload_max_filesize` PHP
2. **WordPress:** `max_upload_size`
3. **Plugin:** Configuración adicional

**Validación:**
- Comparar tamaño con límite
- Retornar error 413 si excede
- Sugerir optimización

### 10.4 Asociación Automática a Post

**Opciones:**
1. **Query Param:** `?post_id=123`
2. **Header:** `X-Post-Id: 123`
3. **Body:** `"post_id": 123` (JSON upload)

**Implementación:**
1. Validar permisos en post (`edit_post`)
2. `wp_update_post()` para featured image
3. `update_post_meta()` para galerías

---

## 11. Generación Automática de Documentación

### 11.1 Estrategia OpenAPI 3.0

**Estructura del Documento:**
```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "WP API Codeia",
    "version": "1.0.0",
    "description": "API generada automáticamente"
  },
  "servers": [
    {
      "url": "https://example.com/api/v1",
      "description": "API v1"
    }
  ],
  "components": {
    "securitySchemes": {
      "apiKey": {
        "type": "apiKey",
        "in": "header",
        "name": "Authorization"
      },
      "jwt": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "JWT"
      }
    }
  },
  "paths": {
    "/posts": {
      "get": {
        "summary": "List posts",
        "tags": ["Posts"],
        "security": [{"apiKey": []}],
        "parameters": [
          {
            "name": "page",
            "in": "query",
            "schema": {"type": "integer"}
          }
        ],
        "responses": {
          "200": {
            "description": "Success",
            "content": {
              "application/json": {
                "schema": {
                  "type": "array",
                  "items": {"$ref": "#/components/schemas/Post"}
                }
              }
            }
          }
        }
      }
    }
  },
  "components": {
    "schemas": {
      "Post": {
        "type": "object",
        "properties": {
          "id": {"type": "integer"},
          "title": {"type": "string"},
          "content": {"type": "string"}
        }
      }
    }
  }
}
```

### 11.2 Mapeo de Campos a OpenAPI

**Tipos de Campos:**
| WordPress | OpenAPI |
|-----------|---------|
| post_title (string) | string |
| menu_order (integer) | integer |
| post_date (datetime) | string, format: date-time |
| meta_field (true_false) | boolean |
| meta_field (gallery) | array of objects |
| ACF Image | object (id, url, sizes) |
| Taxonomy | array of objects |

**Estrategia de Detección de Tipos:**
1. Campo nativo: Mapeo predefinido
2. Meta field registrado: Usar `type` del registro
3. ACF: Mapear field type a OpenAPI type
4. JetEngine: Mapear field type a OpenAPI type

### 11.3 Actualización Automática

**Eventos que Disparan Actualización:**
- Endpoint guardado/actualizado
- CPT registrado
- Campo ACF agregado/modificado
- Campo JetEngine detectado
- Configuración de permisos actualizada

**Estrategia de Actualización:**
1. Hook al evento
2. Marcar documentación como "dirty"
3. Regenerar en el siguiente request a `/docs`
4. O regenerar async (wp_cron)

### 11.4 Endpoint /docs

**Implementación:**
- URL: `/api/v1/docs`
- Retorna especificación OpenAPI JSON
- Incluye schemas de todos los endpoints
- Incluye examples

**Swagger UI:**
- Integración de swagger-ui
- URL: `/wp-admin/admin.php?page=wp-api-codeia-docs`
- Iframe a `/api/v1/docs` con Swagger UI

---

## 12. Estrategia de Seguridad

### 12.1 Rate Limiting

**Estrategia:**
- Rate limiting por endpoint/API key/user/IP
- Ventana deslizante (sliding window)
- Almacenamiento en transients u object cache

**Configuración:**
```json
{
  "rate_limiting": {
    "enabled": true,
    "storage": "transients",
    "defaults": {
      "requests": 1000,
      "window": "hour",
      "burst": 100
    },
    "by_endpoint": {
      "posts": {
        "GET": 1000,
        "POST": 100,
        "PUT": 100,
        "DELETE": 50
      },
      "media": {
        "POST": 50
      }
    },
    "by_api_key": {
      "premium_key": 10000,
      "free_key": 100
    }
  }
}
```

**Implementación:**
1. Identificar cliente (IP, API key, User ID)
2. Obtener configuración de rate limit
3. Consultar contador actual
4. Incrementar contador
5. Retornar 429 si excede

**Headers:**
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1640000000
```

### 12.2 Control de CORS

**Configuración por Endpoint:**
```json
{
  "cors": {
    "enabled": true,
    "allowed_origins": [
      "https://example.com",
      "https://app.example.com"
    ],
    "allowed_methods": ["GET", "POST", "PUT", "DELETE"],
    "allowed_headers": [
      "Authorization",
      "Content-Type",
      "X-WP-Nonce"
    ],
    "exposed_headers": [
      "X-WP-Total",
      "X-WP-TotalPages"
    ],
    "max_age": 86400,
    "allow_credentials": true,
    "by_endpoint": {
      "public": {
        "allowed_origins": ["*"]
      },
      "private": {
        "allowed_origins": ["https://app.example.com"]
      }
    }
  }
}
```

**Headers Enviados:**
```
Access-Control-Allow-Origin: https://example.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE
Access-Control-Allow-Headers: Authorization, Content-Type
Access-Control-Max-Age: 86400
Access-Control-Allow-Credentials: true
```

### 12.3 Sanitización

**Estrategia:**
- Sanitizar todos los inputs
- Usar funciones de WordPress: `sanitize_text_field()`, `sanitize_key()`, etc.
- Validar esquema antes de procesar

**Tipos de Sanitización:**
- Text: `sanitize_text_field()`
- HTML: `wp_kses_post()`
- Integers: `intval()`
- Emails: `sanitize_email()`
- URLs: `esc_url_raw()`

**Validación de Esquema:**
- Schema validator (JSON Schema)
- Validar antes de pasar a WP_Query
- Retornar 400 si inválido

### 12.4 Protección Contra Enumeración

**Riesgos:**
- Enumeración de usuarios
- Enumeración de CPTs
- Enumeración de endpoints

**Mitigación:**
1. **Usuarios:** No exponer lista de usuarios
2. **CPTs:** Requerir autenticación para descubrir
3. **Endpoints:** 404 para endpoints no existentes
4. **Error Messages:** Mensajes genéricos

### 12.5 Nonces

**Uso:**
- Para endpoints accesibles desde frontend
- Para formularios en admin

**Implementación:**
- `wp_create_nonce()` al generar form
- `wp_verify_nonce()` al procesar
- Header: `X-WP-Nonce`

---

## 13. Estrategia de Performance

### 13.1 Caching con Transients

**Estrategia:**
- Cachear responses de GET
- TTL configurable por endpoint
- Invalidación al actualizar contenido

**Configuración:**
```json
{
  "cache": {
    "enabled": true,
    "storage": "transients",
    "defaults": {
      "ttl": 300
    },
    "by_endpoint": {
      "posts": {
        "GET": 300,
        "POST": 0
      }
    },
    "excluded_patterns": [
      "*_me",
      "auth_*"
    ]
  }
}
```

**Invalidación:**
- `save_post`: Invalidar caché del post
- `delete_post`: Invalidar caché del post
- `acf/update_value`: Invalidar campos ACF

### 13.2 Object Caching

**Estrategia:**
- Usar Redis/Memcached si disponible
- Cachear configuración del plugin
- Cachear field registry

**Uso de `wp_cache_set()`:**
- Configuración: 12 horas
- Field Registry: 6 horas
- Endpoints list: 1 hora

### 13.3 Lazy Loading de Campos Pesados

**Estrategia:**
- No cargar campos pesados por defecto
- Incluir solo si solicitado
- Query param: `?fields=id,title,content`

**Campos Pesados:**
- Galerías (ACF Gallery)
- Contenido largo (post_content)
- Relaciones complejas

---

## 14. Logging y Monitoreo

### 14.1 Logs de Requests

**Data a Loggear:**
- Timestamp
- Endpoint
- Método
- User ID / API Key
- IP
- Response Code
- Execution Time
- Memory Usage

**Storage:**
- Custom Table: `wp_api_codeia_logs`
- Índices: endpoint, user_id, date
- Retención: Configurable (default 30 días)

### 14.2 Logs de Errores

**Tipos de Errores:**
- Auth failed (401)
- Permission denied (403)
- Validation errors (400)
- Server errors (500)

**Data:**
- Error code
- Error message
- Stack trace (solo en dev)
- Request data

### 14.3 Logs de Intentos Fallidos de Autenticación

**Propósito:**
- Detectar ataques de fuerza bruta
- Alertar de actividad sospechosa

**Data:**
- Timestamp
- IP
- Username/Key intentado
- Método de auth
- User agent

### 14.4 Dashboard de Logs

**Filtros:**
- Por endpoint
- Por usuario
- Por código de respuesta
- Por rango de fechas
- Por IP

**Métricas:**
- Requests por día
- Promedio de response time
- Top endpoints
- Errores más comunes

---

## 15. Riesgos y Mitigación

### 15.1 Problemas Comunes

| Problema | Mitigación |
|----------|------------|
| **Enumeración de usuarios** | No exponer endpoint /users, 404 genérico |
| **Rate limiting excedido** | Implementar rate limiting, headers informativos |
| **Autenticación rota** | Múltiples métodos, fallback strategies |
| **Cache invalidation fallido** | TTL máximo, invalidación por hooks |
| **ACF/JetEngine cambian estructura** | Versionado de detectores, fallbacks |
| **Performance degradado** | Caching, lazy loading, object cache |
| **SQL Injection** | Usar WP_Query, prepared statements |
| **XSS** | `wp_kses_post()`, escaping |
| **CSRF** | Nonces, SameSite cookies |

### 15.2 Soluciones Arquitectónicas

**Separación de Concerns:**
- Módulos independientes
- Interfaces bien definidas
- Fácil testing

**Graceful Degradation:**
- Si ACF no está disponible, continuar sin ACF
- Si JetEngine no está disponible, continuar sin JetEngine
- Si object cache no está disponible, usar transients

**Observability:**
- Logs detallados
- Métricas accesibles
- Health checks

---

## 16. Checklist Técnico de Implementación

### 16.1 Módulo Core

- [ ] Bootstrapper
- [ ] Config Manager
- [ ] Autoloader
- [ ] Activation/Deactivation hooks
- [ ] Internationalization (i18n)

### 16.2 Módulo Detector

- [ ] CPT Detector
- [ ] Taxonomy Detector
- [ ] Meta Field Detector
- [ ] ACF Detector
- [ ] JetEngine Detector
- [ ] Field Registry
- [ ] Cache invalidation

### 16.3 Módulo Endpoint

- [ ] Endpoint Manager
- [ ] Endpoint Factory
- [ ] Version Manager
- [ ] Rewrite Rules Manager
- [ ] Query Param Manager
- [ ] Request Processor
- [ ] Response Formatter

### 16.4 Módulo Auth

- [ ] Auth Manager
- [ ] API Key Strategy
- [ ] JWT Strategy
- [ ] OAuth2 Strategy (PRO)
- [ ] Basic Auth Strategy
- [ ] App Password Strategy
- [ ] Token Manager

### 16.5 Módulo Permission

- [ ] Permission Manager
- [ ] Role Permission
- [ ] Capability Permission
- [ ] Endpoint Permission
- [ ] Method Permission
- [ ] Field Permission

### 16.6 Módulo Media

- [ ] Media Uploader
- [ ] MIME Validator
- [ ] Size Validator
- [ ] Media Associator

### 16.7 Módulo Documentation

- [ ] OpenAPI Generator
- [ ] Schema Builder
- [ ] Auto Updater
- [ ] Swagger UI integration

### 16.8 Módulo Security

- [ ] Rate Limiter
- [ ] CORS Manager
- [ ] Sanitization Manager
- [ ] Nonce Manager
- [ ] Enumeration Protection

### 16.9 Módulo Performance

- [ ] Cache Manager
- [ ] Cache Invalidation
- [ ] Lazy Loader

### 16.10 Módulo Logging

- [ ] Request Logger
- [ ] Error Logger
- [ ] Auth Logger
- [ ] Log Viewer

### 16.11 Módulo Admin

- [ ] Dashboard
- [ ] Endpoint Admin
- [ ] Auth Admin
- [ ] Permission Admin
- [ ] Media Admin
- [ ] Docs Admin
- [ ] Logs Admin
- [ ] Settings Admin

---

## 17. Estructura Recomendada de Carpetas

```
wp-api-codeia/
├── bootstrap.php                    # Carga principal del plugin
├── wp-api-codeia.php               # Header del plugin
├── README.md                       # Documentación general
├── CHANGELOG.md                    # Historial de cambios
├── LICENSE                         # GPL v2
├── .gitignore                      # Ignorados de git
│
├── admin/                          # Dashboard y páginas de admin
│   ├── class-admin-dashboard.php
│   ├── class-endpoint-admin.php
│   ├── class-auth-admin.php
│   ├── class-permission-admin.php
│   ├── class-media-admin.php
│   ├── class-docs-admin.php
│   ├── class-logs-admin.php
│   └── class-settings-admin.php
│
├── includes/                       # Código principal del plugin
│   ├──
│   │   ├── class-cpt-detector.php
│   │   ├── class-taxonomy-detector.php
│   │   ├── class-meta-field-detector.php
│   │   ├── class-acf-detector.php
│   │   ├── class-jetengine-detector.php
│   │   └── class-field-registry.php
│   │
│   ├── endpoints/
│   │   ├── class-endpoint-manager.php
│   │   ├── class-endpoint-factory.php
│   │   ├── class-version-manager.php
│   │   ├── class-rewrite-rules-manager.php
│   │   └── class-query-param-manager.php
│   │
│   ├── auth/
│   │   ├── class-auth-manager.php
│   │   ├── interface-auth-strategy.php
│   │   ├── class-api-key-strategy.php
│   │   ├── class-jwt-strategy.php
│   │   ├── class-oauth2-strategy.php
│   │   ├── class-basic-auth-strategy.php
│   │   └── class-app-password-strategy.php
│   │
│   ├── permissions/
│   │   ├── class-permission-manager.php
│   │   ├── class-role-permission.php
│   │   ├── class-capability-permission.php
│   │   ├── class-endpoint-permission.php
│   │   └── class-field-permission.php
│   │
│   ├── media/
│   │   ├── class-media-uploader.php
│   │   ├── class-mime-validator.php
│   │   └── class-media-associator.php
│   │
│   ├── documentation/
│   │   ├── class-openapi-generator.php
│   │   ├── class-schema-builder.php
│   │   └── class-docs-auto-updater.php
│   │
│   ├── security/
│   │   ├── class-rate-limiter.php
│   │   ├── class-cors-manager.php
│   │   ├── class-sanitization-manager.php
│   │   ├── class-nonce-manager.php
│   │   └── class-enumeration-protection.php
│   │
│   ├── performance/
│   │   ├── class-cache-manager.php
│   │   ├── class-cache-invalidation.php
│   │   └── class-lazy-loader.php
│   │
│   ├── logging/
│   │   ├── class-request-logger.php
│   │   ├── class-error-logger.php
│   │   ├── class-auth-logger.php
│   │   └── class-log-viewer.php
│   │
│   ├── repositories/
│   │   ├── class-endpoint-repository.php
│   │   ├── class-auth-repository.php
│   │   └── class-log-repository.php
│   │
│   └── utils/
│       ├── class-config-manager.php
│       ├── class-config-validator.php
│       └── helper-functions.php
│
├── templates/                      # Templates de admin
│   ├── endpoint-edit.php
│   ├── auth-keys.php
│   └── docs-viewer.php
│
├── assets/                         # Assets del plugin
│   ├── css/
│   │   ├── admin.css
│   │   └── swagger-ui.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── endpoint-builder.js
│   │   └── swagger-ui-bundle.js
│   └── images/
│
├── lib/                            # Librerías de terceros
│   ├── firebase-php-jwt/          # Para JWT
│   └── swagger-ui/                # Para documentación
│
├── tests/                          # Tests unitarios
│   ├── unit/
│   ├── integration/
│   └── bootstrap.php
│
├── docs/                           # Documentación técnica
│   ├── ARQUITECTURA.md            # Este documento
│   ├── API.md                     # Referencia de API
│   └── CONTRIBUTING.md            # Guía de contribución
│
└── languages/                      # Archivos de traducción
    ├── wp-api-codeia-es_ES.po
    ├── wp-api-codeia-es_ES.mo
    └── wp-api-codeia.pot
```

---

## 18. Roadmap de Implementación

### 18.1 Fase 1: MVP (Mínimo Producto Viable)

**Duración:** 4-6 semanas

**Objetivo:** Plugin funcional con características esenciales

**Características:**
1. ✅ Detección de CPTs nativos
2. ✅ Creación de endpoints simples
3. ✅ Autenticación por API Key
4. ✅ Permisos básicos por rol
5. ✅ Generación de OpenAPI básica
6. ✅ Dashboard simple
7. ✅ Logging básico

**Módulos:**
- Bootstrapper
- Config Manager
- CPT Detector
- Endpoint Manager
- API Key Auth
- Permission Manager (básico)
- OpenAPI Generator (básico)
- Admin Dashboard
- Request Logger

### 18.2 Fase 2: Características Core

**Duración:** 4-6 semanas

**Objetivo:** Funcionalidades adicionales importantes

**Características:**
1. ✅ Detección de ACF
2. ✅ Detección de JetEngine
3. ✅ JWT Authentication
4. ✅ Field-level permissions
5. ✅ Rate limiting
6. ✅ Caching
7. ✅ CORS Manager
8. ✅ Media upload

**Módulos:**
- ACF Detector
- JetEngine Detector
- JWT Auth
- Field Permission
- Rate Limiter
- Cache Manager
- CORS Manager
- Media Uploader

### 18.3 Fase 3: Características Avanzadas

**Duración:** 6-8 semanas

**Objetivo:** Funcionalidades enterprise

**Características:**
1. ✅ Versionado de endpoints
2. ✅ Query params custom
3. ✅ Swagger UI
4. ✅ Advanced logging
5. ✅ Métricas y dashboard
6. ✅ Application Passwords
7. ✅ Sanitización avanzada

**Módulos:**
- Version Manager
- Query Param Manager
- Swagger UI
- Advanced Logging
- Metrics Dashboard
- App Password Strategy
- Advanced Sanitization

### 18.4 Fase 4: Enterprise y PRO

**Duración:** 8+ semanas

**Objetivo:** Funcionalidades premium

**Características:**
1. ✅ OAuth2 completo
2. ✅ Webhooks
3. ✅ Multi-tenant
4. ✅ Rate limiting avanzado
5. ✅ Analytics
6. ✅ GraphQL (opcional)
7. ✅ API Gateway (opcional)

**Módulos:**
- OAuth2 Strategy
- Webhook Manager
- Multi-tenant Manager
- Advanced Rate Limiting
- Analytics Engine

---

## 19. Comparación vs REST Nativo de WordPress

### 19.1 Cuadro Comparativo Detallado

| Aspecto | REST API Nativo | WP API Codeia |
|---------|----------------|---------------|
| **Instalación** | Nativo en WP | Plugin adicional |
| **Configuración** | Requiere código | GUI completa |
| **Versionado** | No (solo /wp/v2/) | v1, v2, etc. |
| **Autenticación** | Cookies, App Passwords | API Keys, JWT, OAuth2 |
| **Permisos** | Por capability | Por rol, endpoint, método, campo |
| **Field Selection** | Requiere `_fields` param | Configuración persistente |
| **ACF Integración** | Requiere código | Automática |
| **JetEngine Integración** | Requiere código | Automática |
| **Documentación** | Manual o plugins | Auto-generada OpenAPI |
| **Rate Limiting** | No nativo | Configurable |
| **Caching** | Manual | Integrado |
| **CORS** | Requiere código | Configurable |
| **Logging** | No nativo | Integrado |
| **Query Params** | Fijos | Customizables |
| **Media Upload** | Nativo | Extendido con validaciones |
| **Gobernanza** | Dispersa | Centralizada |

### 19.2 Cuándo Usar Cada Uno

**Usa REST API Nativo cuando:**
- Requisitos simples
- No necesitas gobernanza
- Equipo comfortable con código
- No requieres versionado
- No requieres autenticación custom

**Usa WP API Codeia cuando:**
- Requieres gobernanza centralizada
- Necesitas versionado
- Autenticación custom (API Keys, JWT)
- Integración con ACF/JetEngine
- Documentación automática
- Rate limiting y logging
- Field-level permissions
- Equipo mixto (dev + no-dev)

---

## 20. Preparación para Escalar a SaaS

### 20.1 Arquitectura Multi-Tenant

**Estrategia:**
- Soporte multisite nativo de WordPress
- API Keys por site
- Rate limiting por site
- Logs segregados por site
- Dashboard de admin de red

**Consideraciones:**
- Configuración global vs por-site
- API Keys globales vs por-site
- Rate limiting compartido vs aislado

### 20.2 Métricas y Analytics

**Métricas a Capturar:**
- Requests por endpoint
- Response time promedio
- Error rate
- Top usuarios
- Uso de bandwidth

**Dashboard Analytics:**
- Gráficos de uso
- Tendencias
- Alertas
- Export de datos

### 20.3 Monetización

**Modelos:**
1. **Freemium:**
   - Free: 1 endpoint, 1000 requests/día
   - Pro: 10 endpoints, 10,000 requests/día
   - Enterprise: Ilimitado

2. **Usage-based:**
   - Pagar por requests
   - Pagar por endpoints
   - Pagar por features

**Implementación:**
- Licencias por funcionalidad
- Cuotas por tier
- Integración con WooCommerce/EDD

### 20.4 Webhooks

**Propósito:**
- Notificar eventos externos
- Integraciones con terceros

**Eventos:**
- Post creado/actualizado
- Usuario creado
- Auth failed (alertas)
- Rate limit exceeded

### 20.5 API Gateway (Opcional)

**Propósito:**
- Proxy para múltiples instancias WP
- Rate limiting global
- Analytics centralizados

**Implementación:**
- Servicio separado (Lambda, Cloudflare Workers)
- Kubernetes con ingress
- Nginx/HAProxy

---

## 21. Consideraciones Multisite

### 21.1 Configuración

**Niveles de Configuración:**
1. **Network Admin:** Configuración global
2. **Site Admin:** Configuración por-site
3. **Overrides:** Sites pueden override network settings

**Storage:**
- `wp_api_codeia_network_config`: Config global
- `wp_api_codeia_site_config_{blog_id}`: Config por site

### 21.2 API Keys

**Estrategia:**
- API Keys globales: Acceso a todos los sites
- API Keys por-site: Acceso solo a un site
- Scope de keys: `global` vs `site:{id}`

### 21.3 Endpoints

**Estrategia:**
- Endpoints globales: Disponibles en todos los sites
- Endpoints por-site: Solo en site específico
- Namespace: `/api/{site_slug}/v1/...` o `/api/v1/...`

### 21.4 Rate Limiting

**Opciones:**
1. **Compartido:** Todos los sites comparten cuota
2. **Aislado:** Cada site tiene su cuota
3. **Mixto:** Cuota global + por-site

---

## 22. Consideraciones de Compatibilidad

### 22.1 WordPress 6.x

**Features a Aprovechar:**
- REST API enhancements
- Application Passwords
- Site Health checks
- Block editor integration
- Performance mejorado

**Hooks a Utilizar:**
- `rest_api_init`
- `rest_authentication_errors`
- `rest_pre_serve_request`
- `rest_endpoints`

### 22.2 PHP 8.2

**Features a Aprovechar:**
- Typed properties
- Union types
- Readonly properties
- Attributes
- Named arguments
- Constructor property promotion

**Compatibilidad:**
- Verificar compatibilidad con librerías de terceros
- Testing en PHP 8.2
- Deprecated warnings

### 22.3 Plugins de Terceros

**ACF:**
- Hooks: `acf/init`, `acf/update_field_group`
- Functions: `acf_get_fields()`, `acf_get_field_groups()`

**JetEngine:**
- No hay hooks documentados
- Direct DB queries
- Validación de existencia de plugin

**WooCommerce:**
- CPTs: `product`, `product_variation`
- Taxonomies: `product_cat`, `product_tag`
- Meta fields: `_price`, `_stock`, etc.

---

## 23. Estrategia de Testing

### 23.1 Unit Tests

**Framework:** PHPUnit

**Tests a Implementar:**
- Tests de detectores (CPT, ACF, JetEngine)
- Tests de estrategias de auth
- Tests de permisos
- Tests de sanitización
- Tests de cache

### 23.2 Integration Tests

**Framework:** PHPUnit + WordPress Testsuite

**Tests a Implementar:**
- Tests de endpoints completos
- Tests de rewrite rules
- Tests de integración con ACF/JetEngine
- Tests de media upload

### 23.3 E2E Tests

**Framework:** Codeception o Playwright

**Tests a Implementar:**
- Tests del dashboard admin
- Tests de creación de endpoints
- Tests de autenticación
- Tests de documentación generada

### 23.4 Load Tests

**Framework:** Apache Bench, JMeter, o k6

**Escenarios:**
- 1000 requests/segundo
- Concurrent users
- Large responses
- Rate limiting

---

## 24. Notas Finales

### 24.1 Namespace Recomendado

**PHP Namespace:** `WP_API_Codeia`

**Prefix de funciones:** `wp_api_codeia_`

**Prefix de options:** `wp_api_codeia_`

**Prefix de transients:** `wp_api_codeia_`

**Prefix de DB tables:** `wp_api_codeia_`

### 24.2 Constantes Críticas

```php
define('WP_API_CODEIA_VERSION', '1.0.0');
define('WP_API_CODEIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_API_CODEIA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_API_CODEIA_NAMESPACE', 'wp-api-codeia');
define('WP_API_CODEIA_BASE_PATH', '/api');
```

### 24.3 Hooks Principales

**Filtros:**
- `wp_api_codeia_endpoint_config`: Modificar configuración de endpoint
- `wp_api_codeia_response`: Modificar response antes de enviar
- `wp_api_codeia_permissions`: Modificar permisos
- `wp_api_codeia_fields`: Modificar campos disponibles

**Acciones:**
- `wp_api_codeia_endpoint_saved`: Endpoint guardado
- `wp_api_codeia_auth_success`: Autenticación exitosa
- `wp_api_codeia_auth_failed`: Autenticación fallida
- `wp_api_codeia_rate_limit_exceeded`: Rate limit excedido

### 24.4 Próximos Pasos

1. **Validación:** Revisar arquitectura con stakeholders
2. **MVP:** Implementar Fase 1 del roadmap
3. **Testing:** Tests exhaustivos de Fase 1
4. **Iteración:** Feedback y mejoras
5. **Expansion:** Implementar Fases 2 y 3

---

**Fin del Documento Arquitectónico**

**Versión:** 1.0.0
**Fecha:** 2025-02-24
**Autor:** Arquitecto Senior WP API Codeia
**Estado:** Aprobado para Implementación
