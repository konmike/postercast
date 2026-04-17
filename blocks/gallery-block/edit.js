import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, PanelColorSettings } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    RangeControl,
    ToggleControl,
    Placeholder,
    Spinner,
    Button,
    TextControl,
    Modal,
    Icon,
    ExternalLink,
} from '@wordpress/components';
import { pencil, trash, plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';

/* ───── Config from PHP ───── */
const pcastConfig = window.pcastConfig || {};
const isPro = pcastConfig.isPro || false;
const multipleGalleries = pcastConfig.multipleGalleries || false;
const defaultGalleryId = pcastConfig.defaultGalleryId || 0;


export default function Edit( { attributes, setAttributes } ) {
    const { galleryId, limit, showAllLink, posterSize, gap } = attributes;
    const blockProps = useBlockProps();

    // Resolve PRO grid component once (PRO registers via wp.hooks filter).
    const ProPosterGrid = useMemo( () => applyFilters( 'pcast.PosterGridComponent', null ), [] );

    const [ newGalleryName, setNewGalleryName ] = useState( '' );
    const [ isCreatingGallery, setIsCreatingGallery ] = useState( false );
    const [ isAddingPosters, setIsAddingPosters ] = useState( false );
    const [ isDeletingPoster, setIsDeletingPoster ] = useState( null );
    const [ posters, setPosters ] = useState( [] );
    const [ isLoadingPosters, setIsLoadingPosters ] = useState( false );
    const [ editingPoster, setEditingPoster ] = useState( null );
    const [ editForm, setEditForm ] = useState( {} );
    const [ isSavingPoster, setIsSavingPoster ] = useState( false );

    const { invalidateResolution } = useDispatch( 'core/data' );

    // In free version, auto-assign default gallery if not set.
    useEffect( () => {
        if ( ! multipleGalleries && ! galleryId && defaultGalleryId ) {
            setAttributes( { galleryId: defaultGalleryId } );
        }
    }, [ galleryId, setAttributes ] );

    const { galleries, isLoading } = useSelect( ( select ) => {
        if ( ! multipleGalleries ) {
            return { galleries: [], isLoading: false };
        }
        const { getEntityRecords, isResolving } = select( 'core' );
        return {
            galleries: getEntityRecords( 'taxonomy', 'poster_gallery', {
                per_page: -1,
                hide_empty: false,
            } ),
            isLoading: isResolving( 'getEntityRecords', [
                'taxonomy',
                'poster_gallery',
                { per_page: -1, hide_empty: false },
            ] ),
        };
    }, [] );

    const effectiveGalleryId = galleryId || defaultGalleryId;

    const fetchPosters = useCallback( async () => {
        if ( ! effectiveGalleryId ) {
            setPosters( [] );
            return;
        }
        setIsLoadingPosters( true );
        try {
            const result = await apiFetch( {
                path: `/pcast/v1/posters?gallery_id=${ effectiveGalleryId }&include_expired=true`,
            } );
            setPosters( result || [] );
        } catch ( error ) {
            console.error( 'Failed to fetch posters:', error );
            setPosters( [] );
        } finally {
            setIsLoadingPosters( false );
        }
    }, [ effectiveGalleryId ] );

    useEffect( () => {
        fetchPosters();
    }, [ fetchPosters ] );

    const galleryOptions = [
        { label: __( '-- Select a Gallery --', 'postercast' ), value: 0 },
        ...( galleries || [] ).map( ( g ) => ( { label: g.name, value: g.id } ) ),
    ];

    const createGallery = useCallback( async () => {
        if ( ! newGalleryName.trim() ) return;
        setIsCreatingGallery( true );
        try {
            const term = await apiFetch( {
                path: '/wp/v2/poster_gallery',
                method: 'POST',
                data: { name: newGalleryName.trim() },
            } );
            invalidateResolution( 'core', 'getEntityRecords', [
                'taxonomy', 'poster_gallery', { per_page: -1, hide_empty: false },
            ] );
            setAttributes( { galleryId: term.id } );
            setNewGalleryName( '' );
        } catch ( error ) {
            console.error( 'Failed to create gallery:', error );
        } finally {
            setIsCreatingGallery( false );
        }
    }, [ newGalleryName, setAttributes, invalidateResolution ] );

    const onSelectImages = useCallback( async ( images ) => {
        if ( ! effectiveGalleryId || ! images.length ) return;
        setIsAddingPosters( true );
        try {
            await Promise.all( images.map( ( image ) =>
                apiFetch( {
                    path: '/wp/v2/posters',
                    method: 'POST',
                    data: {
                        title: image.title || image.filename || __( 'Poster', 'postercast' ),
                        status: 'publish',
                        featured_media: image.id,
                        poster_gallery: [ effectiveGalleryId ],
                        meta: {
                            _pcast_orientation_mode: 'auto',
                            _pcast_orientation: image.width > image.height ? 'landscape' : 'portrait',
                        },
                    },
                } )
            ) );
            await fetchPosters();
        } catch ( error ) {
            console.error( 'Failed to create posters:', error );
        } finally {
            setIsAddingPosters( false );
        }
    }, [ effectiveGalleryId, fetchPosters ] );

    const deletePoster = useCallback( async ( posterId ) => {
        if ( ! confirm( __( 'Remove this poster from the gallery?', 'postercast' ) ) ) return;
        setIsDeletingPoster( posterId );
        try {
            await apiFetch( { path: `/wp/v2/posters/${ posterId }`, method: 'DELETE' } );
            setPosters( ( prev ) => prev.filter( ( p ) => p.id !== posterId ) );
        } catch ( error ) {
            console.error( 'Failed to delete poster:', error );
        } finally {
            setIsDeletingPoster( null );
        }
    }, [] );

    const openEditModal = useCallback( async ( poster ) => {
        try {
            const full = await apiFetch( { path: `/wp/v2/posters/${ poster.id }` } );
            setEditForm( {
                title: full.title?.rendered || full.title?.raw || poster.title,
                url: full.meta?._pcast_url || '',
                orientation_mode: full.meta?._pcast_orientation_mode || 'auto',
            } );
            setEditingPoster( poster );
        } catch ( error ) {
            console.error( 'Failed to load poster details:', error );
        }
    }, [] );

    const savePoster = useCallback( async () => {
        if ( ! editingPoster ) return;
        setIsSavingPoster( true );
        try {
            await apiFetch( {
                path: `/wp/v2/posters/${ editingPoster.id }`,
                method: 'POST',
                data: {
                    title: editForm.title,
                    meta: {
                        _pcast_url: editForm.url,
                        _pcast_orientation_mode: editForm.orientation_mode,
                    },
                },
            } );
            setEditingPoster( null );
            await fetchPosters();
        } catch ( error ) {
            console.error( 'Failed to save poster:', error );
        } finally {
            setIsSavingPoster( false );
        }
    }, [ editingPoster, editForm, fetchPosters ] );

    const renderPosterGrid = () => {
        if ( isLoadingPosters ) {
            return (
                <div style={ { textAlign: 'center', padding: '40px 0' } }>
                    <Spinner />
                </div>
            );
        }

        if ( posters.length === 0 ) {
            return (
                <Placeholder
                    icon="format-gallery"
                    label={ __( 'No posters yet', 'postercast' ) }
                    instructions={ __( 'Upload images to create posters.', 'postercast' ) }
                >
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={ onSelectImages }
                            allowedTypes={ [ 'image' ] }
                            multiple
                            render={ ( { open } ) => (
                                <Button variant="primary" onClick={ open } disabled={ isAddingPosters } isBusy={ isAddingPosters }>
                                    <Icon icon={ plus } style={ { marginRight: '4px' } } />
                                    { __( 'Add Posters', 'postercast' ) }
                                </Button>
                            ) }
                        />
                    </MediaUploadCheck>
                </Placeholder>
            );
        }

        return (
            <div style={ { width: '100%' } }>
                <div style={ {
                    display: 'grid',
                    gridTemplateColumns: 'repeat(2, 1fr)',
                    gap: `${ gap }px`,
                    gridAutoFlow: 'dense',
                } }>
                    { posters.map( ( poster ) => (
                        <div
                            key={ poster.id }
                            style={ {
                                position: 'relative',
                                borderRadius: '8px',
                                overflow: 'hidden',
                                background: '#f0f0f0',
                                width: '100%',
                            } }
                        >
                            <div
                                style={ { cursor: 'pointer' } }
                                onClick={ () => openEditModal( poster ) }
                                role="button"
                                tabIndex={ 0 }
                                onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) openEditModal( poster ); } }
                            >
                                { ( poster.image_thumb || poster.image_full ) && (
                                    <img
                                        src={ poster.image_thumb || poster.image_full }
                                        alt={ poster.title }
                                        style={ {
                                            width: '100%',
                                            height: '100%',
                                            objectFit: 'cover',
                                            display: 'block',
                                        } }
                                    />
                                ) }

                                <div style={ {
                                    position: 'absolute',
                                    top: 0,
                                    left: 0,
                                    right: 0,
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'flex-start',
                                    padding: '6px',
                                    background: 'linear-gradient(to bottom, rgba(0,0,0,0.6), transparent)',
                                } }>
                                    <span style={ {
                                        color: '#fff',
                                        fontSize: '12px',
                                        fontWeight: 600,
                                        textShadow: '0 1px 2px rgba(0,0,0,0.5)',
                                        padding: '2px 6px',
                                        maxWidth: '60%',
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                        whiteSpace: 'nowrap',
                                    } }>
                                        { poster.title }
                                    </span>
                                    <div style={ { display: 'flex', gap: '4px' } }>
                                        <Button
                                            onClick={ ( e ) => { e.stopPropagation(); openEditModal( poster ); } }
                                            style={ btnStyle }
                                            label={ __( 'Edit poster', 'postercast' ) }
                                        >
                                            <Icon icon={ pencil } style={ { color: '#555' } } />
                                        </Button>
                                        <Button
                                            onClick={ ( e ) => { e.stopPropagation(); deletePoster( poster.id ); } }
                                            disabled={ isDeletingPoster === poster.id }
                                            isBusy={ isDeletingPoster === poster.id }
                                            isDestructive
                                            style={ btnStyle }
                                            label={ __( 'Remove poster', 'postercast' ) }
                                        >
                                            <Icon icon={ trash } />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) ) }
                </div>

                <div style={ { display: 'flex', justifyContent: 'center', marginTop: '16px' } }>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={ onSelectImages }
                            allowedTypes={ [ 'image' ] }
                            multiple
                            render={ ( { open } ) => (
                                <Button variant="secondary" onClick={ open } disabled={ isAddingPosters } isBusy={ isAddingPosters }>
                                    <Icon icon={ plus } style={ { marginRight: '4px' } } />
                                    { isAddingPosters ? __( 'Adding…', 'postercast' ) : __( 'Add Posters', 'postercast' ) }
                                </Button>
                            ) }
                        />
                    </MediaUploadCheck>
                </div>

                { isAddingPosters && (
                    <div style={ { textAlign: 'center', padding: '8px', opacity: 0.7 } }>
                        <Spinner />
                    </div>
                ) }
            </div>
        );
    };

    const renderEditModal = () => {
        if ( ! editingPoster ) return null;

        return (
            <Modal
                title={ __( 'Edit Poster', 'postercast' ) }
                onRequestClose={ () => setEditingPoster( null ) }
                style={ { maxWidth: '500px', width: '100%' } }
            >
                <div style={ { display: 'flex', flexDirection: 'column', gap: '16px' } }>
                    { ( editingPoster.image_thumb || editingPoster.image_full ) && (
                        <div style={ { textAlign: 'center' } }>
                            <img
                                src={ editingPoster.image_thumb || editingPoster.image_full }
                                alt={ editForm.title }
                                style={ { maxHeight: '200px', borderRadius: '6px', objectFit: 'contain' } }
                            />
                        </div>
                    ) }

                    <TextControl
                        label={ __( 'Title', 'postercast' ) }
                        value={ editForm.title || '' }
                        onChange={ ( v ) => setEditForm( { ...editForm, title: v } ) }
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />

                    <TextControl
                        label={ __( 'URL', 'postercast' ) }
                        type="url"
                        value={ editForm.url || '' }
                        onChange={ ( v ) => setEditForm( { ...editForm, url: v } ) }
                        placeholder="https://"
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />

                    <SelectControl
                        label={ __( 'Orientation', 'postercast' ) }
                        value={ editForm.orientation_mode || 'auto' }
                        options={ [
                            { label: __( 'Auto (detect from image)', 'postercast' ), value: 'auto' },
                            { label: __( 'Portrait', 'postercast' ), value: 'portrait' },
                            { label: __( 'Landscape', 'postercast' ), value: 'landscape' },
                        ] }
                        onChange={ ( v ) => setEditForm( { ...editForm, orientation_mode: v } ) }
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />

                    <div style={ { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '8px' } }>
                        <Button variant="tertiary" onClick={ () => setEditingPoster( null ) }>
                            { __( 'Cancel', 'postercast' ) }
                        </Button>
                        <Button variant="primary" onClick={ savePoster } disabled={ isSavingPoster } isBusy={ isSavingPoster }>
                            { isSavingPoster ? __( 'Saving…', 'postercast' ) : __( 'Save', 'postercast' ) }
                        </Button>
                    </div>
                </div>
            </Modal>
        );
    };

    return (
        <div { ...blockProps }>
            <InspectorControls>
                { multipleGalleries && (
                    <PanelBody title={ __( 'Gallery', 'postercast' ) } initialOpen>
                        <SelectControl
                            label={ __( 'Gallery', 'postercast' ) }
                            value={ galleryId }
                            options={ galleryOptions }
                            onChange={ ( v ) => setAttributes( { galleryId: parseInt( v, 10 ) } ) }
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                        />
                        <div style={ { marginBottom: '16px' } }>
                            <TextControl
                                label={ __( 'Or create new gallery', 'postercast' ) }
                                value={ newGalleryName }
                                onChange={ setNewGalleryName }
                                placeholder={ __( 'Gallery name…', 'postercast' ) }
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                            />
                            <Button
                                variant="secondary"
                                onClick={ createGallery }
                                disabled={ ! newGalleryName.trim() || isCreatingGallery }
                                isBusy={ isCreatingGallery }
                                style={ { width: '100%', justifyContent: 'center' } }
                            >
                                { isCreatingGallery ? __( 'Creating…', 'postercast' ) : __( 'Create Gallery', 'postercast' ) }
                            </Button>
                        </div>
                    </PanelBody>
                ) }

                <PanelBody title={ __( 'Gallery Settings', 'postercast' ) } initialOpen>
                    <RangeControl
                        label={ __( 'Posters to display', 'postercast' ) }
                        value={ limit }
                        onChange={ ( v ) => setAttributes( { limit: v } ) }
                        min={ 1 }
                        max={ 50 }
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={ __( 'Show "Show all" link', 'postercast' ) }
                        checked={ showAllLink }
                        onChange={ ( v ) => setAttributes( { showAllLink: v } ) }
                        __nextHasNoMarginBottom
                    />
                    <SelectControl
                        label={ __( 'Poster Size', 'postercast' ) }
                        value={ posterSize }
                        options={ [
                            { label: __( 'Medium Large', 'postercast' ), value: 'medium_large' },
                            { label: __( 'Large', 'postercast' ), value: 'large' },
                            { label: __( 'Full', 'postercast' ), value: 'full' },
                        ] }
                        onChange={ ( v ) => setAttributes( { posterSize: v } ) }
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />
                    <RangeControl
                        label={ __( 'Gap (px)', 'postercast' ) }
                        value={ gap }
                        onChange={ ( v ) => setAttributes( { gap: v } ) }
                        min={ 0 }
                        max={ 60 }
                        step={ 2 }
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />
                </PanelBody>

                { ! isPro && (
                    <PanelBody title={ __( 'More Options', 'postercast' ) } initialOpen={ false }>
                        <p style={ { fontSize: '13px', color: '#757575', margin: 0 } }>
                            { __( 'Unlock advanced features: multiple galleries, custom columns, drag & drop reordering, date scheduling, poster alignment, shadow & color customization, and more.', 'postercast' ) }
                        </p>
                        <p style={ { marginTop: '12px', marginBottom: 0 } }>
                            <ExternalLink href="https://michalkoneczny.gumroad.com/l/poster-gallery">
                                { __( 'Get Poster Gallery PRO', 'postercast' ) }
                            </ExternalLink>
                        </p>
                    </PanelBody>
                ) }
            </InspectorControls>

            { isLoading && (
                <Placeholder icon="format-gallery" label={ __( 'Poster Gallery', 'postercast' ) }>
                    <Spinner />
                </Placeholder>
            ) }

            { ! isLoading && ! effectiveGalleryId && ! multipleGalleries && (
                <Placeholder
                    icon="format-gallery"
                    label={ __( 'Poster Gallery', 'postercast' ) }
                    instructions={ __( 'Create your gallery to get started.', 'postercast' ) }
                >
                    <div style={ { display: 'flex', gap: '8px', width: '100%', maxWidth: '360px' } }>
                        <TextControl
                            placeholder={ __( 'Gallery name…', 'postercast' ) }
                            value={ newGalleryName }
                            onChange={ setNewGalleryName }
                            style={ { flex: 1 } }
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                        />
                        <Button
                            variant="primary"
                            onClick={ createGallery }
                            disabled={ ! newGalleryName.trim() || isCreatingGallery }
                            isBusy={ isCreatingGallery }
                        >
                            { __( 'Create', 'postercast' ) }
                        </Button>
                    </div>
                </Placeholder>
            ) }

            { ! isLoading && ! galleryId && multipleGalleries && (
                <Placeholder
                    icon="format-gallery"
                    label={ __( 'Poster Gallery', 'postercast' ) }
                    instructions={ __( 'Select an existing gallery or create a new one.', 'postercast' ) }
                >
                    <div style={ { display: 'flex', flexDirection: 'column', gap: '12px', width: '100%', maxWidth: '360px' } }>
                        { galleries && galleries.length > 0 && (
                            <SelectControl
                                value={ galleryId }
                                options={ galleryOptions }
                                onChange={ ( v ) => setAttributes( { galleryId: parseInt( v, 10 ) } ) }
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                            />
                        ) }
                        <div style={ { display: 'flex', gap: '8px' } }>
                            <TextControl
                                placeholder={ __( 'New gallery name…', 'postercast' ) }
                                value={ newGalleryName }
                                onChange={ setNewGalleryName }
                                style={ { flex: 1 } }
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                            />
                            <Button
                                variant="primary"
                                onClick={ createGallery }
                                disabled={ ! newGalleryName.trim() || isCreatingGallery }
                                isBusy={ isCreatingGallery }
                            >
                                { __( 'Create', 'postercast' ) }
                            </Button>
                        </div>
                    </div>
                </Placeholder>
            ) }

            { ! isLoading && effectiveGalleryId > 0 && ProPosterGrid && (
                <ProPosterGrid
                    posters={ posters }
                    setPosters={ setPosters }
                    attributes={ attributes }
                    openEditModal={ openEditModal }
                    deletePoster={ deletePoster }
                    isDeletingPoster={ isDeletingPoster }
                    isAddingPosters={ isAddingPosters }
                    isLoadingPosters={ isLoadingPosters }
                    onSelectImages={ onSelectImages }
                    fetchPosters={ fetchPosters }
                    effectiveGalleryId={ effectiveGalleryId }
                />
            ) }

            { ! isLoading && effectiveGalleryId > 0 && ! ProPosterGrid && renderPosterGrid() }

            { renderEditModal() }
        </div>
    );
}

const btnStyle = {
    background: 'rgba(255,255,255,0.9)',
    borderRadius: '50%',
    width: '28px',
    height: '28px',
    minWidth: '28px',
    padding: 0,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
};
