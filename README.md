# PosterCast

Beautiful poster and flyer galleries for WordPress — responsive grid, full-screen lightbox, and Gutenberg block.

## Features

- **Responsive CSS Grid** — configurable columns, gap, and smart landscape/portrait spanning
- **Full-Screen Lightbox** — thumbnail strip, keyboard & swipe navigation, image preloading, ARIA accessibility
- **Gutenberg Block** — native block with 20+ visual settings, inline poster editing
- **Shortcode** — `[pcast_gallery]` with full attribute support
- **Auto Orientation Detection** — portrait/landscape detected from image, manual override available
- **Custom Poster URLs** — link button in lightbox for tickets, events, external sites
- **REST API** — endpoints for headless/custom integrations
- **8 Languages** — EN, CS, DE, SK, PL, FR, RU, ZH

## Requirements

- WordPress 6.0+
- PHP 8.0+

## Installation

1. Upload the `postercast` folder to `/wp-content/plugins/`
2. Activate through **Plugins** menu
3. Go to **PosterCast > Add Poster** to create your first poster
4. Add the **PosterCast** block or `[pcast_gallery gallery_id="1"]` shortcode to any page

## Shortcode

```
[pcast_gallery gallery_id="1" limit="12" gap="20" poster_size="medium_large" show_all_link="true"]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `gallery_id` | — | Gallery term ID (required) |
| `limit` | 6 | Number of posters to display |
| `gap` | 16 | Space between posters (px) |
| `poster_size` | medium_large | WordPress image size slug |
| `show_all_link` | true | Show "Show all" button |

PRO adds: `columns`, `landscape_span`, `portrait_span`, `poster_align`, `max_height`, `max_width`, `show_shadow`, `poster_background`, `lightbox_link_text`.

## REST API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/pcast/v1/posters?gallery_id=1` | No | List posters for a gallery |
| GET | `/pcast/v1/galleries` | No | List all galleries |
| POST | `/pcast/v1/order` | Yes | Update poster order |

## Poster Links

Link any element to open a poster in the lightbox:

```html
<a href="#" data-pcast-open="123">View this poster</a>
```

A gallery block/shortcode must be on the same page for the lightbox to work.

## PosterCast PRO

Premium add-on with additional features:

- Multiple galleries
- Custom columns (1–8) with landscape/portrait spanning
- Drag & drop reordering
- Date scheduling (show from / show until)
- Per-poster alignment
- Advanced layout controls
- "Show All" button customization
- Custom lightbox link text
- Poster link format (rich text toolbar)

[Get PosterCast PRO](https://michalkoneczny.gumroad.com/l/poster-gallery)

## Build

```bash
npm install
npm run build
```

## License

GPLv2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
