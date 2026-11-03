---
name: checkstu
description: Das Spielbrett. A family board where everyone plays their own colour.
colors:
  board-white: "#f4f5f8"
  ink: "#14161c"
  card: "#ffffff"
  field-grey: "#e8eaef"
  muted-ink: "#565b68"
  outline: "#d7dae2"
  input-outline: "#c3c8d2"
  plate: "#14161c"
  plate-foreground: "#ffffff"
  player-fallback: "#4f46e5"
  urgent: "#d0103a"
  urgent-soft: "#ffe1e6"
  urgent-ink: "#9f1239"
  warn-soft: "#fdefc3"
  warn-ink: "#8a3c0b"
  done: "#059669"
  destructive: "#d42a3c"
  night-ground: "#0f1115"
  night-card: "#191c22"
  night-field: "#242830"
  night-ink: "#f1f2f5"
  night-muted-ink: "#a0a6b2"
  night-outline: "#2b3039"
  night-plate: "#000000"
typography:
  headline:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.625rem"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 700
    lineHeight: "1.5rem"
  body:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: "1.5rem"
  body-strong:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 500
    lineHeight: "1.5rem"
  label:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: "1.25rem"
  label-strong:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 700
    lineHeight: "1.25rem"
    fontFeature: "tnum"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "20px"
  2xl: "24px"
  full: "9999px"
spacing:
  xs: "6px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "40px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.card}"
    typography: "{typography.body-strong}"
    rounded: "{rounded.full}"
    padding: "0 20px"
    height: "44px"
  button-outline:
    backgroundColor: "{colors.card}"
    textColor: "{colors.ink}"
    typography: "{typography.body-strong}"
    rounded: "{rounded.full}"
    padding: "0 20px"
    height: "44px"
  button-secondary:
    backgroundColor: "{colors.field-grey}"
    textColor: "{colors.ink}"
    typography: "{typography.body-strong}"
    rounded: "{rounded.full}"
    padding: "0 20px"
    height: "44px"
  button-destructive:
    backgroundColor: "{colors.destructive}"
    textColor: "{colors.card}"
    rounded: "{rounded.full}"
    padding: "0 20px"
    height: "44px"
  button-add:
    backgroundColor: "{colors.player-fallback}"
    textColor: "{colors.card}"
    rounded: "{rounded.full}"
    size: "56px"
  input:
    backgroundColor: "{colors.card}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.lg}"
    padding: "8px 16px"
    height: "48px"
  chip:
    backgroundColor: "{colors.card}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: "0 16px"
    height: "36px"
  chip-selected:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.board-white}"
    typography: "{typography.label-strong}"
    rounded: "{rounded.full}"
    padding: "0 16px"
    height: "36px"
  segmented-track:
    backgroundColor: "{colors.field-grey}"
    rounded: "{rounded.full}"
    padding: "4px"
  segmented-option-active:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.board-white}"
    typography: "{typography.label-strong}"
    rounded: "{rounded.full}"
    height: "36px"
  task-card:
    backgroundColor: "{colors.card}"
    textColor: "{colors.ink}"
    typography: "{typography.body-strong}"
    rounded: "{rounded.2xl}"
    padding: "10px 10px 10px 16px"
  up-next-plate:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.plate-foreground}"
    typography: "{typography.headline}"
    rounded: "{rounded.2xl}"
    padding: "16px 16px 16px 20px"
  complete-ring:
    rounded: "{rounded.full}"
    size: "44px"
  tab-pill-active:
    backgroundColor: "{colors.player-fallback}"
    textColor: "{colors.card}"
    rounded: "{rounded.full}"
    height: "32px"
    width: "56px"
  dialog:
    backgroundColor: "{colors.board-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.2xl}"
    padding: "20px"
---

# Design System: checkstu

## Overview

**Creative North Star: "Das Spielbrett"**

The app is a printed game board and every family member is a player with their own colour. Whoever is logged in sees the whole chrome tinted in their colour: the header band on phones, the full left rail on desktop, the active tab pill, the text selection, the caret, the add button and even the PWA status bar. Finishing a task moves your pawn home: a row of round home fields fills one by one, the pawn hops onto the next field. It refuses the grey list with one blue accent that every to-do app defaults to.

The ground is cool board white, never cream, with ink outlines (1.5px) around fields and flat, lacquered colour fills for members. Nothing has a gradient, nothing is glass, nothing rests on a shadow. Circles are the form language: home fields, round context buttons, round complete targets, pill-shaped everything else. Lexend runs at three sizes only; rank comes from weight and from the one reversed ink plate per screen. Density is phone-first and tight (cards stacked 8px apart), opening into real columns on tablet and desktop. Motion overshoots once and settles.

The UI speaks German and says "du". Progress is personal: the board only ever shows the logged-in player's own progress, never a comparison between family members.

**Key Characteristics:**
- Player-tinted chrome: one colour per user, set as a CSS variable on the layout root, with text colour chosen by WCAG contrast.
- Member colours as solid card fills; uncoloured cards are white with a 1.5px outline.
- Exactly one reversed ink plate per screen (the "Als Nächstes" tile on Today).
- Circles and pills everywhere; no square buttons.
- Three type sizes (13 / 16 / 26px), three weights (400 / 500 / 700).
- Flat at rest; shadows belong to overlays only.
- Overshoot motion, all of it off under reduced motion.

## Colors

A cool, near-neutral board carrying ink and exactly one saturated voice per screen: the player's colour, with member colours on cards and red/green reserved for urgency and completion.

### Primary
- **Player Colour** (per user, fallback Board Indigo `player-fallback`): the logged-in player's own colour, injected inline as `--player` with `--player-ink` (near-black ink or white, whichever wins on WCAG contrast). Paints the phone header band, the whole desktop rail, the Today corner behind the plate, the active bottom-tab pill, the add button, ticked episode circles, text selection and the input caret. Parents set each member's colour from a closed set of nine named presets (no free colour input; there is no self-service picker), chosen to sit clearly on one side of the black/white threshold and far enough apart to tell players apart. Red and green are not among them: those hues mean overdue and done. A colour another member already wears is crossed out in the picker.
- **Ink** (`ink`): text, the primary button, selected chips and segmented options, focus rings. Primary action is ink, not a brand hue.

### Secondary
- **Member Colours** (user-chosen, same preset list): the solid fill of a task or archive card assigned to that member, text computed by the same WCAG rule. Confetti on completion is tinted by the assignee's colour (plus amber and white).

### Tertiary
- **Urgent Red** (`urgent`, soft `urgent-soft`, deep `urgent-ink`): overdue section head and dates, the "Dringend" priority badge, swipe-to-delete reveal.
- **Warn Amber** (`warn-soft` / `warn-ink`): the "Hoch" priority badge only.
- **Done Green** (`done`): swipe-to-complete reveal.
- **Destructive** (`destructive`): destructive buttons.

### Neutral
- **Board White** (`board-white`): page ground and dialog/sheet surfaces.
- **Card White** (`card`): unassigned cards, inputs, menus, bottom tab bar.
- **Field Grey** (`field-grey`): segmented-control track, secondary buttons, hover and focus fills in menus.
- **Muted Ink** (`muted-ink`): secondary text, counts, inactive tabs.
- **Outline** (`outline`) and **Input Outline** (`input-outline`): 1.5px field outlines on cards and inputs respectively.
- **Night** set (`night-*`): the dark theme keeps the same roles; the plate goes pure black so it still reads as reversed against a dark ground.

### Named Rules
**The One Player Rule.** The chrome wears the logged-in player's colour and no other. Never hard-code a brand hue where `--player` belongs.

**The Computed Ink Rule.** Text on any player or member colour is computed by WCAG contrast (near-black `ink` or white), never chosen by hand and never by YIQ. That ink is never dimmed (no opacity or alpha on text over a member colour); secondary lines step down in weight or size instead, and hover outlines the control with a 1.5px inset ring rather than tinting the fill.

**The Reversed Plate Rule.** One ink plate per screen, for the single thing to do next. A second plate means the first is no longer next.

## Typography

**Display Font:** none
**Body Font:** Lexend 400 / 500 / 700 (loaded from fonts.bunny.net; fallback ui-sans-serif, system-ui)

**Character:** Lexend's wide, open letterforms read like printed game-box type: friendly, legible at arm's length on a phone, firm enough in bold to carry a 26px task title without a second face.

### Hierarchy
- **Headline** (700, 26px, 1.15, -0.025em): page titles and the task title on the up-next plate. Also the wordmark.
- **Title** (700, 16px, 24px): card titles, sheet titles, emphasised inline text.
- **Body** (400–500, 16px, 24px): task titles on cards (500), inputs, menu items, nav labels on the rail.
- **Label** (500–700, 13px, 20px): meta lines, due dates, chips, segmented options, sticky section heads (700), badges, bottom tab labels.

Every Tailwind size utility collapses onto these three (xs/sm to 13px, base/lg to 16px, xl/2xl/3xl to 26px), so no fourth size can creep in.

### Named Rules
**The Three Sizes Rule.** 13, 16, 26. Rank by weight (400/500/700) and by reversal onto the plate, never by a new size.

**The Tabular Column Rule.** Every date, count and progress number is tabular (`<time>` and `.tabular` get tabular figures by default), so right-aligned columns line up.

## Layout

Phone first. A 16px gutter, cards stacked 8px apart, the bottom tab bar fixed with the safe-area inset plus 8px, and content padded 128px at the bottom so the round add button never covers the last row. The add button sits 84px above the bottom edge and slides out while scrolling down, back on scroll up.

Header below `lg` (1024px) is a slim 56px band in the player colour, sticky, with the wordmark left and round context buttons plus a menu button right. Sticky section heads ("Überfällig", "Heute", "Blockiert") pin just under it on board white.

Content widths: 768px on phones, 1024px from `md`, 1152px from `lg` with a 40px gutter. From `lg` the header and bottom tabs disappear and a 256px left rail in the player colour takes over, with the add button docked in its top corner.

Today goes two-column from `md` (768px): a player-colour column of 352px (368px on `lg`) holding the greeting, the plate and the home fields, sticky on the left; the lists on the right. Task lists stay one column at every width (Aufgaben is held to 48rem), so due dates run down a single right-aligned column; other card lists (media, archive, family, plays) use one column on phones and two from `md` (8px gap, 12px on `lg`). A task page's back link returns to where you came from (Heute, Aufgaben or Archiv). Chip rows scroll sideways on phones and wrap on desktop.

Touch targets are 44px minimum: complete rings, round icon buttons, nav rows, the menu button.

## Elevation & Depth

Flat at rest. Cards, chrome, plate, tabs, header and rail carry no shadow, no gradient and no backdrop blur; depth on the board comes from solid colour fields, 1.5px outlines and the one reversed plate. Only things that float above the board get a shadow, an offset soft blur in black.

### Shadow Vocabulary
- **Menu lift** (`box-shadow: 0 8px 24px rgb(0 0 0 / 0.12)`): dropdown menus, select popovers, title autocomplete.
- **Sheet lift** (`box-shadow: 0 8px 32px rgb(0 0 0 / 0.18)`): side sheets.
- **Bottom sheet lift** (`box-shadow: 0 -8px 32px rgb(0 0 0 / 0.18)`): dialogs as bottom sheets, cast upward.

The modal scrim is flat black at 50% with no blur.

### Named Rules
**The Flat Board Rule.** If it rests on the board, it has no shadow. Shadows are for overlays only.

## Shapes

Circles and pills. Every button, chip, segmented control, tab pill, nav row and badge is fully rounded. The game pieces are true circles: 28px home fields with a 2.5px outline, 32px context buttons with a 2px outline, 44px complete rings with a 2px outline, a 64px pawn target with a 4px player-colour ring. Containers use generous radii: cards 24px, the plate and the Today corner 24px, dialogs 24px (top corners only as a bottom sheet), menus 24px with 20px items, inputs 16px.

Outlines are 1.5px throughout (cards, inputs, chips, empty states, dashed for empty states). The pawn is a solid currentColor SVG: ball head, cone body, rounded foot.

## Components

### Buttons
Pills, quick to press.
- **Shape:** fully round (9999px), 44px tall, 20px side padding; small 36px, large 52px, icon 44px square circle.
- **Primary:** ink fill, white text, Lexend 500 16px. Hover drops to 90%.
- **Hover / Focus:** colour transitions at 150ms; press scales to 0.97; focus is a 2px ink ring offset 2px.
- **Outline / Secondary / Ghost:** card fill with 1.5px input outline; field-grey fill; transparent with field-grey hover.
- **Add button:** 56px player-colour circle with a 4px board-white ring, bottom right above the tabs; on `lg` a 44px ink-on-player circle in the rail. Moves with ease-out-expo over 300ms.

### Chips
- **Style:** 36px pill, card fill, 1.5px outline, 13px Lexend 500, 16px side padding.
- **State:** selected flips to ink fill, board-white text, weight 700. Pill selects (40px) use the same flip when a filter is active and wrap a native select.

### Segmented Control
Field-grey pill track with 4px inset; options are 36px pills, active option in ink with bold board-white text, inactive in muted ink. Used for "Meine / Alle".

### Cards / Containers
- **Corner Style:** 24px.
- **Background:** the assignee's member colour as a solid fill with computed text; unassigned cards are card white with a 1.5px outline.
- **Shadow Strategy:** none (Flat Board Rule).
- **Internal Padding:** 10px top/right/bottom, 16px left; minimum height 64px.
- **Layout:** title (16px/500, two lines max, hyphenated) over a 13px meta line; a 64px right-aligned due column; a 44px round complete ring at the far right, with a check at 25% at rest (60% on hover/focus, solid on press) so touch users see what it does. The same ring ticks the next episode in Medien. Overdue dates go bold (urgent red on uncoloured cards).
- **Swipe:** dragging right reveals done green (deepening at the 96px threshold), left reveals urgent red for delete; the card springs back on ease-pop over 260ms. Blocked cards keep full colour and contrast; in place of the complete ring they show a dashed ring with a lock.
- **Liegen geblieben:** overdue cards older than three days fold into one dashed row ("Liegen geblieben n · älter als 3 Tage", a native `<details>`) above the overdue section, so today's work stays on top. A single occurrence can be skipped from the task page ("Überspringen", parents only), with the undo toast.

### Inputs / Fields
- **Style:** 48px tall, card fill, 1.5px input outline, 16px radius, 16px text; selects match at 48px with a 20px radius.
- **Focus:** 2px ink ring offset 2px; caret in the player colour.
- **Disabled:** 50% opacity.

### Navigation
- **Phone header:** 56px player-colour band, wordmark 16px/700, three 44px round context buttons (32px outlined circle inside; the active one filled in player-ink with the player colour inside): Haushalt (house), Medien (film), Brettspiele (dice). Inside Haushalt the tabs are Heute (sun) and Aufgaben (checklist); board games are Katalog and Partien.
- **Bottom tabs:** card white, top outline, 13px labels; the active tab gets a 56×32px player-colour pill behind its icon and a bold ink label.
- **Rail (`lg`):** 256px, whole rail in player colour, 44px pill rows; active row reverses to player-ink fill with player-colour text, hover draws a 1.5px inset ring in the current ink (never a tint, which would lower the text contrast).
- **Bottom tabs width:** the tab row stays within 448px, centred, so two tabs on a tablet don't drift to the edges.

### Undo Toast
- **Style:** ink bar (`foreground` fill, `background` text), 24px radius, 56px tall, task title (13px/700, truncated) over what happened ("Von dir erledigt", "Erledigt von Leo", "Gelöscht"), and a white "Rückgängig" pill. Sits above the tabs, left of the add button; bottom right at 384px wide on `lg`.
- **Behaviour:** after every completion, skip, task delete, archive restore, reassignment, episode tick, "Alle Folgen abhaken" and a show's "Serie ist durch / abbrechen"; 5 seconds, held open on hover or focus; after a keyboard action it takes focus itself (the card that had focus is gone), so undo is one Enter away; slides up 1rem on ease-out-expo over 300ms. Transient, so it is not a second reversed plate.

### Two-Tap Delete
- **Style:** a ghost trash circle (or trash + label as a full-width row in sheets and detail pages). The first tap arms it into a red `destructive` pill naming the action ("Mitglied löschen?", "Mit Partien?"); the second tap deletes; blur disarms.
- **When:** only for deletes that cannot be undone (members, library entries, games, plays). Tasks delete at once and offer the undo toast instead. Never the browser's `confirm()`.

### Game Shelf Tile
- **Style:** card with the square box cover on top (dice on field grey without a cover), name 16px/700 (tap to rename), players and play time, then the last play: short date and the winner's pawn in their member colour. Status chip ("Haben wir" / "Wollen wir") at the foot; the trash circle sits on the cover's top-right corner. Grid: 2 columns on phone, up to 5 on wide screens.

### Lost Taps
- **Behaviour:** a request that fails for lack of a connection keeps you on the page; the toast says "Nicht angekommen" (a change) or "Seite nicht geladen" (a visit) and its button reads "Nochmal", repeating that exact request. Only full page loads fall back to `offline.html`, which wears the board (ground, ink, Lexend, the pawn) and retries the page you asked for.

### Handing Out
- **Behaviour:** for parents, the assignee name in a card's meta line ("Leo ⌄", "Für alle ⌄") opens "Wer ist dran?": "Für alle" and every member with their colour dot, the current one ticked; one tap reassigns, with the undo toast ("Jetzt bei Leo"). Its tap area is stretched to 44px with an `::after`. Never on private tasks, and children see the plain name.

### Assignee and Recurrence Chips
- **Style:** in the task sheet, "Zuständig" is a chip row: "Für alle" first, then each member with a colour dot; the selected member chip fills with their colour (computed ink). "Wiederholung" (Einmalig · Regelmäßig · Unregelmäßig · Alle X Tage) sits right under the date it changes. Beschreibung lives under "Mehr Optionen".

### Date Quick Picks
- **Style:** a row of four chips under "Fällig am": Heute · Morgen · Irgendwann · Datum… (chip look, 10px side padding so all four fit at 375px). Only "Datum…" opens the native date input, and then shows the chosen date. Opened from Heute, a new task starts on "Heute".

### Dialogs
Bottom sheets on phones (24px top corners, board-white surface, slides up over 300ms), centred 512px cards with 24px corners from `sm` (640px), anchored near the top so the keyboard never hides them.

### Up-Next Plate and Home Fields (signature)
On the player-colour corner of Today: a greeting, then the reversed ink plate with the next task at 26px/700, a 13px due line at 75% white, and a 64px circular target holding the pawn in the player's colour (the pawn lifts and grows on hover with ease-pop). Below it, one 28px circle per own task today, filled in player-ink when done, the pawn standing on the next open field, and a bold "2 von 3 geschafft" count. Completing a task fills the field (`field-fill`, 400ms, ease-pop, from scale 0.4) and hops the pawn on (`pawn-hop`, 420ms, ease-out-expo). With nothing left, the plate says "Alles geschafft." and offers a dice button to the board games.

### Episode Row
A 52px list row: a 28px circle that fills in the player colour with a popping check when watched, the episode number right-aligned in tabular figures, the title, the air date, and a fold-out for watch dates.

## Do's and Don'ts

### Do:
- **Do** tint chrome with `--player` / `--player-ink` and compute text on any member or player colour with the WCAG contrast helper.
- **Do** keep type to 13, 16 and 26px and rank with weight (400/500/700) or the reversed plate.
- **Do** make interactive shapes circles or pills, at least 44px for touch.
- **Do** outline uncoloured fields with 1.5px lines and keep them flat.
- **Do** right-align dates in a shared tabular column.
- **Do** use ease-pop (`cubic-bezier(0.34, 1.56, 0.64, 1)`) for things that land and ease-out-expo (`cubic-bezier(0.16, 1, 0.3, 1)`) for things that travel, and switch all keyframe motion and confetti off under `prefers-reduced-motion`.
- **Do** write UI copy in German, addressing the player as "du".

### Don't:
- **Don't** build a leaderboard or any view that compares family members' progress; progress UI only ever shows the logged-in player's own progress.
- **Don't** put shadows, gradients or backdrop blur on anything resting on the board; shadows are for menus, sheets and dialogs.
- **Don't** place a second reversed ink plate on a screen.
- **Don't** introduce a fourth type size or a second typeface.
- **Don't** use cream or warm off-white grounds; the board is cool white (`board-white`).
- **Don't** hard-code a single brand accent where the player colour belongs.
