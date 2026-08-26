# Design System — District Coordination Case Management Portal

Reference document for Cursor (or any AI coding tool) to follow this exact layout and styling while building the application. Keep every screen consistent with the tokens and component rules below.

---

## 1. Color Tokens

| Token | Hex | Usage |
|---|---|---|
| `--color-bg` | `#F7F5EF` | Page/content background (warm cream) |
| `--color-navy` | `#132A45` | Header, sidebar, primary buttons |
| `--color-navy-light` | `#1E3A5C` | Active nav item background (sidebar) |
| `--color-teal` | `#2F7D6B` | Logo mark, accent bar on active nav item |
| `--color-text-primary` | `#1A1A1A` | Headings, primary body text |
| `--color-text-muted` | `#8A7F6E` | Table headers, helper text |
| `--color-text-inverse` | `#FFFFFF` | Text on navy backgrounds |
| `--color-text-inverse-muted` | `#B8C4D1` | Inactive sidebar/header subtitle text |
| `--color-border` | `#EDEAE2` | Table row dividers, card borders |

### Stat card / badge pairs

| State | Background | Text |
|---|---|---|
| Total (neutral) | `#FFFFFF` | `#1A1A1A` |
| Pending / Amber | `#FBEBD3` | `#9A6A1E` |
| Resolved / Green | `#E1EFE0` | `#3A6B39` |
| Overdue / Red | `#FBE3E3` | `#B23A3A` |

### Status pill badges

| Status | Background | Text |
|---|---|---|
| Escalated | `#FBE3E3` | `#C43D3D` |
| Resolved | `#E1EFE0` | `#3A6B39` |
| In Progress | `#EAE6F7` | `#5B4FA8` |
| Closed | `#EDEAE2` | `#6B6B6B` |
| Referred | `#DCE7F7` | `#2E5FA8` |
| Pending | `#FBEBD3` | `#9A6A1E` |

---

## 2. Typography

- Font family: Inter or a similar clean modern sans-serif.
- Headings (page titles, case IDs): bold, `#132A45` or `#1A1A1A`.
- Stat numbers: bold, large (28–32px).
- Table headers: uppercase, small (11–12px), letter-spaced, `#8A7F6E`, no background fill.
- Body/table text: regular, 14–15px, `#1A1A1A`.
- Timestamps/helper text: small (12–13px), `#8A7F6E`.

## 3. Spacing & Radius

- Base spacing unit: 8px scale (8 / 16 / 24 / 32px gaps between major sections).
- Corner radius: 10–14px on all cards, buttons, inputs, and pills.
- Cards: white or pastel fill, subtle border (`1px solid #EDEAE2`), no harsh drop shadows — soft, minimal elevation only where needed (e.g. login card).

## 4. Layout Structure (applies to every inner page)

```
┌─────────────────────────────────────────────┐
│  Sidebar (navy) │  Top bar (cream)           │
│  #132A45        │  page title · actions      │
│                 ├────────────────────────────┤
│  Dashboard      │                            │
│  All Cases      │  Page content              │
│  New Case       │  (cream bg #F7F5EF)        │
│  High Priority  │                            │
│  Departments    │                            │
│  Reports        │                            │
│  Settings       │                            │
│                 │                            │
│  [user/logout]  │                            │
└─────────────────────────────────────────────┘
```

- **Sidebar**: fixed left, navy `#132A45`, ~240px expanded / ~64px collapsed (icon rail). Collapse toggle at top. Active nav item: `#1E3A5C` pill background + teal `#2F7D6B` left accent bar. User/logout row pinned to bottom.
- **Top bar**: sits beside the sidebar, cream background. Layout: page title on the left · centered global search box (searches all cases by name/CNIC/case ID) · right side holds a bell/notification icon (with unread count badge) + "As of [date/time]" + primary action button (e.g. "New Case," "+ Add Department"). This structure is identical across every inner page — only the title and right-side action button change. Clicking the bell opens a small dropdown preview (latest 4–5 notifications + "View all") or links to the full Notifications page.
- **Login screen** is the one exception — no sidebar, full-height cream background with a single centered white card.

## 5. Core Components

- **Buttons**: primary = solid navy `#132A45` bg, white text, 10–14px radius. Secondary = grey/outline. Segmented control (Priority) uses colored active state (red/amber).
- **Inputs**: rounded, light `#EDEAE2` border, comfortable padding, label above field.
- **Table**: uppercase muted header row, no header fill, thin row dividers, status column uses pill badges, clickable rows with subtle hover highlight, pagination bottom-right.
- **Cards**: white background, rounded, subtle border, used for stat cards, form container, summary panel, activity log, attachments.
- **Activity timeline**: vertical connecting line, small colored dot per event, timestamp (muted, small) + action text (primary).
- **Comment thread**: card per comment, department tag, timestamp, subtle left-border color-coded by department.
- **File upload**: dashed border drop zone, file chips after upload (icon + filename + remove/download icon).
- **Global search box**: centered in top bar, rounded, light border, magnifying-glass icon, searches across all cases by name/CNIC/case ID.
- **Notification bell**: icon button in top bar, small red dot/count badge when unread items exist, opens a dropdown preview or links to the full Notifications page.

## 6. Pages

1. **Login** — centered card, logo, email/username + password, forgot password link, full-width "Sign In" button.
2. **Sidebar Navigation** (persistent component) — collapsible, icon rail on collapse, drawer + hamburger on mobile.
3. **Dashboard** — stat cards row, filters, case table with status pills.
4. **Add New Case** — two-column form in a centered card, priority selector, attachment upload.
5. **Case Detail** — two-column: summary + remarks/comments (left), activity log + attachments + status control (right). Includes a "← Back to Dashboard" link in the top bar.
6. **Department & User Management** (Super Admin only) — responsive card grid of departments, drill-down user table per department. A user assigned to multiple departments appears in each relevant department's user list, and their profile shows all assigned department tags (with one marked "Primary").
7. **Notifications** — filterable list (All / Unread / Assignments / Comments / Escalations), unread rows highlighted with a pale-navy left accent, each row links to its Case Detail page, empty state for no notifications.

---

## 7. Responsiveness

**This is a target spec, not a guarantee from the AI-generated screens.** Stitch/Claude Design mockups give you a strong visual starting point, but they typically nail the desktop layout well and only approximate mobile behavior — true full responsiveness (fluid breakpoints, tested touch targets, real stacking behavior) has to be implemented in code, not assumed from the generated design. Build to these explicit breakpoints in Cursor:

| Breakpoint | Sidebar | Table | Form | Department grid |
|---|---|---|---|---|
| Desktop (≥1024px) | Expanded (240px), user-toggleable to icon rail | Full table | 2-column | 3 columns |
| Tablet (768–1023px) | Auto-collapsed to icon rail (64px) | Full table, horizontal scroll if needed | 1-column | 2 columns |
| Mobile (<768px) | Hidden by default, opens as slide-out drawer via hamburger icon with dark scrim | Converts to stacked card list (one card per row) | 1-column, full-width buttons | 1 column |

Additional rules:
- Case Detail's two-column layout stacks to a single column on tablet/mobile, in order: Priority & Status → Attachments → Activity Log → Summary → Remarks.
- Touch targets (buttons, nav items, table row taps) should be at least 44px tall on mobile.
- Login card scales down to ~90% width with consistent padding on small screens — never edge-to-edge.
- Test the sidebar collapse/drawer interaction explicitly; it's the piece most likely to need manual fixing after AI generation.
