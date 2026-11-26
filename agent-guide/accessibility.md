# Accessibility

The templates of the theme aim at WCAG 2.2 level AA. An override, a template part or the markup of a project plugin keeps that level: follow the patterns below, and keep every attribute of the original that serves assistive technology when you copy a template.

## Landmarks and headings

- Keep the landmarks of the original: the header, the navigation, `<main id="main"` inside `<div id="primary"`, the sidebar and `<footer id="footer"`.
- Give a landmark that occurs more than once on a page a name of its own with `aria-label` or `aria-labelledby`, for example two navigations or two search forms. The sidebar gets its name from the filter [`creationell_wp_theme_sidebar_aria_label`](reference/hooks-templates.md#creationell_wp_theme_sidebar_aria_label).
- Give every page one `h1`. Keep the order of the headings without gaps: an `h3` follows an `h2`, never an `h1` directly.
- A heading that is only there for screen readers gets the class `visually-hidden`; do not drop it.

## Skip links

- `header.php` starts the page with two skip links: `#primary` (Skip to content) and `#footer` (Skip to footer). They are the first elements that take the focus.
- An override of `header.php` keeps both links in this place. An override of a page or single template keeps `<div id="primary"`, an override of `footer.php` keeps `<footer id="footer"`, so the links have a target.

## Links and buttons

- Every link and every button has a name that tells what it does, also out of context. A link with an icon only gets a text in `visually-hidden` or an `aria-label`; the icon itself is hidden with `aria-hidden="true"`.
- A "Read more" link adds the title of the post for screen readers.
- A link that opens a new window or tab says so in its name, for example "(opens in a new tab)". Open a new window only where the visitor needs it.
- Use a `button` for an action on the page and a link for a new address.

## Live regions

- A message that appears without a new page, such as the result of a form, a copied link or the number of filter results, goes into a live region: `role="status"` or `aria-live="polite"`, and `role="alert"` only for an error that needs attention at once.
- Put the live region into the markup when the page loads and change only its text; a region that is added together with its text is often not announced.
- Announce a message once, and keep the text visible as well; a color alone carries no meaning.

## Focus

- The focus is always visible. Do not remove the focus ring with `outline: 0` or `outline: none` without a visible replacement in the project SCSS.
- The sticky header does not hide the focused element: the theme sets `scroll-padding-top` from the height of the header. Keep it, and do not add another fixed bar at the top without the same offset.
- After an action that replaces content, such as an error after sending a form, move the focus to the first field with an error or to the heading of the result.
- A dialog keeps the focus inside while it is open, closes with Escape and gives the focus back to the control that opened it.
- The order of the focus follows the order on the screen. Do not use a positive `tabindex`.

## Disclosure with details

- Use `details` and `summary` for content that opens and closes.
- Do not give the `summary` a `role="heading"`: it takes away the button that screen readers announce. When the label must be a heading, use a heading with a button inside, as in the accordion of Bootstrap.

## Forms

- Every field has a visible label; a group of checkboxes or radios has a `fieldset` with a `legend`.
- Mark required fields in the label and with `required`; connect an error text to its field with `aria-describedby` and mark the field with `aria-invalid="true"`.

## Contrast, size and motion

- Text has a contrast of at least 4.5:1 to its background, large text and the borders of controls and the focus ring at least 3:1. This also holds in the dark color mode (`data-bs-theme="dark"`). The design settings refuse color pairs below the required contrast.
- Targets such as buttons, links in a row, checkboxes and radios are at least 24 x 24 px.
- At 200 % and 400 % zoom and at a width of 320 px the page does not scroll sideways, and nothing overlaps or is cut off.
- Respect the setting to reduce motion: no animation that starts by itself, and a slider does not move on its own.
- Hide decorative icons from assistive technology; name icons that carry meaning.
- Mark the language of a part in another language with `lang`.

## Quick check before finishing

The checklist of [AGENTS.md](../AGENTS.md#checklist-before-finishing) asks for this check of every changed screen:

1. Tab through the page: every control is reachable in a sensible order, and the focus is always visible and never under the sticky header.
2. The skip links reach `#primary` and `#footer`.
3. One `h1`, a heading order without gaps, and every repeated landmark has its own name.
4. Every link and button has a name; a new window is announced.
5. Messages without a new page are announced through a live region.
6. Contrast at least 4.5:1 for text, targets at least 24 x 24 px, no sideways scrolling at 320 px.
