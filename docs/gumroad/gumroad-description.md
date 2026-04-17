# PosterCast — WordPress Plugin

A powerful yet simple WordPress plugin for creating beautiful poster and flyer galleries. Display event posters, promotional flyers, menus, or any image collection in a responsive grid with a stunning full-screen lightbox.

---

## Key Features

**Responsive CSS Grid Layout**
Configurable columns, gap, poster sizing, and smart landscape/portrait spanning. Looks great on every screen size.

**Full-Screen Lightbox**
Thumbnail navigation strip, keyboard controls (arrows, Escape), touch swipe gestures, image preloading, custom link buttons per poster, and full accessibility (ARIA, focus trap).

**Gutenberg Block**
Native block editor integration with 20+ settings. Add, edit, reorder posters, and preview the grid — all without leaving the editor.

**Shortcode Support**
`[pcast_gallery gallery_id="1" columns="3" gap="20" max_height="500"]`
All block settings available as shortcode attributes. Works in classic editor, widgets, and anywhere shortcodes are supported.

**Date-Based Scheduling**
Set "show from" and "show until" dates on each poster. Posters appear and disappear automatically — perfect for time-limited events and seasonal promotions.

**Drag & Drop Ordering**
Dedicated admin page for reordering posters with an intuitive drag & drop interface.

**Multiple Galleries**
Organize posters into unlimited galleries. Display different galleries on different pages, or multiple on the same page.

**Poster Link Format**
Link any text on your page to a poster — select text in the editor, pick a poster from the toolbar, and it opens in the lightbox on click. Also works via `data-pg-open` HTML attribute.

**Auto Orientation Detection**
Automatically detects portrait/landscape from image dimensions and adjusts the grid layout. Manual override available per poster.

**Per-Poster Settings**
Each poster supports: custom URL (link button in lightbox), horizontal & vertical alignment, orientation mode, and visibility scheduling.

---

## Complete Feature List

- Responsive CSS grid layout
- Full-screen lightbox with thumbnail strip
- Keyboard navigation (arrows, Escape, Tab)
- Touch swipe gestures on mobile
- Image preloading for instant transitions
- Gutenberg block with 20+ configurable settings
- Shortcode with all options
- Multiple galleries (unlimited)
- Drag & drop reordering
- Date-based poster scheduling
- Automatic orientation detection
- Custom poster URLs & link buttons
- Per-poster alignment control (horizontal + vertical)
- Inline poster link format (toolbar button)
- Customizable "Show all" button (text, colors, border, radius)
- Poster shadow toggle
- Custom poster background color
- Wide & full-width alignment support
- REST API endpoints for headless usage
- ARIA attributes & focus trap accessibility
- No jQuery dependency on frontend
- Assets loaded only when gallery is rendered
- Automatic migration from older versions
- 8 languages: English, Czech, German, Slovak, Polish, French, Russian, Chinese

---

## Block Settings

- Gallery selection with inline creation
- Number of columns (1–6)
- Landscape & portrait column span
- Poster alignment (stretch, center, left, right)
- Maximum poster height & gallery width
- Gap between posters
- Poster background color
- Shadow toggle
- "Show all" button: text, count, colors, border, radius
- Lightbox link button text
- Spacing (padding & margin) via WordPress native controls

## Shortcode Attributes

```
[pcast_gallery
  gallery_id="1"
  limit="12"
  columns="3"
  gap="20"
  max_height="500"
  poster_align="center"
  landscape_span="2"
  portrait_span="1"
  poster_size="medium_large"
  poster_background="#ffffff"
  show_shadow="true"
  show_all_link="true"
  lightbox_link_text="View event"
]
```

---

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

## License

GPLv2 or later
