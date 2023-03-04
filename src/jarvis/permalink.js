import { Fragment, useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { Button, TextControl, Icon } from '@wordpress/components';
import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { getSEOAudit } from '../helpers';


export default function Permalink() {
    const [isBusy, setIsBusy] = useState( false );
    const { editPost } = dispatch( 'core/editor' );
    
    const { keywords, currentSlug, suggestedPermalink } = useSelect( ( select ) => {
        const { getKeywords } = select( 'rank-math' );
        const { getEditedPostAttribute, getEditedPostContent } = select( 'core/editor' );
        return{
            keywords:getKeywords(),
            currentSlug:getEditedPostAttribute( 'slug' ),
            suggestedPermalink:getEditedPostAttribute( 'suggestedPermalink' ) || [],
        }
    })
    
    const runAudit = () => {        
        let permalinkData = { value:currentSlug, value2:keywords, attribute:'url' }
        setIsBusy( true );
        getSEOAudit( permalinkData, ( data ) => {
            let results = get( data, 'results', [] );
            editPost( { suggestedPermalink: results } );
            setIsBusy( false );
        });
    }
    
    return (
        <Fragment>
        { (keywords && suggestedPermalink.length == 0) && (
            <Button 
                isBusy={isBusy} 
                className={'news-crawler-button'} 
                variant="primary"
                onClick={runAudit}>Search SEO Permalink</Button>
        )}
        {keywords && suggestedPermalink.length !== 0 && (
            <div>
                <h3>Suggested permalinks/slugs:</h3>
                <ul>
                    {suggestedPermalink.map( ( permalink ) => {
                        return <li onClick={ () => {
                            editPost({ slug:permalink });
                        }}>/{permalink}</li>
                    })}
                </ul>
            </div>
        )}
        </Fragment>
    )
}