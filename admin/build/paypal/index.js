/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/paypal/block.json"
/*!*******************************!*\
  !*** ./src/paypal/block.json ***!
  \*******************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"rsvpmaker/paypal","title":"PayPal Charge (RSVPMaker)","icon":"admin-comments","category":"rsvpmaker","description":"Displays a PayPal charge form.","keywords":["Stripe","Payment","Charge"],"supports":{"html":false,"color":{"text":true,"background":true,"link":true},"align":["left","right","center","wide","full"],"typography":{"fontSize":true,"lineHeight":true,"textAlign":true},"spacing":{"margin":true,"padding":true,"blockGap":true}},"attributes":{"description":{"type":"string","default":""},"showdescription":{"type":"string","default":"no"},"amount":{"type":"string","default":""},"paymentType":{"type":"string","default":"once"},"currency":{"type":"string","default":"usd"},"january":{"type":"string","default":""},"february":{"type":"string","default":""},"march":{"type":"string","default":""},"april":{"type":"string","default":""},"may":{"type":"string","default":""},"june":{"type":"string","default":""},"july":{"type":"string","default":""},"august":{"type":"string","default":""},"september":{"type":"string","default":""},"october":{"type":"string","default":""},"november":{"type":"string","default":""},"december":{"type":"string","default":""}},"version":"2","textdomain":"rsvpmaker","editorScript":"file:./index.js","editorStyle":"file:./index.css","render":"file:./render.php","style":"file:./style-index.css"}');

/***/ },

/***/ "./src/paypal/edit.js"
/*!****************************!*\
  !*** ./src/paypal/edit.js ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./editor.scss */ "./src/paypal/editor.scss");
/* harmony import */ var _paypal_logo_svg__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./paypal-logo.svg */ "./src/paypal/paypal-logo.svg");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);
const {
  __
} = wp.i18n;
const {
  InspectorControls,
  useBlockProps
} = wp.blockEditor;
const {
  ToggleControl,
  TextControl,
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
  const {
    attributes: {
      description,
      showdescription,
      amount,
      paymentType,
      january,
      february,
      march,
      april,
      may,
      june,
      july,
      august,
      september,
      october,
      november,
      december,
      currency
    },
    setAttributes,
    className,
    isSelected
  } = props;
  const show = paymentType.toString() == 'schedule' ? true : false;
  let currency_symbol = '';
  if (currency.toString() == 'usd') currency_symbol = '$';else if (currency.toString() == 'eur') currency_symbol = '€';
  const blockProps = useBlockProps({
    className
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    ...blockProps,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(InspectorControls, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
        label: __('Description', 'rsvpmaker'),
        value: description,
        onChange: description => setAttributes({
          description
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        children: ["  ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(SelectControl, {
          label: __('Show Amount/Description Under Button', 'rsvpmaker'),
          value: showdescription,
          onChange: showdescription => setAttributes({
            showdescription
          }),
          options: [{
            value: 'yes',
            label: __('Yes', 'rsvpmaker')
          }, {
            value: 'no',
            label: __('No', 'rsvpmaker')
          }]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(SelectControl, {
          label: __('Payment Type', 'rsvpmaker'),
          value: paymentType,
          onChange: paymentType => setAttributes({
            paymentType
          }),
          options: [{
            value: 'one-time',
            label: __('One time, fixed fee', 'rsvpmaker')
          }, {
            value: 'schedule',
            label: __('Dues schedule', 'rsvpmaker')
          }, {
            value: 'donation',
            label: __('Donation', 'rsvpmaker')
          }]
        })]
      }), !show && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
        label: __('Fee', 'rsvpmaker'),
        value: amount,
        placeholder: "$0.00",
        onChange: amount => setAttributes({
          amount
        })
      }), show && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        children: ["    ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('January', 'rsvpmaker'),
          value: january,
          onChange: january => setAttributes({
            january
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('February', 'rsvpmaker'),
          value: february,
          onChange: february => setAttributes({
            february
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('March', 'rsvpmaker'),
          value: march,
          onChange: march => setAttributes({
            march
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('April', 'rsvpmaker'),
          value: april,
          onChange: april => setAttributes({
            april
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('May', 'rsvpmaker'),
          value: may,
          onChange: may => setAttributes({
            may
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('June', 'rsvpmaker'),
          value: june,
          onChange: june => setAttributes({
            june
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('July', 'rsvpmaker'),
          value: july,
          onChange: july => setAttributes({
            july
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('August', 'rsvpmaker'),
          value: august,
          onChange: august => setAttributes({
            august
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('September', 'rsvpmaker'),
          value: september,
          onChange: september => setAttributes({
            september
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('October', 'rsvpmaker'),
          value: october,
          onChange: october => setAttributes({
            october
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('November', 'rsvpmaker'),
          value: november,
          onChange: november => setAttributes({
            november
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
          label: __('December', 'rsvpmaker'),
          value: december,
          onChange: december => setAttributes({
            december
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(TextControl, {
        label: __('Currency Code (lowercase)', 'rsvpmaker'),
        value: currency,
        onChange: currency => setAttributes({
          currency
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      style: {
        textAlign: 'center',
        padding: '10px',
        backgroundColor: '#ffc439'
      },
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("img", {
        src: _paypal_logo_svg__WEBPACK_IMPORTED_MODULE_1__["default"],
        alt: "PayPal",
        role: "presentation",
        className: "paypal-logo paypal-logo-paypal paypal-logo-color-blue"
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      style: {
        textAlign: 'center',
        fontSize: '10px'
      },
      children: ["Powered by ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("img", {
        src: _paypal_logo_svg__WEBPACK_IMPORTED_MODULE_1__["default"],
        width: "32",
        alt: "PayPal",
        role: "presentation",
        className: "paypal-logo paypal-logo-paypal paypal-logo-color-blue"
      })]
    }), description && showdescription == 'yes' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("p", {
      className: "description",
      children: [currency_symbol, amount, " ", currency, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("br", {}), description]
    }) : null, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("p", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("em", {
        children: "Preview is approximate"
      })
    })]
  });
}

/***/ },

/***/ "./src/paypal/editor.scss"
/*!********************************!*\
  !*** ./src/paypal/editor.scss ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./src/paypal/index.js"
/*!*****************************!*\
  !*** ./src/paypal/index.js ***!
  \*****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/paypal/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/paypal/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./save */ "./src/paypal/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./block.json */ "./src/paypal/block.json");
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

/***/ "./src/paypal/paypal-logo.svg"
/*!************************************!*\
  !*** ./src/paypal/paypal-logo.svg ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ReactComponent: () => (/* binding */ SvgPaypalLogo),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
var _path, _path2;
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }

var SvgPaypalLogo = function SvgPaypalLogo(props) {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0__.createElement("svg", _extends({
    xmlns: "http://www.w3.org/2000/svg",
    width: 101,
    height: 32,
    preserveAspectRatio: "xMinYMin meet"
  }, props), _path || (_path = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0__.createElement("path", {
    fill: "#003087",
    d: "M12.237 2.8h-7.8c-.5 0-1 .4-1.1.9l-3.1 20c-.1.4.2.7.6.7h3.7c.5 0 1-.4 1.1-.9l.8-5.4c.1-.5.5-.9 1.1-.9h2.5c5.1 0 8.1-2.5 8.9-7.4.3-2.1 0-3.8-1-5-1.1-1.3-3.1-2-5.7-2m.9 7.3c-.4 2.8-2.6 2.8-4.6 2.8h-1.2l.8-5.2c0-.3.3-.5.6-.5h.5c1.4 0 2.7 0 3.4.8.5.4.7 1.1.5 2.1M35.437 10h-3.7c-.3 0-.6.2-.6.5l-.2 1-.3-.4c-.8-1.2-2.6-1.6-4.4-1.6-4.1 0-7.6 3.1-8.3 7.5-.4 2.2.1 4.3 1.4 5.7 1.1 1.3 2.8 1.9 4.7 1.9 3.3 0 5.2-2.1 5.2-2.1l-.2 1c-.1.4.2.8.6.8h3.4c.5 0 1-.4 1.1-.9l2-12.8c.1-.2-.3-.6-.7-.6m-5.1 7.2c-.4 2.1-2 3.6-4.2 3.6-1.1 0-1.9-.3-2.5-1s-.8-1.6-.6-2.6c.3-2.1 2.1-3.6 4.2-3.6 1.1 0 1.9.4 2.5 1 .5.7.7 1.6.6 2.6M55.337 10h-3.7c-.4 0-.7.2-.9.5l-5.2 7.6-2.2-7.3c-.1-.5-.6-.8-1-.8h-3.7c-.4 0-.8.4-.6.9l4.1 12.1-3.9 5.4c-.3.4 0 1 .5 1h3.7c.4 0 .7-.2.9-.5l12.5-18c.3-.3 0-.9-.5-.9"
  })), _path2 || (_path2 = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0__.createElement("path", {
    fill: "#009cde",
    d: "M67.737 2.8h-7.8c-.5 0-1 .4-1.1.9l-3.1 19.9c-.1.4.2.7.6.7h4c.4 0 .7-.3.7-.6l.9-5.7c.1-.5.5-.9 1.1-.9h2.5c5.1 0 8.1-2.5 8.9-7.4.3-2.1 0-3.8-1-5-1.2-1.2-3.1-1.9-5.7-1.9m.9 7.3c-.4 2.8-2.6 2.8-4.6 2.8h-1.2l.8-5.2c0-.3.3-.5.6-.5h.5c1.4 0 2.7 0 3.4.8.5.4.6 1.1.5 2.1M90.937 10h-3.7c-.3 0-.6.2-.6.5l-.2 1-.3-.4c-.8-1.2-2.6-1.6-4.4-1.6-4.1 0-7.6 3.1-8.3 7.5-.4 2.2.1 4.3 1.4 5.7 1.1 1.3 2.8 1.9 4.7 1.9 3.3 0 5.2-2.1 5.2-2.1l-.2 1c-.1.4.2.8.6.8h3.4c.5 0 1-.4 1.1-.9l2-12.8c0-.2-.3-.6-.7-.6m-5.2 7.2c-.4 2.1-2 3.6-4.2 3.6-1.1 0-1.9-.3-2.5-1s-.8-1.6-.6-2.6c.3-2.1 2.1-3.6 4.2-3.6 1.1 0 1.9.4 2.5 1 .6.7.8 1.6.6 2.6M95.337 3.3l-3.2 20.3c-.1.4.2.7.6.7h3.2c.5 0 1-.4 1.1-.9l3.2-19.9c.1-.4-.2-.7-.6-.7h-3.6c-.4 0-.6.2-.7.5"
  })));
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAxcHgiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAxMDEgMzIiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaW5ZTWluIG1lZXQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZmlsbD0iIzAwMzA4NyIgZD0iTSAxMi4yMzcgMi44IEwgNC40MzcgMi44IEMgMy45MzcgMi44IDMuNDM3IDMuMiAzLjMzNyAzLjcgTCAwLjIzNyAyMy43IEMgMC4xMzcgMjQuMSAwLjQzNyAyNC40IDAuODM3IDI0LjQgTCA0LjUzNyAyNC40IEMgNS4wMzcgMjQuNCA1LjUzNyAyNCA1LjYzNyAyMy41IEwgNi40MzcgMTguMSBDIDYuNTM3IDE3LjYgNi45MzcgMTcuMiA3LjUzNyAxNy4yIEwgMTAuMDM3IDE3LjIgQyAxNS4xMzcgMTcuMiAxOC4xMzcgMTQuNyAxOC45MzcgOS44IEMgMTkuMjM3IDcuNyAxOC45MzcgNiAxNy45MzcgNC44IEMgMTYuODM3IDMuNSAxNC44MzcgMi44IDEyLjIzNyAyLjggWiBNIDEzLjEzNyAxMC4xIEMgMTIuNzM3IDEyLjkgMTAuNTM3IDEyLjkgOC41MzcgMTIuOSBMIDcuMzM3IDEyLjkgTCA4LjEzNyA3LjcgQyA4LjEzNyA3LjQgOC40MzcgNy4yIDguNzM3IDcuMiBMIDkuMjM3IDcuMiBDIDEwLjYzNyA3LjIgMTEuOTM3IDcuMiAxMi42MzcgOCBDIDEzLjEzNyA4LjQgMTMuMzM3IDkuMSAxMy4xMzcgMTAuMSBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwMzA4NyIgZD0iTSAzNS40MzcgMTAgTCAzMS43MzcgMTAgQyAzMS40MzcgMTAgMzEuMTM3IDEwLjIgMzEuMTM3IDEwLjUgTCAzMC45MzcgMTEuNSBMIDMwLjYzNyAxMS4xIEMgMjkuODM3IDkuOSAyOC4wMzcgOS41IDI2LjIzNyA5LjUgQyAyMi4xMzcgOS41IDE4LjYzNyAxMi42IDE3LjkzNyAxNyBDIDE3LjUzNyAxOS4yIDE4LjAzNyAyMS4zIDE5LjMzNyAyMi43IEMgMjAuNDM3IDI0IDIyLjEzNyAyNC42IDI0LjAzNyAyNC42IEMgMjcuMzM3IDI0LjYgMjkuMjM3IDIyLjUgMjkuMjM3IDIyLjUgTCAyOS4wMzcgMjMuNSBDIDI4LjkzNyAyMy45IDI5LjIzNyAyNC4zIDI5LjYzNyAyNC4zIEwgMzMuMDM3IDI0LjMgQyAzMy41MzcgMjQuMyAzNC4wMzcgMjMuOSAzNC4xMzcgMjMuNCBMIDM2LjEzNyAxMC42IEMgMzYuMjM3IDEwLjQgMzUuODM3IDEwIDM1LjQzNyAxMCBaIE0gMzAuMzM3IDE3LjIgQyAyOS45MzcgMTkuMyAyOC4zMzcgMjAuOCAyNi4xMzcgMjAuOCBDIDI1LjAzNyAyMC44IDI0LjIzNyAyMC41IDIzLjYzNyAxOS44IEMgMjMuMDM3IDE5LjEgMjIuODM3IDE4LjIgMjMuMDM3IDE3LjIgQyAyMy4zMzcgMTUuMSAyNS4xMzcgMTMuNiAyNy4yMzcgMTMuNiBDIDI4LjMzNyAxMy42IDI5LjEzNyAxNCAyOS43MzcgMTQuNiBDIDMwLjIzNyAxNS4zIDMwLjQzNyAxNi4yIDMwLjMzNyAxNy4yIFoiPjwvcGF0aD48cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNIDU1LjMzNyAxMCBMIDUxLjYzNyAxMCBDIDUxLjIzNyAxMCA1MC45MzcgMTAuMiA1MC43MzcgMTAuNSBMIDQ1LjUzNyAxOC4xIEwgNDMuMzM3IDEwLjggQyA0My4yMzcgMTAuMyA0Mi43MzcgMTAgNDIuMzM3IDEwIEwgMzguNjM3IDEwIEMgMzguMjM3IDEwIDM3LjgzNyAxMC40IDM4LjAzNyAxMC45IEwgNDIuMTM3IDIzIEwgMzguMjM3IDI4LjQgQyAzNy45MzcgMjguOCAzOC4yMzcgMjkuNCAzOC43MzcgMjkuNCBMIDQyLjQzNyAyOS40IEMgNDIuODM3IDI5LjQgNDMuMTM3IDI5LjIgNDMuMzM3IDI4LjkgTCA1NS44MzcgMTAuOSBDIDU2LjEzNyAxMC42IDU1LjgzNyAxMCA1NS4zMzcgMTAgWiI+PC9wYXRoPjxwYXRoIGZpbGw9IiMwMDljZGUiIGQ9Ik0gNjcuNzM3IDIuOCBMIDU5LjkzNyAyLjggQyA1OS40MzcgMi44IDU4LjkzNyAzLjIgNTguODM3IDMuNyBMIDU1LjczNyAyMy42IEMgNTUuNjM3IDI0IDU1LjkzNyAyNC4zIDU2LjMzNyAyNC4zIEwgNjAuMzM3IDI0LjMgQyA2MC43MzcgMjQuMyA2MS4wMzcgMjQgNjEuMDM3IDIzLjcgTCA2MS45MzcgMTggQyA2Mi4wMzcgMTcuNSA2Mi40MzcgMTcuMSA2My4wMzcgMTcuMSBMIDY1LjUzNyAxNy4xIEMgNzAuNjM3IDE3LjEgNzMuNjM3IDE0LjYgNzQuNDM3IDkuNyBDIDc0LjczNyA3LjYgNzQuNDM3IDUuOSA3My40MzcgNC43IEMgNzIuMjM3IDMuNSA3MC4zMzcgMi44IDY3LjczNyAyLjggWiBNIDY4LjYzNyAxMC4xIEMgNjguMjM3IDEyLjkgNjYuMDM3IDEyLjkgNjQuMDM3IDEyLjkgTCA2Mi44MzcgMTIuOSBMIDYzLjYzNyA3LjcgQyA2My42MzcgNy40IDYzLjkzNyA3LjIgNjQuMjM3IDcuMiBMIDY0LjczNyA3LjIgQyA2Ni4xMzcgNy4yIDY3LjQzNyA3LjIgNjguMTM3IDggQyA2OC42MzcgOC40IDY4LjczNyA5LjEgNjguNjM3IDEwLjEgWiI+PC9wYXRoPjxwYXRoIGZpbGw9IiMwMDljZGUiIGQ9Ik0gOTAuOTM3IDEwIEwgODcuMjM3IDEwIEMgODYuOTM3IDEwIDg2LjYzNyAxMC4yIDg2LjYzNyAxMC41IEwgODYuNDM3IDExLjUgTCA4Ni4xMzcgMTEuMSBDIDg1LjMzNyA5LjkgODMuNTM3IDkuNSA4MS43MzcgOS41IEMgNzcuNjM3IDkuNSA3NC4xMzcgMTIuNiA3My40MzcgMTcgQyA3My4wMzcgMTkuMiA3My41MzcgMjEuMyA3NC44MzcgMjIuNyBDIDc1LjkzNyAyNCA3Ny42MzcgMjQuNiA3OS41MzcgMjQuNiBDIDgyLjgzNyAyNC42IDg0LjczNyAyMi41IDg0LjczNyAyMi41IEwgODQuNTM3IDIzLjUgQyA4NC40MzcgMjMuOSA4NC43MzcgMjQuMyA4NS4xMzcgMjQuMyBMIDg4LjUzNyAyNC4zIEMgODkuMDM3IDI0LjMgODkuNTM3IDIzLjkgODkuNjM3IDIzLjQgTCA5MS42MzcgMTAuNiBDIDkxLjYzNyAxMC40IDkxLjMzNyAxMCA5MC45MzcgMTAgWiBNIDg1LjczNyAxNy4yIEMgODUuMzM3IDE5LjMgODMuNzM3IDIwLjggODEuNTM3IDIwLjggQyA4MC40MzcgMjAuOCA3OS42MzcgMjAuNSA3OS4wMzcgMTkuOCBDIDc4LjQzNyAxOS4xIDc4LjIzNyAxOC4yIDc4LjQzNyAxNy4yIEMgNzguNzM3IDE1LjEgODAuNTM3IDEzLjYgODIuNjM3IDEzLjYgQyA4My43MzcgMTMuNiA4NC41MzcgMTQgODUuMTM3IDE0LjYgQyA4NS43MzcgMTUuMyA4NS45MzcgMTYuMiA4NS43MzcgMTcuMiBaIj48L3BhdGg+PHBhdGggZmlsbD0iIzAwOWNkZSIgZD0iTSA5NS4zMzcgMy4zIEwgOTIuMTM3IDIzLjYgQyA5Mi4wMzcgMjQgOTIuMzM3IDI0LjMgOTIuNzM3IDI0LjMgTCA5NS45MzcgMjQuMyBDIDk2LjQzNyAyNC4zIDk2LjkzNyAyMy45IDk3LjAzNyAyMy40IEwgMTAwLjIzNyAzLjUgQyAxMDAuMzM3IDMuMSAxMDAuMDM3IDIuOCA5OS42MzcgMi44IEwgOTYuMDM3IDIuOCBDIDk1LjYzNyAyLjggOTUuNDM3IDMgOTUuMzM3IDMuMyBaIj48L3BhdGg+PC9zdmc+DQo=");

/***/ },

/***/ "./src/paypal/save.js"
/*!****************************!*\
  !*** ./src/paypal/save.js ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__);

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
const {
  InnerBlocks
} = wp.blockEditor;

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
function save({
  className
}) {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: className,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(InnerBlocks.Content, {})
  });
}

/***/ },

/***/ "./src/paypal/style.scss"
/*!*******************************!*\
  !*** ./src/paypal/style.scss ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

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
/******/ 			"paypal/index": 0,
/******/ 			"paypal/style-index": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["paypal/style-index"], () => (__webpack_require__("./src/paypal/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map