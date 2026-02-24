# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Autenticación con API Keys
- Sistema de permisos básico
- Endpoints GET para posts y páginas
- Detección automática de CPTs y taxonomías
- Sistema de configuración JSON
- Logging de requests
- Dashboard administrativo

### Changed
- Actualización de README con documentación de uso

## [1.0.0] - 2025-02-24

### Added
- Versión inicial del plugin (Sprint 1 - MVP)
- Estructura modular completa con namespaces
- Autoloader de clases
- Bootstrap principal con inicialización de módulos
- Sistema de instalación con tablas personalizadas:
  - `wp_api_codeia_auth_keys` - Gestión de API Keys
  - `wp_api_codeia_logs` - Logs de requests
  - `wp_api_codeia_rate_limits` - Rate limiting
- Config Manager con almacenamiento en wp_options
- Detector Manager para CPTs y taxonomías
- Auth Manager con múltiples métodos:
  - API Key (completamente implementado)
  - JWT (estructura lista)
  - Basic Auth (solo development)
  - Application Passwords (integración nativa WP)
- Endpoint Manager con:
  - Procesamiento de requests GET
  - Formateo de posts a JSON
  - Paginación
  - Filtrado de campos
- API Key Repository con:
  - Creación de keys con hash
  - Validación de keys
  - Gestión de scopes (read, write, read_write, admin)
  - Actualización de last_used
- Endpoints por defecto:
  - `GET /api/v1/posts` - Listar posts
  - `GET /api/v1/posts/{id}` - Post individual
  - `GET /api/v1/pages` - Listar páginas
  - `GET /api/v1/pages/{id}` - Página individual
- Documentación arquitectónica completa
- Plan de implementación por sprints
- Guía CLAUDE.md para desarrolladores

### Security
- API Keys almacenadas con password_hash()
- Validación de permisos por endpoint
- Prepared statements para queries

[Unreleased]: https://github.com/wp-api-codeia/wp-api-codeia/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/wp-api-codeia/wp-api-codeia/releases/tag/v1.0.0
