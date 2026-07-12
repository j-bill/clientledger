# ClientLedger UI Kit — migration spec (Vuetify → Tailwind)

Dark ledger aesthetic. Warm graphite surfaces, bone text, one brass accent. No neon, no glow, no gradients. All kit components are **globally registered** — use `<ui-button>` etc. in any SFC, no imports needed. Icons come from `lucide-vue-next` and must be imported per file and passed as component refs.

## Design tokens (Tailwind classes)

- Page background: `bg-ink-950` (set on `html`, don't repeat)
- Surfaces: cards/dialogs `bg-ink-900`, popovers `bg-ink-850`, hover rows `bg-ink-850`
- Borders: `border-ink-700` (or `ledger-rule` utility class for hairline bottom rules)
- Text: primary `text-bone-100`, secondary `text-bone-300`, muted `text-bone-500`, faint `text-bone-700`
- Accent (sparingly — primary actions, active nav, sort indicators): `brass-300..600`
- States: success `sage-*`, error `clay-*`, warning `ochre-*`, info `slate-*` (each has `-400` fg and `-900` bg)
- Fonts: default is Instrument Sans. **All money amounts, hours, dates in tables, and stat numbers get class `tnum`** (mono + tabular numerals) — this is the signature of the design.
- Field labels are rendered by the kit in mono uppercase automatically.
- Radius: `rounded-md` (6px) controls, `rounded-lg` (10px) cards/dialogs.

## Layout conventions (replaces v-container/v-row/v-col)

- Page wrapper: `<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">`
- Page heading: `<h1 class="text-2xl font-semibold tracking-tight">` + optional subtitle `<p class="mt-1 text-sm text-bone-500">`; heading row often flex with primary action right.
- Grid: `v-row/v-col cols=12 md=6` → `<div class="grid grid-cols-1 gap-4 md:grid-cols-2">`
- `<v-spacer>` → flexbox `justify-between` / `ml-auto`
- `<v-divider class="my-4">` → `<hr class="my-4 border-ink-700/60">`

## Component mapping

| Vuetify | Kit |
|---|---|
| `v-form ref="form"` | `<ui-form ref="form" @submit="...">` — exposes `validate()` returning `{ valid }`, and `resetValidation()`. Same `rules` API: `(v) => true \| 'msg'`. |
| `v-text-field` | `<ui-input v-model label type rules hint placeholder clearable disabled readonly suffix :icon="LucideIcon" />` (number type auto-casts) |
| `v-textarea` | `<ui-textarea v-model label rows rules hint />` |
| `v-select` | `<ui-select v-model :items item-title item-value label rules clearable return-object />` (items may be strings or objects) |
| `v-autocomplete` | `<ui-autocomplete>` same props as ui-select |
| `v-checkbox` | `<ui-checkbox v-model label hint />` |
| `v-switch` | `<ui-switch v-model label hint />` |
| `v-btn color=primary` | `<ui-button variant="primary" :icon="Plus">Label</ui-button>` |
| `v-btn variant=text` | `variant="ghost"` (or `brass-ghost` / `danger-ghost` for colored text buttons) |
| `v-btn color=error` | `variant="danger"` |
| `v-btn icon` | `<ui-button variant="ghost" size="sm" :icon="Pencil" />` (icon-only when no slot content) |
| button extras | `loading`, `block`, `size="sm|md|lg"`, `type="submit"` |
| `v-card` | `<ui-card title="...">` slots: `title`, `actions` (header right), default body, `footer`. `dense` prop removes body padding (use for tables). |
| `v-dialog` | `<ui-dialog v-model title max-width="800px" persistent>` slots: default, `actions`. Handles teleport/backdrop/esc/scroll-lock. |
| `v-data-table` | `<ui-card dense><ui-data-table :headers :items :loading :search :sort-by :row-props>` — same header objects `{title,key,sortable,align:'end'}`, same cell slots `#item.field="{ item }"`. Client-side sort/filter/pagination built in. |
| `v-data-table-server` | same component + `:server-items-length="total"` `v-model:items-per-page` `@update:options="load"` (options = `{page, itemsPerPage, sortBy}`) |
| `v-alert type=...` | `<ui-alert type="success|error|warning|info" title text closable @close>` |
| `v-chip` | `<ui-chip color="neutral|brass|success|error|warning|info" text />` |
| `v-menu` + `v-list` | `<ui-menu align="right"><template #activator>...button...</template><ui-menu-item :icon="X" title subtitle danger @click /></ui-menu>` |
| `v-tabs` + `v-tab` | `<ui-tabs v-model :tabs="[{value,label,icon?}]" />` + page keeps `v-if`/`v-show` per pane (replaces v-window) |
| `v-avatar` | `<ui-avatar :name="user.name" :size="36" :image="url" />` |
| `v-progress-circular` | `<ui-spinner :size="24" />` |
| `v-tooltip` | `<ui-tooltip text="..."><button/></ui-tooltip>` |
| `v-stepper` | `<ui-stepper v-model="step" :steps="['a','b','c']">` with `#default="{ step }"` content |
| `v-file-input` | `<ui-file-input v-model label accept hint />` |
| `v-date-picker` / `v-time-picker` | native: `<ui-input type="date" ...>` / `<ui-input type="time">` (values are `YYYY-MM-DD` / `HH:MM` strings) |
| `v-icon mdi-*` | lucide: import { Pencil, Trash2, Plus, Search, ... } from 'lucide-vue-next' and render `<Pencil class="h-4 w-4" />` |
| `v-snackbar` / `v-overlay` | already handled by shell components — don't reimplement |

## Common mdi → lucide names

mdi-pencil→Pencil, mdi-delete→Trash2, mdi-plus→Plus, mdi-magnify→Search, mdi-account→User, mdi-email→Mail, mdi-phone→Phone, mdi-map-marker→MapPin, mdi-city→Building2, mdi-earth→Globe, mdi-cash→Banknote, mdi-calendar→Calendar, mdi-clock→Clock, mdi-eye→Eye, mdi-eye-off→EyeOff, mdi-download→Download, mdi-refresh→RefreshCw, mdi-check→Check, mdi-close→X, mdi-cog→Settings, mdi-logout→LogOut, mdi-lock→Lock, mdi-shield→Shield, mdi-file-document→FileText, mdi-message-text→MessageSquareText, mdi-numeric→Hash, mdi-mailbox→Inbox, mdi-alert→TriangleAlert, mdi-information→Info, mdi-content-copy→Copy, mdi-chevron-down→ChevronDown, mdi-dots-vertical→MoreVertical, mdi-account-group→Users, mdi-briefcase→Briefcase, mdi-receipt→Receipt, mdi-chart-*→ChartLine/ChartBar, mdi-home→House, mdi-creation/sparkles→Sparkles

## Migration rules

1. Features, props, events, store calls, i18n keys, `data-test` attributes: keep identical. Only presentation changes.
2. Keep component `ref`s and `submit()`/`validate()` call sites working (kit mirrors the API).
3. Dialog action buttons: cancel = `variant="ghost"`, confirm = `variant="primary"`, destructive confirm = `variant="danger"`. Order: cancel left of confirm, right-aligned (put them in the dialog's `#actions` slot).
4. Tables always wrapped in `<ui-card dense>`.
5. Money/hours/date table cells: wrap in `<span class="tnum">`.
6. Search fields: `<ui-input v-model="search" :icon="Search" clearable :placeholder="$t('common.search')" />` (no label).
7. Don't add decoration: no gradients, no shadows beyond kit defaults, no new colors.
8. Empty states: short sentence + primary action if creation is possible.
