/**
 * Poster Gallery — Poster Link inline format.
 *
 * Adds a RichText toolbar button that lets the user select text and link it
 * to a poster so it opens the lightbox on the frontend.
 */
import { registerFormatType, applyFormat, removeFormat, useAnchor } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { Popover, SearchControl, Spinner } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { image as imageIcon } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

import './editor.css';

const FORMAT_NAME = 'pcast/poster-link';

const FORMAT_SETTINGS = {
	title: __( 'Poster lightbox link', 'postercast' ),
	tagName: 'a',
	className: 'pcast-poster-link',
	attributes: {
		'data-pcast-open': 'data-pcast-open',
		href: 'href',
	},
};

/* Inline styles — Popover renders in a portal outside the editor iframe,
   so external CSS files bundled with the block do not apply to it. */
const STYLES = {
	inner: {
		width: 320,
		maxHeight: 360,
		display: 'flex',
		flexDirection: 'column',
		padding: 8,
		boxSizing: 'border-box',
	},
	status: {
		padding: 16,
		textAlign: 'center',
		color: '#757575',
	},
	list: {
		listStyle: 'none',
		margin: 0,
		padding: 0,
		overflowY: 'auto',
		maxHeight: 280,
	},
	item: {
		display: 'flex',
		flexDirection: 'row',
		alignItems: 'center',
		gap: 10,
		width: '100%',
		padding: '6px 8px',
		margin: 0,
		border: 'none',
		borderRadius: 4,
		background: 'none',
		cursor: 'pointer',
		textAlign: 'left',
		fontSize: 13,
		lineHeight: 1.4,
		boxSizing: 'border-box',
	},
	thumb: {
		flex: '0 0 36px',
		width: 36,
		height: 36,
		objectFit: 'cover',
		borderRadius: 4,
		background: '#e0e0e0',
	},
	title: {
		flex: 1,
		minWidth: 0,
		overflow: 'hidden',
		textOverflow: 'ellipsis',
		whiteSpace: 'nowrap',
	},
};

function PosterLinkButton( { isActive, value, onChange, contentRef } ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ posters, setPosters ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ search, setSearch ] = useState( '' );

	const popoverAnchor = useAnchor( {
		editableContentElement: contentRef.current,
		settings: FORMAT_SETTINGS,
	} );

	const fetchPosters = useCallback( async () => {
		setLoading( true );
		try {
			const results = await apiFetch( {
				path: '/wp/v2/posters?per_page=100&_fields=id,title,featured_media,_links&_embed=wp:featuredmedia',
			} );
			setPosters( results.map( ( p ) => ( {
				id: p.id,
				title: p.title?.rendered || p.title?.raw || `#${ p.id }`,
				thumbnail: p._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.media_details?.sizes?.thumbnail?.source_url
					|| p._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url
					|| '',
			} ) ) );
		} catch {
			setPosters( [] );
		} finally {
			setLoading( false );
		}
	}, [] );

	const handleToggle = () => {
		if ( isActive ) {
			onChange( removeFormat( value, FORMAT_NAME ) );
			return;
		}
		setSearch( '' );
		setIsOpen( true );
		fetchPosters();
	};

	const handleSelect = ( poster ) => {
		onChange(
			applyFormat( value, {
				type: FORMAT_NAME,
				attributes: {
					'data-pcast-open': String( poster.id ),
					href: '#',
				},
			} )
		);
		setIsOpen( false );
	};

	const filtered = posters.filter( ( p ) =>
		p.title.toLowerCase().includes( search.toLowerCase() )
	);

	return (
		<>
			<RichTextToolbarButton
				icon={ imageIcon }
				title={ __( 'Poster lightbox link', 'postercast' ) }
				isActive={ isActive }
				onClick={ handleToggle }
			/>
			{ isOpen && (
				<Popover
					anchor={ popoverAnchor }
					placement="bottom-start"
					onClose={ () => setIsOpen( false ) }
					focusOnMount="firstElement"
					className="pcast-poster-link-popover"
				>
					<div style={ STYLES.inner }>
						<SearchControl
							value={ search }
							onChange={ setSearch }
							placeholder={ __( 'Search posters…', 'postercast' ) }
							__nextHasNoMarginBottom={ true }
						/>
						{ loading && (
							<div style={ STYLES.status }>
								<Spinner />
							</div>
						) }
						{ ! loading && filtered.length === 0 && (
							<div style={ STYLES.status }>
								{ __( 'No posters found.', 'postercast' ) }
							</div>
						) }
						{ ! loading && filtered.length > 0 && (
							<ul style={ STYLES.list }>
								{ filtered.map( ( poster ) => (
									<li key={ poster.id }>
										<button
											type="button"
											style={ STYLES.item }
											onClick={ () => handleSelect( poster ) }
											onMouseEnter={ ( e ) => { e.currentTarget.style.background = '#f0f0f0'; } }
											onMouseLeave={ ( e ) => { e.currentTarget.style.background = 'none'; } }
										>
											{ poster.thumbnail ? (
												<img
													src={ poster.thumbnail }
													alt=""
													style={ STYLES.thumb }
												/>
											) : (
												<span style={ STYLES.thumb } />
											) }
											<span
												style={ STYLES.title }
												dangerouslySetInnerHTML={ { __html: poster.title } }
											/>
										</button>
									</li>
								) ) }
							</ul>
						) }
					</div>
				</Popover>
			) }
		</>
	);
}

registerFormatType( FORMAT_NAME, {
	...FORMAT_SETTINGS,
	edit: PosterLinkButton,
} );
