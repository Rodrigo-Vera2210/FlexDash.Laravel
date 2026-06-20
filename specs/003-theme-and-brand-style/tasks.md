# Tasks: Theme Customization and Brand Styling

## Phase 1: Brand Integration & Favicon
- [ ] T001 Update `resources/views/components/application-logo.blade.php` to render `FlexDash.jpg`.
- [ ] T002 Link favicon to `FlexDash.jpg` in `<head>` of layout and main page templates:
  - `layouts/app.blade.php`
  - `layouts/guest.blade.php`
  - `auth/login.blade.php`
  - `registration/wizard.blade.php`
  - `welcome.blade.php`
- [ ] T003 Update `registration/partials/brand-header.blade.php` to show the brand logo alongside system text.
- [ ] T004 Replace default sidebar icon/text logo in `layouts/app.blade.php` with the new `<x-application-logo>` brand logo.

## Phase 2: Theme Setup & Variables
- [ ] T005 Update Tailwind CSS configuration in `layouts/app.blade.php` to enable `darkMode: 'class'` and define the brand color palette mapped to custom CSS variables.
- [ ] T006 Add inline script to layout `<head>` to initialize dark mode from `localStorage`.
- [ ] T007 Add the CSS variables (light and dark mode sets) to `layouts/app.blade.php` `<style>` block.
- [ ] T008 Add the same CSS variables configuration to `resources/css/app.css` to cover Vite pages.

## Phase 3: Contrast, Rounded Corners & Theme Toggle Button
- [ ] T009 Style components in `layouts/app.blade.php` style tag to use variables and rounded corners (`rounded-3xl`/`1.5rem` for cards/widgets).
- [ ] T009a Apply Neumorphic shadows (outer clean shadows and soft inner shadows) on cards and components.
- [ ] T010 Implement the theme toggle button in layout header next to the date.
- [ ] T011 Update HTML markup tags in `layouts/app.blade.php` to use the theme variables classes (`bg-theme-page`, `bg-theme-card`, `text-theme-text`, `border-theme-border`, `bg-sidebar`, etc.) instead of hardcoded tailwind v3 colors.
- [ ] T011a Update `resources/views/dashboard/index.blade.php` to include neumorphic spline chart gradients and circular product thumbnails with visual depth.
- [ ] T012 Run test suite to verify no regressions in functionality.
