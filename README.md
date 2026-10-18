# ajs-builder

A lightweight, dependency-free drag & drop page builder with sections, columns, modules, global styles and header/footer templates.

== Description ==

AJS Builder is a fast, self-contained visual builder for WordPress. Design pixel-perfect pages, headers, footers and reusable section templates with drag & drop, then publish them anywhere. It ships with no external dependencies, no remote calls and no upsells.

**Build visually**

* Drag & drop sections, columns and modules on a live canvas preview.
* Contextual on-canvas toolbars for sections, columns and rows — edit, duplicate, delete and add columns without leaving the preview.
* Structure panel for nested selections and reordering.
* Responsive editing with dedicated Desktop, Tablet and Mobile contexts; values a smaller device doesn't set inherit from the breakpoint above.

**Sections, columns and the Row module**

* Add sections with up to six columns and control per-device stacking, gaps, alignment, background and spacing.
* The **Row** module splits any column into 1–6 nested columns of its own, each able to hold further modules — build complex grids without leaving the flow.

**60+ modules**

* **Layout** – Heading, Text Editor, Image, Button, Divider, Spacer, Icon, HTML, Logo, Row.
* **Content** – Icon Box, Image Box, Icon List, Counter, Testimonial, Call to Action, Accordion, Tabs, Progress Bar, Social Icons.
* **Blog** – Posts, Loop Carousel, Author Box.
* **WooCommerce** – Products, Products Carousel, Product Category Tabs, Featured Category.
* **Media** – Gallery, Video, Image Slider, Google Map.
* **Header/Footer** – Navigation, Search, Cart, My Account, Compare.
* **WordPress Widgets** – Archives, Calendar, Categories, Pages, Meta, Recent Posts, Recent Comments, Tag Cloud, RSS.
* **Forms** – Contact Form 7.

Every module can be enabled or disabled from the settings screen.

**Global styles and presets**

* Global colors, typography and button tokens applied consistently across the site.
* Ready-made design presets, shadow presets and a font stack that requires no remote fonts.

**Templates**

* Create headers, footers and reusable section templates with display conditions and sticky options.
* Six responsive starter templates (three headers, three footers) to get going quickly.
* Save any section as a reusable template from the Structure panel.
* Import and export templates as JSON to move designs between sites.

**WooCommerce ready**

* Product grids, carousels, category tabs, a featured category module, plus cart, my-account and compare header modules. WooCommerce modules render gracefully when WooCommerce is not active.

**Developer friendly**

* Attributes content with `data-element`, `data-widget`, `data-id` and `data-ajs-id` hooks for targeting.
* Actions, filters and template tags such as `ajs_builder_widgets`, `ajs_builder_template`, `ajs_builder_render_frontend` and `ajs_builder_style_presets` for extending behaviour.

== Installation ==

1. Upload the `ajs-builder` folder to the `/wp-content/plugins/` directory, or install the plugin through the **Plugins > Add New** screen.
2. Activate the plugin through the **Plugins** screen.
3. Open **AJS Builder > Settings** to choose global styles, container width and which modules are available.
4. Edit any page or post and click **Edit with Builder** in the Publish metabox or the admin bar / row action.

== Frequently Asked Questions ==

= Does it require Elementor, Bootstrap or any other plugin? =

No. The builder is fully self-contained and has no runtime dependency on other page builders or front-end frameworks.

= Where do I build headers and footers? =

Go to **AJS Builder > Headers & Footers**, create a template, choose a type (header or footer), set its display conditions, then design it in the builder.

= How do I reuse a section across pages? =

Select the section in the **Structure** panel and click the star icon to **Save as template**, or upload a JSON export from the **Templates** panel. Insert saved templates into any page.

= Can I control what is visible on mobile? =

Yes. Each element has per-device visibility controls, and most style values can be set independently for Desktop, Tablet and Mobile.

= Does it work with WooCommerce? =

Yes. WooCommerce modules appear when WooCommerce is active and render safely when it is not.

== Screenshots ==

1. The builder editor with the Modules panel and live canvas.
2. Responsive editing with the Desktop, Tablet and Mobile tabs.
3. The Structure panel for selecting and reordering nested elements.
4. The Templates panel for reusable section templates.
5. Header and footer template management.

== Changelog ==

= 1.11.6 =
* Added the Row layout module: split any column into 1–6 nested columns with per-device gap, stacking and alignment controls.
* Added on-canvas toolbars for Sections, Columns and Rows (edit, duplicate, delete, add column).
* "Add Section" and the on-canvas "+" now reuse the current section instead of stacking duplicate sections.
* Renamed the column element consistently across the editor labels.
* Added the Image Box, Image Slider and Featured Category modules.
* Added the documentation reference and About screen.

= 1.11.5 and earlier =
* See the plugin's previous release notes.

== Upgrade Notice ==

= 1.11.6 =
Adds the Row layout module and on-canvas editing toolbars. No configuration changes required.
