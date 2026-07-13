# Iya Design System

## Overview & Design Philosophy
"ไอย๊าห์ Iya" is a fast, responsive, and visually appealing IT Support & Equipment Management System. The design philosophy centers around a clean, minimal aesthetic with a vibrant "Coral" accent color to create a modern, premium feel.

## Color Palette
- `primary` (Coral): `#FF8383` - Used for primary actions, active states, and highlights.
- `background`: `#FFFFFF` - Clean white background for the main content area.
- `foreground`: `#000000` - High contrast text for readability.
- `neutral`: Slate grays for borders, subtle text, and secondary elements.

## Typography
- **Primary Font**: `Kanit` (for Thai text)
- **Secondary Font**: `Inter` (for English/Numbers)
- Headings are bold and clean. Body text is legible at 16px.

## Spacing & Border Radius
- Use standard 4px/8px grid system.
- `radius-md`: 8px (standard for inputs, small buttons).
- `radius-lg`: 12px (standard for cards, modals).

## Components
- **Cards**: Soft borders (`border-gray-200`) with subtle shadows.
- **Buttons**: Rounded, high contrast. Primary buttons use the Coral background with white text.
- **Inputs**: Clean borders, focus ring uses the primary Coral color.

## Animation
- Smooth, subtle transitions (`@vueuse/motion`).
- Animated organic background (SVG pattern) with a slow floating effect for visual interest without distraction.
