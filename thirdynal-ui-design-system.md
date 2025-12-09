# Thirdynal Warehouse Ops System - UI Design System

A comprehensive design system documentation for implementing a dark-themed warehouse operations dashboard.

---

## Table of Contents

1. [Color System](#color-system)
2. [Typography](#typography)
3. [Spacing & Layout](#spacing--layout)
4. [Components](#components)
5. [CSS Variables](#css-variables)
6. [Implementation Examples](#implementation-examples)

---

## Color System

### Background Colors

| Token | Hex Value | Usage |
|-------|-----------|-------|
| `--bg-primary` | `#0a0e17` | Main page background |
| `--bg-secondary` | `#0d1219` | Header bar background |
| `--bg-card` | `#151c28` | Card/container backgrounds |
| `--bg-card-hover` | `#1a2332` | Card hover state |
| `--bg-input` | `#1e2a3a` | Input field backgrounds |
| `--bg-elevated` | `#1a2332` | Elevated surfaces, modals |

### Border Colors

| Token | Hex Value | Usage |
|-------|-----------|-------|
| `--border-default` | `#2d3a4d` | Default card/container borders |
| `--border-subtle` | `#1e2736` | Subtle dividers |
| `--border-active` | `#3b82f6` | Active/selected state borders |
| `--border-input` | `#374151` | Input field borders |
| `--border-focus` | `#3b82f6` | Focus state borders |

### Accent Colors

| Token | Hex Value | Usage |
|-------|-----------|-------|
| `--accent-primary` | `#22d3ee` | Primary cyan - headers, stat numbers |
| `--accent-blue` | `#3b82f6` | Buttons, active tabs, links |
| `--accent-green` | `#22c55e` | Success states, DELIVERED badge |
| `--accent-yellow` | `#eab308` | Warning states, pending items |
| `--accent-orange` | `#f59e0b` | PENDING badge background |
| `--accent-red` | `#ef4444` | Error states, RETURNED badge, high return rate |

### Text Colors

| Token | Hex Value | Usage |
|-------|-----------|-------|
| `--text-primary` | `#f8fafc` | Primary text, headings |
| `--text-secondary` | `#94a3b8` | Secondary text, labels |
| `--text-muted` | `#64748b` | Muted text, placeholders |
| `--text-disabled` | `#475569` | Disabled states |

---

## Typography

### Font Stack

```css
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
```

### Font Sizes

| Token | Size | Line Height | Usage |
|-------|------|-------------|-------|
| `--text-xs` | 11px | 1.4 | Badge text, small labels |
| `--text-sm` | 13px | 1.5 | Table headers, secondary info |
| `--text-base` | 14px | 1.5 | Body text, table data |
| `--text-md` | 15px | 1.5 | Input text, navigation |
| `--text-lg` | 18px | 1.4 | Section titles |
| `--text-xl` | 20px | 1.3 | Card titles |
| `--text-2xl` | 24px | 1.2 | Page subtitles |
| `--text-3xl` | 28px | 1.2 | Page title |
| `--text-4xl` | 36px | 1.1 | Large stat numbers |
| `--text-5xl` | 42px | 1.1 | Hero stat numbers |

### Font Weights

| Token | Weight | Usage |
|-------|--------|-------|
| `--font-normal` | 400 | Body text |
| `--font-medium` | 500 | Labels, navigation |
| `--font-semibold` | 600 | Headings, stat numbers |
| `--font-bold` | 700 | Emphasis, badges |

### Special Typography Styles

**Page Title (Cyan Italic)**
```css
.page-title {
  font-size: 28px;
  font-weight: 600;
  font-style: italic;
  color: #22d3ee;
  letter-spacing: -0.02em;
}
```

**Stat Numbers**
```css
.stat-number {
  font-size: 36px;
  font-weight: 600;
  color: #22d3ee;
  letter-spacing: -0.02em;
}
```

**Table Headers**
```css
.table-header {
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
}
```

---

## Spacing & Layout

### Base Grid

The design uses an **8px base grid** system. All spacing values are multiples of 8px.

| Token | Value | Usage |
|-------|-------|-------|
| `--space-1` | 4px | Tight spacing, icon gaps |
| `--space-2` | 8px | Small gaps, inline spacing |
| `--space-3` | 12px | Input padding, small card gaps |
| `--space-4` | 16px | Standard element spacing |
| `--space-5` | 20px | Card internal padding |
| `--space-6` | 24px | Section spacing, large padding |
| `--space-8` | 32px | Major section gaps |
| `--space-10` | 40px | Page margins |
| `--space-12` | 48px | Large vertical spacing |

### Container Widths

```css
--container-sm: 640px;
--container-md: 768px;
--container-lg: 1024px;
--container-xl: 1280px;
--container-2xl: 1536px;
--container-full: 100%;
```

### Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│                     HEADER BAR (80px)                       │
│                   padding: 20px 32px                        │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │              NAVIGATION TABS                         │   │
│  │              padding: 16px 24px                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              CONTENT AREA                            │   │
│  │              padding: 24px                           │   │
│  │                                                      │   │
│  │   ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐  │   │
│  │   │ STAT    │ │ STAT    │ │ STAT    │ │ STAT    │  │   │
│  │   │ CARD    │ │ CARD    │ │ CARD    │ │ CARD    │  │   │
│  │   └─────────┘ └─────────┘ └─────────┘ └─────────┘  │   │
│  │        gap: 16px between cards                       │   │
│  │                                                      │   │
│  │   ┌─────────────────────────────────────────────┐   │   │
│  │   │              DATA TABLE                      │   │   │
│  │   │              margin-top: 24px                │   │   │
│  │   └─────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Page padding: 24px (sides), 16px (top/bottom)             │
└─────────────────────────────────────────────────────────────┘
```

### Grid Layouts

**Stat Cards Grid (Dashboard)**
```css
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* Second row with 2 cards */
.stats-grid-secondary {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  max-width: 50%;
}

/* Responsive: 2 columns on tablet */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Responsive: 1 column on mobile */
@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
```

**Stat Cards Grid (Accounts - 6 columns)**
```css
.stats-grid-extended {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}
```

---

## Components

### Header Bar

```
Height: 80px
Background: #0d1219 (slightly lighter than page)
Border: 1px solid #1e2736 (subtle bottom border)
Padding: 20px 32px
Text alignment: Center

Title: 28px, italic, cyan (#22d3ee), semibold
Subtitle: 14px, muted gray (#94a3b8), regular
```

### Navigation Tabs

```
Container:
  Background: #151c28
  Border: 1px solid #2d3a4d
  Border-radius: 12px
  Padding: 8px
  
Tab Item:
  Padding: 10px 24px
  Border-radius: 8px
  Font-size: 14px
  Font-weight: 500
  
  Inactive:
    Background: transparent
    Color: #94a3b8
    
  Active:
    Background: #3b82f6
    Color: #ffffff
    
  Hover (inactive):
    Background: #1a2332
    Color: #f8fafc

Gap between tabs: 8px
```

### Stat Cards

```
Container:
  Background: #151c28
  Border: 1px solid #2d3a4d
  Border-radius: 12px
  Padding: 20px
  Min-height: 100px
  
  Selected/Active state:
    Border: 2px solid #3b82f6
    
Number:
  Font-size: 36px
  Font-weight: 600
  Color: varies by context
    - Default: #22d3ee (cyan)
    - Success: #22c55e (green)
    - Warning: #eab308 (yellow)
    - Error: #ef4444 (red)
  Margin-bottom: 4px
  
Percentage (inline):
  Font-size: 14px
  Font-weight: 500
  Color: varies (same as number color but slightly muted)
  Margin-left: 8px
  
Label:
  Font-size: 13px
  Font-weight: 400
  Color: #94a3b8
  Text-transform: none
```

### Date Range Filter

```
Container:
  Background: #151c28
  Border: 1px solid #2d3a4d
  Border-radius: 12px
  Padding: 16px 20px
  
Label:
  Font-size: 13px
  Color: #64748b
  Margin-bottom: 8px
  
Date Input:
  Background: #1e2a3a
  Border: 1px solid #374151
  Border-radius: 8px
  Padding: 10px 16px
  Font-size: 14px
  Color: #f8fafc
  
  With calendar icon:
    Padding-right: 40px
    Icon position: right 12px center
    Icon size: 16px
    Icon color: #64748b

Gap between From/To: 16px
Button margin-left: 16px
```

### Buttons

**Primary Button**
```
Background: #3b82f6
Color: #ffffff
Border: none
Border-radius: 8px
Padding: 10px 24px
Font-size: 14px
Font-weight: 500

Hover:
  Background: #2563eb
  
Active:
  Background: #1d4ed8
  
With icon:
  Gap: 8px
  Icon size: 16px
```

**Secondary Button**
```
Background: #1e2a3a
Color: #f8fafc
Border: 1px solid #374151
Border-radius: 8px
Padding: 10px 24px

Hover:
  Background: #2d3a4d
  Border-color: #4b5563
```

### Input Fields

```
Background: #1e2a3a
Border: 1px solid #374151
Border-radius: 8px
Padding: 12px 16px
Font-size: 14px
Color: #f8fafc
Height: 44px

Placeholder:
  Color: #64748b
  
Focus:
  Border-color: #3b82f6
  Outline: none
  Box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1)
  
Search input (full width):
  Width: 100%
  Padding-left: 44px (if has search icon)
  Icon: 20px, positioned left 14px
```

### Select/Dropdown

```
Background: #1e2a3a
Border: 1px solid #374151
Border-radius: 8px
Padding: 10px 36px 10px 16px
Font-size: 14px
Color: #f8fafc
Height: 44px

Chevron icon:
  Position: right 12px center
  Size: 16px
  Color: #64748b
  
Dropdown menu:
  Background: #1e2a3a
  Border: 1px solid #374151
  Border-radius: 8px
  Box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3)
  Margin-top: 4px
  
  Item:
    Padding: 10px 16px
    
    Hover:
      Background: #2d3a4d
```

### Data Table

```
Container:
  Background: #151c28
  Border: 1px solid #2d3a4d
  Border-radius: 12px
  Overflow: hidden
  
Header section:
  Padding: 20px 24px
  Border-bottom: 1px solid #2d3a4d
  
  Title:
    Font-size: 18px
    Font-weight: 600
    Color: #f8fafc
    
  Subtitle:
    Font-size: 13px
    Color: #64748b
    Margin-top: 4px

Table header row:
  Background: transparent
  Border-bottom: 1px solid #2d3a4d
  
  Cell:
    Padding: 12px 16px
    Font-size: 12px
    Font-weight: 500
    Text-transform: uppercase
    Letter-spacing: 0.05em
    Color: #94a3b8

Table data row:
  Border-bottom: 1px solid #1e2736
  
  Cell:
    Padding: 14px 16px
    Font-size: 14px
    Color: #f8fafc
    
  Hover:
    Background: #1a2332

Pagination info:
  Font-size: 13px
  Color: #64748b
  Padding: 12px 16px
  Text-align: center
```

### Status Badges

```
Base style:
  Padding: 4px 12px
  Border-radius: 4px
  Font-size: 11px
  Font-weight: 600
  Text-transform: uppercase
  Letter-spacing: 0.02em

DELIVERED:
  Background: #166534
  Color: #ffffff
  
RETURNED:
  Background: #dc2626
  Color: #ffffff
  
PENDING:
  Background: #4b5563
  Color: #ffffff
  
IN_TRANSIT:
  Background: #0891b2
  Color: #ffffff
  
DISPATCHED:
  Background: #2563eb
  Color: #ffffff
```

### Waybill Number Badge

```
Background: #1e3a5f
Color: #60a5fa
Padding: 4px 10px
Border-radius: 4px
Font-size: 12px
Font-weight: 500
Font-family: monospace
```

### Section Titles with Icons

```
Container:
  Display: flex
  Align-items: center
  Gap: 12px
  
Icon:
  Size: 24px
  Color: #22d3ee or #3b82f6
  
Title:
  Font-size: 20px
  Font-weight: 600
  Color: #f8fafc
  
Subtitle:
  Font-size: 13px
  Color: #64748b
  Margin-top: 4px
```

---

## CSS Variables

Complete CSS custom properties for implementation:

```css
:root {
  /* Colors - Backgrounds */
  --bg-primary: #0a0e17;
  --bg-secondary: #0d1219;
  --bg-card: #151c28;
  --bg-card-hover: #1a2332;
  --bg-input: #1e2a3a;
  --bg-elevated: #1a2332;
  
  /* Colors - Borders */
  --border-default: #2d3a4d;
  --border-subtle: #1e2736;
  --border-active: #3b82f6;
  --border-input: #374151;
  --border-focus: #3b82f6;
  
  /* Colors - Accents */
  --accent-primary: #22d3ee;
  --accent-blue: #3b82f6;
  --accent-blue-hover: #2563eb;
  --accent-green: #22c55e;
  --accent-green-dark: #166534;
  --accent-yellow: #eab308;
  --accent-orange: #f59e0b;
  --accent-red: #ef4444;
  --accent-red-dark: #dc2626;
  
  /* Colors - Text */
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted: #64748b;
  --text-disabled: #475569;
  
  /* Typography */
  --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
  
  --text-xs: 11px;
  --text-sm: 13px;
  --text-base: 14px;
  --text-md: 15px;
  --text-lg: 18px;
  --text-xl: 20px;
  --text-2xl: 24px;
  --text-3xl: 28px;
  --text-4xl: 36px;
  --text-5xl: 42px;
  
  --font-normal: 400;
  --font-medium: 500;
  --font-semibold: 600;
  --font-bold: 700;
  
  /* Spacing */
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  
  /* Border Radius */
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 8px;
  --radius-xl: 12px;
  --radius-full: 9999px;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.25);
  --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.3);
  
  /* Transitions */
  --transition-fast: 150ms ease;
  --transition-base: 200ms ease;
  --transition-slow: 300ms ease;
}
```

---

## Implementation Examples

### React/Tailwind Config

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        'bg-primary': '#0a0e17',
        'bg-secondary': '#0d1219',
        'bg-card': '#151c28',
        'bg-input': '#1e2a3a',
        'border-default': '#2d3a4d',
        'border-subtle': '#1e2736',
        'accent-cyan': '#22d3ee',
        'accent-blue': '#3b82f6',
        'text-primary': '#f8fafc',
        'text-secondary': '#94a3b8',
        'text-muted': '#64748b',
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif'],
        'mono': ['JetBrains Mono', 'monospace'],
      },
      spacing: {
        '18': '4.5rem',
        '22': '5.5rem',
      },
      borderRadius: {
        'xl': '12px',
      }
    }
  }
}
```

### Example Component (React)

```jsx
// StatCard.jsx
const StatCard = ({ value, label, percentage, color = 'cyan', isActive }) => {
  const colorClasses = {
    cyan: 'text-cyan-400',
    green: 'text-green-500',
    yellow: 'text-yellow-500',
    red: 'text-red-500',
    white: 'text-white'
  };

  return (
    <div 
      className={`
        bg-[#151c28] 
        border 
        ${isActive ? 'border-blue-500 border-2' : 'border-[#2d3a4d]'}
        rounded-xl 
        p-5
        min-h-[100px]
        transition-all
        duration-200
        hover:bg-[#1a2332]
      `}
    >
      <div className="flex items-baseline gap-2">
        <span className={`text-4xl font-semibold ${colorClasses[color]}`}>
          {value}
        </span>
        {percentage && (
          <span className={`text-sm font-medium ${colorClasses[color]} opacity-80`}>
            ({percentage})
          </span>
        )}
      </div>
      <p className="text-[13px] text-[#94a3b8] mt-1">{label}</p>
    </div>
  );
};
```

### Example Component (Plain CSS)

```css
/* Base styles */
body {
  font-family: var(--font-primary);
  background-color: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.5;
}

/* Card component */
.card {
  background-color: var(--bg-card);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-xl);
  padding: var(--space-5);
}

.card:hover {
  background-color: var(--bg-card-hover);
}

.card--active {
  border: 2px solid var(--accent-blue);
}

/* Stat card */
.stat-card__value {
  font-size: var(--text-4xl);
  font-weight: var(--font-semibold);
  color: var(--accent-primary);
  letter-spacing: -0.02em;
}

.stat-card__label {
  font-size: var(--text-sm);
  color: var(--text-secondary);
  margin-top: var(--space-1);
}

/* Navigation tabs */
.nav-tabs {
  display: flex;
  gap: var(--space-2);
  background-color: var(--bg-card);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-xl);
  padding: var(--space-2);
}

.nav-tab {
  padding: 10px 24px;
  border-radius: var(--radius-lg);
  font-size: var(--text-base);
  font-weight: var(--font-medium);
  color: var(--text-secondary);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.nav-tab:hover {
  background-color: var(--bg-card-hover);
  color: var(--text-primary);
}

.nav-tab--active {
  background-color: var(--accent-blue);
  color: white;
}

/* Status badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: var(--radius-sm);
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.badge--delivered {
  background-color: #166534;
  color: white;
}

.badge--returned {
  background-color: #dc2626;
  color: white;
}

.badge--pending {
  background-color: #4b5563;
  color: white;
}

/* Data table */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  padding: 12px 16px;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  text-align: left;
  border-bottom: 1px solid var(--border-default);
}

.data-table td {
  padding: 14px 16px;
  font-size: var(--text-base);
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-subtle);
}

.data-table tr:hover {
  background-color: var(--bg-card-hover);
}

/* Form inputs */
.input {
  width: 100%;
  height: 44px;
  padding: 12px 16px;
  background-color: var(--bg-input);
  border: 1px solid var(--border-input);
  border-radius: var(--radius-lg);
  font-size: var(--text-base);
  color: var(--text-primary);
  transition: border-color var(--transition-fast);
}

.input::placeholder {
  color: var(--text-muted);
}

.input:focus {
  outline: none;
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Primary button */
.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 24px;
  background-color: var(--accent-blue);
  color: white;
  border: none;
  border-radius: var(--radius-lg);
  font-size: var(--text-base);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.btn-primary:hover {
  background-color: var(--accent-blue-hover);
}
```

---

## Responsive Breakpoints

```css
/* Mobile first approach */

/* Small (mobile): 0 - 639px */
/* Default styles */

/* Medium (tablet): 640px+ */
@media (min-width: 640px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Large (desktop): 1024px+ */
@media (min-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .page-padding {
    padding: 24px 32px;
  }
}

/* Extra large: 1280px+ */
@media (min-width: 1280px) {
  .container {
    max-width: 1280px;
    margin: 0 auto;
  }
}
```

---

## Accessibility Notes

1. **Color contrast**: All text colors meet WCAG AA standards against their backgrounds
2. **Focus states**: All interactive elements have visible focus indicators
3. **Touch targets**: Minimum 44px height for all interactive elements
4. **Font sizes**: Base font size of 14px with scalable rem units recommended
5. **Screen readers**: Use proper semantic HTML and ARIA labels for badges and icons

---

## Quick Reference

| Element | Background | Border | Border-Radius | Padding |
|---------|------------|--------|---------------|---------|
| Page | `#0a0e17` | none | none | `24px` |
| Header | `#0d1219` | subtle | none | `20px 32px` |
| Card | `#151c28` | `#2d3a4d` | `12px` | `20px` |
| Input | `#1e2a3a` | `#374151` | `8px` | `12px 16px` |
| Button | `#3b82f6` | none | `8px` | `10px 24px` |
| Tab (active) | `#3b82f6` | none | `8px` | `10px 24px` |
| Badge | varies | none | `4px` | `4px 12px` |
| Table row | transparent | `#1e2736` | none | `14px 16px` |

---

*Document generated based on Thirdynal Warehouse Ops System UI analysis*
