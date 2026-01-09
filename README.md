
# API – Sistema de Gestión de Proyectos

API REST desarrollada para la gestión de proyectos académicos, convocatorias y publicaciones,
permitiendo administrar usuarios, roles, proyectos, fases, archivos y certificaciones.

El sistema fue desarrollado como parte de un **proyecto de integración universitaria**, cubriendo
el flujo completo desde la creación de convocatorias hasta la finalización y certificación de proyectos.

---

## Tecnologías

- PHP 8.x
- Laravel
- Laravel Sanctum (autenticación)
- MySQL
- Git

---

## Funcionalidades principales

- Autenticación de usuarios mediante tokens (Laravel Sanctum)
- Gestión de usuarios y roles (admin, revisor, profesor, estudiante)
- Gestión de convocatorias y fases
- Registro, revisión y aprobación de proyectos
- Subida y descarga de archivos por convocatoria, fase y proyecto
- Gestión de observaciones y correcciones
- Generación de certificados por proyecto
- Control de accesos mediante middlewares por rol

---

## Instalación y ejecución

1. Clonar el repositorio:
```bash
git clone https://github.com/JosephRios7/Linkage-projects-api.git
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el archivo `.env` (base de datos y variables de entorno)

4. Ejecutar migraciones:
```bash
php artisan migrate
```

5. Iniciar el servidor:
```bash
php artisan serve
```

---

## Autenticación

La API utiliza **Laravel Sanctum** para autenticación basada en tokens.
Es necesario iniciar sesión para acceder a las rutas protegidas.

---

## Notas

Proyecto desarrollado con fines académicos y de integración universitaria,
siguiendo buenas prácticas de desarrollo backend y control de versiones.
