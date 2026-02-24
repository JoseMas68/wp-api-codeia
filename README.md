# WP API Codeia

Transforma WordPress en una API headless configurable, versionada y completamente gobernable desde un dashboard administrativo.

## Descripción

WP API Codeia es un plugin de arquitectura enterprise que proporciona:

- **Configuración visual sin código**: Exposición selectiva de datos mediante interfaz gráfica
- **Detección automática**: Reconocimiento de CPTs, taxonomías, meta fields, ACF y JetEngine
- **Versionado de endpoints**: Soporte para v1, v2, etc. con backward compatibility
- **Autenticación múltiple**: API Keys, JWT, OAuth2, Application Passwords, Basic Auth
- **Permisos granulares**: Control a nivel de rol, capability, endpoint, método y campo
- **Documentación automática**: Generación de OpenAPI 3.0 actualizada en tiempo real
- **Gobernanza centralizada**: Dashboard unificado para gestión de API

## Instalación

1. Sube los archivos del plugin a `/wp-content/plugins/wp-api-codeia`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Accede a **WP API Codeia** en el menú principal del admin
4. Una API Key será creada automáticamente para los administradores

## Uso Rápido

### Autenticación

El plugin utiliza API Keys para autenticación. Incluye tu API Key en el header Authorization:

```bash
curl -H "Authorization: Bearer wpck_tu_api_key_aqui" \
     https://tu-site.com/api/v1/posts
```

### Endpoints Disponibles

Por defecto, el plugin crea dos endpoints:

**Listar Posts:**
```http
GET /api/v1/posts
```

**Obtener Post Individual:**
```http
GET /api/v1/posts/{id}
```

**Listar Páginas:**
```http
GET /api/v1/pages
```

### Parámetros de Query

- `page`: Número de página (default: 1)
- `per_page`: Items por página, máximo 100 (default: 10)
- `search`: Término de búsqueda

**Ejemplo:**
```bash
curl -H "Authorization: Bearer wpck_tu_api_key" \
     "https://tu-site.com/api/v1/posts?page=2&per_page=20"
```

### Respuesta

```json
{
  "data": [
    {
      "id": 1,
      "title": "Mi Post",
      "content": "<p>Contenido del post...</p>",
      "excerpt": "Extracto del post...",
      "date": "2025-02-24T10:00:00+00:00",
      "link": "https://tu-site.com/mi-post",
      "author": {
        "id": 1,
        "name": "Admin"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 50,
    "total_pages": 5
  }
}
```

## Gestión de API Keys

1. Ve a **WP API Codeia** en el admin
2. Haz clic en **API Keys**
3. Podrás:
   - Ver tus API Keys existentes
   - Crear nuevas Keys
   - Revocar Keys
   - Ver el historial de uso

## Requisitos

- **WordPress**: 6.0 o superior
- **PHP**: 8.2 o superior
- **Permisos**: Acceso al panel de administración de WordPress

## Características en Desarrollo

Este es el Sprint 1 del desarrollo. Las características implementadas incluyen:

### ✅ Sprint 1 (v1.0.0) - Actual
- Estructura modular completa
- Autenticación con API Keys
- Endpoints GET para posts y páginas
- Detección de CPTs y taxonomías
- Sistema de configuración JSON
- Dashboard básico
- Logging de requests

### 🚧 Sprint 2 (v1.1.0) - Próximo
- Detección de campos ACF y JetEngine
- Autenticación JWT
- Permisos a nivel de campo
- Rate limiting
- Sistema de caching
- Control de CORS
- Subida de media vía API

### 📋 Sprint 3 (v1.2.0)
- Versionado de endpoints
- Application Passwords
- Swagger UI
- Logging avanzado
- Optimización de performance

### 🎯 Sprint 4 (v2.0.0)
- OAuth2 completo
- Webhooks
- Multi-tenant avanzado
- Analytics
- Enterprise ready

## Documentación

Para documentación arquitectónica y técnica completa, consulta:
- [ARQUITECTURA.md](docs/ARQUITECTURA.md) - Arquitectura completa del plugin
- [PLAN_IMPLEMENTACION.md](docs/PLAN_IMPLEMENTACION.md) - Plan de desarrollo por sprints
- [CLAUDE.md](CLAUDE.md) - Guía para desarrolladores

## Changelog

Consulta [CHANGELOG.md](CHANGELOG.md) para ver el historial de cambios.

## Seguridad

- Las API Keys son almacenadas con hash usando `password_hash()`
- Todas las requests son validadas con permisos
- Rate limiting configurable (próximamente)
- Logs de auditoría para detectar actividades sospechosas

## Contribución

Este proyecto está en desarrollo activo. Para contribuir:
1. Revisa el plan de implementación
2. Sigue los patrones de arquitectura definidos
3. Mantén compatibilidad con WordPress 6.x y PHP 8.2+

## Licencia

Este plugin está licenciado bajo GPL v2 o posterior.
