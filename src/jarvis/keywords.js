import { Fragment, useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { Button, TextControl, Icon } from '@wordpress/components';
import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { getSEOAudit } from '../helpers';

export default function Keywords() {
    const [isBusy, setIsBusy] = useState( false );
    // const [suggestedKeywords, setSuggestedKeywords] = useState( [] );
    
    const { editPost } = dispatch( 'core/editor' );
    
    const { hasKeyWords, currentTitle, suggestedKeywords } = useSelect( ( select ) => {
        const { getKeywords } = select( 'rank-math' );
        const { getEditedPostAttribute, getEditedPostContent } = select( 'core/editor' );
        return{
            hasKeyWords:getKeywords(),
            currentTitle:getEditedPostAttribute( 'title' ),
            suggestedKeywords:getEditedPostAttribute( 'suggestedKeywords' ) || [],
        }
    })
    
    const runAudit = () => {
        setIsBusy( true );
        
        let keywordsData = { value:currentTitle, attribute:'keywords' }
        getSEOAudit( keywordsData, ( data ) => {
            let results = get( data, 'results', [] );
            // setSuggestedKeywords( results );
            editPost({ suggestedKeywords:results })
            setIsBusy( false );
        });
    }
    
    return(
        <Fragment>
            { (!hasKeyWords || suggestedKeywords.length == 0) && (
                <Button 
                    isBusy={isBusy} 
                    className={'news-crawler-button'} 
                    variant="primary"
                    onClick={runAudit}>Search SEO Keywords</Button>
            )}
            {suggestedKeywords.length !== 0 && (
                <div>
                    <h3>Suggested Keywords:</h3>
                    <ul className='tpd-jarvis-seo-results'>
                        {suggestedKeywords.map( ( keyword ) => {
                            return <li className='tpd-jarvis-seo-item' onClick={ () => {
                                dispatch( 'rank-math' ).updateKeywords( keyword )
                            }}>{keyword}</li>
                        })}
                    </ul>
                </div>
            )}
        </Fragment>
    )
}