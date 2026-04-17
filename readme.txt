=== PosterCast ===
Contributors: michalkonecny10
Tags: gallery, poster, lightbox, flyer, grid
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful poster and flyer galleries with responsive grid, full-screen lightbox, and Gutenberg block.

== Description ==

PosterCast is a simple yet powerful WordPress plugin for creating beautiful poster and flyer galleries. Display event posters, promotional flyers, menus, or any image collection in a clean, responsive grid with a stunning full-screen lightbox.

**Source code:** [GitHub](https://github.com/konmike/postercast)

= Key Features =

**Responsive CSS Grid Layout**
Display posters in a responsive 2-column grid with configurable gap. The grid automatically adapts to any screen size.

**Full-Screen Lightbox**
View posters in a beautiful full-screen lightbox with thumbnail navigation strip, keyboard controls (arrow keys, Escape), touch swipe gestures, image preloading, and accessibility features including focus trapping and ARIA attributes.

**Gutenberg Block**
A native block editor integration with visual settings. Configure poster count, gap, poster size, and "Show all" button — directly in the editor. Add new posters from the Media Library and edit poster details without leaving the editor.

**Shortcode Support**
Use the `[pcast_gallery]` shortcode to embed galleries in classic editor posts, widgets, or anywhere shortcodes are supported.

**Automatic Orientation Detection**
The plugin automatically detects whether each poster image is portrait or landscape and adjusts the grid layout accordingly. Manual override available per poster.

**Custom Poster URLs**
Each poster can have a custom URL displayed as a link button in the lightbox — perfect for linking to event pages, ticket sales, or external sites.

**REST API**
Built-in REST API endpoints for headless or custom integrations.

**Internationalization**
Fully translatable with 8 included languages: English, Czech, German, Slovak, Polish, French, Russian, and Chinese (Simplified).

= Use Cases =

* Event posters and concert flyers
* Movie and theater showtimes
* Restaurant menus and seasonal promotions
* Real estate listing sheets
* Product catalogs and lookbooks
* Artist portfolios and exhibition announcements
* Church bulletins and community boards

= PosterCast PRO =

Want more? [PosterCast PRO](https://michalkoneczny.gumroad.com/l/postercast) is a premium add-on that unlocks:

* **Multiple galleries** — organize posters into unlimited galleries
* **Custom columns** (1-8) with landscape/portrait column spanning
* **Drag & drop reordering** — dedicated admin page
* **Date scheduling** — posters appear/disappear automatically
* **Per-poster alignment** — horizontal and vertical
* **Advanced layout** — max height, max width, shadow toggle, poster background color
* **"Show All" button customization** — text, colors, border, radius
* **Custom lightbox link text**
* **Poster link format** — link any text to open a poster in the lightbox

= Shortcode Attributes =

`[pcast_gallery gallery_id="1" limit="12" gap="20" poster_size="medium_large" show_all_link="true"]`

* `gallery_id` — Gallery term ID (required)
* `limit` — Number of posters to display (default: 6)
* `gap` — Space between posters in pixels (default: 16)
* `poster_size` — WordPress image size slug (default: medium_large)
* `show_all_link` — Show "Show all" button: true/false (default: true)

Additional shortcode attributes available with PRO: `columns`, `landscape_span`, `portrait_span`, `poster_align`, `max_height`, `max_width`, `show_shadow`, `poster_background`, `lightbox_link_text`.

= Poster Link (data attribute) =

Link any element on the page to open a poster in the lightbox:

`<a href="#" data-pcast-open="123">View this poster</a>`

Where `123` is the poster's WordPress post ID. Note: a gallery block/shortcode must be on the same page for the lightbox to work.

= REST API =

* `GET /pcast/v1/posters?gallery_id=1` — List posters for a gallery
* `GET /pcast/v1/galleries` — List all galleries
* `POST /pcast/v1/order` — Update poster order (requires authentication)

= Accessibility =

* Full keyboard navigation in lightbox (arrows, Escape, Tab)
* Focus trapping within lightbox dialog
* ARIA attributes (role=dialog, aria-modal, aria-label)
* Semantic HTML with proper button and link elements
* Focus restoration when lightbox closes

== Installation ==

1. Upload the `postercast` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **PosterCast > All Posters** to create your first poster
4. Assign posters to the default gallery
5. Add the **PosterCast** Gutenberg block to any page or post
6. Alternatively, use the `[pcast_gallery gallery_id="1"]` shortcode

= Requirements =

* WordPress 6.0 or higher
* PHP 8.0 or higher

== Screenshots ==

1. Responsive poster grid on the frontend
2. Full-screen lightbox with thumbnail navigation
3. Lightbox with poster link button
4. Gutenberg block editor with settings panel
5. Poster admin list with custom columns

== Frequently Asked Questions ==

= How do I create a gallery? =

After activating the plugin, a default gallery is created automatically. Go to PosterCast > Add Poster, upload an image, and it will be added to the default gallery. Then add the PosterCast block to any page.

= Can I use multiple galleries? =

Multiple galleries are available with the [PosterCast PRO](https://michalkoneczny.gumroad.com/l/postercast) add-on.

= Does the lightbox work on mobile? =

Yes. The lightbox supports touch swipe gestures for navigation and is fully responsive.

= Can I customize the poster URL button text? =

The default text is "Visit link". Custom text per gallery is available with PRO.

= Is there a PRO version? =

Yes! [PosterCast PRO](https://michalkoneczny.gumroad.com/l/postercast) adds multiple galleries, custom columns, drag & drop reordering, date scheduling, and many more customization options.

== Changelog ==

= 1.0.0 =
* Initial release
* Responsive CSS grid layout
* Full-screen lightbox with thumbnail strip, keyboard and swipe navigation
* Gutenberg block with gallery settings
* Shortcode support
* Automatic image orientation detection
* Custom poster URLs
* REST API endpoints
* 8 included translations
* Accessibility: keyboard navigation, focus trap, ARIA attributes

== Upgrade Notice ==

= 1.0.0 =
Initial release of PosterCast.
