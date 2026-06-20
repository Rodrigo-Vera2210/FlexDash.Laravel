# Plan: Theme Customization and Brand Styling

We will introduce a cohesive color palette, modern rounded card layouts with distinct background contrasts, and light/dark theme switching across the entire system.

## Proposed Changes

### 1. Unified Logo and Favicon Integration
- **[MODIFY] [application-logo.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/components/application-logo.blade.php)**: Replace hardcoded SVG paths with a clean image tag rendering `FlexDash.jpg`.
- **[MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)**, **[login.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/auth/login.blade.php)**, **[guest.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/guest.blade.php)**, **[wizard.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/wizard.blade.php)**, **[welcome.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/welcome.blade.php)**: Include `<link rel="icon" type="image/jpeg" href="{{ asset('build/assets/FlexDash.jpg') }}">` in their head tags.
- **[MODIFY] [brand-header.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/partials/brand-header.blade.php)**: Add the logo to the header.

### 2. Stylesheet Customization (Neumorphic Shadows & Logo Colors)
- **[MODIFY] [app.css](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/css/app.css)**: Define global variables under `:root` and `.dark` blocks representing the extracted palette and theme state colors (backgrounds, cards, borders, typography). Incorporate standard CSS classes for neumorphic bevels (double box-shadows combining positive light sources and negative dark ambient shadows).
- **[MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)**:
  - Inject the theme initialization script in the `<head>`.
  - Define custom tailwind configurations in the CDN configuration script block.
  - Style class definitions (`.kpi-card`, `.card`, `.btn-primary`, `.sidebar-link`) with updated neumorphic shadows (soft outer/inner drop shadow offsets) and rounded corners.

### 3. Theme Toggle Button in Layout Topbar
- **[MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)**: Add a theme toggle button in the header topbar next to the date/time container. Hook click actions to save `theme` state in `localStorage` and toggle `dark` class on document root.
- **[MODIFY] [login.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/auth/login.blade.php)**, **[wizard.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/wizard.blade.php)**: Ensure these views inherit or implement the theme toggle script/styles correctly.

## Verification Plan
1. **Visual Checks**: Ensure elements show soft outer shadows (light top-left, dark bottom-right) and matching inner shadows to create the New Neumorphic three-dimensional look. Test changing themes to dark mode and verify backgrounds and card borders adapt correctly.
2. **Automated Smoke Tests**: Run the existing test suite (`php artisan test`) to ensure markup changes do not break authentication or UI selectors.
