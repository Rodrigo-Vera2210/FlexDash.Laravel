# Especificación 004: Rediseño Visual Completo con Paleta FlexDash

## Objetivo
Rediseñar completamente el sistema de estilos visuales del proyecto FlexDash para usar la paleta de colores extraída del logotipo. El diseño de referencia es el HTML de dashboard SPMB compartido por el usuario (estructura CSS, CSS variables, tipografía, componentes). Se adopta Font Awesome (ya existente) en lugar de Iconify.

## Paleta de Colores FlexDash

| Color | Hex | Uso |
|---|---|---|
| Azul Teal | `#0A7EA5` | Identidad, acentos, sidebar activo, links |
| Amarillo Dorado | `#F2A900` | Destacados, badges warning, detalles |
| Naranja | `#E35205` | CTAs, botones primarios |
| Magenta | `#A41D6A` | Detalles modernos, badges especiales |
| Azul Noche | `#0D1E36` | Textos principales, sidebar background |
| Gris Claro | `#D1D5DB` | Fondos, bordes, espacios neutros |

## Sistema de Variables CSS

### Modo Claro (`:root`)
- `--bg`: `#F8F9FA` (fondo de página)
- `--surface`: `#FFFFFF` (fondo de tarjetas/paneles)
- `--primary`: `#0A7EA5` (azul teal principal)
- `--primary-dark`: `#075f7d`
- `--primary-light`: `rgba(10, 126, 165, 0.09)`
- `--cta`: `#E35205` (naranja - botones acción)
- `--cta-dark`: `#b83f04`
- `--accent-gold`: `#F2A900`
- `--accent-magenta`: `#A41D6A`
- `--text-main`: `#0D1E36`
- `--text-secondary`: `#374151`
- `--text-tertiary`: `#6B7280`
- `--border`: `#E5E7EB`
- `--border-light`: `#F3F4F6`
- `--shadow-sm`: `0 2px 4px rgba(13, 30, 54, 0.04), 0 4px 12px rgba(13, 30, 54, 0.06)`
- `--shadow-md`: `0 4px 12px rgba(13, 30, 54, 0.08), 0 12px 24px rgba(13, 30, 54, 0.12)`

### Modo Oscuro (`.dark`)
- `--bg`: `#0D1E36`
- `--surface`: `#162538`
- `--primary`: `#1aa3d4`
- `--primary-dark`: `#0A7EA5`
- `--primary-light`: `rgba(26, 163, 212, 0.12)`
- `--text-main`: `#F9FAFB`
- `--text-secondary`: `#D1D5DB`
- `--text-tertiary`: `#9CA3AF`
- `--border`: `#1e3352`
- `--border-light`: `#172944`
- `--shadow-sm`: `0 4px 6px rgba(0,0,0,0.4)`
- `--shadow-md`: `0 10px 20px rgba(0,0,0,0.5)`

## Tipografía
- Fuente principal: `'Plus Jakarta Sans'` (Google Fonts) - pesos 300, 400, 500, 600, 700
- Fuente monoespaciada: `'JetBrains Mono'` (Google Fonts) para valores numéricos
- Reemplaza `Inter` en el layout principal

## Componentes a Rediseñar

### Layout Principal (`layouts/app.blade.php`)
- Sidebar: Fondo `var(--bg)` en modo oscuro / `#0D1E36` sólido en modo claro, nav-items con estado activo en `--primary`
- Topbar: Fondo `var(--surface)`, borde inferior `var(--border)`
- Botón de toggle de tema (sol/luna) en topbar
- Script de inicialización de tema en `<head>` (bloqueo de parpadeo)
- Tarjetas (`.card-panel`): `var(--surface)`, `border-radius: 16px`, `box-shadow: var(--shadow-sm)`
- Stat-cards (`.stat-card`): borde izquierdo coloreado con el color de acento

### Layout Auth (`layouts/guest.blade.php`)
- Migrar a Tailwind CDN + sistema de variables CSS
- Fondo de página: `var(--bg)`
- Tarjeta centrada: `.card-panel`
- Logo visible arriba de la tarjeta

### Login (`auth/login.blade.php`)
- Rediseño completo con variables CSS
- Botón de submit: color `--cta` (naranja)
- Inputs con estilos `.input-solid`

### Registro Wizard (`registration/wizard.blade.php`)
- Sidebar izquierdo: `--bg` oscuro / `#0D1E36`
- Pasos activos: `--primary` (teal)
- Área de formulario: `var(--surface)`

### Dashboard (`dashboard/index.blade.php`)
- Welcome banner: `.card-panel`
- KPI Cards: `.stat-card` con borde izquierdo coloreado según métrica
- Gráfico: SVG spline con gradiente teal
- Tablas: `.table-custom` con estilos de variables
- Clientes: avatares con `--primary-light` de fondo

## Persistencia de Tema
- Script bloqueante en `<head>` de todos los layouts
- Toggle en topbar, guarda en `localStorage.theme`
- Clase `.dark` en `<html>`

## Scope de Páginas
Todas las páginas del proyecto:
1. `layouts/app.blade.php` (layout principal)
2. `layouts/guest.blade.php` (layout auth)
3. `auth/login.blade.php`
4. `registration/wizard.blade.php`
5. `dashboard/index.blade.php`
