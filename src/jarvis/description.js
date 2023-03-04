import { Fragment, useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { Button, TextControl, Icon } from '@wordpress/components';
import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { getSEOAudit } from '../helpers';


export default function Descriptions() {
    const [isBusy, setIsBusy] = useState( false );
    const { editPost } = dispatch( 'core/editor' );
    
    const { keywords, currentTitle, suggestedDescriptions } = useSelect( ( select ) => {
        const { getKeywords } = select( 'rank-math' );
        const { getEditedPostAttribute, getEditedPostContent } = select( 'core/editor' );
        return{
            keywords:getKeywords(),
            currentTitle:getEditedPostAttribute( 'title' ),
            suggestedDescriptions:getEditedPostAttribute( 'suggestedDescriptions' ) || [],
        }
    })
    
    const runAudit = () => {        
        let titleData = { value:currentTitle, value2:keywords, attribute:'description' }
        setIsBusy( true );
        getSEOAudit( titleData, ( data ) => {
            let results = get( data, 'results', [] );
            editPost( { suggestedDescriptions: results } );
            setIsBusy( false );
        });
    }
    
    return (
        <Fragment>
        { (keywords && suggestedDescriptions.length == 0) && (
            <Button 
                isBusy={isBusy} 
                className={'news-crawler-button'} 
                variant="primary"
                onClick={runAudit}>Search SEO Descriptions</Button>
        )}
        {keywords && suggestedDescriptions.length !== 0 && (
            <div>
                <h3>Descriptions:</h3>
                <ul>
                    {suggestedDescriptions.map( ( description ) => {
                        return <li onClick={ () => {
                            editPost({ excerpt:description });
                            dispatch( 'rank-math' ).updateDescription( description );
                        }}>{description}</li>
                    })}
                </ul>
            </div>
        )}
        </Fragment>
    )
}