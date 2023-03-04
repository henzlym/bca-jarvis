import { Fragment, useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { Button, TextControl, Icon } from '@wordpress/components';
import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { getSEOAudit } from '../helpers';


export default function Titles(params) {
    const [isBusy, setIsBusy] = useState( false );
    const { editPost } = dispatch( 'core/editor' );
    
    const { keywords, currentTitle, suggestedTitles } = useSelect( ( select ) => {
        const { getKeywords } = select( 'rank-math' );
        const { getEditedPostAttribute, getEditedPostContent } = select( 'core/editor' );
        return{
            keywords:getKeywords(),
            currentTitle:getEditedPostAttribute( 'title' ),
            suggestedTitles:getEditedPostAttribute( 'suggestedTitles' ) || [],
        }
    })
    
    const runAudit = () => {        
        let titleData = { value:currentTitle, value2:keywords, attribute:'title' }
        setIsBusy( true );
        getSEOAudit( titleData, ( data ) => {
            let results = get( data, 'results', [] );
            editPost( { suggestedTitles: results } );
            setIsBusy( false );
        });
    }
    
    return (
        <Fragment>
        { (keywords && suggestedTitles.length == 0) && (
            <Button 
                isBusy={isBusy} 
                className={'news-crawler-button'} 
                variant="primary"
                onClick={runAudit}>Search SEO titles</Button>
        )}
        {keywords && suggestedTitles.length !== 0 && (
            <div>
                <h3>Titles:</h3>
                <ul>
                    {suggestedTitles.map( ( title ) => {
                        return <li onClick={ () => {
                            editPost({ title:title })
                        }}>{title}</li>
                    })}
                </ul>
            </div>
        )}
        </Fragment>
    )
}