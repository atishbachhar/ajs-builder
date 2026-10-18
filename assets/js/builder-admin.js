/*!
 * AJS Builder — visual editor (admin).
 * Same-origin iframe preview: we render server-side via REST and manipulate the
 * preview DOM directly for selection and drag & drop.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.ajsBuilder;
	var app = document.getElementById( 'ajs-app' );
	if ( ! cfg || ! app ) {
		return;
	}

	/* ------------------------------------------------------------- Icons */
	var ICON = {
		dup: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
		trash: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
		move: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>',
		chev: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>',
		plus: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
		img: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
		section: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="4" width="18" height="7" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/></svg>',
		star: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
		column: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="4" width="8" height="16" rx="1.5"/><rect x="13" y="4" width="8" height="16" rx="1.5"/></svg>',
		gear: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
		export: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		left: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
		right: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
		reset: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>',
		desktop: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="13" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
		tablet: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>',
		mobile: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>'
	};
	// Colour values WordPress' picker (iris) understands. Anything else —
	// such as a CSS variable default like var(--ajs-primary) — is treated as
	// "no colour" so the swatch and reset button keep working.
	function isPickerColor( v ) {
		if ( typeof v !== 'string' ) {
			return false;
		}
		v = v.trim();
		return /^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test( v )
			|| /^rgba?\(/i.test( v )
			|| /^hsla?\(/i.test( v );
	}

	/* -------------------------------------------------------------- DOM */
	var frame = document.getElementById( 'ajs-frame' );
	var frameWrap = document.getElementById( 'ajs-frame-wrap' );
	var canvas = document.getElementById( 'ajs-canvas' );
	var panel = document.getElementById( 'ajs-panel' );
	var panelBody = document.getElementById( 'ajs-panel-body' );
	var panelTitle = document.getElementById( 'ajs-panel-title' );
	var structureEl = document.getElementById( 'ajs-structure' );
	var structureBody = document.getElementById( 'ajs-structure-body' );
	var structureToggle = document.getElementById( 'ajs-structure-toggle' );
	var structureClose = document.getElementById( 'ajs-structure-close' );
	var backBtn = document.getElementById( 'ajs-back' );
	var searchInput = document.getElementById( 'ajs-search' );
	var stateEl = document.getElementById( 'ajs-state' );
	var toastEl = document.getElementById( 'ajs-toast' );
	var saveBtn = document.getElementById( 'ajs-save' );
	var titleInput = document.getElementById( 'ajs-title' );
	var layoutSelect = document.getElementById( 'ajs-layout' );
	var tplType = document.getElementById( 'ajs-tpl-type' );
	var tplDisplay = document.getElementById( 'ajs-tpl-display' );
	var tplPages = document.getElementById( 'ajs-tpl-pages' );
	var tplPagesWrap = document.getElementById( 'ajs-tpl-pages-wrap' );
	var tplPagesToggle = document.getElementById( 'ajs-tpl-pages-toggle' );
	var tplPagesMenu = document.getElementById( 'ajs-tpl-pages-menu' );
	var tplPagesLabel = document.getElementById( 'ajs-tpl-pages-label' );
	var tplPagesSearch = document.getElementById( 'ajs-tpl-pages-search' );
	var tplBadge = document.getElementById( 'ajs-tpl-badge' );
	var tplSticky = document.getElementById( 'ajs-tpl-sticky' );
	var tplStickyWrap = document.getElementById( 'ajs-tpl-sticky-wrap' );
	var tplClass = document.getElementById( 'ajs-tpl-class' );
	var tplEnabled = document.getElementById( 'ajs-tpl-enabled' );
	var libEl = document.getElementById( 'ajs-lib' );
	var libToggle = document.getElementById( 'ajs-templates-toggle' );
	var libClose = document.getElementById( 'ajs-lib-close' );
	var libBody = document.getElementById( 'ajs-lib-body' );
	var libImport = document.getElementById( 'ajs-lib-import' );

	/* ------------------------------------------------------------ State */
	var state = {
		data: { version: '1.0', sections: [] },
		selected: null,
		container: null,
		dirty: false,
		device: 'desktop'
	};
	var ov = null;
	var dragged = null;
	var dropped = null;
	var lastDragAt = 0;
	var renderSeq = 0;
	var renderTimer = null;
	var booted = false;
	var pendingFocus = null;
	var editorSeq = 0;
	var activeEditors = {};

	/* ---------------------------------------------------------- Helpers */
	function uid( p ) {
		return ( p || 'ajs' ) + '_' + Math.random().toString( 36 ).slice( 2, 10 );
	}
	function clone( o ) {
		return JSON.parse( JSON.stringify( o ) );
	}
	function escAttr( s ) {
		return String( s == null ? '' : s ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' );
	}
	function h( tag, attrs, kids ) {
		var e = document.createElement( tag );
		if ( attrs ) {
			Object.keys( attrs ).forEach( function ( k ) {
				var v = attrs[ k ];
				if ( k === 'class' ) {
					e.className = v;
				} else if ( k === 'text' ) {
					e.textContent = v;
				} else if ( k === 'html' ) {
					e.innerHTML = v;
				} else if ( k.indexOf( 'on' ) === 0 && typeof v === 'function' ) {
					e.addEventListener( k.slice( 2 ), v );
				} else if ( v !== false && v != null && v !== '' ) {
					e.setAttribute( k, v );
				}
			} );
		}
		if ( kids ) {
			( Array.isArray( kids ) ? kids : [ kids ] ).forEach( function ( k ) {
				if ( k == null ) {
					return;
				}
				e.appendChild( typeof k === 'string' ? document.createTextNode( k ) : k );
			} );
		}
		return e;
	}
	function controlDefaults( controls ) {
		var o = {};
		Object.keys( controls || {} ).forEach( function ( k ) {
			if ( controls[ k ] && 'default' in controls[ k ] ) {
				o[ k ] = clone( controls[ k ].default );
			}
		} );
		return o;
	}
	function widgetControls( type ) {
		var w = cfg.widgets[ type ];
		var c = $.extend( true, {}, w ? w.controls : {} );
		return $.extend( true, c, cfg.advanced || {} );
	}

	/* ------------------------------------------------------- Find / move */
	function findElement( id ) {
		var d = state.data;
		for ( var si = 0; si < d.sections.length; si++ ) {
			var sec = d.sections[ si ];
			if ( sec.id === id ) {
				return { type: 'section', el: sec, list: d.sections, index: si };
			}
			for ( var ci = 0; ci < sec.columns.length; ci++ ) {
				var col = sec.columns[ ci ];
				if ( col.id === id ) {
					return { type: 'column', el: col, list: sec.columns, index: ci, section: sec };
				}
				for ( var wi = 0; wi < col.widgets.length; wi++ ) {
					var w = col.widgets[ wi ];
					if ( w.id === id ) {
						return { type: 'widget', el: w, list: col.widgets, index: wi, section: sec, column: col };
					}
					if ( w.type === 'row' && w.columns ) {
						for ( var rci = 0; rci < w.columns.length; rci++ ) {
							var rcol = w.columns[ rci ];
							if ( rcol.id === id ) {
								return { type: 'column', el: rcol, list: w.columns, index: rci, section: sec, columnParent: col, row: w };
							}
							for ( var rwi = 0; rwi < rcol.widgets.length; rwi++ ) {
								var rw = rcol.widgets[ rwi ];
								if ( rw.id === id ) {
									return { type: 'widget', el: rw, list: rcol.widgets, index: rwi, section: sec, column: rcol, columnParent: col, row: w };
								}
							}
						}
					}
				}
			}
		}
		return null;
	}
	function indexById( arr, id ) {
		for ( var i = 0; i < arr.length; i++ ) {
			if ( arr[ i ].id === id ) {
				return i;
			}
		}
		return -1;
	}
	function reassign( node, type ) {
		node.id = uid( type === 'section' ? 'sec' : type === 'column' ? 'col' : 'wid' );
		if ( type === 'section' ) {
			( node.columns || [] ).forEach( function ( c ) {
				reassign( c, 'column' );
			} );
		} else if ( type === 'column' ) {
			( node.widgets || [] ).forEach( function ( w ) {
				reassign( w, 'widget' );
			} );
		} else if ( type === 'widget' && node.type === 'row' ) {
			( node.columns || [] ).forEach( function ( c ) {
				reassign( c, 'column' );
			} );
		}
	}

	/* ---------------------------------------------------- CRUD builders */
	function newWidget( type ) {
		var w = { id: uid( 'wid' ), type: type, settings: controlDefaults( widgetControls( type ) ) };
		if ( type === 'row' ) {
			w.columns = [];
			var cols = parseInt( w.settings.columns_count || '2', 10 ) || 2;
			for ( var i = 0; i < cols; i++ ) {
				w.columns.push( newColumn() );
			}
		}
		return w;
	}
	function newColumn() {
		return { id: uid( 'col' ), settings: controlDefaults( cfg.elements.column ), widgets: [] };
	}
	function newSection( cols ) {
		cols = cols || 1;
		var s = { id: uid( 'sec' ), settings: controlDefaults( cfg.elements.section ), columns: [] };
		s.settings.columns_count = String( cols );
		for ( var i = 0; i < cols; i++ ) {
			s.columns.push( newColumn() );
		}
		return s;
	}
	function selectedColumn() {
		if ( ! state.selected ) {
			return null;
		}
		var f = findElement( state.selected.id );
		if ( ! f ) {
			return null;
		}
		if ( f.type === 'column' ) {
			return f.el;
		}
		if ( f.type === 'widget' ) {
			return f.column;
		}
		if ( f.type === 'section' ) {
			return f.el.columns[ 0 ] || null;
		}
		return null;
	}
	/**
	 * Column that new modules should be appended to. Remembers the last
	 * column the user worked in so modules keep stacking in one place.
	 */
	function containerColumn() {
		if ( state.container ) {
			var f = findElement( state.container );
			if ( f && f.type === 'column' ) {
				return f.el;
			}
		}
		return selectedColumn();
	}
	function addWidget( type ) {
		addWidgetAt( type, null );
	}
	/**
	 * Insert a new widget, optionally at a specific drop position.
	 *
	 * @param {string} type Widget type.
	 * @param {Object|null} drop Drop descriptor from a drag operation.
	 */
	function addWidgetAt( type, drop ) {
		var col = null;
		if ( drop && drop.containerCol ) {
			var cf = findElement( drop.containerCol );
			if ( cf && cf.type === 'column' ) {
				col = cf.el;
			}
		}
		if ( ! col ) {
			col = containerColumn();
		}
		if ( ! col ) {
			var sec = newSection( 1 );
			state.data.sections.push( sec );
			col = sec.columns[ 0 ];
		}
		var w = newWidget( type );
		if ( drop && drop.targetId && ( drop.pos === 'before' || drop.pos === 'after' ) ) {
			var idx = indexById( col.widgets, drop.targetId );
			if ( idx < 0 ) {
				col.widgets.push( w );
			} else {
				col.widgets.splice( idx + ( drop.pos === 'after' ? 1 : 0 ), 0, w );
			}
		} else {
			col.widgets.push( w );
		}
		state.container = col.id;
		markDirty();
		select( 'widget', w.id );
	}

	/* -------------------------------------------- Drag modules into canvas */
	/**
	 * Make a module tile draggable onto the canvas using pointer capture so we
	 * keep receiving pointer events while the cursor is over the preview iframe.
	 *
	 * @param {HTMLElement} tile  Module tile.
	 * @param {string}      type  Widget type.
	 * @param {string}      title Widget title (for the drag ghost).
	 */
	function bindTileDrag( tile, type, title ) {
		tile.addEventListener( 'pointerdown', function ( e ) {
			if ( typeof e.button === 'number' && 0 !== e.button ) {
				return;
			}
			var pointerId  = e.pointerId;
			var startX     = e.clientX;
			var startY     = e.clientY;
			var active     = false;
			var ghost      = null;

			function onMove( ev ) {
				if ( ev.pointerId !== pointerId ) {
					return;
				}
				if ( ! active ) {
					if ( Math.abs( ev.clientX - startX ) < 5 && Math.abs( ev.clientY - startY ) < 5 ) {
						return;
					}
					active = true;
					app.classList.add( 'ajs-is-dragging' );
					ghost = h( 'div', { class: 'ajs-wghost' } );
					ghost.appendChild( h( 'span', { class: 'ajs-wghost__ico', text: title.charAt( 0 ) } ) );
					ghost.appendChild( h( 'span', { text: title } ) );
					document.body.appendChild( ghost );
					ensureOverlays( frameDoc() );
				}
				if ( ev.cancelable ) {
					ev.preventDefault();
				}
				ghost.style.left = ( ev.clientX + 14 ) + 'px';
				ghost.style.top = ( ev.clientY + 14 ) + 'px';
				widgetDragHover( ev );
			}

			function onUp( ev ) {
				if ( ev.pointerId !== pointerId ) {
					return;
				}
				tile.removeEventListener( 'pointermove', onMove );
				tile.removeEventListener( 'pointerup', onUp );
				tile.removeEventListener( 'pointercancel', onUp );
				try {
					tile.releasePointerCapture( pointerId );
				} catch ( err ) {}
				if ( ghost ) {
					ghost.remove();
				}
				app.classList.remove( 'ajs-is-dragging' );
				if ( active ) {
					lastDragAt = Date.now();
					if ( dropped ) {
						addWidgetAt( type, dropped );
					}
				}
				clearDrop();
			}

			tile.addEventListener( 'pointermove', onMove );
			tile.addEventListener( 'pointerup', onUp );
			tile.addEventListener( 'pointercancel', onUp );
			try {
				tile.setPointerCapture( pointerId );
			} catch ( err ) {}
		} );
	}
	/**
	 * Compute the drop indicator while dragging a module over the canvas.
	 *
	 * @param {PointerEvent} e Pointer event.
	 */
	function widgetDragHover( e ) {
		if ( ! ov ) {
			ensureOverlays( frameDoc() );
		}
		var doc = frameDoc();
		if ( ! doc ) {
			clearDrop();
			return;
		}
		var fr = frame.getBoundingClientRect();
		var lx = e.clientX - fr.left;
		var ly = e.clientY - fr.top;
		if ( lx < 0 || ly < 0 || lx > fr.width || ly > fr.height ) {
			clearDrop();
			return;
		}
		var el = doc.elementFromPoint( lx, ly );
		if ( ! el || ! el.closest ) {
			clearDrop();
			return;
		}
		var col = el.closest( '.ajs-b-col' );
		if ( ! col ) {
			clearDrop();
			return;
		}
		var target = el.closest( '[data-element]' );
		if ( target && target.getAttribute( 'data-type' ) === 'widget' ) {
			var r = target.getBoundingClientRect();
			var pos = ( ly < r.top + r.height / 2 ) ? 'before' : 'after';
			dropped = { containerCol: col.getAttribute( 'data-id' ), pos: pos, targetId: target.getAttribute( 'data-id' ) };
			showDrop( target, pos, 'v' );
			return;
		}
		dropped = { containerCol: col.getAttribute( 'data-id' ), pos: 'inside' };
		showDrop( col, 'inside', 'in' );
	}

	/* --------------------------------------------------------- Dirty */
	function markDirty() {
		state.dirty = true;
		stateEl.textContent = cfg.i18n.unsaved;
		stateEl.classList.add( 'is-dirty' );
		scheduleRender();
	}
	function markClean() {
		state.dirty = false;
		stateEl.classList.remove( 'is-dirty' );
		stateEl.textContent = cfg.i18n.saved;
	}

	/* -------------------------------------------------------- REST I/O */
	function loadDocument() {
		$.ajax( {
			url: cfg.restUrl + '/document/' + cfg.postId,
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function ( res ) {
			state.data = res.data && res.data.sections ? res.data : { version: '1.0', sections: [] };
			if ( res.title ) {
				titleInput.value = res.title;
			}
			if ( res.layout ) {
				layoutSelect.value = res.layout;
			}
			if ( res.template ) {
				if ( tplType ) {
					tplType.value = res.template.type || 'header';
				}
				if ( tplDisplay ) {
					tplDisplay.value = res.template.display || 'entire_site';
				}
				if ( tplPages ) {
					var ids = ( res.template.ids || [] ).map( String );
					Array.prototype.forEach.call( tplPageBoxes(), function ( cb ) {
						cb.checked = ids.indexOf( cb.value ) !== -1;
					} );
					tplUpdatePagesLabel();
				}
				if ( tplSticky ) {
					tplSticky.checked = !! res.template.sticky;
				}
				if ( tplClass ) {
					tplClass.value = res.template.classes || '';
				}
				if ( tplEnabled ) {
					tplEnabled.checked = res.template.enabled !== false;
				}
			}
			syncTpl();
			renderPanel();
			doRender();
		} ).fail( function () {
			renderPanel();
			doRender();
		} );
	}
	function tplPageBoxes() {
		return tplPagesMenu ? tplPagesMenu.querySelectorAll( 'input[type="checkbox"]' ) : [];
	}
	function tplSelectedIds() {
		var ids = [];
		Array.prototype.forEach.call( tplPageBoxes(), function ( cb ) {
			if ( cb.checked ) {
				ids.push( parseInt( cb.value, 10 ) );
			}
		} );
		return ids;
	}
	function tplUpdatePagesLabel() {
		if ( ! tplPagesLabel ) {
			return;
		}
		var n = tplSelectedIds().length;
		if ( ! n ) {
			tplPagesLabel.textContent = tplPagesLabel.getAttribute( 'data-empty' ) || tplPagesLabel.textContent;
			return;
		}
		if ( n === 1 ) {
			tplPagesLabel.textContent = cfg.i18n.pageSelected || '1 page selected';
		} else {
			tplPagesLabel.textContent = ( cfg.i18n.pagesSelected || '%d pages selected' ).replace( '%d', String( n ) );
		}
	}
	function syncTpl() {
		if ( ! tplType ) {
			return;
		}
		if ( tplPagesWrap ) {
			tplPagesWrap.hidden = ( tplDisplay.value !== 'specific' );
		}
		if ( tplBadge ) {
			tplBadge.textContent = ( tplType.value === 'footer' ) ? cfg.i18n.footerTemplate : cfg.i18n.headerTemplate;
		}
		syncSticky();
	}
	function syncSticky() {
		if ( ! tplType ) {
			return;
		}
		var isHeader = ( tplType.value === 'header' );
		if ( tplStickyWrap ) {
			tplStickyWrap.hidden = ! isHeader;
		}
	}
	function templatePayload() {
		if ( ! tplType ) {
			return {};
		}
		return {
			template_type: tplType.value,
			template_conditions: { display: tplDisplay.value, ids: tplSelectedIds() },
			template_sticky: tplSticky ? !! tplSticky.checked : false,
			template_classes: tplClass ? tplClass.value : '',
			template_enabled: tplEnabled ? !! tplEnabled.checked : true
		};
	}
	function save() {
		saveBtn.classList.add( 'is-busy' );
		stateEl.textContent = cfg.i18n.saving;
		$.ajax( {
			url: cfg.restUrl + '/document/' + cfg.postId,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( $.extend( { data: state.data, enabled: true, layout: layoutSelect.value, title: titleInput.value }, templatePayload() ) ),
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function () {
			markClean();
			toast( cfg.i18n.saved );
		} ).fail( function () {
			stateEl.textContent = cfg.i18n.error;
			toast( cfg.i18n.error, true );
		} ).always( function () {
			saveBtn.classList.remove( 'is-busy' );
		} );
	}
	function toast( msg, isError ) {
		toastEl.textContent = msg;
		toastEl.hidden = false;
		toastEl.classList.toggle( 'is-error', !! isError );
		clearTimeout( toastEl.__t );
		toastEl.__t = setTimeout( function () {
			toastEl.hidden = true;
		}, 2200 );
	}

	/* -------------------------------------------------- Templates library */
	function setLibrary( open ) {
		if ( ! libEl ) {
			return;
		}
		libEl.hidden = ! open;
		if ( libToggle ) {
			libToggle.classList.toggle( 'is-active', !! open );
			libToggle.setAttribute( 'aria-pressed', open ? 'true' : 'false' );
		}
		if ( open ) {
			loadLibrary();
		}
	}
	function loadLibrary() {
		if ( ! libBody ) {
			return;
		}
		libBody.innerHTML = '';
		libBody.appendChild( h( 'div', { class: 'ajs-lib__loading', text: cfg.i18n.loading || 'Loading…' } ) );
		$.ajax( {
			url: cfg.restUrl + '/templates',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function ( res ) {
			renderLibrary( ( res && res.templates ) || [] );
		} ).fail( function () {
			renderLibrary( [] );
		} );
	}
	function renderLibrary( list ) {
		if ( ! libBody ) {
			return;
		}
		libBody.innerHTML = '';
		if ( ! list.length ) {
			libBody.appendChild( h( 'div', { class: 'ajs-lib__empty', text: cfg.i18n.noTemplates } ) );
			return;
		}
		list.forEach( function ( tpl ) {
			var card = h( 'div', { class: 'ajs-lib__card' } );
			var main = h( 'div', { class: 'ajs-lib__cardmain' } );
			main.appendChild( h( 'span', { class: 'ajs-lib__name', text: tpl.title } ) );
			main.appendChild( h( 'span', { class: 'ajs-lib__meta', text: ( cfg.i18n.sectionCount || '%d sections' ).replace( '%d', String( tpl.sections || 0 ) ) } ) );
			card.appendChild( main );

			var insert = h( 'button', { type: 'button', class: 'ajs-lib__btn', text: cfg.i18n.insertTemplate || 'Insert' } );
			insert.addEventListener( 'click', function () {
				insertTemplate( tpl.id );
			} );
			card.appendChild( insert );

			var exp = h( 'button', { type: 'button', class: 'ajs-lib__btn ajs-lib__btn--icon', html: ICON.export, title: cfg.i18n.export || 'Export' } );
			exp.addEventListener( 'click', function () {
				exportTemplate( tpl.id, tpl.title );
			} );
			card.appendChild( exp );

			var del = h( 'button', { type: 'button', class: 'ajs-lib__btn ajs-lib__btn--del', html: ICON.trash, title: cfg.i18n.delete } );
			del.addEventListener( 'click', function () {
				if ( ! window.confirm( cfg.i18n.deleteTemplateConfirm ) ) {
					return;
				}
				$.ajax( {
					url: cfg.restUrl + '/templates/' + tpl.id,
					method: 'DELETE',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
					}
				} ).done( function () {
					loadLibrary();
				} );
			} );
			card.appendChild( del );
			libBody.appendChild( card );
		} );
	}
	function appendSections( sections ) {
		var added = 0;
		( sections || [] ).forEach( function ( sec ) {
			var copy = clone( sec );
			reassign( copy, 'section' );
			state.data.sections.push( copy );
			added++;
		} );
		if ( added ) {
			markDirty();
			renderStructure();
			toast( cfg.i18n.inserted || 'Template inserted' );
		}
		return added;
	}
	function insertTemplate( id ) {
		$.ajax( {
			url: cfg.restUrl + '/document/' + id,
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function ( res ) {
			var secs = ( res && res.data && res.data.sections ) || [];
			if ( appendSections( secs ) ) {
				setLibrary( false );
			}
		} );
	}
	function saveSectionTemplate( sec ) {
		var title = window.prompt( cfg.i18n.saveTemplate || 'Save as template' );
		if ( title === null ) {
			return;
		}
		title = title.trim();
		if ( ! title ) {
			return;
		}
		$.ajax( {
			url: cfg.restUrl + '/templates',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { title: title, sections: [ clone( sec ) ] } ),
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function () {
			toast( cfg.i18n.saved );
			if ( libEl && ! libEl.hidden ) {
				loadLibrary();
			}
		} ).fail( function () {
			toast( cfg.i18n.error, true );
		} );
	}
	function exportTemplate( id, title ) {
		$.ajax( {
			url: cfg.restUrl + '/templates/' + id + '/export',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function ( payload ) {
			if ( ! payload ) {
				toast( cfg.i18n.exportError || cfg.i18n.error, true );
				return;
			}
			var slug = String( title || 'ajs-template' ).toLowerCase()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-+|-+$/g, '' );
			var blob = new Blob( [ JSON.stringify( payload, null, 2 ) ], { type: 'application/json' } );
			var url = URL.createObjectURL( blob );
			var a = document.createElement( 'a' );
			a.href = url;
			a.download = ( slug || 'ajs-template' ) + '.json';
			document.body.appendChild( a );
			a.click();
			document.body.removeChild( a );
			setTimeout( function () {
				URL.revokeObjectURL( url );
			}, 0 );
			toast( cfg.i18n.exported || 'Exported' );
		} ).fail( function () {
			toast( cfg.i18n.exportError || cfg.i18n.error, true );
		} );
	}
	function importTemplateFile( file ) {
		if ( ! file ) {
			return;
		}
		var reader = new FileReader();
		reader.onload = function () {
			var data;
			try {
				data = JSON.parse( reader.result );
			} catch ( err ) {
				toast( cfg.i18n.importError, true );
				return;
			}
			var secs = ( data && data.document && data.document.sections )
				? data.document.sections
				: ( data && data.sections ) ? data.sections : null;
			if ( ! secs || ! secs.length ) {
				toast( cfg.i18n.importError, true );
				return;
			}
			var fallback = ( file.name || '' ).replace( /\.[^.]+$/, '' );
			var title = ( data && data.title ) ? data.title : fallback;
			saveImportedTemplate( title, secs );
		};
		reader.onerror = function () {
			toast( cfg.i18n.importError, true );
		};
		reader.readAsText( file );
	}
	function saveImportedTemplate( title, sections ) {
		$.ajax( {
			url: cfg.restUrl + '/templates',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { title: title, sections: sections } ),
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function () {
			toast( cfg.i18n.templateSaved || cfg.i18n.saved );
			if ( libEl && ! libEl.hidden ) {
				loadLibrary();
			}
		} ).fail( function () {
			toast( cfg.i18n.importError, true );
		} );
	}

	/* ------------------------------------------------------- Rendering */
	function scheduleRender() {
		clearTimeout( renderTimer );
		renderTimer = setTimeout( doRender, 160 );
	}
	function doRender() {
		var seq = ++renderSeq;
		canvas.classList.add( 'is-loading' );
		$.ajax( {
			url: cfg.restUrl + '/render',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { data: state.data } ),
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce );
			}
		} ).done( function ( res ) {
			if ( seq !== renderSeq ) {
				return;
			}
			inject( res.html );
		} ).always( function () {
			if ( seq === renderSeq ) {
				canvas.classList.remove( 'is-loading' );
			}
		} );
	}
	function inject( html ) {
		var doc = frameDoc();
		if ( ! doc ) {
			return;
		}
		var target = doc.querySelector( '[data-ajs-canvas]' );
		if ( ! target ) {
			return;
		}
		target.innerHTML = html;
		applyDeviceClass();
		if ( frame.contentWindow.AjsBuilderFrontend ) {
			frame.contentWindow.AjsBuilderFrontend.init( doc );
		}
		decorateCanvas( doc );
		applySelection();
		renderStructure();
	}
	function frameDoc() {
		try {
			return frame.contentDocument || frame.contentWindow.document;
		} catch ( e ) {
			return null;
		}
	}
	/**
	 * Tag the preview body with the active device so device-visibility rules
	 * preview correctly regardless of the canvas width.
	 */
	function applyDeviceClass() {
		var doc = frameDoc();
		if ( ! doc || ! doc.body ) {
			return;
		}
		doc.body.classList.remove( 'ajs-device-desktop', 'ajs-device-tablet', 'ajs-device-mobile' );
		doc.body.classList.add( 'ajs-device-' + state.device );
	}
	function setDevice( dev ) {
		dev = dev || 'desktop';
		state.device = dev;
		if ( frameWrap ) {
			frameWrap.setAttribute( 'data-device', dev );
		}
		applyDeviceClass();
		var devBar = document.getElementById( 'ajs-devices' );
		if ( devBar ) {
			devBar.querySelectorAll( 'button' ).forEach( function ( x ) {
				x.classList.toggle( 'is-active', x.getAttribute( 'data-device' ) === dev );
			} );
		}
		if ( state.selected ) {
			renderSettings();
		}
	}

	/* ------------------------------------------------------- Selection */
	function select( type, id ) {
		state.selected = { type: type, id: id };
		var f = findElement( id );
		if ( f ) {
			if ( f.type === 'column' ) {
				state.container = f.el.id;
			} else if ( f.type === 'widget' && f.column ) {
				state.container = f.column.id;
			} else if ( f.type === 'section' && f.el.columns && f.el.columns[ 0 ] ) {
				state.container = f.el.columns[ 0 ].id;
			}
		}
		renderPanel();
		applySelection();
	}
	function deselect() {
		state.selected = null;
		renderPanel();
		applySelection();
	}
	function applySelection() {
		var doc = frameDoc();
		if ( ! doc ) {
			return;
		}
		doc.querySelectorAll( '.ajs-el-selected' ).forEach( function ( n ) {
			n.classList.remove( 'ajs-el-selected' );
		} );
		if ( ! state.selected ) {
			hideOverlay();
			return;
		}
		var node = doc.querySelector( '[data-id="' + cssEsc( state.selected.id ) + '"]' );
		if ( node ) {
			node.classList.add( 'ajs-el-selected' );
		}
		positionOverlay();
		updateStructureSelection();
	}
	function cssEsc( s ) {
		return String( s ).replace( /["\\]/g, '\\$&' );
	}
	function labelFor( sel ) {
		if ( ! sel ) {
			return '';
		}
		if ( sel.type === 'widget' ) {
			var f = findElement( sel.id );
			var t = f && f.el.type;
			return t && cfg.widgets[ t ] ? cfg.widgets[ t ].title : 'Widget';
		}
		return sel.type.charAt( 0 ).toUpperCase() + sel.type.slice( 1 );
	}

	/* ------------------------------------------- Canvas toolbars (columns) */
	/**
	 * Inject Edit / Duplicate / Delete toolbars into every section and column
	 * directly in the editor preview. Because they live inside the preview DOM
	 * they follow responsive layout automatically and stay in sync with the
	 * Structure panel (both are rebuilt from the same model on every render).
	 *
	 * @param {Document} doc Preview document.
	 */
	function decorateCanvas( doc ) {
		if ( ! doc ) {
			return;
		}
		// Decorate only the editable page canvas — header/footer templates are
		// rendered into the same preview but are not part of state.data.
		var root = doc.querySelector( '[data-ajs-canvas]' );
		if ( ! root ) {
			return;
		}
		root.querySelectorAll( '.ajs-b-section[data-id]' ).forEach( function ( sec ) {
			if ( sec.querySelector( ':scope > .ajs-cvtool--section' ) ) {
				return;
			}
			var secId = sec.getAttribute( 'data-id' );
			// Only the section's own row columns count toward the max — nested
			// Row-widget columns live deeper in the tree.
			var secRow = sec.querySelector( ':scope > .ajs-b-container > .ajs-b-row' ) || sec.querySelector( '.ajs-b-row' );
			var atMax = ( secRow ? secRow.querySelectorAll( ':scope > .ajs-b-col' ).length : 0 ) >= 6;
			var bar = doc.createElement( 'div' );
			bar.className = 'ajs-cvtool ajs-cvtool--section';
			bar.innerHTML =
				'<span class="ajs-cvtool__label">' + secLabel( sec ) + '</span>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="select" data-type="section" data-id="' + escAttr( secId ) + '" title="' + escAttr( cfg.i18n.settings || 'Edit' ) + '">' + ICON.gear + '</button>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="duplicate" data-type="section" data-id="' + escAttr( secId ) + '" title="' + escAttr( cfg.i18n.duplicate ) + '">' + ICON.dup + '</button>' +
				'<button type="button" class="ajs-cvtool__btn ajs-cvtool__btn--del" data-ajs-act="delete" data-type="section" data-id="' + escAttr( secId ) + '" title="' + escAttr( cfg.i18n.delete ) + '">' + ICON.trash + '</button>' +
				'<span class="ajs-cvtool__sep"></span>' +
				'<button type="button" class="ajs-cvtool__btn ajs-cvtool__btn--add" data-ajs-act="add-column" data-id="' + escAttr( secId ) + '" title="' + escAttr( cfg.i18n.addColumn ) + '"' + ( atMax ? ' disabled' : '' ) + '>' + ICON.plus + '<span>' + escAttr( cfg.i18n.addColumn ) + '</span></button>';
			sec.appendChild( bar );
		} );
		// Section "+" inserters: a small plus anchored to the bottom of every
		// section so a new section can be inserted right after it (i.e. between
		// two sections, or at the very end of the page).
		root.querySelectorAll( '.ajs-b-section[data-id]' ).forEach( function ( sec ) {
			if ( sec.querySelector( ':scope > .ajs-sec-add' ) ) {
				return;
			}
			var secId = sec.getAttribute( 'data-id' );
			var add = doc.createElement( 'button' );
			add.type = 'button';
			add.className = 'ajs-sec-add';
			add.setAttribute( 'data-ajs-act', 'add-section' );
			add.setAttribute( 'data-id', secId );
			add.setAttribute( 'title', cfg.i18n.addSection || 'Add Section' );
			add.innerHTML = ICON.plus;
			sec.appendChild( add );
		} );
		root.querySelectorAll( '.ajs-b-col[data-id]' ).forEach( function( col ) {
			if ( col.querySelector( ':scope > .ajs-cvtool--col' ) ) {
				return;
			}
			var colId  = col.getAttribute( 'data-id' );
			var parent = col.parentNode;
			var idx    = Array.prototype.indexOf.call( parent ? parent.children : [], col );
			var total  = parent ? parent.children.length : 1;
			var bar = doc.createElement( 'div' );
			bar.className = 'ajs-cvtool ajs-cvtool--col';
			bar.innerHTML =
				'<span class="ajs-cvtool__label">' + escAttr( cfg.i18n.column || 'Column' ) + ' ' + ( idx + 1 ) + '</span>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="move-col" data-dir="left" data-id="' + escAttr( colId ) + '" title="' + escAttr( cfg.i18n.moveLeft ) + '"' + ( idx === 0 ? ' disabled' : '' ) + '>' + ICON.left + '</button>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="move-col" data-dir="right" data-id="' + escAttr( colId ) + '" title="' + escAttr( cfg.i18n.moveRight ) + '"' + ( idx >= total - 1 ? ' disabled' : '' ) + '>' + ICON.right + '</button>' +
				'<span class="ajs-cvtool__sep"></span>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="select" data-type="column" data-id="' + escAttr( colId ) + '" title="' + escAttr( cfg.i18n.settings || 'Edit' ) + '">' + ICON.gear + '</button>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="duplicate" data-type="column" data-id="' + escAttr( colId ) + '" title="' + escAttr( cfg.i18n.duplicate ) + '">' + ICON.dup + '</button>' +
				'<button type="button" class="ajs-cvtool__btn ajs-cvtool__btn--del" data-ajs-act="delete" data-type="column" data-id="' + escAttr( colId ) + '" title="' + escAttr( cfg.i18n.delete ) + '">' + ICON.trash + '</button>';
			col.appendChild( bar );
		} );
		// Row modules get their own toolbar so the Row itself (not just the
		// columns inside it) can be edited, duplicated or given a new
		// column without hunting for an empty spot to click.
		root.querySelectorAll( '.ajs-b-widget--row[data-id]' ).forEach( function ( rowW ) {
			if ( rowW.querySelector( ':scope > .ajs-cvtool--row' ) ) {
				return;
			}
			var wid    = rowW.getAttribute( 'data-id' );
			var rowEl  = rowW.querySelector( ':scope > .ajs-b-row' );
			var atMax  = ( rowEl ? rowEl.querySelectorAll( ':scope > .ajs-b-col' ).length : 0 ) >= 6;
			var rowBar = doc.createElement( 'div' );
			rowBar.className = 'ajs-cvtool ajs-cvtool--row';
			rowBar.innerHTML =
				'<span class="ajs-cvtool__label">' + escAttr( ( cfg.widgets && cfg.widgets.row && cfg.widgets.row.title ) || 'Row' ) + '</span>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="select" data-type="widget" data-id="' + escAttr( wid ) + '" title="' + escAttr( cfg.i18n.settings || 'Edit' ) + '">' + ICON.gear + '</button>' +
				'<button type="button" class="ajs-cvtool__btn" data-ajs-act="duplicate" data-type="widget" data-id="' + escAttr( wid ) + '" title="' + escAttr( cfg.i18n.duplicate ) + '">' + ICON.dup + '</button>' +
				'<button type="button" class="ajs-cvtool__btn ajs-cvtool__btn--del" data-ajs-act="delete" data-type="widget" data-id="' + escAttr( wid ) + '" title="' + escAttr( cfg.i18n.delete ) + '">' + ICON.trash + '</button>' +
				'<span class="ajs-cvtool__sep"></span>' +
				'<button type="button" class="ajs-cvtool__btn ajs-cvtool__btn--add" data-ajs-act="add-column" data-id="' + escAttr( wid ) + '" title="' + escAttr( cfg.i18n.addColumn ) + '"' + ( atMax ? ' disabled' : '' ) + '>' + ICON.plus + '<span>' + escAttr( cfg.i18n.addColumn ) + '</span></button>';
			rowW.appendChild( rowBar );
		} );
		if ( pendingFocus ) {
			var focusNode = root.querySelector( '.ajs-b-section[data-id="' + pendingFocus + '"]' );
			var focusId = pendingFocus;
			pendingFocus = null;
			if ( focusNode ) {
				focusNode.classList.add( 'ajs-b-section--focus' );
				if ( focusNode.scrollIntoView ) {
					focusNode.scrollIntoView( { block: 'center', behavior: 'smooth' } );
				}
				window.setTimeout( function () {
					var stale = root.querySelector( '.ajs-b-section[data-id="' + focusId + '"]' );
					if ( stale ) {
						stale.classList.remove( 'ajs-b-section--focus' );
					}
				}, 1500 );
			}
		}
	}
	function secLabel( sec ) {
		var secs = ( state.data && state.data.sections ) || [];
		var i = secs.indexOf( findSec( sec.getAttribute( 'data-id' ) ) );
		return ( cfg.i18n.section || 'Section' ) + ' ' + ( i < 0 ? 1 : i + 1 );
	}
	function findSec( id ) {
		var secs = ( state.data && state.data.sections ) || [];
		for ( var i = 0; i < secs.length; i++ ) {
			if ( secs[ i ].id === id ) {
				return secs[ i ];
			}
		}
		return null;
	}
	/**
	 * Handle a click on a canvas toolbar button.
	 *
	 * @param {HTMLElement} btn Button element.
	 */
	function handleCanvasAct( btn ) {
		var act = btn.getAttribute( 'data-ajs-act' );
		var id  = btn.getAttribute( 'data-id' );
		var type = btn.getAttribute( 'data-type' );
		if ( act === 'add-column' ) {
			addColumnTo( id );
			return;
		}
		if ( act === 'add-section' ) {
			addSectionAfter( id );
			return;
		}
		if ( act === 'move-col' ) {
			moveColumn( id, btn.getAttribute( 'data-dir' ) );
			return;
		}
		if ( act === 'select' ) {
			select( type, id );
			return;
		}
		if ( act === 'duplicate' ) {
			duplicateById( type, id );
			return;
		}
		if ( act === 'delete' ) {
			deleteById( id );
			return;
		}
	}

	/* ------------------------------------------------------- Structure */
	function widgetLabel( type ) {
		return cfg.widgets[ type ] ? cfg.widgets[ type ].title : type;
	}
	/**
	 * Build one row of the structure (navigator) tree.
	 *
	 * @param {string} type  section|column|widget
	 * @param {Object} el    Model element.
	 * @param {number} level Indent level.
	 * @param {string} label Display label.
	 * @return {HTMLElement}
	 */
	function structureItem( type, el, level, label ) {
		var item = h( 'div', {
			class: 'ajs-structure__item ajs-structure__item--' + type,
			'data-id': el.id,
			'data-type': type
		} );

		var row = h( 'button', { type: 'button', class: 'ajs-structure__row' } );
		row.style.paddingLeft = ( 8 + level * 16 ) + 'px';

		var ico = h( 'span', { class: 'ajs-structure__ico ajs-structure__ico--' + type } );
		if ( type === 'widget' ) {
			var svg = moduleIcon( el.type );
			ico.innerHTML = svg || '<span class="ajs-structure__dot"></span>';
		} else {
			ico.innerHTML = ( type === 'section' ? ICON.section : ICON.column );
		}
		row.appendChild( ico );
		row.appendChild( h( 'span', { class: 'ajs-structure__label', text: label } ) );
		row.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			select( type, el.id );
		} );
		item.appendChild( row );

		var acts = h( 'span', { class: 'ajs-structure__acts' } );
		if ( type === 'section' || ( type === 'widget' && el.type === 'row' ) ) {
			var add = h( 'button', { type: 'button', class: 'ajs-structure__act', html: ICON.plus, title: cfg.i18n.addColumn } );
			if ( ( el.columns || [] ).length >= 6 ) {
				add.disabled = true;
			}
			add.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				addColumnTo( el.id );
				renderStructure();
			} );
			acts.appendChild( add );
			if ( ! cfg.isTemplate ) {
				var saveTpl = h( 'button', { type: 'button', class: 'ajs-structure__act ajs-structure__act--tpl', html: ICON.star, title: cfg.i18n.saveTemplate || 'Save as template' } );
				saveTpl.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					saveSectionTemplate( el );
				} );
				acts.appendChild( saveTpl );
			}
		}
		var edit = h( 'button', { type: 'button', class: 'ajs-structure__act', html: ICON.gear, title: cfg.i18n.settings || 'Edit' } );
		edit.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			select( type, el.id );
		} );
		var dup = h( 'button', { type: 'button', class: 'ajs-structure__act', html: ICON.dup, title: cfg.i18n.duplicate } );
		dup.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			duplicateById( type, el.id );
			renderStructure();
		} );
		var del = h( 'button', { type: 'button', class: 'ajs-structure__act ajs-structure__act--del', html: ICON.trash, title: cfg.i18n.delete } );
		del.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			deleteById( el.id );
			renderStructure();
		} );
		acts.appendChild( edit );
		acts.appendChild( dup );
		acts.appendChild( del );
		item.appendChild( acts );

		return item;
	}
	function renderStructure() {
		if ( ! structureBody ) {
			return;
		}
		structureBody.innerHTML = '';
		var secs = ( state.data && state.data.sections ) || [];
		if ( ! secs.length ) {
			structureBody.appendChild( h( 'div', { class: 'ajs-structure__empty', text: cfg.i18n.emptyCanvas } ) );
			return;
		}
		secs.forEach( function ( sec, si ) {
			structureBody.appendChild( structureItem( 'section', sec, 0, ( cfg.i18n.section || 'Section' ) + ' ' + ( si + 1 ) ) );
			( sec.columns || [] ).forEach( function ( col, ci ) {
				structureBody.appendChild( structureItem( 'column', col, 1, ( cfg.i18n.column || 'Column' ) + ' ' + ( ci + 1 ) ) );
				( col.widgets || [] ).forEach( function ( w ) {
					structureBody.appendChild( structureItem( 'widget', w, 2, widgetLabel( w.type ) ) );
					// Row widgets contain their own columns.
					if ( w.type === 'row' ) {
						( w.columns || [] ).forEach( function ( rc, rci ) {
							structureBody.appendChild( structureItem( 'column', rc, 3, ( cfg.i18n.column || 'Column' ) + ' ' + ( rci + 1 ) ) );
							( rc.widgets || [] ).forEach( function ( rw ) {
								structureBody.appendChild( structureItem( 'widget', rw, 4, widgetLabel( rw.type ) ) );
							} );
						} );
					}
				} );
			} );
		} );
		updateStructureSelection();
	}
	function updateStructureSelection() {
		if ( ! structureBody ) {
			return;
		}
		var id = state.selected ? state.selected.id : null;
		structureBody.querySelectorAll( '.ajs-structure__item' ).forEach( function ( it ) {
			it.classList.toggle( 'is-active', !! id && it.getAttribute( 'data-id' ) === id );
		} );
		var active = structureBody.querySelector( '.ajs-structure__item.is-active' );
		if ( active && active.scrollIntoView ) {
			active.scrollIntoView( { block: 'nearest' } );
		}
	}
	function setStructure( open ) {
		if ( ! structureEl ) {
			return;
		}
		structureEl.hidden = ! open;
		if ( structureToggle ) {
			structureToggle.classList.toggle( 'is-active', !! open );
			structureToggle.setAttribute( 'aria-pressed', open ? 'true' : 'false' );
		}
		if ( open ) {
			renderStructure();
		}
	}

	/* --------------------------------------------------------- Overlays */
	function ensureOverlays( doc ) {
		if ( ov && ov.hover.isConnected ) {
			return;
		}
		ov = {};
		ov.hover = h( 'div', { class: 'ajs-ov-hover' } );
		ov.select = h( 'div', { class: 'ajs-ov-select' } );
		ov.drop = h( 'div', { class: 'ajs-ov-drop', hidden: 'hidden' } );

		ov.tag = h( 'div', { class: 'ajs-ov-tag' } );
		ov.handle = h( 'span', { class: 'ajs-ov-handle', html: ICON.move, title: 'Drag' } );
		ov.tagLabel = h( 'span', { class: 'ajs-ov-tag__label', text: '' } );
		ov.btnDup = h( 'button', { type: 'button', html: ICON.dup, title: cfg.i18n.duplicate } );
		ov.btnDel = h( 'button', { type: 'button', html: ICON.trash, title: cfg.i18n.delete } );

		ov.btnDup.addEventListener( 'mousedown', function ( e ) {
			e.stopPropagation();
		} );
		ov.btnDel.addEventListener( 'mousedown', function ( e ) {
			e.stopPropagation();
		} );
		ov.btnDup.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			duplicateSelected();
		} );
		ov.btnDel.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			deleteSelected();
		} );
		ov.handle.addEventListener( 'mousedown', function ( e ) {
			e.stopPropagation();
			e.preventDefault();
			startDrag( e );
		} );

		ov.tag.appendChild( ov.handle );
		ov.tag.appendChild( ov.tagLabel );
		ov.tag.appendChild( ov.btnDup );
		ov.tag.appendChild( ov.btnDel );
		ov.select.appendChild( ov.tag );

		doc.body.appendChild( ov.hover );
		doc.body.appendChild( ov.drop );
		doc.body.appendChild( ov.select );
	}
	function rectDoc( el ) {
		var w = frame.contentWindow;
		var r = el.getBoundingClientRect();
		return { top: r.top + w.scrollY, left: r.left + w.scrollX, width: r.width, height: r.height };
	}
	function positionOverlay() {
		if ( ! ov ) {
			return;
		}
		var doc = frameDoc();
		if ( ! doc || ! state.selected ) {
			hideOverlay();
			return;
		}
		var node = doc.querySelector( '[data-id="' + cssEsc( state.selected.id ) + '"]' );
		if ( ! node ) {
			ov.select.style.display = 'none';
			return;
		}
		var r = rectDoc( node );
		ov.select.className = 'ajs-ov-select' + ( state.selected.type === 'column' ? ' ajs-ov-select--column' : '' );
		ov.select.style.display = 'block';
		ov.select.style.top = r.top + 'px';
		ov.select.style.left = r.left + 'px';
		ov.select.style.width = r.width + 'px';
		ov.select.style.height = r.height + 'px';
		ov.tagLabel.textContent = labelFor( state.selected );
	}
	function hideOverlay() {
		if ( ov ) {
			ov.select.style.display = 'none';
			ov.drop.hidden = true;
		}
	}
	function setHover( node ) {
		if ( ! ov ) {
			return;
		}
		if ( ! node || ( state.selected && node.getAttribute( 'data-id' ) === state.selected.id ) ) {
			ov.hover.style.display = 'none';
			return;
		}
		var r = rectDoc( node );
		ov.hover.className = 'ajs-ov-hover' + ( node.getAttribute( 'data-type' ) === 'column' ? ' ajs-ov-hover--column' : '' );
		ov.hover.style.display = 'block';
		ov.hover.style.top = r.top + 'px';
		ov.hover.style.left = r.left + 'px';
		ov.hover.style.width = r.width + 'px';
		ov.hover.style.height = r.height + 'px';
	}
	function showDrop( node, pos, axis ) {
		if ( ! ov ) {
			return;
		}
		var r = rectDoc( node );
		ov.drop.hidden = false;
		ov.drop.className = 'ajs-ov-drop';
		if ( pos === 'inside' ) {
			ov.drop.classList.add( 'ajs-ov-drop--in' );
			ov.drop.style.top = r.top + 'px';
			ov.drop.style.left = r.left + 'px';
			ov.drop.style.width = r.width + 'px';
			ov.drop.style.height = r.height + 'px';
		} else if ( axis === 'h' ) {
			ov.drop.classList.add( 'ajs-ov-drop--h' );
			ov.drop.style.top = r.top + 'px';
			ov.drop.style.left = ( r.left + ( pos === 'after' ? r.width : 0 ) ) + 'px';
			ov.drop.style.height = r.height + 'px';
		} else {
			ov.drop.classList.add( 'ajs-ov-drop--v' );
			ov.drop.style.top = ( r.top + ( pos === 'after' ? r.height : 0 ) ) + 'px';
			ov.drop.style.left = r.left + 'px';
			ov.drop.style.width = r.width + 'px';
		}
	}
	function clearDrop() {
		dropped = null;
		if ( ov ) {
			ov.drop.hidden = true;
		}
	}

	/* ------------------------------------------------------- Canvas bind */
	function attachCanvas( doc ) {
		doc.addEventListener( 'mousemove', function ( e ) {
			if ( dragged ) {
				dragMove( e );
				return;
			}
			if ( e.target && e.target.closest && e.target.closest( '.ajs-cvtool' ) ) {
				setHover( null );
				return;
			}
			var t = e.target && e.target.closest ? e.target.closest( '[data-element]' ) : null;
			setHover( t );
		}, true );
		doc.addEventListener( 'mouseleave', function () {
			if ( ov ) {
				ov.hover.style.display = 'none';
			}
		} );
		doc.addEventListener( 'click', function ( e ) {
			var actBtn = e.target.closest ? e.target.closest( '[data-ajs-act]' ) : null;
			if ( actBtn ) {
				e.preventDefault();
				e.stopPropagation();
				handleCanvasAct( actBtn );
				return;
			}
			if ( e.target.closest && e.target.closest( '.ajs-ov-tag' ) ) {
				return;
			}
			var empty = e.target.closest ? e.target.closest( '.ajs-b-empty' ) : null;
			if ( empty ) {
				e.preventDefault();
				e.stopPropagation();
				var col = empty.closest( '.ajs-b-col' );
				state.selected = null;
				if ( col ) {
					state.container = col.getAttribute( 'data-id' );
				}
				if ( panel ) {
					panel.classList.remove( 'is-collapsed' );
				}
				renderPanel();
				applySelection();
				return;
			}
			var el = e.target.closest ? e.target.closest( '[data-element]' ) : null;
			if ( ! el ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			select( el.getAttribute( 'data-type' ), el.getAttribute( 'data-id' ) );
		}, true );
		// Re-position overlays while scrolling / resizing.
		frame.contentWindow.addEventListener( 'scroll', positionOverlay, true );
		frame.contentWindow.addEventListener( 'resize', positionOverlay );
	}

	/* ------------------------------------------------------------- Drag */
	function startDrag( e ) {
		if ( ! state.selected ) {
			return;
		}
		dragged = { type: state.selected.type, id: state.selected.id, started: false };
		var doc = frameDoc();
		var node = doc.querySelector( '[data-id="' + cssEsc( dragged.id ) + '"]' );
		if ( node ) {
			node.classList.add( 'ajs-b-dragging' );
		}
		doc.body.classList.add( 'ajs-dragging-canvas' );
		ov.select.style.pointerEvents = 'none';
		doc.addEventListener( 'mousemove', dragMove, true );
		doc.addEventListener( 'mouseup', endDrag, true );
	}
	function dragMove( e ) {
		if ( ! dragged ) {
			return;
		}
		dragged.started = true;
		var doc = frameDoc();
		var el = doc.elementFromPoint( e.clientX, e.clientY );
		if ( ! el ) {
			clearDrop();
			return;
		}
		var target = el.closest ? el.closest( '[data-element]' ) : null;
		if ( ! target || target.getAttribute( 'data-id' ) === dragged.id ) {
			var col0 = el.closest ? el.closest( '.ajs-b-col' ) : null;
			if ( dragged.type === 'widget' && col0 && col0.getAttribute( 'data-id' ) !== dragged.id ) {
				dropped = { containerCol: col0.getAttribute( 'data-id' ), pos: 'inside' };
				showDrop( col0, 'inside', 'in' );
				return;
			}
			clearDrop();
			return;
		}
		var tType = target.getAttribute( 'data-type' );
		var rect = target.getBoundingClientRect();

		if ( dragged.type === 'widget' ) {
			var col = el.closest( '.ajs-b-col' );
			if ( ! col ) {
				clearDrop();
				return;
			}
			if ( tType === 'widget' ) {
				var posW = ( e.clientY < rect.top + rect.height / 2 ) ? 'before' : 'after';
				dropped = { containerCol: col.getAttribute( 'data-id' ), pos: posW, targetId: target.getAttribute( 'data-id' ) };
				showDrop( target, posW, 'v' );
			} else {
				dropped = { containerCol: col.getAttribute( 'data-id' ), pos: 'inside' };
				showDrop( col, 'inside', 'in' );
			}
		} else if ( dragged.type === 'column' ) {
			var sec = el.closest( '.ajs-b-section' );
			if ( ! sec ) {
				clearDrop();
				return;
			}
			// A column sitting inside a Row widget belongs to that row, not the
			// outer section, so the drop target id is the row widget id.
			var host = el.closest( '.ajs-b-widget--row' );
			var hostId = host ? host.getAttribute( 'data-id' ) : sec.getAttribute( 'data-id' );
			if ( tType === 'column' ) {
				var posC = ( e.clientX < rect.left + rect.width / 2 ) ? 'before' : 'after';
				dropped = { sectionId: hostId, pos: posC, targetId: target.getAttribute( 'data-id' ) };
				showDrop( target, posC, 'h' );
			} else {
				dropped = { sectionId: hostId, pos: 'inside' };
				showDrop( host || sec.querySelector( '.ajs-b-row' ) || sec, 'inside', 'in' );
			}
		} else {
			var s2 = el.closest( '.ajs-b-section' );
			if ( ! s2 ) {
				clearDrop();
				return;
			}
			var r2 = s2.getBoundingClientRect();
			var posS = ( e.clientY < r2.top + r2.height / 2 ) ? 'before' : 'after';
			dropped = { pos: posS, targetId: s2.getAttribute( 'data-id' ) };
			showDrop( s2, posS, 'v' );
		}
	}
	function endDrag() {
		var doc = frameDoc();
		doc.removeEventListener( 'mousemove', dragMove, true );
		doc.removeEventListener( 'mouseup', endDrag, true );
		if ( doc.body ) {
			doc.body.classList.remove( 'ajs-dragging-canvas' );
		}
		doc.querySelectorAll( '.ajs-b-dragging' ).forEach( function ( n ) {
			n.classList.remove( 'ajs-b-dragging' );
		} );
		if ( ov ) {
			ov.select.style.pointerEvents = '';
		}
		if ( dragged && dropped ) {
			applyMove( dragged, dropped );
		}
		dragged = null;
		clearDrop();
		applySelection();
	}
	function applyMove( d, drop ) {
		var found = findElement( d.id );
		if ( ! found ) {
			return;
		}
		if ( d.type === 'widget' ) {
			var moved = found.list.splice( found.index, 1 )[ 0 ];
			var targetCol = findElement( drop.containerCol );
			targetCol = targetCol && targetCol.el;
			if ( ! targetCol ) {
				found.list.splice( found.index, 0, moved );
				return;
			}
			if ( drop.pos === 'inside' || ! drop.targetId ) {
				targetCol.widgets.push( moved );
			} else {
				var ti = indexById( targetCol.widgets, drop.targetId );
				targetCol.widgets.splice( ti < 0 ? targetCol.widgets.length : ti + ( drop.pos === 'after' ? 1 : 0 ), 0, moved );
			}
		} else if ( d.type === 'column' ) {
			var srcHost = found.row || found.section;
			var movedCol = found.list.splice( found.index, 1 )[ 0 ];
			var sec = findElement( drop.sectionId );
			sec = sec && sec.el;
			if ( ! sec ) {
				found.list.splice( found.index, 0, movedCol );
				return;
			}
			if ( drop.pos === 'inside' || ! drop.targetId ) {
				sec.columns.push( movedCol );
			} else {
				var ci = indexById( sec.columns, drop.targetId );
				sec.columns.splice( ci < 0 ? sec.columns.length : ci + ( drop.pos === 'after' ? 1 : 0 ), 0, movedCol );
			}
			// Keep both the source and destination column counts in sync.
			[ srcHost, sec ].forEach( function ( host ) {
				if ( host && host.columns ) {
					host.settings = host.settings || {};
					host.settings.columns_count = String( host.columns.length );
				}
			} );
		} else {
			var arr = state.data.sections;
			var si = indexById( arr, d.id );
			if ( si < 0 ) {
				return;
			}
			var sec2 = arr.splice( si, 1 )[ 0 ];
			var ti2 = indexById( arr, drop.targetId );
			arr.splice( ti2 < 0 ? arr.length : ti2 + ( drop.pos === 'after' ? 1 : 0 ), 0, sec2 );
		}
		markDirty();
		select( d.type, d.id );
	}

	/* ------------------------------------------------------- Duplicate */
	function duplicateSelected() {
		if ( ! state.selected ) {
			return;
		}
		duplicateById( state.selected.type, state.selected.id );
	}
	/**
	 * Duplicate any element by id (works for blank and populated columns too).
	 *
	 * @param {string} type section|column|widget
	 * @param {string} id   Element id.
	 */
	function duplicateById( type, id ) {
		var f = findElement( id );
		if ( ! f ) {
			return;
		}
		var copy = clone( f.el );
		reassign( copy, f.type );
		f.list.splice( f.index + 1, 0, copy );
		if ( f.type === 'column' ) {
			var host = f.row || f.section;
			if ( host ) {
				host.settings = host.settings || {};
				host.settings.columns_count = String( ( f.row ? f.row.columns : f.section.columns ).length );
			}
		}
		markDirty();
		select( f.type, copy.id );
	}
	function deleteSelected() {
		if ( ! state.selected ) {
			return;
		}
		deleteById( state.selected.id );
	}
	/**
	 * Delete any element by id.
	 *
	 * @param {string} id Element id.
	 */
	function deleteById( id ) {
		var f = findElement( id );
		if ( ! f ) {
			return;
		}
		var msg = f.type === 'section' ? cfg.i18n.deleteSection : f.type === 'column' ? cfg.i18n.deleteColumn : cfg.i18n.deleteWidget;
		if ( ! window.confirm( msg ) ) {
			return;
		}
		f.list.splice( f.index, 1 );
		if ( f.type === 'column' ) {
			var dHost = f.row || f.section;
			if ( dHost ) {
				dHost.settings = dHost.settings || {};
				dHost.settings.columns_count = String( ( f.row ? f.row.columns : f.section.columns ).length );
			}
		}
		if ( state.selected && state.selected.id === id ) {
			if ( f.type === 'section' ) {
				state.selected = null;
			} else if ( f.type === 'column' ) {
				state.selected = f.row ? { type: 'widget', id: f.row.id } : { type: 'section', id: f.section.id };
			} else {
				state.selected = { type: 'column', id: f.column.id };
			}
		}
		markDirty();
		renderPanel();
		applySelection();
		renderStructure();
	}

	/* ---------------------------------------------------- Columns (canvas) */
	/**
	 * Append a fresh column to a section (drives the on-canvas "Add Column").
	 *
	 * @param {string} sectionId Section id.
	 */
	function addColumnTo( sectionId ) {
		var f = findElement( sectionId );
		if ( ! f || ( f.type !== 'section' && !( f.type === 'widget' && f.el.type === 'row' ) ) ) {
			return;
		}
		var max = 6;
		if ( ( f.el.columns || [] ).length >= max ) {
			toast( ( cfg.i18n.maxColumns || 'A section can have up to %d columns.' ).replace( '%d', max ), true );
			return;
		}
		var col = newColumn();
		f.el.columns = f.el.columns || [];
		f.el.columns.push( col );
		f.el.settings = f.el.settings || {};
		f.el.settings.columns_count = String( f.el.columns.length );
		markDirty();
		select( 'column', col.id );
	}
	/**
	 * "Add Section" / on-canvas "+" entry point.
	 *
	 * If the layout has no section yet it creates one with a default column;
	 * if a section already exists it reuses that section (ensuring it has a
	 * column) instead of stacking duplicate sections/columns. Existing
	 * content, settings and responsive overrides are never touched.
	 *
	 * @param {string} [afterId] Section the "+" was clicked under (optional).
	 */
	function addSectionAfter( afterId ) {
		var secs = state.data.sections || ( state.data.sections = [] );
		if ( ! secs.length ) {
			var sec = newSection( 1 );
			secs.push( sec );
			markDirty();
			focusNewSection( sec );
			return;
		}
		// Reuse the section the "+" belongs to (or the last one) rather than
		// creating a duplicate.
		var target = ( afterId ? findSec( afterId ) : null ) || secs[ secs.length - 1 ];
		ensureSectionContainer( target );
		markDirty();
		focusNewSection( target );
	}
	/**
	 * Guarantee a section owns at least one column without disturbing any
	 * existing content. Only appends a column when the section is empty and
	 * keeps the column count in sync so tablet/mobile layouts stay correct.
	 *
	 * @param {Object} sec Section model.
	 */
	function ensureSectionContainer( sec ) {
		if ( ! sec ) {
			return;
		}
		sec.columns = sec.columns || [];
		if ( ! sec.columns.length ) {
			sec.columns.push( newColumn() );
		}
		sec.settings = sec.settings || {};
		sec.settings.columns_count = String( sec.columns.length );
	}
	/**
	 * Drop a section into "ready to fill" mode: its first container becomes the
	 * active drop target and the Modules panel opens so the user can immediately
	 * add any module (Row, Image Box, …). The section is scrolled into view and
	 * briefly highlighted once the canvas re-renders. Works for a freshly
	 * created section and for an existing one that is being reused.
	 *
	 * @param {Object} sec Section model.
	 */
	function focusNewSection( sec ) {
		state.selected  = null;
		state.container = ( sec.columns && sec.columns[ 0 ] ) ? sec.columns[ 0 ].id : null;
		pendingFocus    = sec.id;
		renderPanel();
		renderStructure();
	}
	/**
	 * Move a column one slot left/right inside its section.
	 *
	 * @param {string} id  Column id.
	 * @param {string} dir "left"|"right".
	 */
	function moveColumn( id, dir ) {
		var f = findElement( id );
		if ( ! f || f.type !== 'column' ) {
			return;
		}
		var list = f.list;
		var i = f.index;
		var j = ( dir === 'left' ) ? i - 1 : i + 1;
		if ( j < 0 || j >= list.length ) {
			return;
		}
		var tmp = list[ i ];
		list[ i ] = list[ j ];
		list[ j ] = tmp;
		markDirty();
		select( 'column', id );
		renderStructure();
	}

	/* ----------------------------------------------------------- Panel */
	function renderPanel() {
		if ( state.selected ) {
			panelTitle.textContent = cfg.i18n.settings;
			backBtn.hidden = false;
			searchInput.hidden = true;
			renderSettings();
		} else {
			panelTitle.textContent = cfg.i18n.modules;
			backBtn.hidden = true;
			searchInput.hidden = false;
			searchInput.value = '';
			renderModules( '' );
		}
	}
	function moduleIcon( type ) {
		var m = ( cfg.moduleIcons || {} )[ type ];
		if ( ! m ) {
			return null;
		}
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + m + '</svg>';
	}
	var GROUP_STATE_KEY = 'ajsBuilderCollapsedGroups';
	function collapsedGroups() {
		try {
			return JSON.parse( localStorage.getItem( GROUP_STATE_KEY ) ) || {};
		} catch ( e ) {
			return {};
		}
	}
	function setGroupCollapsed( g, collapsed ) {
		var s = collapsedGroups();
		if ( collapsed ) {
			s[ g ] = 1;
		} else {
			delete s[ g ];
		}
		try {
			localStorage.setItem( GROUP_STATE_KEY, JSON.stringify( s ) );
		} catch ( e ) {}
	}
	function renderModules( query ) {
		disposeEditors();
		panelBody.innerHTML = '';
		var q = ( query || '' ).toLowerCase().trim();

		var addWrap = h( 'div', { class: 'ajs-add-section' } );
		var addBtn = h( 'button', { type: 'button' } );
		addBtn.innerHTML = ICON.plus + ' ' + cfg.i18n.addSection;
		addBtn.style.display = 'flex';
		addBtn.style.alignItems = 'center';
		addBtn.style.justifyContent = 'center';
		addBtn.style.gap = '8px';
		addBtn.addEventListener( 'click', function () {
			addSectionAfter( null );
		} );
		addWrap.appendChild( addBtn );
		panelBody.appendChild( addWrap );

		var target = containerColumn();
		if ( state.container && target ) {
			var note = h( 'div', { class: 'ajs-addnote' } );
			note.appendChild( h( 'span', { class: 'ajs-addnote__text', text: cfg.i18n.addingTo } ) );
			var fresh = h( 'button', { type: 'button', class: 'ajs-addnote__btn', text: cfg.i18n.newSectionHint } );
			fresh.addEventListener( 'click', function () {
				state.container = null;
				renderModules( '' );
			} );
			note.appendChild( fresh );
			panelBody.appendChild( note );
		}

		panelBody.appendChild( h( 'div', { class: 'ajs-draghint', text: cfg.i18n.dragHint } ) );

		var groups = cfg.groups;
		var byGroup = {};
		Object.keys( cfg.widgets ).forEach( function ( type ) {
			var w = cfg.widgets[ type ];
			if ( w.disabled ) {
				return;
			}
			if ( q && w.title.toLowerCase().indexOf( q ) === -1 ) {
				return;
			}
			var gs = w.groups && w.groups.length ? w.groups : [ w.group || 'layout' ];
			gs.forEach( function ( g ) {
				( byGroup[ g ] = byGroup[ g ] || [] ).push( { type: type, w: w } );
			} );
		} );

		var any = false;
		var collapsed = collapsedGroups();
		Object.keys( groups ).forEach( function ( g ) {
			if ( ! byGroup[ g ] || ! byGroup[ g ].length ) {
				return;
			}
			any = true;
			var isCollapsed = ! q && !! collapsed[ g ];
			var group = h( 'div', { class: 'ajs-group' } );
			if ( isCollapsed ) {
				group.classList.add( 'is-collapsed' );
			}

			var head = h( 'button', { type: 'button', class: 'ajs-group__head' } );
			head.appendChild( h( 'span', { class: 'ajs-group__title', text: groups[ g ] } ) );
			head.appendChild( h( 'span', { class: 'ajs-group__count', text: String( byGroup[ g ].length ) } ) );
			head.appendChild( h( 'span', { class: 'ajs-group__chev', html: ICON.chev } ) );
			head.setAttribute( 'aria-expanded', isCollapsed ? 'false' : 'true' );
			head.addEventListener( 'click', function () {
				var now = group.classList.toggle( 'is-collapsed' );
				head.setAttribute( 'aria-expanded', now ? 'false' : 'true' );
				setGroupCollapsed( g, now );
			} );

			var body = h( 'div', { class: 'ajs-group__body' } );
			var grid = h( 'div', { class: 'ajs-grid' } );
			byGroup[ g ].forEach( function ( item ) {
				var tile = h( 'button', { type: 'button', class: 'ajs-tile' } );
				var ico  = h( 'span', { class: 'ajs-tile__ico ajs-tile__ico--' + g } );
				var svg  = moduleIcon( item.type );
				if ( svg ) {
					ico.innerHTML = svg;
				} else {
					ico.textContent = item.w.title.charAt( 0 );
				}
				tile.appendChild( ico );
				tile.appendChild( h( 'span', { class: 'ajs-tile__name', text: item.w.title } ) );
				tile.addEventListener( 'click', function () {
					if ( Date.now() - lastDragAt < 300 ) {
						return;
					}
					addWidget( item.type );
				} );
				bindTileDrag( tile, item.type, item.w.title );
				grid.appendChild( tile );
			} );
			body.appendChild( grid );
			group.appendChild( head );
			group.appendChild( body );
			panelBody.appendChild( group );
		} );
		if ( ! any ) {
			panelBody.appendChild( h( 'div', { class: 'ajs-panel__empty', text: cfg.i18n.noModules } ) );
		}
	}
	function deviceName( device ) {
		if ( device === 'tablet' ) {
			return cfg.i18n.deviceTablet || 'Tablet';
		}
		if ( device === 'mobile' ) {
			return cfg.i18n.deviceMobile || 'Mobile';
		}
		return cfg.i18n.deviceDesktop || 'Desktop';
	}
	/**
	 * Fold explicit per-device copies (key_tablet / key_mobile, or a
	 * key_desktop base) into their base control so a single control can drive
	 * every device through an inline Desktop/Tablet/Mobile switcher. Storage
	 * keys stay identical, so the renderer and saved data are untouched.
	 */
	function mergeDeviceVariants( controls ) {
		var baseOf = {};
		Object.keys( controls ).forEach( function ( key ) {
			var c = controls[ key ];
			if ( ! c.device ) {
				return;
			}
			var base = key.replace( /_(tablet|mobile)$/, '' );
			if ( ! controls[ base ] && controls[ base + '_desktop' ] ) {
				base = base + '_desktop';
			}
			if ( base !== key && controls[ base ] && ! controls[ base ].device ) {
				baseOf[ key ] = base;
			}
		} );
		var out = {};
		Object.keys( controls ).forEach( function ( key ) {
			if ( baseOf[ key ] ) {
				return; // absorbed into its base control
			}
			var c = controls[ key ];
			var isBase = false;
			Object.keys( baseOf ).forEach( function ( vk ) {
				if ( baseOf[ vk ] === key ) {
					isBase = true;
				}
			} );
			if ( isBase ) {
				c = clone( c );
				c.device_control = true;
				c.device_keys = { desktop: key };
				var dchoices = {};
				if ( c.choices ) {
					dchoices.desktop = c.choices;
				}
				Object.keys( baseOf ).forEach( function ( vk ) {
					if ( baseOf[ vk ] !== key ) {
						return;
					}
					var dev = controls[ vk ].device;
					c.device_keys[ dev ] = vk;
					if ( controls[ vk ].choices ) {
						dchoices[ dev ] = controls[ vk ].choices;
					}
				} );
				if ( Object.keys( dchoices ).length ) {
					c.device_choices = dchoices;
				}
				if ( c.label ) {
					c.label = c.label
						.replace( /\s*[–-]\s*(desktop|tablet|mobile)\s*$/i, '' )
						.replace( /\s*\((desktop|tablet|mobile)\)\s*$/i, '' );
				}
			}
			out[ key ] = c;
		} );
		return out;
	}
	/**
	 * Give every control flagged `responsive` a single control with an inline
	 * Desktop/Tablet/Mobile switcher (the renderer still reads the suffixed
	 * <name>_tablet / <name>_mobile keys).
	 */
	function expandResponsive( controls ) {
		var out = {};
		Object.keys( controls ).forEach( function ( key ) {
			var c = controls[ key ];
			if ( ! c.responsive || c.device || c.device_control ) {
				out[ key ] = c;
				return;
			}
			var single = clone( c );
			single.device_control = true;
			out[ key ] = single;
		} );
		return out;
	}
	function elementControls( f ) {
		var base = f.type === 'widget' ? widgetControls( f.el.type ) : cfg.elements[ f.type ];
		return expandResponsive( mergeDeviceVariants( base || {} ) );
	}
	/**
	 * How many direct children a container element currently holds. Used to
	 * hide controls that only make sense once a container has enough items
	 * (e.g. a column's direction needs at least two modules).
	 *
	 * @param {Object} f Found element record.
	 * @return {number} Child count.
	 */
	function elementChildCount( f ) {
		if ( ! f || ! f.el ) {
			return 0;
		}
		if ( f.type === 'column' ) {
			return ( f.el.widgets || [] ).length;
		}
		if ( f.type === 'section' ) {
			return ( f.el.columns || [] ).length;
		}
		return 0;
	}
	function renderSettings() {
		disposeEditors();
		panelBody.innerHTML = '';
		var f = findElement( state.selected.id );
		if ( ! f ) {
			state.selected = null;
			renderPanel();
			return;
		}
		var controls = elementControls( f );

		// Header
		var head = h( 'div', { class: 'ajs-elhead' } );
		head.appendChild( h( 'span', { class: 'ajs-elhead__name', text: labelFor( state.selected ) } ) );
		var actions = h( 'div', { class: 'ajs-elhead__actions' } );
		var bDup = h( 'button', { type: 'button', class: 'ajs-iconbtn', html: ICON.dup, title: cfg.i18n.duplicate } );
		bDup.addEventListener( 'click', duplicateSelected );
		var bDel = h( 'button', { type: 'button', class: 'ajs-iconbtn ajs-iconbtn--danger', html: ICON.trash, title: cfg.i18n.delete } );
		bDel.addEventListener( 'click', deleteSelected );
		actions.appendChild( bDup );
		actions.appendChild( bDel );
		head.appendChild( actions );
		panelBody.appendChild( head );

		var device = state.device || 'desktop';
		var hint = h( 'div', { class: 'ajs-devhint ajs-devhint--' + device } );
		hint.innerHTML = ICON[ device === 'desktop' ? 'section' : 'column' ] +
			'<span>' + escAttr( ( cfg.i18n.responsiveHint || 'Editing %s values.' ).replace( '%s', deviceName( device ) ) ) + '</span>';
		panelBody.appendChild( hint );

		var order = [ 'Content', 'Layout', 'Style', 'Advanced' ];
		var groupsMap = {};
		Object.keys( controls ).forEach( function ( key ) {
			var c = controls[ key ];
			// Per-device controls surface only while that device is active.
			if ( c.device && c.device !== device ) {
				return;
			}
			// Some controls only make sense once a container has enough children
			// (e.g. a column needs two or more modules before direction matters).
			if ( c.min_children && elementChildCount( f ) < c.min_children ) {
				return;
			}
			var g = c.group || 'Content';
			( groupsMap[ g ] = groupsMap[ g ] || [] ).push( { key: key, c: c } );
		} );
		var keys = order.filter( function ( g ) {
			return groupsMap[ g ];
		} );
		Object.keys( groupsMap ).forEach( function ( g ) {
			if ( keys.indexOf( g ) === -1 ) {
				keys.push( g );
			}
		} );

		keys.forEach( function ( g, idx ) {
			var sec = h( 'div', { class: 'ajs-sec' + ( idx === 0 ? ' is-open' : '' ) } );
			var headBtn = h( 'button', { type: 'button', class: 'ajs-sec__head' } );
			headBtn.appendChild( h( 'span', { text: g } ) );
			headBtn.appendChild( h( 'span', { html: ICON.chev } ) );
			headBtn.addEventListener( 'click', function () {
				sec.classList.toggle( 'is-open' );
			} );
			var body = h( 'div', { class: 'ajs-sec__body' } );
			groupsMap[ g ].forEach( function ( item ) {
				body.appendChild( buildController( f.el, item.key, item.c, f.type ) );
			} );
			sec.appendChild( headBtn );
			sec.appendChild( body );
			panelBody.appendChild( sec );
		} );

		initPickers( panelBody );
		applyConditions();
	}
	function getVal( el, key, ctrl ) {
		if ( el.settings && el.settings[ key ] !== undefined ) {
			return el.settings[ key ];
		}
		return ctrl && 'default' in ctrl ? clone( ctrl.default ) : '';
	}
	function setVal( el, key, val ) {
		el.settings = el.settings || {};
		el.settings[ key ] = val;
	}
	// Storage key for a device-aware control on a given device.
	function deviceKeyFor( key, ctrl, dev ) {
		if ( ctrl && ctrl.device_keys && ctrl.device_keys[ dev ] ) {
			return ctrl.device_keys[ dev ];
		}
		return dev === 'desktop' ? key : key + '_' + dev;
	}
	// Devices a value cascades from: mobile inherits tablet inherits desktop,
	// matching the max-width media queries in the renderer.
	function deviceChain( device ) {
		if ( device === 'mobile' ) {
			return [ 'mobile', 'tablet', 'desktop' ];
		}
		if ( device === 'tablet' ) {
			return [ 'tablet', 'desktop' ];
		}
		return [ 'desktop' ];
	}
	// Value shown for the active device: the first explicitly-set value in the
	// cascade, otherwise the control default. Nothing is written.
	function resolveDeviceValue( el, key, ctrl, device ) {
		var chain = deviceChain( device );
		for ( var i = 0; i < chain.length; i++ ) {
			var k = deviceKeyFor( key, ctrl, chain[ i ] );
			if ( el.settings && el.settings[ k ] !== undefined ) {
				return el.settings[ k ];
			}
		}
		return ctrl && 'default' in ctrl ? clone( ctrl.default ) : '';
	}
	function deviceDropdown() {
		var dev = state.device || 'desktop';
		var box = h( 'span', { class: 'ajs-devseg' } );
		[
			[ 'desktop', cfg.i18n.deviceDesktop || 'Desktop', ICON.desktop ],
			[ 'tablet', cfg.i18n.deviceTablet || 'Tablet', ICON.tablet ],
			[ 'mobile', cfg.i18n.deviceMobile || 'Mobile', ICON.mobile ]
		].forEach( function ( d ) {
			var b = h( 'button', {
				type: 'button',
				class: 'ajs-devseg__btn' + ( dev === d[ 0 ] ? ' is-active' : '' ),
				html: d[ 2 ],
				title: d[ 1 ],
				'aria-label': d[ 1 ]
			} );
			b.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				setDevice( d[ 0 ] );
			} );
			box.appendChild( b );
		} );
		return box;
	}
	function buildController( el, key, ctrl, elType ) {
		var wrap = h( 'div', { class: 'ajs-ctl', 'data-ctlkey': key } );
		var device = state.device || 'desktop';
		// Device-aware controls read & write the value for the active device
		// through a single control with an inline device switcher.
		var devKey = ctrl.device_control ? deviceKeyFor( key, ctrl, device ) : key;
		var hasLabel = !!( ctrl.type !== 'switch' && ctrl.label );
		if ( hasLabel ) {
			var label = h( 'label', { class: 'ajs-ctl__label' + ( ctrl.device_control ? ' ajs-ctl__label--dev' : '' ), text: ctrl.label } );
			if ( ctrl.device_control ) {
				label.appendChild( deviceDropdown() );
			}
			wrap.appendChild( label );
		}
		var value = ctrl.device_control ? resolveDeviceValue( el, key, ctrl, device ) : getVal( el, devKey, ctrl );
		var ctrlForDevice = ctrl;
		if ( ctrl.device_choices && ctrl.device_choices[ device ] ) {
			ctrlForDevice = clone( ctrl );
			ctrlForDevice.choices = ctrl.device_choices[ device ];
		}
		function change( v ) {
			setVal( el, devKey, v );
			markDirty();
			applyConditions();
		}
		wrap.appendChild( buildControl( devKey, ctrlForDevice, value, change, el, elType ) );
		if ( ctrl.device_control && ! hasLabel ) {
			var devrow = h( 'div', { class: 'ajs-ctl__devrow' } );
			devrow.appendChild( deviceDropdown() );
			wrap.appendChild( devrow );
		}
		if ( ctrl.hint ) {
			wrap.appendChild( h( 'div', { class: 'ajs-ctl__hint', text: ctrl.hint } ) );
		}
		return wrap;
	}
	function applyConditions() {
		if ( ! state.selected ) {
			return;
		}
		var f = findElement( state.selected.id );
		if ( ! f ) {
			return;
		}
		var el = f.el;
		var controls = elementControls( f );
		if ( ! controls ) {
			return;
		}
		panelBody.querySelectorAll( '.ajs-ctl[data-ctlkey]' ).forEach( function ( w ) {
			var key = w.getAttribute( 'data-ctlkey' );
			var c = controls[ key ];
			if ( ! c || ! c.show_if ) {
				w.style.display = '';
				return;
			}
			var ok = true;
			Object.keys( c.show_if ).forEach( function ( dep ) {
				var want = c.show_if[ dep ];
				var cur = ( el.settings && el.settings[ dep ] !== undefined )
					? el.settings[ dep ]
					: ( controls[ dep ] && 'default' in controls[ dep ] ? controls[ dep ].default : '' );
				if ( Object.prototype.toString.call( want ) === '[object Array]' ) {
					var hit = want.some( function ( v ) {
						return String( v ) === String( cur );
					} );
					if ( ! hit ) {
						ok = false;
					}
				} else if ( String( cur ) !== String( want ) ) {
					ok = false;
				}
			} );
			w.style.display = ok ? '' : 'none';
		} );
	}
	/* ----------------------------------------------------------- Colours */
	// A gradient is stored as a real CSS value so the renderer can promote it
	// from `background-color` to `background-image` without caring where the
	// value came from.
	function isGradient( v ) {
		return typeof v === 'string' && /(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i.test( v );
	}
	// Split a CSS argument list on top-level commas (ignores commas inside
	// rgba(...)/var(...) etc.).
	function splitCssList( str ) {
		var out = [];
		var depth = 0;
		var cur = '';
		for ( var i = 0; i < str.length; i++ ) {
			var ch = str.charAt( i );
			if ( ch === '(' ) {
				depth++;
			} else if ( ch === ')' ) {
				depth = Math.max( 0, depth - 1 );
			}
			if ( ch === ',' && depth === 0 ) {
				out.push( cur.trim() );
				cur = '';
				continue;
			}
			cur += ch;
		}
		if ( cur.trim() ) {
			out.push( cur.trim() );
		}
		return out;
	}
	// Pull { angle, from, to } out of a linear-gradient() value. Anything we
	// can't read falls back to sane defaults so the UI never breaks.
	function parseGradient( v ) {
		if ( ! isGradient( v ) ) {
			return null;
		}
		var inner = v.replace( /^\s*[a-z-]*gradient\s*\(/i, '' ).replace( /\)\s*$/, '' );
		var parts = splitCssList( inner );
		var angle = 135;
		if ( parts.length && /^-?[\d.]+deg$/i.test( parts[ 0 ] ) ) {
			angle = parseFloat( parts.shift() );
		}
		// Radial/conic gradients lead with a shape or position descriptor
		// ("circle at 30% 40%") that is not a colour — drop it so the editor
		// shows something usable instead of a broken stop.
		if ( parts.length > 2 && /(?:^|\s)(?:circle|ellipse|at\s|closest-side|closest-corner|farthest-side|farthest-corner|from\s)/i.test( parts[ 0 ] ) ) {
			parts.shift();
		}
		parts = parts.map( function ( p ) {
			return p.replace( /\s+[\d.]+%(\s+[\d.]+%)?$/, '' ).trim();
		} );
		return {
			angle: angle,
			from: parts[ 0 ] || '',
			to: parts.length > 1 ? parts[ parts.length - 1 ] : ''
		};
	}
	function gradientCss( angle, from, to ) {
		if ( ! from || ! to ) {
			return '';
		}
		var a = ( angle === '' || angle === null || angle === undefined ) ? 135 : angle;
		return 'linear-gradient(' + a + 'deg, ' + from + ', ' + to + ')';
	}

	// A single colour row: WP swatch + always-visible code field + reset.
	function buildColorRow( ctrl, value, onChange, opts ) {
		opts = opts || {};
		var row = h( 'div', { class: 'ajs-colorwrap' + ( opts.className ? ' ' + opts.className : '' ) } );
		// WordPress' picker (iris) can only parse real colours. A CSS variable
		// default such as var(--ajs-primary) makes the swatch blank and
		// silently breaks the Default/Clear button, so treat any non-colour
		// value as "no colour" (inherit).
		var cval = isPickerColor( value ) ? value : '';
		var cdef = isPickerColor( ctrl.default ) ? ctrl.default : '';
		var cin  = h( 'input', { type: 'text', class: 'ajs-color', value: cval, 'data-default': cdef } );
		cin.__ajsChange = onChange;
		// Always-visible colour code field: type or paste a hex/rgb value
		// without opening the picker. Kept in sync with the swatch both ways.
		var ccode = h( 'input', {
			type: 'text',
			class: 'ajs-color-code',
			value: value || '',
			placeholder: opts.placeholder || cdef || '#000000',
			spellcheck: 'false',
			autocomplete: 'off',
			'aria-label': ( opts.label || ctrl.label || 'Colour' ) + ' code'
		} );
		ccode.addEventListener( 'input', function () {
			var v = ccode.value.trim();
			onChange( v );
			if ( isPickerColor( v ) && $.fn.wpColorPicker && $( cin ).hasClass( 'wp-color-picker' ) ) {
				$( cin ).wpColorPicker( 'color', v );
			}
		} );
		var creset = h( 'button', {
			type: 'button',
			class: 'ajs-color-reset',
			html: ICON.reset,
			title: cfg.i18n.resetColor || cfg.i18n.reset || 'Reset',
			'aria-label': cfg.i18n.resetColor || cfg.i18n.reset || 'Reset'
		} );
		creset.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			// Prefer WordPress' own Clear/Default button so the swatch, the
			// code field and the saved value all stay in sync.
			if ( $.fn.wpColorPicker ) {
				var $btn = $( cin ).closest( '.wp-picker-container' ).find( '.wp-picker-default, .wp-picker-clear' );
				if ( $btn.length ) {
					$btn.trigger( 'click' );
					return;
				}
			}
			cin.value = cdef || '';
			ccode.value = cdef || '';
			if ( $.fn.wpColorPicker && $( cin ).hasClass( 'wp-color-picker' ) ) {
				$( cin ).wpColorPicker( 'color', cdef || '' );
			}
			onChange( cdef );
		} );
		row.appendChild( cin );
		row.appendChild( ccode );
		if ( ! opts.hideReset ) {
			row.appendChild( creset );
		}
		return row;
	}

	// Colour control. Controls flagged `gradient: true` get a Solid/Gradient
	// switch; everything else stays a plain picker row.
	function buildColorControl( ctrl, value, onChange ) {
		if ( ! ctrl.gradient ) {
			return buildColorRow( ctrl, value, onChange );
		}

		var isGrad   = !! isGradient( value );
		var state    = isGrad ? parseGradient( value ) : null;
		var solidVal = isGrad ? '' : ( value || '' );
		var fromVal  = state ? state.from : solidVal;
		var toVal    = state ? state.to : '';
		var angleEl;
		var angleOut;

		var wrap = h( 'div', { class: 'ajs-colorwrap--gradient' } );

		// Solid / Gradient switch.
		var modes  = h( 'div', { class: 'ajs-colormodes' } );
		var bSolid = h( 'button', { type: 'button', class: 'ajs-colormode', text: cfg.i18n.solid || 'Solid' } );
		var bGrad  = h( 'button', { type: 'button', class: 'ajs-colormode', text: cfg.i18n.gradient || 'Gradient' } );
		modes.appendChild( bSolid );
		modes.appendChild( bGrad );

		// --- Solid pane -----------------------------------------------------
		var solidPane = h( 'div', { class: 'ajs-colormodepane' } );
		solidPane.appendChild( buildColorRow( ctrl, solidVal, function ( v ) {
			solidVal = v;
			onChange( v );
		} ) );

		// --- Gradient pane --------------------------------------------------
		var gradPane  = h( 'div', { class: 'ajs-colormodepane' } );
		var previewEl = h( 'div', { class: 'ajs-gradpreview' } );

		function commit() {
			previewEl.style.background = gradientCss( angleEl.value, fromVal, toVal ) || 'transparent';
			var css = gradientCss( angleEl.value, fromVal, toVal );
			if ( css ) {
				onChange( css );
			}
		}
		function stopRow( label, color, setter ) {
			var row = h( 'div', { class: 'ajs-gradrow' } );
			row.appendChild( h( 'span', { class: 'ajs-gradrow__label', text: label } ) );
			row.appendChild( buildColorRow( { default: '', label: label }, color, setter, { hideReset: true } ) );
			return row;
		}

		gradPane.appendChild( stopRow( cfg.i18n.from || 'From', fromVal, function ( v ) {
			fromVal = v;
			commit();
		} ) );
		gradPane.appendChild( stopRow( cfg.i18n.to || 'To', toVal, function ( v ) {
			toVal = v;
			commit();
		} ) );

		var angleRow = h( 'div', { class: 'ajs-gradrow' } );
		angleRow.appendChild( h( 'span', { class: 'ajs-gradrow__label', text: cfg.i18n.angle || 'Angle' } ) );
		angleEl  = h( 'input', { type: 'range', class: 'ajs-gradangle', min: 0, max: 360, step: 1, value: state ? state.angle : 135 } );
		angleOut = h( 'output', { class: 'ajs-gradangle-out', text: ( state ? state.angle : 135 ) + '\u00b0' } );
		angleEl.addEventListener( 'input', function () {
			angleOut.textContent = angleEl.value + '\u00b0';
			commit();
		} );
		angleRow.appendChild( angleEl );
		angleRow.appendChild( angleOut );
		gradPane.appendChild( angleRow );
		gradPane.appendChild( previewEl );

		previewEl.style.background = gradientCss( angleEl.value, fromVal, toVal ) || 'transparent';

		// --- Mode switch ----------------------------------------------------
		function show( grad ) {
			bSolid.classList.toggle( 'is-active', ! grad );
			bGrad.classList.toggle( 'is-active', !! grad );
			solidPane.style.display = grad ? 'none' : '';
			gradPane.style.display = grad ? '' : 'none';
		}
		show( isGrad );

		bSolid.addEventListener( 'click', function () {
			show( false );
			onChange( solidVal || fromVal || '' );
		} );
		bGrad.addEventListener( 'click', function () {
			show( true );
			if ( ! fromVal ) {
				fromVal = isPickerColor( solidVal ) ? solidVal : '#000000';
			}
			if ( ! toVal ) {
				toVal = '#ffffff';
			}
			commit();
			// Pickers revealed by the switch may not be initialised yet.
			initPickers( panelBody );
		} );

		wrap.appendChild( modes );
		wrap.appendChild( solidPane );
		wrap.appendChild( gradPane );
		return wrap;
	}

	function buildControl( key, ctrl, value, onChange, el, elType ) {
		var t = ctrl.type;
		if ( t === 'text' || t === 'url' ) {
			var inp = h( 'input', { type: t === 'url' ? 'url' : 'text', value: value || '', placeholder: ctrl.placeholder || '' } );
			inp.addEventListener( 'input', function () {
				onChange( inp.value );
			} );
			return inp;
		}
		if ( t === 'number' ) {
			var nin = h( 'input', { type: 'number', value: value === undefined || value === null ? '' : value, placeholder: ctrl.placeholder || '' } );
			nin.addEventListener( 'input', function () {
				onChange( nin.value === '' ? '' : parseFloat( nin.value ) );
			} );
			return nin;
		}
		if ( t === 'textarea' || t === 'code' ) {
			var ta = h( 'textarea', { rows: ctrl.rows || 4, class: t === 'code' ? 'ajs-code' : '' } );
			ta.value = value || '';
			ta.addEventListener( 'input', function () {
				onChange( ta.value );
			} );
			return ta;
		}
		if ( t === 'select' ) {
			var sel = h( 'select' );
			Object.keys( ctrl.choices || {} ).forEach( function ( v ) {
				var o = h( 'option', { value: v, text: ctrl.choices[ v ] } );
				if ( String( value ) === String( v ) ) {
					o.selected = true;
				}
				sel.appendChild( o );
			} );
			sel.addEventListener( 'change', function () {
				var v = sel.value;
				if ( key === 'columns_count' && ( elType === 'section' || ( el && el.type === 'row' ) ) ) {
					onChange( v );
					syncColumns( el, parseInt( v, 10 ) );
					renderSettings();
					return;
				}
				onChange( /^\d+$/.test( v ) ? parseInt( v, 10 ) : v );
			} );
			return sel;
		}
		if ( t === 'choose' ) {
			var box = h( 'div', { class: 'ajs-choose' } );
			Object.keys( ctrl.choices || {} ).forEach( function ( v ) {
				var b = h( 'button', { type: 'button', class: String( value ) === String( v ) ? 'is-active' : '', text: ctrl.choices[ v ] } );
				b.addEventListener( 'click', function () {
					box.querySelectorAll( 'button' ).forEach( function ( x ) {
						x.classList.remove( 'is-active' );
					} );
					b.classList.add( 'is-active' );
					onChange( v );
				} );
				box.appendChild( b );
			} );
			return box;
		}
		if ( t === 'switch' ) {
			var lab = h( 'label', { class: 'ajs-switch' } );
			var cb = h( 'input', { type: 'checkbox' } );
			cb.checked = !! value;
			var track = h( 'span', { class: 'ajs-switch__track' } );
			lab.appendChild( cb );
			lab.appendChild( track );
			if ( ctrl.label ) {
				lab.appendChild( h( 'span', { text: ctrl.label } ) );
			}
			cb.addEventListener( 'change', function () {
				onChange( cb.checked );
			} );
			return lab;
		}
		if ( t === 'slider' ) {
			var sw = h( 'div', { class: 'ajs-slider' } );
			var out = h( 'output', { text: value === undefined ? '' : value } );
			var rng = h( 'input', { type: 'range', min: ctrl.min || 0, max: ctrl.max == null ? 100 : ctrl.max, step: ctrl.step || 1, value: value === undefined ? 0 : value } );
			rng.addEventListener( 'input', function () {
				out.textContent = rng.value;
				onChange( parseFloat( rng.value ) );
			} );
			sw.appendChild( rng );
			sw.appendChild( out );
			return sw;
		}
		if ( t === 'color' ) {
			return buildColorControl( ctrl, value, onChange );
		}
		if ( t === 'media' ) {
			return mediaControl( ctrl, value, onChange );
		}
		if ( t === 'icon' ) {
			return iconControl( ctrl, value, onChange );
		}
		if ( t === 'dimensions' ) {
			return dimensionsControl( ctrl, value, onChange );
		}
		if ( t === 'repeater' ) {
			return repeaterControl( ctrl, value, onChange );
		}
		if ( t === 'typography' ) {
			return typographyControl( ctrl, value, onChange );
		}
		if ( t === 'editor' ) {
			return editorControl( ctrl, value, onChange );
		}
		var fallback = h( 'input', { type: 'text', value: value || '' } );
		fallback.addEventListener( 'input', function () {
			onChange( fallback.value );
		} );
		return fallback;
	}
	function disposeEditors() {
		Object.keys( activeEditors ).forEach( function ( id ) {
			activeEditors[ id ] = false;
			try {
				if ( window.wp && wp.editor && wp.editor.remove ) {
					wp.editor.remove( id );
				}
			} catch ( e ) {}
		} );
		activeEditors = {};
	}
	function editorControl( ctrl, value, onChange ) {
		editorSeq++;
		var id = 'ajs-editor-' + editorSeq;
		activeEditors[ id ] = true;

		var wrap = h( 'div', { class: 'ajs-editor' } );
		var ta = h( 'textarea', { id: id, class: 'ajs-editor__area', rows: ctrl.rows || 8 } );
		ta.value = value || '';
		ta.addEventListener( 'input', function () {
			onChange( ta.value );
		} );
		wrap.appendChild( ta );

		function tryInit( tries ) {
			if ( activeEditors[ id ] !== true ) {
				return;
			}
			var apiReady = window.wp && wp.editor && wp.editor.getDefaultSettings && wp.editor.initialize;
			var inDom    = !! document.getElementById( id );
			if ( apiReady && inDom ) {
				try {
					var hasTiny = !! window.tinymce;
					var mceSettings = hasTiny ? {
						toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,undo,redo',
						toolbar2: '',
						height: 240,
						setup: function ( ed ) {
							ed.on( 'change keyup undo redo', function () {
								onChange( ed.getContent() );
							} );
						}
					} : false;
					wp.editor.initialize( id, {
						tinymce: mceSettings,
						quicktags: true,
						mediaButtons: true
					} );
				} catch ( err ) {}
				return;
			}
			if ( tries < 100 ) {
				window.setTimeout( function () {
					tryInit( tries + 1 );
				}, 50 );
			}
		}
		tryInit( 0 );

		return wrap;
	}
	function syncColumns( sec, count ) {
		sec.columns = sec.columns || [];
		while ( sec.columns.length < count ) {
			sec.columns.push( newColumn() );
		}
		if ( sec.columns.length > count ) {
			sec.columns = sec.columns.slice( 0, count );
		}
	}
	function mediaControl( ctrl, value, onChange ) {
		value = value && typeof value === 'object' ? value : { id: 0, url: '' };
		var box = h( 'div', { class: 'ajs-media' } );
		function render() {
			box.innerHTML = '';
			var row = h( 'div', { class: 'ajs-media__box' } );
			var thumb = h( 'span', { class: 'ajs-media__thumb' } );
			thumb.innerHTML = value.url ? '<img src="' + escAttr( value.url ) + '" alt="">' : ICON.img;
			var btns = h( 'div', { class: 'ajs-media__btns' } );
			var choose = h( 'button', { type: 'button', class: 'button', text: value.url ? cfg.i18n.replaceImage : cfg.i18n.chooseImage } );
			choose.addEventListener( 'click', function () {
				openMedia( function ( att ) {
					value = { id: att.id, url: att.url };
					onChange( value );
					render();
				} );
			} );
			btns.appendChild( choose );
			if ( value.url ) {
				var rm = h( 'button', { type: 'button', class: 'button', text: cfg.i18n.removeImage } );
				rm.addEventListener( 'click', function () {
					value = { id: 0, url: '' };
					onChange( value );
					render();
				} );
				btns.appendChild( rm );
			}
			row.appendChild( thumb );
			row.appendChild( btns );
			box.appendChild( row );
		}
		render();
		return box;
	}
	function iconControl( ctrl, value, onChange ) {
		var wrap    = h( 'div', { class: 'ajs-iconpick' } );
		var current = value || ctrl.default || '';
		var names   = Object.keys( cfg.icons.paths );
		var grid    = h( 'div', { class: 'ajs-icons' } );
		var search  = h( 'input', { type: 'search', class: 'ajs-iconpick__search', placeholder: cfg.i18n.searchIcons || 'Search icons…' } );

		function render( q ) {
			q = ( q || '' ).trim().toLowerCase();
			grid.innerHTML = '';
			var shown = 0;
			names.forEach( function ( name ) {
				var label = cfg.icons.labels[ name ] || name;
				if ( q && name.toLowerCase().indexOf( q ) === -1 && label.toLowerCase().indexOf( q ) === -1 ) {
					return;
				}
				shown++;
				var b = h( 'button', { type: 'button', class: name === current ? 'is-active' : '', title: label, 'data-icon': name } );
				b.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + cfg.icons.paths[ name ] + '</svg>';
				b.addEventListener( 'click', function () {
					current = name;
					grid.querySelectorAll( 'button' ).forEach( function ( x ) {
						x.classList.remove( 'is-active' );
					} );
					b.classList.add( 'is-active' );
					onChange( name );
				} );
				grid.appendChild( b );
			} );
			if ( ! shown ) {
				grid.appendChild( h( 'div', { class: 'ajs-icons__empty', text: cfg.i18n.noIconsFound || 'No icons found' } ) );
			}
		}

		search.addEventListener( 'input', function () {
			render( search.value );
		} );

		render( '' );
		wrap.appendChild( search );
		wrap.appendChild( grid );
		return wrap;
	}
	function dimensionsControl( ctrl, value, onChange ) {
		var v = $.extend( { unit: 'px', top: '', right: '', bottom: '', left: '' }, ctrl.default || {}, value && typeof value === 'object' ? value : {} );
		var box = h( 'div', { class: 'ajs-dim' } );
		[ 'top', 'right', 'bottom', 'left' ].forEach( function ( side ) {
			var inp = h( 'input', { type: 'number', value: v[ side ] === undefined || v[ side ] === '' ? '' : v[ side ], placeholder: '-' } );
			inp.addEventListener( 'input', function () {
				v[ side ] = inp.value === '' ? '' : parseFloat( inp.value );
				onChange( clone( v ) );
			} );
			box.appendChild( h( 'label', {}, inp ) );
		} );
		var unitWrap = h( 'div', { class: 'ajs-dim__unit' } );
		var usel = h( 'select' );
		[ 'px', 'em', 'rem', '%', 'vw', 'vh' ].forEach( function ( u ) {
			var o = h( 'option', { value: u, text: u } );
			if ( v.unit === u ) {
				o.selected = true;
			}
			usel.appendChild( o );
		} );
		usel.addEventListener( 'change', function () {
			v.unit = usel.value;
			onChange( clone( v ) );
		} );
		unitWrap.appendChild( usel );
		box.appendChild( unitWrap );
		return box;
	}
	var AJS_FONTS = [
		{ v: 'system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif', l: 'System UI' },
		{ v: 'Arial, Helvetica, sans-serif', l: 'Arial' },
		{ v: '"Helvetica Neue", Helvetica, Arial, sans-serif', l: 'Helvetica Neue' },
		{ v: 'Georgia, serif', l: 'Georgia' },
		{ v: '"Times New Roman", Times, serif', l: 'Times New Roman' },
		{ v: '"Trebuchet MS", Helvetica, sans-serif', l: 'Trebuchet MS' },
		{ v: 'Verdana, Geneva, sans-serif', l: 'Verdana' },
		{ v: 'Tahoma, Geneva, sans-serif', l: 'Tahoma' },
		{ v: '"Courier New", Courier, monospace', l: 'Courier New' },
		{ v: 'Poppins, sans-serif', l: 'Poppins' },
		{ v: 'Montserrat, sans-serif', l: 'Montserrat' },
		{ v: 'Lato, sans-serif', l: 'Lato' },
		{ v: 'Oswald, sans-serif', l: 'Oswald' },
		{ v: '"Playfair Display", serif', l: 'Playfair Display' },
		{ v: 'Roboto, sans-serif', l: 'Roboto' }
	];
	function fontDatalist() {
		if ( document.getElementById( 'ajs-font-list' ) ) {
			return;
		}
		var dl = h( 'datalist', { id: 'ajs-font-list' } );
		AJS_FONTS.forEach( function ( f ) {
			dl.appendChild( h( 'option', { value: f.v, text: f.l } ) );
		} );
		document.body.appendChild( dl );
	}
	function typoRow( label, nodes ) {
		var r = h( 'div', { class: 'ajs-typo__row' } );
		r.appendChild( h( 'span', { class: 'ajs-typo__lbl', text: label } ) );
		var f = h( 'div', { class: 'ajs-typo__f' } );
		( Array.isArray( nodes ) ? nodes : [ nodes ] ).forEach( function ( n ) {
			f.appendChild( n );
		} );
		r.appendChild( f );
		return r;
	}
	function unitSelect( units, cur, onSet ) {
		var sel = h( 'select', { class: 'ajs-typo__unit' } );
		units.forEach( function ( u ) {
			var o = h( 'option', { value: u, text: u } );
			if ( String( cur ) === String( u ) ) {
				o.selected = true;
			}
			sel.appendChild( o );
		} );
		sel.addEventListener( 'change', function () {
			onSet( sel.value );
		} );
		return sel;
	}
	function simpleSelect( options, cur, onSet ) {
		var sel = h( 'select' );
		options.forEach( function ( o ) {
			var opt = h( 'option', { value: o[ 0 ], text: o[ 1 ] } );
			if ( String( cur ) === String( o[ 0 ] ) ) {
				opt.selected = true;
			}
			sel.appendChild( opt );
		} );
		sel.addEventListener( 'change', function () {
			onSet( sel.value );
		} );
		return sel;
	}
	/**
	 * Typography control: font family + size/unit, weight, line height,
	 * letter spacing/unit, text transform and font style. Fonts are free-text
	 * with a datalist of sensible suggestions so any web font can be used.
	 *
	 * @param {Object}   ctrl     Control definition.
	 * @param {Object}   value    Current value.
	 * @param {Function} onChange Change handler.
	 * @return {HTMLElement}
	 */
	function typographyControl( ctrl, value, onChange ) {
		fontDatalist();
		var v = ( value && typeof value === 'object' ) ? value : {};
		var box = h( 'div', { class: 'ajs-typo' } );
		function set( k, val ) {
			var nv = $.extend( {}, v );
			nv[ k ] = val;
			v = nv;
			onChange( clone( v ) );
		}

		var fam = h( 'input', { type: 'text', class: 'ajs-typo__fam', list: 'ajs-font-list', placeholder: 'e.g. Poppins, sans-serif', value: v.font_family || '' } );
		fam.addEventListener( 'input', function () {
			set( 'font_family', fam.value );
		} );
		box.appendChild( typoRow( 'Font', fam ) );

		var size = h( 'input', { type: 'number', value: v.font_size === undefined ? '' : v.font_size, placeholder: '-' } );
		size.addEventListener( 'input', function () {
			set( 'font_size', size.value === '' ? '' : parseFloat( size.value ) );
			// Persist a unit as soon as a size is typed, otherwise the saved
			// value has no *_unit key and the CSS ends up unitless/invalid.
			if ( size.value !== '' && ! v.font_size_unit ) {
				set( 'font_size_unit', 'px' );
			}
		} );
		box.appendChild( typoRow( 'Size', [ size, unitSelect( [ 'px', 'em', 'rem', '%', 'vw', 'vh' ], v.font_size_unit || 'px', function ( u ) {
			set( 'font_size_unit', u );
		} ) ] ) );

		box.appendChild( typoRow( 'Weight', simpleSelect( [
			[ '', 'Default' ], [ '300', '300 Light' ], [ '400', '400 Regular' ], [ '500', '500 Medium' ],
			[ '600', '600 Semi-bold' ], [ '700', '700 Bold' ], [ '800', '800 Extra-bold' ], [ '900', '900 Black' ]
		], v.font_weight || '', function ( x ) {
			set( 'font_weight', x );
		} ) ) );

		var lh = h( 'input', { type: 'number', step: '0.1', value: v.line_height === undefined ? '' : v.line_height, placeholder: '-' } );
		lh.addEventListener( 'input', function () {
			set( 'line_height', lh.value === '' ? '' : parseFloat( lh.value ) );
		} );
		box.appendChild( typoRow( 'Line height', lh ) );

		var ls = h( 'input', { type: 'number', step: '0.1', value: v.letter_spacing === undefined ? '' : v.letter_spacing, placeholder: '-' } );
		ls.addEventListener( 'input', function () {
			set( 'letter_spacing', ls.value === '' ? '' : parseFloat( ls.value ) );
			if ( ls.value !== '' && ! v.letter_spacing_unit ) {
				set( 'letter_spacing_unit', 'px' );
			}
		} );
		box.appendChild( typoRow( 'Letter spacing', [ ls, unitSelect( [ 'px', 'em', 'rem' ], v.letter_spacing_unit || 'px', function ( u ) {
			set( 'letter_spacing_unit', u );
		} ) ] ) );

		box.appendChild( typoRow( 'Transform', simpleSelect( [
			[ '', 'Default' ], [ 'none', 'None' ], [ 'uppercase', 'Uppercase' ],
			[ 'lowercase', 'Lowercase' ], [ 'capitalize', 'Capitalize' ]
		], v.text_transform || '', function ( x ) {
			set( 'text_transform', x );
		} ) ) );

		box.appendChild( typoRow( 'Style', simpleSelect( [
			[ '', 'Default' ], [ 'normal', 'Normal' ], [ 'italic', 'Italic' ]
		], v.font_style || '', function ( x ) {
			set( 'font_style', x );
		} ) ) );

		return box;
	}
	function repeaterControl( ctrl, value, onChange ) {
		var items = Array.isArray( value ) ? value : ( ctrl.default ? clone( ctrl.default ) : [] );
		var wrap = h( 'div', { class: 'ajs-rep' } );
		function titleFor( item, i ) {
			var keys = Object.keys( ctrl.fields );
			for ( var k = 0; k < keys.length; k++ ) {
				var val = item[ keys[ k ] ];
				if ( typeof val === 'string' && val.trim() ) {
					return val;
				}
			}
			return 'Item ' + ( i + 1 );
		}
		function renderList() {
			wrap.innerHTML = '';
			items.forEach( function ( item, i ) {
				var box = h( 'div', { class: 'ajs-rep__item' } );
				var head = h( 'div', { class: 'ajs-rep__head' } );
				var ttl = h( 'span', { class: 'ajs-rep__title', text: titleFor( item, i ) } );
				var del = h( 'button', { type: 'button', html: ICON.trash, title: 'Remove' } );
				head.appendChild( h( 'span', { class: 'ajs-rep__handle', html: ICON.move } ) );
				head.appendChild( ttl );
				head.appendChild( del );
				var body = h( 'div', { class: 'ajs-rep__body' } );
				del.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					items.splice( i, 1 );
					onChange( clone( items ) );
					renderList();
				} );
				head.addEventListener( 'click', function () {
					box.classList.toggle( 'is-open' );
				} );
				Object.keys( ctrl.fields ).forEach( function ( fk ) {
					var def = ctrl.fields[ fk ];
					var sub = h( 'div', { class: 'ajs-ctl' } );
					if ( def.type !== 'switch' && def.label ) {
						sub.appendChild( h( 'label', { class: 'ajs-ctl__label', text: def.label } ) );
					}
					var cur = item[ fk ] !== undefined ? item[ fk ] : ( 'default' in def ? clone( def.default ) : '' );
					sub.appendChild( buildControl( fk, def, cur, function ( val ) {
						item[ fk ] = val;
						ttl.textContent = titleFor( item, i );
						onChange( clone( items ) );
					} ) );
					body.appendChild( sub );
				} );
				box.appendChild( head );
				box.appendChild( body );
				wrap.appendChild( box );
			} );
			var add = h( 'button', { type: 'button', class: 'ajs-btn-add' } );
			add.innerHTML = ICON.plus + ' ' + cfg.i18n.addItem;
			add.addEventListener( 'click', function () {
				items.push( controlDefaults( ctrl.fields ) );
				onChange( clone( items ) );
				renderList();
			} );
			wrap.appendChild( add );
		}
		renderList();
		return wrap;
	}
	function initPickers( root ) {
		$( root ).find( '.ajs-color' ).each( function () {
			var el = this;
			if ( el.__ajsPickerReady ) {
				return;
			}
			el.__ajsPickerReady = true;
			var cb = el.__ajsChange;
			var $code = $( el ).closest( '.ajs-colorwrap' ).find( '.ajs-color-code' );
			// Push the picker's value into the sibling colour-code field,
			// without stomping on what the user is currently typing.
			function syncCode( v ) {
				if ( $code.length && document.activeElement !== $code[ 0 ] ) {
					$code.val( v );
				}
			}
			$( el ).wpColorPicker( {
				defaultColor: el.getAttribute( 'data-default' ) || false,
				palettes: true,
				change: function ( e, ui ) {
					if ( cb ) {
						cb( ui.color.toString() );
					}
					syncCode( ui.color.toString() );
				},
				clear: function () {
					if ( cb ) {
						cb( '' );
					}
					syncCode( '' );
				}
			} );
		} );
	}

	/* ---------------------------------------------------------- Media */
	var mediaFrame = null;
	function openMedia( cb ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		mediaFrame = window.wp.media( {
			title: cfg.i18n.chooseImage,
			button: { text: cfg.i18n.chooseImage },
			library: { type: 'image' },
			multiple: false
		} );
		mediaFrame.on( 'select', function () {
			var att = mediaFrame.state().get( 'selection' ).first().toJSON();
			cb( { id: att.id, url: att.url } );
		} );
		mediaFrame.open();
	}

	/* ------------------------------------------------------------ Boot */
	function bindChrome() {
		saveBtn.addEventListener( 'click', save );
		backBtn.addEventListener( 'click', function () {
			deselect();
		} );
		document.getElementById( 'ajs-toggle-panel' ).addEventListener( 'click', function () {
			panel.classList.toggle( 'is-collapsed' );
		} );
		if ( structureToggle ) {
			structureToggle.addEventListener( 'click', function () {
				setStructure( structureEl.hidden );
			} );
		}
		if ( structureClose ) {
			structureClose.addEventListener( 'click', function () {
				setStructure( false );
			} );
		}
		searchInput.addEventListener( 'input', function () {
			renderModules( searchInput.value );
		} );
		titleInput.addEventListener( 'input', markDirty );
		layoutSelect.addEventListener( 'change', markDirty );
		if ( tplType ) {
			tplType.addEventListener( 'change', function () {
				syncTpl();
				markDirty();
			} );
			tplDisplay.addEventListener( 'change', function () {
				syncTpl();
				markDirty();
			} );
			if ( tplSticky ) {
				tplSticky.addEventListener( 'change', function () {
					syncSticky();
					markDirty();
				} );
			}
			if ( tplClass ) {
				tplClass.addEventListener( 'input', markDirty );
			}
			if ( tplEnabled ) {
				tplEnabled.addEventListener( 'change', markDirty );
			}
			syncTpl();
		}
		if ( libToggle ) {
			libToggle.addEventListener( 'click', function () {
				setLibrary( libEl.hidden );
			} );
		}
		if ( libClose ) {
			libClose.addEventListener( 'click', function () {
				setLibrary( false );
			} );
		}
		if ( libImport ) {
			libImport.addEventListener( 'change', function () {
				if ( libImport.files && libImport.files[ 0 ] ) {
					importTemplateFile( libImport.files[ 0 ] );
					libImport.value = '';
				}
			} );
		}
		if ( tplPagesToggle ) {
			tplPagesLabel.setAttribute( 'data-empty', tplPagesLabel.textContent );
			tplPagesToggle.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var open = tplPagesMenu.hidden;
				tplPagesMenu.hidden = ! open;
				tplPagesToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				tplPages.classList.toggle( 'is-open', open );
				if ( open && tplPagesSearch ) {
					tplPagesSearch.value = '';
					Array.prototype.forEach.call( tplPagesMenu.querySelectorAll( '.ajs-dd__groupblock' ), function ( b ) {
						b.hidden = false;
					} );
					Array.prototype.forEach.call( tplPagesMenu.querySelectorAll( '.ajs-dd__opt' ), function ( o ) {
						o.hidden = false;
					} );
					tplPagesSearch.focus();
				}
			} );
			tplPagesMenu.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
			} );
			tplPagesMenu.addEventListener( 'change', function () {
				tplUpdatePagesLabel();
				markDirty();
			} );
			document.addEventListener( 'click', function () {
				if ( ! tplPagesMenu.hidden ) {
					tplPagesMenu.hidden = true;
					tplPagesToggle.setAttribute( 'aria-expanded', 'false' );
					tplPages.classList.remove( 'is-open' );
				}
			} );
			if ( tplPagesSearch ) {
				tplPagesSearch.addEventListener( 'input', function () {
					var q = tplPagesSearch.value.toLowerCase();
					Array.prototype.forEach.call( tplPagesMenu.querySelectorAll( '.ajs-dd__groupblock' ), function ( b ) {
						var visible = false;
						Array.prototype.forEach.call( b.querySelectorAll( '.ajs-dd__opt' ), function ( o ) {
							var label = o.getAttribute( 'data-label' ) || '';
							var show = label.indexOf( q ) !== -1;
							o.hidden = ! show;
							if ( show ) {
								visible = true;
							}
						} );
						b.hidden = ! visible;
					} );
				} );
			}
			tplUpdatePagesLabel();
		}
		document.getElementById( 'ajs-devices' ).addEventListener( 'click', function ( e ) {
			var b = e.target.closest( 'button' );
			if ( ! b ) {
				return;
			}
			setDevice( b.getAttribute( 'data-device' ) );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ( e.ctrlKey || e.metaKey ) && e.key.toLowerCase() === 's' ) {
				e.preventDefault();
				save();
			} else if ( e.key === 'Escape' && structureEl && ! structureEl.hidden ) {
				setStructure( false );
			}
		} );
		window.addEventListener( 'beforeunload', function ( e ) {
			if ( state.dirty ) {
				e.preventDefault();
				e.returnValue = cfg.i18n.unsaved;
			}
		} );
	}
	function boot() {
		bindChrome();
		renderModules( '' );
		frame.addEventListener( 'load', function () {
			if ( booted ) {
				return;
			}
			booted = true;
			var doc = frameDoc();
			if ( ! doc ) {
				return;
			}
			ensureOverlays( doc );
			applyDeviceClass();
			attachCanvas( doc );
			loadDocument();
		} );
		frame.src = cfg.previewUrl + ( cfg.previewUrl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'ajs_ts=' + Date.now();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )( jQuery );
