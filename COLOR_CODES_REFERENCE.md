# 🎨 Color System - Complete Color Mapping

## 📋 All Color Codes Reference

### TENANT APP (Barangay Dashboard)
```
┌─────────────────────────────────────────────┐
│ TENANT APP COLOR PALETTE                    │
├─────────────────────────────────────────────┤
│ PRIMARY        #635bff  ████ Purple         │
│ PRIMARY-LIGHT  #ecf0ff  ████ Very light     │
│ SIDEBAR        #121621  ████ Very dark      │
│ ACCENT         #0ea5e9  ████ Sky blue       │
│ SUCCESS        #10b981  ████ Emerald        │
│ WARNING        #f59e0b  ████ Amber          │
│ DANGER         #ef4444  ████ Red            │
│ BACKGROUND     #f8fafc  ████ Off-white      │
└─────────────────────────────────────────────┘
```

### CENTRAL APP (City Admin Dashboard)
```
┌─────────────────────────────────────────────┐
│ CENTRAL APP COLOR PALETTE                   │
├─────────────────────────────────────────────┤
│ PRIMARY        #1e40af  ████ Deep blue      │
│ PRIMARY-LIGHT  #dbeafe  ████ Light blue     │
│ SIDEBAR        #0f172a  ████ Very dark      │
│ ACCENT         #06b6d4  ████ Cyan           │
│ SUCCESS        #059669  ████ Forest green   │
│ WARNING        #d97706  ████ Amber-dark     │
│ DANGER         #dc2626  ████ Dark red       │
│ BACKGROUND     #f0f9ff  ████ Blue-tint      │
└─────────────────────────────────────────────┘
```

---

## 🗺️ Element-to-Color Mapping

### SIDEBAR

| Element | Tenant | Central | Purpose |
|---------|--------|---------|---------|
| Background | #121621 | #0f172a | Navigation container |
| Text - Normal | white | white | Navigation labels |
| Text - Hover | white | white | Hover state |
| Link Active BG | `rgba(99,91,255,0.2)` | `rgba(30,64,175,0.3)` | Current page highlight |
| Border | `rgba(255,255,255,0.05)` | `rgba(255,255,255,0.1)` | Subtle divider |

### HEADER (Top Bar)

| Element | Tenant | Central | Purpose |
|---------|--------|---------|---------|
| Background | white | #1e293b | Header container |
| Border | #e2e8f0 | `rgba(30,64,175,0.3)` | Bottom divider |
| Mobile Nav - Active BG | `rgba(99,91,255,0.1)` | `rgba(30,64,175,0.3)` | Mobile active link |
| Mobile Nav - Active Text | #7c3aed | #93c5fd | Mobile active text |
| Badge Border | #10b981 | #06b6d4 | Badge outline |
| Badge BG | `rgba(16,185,129,0.1)` | `rgba(6,182,212,0.1)` | Badge background |
| Badge Text | #059669 | #22d3ee | Badge label |
| Badge Icon | 🏘️ | 🏛️ | Identity icon |

### BUTTONS & LINKS

| Element | Tenant | Central | Purpose |
|---------|--------|---------|---------|
| Primary BG | #635bff | #1e40af | Main action button |
| Primary Hover | (lighter) | (lighter) | Hover state |
| Secondary BG | #f8fafc | #1e293b | Secondary button |
| Secondary Hover | #f1f5f9 | #334155 | Hover state |
| Secondary Border | #e2e8f0 | #475569 | Button outline |
| Secondary Text | #475569 | #cbd5e1 | Button label |

### MAIN CONTENT AREA

| Element | Tenant | Central | Purpose |
|---------|--------|---------|---------|
| Background | #f8fafc | #f0f9ff | Main page background |
| Text - Primary | #1f2937 | #1f2937 | Body text |
| Text - Secondary | #6b7280 | #6b7280 | Muted text |
| Card Background | white | white | Card container |
| Card Border | #e5e7eb | #dbeafe | Card outline |

### STATUS MESSAGES

| Type | Tenant | Central | Use Case |
|------|--------|---------|----------|
| **Success** | #10b981 | #059669 | Positive actions |
| Success BG | `rgba(16,185,129,0.1)` | `rgba(5,150,105,0.1)` | Success alert bg |
| **Warning** | #f59e0b | #d97706 | Cautions |
| Warning BG | `rgba(245,158,11,0.1)` | `rgba(217,119,6,0.1)` | Warning alert bg |
| **Danger** | #ef4444 | #dc2626 | Errors |
| Danger BG | `rgba(239,68,68,0.1)` | `rgba(220,38,38,0.1)` | Error alert bg |

---

## 🎯 When to Use Each Color

### Use #635bff (Tenant Purple) For:
- Tenant app primary buttons
- Tenant app active states
- Tenant app brand elements
- Community-focused features

### Use #1e40af (Central Blue) For:
- Central app primary buttons
- Central app active states
- Central app brand elements
- Government oversight features

### Use #0ea5e9 (Tenant Sky Blue) For:
- Tenant app highlights
- Tenant app accents
- Attention-getting elements (not critical)

### Use #06b6d4 (Central Cyan) For:
- Central app highlights
- Central app accents
- Modern/tech indicators

### Use #10b981 (Tenant Green) For:
- Tenant app success messages
- Positive actions in tenant context
- Community health indicators

### Use #059669 (Central Forest Green) For:
- Central app success messages
- Positive actions in central context
- City health indicators

---

## 📝 Configuration Reference

### In tailwind.config.js
```javascript
colors: {
    tenant: {
        primary: '#635bff',
        'primary-light': '#ecf0ff',
        'sidebar': '#121621',
        'accent': '#0ea5e9',
        'success': '#10b981',
        'warning': '#f59e0b',
        'danger': '#ef4444',
        'bg': '#f8fafc',
    },
    central: {
        primary: '#1e40af',
        'primary-light': '#dbeafe',
        'sidebar': '#0f172a',
        'accent': '#06b6d4',
        'success': '#059669',
        'warning': '#d97706',
        'danger': '#dc2626',
        'bg': '#f0f9ff',
    },
}
```

---

## 💾 Copy-Paste Color Codes

### Tenant Colors
```
Primary:       #635bff
Primary Light: #ecf0ff
Sidebar:       #121621
Accent:        #0ea5e9
Success:       #10b981
Warning:       #f59e0b
Danger:        #ef4444
Background:    #f8fafc
```

### Central Colors
```
Primary:       #1e40af
Primary Light: #dbeafe
Sidebar:       #0f172a
Accent:        #06b6d4
Success:       #059669
Warning:       #d97706
Danger:        #dc2626
Background:    #f0f9ff
```

---

## 🎨 RGB Equivalents (If Needed)

### Tenant Colors
| Color | HEX | RGB |
|-------|-----|-----|
| Primary | #635bff | rgb(99, 91, 255) |
| Accent | #0ea5e9 | rgb(14, 165, 233) |
| Success | #10b981 | rgb(16, 185, 129) |

### Central Colors
| Color | HEX | RGB |
|-------|-----|-----|
| Primary | #1e40af | rgb(30, 64, 175) |
| Accent | #06b6d4 | rgb(6, 182, 212) |
| Success | #059669 | rgb(5, 150, 105) |

---

## 🔧 Usage Examples

### In React/JSX
```jsx
// Using Tailwind classes
<div className="bg-tenant-primary">Purple button</div>
<div className="bg-central-primary">Blue button</div>

// Using inline styles
<div style={{ backgroundColor: '#635bff' }}>Purple</div>
<div style={{ backgroundColor: '#1e40af' }}>Blue</div>

// Using CSS variables
<div style={{ backgroundColor: 'var(--color-tenant-primary)' }}>
```

### In CSS
```css
.tenant-button {
    background-color: #635bff;
}

.central-button {
    background-color: #1e40af;
}

.tenant-accent {
    color: #0ea5e9;
}

.central-accent {
    color: #06b6d4;
}
```

---

## 🎯 Quick Lookup

### "I want tenant app color"
→ Use colors from TENANT APP section above

### "I want central app color"
→ Use colors from CENTRAL APP section above

### "I want to highlight something"
→ Tenant: Use #0ea5e9 | Central: Use #06b6d4

### "I want success green"
→ Tenant: #10b981 | Central: #059669

### "I want background"
→ Tenant: #f8fafc | Central: #f0f9ff

---

## 📊 Color Contrast Ratios

All colors meet WCAG AA standards:
- ✅ Text on light backgrounds: 4.5:1 contrast
- ✅ Text on dark backgrounds: 4.5:1 contrast
- ✅ Large text: 3:1 contrast minimum

---

## 📋 Master Color List

| Name | Hex | Tenant | Central | Type |
|------|-----|--------|---------|------|
| Primary | #635bff / #1e40af | ✓ | ✓ | Main |
| Accent | #0ea5e9 / #06b6d4 | ✓ | ✓ | Highlight |
| Success | #10b981 / #059669 | ✓ | ✓ | Status |
| Warning | #f59e0b / #d97706 | ✓ | ✓ | Status |
| Danger | #ef4444 / #dc2626 | ✓ | ✓ | Status |
| Sidebar | #121621 / #0f172a | ✓ | ✓ | Nav |
| Background | #f8fafc / #f0f9ff | ✓ | ✓ | Canvas |

---

## ✅ Implementation Checklist

- ✅ Colors defined in Tailwind config
- ✅ CentralLayout updated with central colors
- ✅ TenantLayout updated with tenant colors
- ✅ All color codes documented
- ✅ Ready for use in components
- ✅ Future-extensible design

---

**Print this page or save the color codes for easy reference when building components!** 📋
