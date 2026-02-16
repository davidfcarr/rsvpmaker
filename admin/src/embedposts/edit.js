/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useState, useEffect } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';
const { Panel, PanelBody,SelectControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({attributes, setAttributes, className}) {
        const { selection, posts_per_page } = attributes;
        const [cats, setCats] = useState([]);
        const [preview, setPreview] = useState('');
        const blockProps = useBlockProps({ className });
        const postsPath = 'rsvpmaker/v1/preview/emailpostorposts'; 
        const catsPath = "rsvpmaker/v1/postsorcategories";
        useEffect( () => {
            const loadCats = async () => {
                if(cats.length == 0) {
                    const fetchedCats = await apiFetch({path: catsPath});
                    setCats( fetchedCats );	
                }
            };
            loadCats();
        }, []);
    
        const fetchPosts = async () => {
            const path = `${postsPath}?posts_per_page=${posts_per_page}&selection=${selection}`;
            const result = await apiFetch({path});
            setPreview(result);
        }
    
        useEffect( () => { fetchPosts(); }, [selection, posts_per_page, fetchPosts]);
    
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <Panel>
                        <PanelBody>
                            <SelectControl
                                label={__("Select Post or Category", "rsvpmaker")}
                                options={cats}
                                value={selection}
                                onChange={(val) => setAttributes({selection: val})}
                            />
                            <SelectControl
                                label={__("Number of Posts for Listings", "rsvpmaker")}
                                options={[
                                {"label":'1',value:1},
                                {"label":'2',value:2},
                                {"label":'3',value:3},
                                {"label":'4',value:4},
                                {"label":'5',value:5},
                                {"label":'6',value:6},
                                {"label":'7',value:7},
                                {"label":'8',value:8},
                                {"label":'9',value:9},
                                {"label":'10',value:10}
                            ]}
                                value={posts_per_page}
                                onChange={(val) => setAttributes({posts_per_page: val})}
                            />
                        </PanelBody>
                    </Panel>
                </InspectorControls>
                <div {...blockProps}>
                    <div className="mylatests-list">
                        <RawHTML>{preview}</RawHTML>
                        {!preview || preview === '' && <p>Loading...</p>}
                    </div>
                </div>
    
            </div>
        );        
}
