import { createHigherOrderComponent } from '@wordpress/compose';
import {
    InspectorControls,
} from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { Fragment, render, useEffect, useState } from '@wordpress/element';
import { select } from '@wordpress/data'

import './jarvis/sidebar';

const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        if (props.name !== 'core/paragraph') {
            return <BlockEdit { ...props } />;
        }
        
        const [isRewriting, setIsRewriting] = useState( false )
        const { attributes:{ content }, setAttributes } = props;
        const postTitle = select('core/editor').getCurrentPostAttribute('title');
    
        const createCompletion = async () => {
            try {
                setIsRewriting( true );
                const response = await fetch("/wp-json/openai/v1/create_completion", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ content: content, title:postTitle }),
                });
    
                const data = await response.json();
                if (response.status !== 200) {
                    throw data.error || new Error(`Request failed with status ${response.status}`);
                }
                console.log( data );
                setAttributes({ content: data.result });
                setIsRewriting( false );
            } catch (error) {
                // Consider implementing your own error handling logic here
                console.error(error);
                alert(error.message);
            }
        }
        
        return (
            <>
                <BlockEdit { ...props } />
                <InspectorControls>
                    <PanelBody>
                        <Button variant="secondary" isBusy={isRewriting} onClick={ () => { createCompletion() } }>Rewrite</Button>
                    </PanelBody>
                </InspectorControls>
            </>
        );
    };
}, 'withInspectorControl' );

wp.hooks.addFilter(
    'editor.BlockEdit',
    'my-plugin/with-inspector-controls',
    withInspectorControls
);

