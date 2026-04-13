/**
 * Kanban board — filter bar, URL hash sync, drag-and-drop, task detail modal.
 *
 * Requires: jQuery, window.Sortable (SortableJS), window.sc_kanban (localised
 * via wp_localize_script in archive-tasca.php).
 *
 * Dependencies on HTML structure set by archive-tasca.twig:
 *   #kanban-board            — board container
 *   .kanban-column           — one column per estat_tasca term; data-estat attribute
 *   .kanban-column__cards    — drop zone inside each column
 *   .kanban-card             — task card; data-id, data-projecte, data-assignee,
 *                              data-milestone, data-estat, data-tags, data-due
 *   .kanban-card__handle     — drag handle
 *   #kanban-modal            — Bootstrap 3 modal
 *   #kanban-modal-label      — modal title element
 *   #kanban-modal-body       — modal body element
 *   #kanban-modals           — hidden container of pre-rendered task detail divs
 *   #task-detail-{id}        — pre-rendered detail div per task
 *   .kanban-filter           — filter <select> elements; data-filter-type attribute
 *   #kanban-reset-filters    — reset button
 *   .kanban-column__count    — badge showing visible card count per column
 *   #kanban-notice           — dismissible notice container (fixed, bottom-right)
 *
 * Intra-column ordering is intentionally disabled (sort: false per column).
 * PATCH updates column assignment only, not position within a column.
 * If intra-column position tracking is needed in future, add a `position` meta
 * field to tasca and a separate PATCH endpoint.
 */

jQuery( document ).ready( function ( $ ) {

	// ─── Guard: board must be present ─────────────────────────────────────────
	if ( ! $( '#kanban-board' ).length ) {
		return;
	}

	var isLoggedIn    = !! ( window.sc_kanban && window.sc_kanban.is_logged_in );
	var restUrl       = window.sc_kanban ? window.sc_kanban.rest_url : '';
	var nonce         = window.sc_kanban ? window.sc_kanban.nonce : '';
	var filterState   = {};
	var isDragging    = false;

	// ─── Mark overdue due-date badges ─────────────────────────────────────────
	var today = new Date();
	today.setHours( 0, 0, 0, 0 );
	$( '.kanban-card__badge--due[data-due]' ).each( function () {
		var raw = $( this ).data( 'due' );
		if ( ! raw ) { return; }
		// Expect YYYY-MM-DD from ACF date_picker return_format
		var parts = String( raw ).split( '-' );
		if ( parts.length !== 3 ) { return; }
		var due = new Date( parts[0], parts[1] - 1, parts[2] );
		if ( due < today ) {
			$( this ).addClass( 'is-overdue' );
		}
	} );

	// ─── Column collapse toggle ────────────────────────────────────────────────

	var COL_WIDTH     = 220; // px — must match .kanban-column width in kanban.css
	var COL_COLLAPSED = 48;  // px — must match .kanban-column--collapsed width
	var COL_GAP       = 14;  // px — must match .kanban-board gap

	/**
	 * Toggle collapsed state on a collapsible column.
	 * Works for both click and keyboard (Enter/Space) on the header.
	 */
	function toggleColumnCollapse( $column ) {
		var collapsed = $column.hasClass( 'kanban-column--collapsed' );
		$column.toggleClass( 'kanban-column--collapsed', ! collapsed );
		$column.find( '.kanban-column__header' )
		       .attr( 'aria-expanded', collapsed ? 'true' : 'false' );
	}

	/**
	 * On load: decide which empty collapsible columns to collapse.
	 *
	 * Strategy: if all columns fit within the board's available width, expand
	 * everything. If not, collapse empty collapsible columns one by one (in
	 * reverse order — last columns first) until everything fits.
	 */
	function initCollapseState() {
		var $board       = $( '#kanban-board' );
		var boardWidth   = $board.parent().width(); // available width (container)
		var $allCols     = $board.children( '.kanban-column, .kanban-column--terminal' );
		var totalCols    = $allCols.length;

		// Width if every column were fully expanded.
		var fullWidth = totalCols * COL_WIDTH + Math.max( 0, totalCols - 1 ) * COL_GAP;

		// First: strip any server-rendered collapsed classes — JS owns this now.
		$allCols.each( function () {
			$( this ).removeClass( 'kanban-column--collapsed' );
			$( this ).find( '.kanban-column__header' ).attr( 'aria-expanded', 'true' );
		} );

		if ( fullWidth <= boardWidth ) {
			// Everything fits — nothing to collapse.
			return;
		}

		// Collect empty collapsible columns (candidates for collapsing).
		// We collapse from the end of the board backwards so the most-used
		// columns (earlier in the flow) stay expanded first.
		var $candidates = [];
		$allCols.each( function () {
			var $col = $( this );
			if (
				$col.data( 'collapsible' ) === 1 &&
				$col.find( '.kanban-card' ).length === 0
			) {
				$candidates.push( $col );
			}
		} );
		$candidates.reverse(); // collapse rightmost empty columns first

		var currentWidth = fullWidth;
		for ( var i = 0; i < $candidates.length; i++ ) {
			if ( currentWidth <= boardWidth ) {
				break;
			}
			var $col = $candidates[ i ];
			$col.addClass( 'kanban-column--collapsed' );
			$col.find( '.kanban-column__header' ).attr( 'aria-expanded', 'false' );
			// Collapsing saves (COL_WIDTH - COL_COLLAPSED) px.
			currentWidth -= ( COL_WIDTH - COL_COLLAPSED );
		}
	}

	initCollapseState();

	$( document ).on( 'click', '.kanban-column[data-collapsible="1"] .kanban-column__header', function () {
		toggleColumnCollapse( $( this ).closest( '.kanban-column' ) );
	} );

	$( document ).on( 'keydown', '.kanban-column[data-collapsible="1"] .kanban-column__header', function ( e ) {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			toggleColumnCollapse( $( this ).closest( '.kanban-column' ) );
		}
	} );

	// ─── Filter helpers ────────────────────────────────────────────────────────

	/**
	 * Parse the URL hash into a filter state object.
	 * Only permits [a-z0-9_-] characters in values to prevent injection via hash.
	 *
	 * @returns {Object}
	 */
	function parseHash() {
		var hash   = window.location.hash.replace( '#', '' );
		var state  = {};
		if ( ! hash ) {
			return state;
		}
		hash.split( '&' ).forEach( function ( part ) {
			var kv    = part.split( '=' );
			var key   = kv[0];
			var value = kv[1] ? decodeURIComponent( kv[1] ) : '';
			// Sanitise: only allow safe characters.
			value = value.replace( /[^a-z0-9_\-]/gi, '' );
			if ( key && value ) {
				state[ key ] = value;
			}
		} );
		return state;
	}

	/**
	 * Serialise the current filter state back to a URL hash string.
	 */
	function serialiseHash( state ) {
		var parts = [];
		Object.keys( state ).forEach( function ( key ) {
			if ( state[ key ] ) {
				parts.push( key + '=' + encodeURIComponent( state[ key ] ) );
			}
		} );
		return parts.length ? '#' + parts.join( '&' ) : '';
	}

	/**
	 * Apply the current filterState to all cards: show/hide based on data-*.
	 * Also updates column count badges.
	 */
	function applyFilters() {
		$( '.kanban-card' ).each( function () {
			var $card   = $( this );
			var visible = true;

			if ( filterState.project && $card.data( 'projecte' ) !== filterState.project ) {
				visible = false;
			}
			if ( filterState.assignee ) {
				var assignees = String( $card.data( 'assignee' ) || '' ).split( ',' );
				if ( assignees.indexOf( filterState.assignee ) === -1 ) {
					visible = false;
				}
			}
			if ( filterState.milestone && String( $card.data( 'milestone' ) ) !== filterState.milestone ) {
				visible = false;
			}
			if ( filterState.estat && $card.data( 'estat' ) !== filterState.estat ) {
				visible = false;
			}
			if ( filterState.tag ) {
				var tags = String( $card.data( 'tags' ) || '' ).split( ',' );
				if ( tags.indexOf( filterState.tag ) === -1 ) {
					visible = false;
				}
			}

			$card.toggle( visible );
		} );

		// Update column card counts.
		$( '.kanban-column' ).each( function () {
			var count = $( this ).find( '.kanban-card:visible' ).length;
			$( this ).find( '.kanban-column__count' ).text( count );
		} );
	}

	/**
	 * Sync the <select> filter controls to the current filterState.
	 */
	function syncSelects() {
		$( '.kanban-filter' ).each( function () {
			var type = $( this ).data( 'filter-type' );
			$( this ).val( filterState[ type ] || '' );
		} );
	}

	/**
	 * Read the hash, update filterState, sync selects, apply.
	 */
	function loadFromHash() {
		filterState = parseHash();
		syncSelects();
		applyFilters();
	}

	// ─── Filter bar events ─────────────────────────────────────────────────────

	$( '.kanban-filter' ).on( 'change', function () {
		var type  = $( this ).data( 'filter-type' );
		var value = $( this ).val();
		if ( value ) {
			filterState[ type ] = value;
		} else {
			delete filterState[ type ];
		}
		window.location.hash = serialiseHash( filterState );
		applyFilters();
	} );

	$( '#kanban-reset-filters' ).on( 'click', function () {
		filterState = {};
		history.replaceState( null, '', window.location.pathname + window.location.search );
		syncSelects();
		applyFilters();
	} );

	$( window ).on( 'hashchange', function () {
		loadFromHash();
	} );

	// Apply filters immediately from hash on page load.
	loadFromHash();

	// ─── Task detail modal ─────────────────────────────────────────────────────

	$( document ).on( 'click', '.kanban-card', function ( e ) {
		// Ignore clicks on the drag handle, and ignore clicks while dragging.
		if ( $( e.target ).closest( '.kanban-card__handle' ).length || isDragging ) {
			return;
		}

		var id      = $( this ).data( 'id' );
		var title   = $( this ).find( '.kanban-card__title' ).text().trim();
		var $detail = $( '#task-detail-' + id );

		if ( ! $detail.length ) {
			return;
		}

		$( '#kanban-modal-label' ).text( title );
		$( '#kanban-modal-body' ).html( $detail.html() );
		$( '#kanban-modal' ).modal( 'show' );
	} );

	// Allow keyboard activation of cards (Enter / Space).
	$( document ).on( 'keydown', '.kanban-card', function ( e ) {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			$( this ).trigger( 'click' );
		}
	} );

	// ─── Notice helpers ────────────────────────────────────────────────────────

	function showNotice( message, type ) {
		var $notice = $( '#kanban-notice' );
		$notice
			.removeClass( 'alert-danger alert-warning alert-info alert-success' )
			.addClass( 'alert-' + ( type || 'danger' ) )
			.html( message + ' <button type="button" class="close" aria-label="Tanca"><span aria-hidden="true">&times;</span></button>' )
			.show();
	}

	$( document ).on( 'click', '#kanban-notice .close', function () {
		$( '#kanban-notice' ).hide();
	} );

	// ─── PATCH helper ─────────────────────────────────────────────────────────

	/**
	 * PATCH the task's estat_tasca term via the REST endpoint.
	 * Implements optimistic UI: card is already moved; revert on failure.
	 *
	 * @param {number}  id             Task post ID.
	 * @param {string}  newEstat       Slug of the target estat_tasca term.
	 * @param {jQuery}  $card          The dragged card element.
	 * @param {Element} originalColumn The source .kanban-column__cards element.
	 * @param {Element} nextSibling    Next sibling in the source column (for revert).
	 */
	function patchTaskEstat( id, newEstat, $card, originalColumn, nextSibling ) {
		fetch( restUrl + id + '/estat', {
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce
			},
			body: JSON.stringify( { estat: newEstat } )
		} )
			.then( function ( response ) {
				if ( response.ok ) {
					// Update the card's data attribute so future filters work.
					$card.attr( 'data-estat', newEstat );
					return;
				}
				// Non-2xx: revert card.
				revertCard( $card, originalColumn, nextSibling );
				if ( response.status === 401 ) {
					showNotice(
						'La sessió ha caducat. <a href="' + window.location.pathname + '">Torneu a iniciar sessió</a>.',
						'warning'
					);
				} else {
					showNotice( 'S\'ha produït un error en desar el canvi. Torneu-ho a provar.', 'danger' );
				}
			} )
			.catch( function () {
				revertCard( $card, originalColumn, nextSibling );
				showNotice( 'S\'ha produït un error de xarxa. Torneu-ho a provar.', 'danger' );
			} );
	}

	/**
	 * Move a card back to its original column.
	 */
	function revertCard( $card, originalColumn, nextSibling ) {
		if ( nextSibling ) {
			$( originalColumn ).insertBefore( $card[0], nextSibling );
		} else {
			$( originalColumn ).append( $card[0] );
		}
	}

	// ─── Drag-and-drop (logged-in only) ───────────────────────────────────────

	if ( isLoggedIn && typeof window.Sortable !== 'undefined' ) {

		$( '.kanban-column__cards' ).each( function () {
			var columnEl = this;

			window.Sortable.create( columnEl, {
				group: {
					name: 'kanban',
					pull: true,
					put: true
				},
				animation:  150,
				handle:     '.kanban-card__handle',
				draggable:  '.kanban-card',
				sort:       false, // Intra-column reorder disabled (see file header).
				ghostClass: 'kanban-card--ghost',

				onStart: function () {
					isDragging = true;
				},

				onEnd: function () {
					// Small delay so the click handler's isDragging guard fires first.
					setTimeout( function () { isDragging = false; }, 50 );
				},

				onAdd: function ( evt ) {
					// Cross-column drop only (onUpdate never fires because sort: false).
					if ( evt.from === evt.to ) {
						return;
					}

					var $card          = $( evt.item );
					var cardId         = $card.data( 'id' );
					var newEstat       = $( evt.to ).closest( '.kanban-column' ).data( 'estat' );
					var originalColumn = evt.from;
					var nextSibling    = evt.oldIndex < evt.from.children.length
						? evt.from.children[ evt.oldIndex ]
						: null;

					if ( ! cardId || newEstat === undefined ) {
						return;
					}

					// Update column counts immediately (optimistic).
					updateColumnCounts();

					patchTaskEstat( cardId, newEstat, $card, originalColumn, nextSibling );
				}
			} );
		} );
	}

	function updateColumnCounts() {
		$( '.kanban-column' ).each( function () {
			var count = $( this ).find( '.kanban-card:visible' ).length;
			$( this ).find( '.kanban-column__count' ).text( count );
		} );
	}

} );
