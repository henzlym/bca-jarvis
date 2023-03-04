import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { DOWN, ENTER } from '@wordpress/keycodes';
import { Button, TextControl, Icon } from '@wordpress/components';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { JarvisIcons } from '../icons/voice';
import { store as editor } from '@wordpress/editor';
import { addQueryArgs } from '@wordpress/url';
import {
	createBlock,
	getBlockContent,
	pasteHandler,
	rawHandler,
	registerBlockType,
	serialize,
    parse
} from '@wordpress/blocks';
import '../editor.scss'
import Keywords from './keywords';
import Titles from './titles';
import Descriptions from './description';
import Permalink from './permalink';

const rewriteText = ( content, cb ) => {
    fetch("/wp-json/jarvis/v1/write", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            'X-WP-Nonce': jarvisSettings.nonce
        },
        body: JSON.stringify({ content: content })
    })
    .then( res => res.json() )
    .then( data => {
        cb( data );
    })
    .catch((error) => {
      console.error('Error fetching data:', error);
      // Handle the error here, e.g. display a user-friendly error message
    });
}
const getSEOAudit = ( data, cb ) => {
        
    fetch("/wp-json/jarvis/v1/seo", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            'X-WP-Nonce': jarvisSettings.nonce
        },
        body: JSON.stringify(data)
    })
    .then( res => res.json() )
    .then( data => {
        cb( data );
    })
    .catch((error) => {
      console.error('Error fetching data:', error);
      // Handle the error here, e.g. display a user-friendly error message
    });
}
const convertToBlocks = ( content ) => {
    let blocks = pasteHandler({ HTML: content });
    return blocks
}
const replaceArticleContent = ( newContent ) => {
    const { replaceBlocks } = dispatch( 'core/editor' );
    const blocks = select( 'core/block-editor' ).getBlocks();
    const clientIds = blocks.map( ( block ) => block.clientId );
    replaceBlocks( clientIds, convertToBlocks( newContent ) );
}

function JarvisRewrite() {
    const [isBusy, setIsBusy] = useState( false )
    
    const { getEditedPostContent } = select( 'core/editor' );

    const rewriteArticle = () => {

        const articleContent = getEditedPostContent();

        if (!articleContent) return;
        
        setIsBusy( true );
                
        rewriteText( articleContent, ( data ) => {
            let newContent = get( data, 'result', null );
            if (newContent == null) {
                setIsBusy( false );
                return;
            }
            replaceArticleContent( newContent );
            setIsBusy( false );
        } ); 
        
    }

    return(
        <Button 
        isBusy={isBusy} 
        className={'news-crawler-button'} 
        variant="primary"
        onClick={rewriteArticle}>Rewrite Article</Button>
    )
}
function JarvisSEOAudit() {
    const [isBusy, setIsBusy] = useState( false );
    const [suggestedTitles, setSuggestedTitles] = useState( [] );
    const [suggestedKeywords, setSuggestedKeywords] = useState( [] );
    const [suggestedPermalinks, setSuggestedPermalinks] = useState( [] );

    const { getEditedPostAttribute, getEditedPostContent } = select( 'core/editor' );
    const { getKeywords } = select( 'rank-math' );
    const { editPost } = dispatch( 'core/editor' );

    const hasKeyWords = getKeywords();
    
    return(
        <Fragment>
            <div className='jarvis-suggestions'>
                <Keywords />
                <Titles />
                <Descriptions />
                <Permalink />
            </div>
        </Fragment>
    )
}
function Sidebar() {


    return (
        <Fragment>
            <PluginSidebarMoreMenuItem
                target="jarvis-sidebar"
            >
                Jarvis
            </PluginSidebarMoreMenuItem>
            <PluginSidebar
                className='jarvis-sidebar'
                name="jarvis-sidebar"
                title="Jarvis"
                icon={JarvisIcons}
            >
                
                <JarvisRewrite />
                <JarvisSEOAudit />
            </PluginSidebar>
        </Fragment>
    )

}

const { registerPlugin } = wp.plugins;

registerPlugin('jarvis-sidebar', {
    render: function () {
        return <Sidebar />;
    },
});