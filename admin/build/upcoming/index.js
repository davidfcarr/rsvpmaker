/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/upcoming/block.json"
/*!*********************************!*\
  !*** ./src/upcoming/block.json ***!
  \*********************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"rsvpmaker/upcoming","title":"RSVPMaker Upcoming Events","icon":"calendar-alt","category":"rsvpmaker","description":"Displays an RSVPMaker event listing and/or a calendar widget. (Alternative: the RSVPMaker Query Loop block variation allows more control over the formatting of your events listing)","keywords":["RSVPMaker","Events","Calendar"],"attributes":{"calendar":{"type":"string","default":"0"},"excerpt":{"type":"string","default":"0"},"nav":{"type":"string","default":"bottom"},"days":{"type":"string","default":"180"},"posts_per_page":{"type":"string","default":"10"},"itemcolor":{"type":"string","default":"#FFFFFF"},"itembg":{"type":"string","default":"#000000"},"itemfontsize":{"type":"string","default":"10"},"type":{"type":"string","default":""},"exclude_type":{"type":"string","default":""},"no_events":{"type":"string","default":"No events listed"},"author":{"type":"string","default":""},"hideauthor":{"type":"boolean","default":false}},"version":"1","supports":{"html":false,"color":{"link":true}},"textdomain":"rsvpmaker","editorScript":"file:./index.js","editorStyle":"file:./index.css","render":"file:./render.php","style":"file:./style-index.css"}');

/***/ },

/***/ "./src/upcoming/edit.js"
/*!******************************!*\
  !*** ./src/upcoming/edit.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./editor.scss */ "./src/upcoming/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);
/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */


const {
  Component,
  Fragment
} = wp.element;
const {
  Panel,
  PanelBody,
  SelectControl,
  RadioControl,
  TextControl,
  ColorPalette,
  FontSizePicker
} = wp.components;



/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */

function Edit(props) {
  const {
    attributes
  } = props;
  const [rsvptypes, setTypes] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [rsvpauthors, setAuthors] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [preview, setPreview] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const t = [{
      value: '',
      label: 'None selected (optional)'
    }];
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: 'rsvpmaker/v1/types'
    }).then(types => {
      if (Array.isArray(types)) types.map(function (type) {
        if (type.slug && type.name) t.push({
          value: type.slug,
          label: type.name
        });
      });else {
        var typesarray = Object.values(types);
        typesarray.map(function (type) {
          if (type.slug && type.name) t.push({
            value: type.slug,
            label: type.name
          });
        });
        console.log(type.slug);
        console.log(typeof type.slug);
        console.log(type.name);
        console.log(typeof type.name);
      }
    }).catch(err => {
      console.log(err);
    });
    setTypes(t);
    const a = [{
      value: '',
      label: 'Any'
    }];
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: 'rsvpmaker/v1/authors'
    }).then(authors => {
      if (Array.isArray(authors)) authors.map(function (author) {
        if (author.ID && author.name) a.push({
          value: author.ID,
          label: author.name
        });
      });else {
        authors = Object.values(authors);
        authors.map(function (author) {
          if (author.ID && author.name) a.push({
            value: author.ID,
            label: author.name
          });
        });
      }
    }).catch(err => {
      console.log(err);
    });
    setAuthors(a);
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: (0,_wordpress_url__WEBPACK_IMPORTED_MODULE_4__.addQueryArgs)('/rsvpmaker/v1/upcoming_preview/', attributes)
    }).then(p => {
      if (p.calendar) setPreview(p.calendar);
    });
  }, [attributes]);
  class UpcomingInspector extends Component {
    render() {
      const {
        attributes: {
          calendar,
          excerpt,
          days,
          posts_per_page,
          hideauthor,
          no_events,
          nav,
          type,
          exclude_type,
          author,
          itemcolor,
          itembg,
          itemfontsize
        },
        setAttributes,
        isSelected
      } = this.props;
      const fontSizes = [{
        name: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Small'),
        slug: 'small',
        size: 10
      }, {
        name: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Medium'),
        slug: 'medium',
        size: 12
      }, {
        name: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Large'),
        slug: 'large',
        size: 13
      }, {
        name: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Extra Large'),
        slug: 'xlarge',
        size: 14
      }];
      const fallbackFontSize = 10;
      console.log('type', type);
      console.log('types', rsvptypes);
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(PanelBody, {
            title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('RSVPMaker Upcoming Options', 'rsvpmaker'),
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("form", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Display Calendar", 'rsvpmaker'),
                value: calendar,
                options: [{
                  value: 1,
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Yes - Calendar plus events listing')
                }, {
                  value: 0,
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No - Events listing only')
                }, {
                  value: 2,
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Calendar only')
                }],
                onChange: calendar => {
                  console.log('calendar choice ' + typeof calendar);
                  console.log(calendar);
                  setAttributes({
                    calendar: calendar
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Format", 'rsvpmaker'),
                value: excerpt,
                options: [{
                  value: 0,
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Full Text')
                }, {
                  value: 1,
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Excerpt')
                }],
                onChange: excerpt => {
                  setAttributes({
                    excerpt: excerpt
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Events Per Page", 'rsvpmaker'),
                value: posts_per_page,
                options: [{
                  value: 5,
                  label: 5
                }, {
                  value: 10,
                  label: 10
                }, {
                  value: 15,
                  label: 15
                }, {
                  value: 20,
                  label: 20
                }, {
                  value: 25,
                  label: 25
                }, {
                  value: 30,
                  label: 30
                }, {
                  value: 35,
                  label: 35
                }, {
                  value: 40,
                  label: 40
                }, {
                  value: 45,
                  label: 45
                }, {
                  value: 50,
                  label: 50
                }, {
                  value: '-1',
                  label: 'No limit'
                }],
                onChange: posts_per_page => {
                  setAttributes({
                    posts_per_page: posts_per_page
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Date Range", 'rsvpmaker'),
                value: days,
                options: [{
                  value: 5,
                  label: 5
                }, {
                  value: 30,
                  label: '30 Days'
                }, {
                  value: 60,
                  label: '60 Days'
                }, {
                  value: 90,
                  label: '90 Days'
                }, {
                  value: 180,
                  label: '180 Days'
                }, {
                  value: 366,
                  label: '1 Year'
                }],
                onChange: days => {
                  setAttributes({
                    days: days
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Event Type", 'rsvpmaker'),
                selected: type,
                value: type,
                options: rsvptypes,
                onChange: type => {
                  setAttributes({
                    type: type
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("EXCLUDE Event Type", 'rsvpmaker'),
                selected: exclude_type,
                value: exclude_type,
                options: rsvptypes,
                onChange: exclude_type => {
                  setAttributes({
                    exclude_type: exclude_type
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Author", 'rsvpmaker'),
                value: author,
                options: rsvpauthors,
                onChange: author => {
                  setAttributes({
                    author: author
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Calendar Navigation", 'rsvpmaker'),
                value: nav,
                options: [{
                  value: 'top',
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Top')
                }, {
                  value: 'bottom',
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Bottom')
                }, {
                  value: 'both',
                  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Both')
                }],
                onChange: nav => {
                  setAttributes({
                    nav: nav
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(SelectControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Show Event Author", 'rsvpmaker'),
                value: hideauthor,
                options: [{
                  label: 'No',
                  value: true
                }, {
                  label: 'Yes',
                  value: false
                }],
                onChange: hideauthor => {
                  setAttributes({
                    hideauthor: hideauthor
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(TextControl, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Text to show for no events listed", 'rsvpmaker'),
                value: no_events,
                onChange: no_events => {
                  setAttributes({
                    no_events: no_events
                  });
                }
              })]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(Panel, {
            header: "Calendar Colors",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(PanelBody, {
              title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Calendar Item Text Color', 'rsvpmaker'),
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(ColorPalette, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Calendar item text color", 'rsvpmaker'),
                colors: wp.data.select("core/editor").getEditorSettings().colors,
                value: itemcolor,
                defaultValue: itemcolor,
                onChange: itemcolor => {
                  setAttributes({
                    itemcolor
                  });
                }
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(PanelBody, {
              title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Calendar Item Background Color', 'rsvpmaker'),
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(ColorPalette, {
                colors: wp.data.select("core/editor").getEditorSettings().colors,
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Calendar item background color", 'rsvpmaker'),
                value: itembg,
                defaultValue: itembg,
                onChange: itembg => {
                  setAttributes({
                    itembg
                  });
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("svg", {
                  viewBox: "0 0 24 24",
                  xmlns: "http://www.w3.org/2000/svg",
                  width: "24",
                  height: "24",
                  "aria-hidden": "true",
                  focusable: "false",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("path", {
                    d: "M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"
                  })
                }), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("em", {
                  children: "See the styles tab for the overall text and background color settings."
                })]
              })]
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(Panel, {
            header: "Calendar Fonts",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(PanelBody, {
              title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Calendar Item Font Size', 'rsvpmaker'),
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(FontSizePicker, {
                label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Calendar item text size", 'rsvpmaker'),
                value: itemfontsize,
                fontSizes: fontSizes,
                fallbackFontSize: fallbackFontSize,
                onChange: itemfontsize => {
                  setAttributes({
                    itemfontsize: itemfontsize
                  });
                }
              })
            })
          })]
        }, "upcominginspector")
      });
    }
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(Fragment, {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)(),
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(UpcomingInspector, {
        ...props
      }), preview && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
        dangerouslySetInnerHTML: {
          __html: preview
        }
      }), !preview && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
        children: "RSVPMaker Upcoming loading ..."
      })]
    })
  });
}

/***/ },

/***/ "./src/upcoming/editor.scss"
/*!**********************************!*\
  !*** ./src/upcoming/editor.scss ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./src/upcoming/index.js"
/*!*******************************!*\
  !*** ./src/upcoming/index.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/upcoming/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/upcoming/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./save */ "./src/upcoming/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./block.json */ "./src/upcoming/block.json");
/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * Internal dependencies
 */




/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_4__.name, {
  /**
   * @see ./edit.js
   */
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"],
  /**
   * @see ./save.js
   */
  save: _save__WEBPACK_IMPORTED_MODULE_3__["default"],
  transforms: {
    to: [{
      type: 'block',
      blocks: ['core/query'],
      transform: atts => {
        const qatts = {
          "queryId": 0,
          "query": {
            "perPage": 20,
            "pages": 0,
            "offset": 0,
            "postType": "rsvpmaker",
            "order": "asc",
            "author": "",
            "search": "",
            "exclude": [],
            "sticky": "",
            "inherit": false
          },
          "namespace": "rsvpmaker/rsvpmaker-loop"
        };
        const template = atts.calendar && parseInt(atts.calendar) ? [['rsvpmaker/calendar', atts], ['core/post-template', {
          "layout": {
            "type": "grid",
            "columnCount": 2
          }
        }, [['core/post-title', {
          "isLink": true
        }], ['core/post-featured-image'], ['rsvpmaker/loop-blocks'], ['core/read-more', {
          "content": "Read More \u003e\u003e"
        }]]], ['core/query-pagination'], ['core/query-no-results', {}, [['core/paragraph', {
          "content": "No events found."
        }]]]] : [['core/post-template', {
          "layout": {
            "type": "grid",
            "columnCount": 2
          }
        }, [['core/post-title', {
          "isLink": true
        }], ['core/post-featured-image'], ['rsvpmaker/loop-blocks'], ['core/read-more', {
          "content": "Read More \u003e\u003e"
        }]]], ['core/query-pagination'], ['core/query-no-results', {}, [['core/paragraph', {
          "content": "No events found."
        }]]]];
        const innerblocks = (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.createBlocksFromInnerBlocksTemplate)(template);
        return (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.createBlock)('core/query', qatts, innerblocks);
      }
    }]
  }
});

/***/ },

/***/ "./src/upcoming/save.js"
/*!******************************!*\
  !*** ./src/upcoming/save.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__);
/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */


/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
function save() {
  return null;
}

/***/ },

/***/ "./src/upcoming/style.scss"
/*!*********************************!*\
  !*** ./src/upcoming/style.scss ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "@wordpress/url"
/*!*****************************!*\
  !*** external ["wp","url"] ***!
  \*****************************/
(module) {

module.exports = window["wp"]["url"];

/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"upcoming/index": 0,
/******/ 			"upcoming/style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkadmin"] = globalThis["webpackChunkadmin"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["upcoming/style-index"], () => (__webpack_require__("./src/upcoming/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map