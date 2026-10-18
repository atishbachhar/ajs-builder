/*!
 * AJS Builder — front-end behaviour.
 * Accordion, tabs, counters, progress and lightbox. Exposes
 * window.AjsBuilderFrontend.init(scope) so the editor can re-init after a render.
 */
( function () {
	'use strict';

	function ready( fn ) {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function initAccordion( scope ) {
		scope.querySelectorAll( '[data-accordion]' ).forEach( function ( acc ) {
			if ( acc.__ajsInit ) {
				return;
			}
			acc.__ajsInit = true;
			acc.querySelectorAll( '.ajs-acc__head' ).forEach( function ( head ) {
				head.addEventListener( 'click', function ( e ) {
					if ( document.body.classList.contains( 'ajs-builder-editing' ) ) {
						e.preventDefault();
						return;
					}
					var item = head.closest( '.ajs-acc__item' );
					if ( item ) {
						item.classList.toggle( 'is-open' );
					}
				} );
			} );
		} );
	}

	function initTabs( scope ) {
		scope.querySelectorAll( '[data-tabs]' ).forEach( function ( tabs ) {
			if ( tabs.__ajsInit ) {
				return;
			}
			tabs.__ajsInit = true;
			var nav = tabs.querySelector( '.ajs-tabs__nav' );
			var panes = tabs.querySelector( '.ajs-tabs__panes' );
			if ( ! nav || ! panes ) {
				return;
			}
			nav.querySelectorAll( '.ajs-tabs__tab' ).forEach( function ( tab ) {
				tab.addEventListener( 'click', function ( e ) {
					if ( document.body.classList.contains( 'ajs-builder-editing' ) ) {
						e.preventDefault();
						return;
					}
					var i = tab.getAttribute( 'data-tab' );
					nav.querySelectorAll( '.ajs-tabs__tab' ).forEach( function ( t ) {
						t.classList.toggle( 'is-active', t === tab );
					} );
					panes.querySelectorAll( '.ajs-tabs__pane' ).forEach( function ( p ) {
						p.classList.toggle( 'is-active', p.getAttribute( 'data-pane' ) === i );
					} );
				} );
			} );
		} );
	}

	function runCounter( el ) {
		if ( el.__ajsDone ) {
			return;
		}
		el.__ajsDone = true;
		var start = parseFloat( el.getAttribute( 'data-start' ) ) || 0;
		var end = parseFloat( el.getAttribute( 'data-end' ) ) || 0;
		var pre = el.getAttribute( 'data-prefix' ) || '';
		var suf = el.getAttribute( 'data-suffix' ) || '';
		var dur = parseInt( el.getAttribute( 'data-duration' ), 10 ) || 1100;
		var t0 = null;
		function step( ts ) {
			if ( ! t0 ) {
				t0 = ts;
			}
			var p = Math.min( 1, ( ts - t0 ) / dur );
			var eased = 1 - Math.pow( 1 - p, 3 );
			var v = Math.round( start + ( end - start ) * eased );
			el.textContent = pre + v.toLocaleString() + suf;
			if ( p < 1 ) {
				window.requestAnimationFrame( step );
			}
		}
		window.requestAnimationFrame( step );
	}

	function initCounters( scope ) {
		var els = scope.querySelectorAll( '[data-counter]:not([data-ajs-observed])' );
		if ( ! els.length ) {
			return;
		}
		if ( 'IntersectionObserver' in window ) {
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						runCounter( entry.target );
						io.unobserve( entry.target );
					}
				} );
			}, { threshold: 0.25 } );
			els.forEach( function ( el ) {
				el.setAttribute( 'data-ajs-observed', '1' );
				io.observe( el );
			} );
		} else {
			els.forEach( runCounter );
		}
	}

	function initLightbox( scope ) {
		if ( ! document.getElementById( 'ajs-lightbox-style' ) ) {
			var style = document.createElement( 'style' );
			style.id = 'ajs-lightbox-style';
			style.textContent = '.ajs-lightbox{position:fixed;inset:0;z-index:100001;background:rgba(8,10,14,.9);display:flex;align-items:center;justify-content:center;padding:4vw;cursor:zoom-out}.ajs-lightbox img{max-width:100%;max-height:92vh;border-radius:8px;box-shadow:0 30px 60px rgba(0,0,0,.5)}';
			document.head.appendChild( style );
		}
		scope.querySelectorAll( '[data-lightbox]' ).forEach( function ( a ) {
			if ( a.__ajsInit ) {
				return;
			}
			a.__ajsInit = true;
			a.addEventListener( 'click', function ( e ) {
				if ( editing() ) {
					e.preventDefault();
					return;
				}
				var src = a.getAttribute( 'href' );
				if ( ! src ) {
					return;
				}
				e.preventDefault();
				var ov = document.createElement( 'div' );
				ov.className = 'ajs-lightbox';
				ov.innerHTML = '<img alt="">';
				ov.querySelector( 'img' ).src = src;
				ov.addEventListener( 'click', function () {
					ov.remove();
				} );
				document.body.appendChild( ov );
			} );
		} );
	}

	function editing() {
		return document.body.classList.contains( 'ajs-builder-editing' );
	}

	/**
	 * Mount a carousel/slider. Supports slide + fade transitions, autoplay,
	 * looping, pause-on-hover, arrows and dots, and any number of slides per
	 * view (the step is measured from the DOM so multi-column layouts work).
	 *
	 * @param {Element} root Slider root element.
	 * @param {Object}  sel  Selectors: track, slide, dot, prev, next, dotAttr.
	 */
	function mountCarousel( root, sel ) {
		if ( root.__ajsInit ) {
			return;
		}
		root.__ajsInit = true;
		var track  = root.querySelector( sel.track );
		var slides = Array.prototype.slice.call( root.querySelectorAll( sel.slide ) );
		if ( slides.length < 2 ) {
			return;
		}
		var dots  = Array.prototype.slice.call( root.querySelectorAll( sel.dot ) );
		var speed = parseInt( root.getAttribute( 'data-speed' ), 10 ) || 4000;
		var autoplay = root.getAttribute( 'data-autoplay' ) === '1';
		var loop  = root.getAttribute( 'data-loop' ) !== '0';
		var pause = root.getAttribute( 'data-pause' ) !== '0';
		var transition = root.getAttribute( 'data-transition' ) || 'slide';
		var index = 0;
		var timer = null;

		function step() {
			if ( slides.length < 2 ) {
				return slides.length ? slides[ 0 ].offsetWidth : 0;
			}
			return Math.max( 1, slides[ 1 ].offsetLeft - slides[ 0 ].offsetLeft );
		}
		function perView() {
			if ( ! track ) {
				return 1;
			}
			return Math.max( 1, Math.round( track.clientWidth / step() ) );
		}
		function maxIndex() {
			return Math.max( 0, slides.length - perView() );
		}
		function apply() {
			slides.forEach( function ( s, n ) {
				s.classList.toggle( 'is-active', n === index );
			} );
			dots.forEach( function ( d, n ) {
				d.classList.toggle( 'is-active', n === index );
			} );
			if ( track ) {
				if ( transition === 'fade' ) {
					track.style.transform = '';
				} else {
					track.style.transform = 'translateX(' + ( -index * step() ) + 'px)';
				}
			}
		}
		function go( i ) {
			var last = maxIndex();
			if ( loop ) {
				index = ( ( i % slides.length ) + slides.length ) % slides.length;
				if ( index > last ) {
					index = 0;
				}
			} else {
				index = Math.max( 0, Math.min( last, i ) );
			}
			apply();
		}
		function next() {
			if ( ! loop && index >= maxIndex() ) {
				stop();
				return;
			}
			go( index + 1 );
		}
		function prev() {
			go( index - 1 );
		}
		function stop() {
			if ( timer ) {
				clearInterval( timer );
				timer = null;
			}
		}
		function start() {
			stop();
			if ( ! autoplay ) {
				return;
			}
			timer = setInterval( function () {
				if ( ! root.isConnected ) {
					stop();
					return;
				}
				next();
			}, speed );
		}

		var prevBtn = root.querySelector( sel.prev );
		var nextBtn = root.querySelector( sel.next );
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function ( e ) {
				if ( editing() ) {
					return;
				}
				e.preventDefault();
				prev();
				start();
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function ( e ) {
				if ( editing() ) {
					return;
				}
				e.preventDefault();
				next();
				start();
			} );
		}
		dots.forEach( function ( d ) {
			d.addEventListener( 'click', function ( e ) {
				if ( editing() ) {
					return;
				}
				e.preventDefault();
				var i = parseInt( d.getAttribute( sel.dotAttr ) || d.getAttribute( 'data-slide' ) || d.getAttribute( 'data-index' ), 10 ) || 0;
				go( i );
				start();
			} );
		} );
		if ( pause ) {
			root.addEventListener( 'mouseenter', stop );
			root.addEventListener( 'mouseleave', start );
		}
		if ( track ) {
			root.__ajsSliderApply = apply;
		}
		apply();
		start();
	}

	function initSlider( scope ) {
		scope.querySelectorAll( '[data-slider]' ).forEach( function ( slider ) {
			mountCarousel( slider, {
				track: '.ajs-w-slider__slides',
				slide: '.ajs-w-slider__slide',
				dot: '.ajs-w-slider__dot',
				prev: '.ajs-w-slider__nav--prev',
				next: '.ajs-w-slider__nav--next',
				dotAttr: 'data-slide'
			} );
		} );
	}

	function initTestimonialSlider( scope ) {
		scope.querySelectorAll( '[data-testimonial-slider]' ).forEach( function ( el ) {
			mountCarousel( el, {
				track: '.ajs-testimonial__track',
				slide: '.ajs-testimonial__slide',
				dot: '.ajs-testimonial__dot',
				prev: '.ajs-testimonial__nav--prev',
				next: '.ajs-testimonial__nav--next',
				dotAttr: 'data-index'
			} );
		} );
	}

	function initFeaturedCategorySlider( scope ) {
		scope.querySelectorAll( '[data-fcat-slider]' ).forEach( function ( el ) {
			mountCarousel( el, {
				track: '.ajs-fcat__track',
				slide: '.ajs-fcat__slide',
				dot: '.ajs-fcat__dot',
				prev: '.ajs-fcat__nav--prev',
				next: '.ajs-fcat__nav--next',
				dotAttr: 'data-index'
			} );
		} );
	}

	function initProductSlider( scope ) {
		scope.querySelectorAll( '[data-pslider]' ).forEach( function ( el ) {
			if ( el.__ajsInit ) {
				return;
			}
			el.__ajsInit = true;
			var track = el.querySelector( '.ajs-w-pslider__track ul.products' );
			if ( ! track ) {
				return;
			}
			var speed = parseInt( el.getAttribute( 'data-speed' ), 10 ) || 4000;
			var autoplay = el.getAttribute( 'data-autoplay' ) === '1';
			var timer = null;

			function step( dir ) {
				var item = track.querySelector( 'li.product' );
				var gap = 20;
				var width = item ? ( item.getBoundingClientRect().width + gap ) : track.clientWidth;
				var maxScroll = track.scrollWidth - track.clientWidth - 2;
				if ( dir > 0 && track.scrollLeft >= maxScroll ) {
					track.scrollTo( { left: 0, behavior: 'smooth' } );
					return;
				}
				if ( dir < 0 && track.scrollLeft <= 2 ) {
					track.scrollTo( { left: track.scrollWidth, behavior: 'smooth' } );
					return;
				}
				track.scrollBy( { left: dir * width, behavior: 'smooth' } );
			}
			function stop() {
				if ( timer ) {
					clearInterval( timer );
					timer = null;
				}
			}
			function start() {
				stop();
				if ( ! autoplay ) {
					return;
				}
				timer = setInterval( function () {
					if ( ! el.isConnected ) {
						stop();
						return;
					}
					step( 1 );
				}, speed );
			}

			var prevBtn = el.querySelector( '.ajs-pslider__nav--prev' );
			var nextBtn = el.querySelector( '.ajs-pslider__nav--next' );
			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', function ( e ) {
					if ( editing() ) {
						return;
					}
					e.preventDefault();
					step( -1 );
					start();
				} );
			}
			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', function ( e ) {
					if ( editing() ) {
						return;
					}
					e.preventDefault();
					step( 1 );
					start();
				} );
			}
			el.addEventListener( 'mouseenter', stop );
			el.addEventListener( 'mouseleave', start );
			start();
		} );
	}

	function initLoop( scope ) {
		scope.querySelectorAll( '[data-loop]' ).forEach( function ( el ) {
			if ( el.__ajsInit ) {
				return;
			}
			el.__ajsInit = true;
			var track = el.querySelector( '.ajs-loop__track' );
			if ( ! track ) {
				return;
			}
			var items = Array.prototype.slice.call( track.querySelectorAll( '.ajs-loop__item' ) );
			if ( items.length < 2 ) {
				return;
			}
			var dots = Array.prototype.slice.call( el.querySelectorAll( '.ajs-loop__dot' ) );
			var speed = parseInt( el.getAttribute( 'data-speed' ), 10 ) || 4000;
			var autoplay = el.getAttribute( 'data-autoplay' ) === '1';
			var loop = el.getAttribute( 'data-loop-scroll' ) !== '0';
			var pause = el.getAttribute( 'data-pause' ) !== '0';
			var timer = null;

			function gap() {
				var g = parseFloat( window.getComputedStyle( track ).columnGap );
				return isNaN( g ) ? 0 : g;
			}
			function step( dir ) {
				var first = items[ 0 ];
				var width = first ? ( first.getBoundingClientRect().width + gap() ) : track.clientWidth;
				var maxScroll = track.scrollWidth - track.clientWidth - 2;
				if ( dir > 0 && track.scrollLeft >= maxScroll ) {
					track.scrollTo( { left: loop ? 0 : track.scrollLeft, behavior: 'smooth' } );
					return;
				}
				if ( dir < 0 && track.scrollLeft <= 2 ) {
					track.scrollTo( { left: loop ? track.scrollWidth : 0, behavior: 'smooth' } );
					return;
				}
				track.scrollBy( { left: dir * width, behavior: 'smooth' } );
			}
			function goTo( i ) {
				var first = items[ i ];
				if ( ! first ) {
					return;
				}
				var left = track.scrollLeft + first.getBoundingClientRect().left - track.getBoundingClientRect().left;
				track.scrollTo( { left: Math.max( 0, left ), behavior: 'smooth' } );
			}
			function updateDots() {
				if ( ! dots.length ) {
					return;
				}
				var w = items[ 0 ].getBoundingClientRect().width + gap();
				var idx = w ? Math.round( track.scrollLeft / w ) : 0;
				idx = Math.max( 0, Math.min( items.length - 1, idx ) );
				dots.forEach( function ( d, n ) {
					d.classList.toggle( 'is-active', n === idx );
				} );
			}
			function stop() {
				if ( timer ) {
					clearInterval( timer );
					timer = null;
				}
			}
			function start() {
				stop();
				if ( ! autoplay || editing() ) {
					return;
				}
				timer = setInterval( function () {
					if ( ! el.isConnected ) {
						stop();
						return;
					}
					step( 1 );
				}, speed );
			}

			var prevBtn = el.querySelector( '.ajs-loop__nav--prev' );
			var nextBtn = el.querySelector( '.ajs-loop__nav--next' );
			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', function ( e ) {
					if ( editing() ) {
						return;
					}
					e.preventDefault();
					step( -1 );
					start();
				} );
			}
			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', function ( e ) {
					if ( editing() ) {
						return;
					}
					e.preventDefault();
					step( 1 );
					start();
				} );
			}
			dots.forEach( function ( d ) {
				d.addEventListener( 'click', function ( e ) {
					if ( editing() ) {
						return;
					}
					e.preventDefault();
					goTo( parseInt( d.getAttribute( 'data-index' ), 10 ) || 0 );
					start();
				} );
			} );
			track.addEventListener( 'scroll', updateDots, { passive: true } );
			el.addEventListener( 'mouseenter', function () {
				if ( pause ) {
					stop();
				}
			} );
			el.addEventListener( 'mouseleave', function () {
				if ( pause ) {
					start();
				}
			} );
			updateDots();
			start();
		} );
	}

	function initStickyHeader( scope ) {
		var header = scope.querySelector ? scope.querySelector( '.ajs-custom-header[data-ajs-sticky="1"]' ) : null;
		if ( ! header || header.__ajsSticky ) {
			return;
		}
		header.__ajsSticky = true;

		var adminBar = document.getElementById( 'wpadminbar' );
		var offset = adminBar ? adminBar.offsetHeight : 0;

		function update() {
			if ( document.body.classList.contains( 'ajs-builder-editing' ) ) {
				return;
			}
			var stuck = window.pageYOffset > 0 && header.getBoundingClientRect().top <= offset + 0.5;
			header.classList.toggle( 'is-stuck', stuck );
		}

		window.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', function () {
			offset = adminBar ? adminBar.offsetHeight : 0;
			update();
		} );
		update();
	}

	function initHeaderActions( scope ) {
		scope.querySelectorAll( '[data-ajs-search-toggle]' ).forEach( function ( btn ) {
			if ( btn.__ajsInit ) {
				return;
			}
			btn.__ajsInit = true;
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var panel = btn.parentNode ? btn.parentNode.querySelector( '.ajs-w-search__panel' ) : null;
				if ( ! panel ) {
					return;
				}
				var open = panel.classList.toggle( 'is-open' );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				var input = panel.querySelector( 'input' );
				if ( open && input ) {
					input.focus();
				}
			} );
		} );

		scope.querySelectorAll( '.ajs-w-nav__toggle' ).forEach( function ( btn ) {
			if ( btn.__ajsInit ) {
				return;
			}
			btn.__ajsInit = true;
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var nav = btn.closest( '.ajs-w-nav' );
				if ( ! nav ) {
					return;
				}
				var open = nav.classList.toggle( 'is-open' );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		} );
	}

	function init( scope ) {
		scope = scope || document;
		initAccordion( scope );
		initTabs( scope );
		initCounters( scope );
		initLightbox( scope );
		initSlider( scope );
		initTestimonialSlider( scope );
		initFeaturedCategorySlider( scope );
		initProductSlider( scope );
		initLoop( scope );
		initStickyHeader( scope );
		initHeaderActions( scope );
	}

	window.AjsBuilderFrontend = { init: init };

	function reflowSliders() {
		document.querySelectorAll( '[data-slider], [data-testimonial-slider], [data-fcat-slider]' ).forEach( function ( r ) {
			if ( typeof r.__ajsSliderApply === 'function' ) {
				r.__ajsSliderApply();
			}
		} );
	}
	var reflowTimer = null;
	window.addEventListener( 'resize', function () {
		clearTimeout( reflowTimer );
		reflowTimer = setTimeout( reflowSliders, 150 );
	} );

	ready( function () {
		init( document );
	} );
} )();
