# Ticket Categories

`wp_sbay_categories` owns reusable ticket classification records. Categories contain a unique slug, optional description, optional department applicability, active/inactive status, color, sort order, and WordPress-local timestamps.

Tickets reference categories through nullable `category_id`. Existing and uncategorized tickets remain valid. Category lifecycle is owned by the Categories module; ticket repositories persist only the reference.

Protected REST routes under `/sbay/v1/categories` allow ticket staff to read categories and users with `sbay_manage_categories` to create, update, and delete them.
