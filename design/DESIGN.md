---
name: Obsidian Emerald Wealth
colors:
  surface: '#0f131c'
  surface-dim: '#0f131c'
  surface-bright: '#353942'
  surface-container-lowest: '#0a0e16'
  surface-container-low: '#181c24'
  surface-container: '#1c2028'
  surface-container-high: '#262a33'
  surface-container-highest: '#31353e'
  on-surface: '#dfe2ee'
  on-surface-variant: '#bbcabf'
  inverse-surface: '#dfe2ee'
  inverse-on-surface: '#2c3039'
  outline: '#86948a'
  outline-variant: '#3c4a42'
  surface-tint: '#4edea3'
  primary: '#4edea3'
  on-primary: '#003824'
  primary-container: '#10b981'
  on-primary-container: '#00422b'
  inverse-primary: '#006c49'
  secondary: '#45dfa4'
  on-secondary: '#003825'
  secondary-container: '#00bd85'
  on-secondary-container: '#00452e'
  tertiary: '#68dba9'
  on-tertiary: '#003825'
  tertiary-container: '#3eb686'
  on-tertiary-container: '#00422c'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#6ffbbe'
  primary-fixed-dim: '#4edea3'
  on-primary-fixed: '#002113'
  on-primary-fixed-variant: '#005236'
  secondary-fixed: '#68fcbf'
  secondary-fixed-dim: '#45dfa4'
  on-secondary-fixed: '#002114'
  on-secondary-fixed-variant: '#005137'
  tertiary-fixed: '#85f8c4'
  tertiary-fixed-dim: '#68dba9'
  on-tertiary-fixed: '#002114'
  on-tertiary-fixed-variant: '#005137'
  background: '#0f131c'
  on-background: '#dfe2ee'
  surface-variant: '#31353e'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.04em
  numerical-display:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-tablet: 1.5rem
  margin-desktop: 2rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system establishes a high-tier financial management and investment environment characterized by precision, discretion, and quiet confidence. It merges a dark, velvety backdrop with luminous emerald cues, evoking the feeling of a private wealth management terminal reimagined as an intuitive, high-touch mobile product.

### Core Attributes
- **Discreet Authority:** Clean visual discipline free of decorative noise. Every element directly serves analytical clarity or decisive action.
- **Luminous Precision:** High-contrast tactical green accents that cut through the darkness to signal growth, stability, and confirmation without visual fatigue.
- **Architectural Tactility:** Subtle, translucent glassmorphic surfaces layered over deep obsidian substrates to provide perceived spatial depth.

### Design Style
The system adopts an **Elevated Minimalist Glassmorphic** aesthetic. It relies on subtle light-catching hairline borders, deep dark container tiers, and targeted emerald luminescence rather than aggressive gradients or heavy skeuomorphism.

## Colors

The palette is tuned specifically for low-light environments, prioritizing legibility, reduced glare, and rapid semantic comprehension of asset growth and transactions.

### Palette Architecture
- **Primary (`#10B981`):** Serves as the key financial driver—used for primary interactions, key portfolio metrics, positive yields, and active states.
- **Secondary (`#34D399`):** A high-luminance mint-emerald used for micro-glows, focused states, and top-tier emphasis on charts.
- **Tertiary (`#059669`):** A deeper emerald for hover states, filled badges, and container borders requiring subdued branding.
- **Neutral Core (`#0B0F17`):** The deep obsidian canvas substrate providing zero-light bleed and maximum contrast against glowing metrics.

### Functional & Surface Roles
- **Canvas Base:** `#0B0F17` (Deep Obsidian).
- **Surface Elevation 1 (Cards & Modules):** `#131A26` with 60% opacity against the dark canvas.
- **Surface Elevation 2 (Floating Modals & Sheets):** `#1A2332`.
- **Borders & Hairlines:** `rgba(30, 41, 59, 0.65)` (Slate 800 at 65% opacity) to catch edge light without boxing in content.
- **Semantic Neutral Text:**
  - Primary Content: `#F8FAFC` (Slate 50)
  - Secondary / Supporting: `#94A3B8` (Slate 400)
  - Disabled / Hairline Markers: `#475569` (Slate 630)
- **Negative / Risk State:** `#F43F5E` (Rose 500) for loss indicators and critical alerts, calibrated to match the visual weight of the emerald primary.

## Typography

The typography leverages **Plus Jakarta Sans** across all roles to ensure geometric balance, modern appeal, and legibility on mobile screens.

### Numerical Representation
Financial values require precise visual alignment. Tabular figures (`tnum`) must be enforced across all monetary counters, balances, percentage shifts, and transaction rows to eliminate jitter during value updates and ensure vertical column alignment.

### Mobile Hierarchy Rules
- Portfolio totals utilize `display-lg-mobile` or `numerical-display` to keep numbers prominent without wrapping.
- All category labels and transaction metadata must use `label-sm` with slight uppercase letter-spacing to distinguish metadata from content values.
- Headlines maintain tightened negative letter-spacing for a modern, architectural structure.

## Layout & Spacing

The layout is built mobile-first, using a fluid grid system designed for thumb-driven interaction.

### Grid & Breakpoints
- **Mobile (< 640px):** Single-column fluid view with `margin` of `1rem` and internal component gutters of `1rem`. Core controls are anchored to the bottom third of the viewport.
- **Tablet (640px - 1024px):** 6-column fluid structure, allowing side-by-side metric tiles and detailed chart layouts. Canvas margin expands to `1.5rem`.
- **Desktop (> 1024px):** 12-column layout maxing out at `1280px` centered width. Balances comprehensive data density without expanding card dimensions beyond readable scanning spans.

### Spatial Rhythm
- Apply `space-xs` and `space-sm` for tight data-to-label couplings inside financial widgets.
- Use `space-md` for structural card padding and element division.
- Apply `space-lg` and `space-xl` strictly for major section demarcations and dashboard module segregation.

## Elevation & Depth

Visual hierarchy is maintained through subtle glassmorphic translucency and directional light refraction, avoiding opaque drop shadows.

### Depth Paradigm
- **Ground Tier (Canvas):** Pure `#0B0F17`. Absolute depth with no refraction.
- **Base Cards (Elevation 1):** Background `rgba(19, 26, 38, 0.75)` backed by an `8px` blur. Encased in a single `1px` translucent border of `rgba(30, 41, 59, 0.6)`.
- **Overlay Elements & Sheets (Elevation 2):** Background `rgba(26, 35, 50, 0.85)` with a `16px` blur, bordered with `rgba(52, 211, 153, 0.15)` for a faint emerald top-edge sheen.
- **Luminous Micro-Glow:** Interactive and positive elements receive a subtle, diffused emerald ambient glow (`box-shadow: 0 0 20px -5px rgba(16, 185, 129, 0.2)`), signaling liveliness and positive returns without visually drowning the interface.

## Shapes

The geometric framework balances friendly accessibility with industrial precision through rounded corners.

### Shape Hierarchy
- **Base Metric Cards & Inputs:** `rounded` (8px / `0.5rem`) provides a stable container frame.
- **Primary Surfaces & Modals:** `rounded-lg` (16px / `1rem`) balances screen margins on mobile devices.
- **Action Buttons & Badges:** `rounded-lg` creates a tactile, distinct boundary for interaction.
- **Pill Indicators:** Fully rounded pill geometry is reserved strictly for status chips, stock ticker badges, and micro-switches.

## Components

### Buttons
- **Primary Action (Emerald Glow):** Fill of `#10B981` transitioning subtly to `#059669`. White bold text with a faint inner top highlight (`1px inset rgba(255, 255, 255, 0.25)`). On hover/active, expands a localized `rgba(16, 185, 129, 0.35)` blur.
- **Secondary (Glass):** Background of `rgba(19, 26, 38, 0.6)` with border `rgba(30, 41, 59, 0.8)` and Slate 50 text.
- **Ghost:** Zero background; text colored `#10B981` with an underline hover state.

### Cards & Modules
- Structured with an internal padding of `space-md` or `space-lg`.
- Top-aligned header zone containing micro-label and badge, followed by prominent tabular figures, terminated by trend indicators or sparklines at the base.
- Border-top incorporates a hairline linear-gradient fading from `rgba(52, 211, 153, 0.2)` down to transparent.

### Badges & Chips
- **Gain / Positive:** `rgba(16, 185, 129, 0.12)` background, `rgba(16, 185, 129, 0.3)` border, `#34D399` text.
- **Neutral / Asset Category:** `rgba(30, 41, 59, 0.5)` background, `#94A3B8` text.
- Always compact: `space-xs` vertical padding, `space-sm` horizontal padding, with `label-sm` font.

### Progress Bars & Allocations
- **Track:** Recessed `#131A26` with a `1px` inner shadow.
- **Fill:** Gradient from `#059669` to `#34D399` with rounded caps and a `4px` height scale for minimal visual weight.

### Form Inputs
- Background of `rgba(11, 15, 23, 0.8)` embedded inside card surfaces to create depth.
- Border is `rgba(30, 41, 59, 0.8)`, brightening to `#10B981` on focus with an ambient outer glow.
- Currency signifiers sit fixed in secondary text color, with entered numbers rendering in bold tabular typography.

### Data Visualizations & Trend Lines
- Positive asset curves use a `#10B981` stroke (2px) accompanied by an underlying vertical gradient fill fading from `rgba(16, 185, 129, 0.25)` to transparent.
- Gridlines rendered in low-contrast dotted `rgba(30, 41, 59, 0.4)`. Tooltips mimic the Glassmorphic Elevation 2 structure.