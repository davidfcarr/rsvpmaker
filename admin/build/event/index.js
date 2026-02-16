/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/event/block.json"
/*!******************************!*\
  !*** ./src/event/block.json ***!
  \******************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"rsvpmaker/event","title":"RSVPMaker Embed Event","icon":"products","category":"rsvpmaker","keywords":["RSVPMaker","Event","Calendar"],"attributes":{"post_id":{"type":"string","default":""},"one_hideauthor":{"type":"boolean","default":true},"type":{"type":"string","default":""},"one_format":{"type":"string","default":""},"hide_past":{"type":"string","default":""}},"version":"2","textdomain":"rsvpmaker","editorScript":"file:./index.js","editorStyle":"file:./index.css","render":"file:./render.php","style":"file:./style-index.css"}');

/***/ },

/***/ "./src/event/edit.js"
/*!***************************!*\
  !*** ./src/event/edit.js ***!
  \***************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./editor.scss */ "./src/event/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
const {
  __
} = wp.i18n;
const {
  InspectorControls,
  useBlockProps
} = wp.blockEditor;
const {
  SelectControl
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
  const blockProps = useBlockProps();
  const {
    attributes: {
      post_id,
      type,
      one_hideauthor,
      one_format,
      hide_past
    },
    attributes,
    setAttributes,
    isSelected
  } = props;
  const [eventHtml, setEventHtml] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  if (post_id == '') setAttributes({
    post_id: 'next'
  });
  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: (0,_wordpress_url__WEBPACK_IMPORTED_MODULE_1__.addQueryArgs)('/rsvpmaker/v1/preview/one', attributes)
  }).then(x => {
    console.log('downloaded event html', x);
    setEventHtml(x);
  });
  const rsvpupcoming = [{
    label: __('Choose event'),
    value: ''
  }, {
    label: __('Next event'),
    value: 'next'
  }, {
    label: __('Next event - RSVP on'),
    value: 'nextrsvp'
  }];
  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: 'rsvpmaker/v1/future'
  }).then(events => {
    if (Array.isArray(events)) {
      events.map(function (event) {
        if (event.ID) {
          var title = event.date ? event.post_title + ' - ' + event.date : event.post_title;
          rsvpupcoming.push({
            value: event.ID,
            label: title
          });
        }
      });
    } else {
      var eventsarray = Object.values(events);
      eventsarray.map(function (event) {
        if (event.ID) {
          var title = event.date ? event.post_title + ' - ' + event.date : event.post_title;
          rsvpupcoming.push({
            value: event.ID,
            label: title
          });
        }
      });
    }
  }).catch(err => {
    console.log(err);
  });
  const rsvptypes = [{
    value: '',
    label: 'None selected (optional)'
  }];
  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: 'rsvpmaker/v1/types'
  }).then(types => {
    if (Array.isArray(types)) types.map(function (type) {
      if (type.slug && type.name) rsvptypes.push({
        value: type.slug,
        label: type.name
      });
    });else {
      var typesarray = Object.values(types);
      typesarray.map(function (type) {
        if (type.slug && type.name) rsvptypes.push({
          value: type.slug,
          label: type.name
        });
      });
    }
  }).catch(err => {
    console.log(err);
  });
  const rsvpauthors = [{
    value: '',
    label: 'Any'
  }];
  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: 'rsvpmaker/v1/authors'
  }).then(authors => {
    if (Array.isArray(authors)) authors.map(function (author) {
      if (author.ID && author.name) rsvpauthors.push({
        value: author.ID,
        label: author.name
      });
    });else {
      authors = Object.values(authors);
      authors.map(function (author) {
        if (author.ID && author.name) rsvpauthors.push({
          value: author.ID,
          label: author.name
        });
      });
    }
  }).catch(err => {
    console.log(err);
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    ...blockProps,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(InspectorControls, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(SelectControl, {
        label: __("Select Post", 'rsvpmaker'),
        value: post_id,
        options: rsvpupcoming,
        onChange: post_id => {
          setAttributes({
            post_id: post_id
          });
        }
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(SelectControl, {
        label: __("Format", 'rsvpmaker'),
        value: one_format,
        options: [{
          label: 'Event with Form',
          value: ''
        }, {
          label: 'Event with Button',
          value: 'button'
        }, {
          label: 'Event Excerpt with Button',
          value: 'excerpt'
        }, {
          label: 'Headline, Date and Icons, Button',
          value: 'headline_date_button'
        }, {
          label: 'Button Only',
          value: 'button_only'
        }, {
          label: 'Form Only',
          value: 'form'
        }, {
          label: 'Compact (Headline/Date/Button)',
          value: 'compact'
        }, {
          label: 'Dates Only',
          value: 'embed_dateblock'
        }],
        onChange: one_format => {
          setAttributes({
            one_format: one_format
          });
        }
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(SelectControl, {
        label: __("Hide After", 'rsvpmaker'),
        value: hide_past,
        options: [{
          label: 'Not Set',
          value: ''
        }, {
          label: '1 hour',
          value: '1'
        }, {
          label: '2 hours',
          value: '2'
        }, {
          label: '3 hours',
          value: '3'
        }, {
          label: '4 hours',
          value: '4'
        }, {
          label: '5 hours',
          value: '5'
        }, {
          label: '6 hours',
          value: '6'
        }, {
          label: '7 hours',
          value: '7'
        }, {
          label: '8 hours',
          value: '8'
        }, {
          label: '12 hours',
          value: '12'
        }, {
          label: '18 hours',
          value: '18'
        }, {
          label: '24 hours',
          value: '24'
        }, {
          label: '2 days',
          value: '48'
        }, {
          label: '3 days',
          value: '72'
        }],
        onChange: hide_past => {
          setAttributes({
            hide_past: hide_past
          });
        }
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(SelectControl, {
        label: __("Event Type", 'rsvpmaker'),
        value: type,
        options: rsvptypes,
        onChange: type => {
          setAttributes({
            type: type
          });
        }
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(SelectControl, {
        label: __("Show Author", 'rsvpmaker'),
        value: one_hideauthor,
        options: [{
          label: 'No',
          value: '1'
        }, {
          label: 'Yes',
          value: '0'
        }],
        onChange: one_hideauthor => {
          setAttributes({
            one_hideauthor: one_hideauthor
          });
        }
      })]
    }, "eventinspector"), eventHtml && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      dangerouslySetInnerHTML: {
        __html: eventHtml
      }
    }), !eventHtml && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
        children: "Loading ..."
      })
    })]
  });
}

/***/ },

/***/ "./src/event/editor.scss"
/*!*******************************!*\
  !*** ./src/event/editor.scss ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./src/event/index.js"
/*!****************************!*\
  !*** ./src/event/index.js ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/event/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/event/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./save */ "./src/event/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./block.json */ "./src/event/block.json");
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

/***/ "./src/event/save.js"
/*!***************************!*\
  !*** ./src/event/save.js ***!
  \***************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
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

/***/ "./src/event/style.scss"
/*!******************************!*\
  !*** ./src/event/style.scss ***!
  \******************************/
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

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

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
/******/ 			"event/index": 0,
/******/ 			"event/style-index": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["event/style-index"], () => (__webpack_require__("./src/event/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map