# Spec: Theme Customization and Brand Styling (New Neumorphic Design)

## Overview
This specification details the customization of the visual design of the FlexDash POS system. We will replace placeholders and generic logos with the new `FlexDash.jpg` brand logo, establish a color palette based on this logo, implement a "New Neumorphism" design style, and add toggleable light/dark themes.

## Objectives
1. **Brand Integration**: 
   - Use `FlexDash.jpg` as the browser favicon and include it in place of generic logos in all layout headers, login views, and unified logo components.
2. **Color Palette Mapping**:
   - Extract colors from the brand logo:
     - **Primary Blue**: `#0054a6`
     - **Teal / Cyan (Primary Accent)**: `#00a2e8`
     - **CTA Yellow**: `#ffc20e`
     - **CTA Orange**: `#f39200`
     - **Accent Magenta**: `#e5007d`
3. **New Neumorphic Design Style**:
   - **Backgrounds**: Very light off-white (`#f0f0f3`) for light mode, deep dark slate/navy (`#0f172a` or `#0b0f19`) for dark mode.
   - **Modular Cards & Components**: Rounded corners (`border-radius: 1.5rem` / `rounded-3xl`) styled with soft, subtle inner shadows and clean outer shadows to create a tactile, three-dimensional, pressable effect.
   - **Typography**: Deep graphite grey text in light mode, off-white text in dark mode.
   - **Accents**: Deep teal primary accents, with subtle gradient spline representations for analytics widgets.
4. **Light & Dark Themes**:
   - Implement class-based light/dark theme switching.
   - Inject a Toggle Theme button in the dashboard topbar.
   - Define custom CSS variables in the stylesheets.`, `--bg-card`, `--text-main`, `--text-muted`, `--border-color`, `--sidebar-bg`) in the stylesheet.
