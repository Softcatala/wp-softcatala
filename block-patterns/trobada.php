<?php
/**
 * Title: Pàgina de trobada
 * Slug: softcatala/trobada
 * Description: Una trobada sencera: com apuntar-s'hi, les sessions del dia en columnes i la taula «Qui va a què» amb els totals automàtics.
 * Categories: softcatala
 * Keywords: trobada, assemblea, sopar, assistència
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1100
 *
 * Plain block markup, no PHP: frontend/tests/blocks.test.ts validates it.
 * The attendance table's Total row is filled in when the page renders
 * (Softcatala\Blocks\AttendanceTable), so it ships empty.
 *
 * @package Softcatala
 */

?>
<!-- wp:paragraph {"className":"is-style-avis-info"} -->
<p class="is-style-avis-info"><strong>Per apuntar-vos:</strong> editeu aquesta pàgina i afegiu-vos a la taula «Qui va a què». Poseu ✓ (o «sí») a cada sessió on vingueu. Si veniu acompanyats, afegiu-hi «+1» per cada acompanyant i «+1 nen» per cada infant. Els totals es calculen sols.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Dissabte, dia de mes</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
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
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Sessió de la tarda</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>16:00 – 20:00</strong><br><a href="https://www.softcatala.org/">Lloc</a>, sala</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Ordre del dia</strong></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Primer punt — responsable</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Segon punt — responsable</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Dinar</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>14:00 – 16:00</strong><br><a href="https://www.softcatala.org/">Restaurant</a></p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Sopar</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>21:00</strong><br><a href="https://www.softcatala.org/">Restaurant</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Indiqueu acompanyants i infants a la taula: el restaurant necessita saber quants menús infantils cal.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Qui va a què</h2>
<!-- /wp:heading -->

<!-- wp:table {"hasFixedLayout":false,"className":"is-style-assistencia"} -->
<figure class="wp-block-table is-style-assistencia"><table><thead><tr><th>Persona</th><th class="has-text-align-center" data-align="center">Matí</th><th class="has-text-align-center" data-align="center">Dinar</th><th class="has-text-align-center" data-align="center">Tarda</th><th class="has-text-align-center" data-align="center">Sopar</th></tr></thead><tbody><tr><td>El vostre nom</td><td class="has-text-align-center" data-align="center">✓</td><td class="has-text-align-center" data-align="center">✓</td><td class="has-text-align-center" data-align="center">✓</td><td class="has-text-align-center" data-align="center">✓ +1</td></tr></tbody></table></figure>
<!-- /wp:table -->
