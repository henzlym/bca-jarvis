import { Fragment, render, useEffect, useState } from '@wordpress/element';
import { Notice } from "@wordpress/components";
import {
	createBlock,
	getBlockContent,
	pasteHandler,
	rawHandler,
	registerBlockType,
	serialize,
} from '@wordpress/blocks';
import {
    BlockEditorProvider,
    BlockList,
    BlockTools,
    WritingFlow,
    ObserveTyping,
} from '@wordpress/block-editor';
import { registerCoreBlocks } from '@wordpress/block-library';
import domReady from '@wordpress/dom-ready';

import "./editor.scss";

export default function Home() {
    const [urlInput, setUrlInput] = useState("");
    const [animalInput, setAnimalInput] = useState("");
    const [result, setResult] = useState();
    const [crawledResult, setCrawledResult] = useState(false);
    const [postCreated, setPostCreated] = useState(false);

    async function onSubmit(event) {
        event.preventDefault();
        try {
            const response = await fetch("/wp-json/openai/v1/create_completion", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ animal: animalInput }),
            });

            const data = await response.json();
            if (response.status !== 200) {
                throw data.error || new Error(`Request failed with status ${response.status}`);
            }

            setResult(data.result);
            setAnimalInput("");
        } catch (error) {
            // Consider implementing your own error handling logic here
            console.error(error);
            alert(error.message);
        }
    }

    async function onUrlSubmit(event) {
        event.preventDefault();
        try {
            const response = await fetch("/wp-json/jarvis/v1/crawler?url=" + urlInput, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                },
            });

            const data = await response.json();
            if (response.status !== 200) {
                throw data.error || new Error(`Request failed with status ${response.status}`);
            }

            setCrawledResult(data.result);
            setUrlInput("");
        } catch (error) {
            // Consider implementing your own error handling logic here
            console.error(error);
            alert(error.message);
        }
    }
    const convertToBlocks = ( content ) => {
		let blocks = pasteHandler({ HTML: content });
        console.log(blocks);
        console.log(serialize(blocks));
        return serialize(blocks)
    }
    
    const createDraftPost = (title, content) => {
        const url = "/wp-json/wp/v2/posts";

        const data = {
            title: title,
            content: convertToBlocks( content ),
            status: "draft"
        };

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-WP-Nonce' : jarvisSettings.nonce
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(post => {
            console.log(post);
            setPostCreated(true);
        });
    }

    return (
        <div>
            <main className='main'>
                <h3>Jarvis</h3>
                <form onSubmit={onUrlSubmit}>
                    <input
                        type="text"
                        name="url"
                        placeholder="Enter url"
                        value={urlInput}
                        onChange={(e) => setUrlInput(e.target.value)}
                    />
                    <input type="submit" value="Crawl Url" />
                </form>
                {postCreated && (
                    <Notice status="success" isDismissible={true}>
                        Post created successfully.
                    </Notice>
                )}
                {crawledResult && (
                    <Fragment>
                        <div>
                            <h3>{crawledResult.title}</h3>
                            <p>{crawledResult.content}</p>
                            <div dangerouslySetInnerHTML={{ __html: crawledResult.html }} />
                        </div>
                        <button onClick={() => createDraftPost(crawledResult.title, crawledResult.html)}>
                            Create Draft Post
                        </button>
                    </Fragment>
                )}

                <form onSubmit={onSubmit}>
                    <input
                        type="text"
                        name="animal"
                        placeholder="Enter an animal"
                        value={animalInput}
                        onChange={(e) => setAnimalInput(e.target.value)}
                    />
                    <input type="submit" value="Generate names" />
                </form>
                <div>{result}</div>
            </main>
        </div>
    );
}
function MyEditorComponent() {
    const [blocks, updateBlocks] = useState([]);

    return (
        <BlockEditorProvider
            value={[]}
            onInput={(blocks) => updateBlocks(blocks)}
            onChange={(blocks) => updateBlocks(blocks)}
        >
            <Home />
        </BlockEditorProvider>
    );
}

domReady( function () {
    const settings = window.getdaveSbeSettings || {};
    registerCoreBlocks();
    render(
        <MyEditorComponent settings={ settings } />,
        document.getElementById( 'bca-jarvis-root' )
    );
} );