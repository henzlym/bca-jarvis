import { get, has, isArray, isEmpty, isString, isPlainObject } from 'lodash';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { DOWN, ENTER } from '@wordpress/keycodes';
import { Button, TextControl, Icon } from '@wordpress/components';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { useDispatch, useSelect, dispatch, register, select } from '@wordpress/data';
import { store as core } from '@wordpress/core-data';
import { store as editor } from '@wordpress/editor';
import { addQueryArgs } from '@wordpress/url';

import "./editor.scss";
import { convertToBlocks, createTerm, createTerms } from './helpers';

function Article( article ) {

    const [isCrawling, setIsCrawling] = useState( false )
    const [isCreatingArticle, setIsCreatingArticle] = useState( false )
    const [isPostCreated,setPostCreated] = useState( false )
    const [crawledArticle, setCrawledArticle] = useState( null )
    const crawlArticle = () => {
        setIsCrawling( true );
        fetch("/wp-json/jarvis/v1/crawler?url=" + article.link, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
            },
        }).then( res => res.json())
        .then( response => {
            setCrawledArticle(response.result);
            setIsCrawling( false );
        }).catch( error => {
            console.error(error);
            alert(error.message);
            setIsCrawling( false );
        });
    }
    const createPost = async ( article ) => {
        
        setIsCreatingArticle( true )
        
        const url = "/wp-json/wp/v2/posts";
        
        if (!has(article, 'title') || isEmpty(article.title)) {
            console.error('The article must have a non-empty title.');
        }

        if (!has(crawledArticle, 'html') || isEmpty(crawledArticle.html)) {
            console.error('The article must have non-empty content.');
        }

        if (!has(article, 'description') || isEmpty(article.description)) {
            console.error('The article must have a non-empty description.');
        }

        if (!has(article, 'tags') || !isArray(article.tags) || isEmpty(article.tags)) {
            console.error('The article must have a non-empty tags array.');
        }

        if (!has(article, 'category') || isEmpty(article.category)) {
            console.error('The article must have a non-empty category.');
        }
        
        let tags = article.tags.map( tag => tag.term );

        const post = {
            title:article.title,
            content:convertToBlocks( crawledArticle.html ),
            excerpt:article.description,
            meta:{
                'crawled_domain':get( crawledArticle, 'domain', '' ),
                'crawled_categories':article.category,
                'crawled_tags':tags,
                'crawled_summary':get( crawledArticle, 'summary', '' ),
                'crawled_author':get( article, 'author', '' )
            },
            status: "draft",
        }
        
        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-WP-Nonce' : jarvisSettings.nonce
            },
            body: JSON.stringify(post)
        })
        .then(response => response.json())
        .then(post => {
            console.log(post);
            setPostCreated(true);
            setIsCreatingArticle( false );
        });
    }
    return(
        <div className='crawled-article'>
            <div className='crawled-article-headline'>
                <h3><a href={article.link} target="_blank">{article.title}</a></h3>
                <ul className='crawled-article-categories'>
                    <li>{article.category}</li>
                </ul>
                <Button 
                    isBusy={isCrawling} 
                    variant="secondary" 
                    isSmall={true}
                    onClick={crawlArticle}>Crawl</Button>
                { crawledArticle !== null && (
                    <Button 
                        isBusy={isCreatingArticle} 
                        variant="secondary" 
                        isSmall={true}
                        disabled={isPostCreated}
                        onClick={ () => {
                            if (isPostCreated == false) {
                                createPost(article)
                            }
                        } }>{ isPostCreated ? 'Draft Created' : 'Create Draft'}</Button>
                )}
            </div>
        </div>
    )
}
function Articles( props ) {
    const { articles } = props
    if (articles.length == 0) {
        return null;
    }
    return(
        <ul className='crawled-articles'>
            {articles.map( ( article ) => {
                return(
                    <li>
                        <Article {...article}/>
                    </li>
                )
            })}
            
        </ul>
    )
}
function Sidebar() {
    const [isFetching, setIsFetching] = useState( false )
    const [articles, setArticles] = useState( [] )

    const siteUrl = useSelect( ( select ) => {
        return select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ 'site_url' ];
    })
    const { editPost } = useDispatch( 'core/editor' );


    const getArticles = () => {
        setIsFetching( true );
        fetch(`/wp-json/jarvis/v1/feed?url=${siteUrl}`, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
            },
        }).then( res => res.json())
        .then( response => {

            console.log(response);
            setArticles( response )
            setIsFetching( false );
        }).catch( error => {
            console.error(error);
            alert(error.message);
            setIsFetching( false );
            setArticles( [] )

        });
    }
    return (
        <Fragment>
            <PluginSidebarMoreMenuItem
                target="news-crawler"
            >
                News Crawler
            </PluginSidebarMoreMenuItem>
            <PluginSidebar
                className='news-crawler-sidebar'
                name="news-crawler"
                title="Easy Attachments"
                icon={'admin-site-alt2'}
            >
                <TextControl
                    label="Site Url"
                    value={siteUrl}
                    onChange={(url) => {
                        editPost({ meta:{ site_url:url } });
                    }}
                />
                <Button 
                    isBusy={isFetching} 
                    className={'news-crawler-button'} 
                    variant="primary"
                    onClick={getArticles}>Get Articles</Button>

                <Articles articles={articles}/>
            </PluginSidebar>
        </Fragment>
    )

}

const { registerPlugin } = wp.plugins;

registerPlugin('news-crawler-sidebar', {
    render: function () {
        return <Sidebar />;
    },
});