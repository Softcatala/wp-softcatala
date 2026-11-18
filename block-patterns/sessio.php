<?php
/**
 * Title: Sessió de trobada
 * Slug: softcatala/sessio
 * Description: Una sessió: títol, hora i lloc, i l'ordre del dia amb responsables. Pensada per a una columna de la pàgina de trobada.
 * Categories: softcatala
 * Keywords: trobada, sessió, ordre del dia, assemblea
 * Viewport Width: 400
 *
 * Plain block markup, no PHP: frontend/tests/blocks.test.ts validates it.
 *
 * @package Softcatala
 */

?>
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Sessió del matí</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>10:00 – 14:00</strong><br><a href="https://www.softcatala.org/">Lloc</a>, sala</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Ordre del dia</strong> (poseu-hi el responsable de cada punt)</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Primer punt — responsable</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Segon punt — responsable</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
