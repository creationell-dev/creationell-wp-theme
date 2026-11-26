# Change the card markup of the post blocks

## Goal

Change the classes or the markup of the post cards in the child theme. The blocks post list, post slider and related posts show the same cards, so one change reaches all three.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- `wp creationell-theme module list` shows the module of the block as `active`: `post-lists`, `post-slider` or `related-posts`.
- The cards are `template-parts/post-lists/card-grid.php`, `template-parts/post-lists/card-list.php` and `template-parts/post-lists/card-hero.php` of the parent theme.

## Steps

1. Try the class filter first. Every element of a card takes its classes from the filter [`creationell_wp_theme_post_card_classes`](../reference/hooks-core.md#creationell_wp_theme_post_card_classes). It gets the classes by element, the card part and the context of the block (`post-list`, `post-slider`, `related-posts`). Add a callback to the `functions.php` of the child theme:

   ```php
   add_filter(
   	'creationell_wp_theme_post_card_classes',
   	static function ( array $classes, string $part, string $context ): array {
   		if ( 'card-grid' === $part && 'related-posts' === $context ) {
   			$classes['card'] .= ' shadow-sm';
   		}
   		return $classes;
   	},
   	10,
   	3
   );
   ```

   The keys of `$classes` are elements such as `card`, `image`, `body`, `title`, `link` and `meta`.
2. Copy a card only when the markup itself must change. Copy it to the same path in the child theme, for example `template-parts/post-lists/card-grid.php`, as [override-child-template.md](override-child-template.md) shows.
3. In the copy, keep one link per card (the title), `alt=""` on the image, the hidden post title after "Read more", every escaping call and the text domain `creationell-wp-theme` for texts that come from the parent theme.
4. After an update of the parent theme, compare the copy with the new card and carry the changes over.

## Check

- `wp creationell-theme child check` gives `Success: 0 violations.`; a card at the path of the parent theme passes the rule `allowed-paths`.
- `wp creationell-theme doctor` reports no new problem.
- Open a page with a post list on the site: the cards show the new classes or markup, and each card has one link.

## Common mistakes

- The post accordion shows panels (`template-parts/post-lists/panel.php`), not cards: the card filter does not run there.
- A callback with two parameters gets no context: register it with `10, 3` as above.
- A copied card under another name, or a copy in a project plugin, is never loaded: the template part must sit at the path of the parent theme.
- A copy that drops the hidden title or the escaping breaks accessibility and security ([../accessibility.md](../accessibility.md), [../security.md](../security.md)).
