/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/axios/index.js":
/*!*************************************!*\
  !*** ./node_modules/axios/index.js ***!
  \*************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__(/*! ./lib/axios */ "./node_modules/axios/lib/axios.js");

/***/ }),

/***/ "./node_modules/axios/lib/adapters/xhr.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/adapters/xhr.js ***!
  \************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var settle = __webpack_require__(/*! ./../core/settle */ "./node_modules/axios/lib/core/settle.js");
var cookies = __webpack_require__(/*! ./../helpers/cookies */ "./node_modules/axios/lib/helpers/cookies.js");
var buildURL = __webpack_require__(/*! ./../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var buildFullPath = __webpack_require__(/*! ../core/buildFullPath */ "./node_modules/axios/lib/core/buildFullPath.js");
var parseHeaders = __webpack_require__(/*! ./../helpers/parseHeaders */ "./node_modules/axios/lib/helpers/parseHeaders.js");
var isURLSameOrigin = __webpack_require__(/*! ./../helpers/isURLSameOrigin */ "./node_modules/axios/lib/helpers/isURLSameOrigin.js");
var createError = __webpack_require__(/*! ../core/createError */ "./node_modules/axios/lib/core/createError.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

module.exports = function xhrAdapter(config) {
  return new Promise(function dispatchXhrRequest(resolve, reject) {
    var requestData = config.data;
    var requestHeaders = config.headers;
    var responseType = config.responseType;
    var onCanceled;
    function done() {
      if (config.cancelToken) {
        config.cancelToken.unsubscribe(onCanceled);
      }

      if (config.signal) {
        config.signal.removeEventListener('abort', onCanceled);
      }
    }

    if (utils.isFormData(requestData)) {
      delete requestHeaders['Content-Type']; // Let the browser set it
    }

    var request = new XMLHttpRequest();

    // HTTP basic authentication
    if (config.auth) {
      var username = config.auth.username || '';
      var password = config.auth.password ? unescape(encodeURIComponent(config.auth.password)) : '';
      requestHeaders.Authorization = 'Basic ' + btoa(username + ':' + password);
    }

    var fullPath = buildFullPath(config.baseURL, config.url);
    request.open(config.method.toUpperCase(), buildURL(fullPath, config.params, config.paramsSerializer), true);

    // Set the request timeout in MS
    request.timeout = config.timeout;

    function onloadend() {
      if (!request) {
        return;
      }
      // Prepare the response
      var responseHeaders = 'getAllResponseHeaders' in request ? parseHeaders(request.getAllResponseHeaders()) : null;
      var responseData = !responseType || responseType === 'text' ||  responseType === 'json' ?
        request.responseText : request.response;
      var response = {
        data: responseData,
        status: request.status,
        statusText: request.statusText,
        headers: responseHeaders,
        config: config,
        request: request
      };

      settle(function _resolve(value) {
        resolve(value);
        done();
      }, function _reject(err) {
        reject(err);
        done();
      }, response);

      // Clean up request
      request = null;
    }

    if ('onloadend' in request) {
      // Use onloadend if available
      request.onloadend = onloadend;
    } else {
      // Listen for ready state to emulate onloadend
      request.onreadystatechange = function handleLoad() {
        if (!request || request.readyState !== 4) {
          return;
        }

        // The request errored out and we didn't get a response, this will be
        // handled by onerror instead
        // With one exception: request that using file: protocol, most browsers
        // will return status as 0 even though it's a successful request
        if (request.status === 0 && !(request.responseURL && request.responseURL.indexOf('file:') === 0)) {
          return;
        }
        // readystate handler is calling before onerror or ontimeout handlers,
        // so we should call onloadend on the next 'tick'
        setTimeout(onloadend);
      };
    }

    // Handle browser request cancellation (as opposed to a manual cancellation)
    request.onabort = function handleAbort() {
      if (!request) {
        return;
      }

      reject(createError('Request aborted', config, 'ECONNABORTED', request));

      // Clean up request
      request = null;
    };

    // Handle low level network errors
    request.onerror = function handleError() {
      // Real errors are hidden from us by the browser
      // onerror should only fire if it's a network error
      reject(createError('Network Error', config, null, request));

      // Clean up request
      request = null;
    };

    // Handle timeout
    request.ontimeout = function handleTimeout() {
      var timeoutErrorMessage = config.timeout ? 'timeout of ' + config.timeout + 'ms exceeded' : 'timeout exceeded';
      var transitional = config.transitional || defaults.transitional;
      if (config.timeoutErrorMessage) {
        timeoutErrorMessage = config.timeoutErrorMessage;
      }
      reject(createError(
        timeoutErrorMessage,
        config,
        transitional.clarifyTimeoutError ? 'ETIMEDOUT' : 'ECONNABORTED',
        request));

      // Clean up request
      request = null;
    };

    // Add xsrf header
    // This is only done if running in a standard browser environment.
    // Specifically not if we're in a web worker, or react-native.
    if (utils.isStandardBrowserEnv()) {
      // Add xsrf header
      var xsrfValue = (config.withCredentials || isURLSameOrigin(fullPath)) && config.xsrfCookieName ?
        cookies.read(config.xsrfCookieName) :
        undefined;

      if (xsrfValue) {
        requestHeaders[config.xsrfHeaderName] = xsrfValue;
      }
    }

    // Add headers to the request
    if ('setRequestHeader' in request) {
      utils.forEach(requestHeaders, function setRequestHeader(val, key) {
        if (typeof requestData === 'undefined' && key.toLowerCase() === 'content-type') {
          // Remove Content-Type if data is undefined
          delete requestHeaders[key];
        } else {
          // Otherwise add header to the request
          request.setRequestHeader(key, val);
        }
      });
    }

    // Add withCredentials to request if needed
    if (!utils.isUndefined(config.withCredentials)) {
      request.withCredentials = !!config.withCredentials;
    }

    // Add responseType to request if needed
    if (responseType && responseType !== 'json') {
      request.responseType = config.responseType;
    }

    // Handle progress if needed
    if (typeof config.onDownloadProgress === 'function') {
      request.addEventListener('progress', config.onDownloadProgress);
    }

    // Not all browsers support upload events
    if (typeof config.onUploadProgress === 'function' && request.upload) {
      request.upload.addEventListener('progress', config.onUploadProgress);
    }

    if (config.cancelToken || config.signal) {
      // Handle cancellation
      // eslint-disable-next-line func-names
      onCanceled = function(cancel) {
        if (!request) {
          return;
        }
        reject(!cancel || (cancel && cancel.type) ? new Cancel('canceled') : cancel);
        request.abort();
        request = null;
      };

      config.cancelToken && config.cancelToken.subscribe(onCanceled);
      if (config.signal) {
        config.signal.aborted ? onCanceled() : config.signal.addEventListener('abort', onCanceled);
      }
    }

    if (!requestData) {
      requestData = null;
    }

    // Send the request
    request.send(requestData);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/axios.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/axios.js ***!
  \*****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");
var Axios = __webpack_require__(/*! ./core/Axios */ "./node_modules/axios/lib/core/Axios.js");
var mergeConfig = __webpack_require__(/*! ./core/mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var defaults = __webpack_require__(/*! ./defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Create an instance of Axios
 *
 * @param {Object} defaultConfig The default config for the instance
 * @return {Axios} A new instance of Axios
 */
function createInstance(defaultConfig) {
  var context = new Axios(defaultConfig);
  var instance = bind(Axios.prototype.request, context);

  // Copy axios.prototype to instance
  utils.extend(instance, Axios.prototype, context);

  // Copy context to instance
  utils.extend(instance, context);

  // Factory for creating new instances
  instance.create = function create(instanceConfig) {
    return createInstance(mergeConfig(defaultConfig, instanceConfig));
  };

  return instance;
}

// Create the default instance to be exported
var axios = createInstance(defaults);

// Expose Axios class to allow class inheritance
axios.Axios = Axios;

// Expose Cancel & CancelToken
axios.Cancel = __webpack_require__(/*! ./cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");
axios.CancelToken = __webpack_require__(/*! ./cancel/CancelToken */ "./node_modules/axios/lib/cancel/CancelToken.js");
axios.isCancel = __webpack_require__(/*! ./cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
axios.VERSION = (__webpack_require__(/*! ./env/data */ "./node_modules/axios/lib/env/data.js").version);

// Expose all/spread
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = __webpack_require__(/*! ./helpers/spread */ "./node_modules/axios/lib/helpers/spread.js");

// Expose isAxiosError
axios.isAxiosError = __webpack_require__(/*! ./helpers/isAxiosError */ "./node_modules/axios/lib/helpers/isAxiosError.js");

module.exports = axios;

// Allow use of default import syntax in TypeScript
module.exports["default"] = axios;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/Cancel.js":
/*!*************************************************!*\
  !*** ./node_modules/axios/lib/cancel/Cancel.js ***!
  \*************************************************/
/***/ ((module) => {

"use strict";


/**
 * A `Cancel` is an object that is thrown when an operation is canceled.
 *
 * @class
 * @param {string=} message The message.
 */
function Cancel(message) {
  this.message = message;
}

Cancel.prototype.toString = function toString() {
  return 'Cancel' + (this.message ? ': ' + this.message : '');
};

Cancel.prototype.__CANCEL__ = true;

module.exports = Cancel;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/CancelToken.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/cancel/CancelToken.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var Cancel = __webpack_require__(/*! ./Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

/**
 * A `CancelToken` is an object that can be used to request cancellation of an operation.
 *
 * @class
 * @param {Function} executor The executor function.
 */
function CancelToken(executor) {
  if (typeof executor !== 'function') {
    throw new TypeError('executor must be a function.');
  }

  var resolvePromise;

  this.promise = new Promise(function promiseExecutor(resolve) {
    resolvePromise = resolve;
  });

  var token = this;

  // eslint-disable-next-line func-names
  this.promise.then(function(cancel) {
    if (!token._listeners) return;

    var i;
    var l = token._listeners.length;

    for (i = 0; i < l; i++) {
      token._listeners[i](cancel);
    }
    token._listeners = null;
  });

  // eslint-disable-next-line func-names
  this.promise.then = function(onfulfilled) {
    var _resolve;
    // eslint-disable-next-line func-names
    var promise = new Promise(function(resolve) {
      token.subscribe(resolve);
      _resolve = resolve;
    }).then(onfulfilled);

    promise.cancel = function reject() {
      token.unsubscribe(_resolve);
    };

    return promise;
  };

  executor(function cancel(message) {
    if (token.reason) {
      // Cancellation has already been requested
      return;
    }

    token.reason = new Cancel(message);
    resolvePromise(token.reason);
  });
}

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
CancelToken.prototype.throwIfRequested = function throwIfRequested() {
  if (this.reason) {
    throw this.reason;
  }
};

/**
 * Subscribe to the cancel signal
 */

CancelToken.prototype.subscribe = function subscribe(listener) {
  if (this.reason) {
    listener(this.reason);
    return;
  }

  if (this._listeners) {
    this._listeners.push(listener);
  } else {
    this._listeners = [listener];
  }
};

/**
 * Unsubscribe from the cancel signal
 */

CancelToken.prototype.unsubscribe = function unsubscribe(listener) {
  if (!this._listeners) {
    return;
  }
  var index = this._listeners.indexOf(listener);
  if (index !== -1) {
    this._listeners.splice(index, 1);
  }
};

/**
 * Returns an object that contains a new `CancelToken` and a function that, when called,
 * cancels the `CancelToken`.
 */
CancelToken.source = function source() {
  var cancel;
  var token = new CancelToken(function executor(c) {
    cancel = c;
  });
  return {
    token: token,
    cancel: cancel
  };
};

module.exports = CancelToken;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/isCancel.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/cancel/isCancel.js ***!
  \***************************************************/
/***/ ((module) => {

"use strict";


module.exports = function isCancel(value) {
  return !!(value && value.__CANCEL__);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/Axios.js":
/*!**********************************************!*\
  !*** ./node_modules/axios/lib/core/Axios.js ***!
  \**********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var buildURL = __webpack_require__(/*! ../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var InterceptorManager = __webpack_require__(/*! ./InterceptorManager */ "./node_modules/axios/lib/core/InterceptorManager.js");
var dispatchRequest = __webpack_require__(/*! ./dispatchRequest */ "./node_modules/axios/lib/core/dispatchRequest.js");
var mergeConfig = __webpack_require__(/*! ./mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var validator = __webpack_require__(/*! ../helpers/validator */ "./node_modules/axios/lib/helpers/validator.js");

var validators = validator.validators;
/**
 * Create a new instance of Axios
 *
 * @param {Object} instanceConfig The default config for the instance
 */
function Axios(instanceConfig) {
  this.defaults = instanceConfig;
  this.interceptors = {
    request: new InterceptorManager(),
    response: new InterceptorManager()
  };
}

/**
 * Dispatch a request
 *
 * @param {Object} config The config specific for this request (merged with this.defaults)
 */
Axios.prototype.request = function request(configOrUrl, config) {
  /*eslint no-param-reassign:0*/
  // Allow for axios('example/url'[, config]) a la fetch API
  if (typeof configOrUrl === 'string') {
    config = config || {};
    config.url = configOrUrl;
  } else {
    config = configOrUrl || {};
  }

  if (!config.url) {
    throw new Error('Provided config url is not valid');
  }

  config = mergeConfig(this.defaults, config);

  // Set config.method
  if (config.method) {
    config.method = config.method.toLowerCase();
  } else if (this.defaults.method) {
    config.method = this.defaults.method.toLowerCase();
  } else {
    config.method = 'get';
  }

  var transitional = config.transitional;

  if (transitional !== undefined) {
    validator.assertOptions(transitional, {
      silentJSONParsing: validators.transitional(validators.boolean),
      forcedJSONParsing: validators.transitional(validators.boolean),
      clarifyTimeoutError: validators.transitional(validators.boolean)
    }, false);
  }

  // filter out skipped interceptors
  var requestInterceptorChain = [];
  var synchronousRequestInterceptors = true;
  this.interceptors.request.forEach(function unshiftRequestInterceptors(interceptor) {
    if (typeof interceptor.runWhen === 'function' && interceptor.runWhen(config) === false) {
      return;
    }

    synchronousRequestInterceptors = synchronousRequestInterceptors && interceptor.synchronous;

    requestInterceptorChain.unshift(interceptor.fulfilled, interceptor.rejected);
  });

  var responseInterceptorChain = [];
  this.interceptors.response.forEach(function pushResponseInterceptors(interceptor) {
    responseInterceptorChain.push(interceptor.fulfilled, interceptor.rejected);
  });

  var promise;

  if (!synchronousRequestInterceptors) {
    var chain = [dispatchRequest, undefined];

    Array.prototype.unshift.apply(chain, requestInterceptorChain);
    chain = chain.concat(responseInterceptorChain);

    promise = Promise.resolve(config);
    while (chain.length) {
      promise = promise.then(chain.shift(), chain.shift());
    }

    return promise;
  }


  var newConfig = config;
  while (requestInterceptorChain.length) {
    var onFulfilled = requestInterceptorChain.shift();
    var onRejected = requestInterceptorChain.shift();
    try {
      newConfig = onFulfilled(newConfig);
    } catch (error) {
      onRejected(error);
      break;
    }
  }

  try {
    promise = dispatchRequest(newConfig);
  } catch (error) {
    return Promise.reject(error);
  }

  while (responseInterceptorChain.length) {
    promise = promise.then(responseInterceptorChain.shift(), responseInterceptorChain.shift());
  }

  return promise;
};

Axios.prototype.getUri = function getUri(config) {
  if (!config.url) {
    throw new Error('Provided config url is not valid');
  }
  config = mergeConfig(this.defaults, config);
  return buildURL(config.url, config.params, config.paramsSerializer).replace(/^\?/, '');
};

// Provide aliases for supported request methods
utils.forEach(['delete', 'get', 'head', 'options'], function forEachMethodNoData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: (config || {}).data
    }));
  };
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, data, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: data
    }));
  };
});

module.exports = Axios;


/***/ }),

/***/ "./node_modules/axios/lib/core/InterceptorManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/core/InterceptorManager.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function InterceptorManager() {
  this.handlers = [];
}

/**
 * Add a new interceptor to the stack
 *
 * @param {Function} fulfilled The function to handle `then` for a `Promise`
 * @param {Function} rejected The function to handle `reject` for a `Promise`
 *
 * @return {Number} An ID used to remove interceptor later
 */
InterceptorManager.prototype.use = function use(fulfilled, rejected, options) {
  this.handlers.push({
    fulfilled: fulfilled,
    rejected: rejected,
    synchronous: options ? options.synchronous : false,
    runWhen: options ? options.runWhen : null
  });
  return this.handlers.length - 1;
};

/**
 * Remove an interceptor from the stack
 *
 * @param {Number} id The ID that was returned by `use`
 */
InterceptorManager.prototype.eject = function eject(id) {
  if (this.handlers[id]) {
    this.handlers[id] = null;
  }
};

/**
 * Iterate over all the registered interceptors
 *
 * This method is particularly useful for skipping over any
 * interceptors that may have become `null` calling `eject`.
 *
 * @param {Function} fn The function to call for each interceptor
 */
InterceptorManager.prototype.forEach = function forEach(fn) {
  utils.forEach(this.handlers, function forEachHandler(h) {
    if (h !== null) {
      fn(h);
    }
  });
};

module.exports = InterceptorManager;


/***/ }),

/***/ "./node_modules/axios/lib/core/buildFullPath.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/buildFullPath.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var isAbsoluteURL = __webpack_require__(/*! ../helpers/isAbsoluteURL */ "./node_modules/axios/lib/helpers/isAbsoluteURL.js");
var combineURLs = __webpack_require__(/*! ../helpers/combineURLs */ "./node_modules/axios/lib/helpers/combineURLs.js");

/**
 * Creates a new URL by combining the baseURL with the requestedURL,
 * only when the requestedURL is not already an absolute URL.
 * If the requestURL is absolute, this function returns the requestedURL untouched.
 *
 * @param {string} baseURL The base URL
 * @param {string} requestedURL Absolute or relative URL to combine
 * @returns {string} The combined full path
 */
module.exports = function buildFullPath(baseURL, requestedURL) {
  if (baseURL && !isAbsoluteURL(requestedURL)) {
    return combineURLs(baseURL, requestedURL);
  }
  return requestedURL;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/createError.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/createError.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var enhanceError = __webpack_require__(/*! ./enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

/**
 * Create an Error with the specified message, config, error code, request and response.
 *
 * @param {string} message The error message.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The created error.
 */
module.exports = function createError(message, config, code, request, response) {
  var error = new Error(message);
  return enhanceError(error, config, code, request, response);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/dispatchRequest.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/core/dispatchRequest.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var transformData = __webpack_require__(/*! ./transformData */ "./node_modules/axios/lib/core/transformData.js");
var isCancel = __webpack_require__(/*! ../cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
function throwIfCancellationRequested(config) {
  if (config.cancelToken) {
    config.cancelToken.throwIfRequested();
  }

  if (config.signal && config.signal.aborted) {
    throw new Cancel('canceled');
  }
}

/**
 * Dispatch a request to the server using the configured adapter.
 *
 * @param {object} config The config that is to be used for the request
 * @returns {Promise} The Promise to be fulfilled
 */
module.exports = function dispatchRequest(config) {
  throwIfCancellationRequested(config);

  // Ensure headers exist
  config.headers = config.headers || {};

  // Transform request data
  config.data = transformData.call(
    config,
    config.data,
    config.headers,
    config.transformRequest
  );

  // Flatten headers
  config.headers = utils.merge(
    config.headers.common || {},
    config.headers[config.method] || {},
    config.headers
  );

  utils.forEach(
    ['delete', 'get', 'head', 'post', 'put', 'patch', 'common'],
    function cleanHeaderConfig(method) {
      delete config.headers[method];
    }
  );

  var adapter = config.adapter || defaults.adapter;

  return adapter(config).then(function onAdapterResolution(response) {
    throwIfCancellationRequested(config);

    // Transform response data
    response.data = transformData.call(
      config,
      response.data,
      response.headers,
      config.transformResponse
    );

    return response;
  }, function onAdapterRejection(reason) {
    if (!isCancel(reason)) {
      throwIfCancellationRequested(config);

      // Transform response data
      if (reason && reason.response) {
        reason.response.data = transformData.call(
          config,
          reason.response.data,
          reason.response.headers,
          config.transformResponse
        );
      }
    }

    return Promise.reject(reason);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/core/enhanceError.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/core/enhanceError.js ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";


/**
 * Update an Error with the specified config, error code, and response.
 *
 * @param {Error} error The error to update.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The error.
 */
module.exports = function enhanceError(error, config, code, request, response) {
  error.config = config;
  if (code) {
    error.code = code;
  }

  error.request = request;
  error.response = response;
  error.isAxiosError = true;

  error.toJSON = function toJSON() {
    return {
      // Standard
      message: this.message,
      name: this.name,
      // Microsoft
      description: this.description,
      number: this.number,
      // Mozilla
      fileName: this.fileName,
      lineNumber: this.lineNumber,
      columnNumber: this.columnNumber,
      stack: this.stack,
      // Axios
      config: this.config,
      code: this.code,
      status: this.response && this.response.status ? this.response.status : null
    };
  };
  return error;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/mergeConfig.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/mergeConfig.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

/**
 * Config-specific merge-function which creates a new config-object
 * by merging two configuration objects together.
 *
 * @param {Object} config1
 * @param {Object} config2
 * @returns {Object} New object resulting from merging config2 to config1
 */
module.exports = function mergeConfig(config1, config2) {
  // eslint-disable-next-line no-param-reassign
  config2 = config2 || {};
  var config = {};

  function getMergedValue(target, source) {
    if (utils.isPlainObject(target) && utils.isPlainObject(source)) {
      return utils.merge(target, source);
    } else if (utils.isPlainObject(source)) {
      return utils.merge({}, source);
    } else if (utils.isArray(source)) {
      return source.slice();
    }
    return source;
  }

  // eslint-disable-next-line consistent-return
  function mergeDeepProperties(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(config1[prop], config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function valueFromConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(undefined, config2[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function defaultToConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(undefined, config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function mergeDirectKeys(prop) {
    if (prop in config2) {
      return getMergedValue(config1[prop], config2[prop]);
    } else if (prop in config1) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  var mergeMap = {
    'url': valueFromConfig2,
    'method': valueFromConfig2,
    'data': valueFromConfig2,
    'baseURL': defaultToConfig2,
    'transformRequest': defaultToConfig2,
    'transformResponse': defaultToConfig2,
    'paramsSerializer': defaultToConfig2,
    'timeout': defaultToConfig2,
    'timeoutMessage': defaultToConfig2,
    'withCredentials': defaultToConfig2,
    'adapter': defaultToConfig2,
    'responseType': defaultToConfig2,
    'xsrfCookieName': defaultToConfig2,
    'xsrfHeaderName': defaultToConfig2,
    'onUploadProgress': defaultToConfig2,
    'onDownloadProgress': defaultToConfig2,
    'decompress': defaultToConfig2,
    'maxContentLength': defaultToConfig2,
    'maxBodyLength': defaultToConfig2,
    'transport': defaultToConfig2,
    'httpAgent': defaultToConfig2,
    'httpsAgent': defaultToConfig2,
    'cancelToken': defaultToConfig2,
    'socketPath': defaultToConfig2,
    'responseEncoding': defaultToConfig2,
    'validateStatus': mergeDirectKeys
  };

  utils.forEach(Object.keys(config1).concat(Object.keys(config2)), function computeConfigValue(prop) {
    var merge = mergeMap[prop] || mergeDeepProperties;
    var configValue = merge(prop);
    (utils.isUndefined(configValue) && merge !== mergeDirectKeys) || (config[prop] = configValue);
  });

  return config;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/settle.js":
/*!***********************************************!*\
  !*** ./node_modules/axios/lib/core/settle.js ***!
  \***********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var createError = __webpack_require__(/*! ./createError */ "./node_modules/axios/lib/core/createError.js");

/**
 * Resolve or reject a Promise based on response status.
 *
 * @param {Function} resolve A function that resolves the promise.
 * @param {Function} reject A function that rejects the promise.
 * @param {object} response The response.
 */
module.exports = function settle(resolve, reject, response) {
  var validateStatus = response.config.validateStatus;
  if (!response.status || !validateStatus || validateStatus(response.status)) {
    resolve(response);
  } else {
    reject(createError(
      'Request failed with status code ' + response.status,
      response.config,
      null,
      response.request,
      response
    ));
  }
};


/***/ }),

/***/ "./node_modules/axios/lib/core/transformData.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/transformData.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var defaults = __webpack_require__(/*! ./../defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Transform the data for a request or a response
 *
 * @param {Object|String} data The data to be transformed
 * @param {Array} headers The headers for the request or response
 * @param {Array|Function} fns A single function or Array of functions
 * @returns {*} The resulting transformed data
 */
module.exports = function transformData(data, headers, fns) {
  var context = this || defaults;
  /*eslint no-param-reassign:0*/
  utils.forEach(fns, function transform(fn) {
    data = fn.call(context, data, headers);
  });

  return data;
};


/***/ }),

/***/ "./node_modules/axios/lib/defaults.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/defaults.js ***!
  \********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var normalizeHeaderName = __webpack_require__(/*! ./helpers/normalizeHeaderName */ "./node_modules/axios/lib/helpers/normalizeHeaderName.js");
var enhanceError = __webpack_require__(/*! ./core/enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

var DEFAULT_CONTENT_TYPE = {
  'Content-Type': 'application/x-www-form-urlencoded'
};

function setContentTypeIfUnset(headers, value) {
  if (!utils.isUndefined(headers) && utils.isUndefined(headers['Content-Type'])) {
    headers['Content-Type'] = value;
  }
}

function getDefaultAdapter() {
  var adapter;
  if (typeof XMLHttpRequest !== 'undefined') {
    // For browsers use XHR adapter
    adapter = __webpack_require__(/*! ./adapters/xhr */ "./node_modules/axios/lib/adapters/xhr.js");
  } else if (typeof process !== 'undefined' && Object.prototype.toString.call(process) === '[object process]') {
    // For node use HTTP adapter
    adapter = __webpack_require__(/*! ./adapters/http */ "./node_modules/axios/lib/adapters/xhr.js");
  }
  return adapter;
}

function stringifySafely(rawValue, parser, encoder) {
  if (utils.isString(rawValue)) {
    try {
      (parser || JSON.parse)(rawValue);
      return utils.trim(rawValue);
    } catch (e) {
      if (e.name !== 'SyntaxError') {
        throw e;
      }
    }
  }

  return (encoder || JSON.stringify)(rawValue);
}

var defaults = {

  transitional: {
    silentJSONParsing: true,
    forcedJSONParsing: true,
    clarifyTimeoutError: false
  },

  adapter: getDefaultAdapter(),

  transformRequest: [function transformRequest(data, headers) {
    normalizeHeaderName(headers, 'Accept');
    normalizeHeaderName(headers, 'Content-Type');

    if (utils.isFormData(data) ||
      utils.isArrayBuffer(data) ||
      utils.isBuffer(data) ||
      utils.isStream(data) ||
      utils.isFile(data) ||
      utils.isBlob(data)
    ) {
      return data;
    }
    if (utils.isArrayBufferView(data)) {
      return data.buffer;
    }
    if (utils.isURLSearchParams(data)) {
      setContentTypeIfUnset(headers, 'application/x-www-form-urlencoded;charset=utf-8');
      return data.toString();
    }
    if (utils.isObject(data) || (headers && headers['Content-Type'] === 'application/json')) {
      setContentTypeIfUnset(headers, 'application/json');
      return stringifySafely(data);
    }
    return data;
  }],

  transformResponse: [function transformResponse(data) {
    var transitional = this.transitional || defaults.transitional;
    var silentJSONParsing = transitional && transitional.silentJSONParsing;
    var forcedJSONParsing = transitional && transitional.forcedJSONParsing;
    var strictJSONParsing = !silentJSONParsing && this.responseType === 'json';

    if (strictJSONParsing || (forcedJSONParsing && utils.isString(data) && data.length)) {
      try {
        return JSON.parse(data);
      } catch (e) {
        if (strictJSONParsing) {
          if (e.name === 'SyntaxError') {
            throw enhanceError(e, this, 'E_JSON_PARSE');
          }
          throw e;
        }
      }
    }

    return data;
  }],

  /**
   * A timeout in milliseconds to abort a request. If set to 0 (default) a
   * timeout is not created.
   */
  timeout: 0,

  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',

  maxContentLength: -1,
  maxBodyLength: -1,

  validateStatus: function validateStatus(status) {
    return status >= 200 && status < 300;
  },

  headers: {
    common: {
      'Accept': 'application/json, text/plain, */*'
    }
  }
};

utils.forEach(['delete', 'get', 'head'], function forEachMethodNoData(method) {
  defaults.headers[method] = {};
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  defaults.headers[method] = utils.merge(DEFAULT_CONTENT_TYPE);
});

module.exports = defaults;


/***/ }),

/***/ "./node_modules/axios/lib/env/data.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/env/data.js ***!
  \********************************************/
/***/ ((module) => {

module.exports = {
  "version": "0.25.0"
};

/***/ }),

/***/ "./node_modules/axios/lib/helpers/bind.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/helpers/bind.js ***!
  \************************************************/
/***/ ((module) => {

"use strict";


module.exports = function bind(fn, thisArg) {
  return function wrap() {
    var args = new Array(arguments.length);
    for (var i = 0; i < args.length; i++) {
      args[i] = arguments[i];
    }
    return fn.apply(thisArg, args);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/buildURL.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/buildURL.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function encode(val) {
  return encodeURIComponent(val).
    replace(/%3A/gi, ':').
    replace(/%24/g, '$').
    replace(/%2C/gi, ',').
    replace(/%20/g, '+').
    replace(/%5B/gi, '[').
    replace(/%5D/gi, ']');
}

/**
 * Build a URL by appending params to the end
 *
 * @param {string} url The base of the url (e.g., http://www.google.com)
 * @param {object} [params] The params to be appended
 * @returns {string} The formatted url
 */
module.exports = function buildURL(url, params, paramsSerializer) {
  /*eslint no-param-reassign:0*/
  if (!params) {
    return url;
  }

  var serializedParams;
  if (paramsSerializer) {
    serializedParams = paramsSerializer(params);
  } else if (utils.isURLSearchParams(params)) {
    serializedParams = params.toString();
  } else {
    var parts = [];

    utils.forEach(params, function serialize(val, key) {
      if (val === null || typeof val === 'undefined') {
        return;
      }

      if (utils.isArray(val)) {
        key = key + '[]';
      } else {
        val = [val];
      }

      utils.forEach(val, function parseValue(v) {
        if (utils.isDate(v)) {
          v = v.toISOString();
        } else if (utils.isObject(v)) {
          v = JSON.stringify(v);
        }
        parts.push(encode(key) + '=' + encode(v));
      });
    });

    serializedParams = parts.join('&');
  }

  if (serializedParams) {
    var hashmarkIndex = url.indexOf('#');
    if (hashmarkIndex !== -1) {
      url = url.slice(0, hashmarkIndex);
    }

    url += (url.indexOf('?') === -1 ? '?' : '&') + serializedParams;
  }

  return url;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/combineURLs.js":
/*!*******************************************************!*\
  !*** ./node_modules/axios/lib/helpers/combineURLs.js ***!
  \*******************************************************/
/***/ ((module) => {

"use strict";


/**
 * Creates a new URL by combining the specified URLs
 *
 * @param {string} baseURL The base URL
 * @param {string} relativeURL The relative URL
 * @returns {string} The combined URL
 */
module.exports = function combineURLs(baseURL, relativeURL) {
  return relativeURL
    ? baseURL.replace(/\/+$/, '') + '/' + relativeURL.replace(/^\/+/, '')
    : baseURL;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/cookies.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/helpers/cookies.js ***!
  \***************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs support document.cookie
    (function standardBrowserEnv() {
      return {
        write: function write(name, value, expires, path, domain, secure) {
          var cookie = [];
          cookie.push(name + '=' + encodeURIComponent(value));

          if (utils.isNumber(expires)) {
            cookie.push('expires=' + new Date(expires).toGMTString());
          }

          if (utils.isString(path)) {
            cookie.push('path=' + path);
          }

          if (utils.isString(domain)) {
            cookie.push('domain=' + domain);
          }

          if (secure === true) {
            cookie.push('secure');
          }

          document.cookie = cookie.join('; ');
        },

        read: function read(name) {
          var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
          return (match ? decodeURIComponent(match[3]) : null);
        },

        remove: function remove(name) {
          this.write(name, '', Date.now() - 86400000);
        }
      };
    })() :

  // Non standard browser env (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return {
        write: function write() {},
        read: function read() { return null; },
        remove: function remove() {}
      };
    })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isAbsoluteURL.js":
/*!*********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAbsoluteURL.js ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";


/**
 * Determines whether the specified URL is absolute
 *
 * @param {string} url The URL to test
 * @returns {boolean} True if the specified URL is absolute, otherwise false
 */
module.exports = function isAbsoluteURL(url) {
  // A URL is considered absolute if it begins with "<scheme>://" or "//" (protocol-relative URL).
  // RFC 3986 defines scheme name as a sequence of characters beginning with a letter and followed
  // by any combination of letters, digits, plus, period, or hyphen.
  return /^([a-z][a-z\d+\-.]*:)?\/\//i.test(url);
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isAxiosError.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAxiosError.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

/**
 * Determines whether the payload is an error thrown by Axios
 *
 * @param {*} payload The value to test
 * @returns {boolean} True if the payload is an error thrown by Axios, otherwise false
 */
module.exports = function isAxiosError(payload) {
  return utils.isObject(payload) && (payload.isAxiosError === true);
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isURLSameOrigin.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isURLSameOrigin.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs have full support of the APIs needed to test
  // whether the request URL is of the same origin as current location.
    (function standardBrowserEnv() {
      var msie = /(msie|trident)/i.test(navigator.userAgent);
      var urlParsingNode = document.createElement('a');
      var originURL;

      /**
    * Parse a URL to discover it's components
    *
    * @param {String} url The URL to be parsed
    * @returns {Object}
    */
      function resolveURL(url) {
        var href = url;

        if (msie) {
        // IE needs attribute set twice to normalize properties
          urlParsingNode.setAttribute('href', href);
          href = urlParsingNode.href;
        }

        urlParsingNode.setAttribute('href', href);

        // urlParsingNode provides the UrlUtils interface - http://url.spec.whatwg.org/#urlutils
        return {
          href: urlParsingNode.href,
          protocol: urlParsingNode.protocol ? urlParsingNode.protocol.replace(/:$/, '') : '',
          host: urlParsingNode.host,
          search: urlParsingNode.search ? urlParsingNode.search.replace(/^\?/, '') : '',
          hash: urlParsingNode.hash ? urlParsingNode.hash.replace(/^#/, '') : '',
          hostname: urlParsingNode.hostname,
          port: urlParsingNode.port,
          pathname: (urlParsingNode.pathname.charAt(0) === '/') ?
            urlParsingNode.pathname :
            '/' + urlParsingNode.pathname
        };
      }

      originURL = resolveURL(window.location.href);

      /**
    * Determine if a URL shares the same origin as the current location
    *
    * @param {String} requestURL The URL to test
    * @returns {boolean} True if URL shares the same origin, otherwise false
    */
      return function isURLSameOrigin(requestURL) {
        var parsed = (utils.isString(requestURL)) ? resolveURL(requestURL) : requestURL;
        return (parsed.protocol === originURL.protocol &&
            parsed.host === originURL.host);
      };
    })() :

  // Non standard browser envs (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return function isURLSameOrigin() {
        return true;
      };
    })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/normalizeHeaderName.js":
/*!***************************************************************!*\
  !*** ./node_modules/axios/lib/helpers/normalizeHeaderName.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

module.exports = function normalizeHeaderName(headers, normalizedName) {
  utils.forEach(headers, function processHeader(value, name) {
    if (name !== normalizedName && name.toUpperCase() === normalizedName.toUpperCase()) {
      headers[normalizedName] = value;
      delete headers[name];
    }
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/parseHeaders.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/parseHeaders.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

// Headers whose duplicates are ignored by node
// c.f. https://nodejs.org/api/http.html#http_message_headers
var ignoreDuplicateOf = [
  'age', 'authorization', 'content-length', 'content-type', 'etag',
  'expires', 'from', 'host', 'if-modified-since', 'if-unmodified-since',
  'last-modified', 'location', 'max-forwards', 'proxy-authorization',
  'referer', 'retry-after', 'user-agent'
];

/**
 * Parse headers into an object
 *
 * ```
 * Date: Wed, 27 Aug 2014 08:58:49 GMT
 * Content-Type: application/json
 * Connection: keep-alive
 * Transfer-Encoding: chunked
 * ```
 *
 * @param {String} headers Headers needing to be parsed
 * @returns {Object} Headers parsed into an object
 */
module.exports = function parseHeaders(headers) {
  var parsed = {};
  var key;
  var val;
  var i;

  if (!headers) { return parsed; }

  utils.forEach(headers.split('\n'), function parser(line) {
    i = line.indexOf(':');
    key = utils.trim(line.substr(0, i)).toLowerCase();
    val = utils.trim(line.substr(i + 1));

    if (key) {
      if (parsed[key] && ignoreDuplicateOf.indexOf(key) >= 0) {
        return;
      }
      if (key === 'set-cookie') {
        parsed[key] = (parsed[key] ? parsed[key] : []).concat([val]);
      } else {
        parsed[key] = parsed[key] ? parsed[key] + ', ' + val : val;
      }
    }
  });

  return parsed;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/spread.js":
/*!**************************************************!*\
  !*** ./node_modules/axios/lib/helpers/spread.js ***!
  \**************************************************/
/***/ ((module) => {

"use strict";


/**
 * Syntactic sugar for invoking a function and expanding an array for arguments.
 *
 * Common use case would be to use `Function.prototype.apply`.
 *
 *  ```js
 *  function f(x, y, z) {}
 *  var args = [1, 2, 3];
 *  f.apply(null, args);
 *  ```
 *
 * With `spread` this example can be re-written.
 *
 *  ```js
 *  spread(function(x, y, z) {})([1, 2, 3]);
 *  ```
 *
 * @param {Function} callback
 * @returns {Function}
 */
module.exports = function spread(callback) {
  return function wrap(arr) {
    return callback.apply(null, arr);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/validator.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/validator.js ***!
  \*****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var VERSION = (__webpack_require__(/*! ../env/data */ "./node_modules/axios/lib/env/data.js").version);

var validators = {};

// eslint-disable-next-line func-names
['object', 'boolean', 'number', 'function', 'string', 'symbol'].forEach(function(type, i) {
  validators[type] = function validator(thing) {
    return typeof thing === type || 'a' + (i < 1 ? 'n ' : ' ') + type;
  };
});

var deprecatedWarnings = {};

/**
 * Transitional option validator
 * @param {function|boolean?} validator - set to false if the transitional option has been removed
 * @param {string?} version - deprecated version / removed since version
 * @param {string?} message - some message with additional info
 * @returns {function}
 */
validators.transitional = function transitional(validator, version, message) {
  function formatMessage(opt, desc) {
    return '[Axios v' + VERSION + '] Transitional option \'' + opt + '\'' + desc + (message ? '. ' + message : '');
  }

  // eslint-disable-next-line func-names
  return function(value, opt, opts) {
    if (validator === false) {
      throw new Error(formatMessage(opt, ' has been removed' + (version ? ' in ' + version : '')));
    }

    if (version && !deprecatedWarnings[opt]) {
      deprecatedWarnings[opt] = true;
      // eslint-disable-next-line no-console
      console.warn(
        formatMessage(
          opt,
          ' has been deprecated since v' + version + ' and will be removed in the near future'
        )
      );
    }

    return validator ? validator(value, opt, opts) : true;
  };
};

/**
 * Assert object's properties type
 * @param {object} options
 * @param {object} schema
 * @param {boolean?} allowUnknown
 */

function assertOptions(options, schema, allowUnknown) {
  if (typeof options !== 'object') {
    throw new TypeError('options must be an object');
  }
  var keys = Object.keys(options);
  var i = keys.length;
  while (i-- > 0) {
    var opt = keys[i];
    var validator = schema[opt];
    if (validator) {
      var value = options[opt];
      var result = value === undefined || validator(value, opt, options);
      if (result !== true) {
        throw new TypeError('option ' + opt + ' must be ' + result);
      }
      continue;
    }
    if (allowUnknown !== true) {
      throw Error('Unknown option ' + opt);
    }
  }
}

module.exports = {
  assertOptions: assertOptions,
  validators: validators
};


/***/ }),

/***/ "./node_modules/axios/lib/utils.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/utils.js ***!
  \*****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");

// utils is a library of generic helper functions non-specific to axios

var toString = Object.prototype.toString;

/**
 * Determine if a value is an Array
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Array, otherwise false
 */
function isArray(val) {
  return Array.isArray(val);
}

/**
 * Determine if a value is undefined
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if the value is undefined, otherwise false
 */
function isUndefined(val) {
  return typeof val === 'undefined';
}

/**
 * Determine if a value is a Buffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Buffer, otherwise false
 */
function isBuffer(val) {
  return val !== null && !isUndefined(val) && val.constructor !== null && !isUndefined(val.constructor)
    && typeof val.constructor.isBuffer === 'function' && val.constructor.isBuffer(val);
}

/**
 * Determine if a value is an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an ArrayBuffer, otherwise false
 */
function isArrayBuffer(val) {
  return toString.call(val) === '[object ArrayBuffer]';
}

/**
 * Determine if a value is a FormData
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an FormData, otherwise false
 */
function isFormData(val) {
  return toString.call(val) === '[object FormData]';
}

/**
 * Determine if a value is a view on an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a view on an ArrayBuffer, otherwise false
 */
function isArrayBufferView(val) {
  var result;
  if ((typeof ArrayBuffer !== 'undefined') && (ArrayBuffer.isView)) {
    result = ArrayBuffer.isView(val);
  } else {
    result = (val) && (val.buffer) && (isArrayBuffer(val.buffer));
  }
  return result;
}

/**
 * Determine if a value is a String
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a String, otherwise false
 */
function isString(val) {
  return typeof val === 'string';
}

/**
 * Determine if a value is a Number
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Number, otherwise false
 */
function isNumber(val) {
  return typeof val === 'number';
}

/**
 * Determine if a value is an Object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Object, otherwise false
 */
function isObject(val) {
  return val !== null && typeof val === 'object';
}

/**
 * Determine if a value is a plain Object
 *
 * @param {Object} val The value to test
 * @return {boolean} True if value is a plain Object, otherwise false
 */
function isPlainObject(val) {
  if (toString.call(val) !== '[object Object]') {
    return false;
  }

  var prototype = Object.getPrototypeOf(val);
  return prototype === null || prototype === Object.prototype;
}

/**
 * Determine if a value is a Date
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Date, otherwise false
 */
function isDate(val) {
  return toString.call(val) === '[object Date]';
}

/**
 * Determine if a value is a File
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a File, otherwise false
 */
function isFile(val) {
  return toString.call(val) === '[object File]';
}

/**
 * Determine if a value is a Blob
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Blob, otherwise false
 */
function isBlob(val) {
  return toString.call(val) === '[object Blob]';
}

/**
 * Determine if a value is a Function
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Function, otherwise false
 */
function isFunction(val) {
  return toString.call(val) === '[object Function]';
}

/**
 * Determine if a value is a Stream
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Stream, otherwise false
 */
function isStream(val) {
  return isObject(val) && isFunction(val.pipe);
}

/**
 * Determine if a value is a URLSearchParams object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a URLSearchParams object, otherwise false
 */
function isURLSearchParams(val) {
  return toString.call(val) === '[object URLSearchParams]';
}

/**
 * Trim excess whitespace off the beginning and end of a string
 *
 * @param {String} str The String to trim
 * @returns {String} The String freed of excess whitespace
 */
function trim(str) {
  return str.trim ? str.trim() : str.replace(/^\s+|\s+$/g, '');
}

/**
 * Determine if we're running in a standard browser environment
 *
 * This allows axios to run in a web worker, and react-native.
 * Both environments support XMLHttpRequest, but not fully standard globals.
 *
 * web workers:
 *  typeof window -> undefined
 *  typeof document -> undefined
 *
 * react-native:
 *  navigator.product -> 'ReactNative'
 * nativescript
 *  navigator.product -> 'NativeScript' or 'NS'
 */
function isStandardBrowserEnv() {
  if (typeof navigator !== 'undefined' && (navigator.product === 'ReactNative' ||
                                           navigator.product === 'NativeScript' ||
                                           navigator.product === 'NS')) {
    return false;
  }
  return (
    typeof window !== 'undefined' &&
    typeof document !== 'undefined'
  );
}

/**
 * Iterate over an Array or an Object invoking a function for each item.
 *
 * If `obj` is an Array callback will be called passing
 * the value, index, and complete array for each item.
 *
 * If 'obj' is an Object callback will be called passing
 * the value, key, and complete object for each property.
 *
 * @param {Object|Array} obj The object to iterate
 * @param {Function} fn The callback to invoke for each item
 */
function forEach(obj, fn) {
  // Don't bother if no value provided
  if (obj === null || typeof obj === 'undefined') {
    return;
  }

  // Force an array if not already something iterable
  if (typeof obj !== 'object') {
    /*eslint no-param-reassign:0*/
    obj = [obj];
  }

  if (isArray(obj)) {
    // Iterate over array values
    for (var i = 0, l = obj.length; i < l; i++) {
      fn.call(null, obj[i], i, obj);
    }
  } else {
    // Iterate over object keys
    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        fn.call(null, obj[key], key, obj);
      }
    }
  }
}

/**
 * Accepts varargs expecting each argument to be an object, then
 * immutably merges the properties of each object and returns result.
 *
 * When multiple objects contain the same key the later object in
 * the arguments list will take precedence.
 *
 * Example:
 *
 * ```js
 * var result = merge({foo: 123}, {foo: 456});
 * console.log(result.foo); // outputs 456
 * ```
 *
 * @param {Object} obj1 Object to merge
 * @returns {Object} Result of all merge properties
 */
function merge(/* obj1, obj2, obj3, ... */) {
  var result = {};
  function assignValue(val, key) {
    if (isPlainObject(result[key]) && isPlainObject(val)) {
      result[key] = merge(result[key], val);
    } else if (isPlainObject(val)) {
      result[key] = merge({}, val);
    } else if (isArray(val)) {
      result[key] = val.slice();
    } else {
      result[key] = val;
    }
  }

  for (var i = 0, l = arguments.length; i < l; i++) {
    forEach(arguments[i], assignValue);
  }
  return result;
}

/**
 * Extends object a by mutably adding to it the properties of object b.
 *
 * @param {Object} a The object to be extended
 * @param {Object} b The object to copy properties from
 * @param {Object} thisArg The object to bind function to
 * @return {Object} The resulting value of object a
 */
function extend(a, b, thisArg) {
  forEach(b, function assignValue(val, key) {
    if (thisArg && typeof val === 'function') {
      a[key] = bind(val, thisArg);
    } else {
      a[key] = val;
    }
  });
  return a;
}

/**
 * Remove byte order marker. This catches EF BB BF (the UTF-8 BOM)
 *
 * @param {string} content with BOM
 * @return {string} content value without BOM
 */
function stripBOM(content) {
  if (content.charCodeAt(0) === 0xFEFF) {
    content = content.slice(1);
  }
  return content;
}

module.exports = {
  isArray: isArray,
  isArrayBuffer: isArrayBuffer,
  isBuffer: isBuffer,
  isFormData: isFormData,
  isArrayBufferView: isArrayBufferView,
  isString: isString,
  isNumber: isNumber,
  isObject: isObject,
  isPlainObject: isPlainObject,
  isUndefined: isUndefined,
  isDate: isDate,
  isFile: isFile,
  isBlob: isBlob,
  isFunction: isFunction,
  isStream: isStream,
  isURLSearchParams: isURLSearchParams,
  isStandardBrowserEnv: isStandardBrowserEnv,
  forEach: forEach,
  merge: merge,
  extend: extend,
  trim: trim,
  stripBOM: stripBOM
};


/***/ }),

/***/ "./src/Ctrl.js":
/*!*********************!*\
  !*** ./src/Ctrl.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "NumberCtrl": () => (/* binding */ NumberCtrl),
/* harmony export */   "SelectCtrl": () => (/* binding */ SelectCtrl)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);


function SelectCtrl(props) {
  const style = {
    'display': props.display ? props.display : 'block',
    'maxWidth': props.width ? props.width : '350px'
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: style
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      'display': 'block',
      'fontSize': '11px',
      'textTransform': 'uppercase'
    }
  }, props.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: props.value,
    onChange: e => props.onChange(e.target.value)
  }, props.options.map(o => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: o.value
  }, o.label))));
}
function NumberCtrl(props) {
  const style = {
    'display': props.display ? props.display : 'block',
    'maxWidth': props.maxWidth ? props.maxWidth : '300px'
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: style
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      'display': 'block',
      'fontSize': '11px',
      'textTransform': 'uppercase'
    }
  }, props.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    value: props.value ? props.value : 0,
    onChange: e => props.onChange(e.target.value)
  }));
}
/*
export function RadioCtrl (props) {
    const buttonDisplay = {'display': (props.buttonDisplay) ? props.buttonDisplay : 'inline-block' };
    return (
        <div onChange={(e) => props.onChange(e.target.value)}>
            <label style={{'display':'block','fontSize':'11px','textTransform':'uppercase'}}>{props.label}</label>
            <input type="number" value={(props.value) ? props.value : 0} onChange={(e) => props.onChange(e.target.value)} />
        </div>
    )
}
*/

/***/ }),

/***/ "./src/DateTimeMaker.js":
/*!******************************!*\
  !*** ./src/DateTimeMaker.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DateTimeMaker)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Ctrl__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Ctrl */ "./src/Ctrl.js");
/* harmony import */ var _queries_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./queries.js */ "./src/queries.js");





function DateTimeMaker(props) {
  const {
    event_id,
    eventdata,
    isLoadingDates
  } = props;
  const [error, setError] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const {
    mutate: datemutate
  } = (0,_queries_js__WEBPACK_IMPORTED_MODULE_4__.useRSVPDateMutation)(event_id, setError);
  if (!eventdata.tzchoices) eventdata.tzchoices = [];
  console.log('props DateTimeMaker', props);
  console.log('eventdata DateTimeMaker', eventdata);
  const d = new Date();
  const is12Hour = true;
  const date = new Date(eventdata.date);
  const endDate = new Date(eventdata.enddate);
  const elapsed = endDate.getTime() - date.getTime();
  console.log('elapsed', elapsed);
  function sqlDate(date) {
    console.log(typeof date);
    if (typeof date == 'string') date = new Date(date);
    console.log('date for sqlDate', date);
    var pad = function (num) {
      return ('00' + num).slice(-2);
    };
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
  }
  function setDate(datestring) {
    const dateobj = new Date(datestring);
    const m = dateobj.getTime();
    const m_end = m + elapsed ? elapsed : 0;
    //console.log('setDate value',dateobj);
    const newendDate = new Date();
    newendDate.setTime(m_end);
    console.log(m);
    console.log(m_end);
    const sqldate = sqlDate(dateobj);
    const endsqldate = sqlDate(newendDate);
    datemutate({
      'date': sqldate,
      'enddate': endsqldate
    });
  }
  function setEndDate(datestring) {
    const newendDate = new Date(datestring);
    if (newendDate.getTime() < date.getTime()) {
      alert('end date cannot be before start date');
      return;
    }
    const endsqldate = sqlDate(newendDate);
    datemutate({
      'enddate': sqlDate(newendDate)
    });
  }
  function setOther(key, value) {
    const change = {
      ...eventdata
    };
    change[key] = value;
    datemutate(change);
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsvpmaker-date-time"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Start Date and Time"), isLoadingDates && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Loading fresh data ...")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.DateTimePicker, {
    currentDate: date,
    onChange: newDate => {
      setDate(newDate);
      console.log('new date', newDate);
    },
    is12Hour: is12Hour,
    __nextRemoveHelpButton: true,
    __nextRemoveResetButton: true
  }), error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    style: {
      'color': 'red'
    }
  }, error), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
    label: "Date Display Options",
    value: eventdata.display_type,
    options: [{
      'label': 'Show Start Time Only',
      'value': ''
    }, {
      'label': 'Show Both Start and End Time',
      'value': 'set'
    }, {
      'label': 'Show Date Only, No Times',
      'value': 'allday'
    }],
    onChange: value => {
      setOther('display_type', value);
    }
  }), eventdata.display_type != '' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsvp-end-date"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "End Date and Time"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.DateTimePicker, {
    currentDate: endDate,
    onChange: newDate => {
      setEndDate(newDate);
      console.log('new enddate', newDate);
    },
    is12Hour: is12Hour,
    __nextRemoveHelpButton: true,
    __nextRemoveResetButton: true
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
    label: "Time Zone",
    value: eventdata.timezone,
    options: eventdata.tzchoices.map(choice => {
      return {
        'label': choice,
        'value': choice
      };
    }),
    onChange: value => {
      setOther('timezone', value);
    }
  }));
}

/***/ }),

/***/ "./src/Forms.js":
/*!**********************!*\
  !*** ./src/Forms.js ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Forms)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Ctrl_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Ctrl.js */ "./src/Ctrl.js");
/* harmony import */ var _SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SanitizedHTML.js */ "./src/SanitizedHTML.js");
/* harmony import */ var _SaveControls__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./SaveControls */ "./src/SaveControls.js");
/* harmony import */ var _icons_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./icons.js */ "./src/icons.js");
/* harmony import */ var _http_common_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./http-common.js */ "./src/http-common.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");









function Forms(props) {
  const [formId, setFormId] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.form_id);
  const [addfield, setAddfield] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('rsvpmaker/formfield');
  const [addfieldLabel, setAddfieldLabel] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [addfieldChoices, setAddfieldChoices] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [showPreview, setShowPreview] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [newForm, setNewForm] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const {
    isSaving,
    saveEffect,
    SaveControls,
    makeNotification
  } = (0,_SaveControls__WEBPACK_IMPORTED_MODULE_5__.useSaveControls)();
  const {
    changes,
    addChange,
    setChanges
  } = props;
  const event_id = wp?.data?.select("core/editor")?.getCurrentPostId();
  console.log('Forms props', props);
  console.log('Forms formId', formId);
  function fetchForms() {
    return _http_common_js__WEBPACK_IMPORTED_MODULE_7__["default"].get('rsvp_form?form_id=' + formId + '&post_id=' + wp?.data?.select("core/editor")?.getCurrentPostId());
  }
  const {
    data,
    isLoading,
    isError
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_8__.useQuery)(['rsvp_form', formId], fetchForms, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      if (!formId) setFormId(data.data.form_id);
      console.log('rsvp forms query', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving rsvp forms', err);
    },
    refetchInterval: false
  });
  if (isError) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Error loading form options");
  const queryClient = (0,react_query__WEBPACK_IMPORTED_MODULE_8__.useQueryClient)();
  async function updateForm(form) {
    return await _http_common_js__WEBPACK_IMPORTED_MODULE_7__["default"].post('rsvp_form?form_id=' + formId + '&post_id=' + wp?.data?.select("core/editor")?.getCurrentPostId(), {
      'form': form,
      'newForm': newForm,
      'event_id': props.event_id ? props.event_id : 0
    });
  }
  const {
    mutate: formMutate
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_8__.useMutation)(updateForm, {
    onMutate: async form => {
      const previousValue = queryClient.getQueryData(['rsvp_form', formId]);
      console.log('optimistic update form', form);
      await queryClient.cancelQueries(['rsvp_form', formId]);
      queryClient.setQueryData(['rsvp_form', formId], oldQueryData => {
        //function passed to setQueryData
        const {
          data
        } = oldQueryData;
        data.form = form;
        const newdata = {
          ...oldQueryData,
          data: data
        };
        console.log('newdata optimistic form update', newdata);
        return newdata;
      });
      //makeNotification('Updating ...');
      console.log('updating options');
      return {
        previousValue
      };
    },
    onSettled: (data, error, variables, context) => {
      queryClient.invalidateQueries(['rsvp_form', formId]);
    },
    onSuccess: (data, error, variables, context) => {
      console.log('updated');
      setFormId(data.data.form_id);
    },
    onError: (err, variables, context) => {
      //makeNotification('Error '+err.message);
      console.log('update options error', err);
      queryClient.setQueryData(['rsvp_form', formId], context.previousValue);
    }
  });
  if (isLoading) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Loading ...");
  const form = data.data.form.filter(block => block.blockName);
  const formOptions = data.data.form_options;
  const fetchedFormID = data.data.form_id;
  let lastblock = form.length - 1;
  function toServerTs(datestr) {
    const newdate = new Date(datestr);
    return newdate.getTime() - correction;
  }

  //check for presence of guest and note
  const guestfields = [];
  for (let i = 0; i < lastblock; i++) {
    let block = form[i];
    if (block?.attrs.guestform) guestfields.push(i);else console.log('guestfields attr not found', i);
    if (['rsvpmaker/formnote', 'rsvpmaker/guests'].includes(block.blockName)) {
      lastblock = i - 1;
      break;
    }
  }
  ;
  const addfields = [{
    'label': 'Choose Field Type',
    'value': ''
  }, {
    'label': 'Text Field',
    'value': 'rsvpmaker/formfield'
  }, {
    'label': 'Text Area',
    'value': 'rsvpmaker/formtextarea'
  }, {
    'label': 'Select',
    'value': 'rsvpmaker/formselect'
  }, {
    'label': 'Radio Buttons',
    'value': 'rsvpmaker/formradio'
  }, {
    'label': 'Add to Email List Checkbox',
    'value': 'rsvpmaker/formchimp'
  }];
  let hasguests = false;
  let hasnote = false;
  for (let i = 0; i < form.length; i++) {
    let block = form[i];
    console.log('check for end fields', block);
    if ('rsvpmaker/guests' == block.blockName) {
      hasguests = true;
      console.log('check for end fields found guests');
    }
    if ('rsvpmaker/formnote' == block.blockName) {
      hasnote = true;
      console.log('check for end fields found note');
    }
  }
  ;
  if (!hasguests) addfields.push({
    'label': 'Guest Fields',
    'value': 'rsvpmaker/guests'
  });
  if (!hasnote) addfields.push({
    'label': 'Note Field',
    'value': 'rsvpmaker/formnote'
  });
  function DeleteButton(props) {
    const [deletemode, setDeleteMode] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
    const {
      blockindex
    } = props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !deletemode && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "blockmove deletebutton",
      onClick: () => {
        setDeleteMode(true);
      }
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_icons_js__WEBPACK_IMPORTED_MODULE_6__.Delete, null), " Delete"), " ", deletemode && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "blockmove",
      onClick: () => {
        moveBlock(blockindex, 'delete');
      }
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_icons_js__WEBPACK_IMPORTED_MODULE_6__.Delete, null), " Confirm Delete"), " ");
  }
  function moveBlock(blockindex, direction) {
    const newform = [];
    const current = form[blockindex];
    let placed = 'delete' == direction ? true : false;
    form.forEach((block, index) => {
      if (null == block.blockName || index == blockindex) ;else {
        if ('up' == direction && index == blockindex - 1) {
          newform.push(current);
          placed = true;
        }
        if ('down' == direction && index == blockindex + 2) {
          newform.push(current);
          placed = true;
        }
        newform.push(block);
      }
    });
    if (!placed) newform.push(current);
    console.log('newform', newform);
    formMutate(newform);
  }
  function addFieldNow() {
    if (!addfield) return;
    console.log(addfield);
    console.log(addfieldLabel);
    let newfield;
    if ('rsvpmaker/formnote' == addfield) newfield = {
      "blockName": "rsvpmaker/formnote",
      "attrs": [],
      "innerBlocks": [],
      "innerHTML": "",
      "innerContent": []
    };else if ('rsvpmaker/guests' == addfield) newfield = {
      "blockName": "rsvpmaker/guests",
      "attrs": [],
      "innerBlocks": [{
        "blockName": "core/paragraph",
        "attrs": [],
        "innerBlocks": [],
        "innerHTML": "\n<p><\/p>\n",
        "innerContent": ["\n<p><\/p>\n"]
      }],
      "innerHTML": "\n<div class=\"wp-block-rsvpmaker-guests\"><\/div>\n",
      "innerContent": ["\n<div class=\"wp-block-rsvpmaker-guests\">", null, "</div>\n"]
    };else {
      if (!addfieldLabel) {
        alert('a field label is required');
        return;
      }
      newfield = {
        'blockName': addfield,
        'innerHTML': '',
        'innerBlocks': [],
        'innerContent': [],
        'attrs': {
          'label': addfieldLabel,
          'slug': addfieldLabel.toLowerCase().replace(/[^a-z0-0]/, '')
        }
      };
    }
    if (addfieldChoices) newfield.attrs.choicearray = addfieldChoices.split('\n');
    console.log('newfield', newfield);
    const newform = [];
    form.forEach((block, blockindex) => {
      newform.push(block);
      if (blockindex == lastblock) newform.push(newfield);
    });
    formMutate(newform);
    setAddfield('rsvpmaker/formfield');
    setAddfieldLabel('');
    setAddfieldChoices('');
  }
  function setFormItemAttr(blockindex, field, value) {
    const newform = [...form];
    newform[blockindex].attrs[field] = value;
    formMutate(newform);
  }
  function FormItem(props) {
    const {
      blockindex,
      setFormItemAttr
    } = props;
    const [choices, setChoices] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.attrs.choicearray);
    const [req, setReq] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.attrs.required);
    const [label, setLabel] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.attrs.label);
    const [guestform, setGuestform] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.attrs.guestform);
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "formAtts"
    }, ['rsvpmaker/formfield', 'rsvpmaker/formselect', 'rsvpmaker/formradio', 'rsvpmaker/formtextarea'].includes(props.blockName) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Field Label"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "text",
      value: label,
      onChange: e => {
        setLabel(e.target.value);
      },
      onBlur: () => {
        setFormItemAttr(blockindex, 'label', label);
      }
    })), choices && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Choices", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
      value: choices.join('\n'),
      onChange: e => {
        let newchoices = e.target.value.split('\n');
        setChoices(newchoices);
        setFormItemAttr(blockindex, 'choicearray', newchoices);
      },
      onBlur: () => {
        setFormItemAttr(blockindex, 'choicearray', choices);
      }
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Enter one choice per line")), ['rsvpmaker/formfield', 'rsvpmaker/formselect', 'rsvpmaker/formradio', 'rsvpmaker/formtextarea'].includes(props.blockName) && props.attrs.slug != 'email' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
      label: "Required",
      checked: req == 'required',
      onChange: () => {
        let newr = req == 'required' ? '' : 'required';
        setReq(newr);
        setFormItemAttr(blockindex, 'required', newr);
      }
    }), props.attrs.slug == 'email' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Email is always required")), ['rsvpmaker/formfield', 'rsvpmaker/formselect', 'rsvpmaker/formradio', 'rsvpmaker/formtextarea'].includes(props.blockName) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
      label: "Include on Guest Form",
      checked: guestform,
      onChange: () => {
        let newg = !guestform;
        setGuestform(newg);
        setFormItemAttr(blockindex, 'guestform', newg);
      }
    }));
  }
  function Guests() {
    console.log('guestfields', guestfields);
    const [open, setOpen] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
    if (!open) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      onClick: () => {
        setOpen(true);
      }
    }, "+ Add Guests"));
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Guest 1"), guestfields.map(i => {
      const block = form[i];
      console.log('guestfields forEach', block);
      let choices = [];
      if (block?.attrs.choicearray) choices = block.attrs.choicearray.map(item => {
        return {
          'label': item,
          'value': item
        };
      });
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "formAtts"
      }, 'rsvpmaker/formfield' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
        label: block.attrs.label
      }), 'rsvpmaker/formtextarea' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextareaControl, {
        label: block.attrs.label
      }), 'rsvpmaker/formselect' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl_js__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
        label: block.attrs.label,
        options: choices
      }), 'rsvpmaker/formradio' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.RadioControl, {
        label: block.attrs.label,
        options: choices
      }));
    }));
  }
  let fieldlabel = '';
  let isrsvp = true;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsvptab"
  }, !props.single_form && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl_js__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
    label: "Choose Form",
    value: formId,
    options: formOptions,
    onChange: setFormId
  }), formId != fetchedFormID && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Reloading ..."), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: "Show Form Preview",
    checked: showPreview,
    onChange: () => {
      setShowPreview(!showPreview);
    }
  }), showPreview && form.map((block, blockindex) => {
    isrsvp = block.blockName.indexOf('rsvpmaker') > -1;
    let choices = [];
    if (block?.attrs.choicearray) choices = block.attrs.choicearray.map(item => {
      return {
        'label': item,
        'value': item
      };
    });
    if (null == block.blockName) return;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, 'rsvpmaker/formfield' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
      label: block.attrs.label
    }), 'rsvpmaker/formtextarea' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextareaControl, {
      label: block.attrs.label
    }), 'rsvpmaker/formselect' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl_js__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
      label: block.attrs.label,
      options: choices
    }), 'rsvpmaker/formradio' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.RadioControl, {
      label: block.attrs.label,
      options: choices
    }), 'rsvpmaker/guests' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Guests, null), 'rsvpmaker/formchimp' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox"
    }), " Add me to your email list"), !isrsvp && block.innerHTML && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
      innerHTML: block.innerHTML
    }));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Form Editing Controls"), form.map((block, blockindex) => {
    fieldlabel = block.blockName.replace(/^[^/]+\//, '');
    isrsvp = block.blockName.indexOf('rsvpmaker') > -1;
    if ('formchimp' == fieldlabel) fieldlabel = 'Add to Email List Checkbox';else if ('formnote' == fieldlabel) fieldlabel = 'Note';
    if (null == block.blockName) return;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "formblock"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      class: "blockheader"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "reorganize-drag"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "titleline"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, fieldlabel, " ", block.attrs.label && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "fieldlabel"
    }, block.attrs.label)), " ", !['rsvpmaker/formnote', 'rsvpmaker/guests'].includes(block.blockName) && blockindex > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "blockmove",
      onClick: () => {
        moveBlock(blockindex, 'up');
      }
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_icons_js__WEBPACK_IMPORTED_MODULE_6__.Up, null)), "  ", !['rsvpmaker/formnote', 'rsvpmaker/guests'].includes(block.blockName) && blockindex != lastblock && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "blockmove",
      onClick: () => {
        moveBlock(blockindex, 'down');
      }
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_icons_js__WEBPACK_IMPORTED_MODULE_6__.Down, null)), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DeleteButton, {
      blockindex: blockindex
    }), " "), 'rsvpmaker/guests' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Gathers information about guests.")), 'rsvpmaker/formnote' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "A free text entry note at the bottom of the form.")), 'rsvpmaker/formchimp' == block.blockName && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Displays an Add to Email List checkbox on the form")), !isrsvp && block.innerHTML && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
      innerHTML: block.innerHTML
    }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: '/wp-admin/post.php?action=edit&post=' + formId
    }, "Open in the WordPress editor"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(FormItem, {
      attrs: block.attrs,
      blockName: block.blockName,
      blockindex: blockindex,
      setFormItemAttr: setFormItemAttr
    }))));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Add a form field ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl_js__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
    label: "Type",
    options: addfields,
    value: addfield,
    onChange: value => {
      setAddfield(value);
    }
  }), " ", !['rsvpmaker/formnote', 'rsvpmaker/guests', 'rsvpmaker/formchimp'].includes(addfield) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: "Field Label",
    value: addfieldLabel,
    onChange: value => {
      setAddfieldLabel(value);
    }
  }), " "), ['rsvpmaker/formselect', 'rsvpmaker/formradio'].includes(addfield) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Choices"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    value: addfieldChoices,
    onChange: e => {
      setAddfieldChoices(e.target.value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Enter one choice per line")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: addFieldNow
  }, "Add")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "You can also open this form ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: '/wp-admin/post.php?action=edit&post=' + formId
  }, "in the WordPress editor"), "."), !props.single_form && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Create Custom Form"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: "Form Title",
    value: newForm,
    onChange: setNewForm
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "The content of the currently selected form will be used as a starting point."), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: e => {
      e.preventDefault();
      formMutate(form);
    }
  }, "Create Form"))));
}

/***/ }),

/***/ "./src/SanitizedHTML.js":
/*!******************************!*\
  !*** ./src/SanitizedHTML.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "SanitizedHTML": () => (/* binding */ SanitizedHTML)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_2__);



function SanitizedHTML(props) {
  const {
    innerHTML
  } = props;
  const cleanHTML = dompurify__WEBPACK_IMPORTED_MODULE_2___default().sanitize(innerHTML).replace('class=', 'className=');
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    dangerouslySetInnerHTML: {
      __html: cleanHTML
    }
  });
}

/***/ }),

/***/ "./src/SaveControls.js":
/*!*****************************!*\
  !*** ./src/SaveControls.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useSaveControls": () => (/* binding */ useSaveControls)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _queries_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./queries.js */ "./src/queries.js");



function useSaveControls() {
  const [isSaving, setIsSaving] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [notificationTimeout, setNotificationTimeout] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [notification, setNotification] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  function saveEffect() {
    setIsSaving(true);
    setTimeout(() => {
      setIsSaving(false);
    }, 500);
  }
  function makeNotification() {
    let message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    if (message == '') return notification;
    if (notificationTimeout) clearTimeout(notificationTimeout);
    setNotification({
      'message': message
    });
    let nt = setTimeout(() => {
      setNotification(null);
    }, 5000);
    setNotificationTimeout(nt);
  }
  function SaveControls(props) {
    const {
      changes,
      setChanges
    } = props;
    console.log('changes SaveControls', changes);
    const {
      mutate: setOption
    } = (0,_queries_js__WEBPACK_IMPORTED_MODULE_2__.useOptionsMutation)(setChanges, makeNotification);
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "savecontrols"
    }, notification && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsvp-notification rsvp-notification-success"
    }, notification.message), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "savebuttonwrapper"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => {
        console.log('save changeset', changes);
        saveEffect();
        setOption(changes);
      }
    }, "Save")));
  }
  return {
    SaveControls,
    notification,
    setNotification,
    isSaving,
    saveEffect,
    makeNotification
  };
}

/***/ }),

/***/ "./src/editor-sidebar/Checkbox.js":
/*!****************************************!*\
  !*** ./src/editor-sidebar/Checkbox.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);


const Checkbox = _ref => {
  let {
    id,
    type,
    name,
    handleClick,
    isChecked,
    value
  } = _ref;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: id,
    name: name,
    type: type,
    onChange: handleClick,
    checked: isChecked,
    value: value
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Checkbox);

/***/ }),

/***/ "./src/editor-sidebar/Confirmation.js":
/*!********************************************!*\
  !*** ./src/editor-sidebar/Confirmation.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Confirmation)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Ctrl_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Ctrl.js */ "./src/Ctrl.js");
/* harmony import */ var _SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../SanitizedHTML.js */ "./src/SanitizedHTML.js");
/* harmony import */ var _icons_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../icons.js */ "./src/icons.js");
/* harmony import */ var _http_common_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../http-common.js */ "./src/http-common.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");








function Confirmation() {
  const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [reminderHours, setReminderHours] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [addBeforeAfter, setBeforeAfter] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('before');
  function fetchMessages() {
    return _http_common_js__WEBPACK_IMPORTED_MODULE_6__["default"].get('confirm_remind?event_id=' + rsvpmaker_ajax.event_id);
  }
  const {
    data,
    isLoading,
    isError
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_7__.useQuery)(['confirm_remind'], fetchMessages, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      console.log('rsvp confirmation query', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving messages', err);
    },
    refetchInterval: false
  });
  if (isError) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Error loading confirmation message data");
  const queryClient = (0,react_query__WEBPACK_IMPORTED_MODULE_7__.useQueryClient)();
  async function updateMessage(command) {
    return await _http_common_js__WEBPACK_IMPORTED_MODULE_6__["default"].post('confirm_remind?event_id=' + rsvpmaker_ajax.event_id, command);
  }
  const {
    mutate: messageMutate
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_7__.useMutation)(updateMessage, {
    /*
    onMutate: async (form) => {
        const previousValue = queryClient.getQueryData(['rsvp_form',formId]);
        console.log('optimistic update form',form);
        await queryClient.cancelQueries(['rsvp_form',formId]);
        queryClient.setQueryData(['rsvp_form',formId],(oldQueryData) => {
            //function passed to setQueryData
            const {data} = oldQueryData;
            data.form = form;
            const newdata = {
                ...oldQueryData, data: data
            };
            console.log('newdata optimistic form update',newdata);
            return newdata;
        }) 
        //makeNotification('Updating ...');
        console.log('updating options');
        return {previousValue}
    },
      onSettled: (data, error, variables, context) => {
        queryClient.invalidateQueries(['rsvp_form',formId]);
    },
    */
    onSuccess: (data, error, variables, context) => {
      console.log('updated', data);
      setStatus('');
      queryClient.setQueryData(['confirm_remind'], data);
    },
    onError: (err, variables, context) => {
      //makeNotification('Error '+err.message);
      console.log('update message error', err);
      //queryClient.setQueryData(['rsvp_form',formId], context.previousValue);
    }
  });

  if (isLoading) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Loading ...");
  const messagedata = data.data;
  const {
    confirmation,
    reminder,
    edit_url
  } = messagedata;
  const ropt = [];
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Confirmation Message: ", messagedata.confirmation.post_title), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
    innerHTML: confirmation.html
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    target: "_blank",
    href: edit_url + confirmation.ID
  }, "Edit")), confirmation.post_parent != rsvpmaker_ajax.event_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => {
      messageMutate({
        'action': 'customize',
        'type': 'confirmation',
        'source': confirmation.ID,
        'event_id': rsvpmaker_ajax.event_id
      }), setStatus('working ...');
    }
  }, "Customize for this ", rsvpmaker.post_type == 'rsvpmaker' ? 'event' : 'template'), " ", status), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Payment Confirmation ", !messagedata.payment_confirmation && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, ":Not Set")), !messagedata.payment_confirmation && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => {
      messageMutate({
        'action': 'add_payment_confirmation',
        'type': 'payment confirmation',
        'source': confirmation.ID,
        'event_id': rsvpmaker_ajax.event_id
      }), setStatus('working ...');
    }
  }, "Customize for this ", rsvpmaker.post_type == 'rsvpmaker' ? 'event' : 'template'), " ", status, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), "If you are charging for an event, you can add a separate payment confiration message that is only sent after payment is received."), messagedata.payment_confirmation && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
    innerHTML: messagedata.payment_confirmation.html
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    target: "_blank",
    href: edit_url + messagedata.payment_confirmation.ID
  }, "Edit"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Reminder and Follow Up Messages"), reminder.map(rem => {
    const hour = parseInt(rem.hour);
    const beforeafter = hour < 0 ? 'before' : 'after';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, Math.abs(hour), " hours ", beforeafter), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
      innerHTML: rem.post_title
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_4__.SanitizedHTML, {
      innerHTML: rem.html
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: "_blank",
      href: edit_url + rem.ID
    }, "Edit")));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Add a Message"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Number of Hours", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "1",
    value: reminderHours,
    onChange: e => {
      if (e.target.value > 0) setReminderHours(e.target.value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Ctrl_js__WEBPACK_IMPORTED_MODULE_3__.SelectCtrl, {
    value: addBeforeAfter,
    onChange: value => {
      setBeforeAfter(value);
      console.log(value);
    },
    options: [{
      'label': 'before event start time',
      'value': 'before'
    }, {
      'label': 'after event start time',
      'value': 'after'
    }]
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => {
      messageMutate({
        'action': 'add_reminder',
        'type': 'reminder ' + reminderHours + ' ' + addBeforeAfter,
        'source': confirmation.ID,
        'event_id': rsvpmaker_ajax.event_id,
        'hours': reminderHours,
        'beforeafter': addBeforeAfter
      }), setStatus('working ...');
    }
  }, "Add")));
}

/***/ }),

/***/ "./src/editor-sidebar/DateOrTemplate.js":
/*!**********************************************!*\
  !*** ./src/editor-sidebar/DateOrTemplate.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DateOrTemplate)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _DateTimeMaker_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../DateTimeMaker.js */ "./src/DateTimeMaker.js");
/* harmony import */ var _TemplateControl_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./TemplateControl.js */ "./src/editor-sidebar/TemplateControl.js");
/* harmony import */ var _Setup_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Setup.js */ "./src/editor-sidebar/Setup.js");
/* harmony import */ var _queries_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../queries.js */ "./src/queries.js");






function DateOrTemplate() {
  const initialPostStatus = wp?.data?.select('core/editor').getEditedPostAttribute('status');
  const url = window.location.href;
  const tabarg = url.match(/tab=([^&]+)/);
  const tab = tabarg ? tabarg[1] : 'basics';
  const [openModal, setOpenModal] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('draft' == initialPostStatus || 'auto-draft' == initialPostStatus || tabarg && tabarg[1]);
  const event_id = wp?.data?.select("core/editor").getCurrentPostId();
  const {
    data,
    isLoading,
    isError
  } = (0,_queries_js__WEBPACK_IMPORTED_MODULE_5__.useRSVPDate)(event_id);
  if (isError) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Error loading event date");
  const eventdata = isLoading ? rsvpmaker_ajax.eventdata : data.data;
  console.log('eventdata DateOrTemplate', eventdata);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "date-or-template"
  }, rsvpmaker_ajax.top_message, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Setup_js__WEBPACK_IMPORTED_MODULE_4__["default"], {
    open: openModal,
    setOpenModal: setOpenModal,
    tab: tab,
    eventdata: eventdata
  }), !rsvpmaker_ajax.special && rsvpmaker.post_type == 'rsvpmaker' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, !openModal && rsvpmaker.post_type == 'rsvpmaker' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DateTimeMaker_js__WEBPACK_IMPORTED_MODULE_2__["default"], {
    event_id: event_id,
    eventdata: eventdata
  })), rsvpmaker.post_type == 'rsvpmaker_template' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_TemplateControl_js__WEBPACK_IMPORTED_MODULE_3__["default"], null));
}

/***/ }),

/***/ "./src/editor-sidebar/FormSetup.js":
/*!*****************************************!*\
  !*** ./src/editor-sidebar/FormSetup.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FormSetup)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");
/* harmony import */ var _Forms_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Forms.js */ "./src/Forms.js");



const queryClient = new react_query__WEBPACK_IMPORTED_MODULE_2__.QueryClient();

function FormSetup(props) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_query__WEBPACK_IMPORTED_MODULE_2__.QueryClientProvider, {
    client: queryClient
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Forms_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    form_id: rsvpmaker_ajax.form_id,
    event_id: rsvpmaker_ajax.event_id
  }));
}
;

/***/ }),

/***/ "./src/editor-sidebar/Pricing.js":
/*!***************************************!*\
  !*** ./src/editor-sidebar/Pricing.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Pricing)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _http_common_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../http-common.js */ "./src/http-common.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");
/* harmony import */ var _metadata_components_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./metadata_components.js */ "./src/editor-sidebar/metadata_components.js");






const {
  __
} = wp.i18n; // Import __() from wp.i18n

function Pricing() {
  const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [priceToAdd, setPriceToAdd] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('0.00');
  const [unitToAdd, setUnitToAdd] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [deadlineDate, setDeadlineDate] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [deadlineTime, setDeadlineTime] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [multiple, setMultiple] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [toUpdate, setToUpdate] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(-1);
  const [code, setCode] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [discount, setDiscount] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('0.00');
  const [method, setMethod] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('percent');
  const [codeToUpdate, setCodeToUpdate] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(-1);
  function fetchPricing() {
    return _http_common_js__WEBPACK_IMPORTED_MODULE_3__["default"].get('pricing?event_id=' + rsvpmaker_ajax.event_id);
  }
  const {
    data,
    isLoading,
    isError
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_4__.useQuery)(['pricing'], fetchPricing, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      console.log('rsvp pricing query', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving pricing', err);
    },
    refetchInterval: false
  });
  const queryClient = (0,react_query__WEBPACK_IMPORTED_MODULE_4__.useQueryClient)();
  async function updatePricing(command) {
    return await _http_common_js__WEBPACK_IMPORTED_MODULE_3__["default"].post('pricing?event_id=' + rsvpmaker_ajax.event_id, command);
  }
  if (isError) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Error loading pricing data.");
  const {
    mutate: mutatePricing
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_4__.useMutation)(updatePricing, {
    onMutate: async command => {
      const previousValue = queryClient.getQueryData(['pricing']);
      await queryClient.cancelQueries(['pricing']);
      queryClient.setQueryData(['pricing'], oldQueryData => {
        const {
          data
        } = oldQueryData;
        data.change = command.change;
        const newdata = {
          ...oldQueryData,
          data: data
        };
        return newdata;
      });
      //makeNotification('Updating ...');
      return {
        previousValue
      };
    },
    onSettled: (data, error, variables, context) => {
      queryClient.invalidateQueries(['pricing']);
    },
    onSuccess: (data, error, variables, context) => {
      console.log('updated', data);
      setStatus('');
      queryClient.setQueryData(['pricing'], data);
    },
    onError: (err, variables, context) => {
      console.log('update message error', err);
    }
  });
  function postPrice() {
    setStatus('Posting ...');
    const change = [...pricing];
    if (toUpdate < 0) change.push({
      'unit': unitToAdd,
      'price': priceToAdd,
      'deadlineDate': deadlineDate,
      'deadlineTime': deadlineTime,
      'price_multiple': multiple
    });else change[toUpdate] = {
      'unit': unitToAdd,
      'price': priceToAdd,
      'deadlineDate': deadlineDate,
      'deadlineTime': deadlineTime,
      'price_multiple': multiple
    };
    mutatePricing({
      'update': 'pricing',
      'change': change
    });
    setDeadlineDate('');
    setDeadlineTime('');
    setMultiple(1);
    setToUpdate(-1);
    setUnitToAdd('');
    setPriceToAdd('0.00');
  }
  function deletePrice(index) {
    setStatus('Posting ...');
    const change = [...pricing];
    delete change[index];
    mutatePricing({
      'update': 'pricing',
      'change': change
    });
  }
  function deleteCode(index) {
    setStatus('Posting ...');
    const change = [...coupon_codes];
    delete change[index];
    mutatePricing({
      'update': 'coupon_codes',
      'change': change
    });
  }
  function postCode() {
    const codes = [...coupon_codes];
    const discounts = [...coupon_discounts];
    const methods = [...coupon_methods];
    if (codeToUpdate < 0) {
      codes.push(code);
      discounts.push(discount);
      methods.push(method);
    } else {
      codes[codeToUpdate] = code;
      discounts[codeToUpdate] = discount;
      methods[codeToUpdate] = method;
    }
    setStatus('Posting ...');
    mutatePricing({
      'update': 'coupon_codes',
      'change': {
        'coupon_codes': codes,
        'coupon_discounts': discounts,
        'coupon_methods': methods
      }
    });
    setCode('');
    setMethod('');
    setDiscount('percent');
    setCodeToUpdate(-1);
  }
  function displayDiscount(discount, method) {
    if ('percent' == method) return discount * 100 + '% off';else return discount + ' discount';
  }
  const pricingdata = isLoading ? null : data.data;
  const pricing = isLoading ? [] : pricingdata.pricing;
  const coupon_codes = isLoading || !pricingdata.coupon_codes ? [] : pricingdata.coupon_codes;
  const coupon_methods = isLoading || !pricingdata.coupon_methods ? [] : pricingdata.coupon_methods;
  const coupon_discounts = isLoading || !pricingdata.coupon_discounts ? [] : pricingdata.coupon_discounts;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_5__.MetaSelectControl, {
    label: "Payment Gateway",
    metaKey: "_payment_gateway",
    options: rsvpmaker_ajax.payment_gateway_options.map(value => {
      return {
        'label': value,
        'value': value
      };
    })
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_5__.MetaTextControl, {
    label: "Currency",
    metaKey: "_rsvp_currency"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_5__.MetaRadioControl, {
    label: "Price Calculation",
    metaKey: "_rsvp_count_party",
    options: [{
      'label': __('Multiply by size of party'),
      'value': '1'
    }, {
      'label': __('Let user specify number of admissions per category'),
      'value': '0'
    }]
  })), isLoading && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Loading prices ..."), pricing.map((item, index) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, item.unit, ": ", item.price, " ", item.niceDeadline && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "deadline: ", item.niceDeadline), " multiple: ", item.price_multiple, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => {
        console.log('click on index', index);
        setToUpdate(index);
        setUnitToAdd(item.unit);
        setPriceToAdd(item.price);
        setMultiple(item.price_multiple);
        setDeadlineDate(item.deadlineDate);
        setDeadlineTime(item.deadlineTime);
      }
    }, "Edit"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => {
        deletePrice(index);
      }
    }, "Delete"));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Unit"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    value: unitToAdd,
    onChange: setUnitToAdd
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Price"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    value: priceToAdd,
    onChange: setPriceToAdd
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Deadline (optional)"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "date",
    value: deadlineDate,
    onChange: e => {
      setDeadlineDate(e.target.value);
    }
  }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "time",
    value: deadlineTime,
    onChange: e => {
      setDeadlineTime(e.target.value);
    }
  }), " ", (deadlineDate || deadlineTime) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => {
      setDeadlineDate('');
      setDeadlineTime('');
    }
  }, "Remove Deadline"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Example: early bird rate to register for a conference.")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Multiple (optional)"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    value: multiple,
    onChange: e => {
      setMultiple(e.target.value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Example: price for a table of 8 at a dinner, rather than a single registration.")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    disabled: isLoading,
    onClick: postPrice
  }, toUpdate > -1 ? 'Update Price' : 'Add Price'), " ", status), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Discounts"), isLoading && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Loading discounts ..."), coupon_codes.map((code, index) => {
    let method = coupon_methods[index];
    let discount = coupon_discounts[index];
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Code: ", code, " ", displayDiscount(discount, method), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => {
        console.log('click on index', index);
        setCodeToUpdate(index);
        setCode(code);
        setMethod(method);
        setDiscount(discount);
      }
    }, "Edit"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => {
        deleteCode(index);
      }
    }, "Delete")), " ");
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: "Coupon Code",
    value: code,
    onChange: value => {
      setCode(value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.RadioControl, {
    label: "Method",
    selected: method,
    options: [{
      'label': 'Discount Amount',
      'value': 'amount'
    }, {
      'label': 'Discount Percent',
      'value': 'percent'
    }],
    onChange: value => {
      setMethod(value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: "Discount",
    value: discount,
    onChange: value => {
      setDiscount(value);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, displayDiscount(discount, method)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    disabled: isLoading,
    onClick: postCode
  }, codeToUpdate > -1 ? 'Update Code' : 'Add Code'), " ", status)));
}

/***/ }),

/***/ "./src/editor-sidebar/Setup.js":
/*!*************************************!*\
  !*** ./src/editor-sidebar/Setup.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Setup)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _metadata_components_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./metadata_components.js */ "./src/editor-sidebar/metadata_components.js");
/* harmony import */ var _DateTimeMaker_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../DateTimeMaker.js */ "./src/DateTimeMaker.js");
/* harmony import */ var _TemplateControl_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./TemplateControl.js */ "./src/editor-sidebar/TemplateControl.js");
/* harmony import */ var _FormSetup_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FormSetup.js */ "./src/editor-sidebar/FormSetup.js");
/* harmony import */ var _Confirmation_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Confirmation.js */ "./src/editor-sidebar/Confirmation.js");
/* harmony import */ var _Pricing_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Pricing.js */ "./src/editor-sidebar/Pricing.js");


const {
  Modal,
  TabPanel,
  Guide,
  GuidePage,
  ToggleControl,
  Panel,
  PanelBody,
  PanelRow,
  Flex,
  FlexBlock,
  FlexItem
} = wp.components;






const {
  __
} = wp.i18n; // Import __() from wp.i18n

const onSelect = tabName => {
  console.log('Selecting tab', tabName);
};
function Setup(props) {
  const [isOpen, setOpen] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.open);
  const {
    tab,
    eventdata
  } = props;
  const start = tab ? tab : 'basics';
  console.log('setup start', start);
  function close() {
    setOpen(false);
    if (props.setOpenModal) props.setOpenModal(false);
  }
  function open() {
    setOpen(true);
    if (props.setOpenModal) props.setOpenModal(true);
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, !isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: open
  }, "RSVP / Event Options"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Click to see more event options")), isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Modal, {
    className: "rsvpmaker-setup-modal",
    title: "RSVP / Event Options",
    onRequestClose: close
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(TabPanel, {
    className: "rsvpmaker-tab-panel",
    activeClass: "is-active",
    onSelect: onSelect,
    initialTabName: start,
    tabs: [{
      name: 'basics',
      title: 'Basic Settings',
      className: 'rsvpmaker-modal-tab'
    }, {
      name: 'form',
      title: 'RSVP Form',
      className: 'rsvpmaker-modal-tab'
    }, {
      name: 'confirmation',
      title: 'Confirmation and Reminder Messages',
      className: 'rsvpmaker-modal-tab'
    }, {
      name: 'pricing',
      title: 'Pricing',
      className: 'rsvpmaker-modal-tab'
    }]
  }, tab => {
    if ('basics' == tab.name) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsvpsettings-tab-contents"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Basics, {
      eventdata: eventdata
    }));else if ('form' == tab.name) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsvpsettings-tab-contents"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Form, {
      form_id: rsvpmaker_ajax.form_id,
      event_id: rsvpmaker_ajax.event_id
    }));else if ('confirmation' == tab.name) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsvpsettings-tab-contents"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Confirmation_js__WEBPACK_IMPORTED_MODULE_6__["default"], null));else if ('pricing' == tab.name) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsvpsettings-tab-contents"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Pricing_js__WEBPACK_IMPORTED_MODULE_7__["default"], null));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: close
  }, "Close"))));
}
function Form() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, rsvpmaker_ajax.form_fields), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, rsvpmaker_ajax.form_type)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Login required to RSVP", 'rsvpmaker'),
    metaKey: "_rsvp_login_required"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Captcha security challenge", 'rsvpmaker'),
    metaKey: "_rsvp_captcha"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Show Yes/No Options on Registration Form", 'rsvpmaker'),
    metaKey: "_rsvp_yesno"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Show Date and Time on Form", 'rsvpmaker'),
    metaKey: "_rsvp_form_show_date"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __('Maximum number of participants (0 for no limit)', 'rsvpmaker'),
    metaKey: "_rsvp_max"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextareaControl, {
    label: __('Form Instructions for User', 'rsvpmaker'),
    metaKey: "_rsvp_instructions"
  }), rsvpmaker.post_type == 'rsvpmaker' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTimestampControl, {
    label: "Registration Start Date (optional)",
    metaKey: "_rsvp_start"
  }), rsvpmaker.post_type == 'rsvpmaker' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTimestampControl, {
    label: "Registration Deadline (optional)",
    metaKey: "_rsvp_deadline"
  }), rsvpmaker.post_type == 'rsvpmaker_template' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Registration Start Date (optional)"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __('Start Date, Days Before', 'rsvpmaker'),
    metaKey: "_rsvp_reg_daysbefore"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __('Start Date, Hours Before', 'rsvpmaker'),
    metaKey: "_rsvp_reg_hours"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Registration Deadline (optional)"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __('Deadline, Days Before', 'rsvpmaker'),
    metaKey: "_rsvp_deadline_daysbefore"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __('Deadline, Hours Before', 'rsvpmaker'),
    metaKey: "_rsvp_deadline_hours"
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_FormSetup_js__WEBPACK_IMPORTED_MODULE_5__["default"], null));
}
function Reminders() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Form controls go here");
}
function Basics(props) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "guide-page-1-columns"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsvpguide-datetime"
  }, rsvpmaker.post_type == 'rsvpmaker' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DateTimeMaker_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    event_id: rsvpmaker_rest.post_id,
    eventdata: props.eventdata
  }), rsvpmaker.post_type == 'rsvpmaker_template' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_TemplateControl_js__WEBPACK_IMPORTED_MODULE_4__["default"], null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "guide-options-column"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __('Collect RSVPs', 'rsvpmaker'),
    metaKey: "_rsvp_on"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Panel, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: "Display",
    icon: "admin-settings",
    initialOpen: true
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __('"Show Add to Google/Outlook Calendar Icons" ', 'rsvpmaker'),
    metaKey: "_calendar_icons"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Add Timezone to Date", 'rsvpmaker'),
    metaKey: "_add_timezone"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Show Timezone Conversion Button", 'rsvpmaker'),
    metaKey: "_convert_timezone"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Show RSVP Count", 'rsvpmaker'),
    metaKey: "_rsvp_count"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaSelectControl, {
    label: __("Display attendee names / RSVP note field", 'rsvpmaker'),
    metaKey: "_rsvp_show_attendees",
    options: [{
      label: 'No',
      value: '0'
    }, {
      label: 'Yes',
      value: '1'
    }, {
      label: 'Only for Logged In Users',
      value: '2'
    }]
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __("Notifications / Reminders", 'rsvpmaker'),
    icon: "email",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    title: __("Send notifications to:", 'rsvpmaker'),
    metaKey: "_rsvp_to"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Send Confirmation Email", 'rsvpmaker'),
    metaKey: "_rsvp_rsvpmaker_send_confirmation_email"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __("Confirm AFTER Payment", 'rsvpmaker'),
    metaKey: "_rsvp_confirmation_after_payment"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __('"Include Event Content with Confirmation"', 'rsvpmaker'),
    metaKey: "_rsvp_confirmation_include_event"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, __('Confirmation Message (exerpt)', 'rsvpmaker'), ": ", rsvpmaker_ajax.confirmation_excerpt), rsvpmaker_ajax.confirmation_links.map(function (x) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: x.href
    }, x.title));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: rsvpmaker_ajax.reminders
  }, __('Create / Edit Reminders'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaSelectControl, {
    label: __("Email Template for Confirmations", 'rsvpmaker'),
    metaKey: "rsvp_tx_template",
    options: rsvpmaker_ajax.rsvp_tx_template_choices
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Venue:", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    title: __("Venue", 'rsvpmaker'),
    metaKey: "venue"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, __('A street address or web address to include on the calendar invite attachment included with confirmations. If not specifed, RSVPMaker includes a link to the event post.', 'rsvpmaker')))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: "Related",
    icon: "admin-links",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: wp.data.select('core/editor').getPermalink()
  }, __('View Event', 'rsvpmaker'))), rsvpmaker_ajax.related_document_links.map(function (x) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
      class: x.class
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: x.href
    }, x.title));
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __("Pricing", 'rsvpmaker'),
    icon: "smiley",
    initialOpen: false
  }, rsvpmaker_ajax.complex_pricing != '' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, rsvpmaker_ajax.complex_pricing), rsvpmaker_ajax.complex_pricing == '' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __("Label for Payments"),
    metaKey: "simple_price_label"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaTextControl, {
    label: __("Price"),
    metaKey: "simple_price"
  })), rsvpmaker_ajax.edit_payment_confirmation != '' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "See ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Confirmation/Notifications"), " for paymment confirmation message."), rsvpmaker_ajax.edit_payment_confirmation == '' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, __('Neither PayPal nor Stripe is active', 'rsvpmaker'))))));
}

/***/ }),

/***/ "./src/editor-sidebar/TemplateControl.js":
/*!***********************************************!*\
  !*** ./src/editor-sidebar/TemplateControl.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TemplateControl)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _metadata_components_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./metadata_components.js */ "./src/editor-sidebar/metadata_components.js");



const {
  __
} = wp.i18n; // Import __() from wp.i18n

function TemplateControl() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "RSVPMaker Template ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: rsvpmaker_ajax.projected_url
  }, "(Create/Update)")), rsvpmaker_ajax.top_message, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    class: "sked_frequency"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "varies"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Varies",
    metaKey: "_sked_Varies"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "weeknumber"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "First",
    metaKey: "_sked_First"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "weeknumber"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Second",
    metaKey: "_sked_Second"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "weeknumber"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Third",
    metaKey: "_sked_Third"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "weeknumber"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Fourth",
    metaKey: "_sked_Fourth"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "weeknumber"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Last",
    metaKey: "_sked_Last"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    class: "every"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Every",
    metaKey: "_sked_Every"
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Sunday",
    metaKey: "_sked_Sunday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Monday",
    metaKey: "_sked_Monday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Tuesday",
    metaKey: "_sked_Tuesday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Wednesday",
    metaKey: "_sked_Wednesday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Thursday",
    metaKey: "_sked_Thursday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Friday",
    metaKey: "_sked_Friday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Saturday",
    metaKey: "_sked_Saturday"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaSelectControl, {
    label: __('Start Time (hour)', 'rsvpmaker'),
    metaKey: "_sked_hour",
    options: [{
      label: '12 midnight',
      value: '00'
    }, {
      label: '1 am / 01:',
      value: '01'
    }, {
      label: '2 am / 02:',
      value: '02'
    }, {
      label: '3 am / 03:',
      value: '03'
    }, {
      label: '4 am / 04:',
      value: '04'
    }, {
      label: '5 am / 05:',
      value: '05'
    }, {
      label: '6 am / 06:',
      value: '06'
    }, {
      label: '7 am / 07:',
      value: '07'
    }, {
      label: '8 am / 08:',
      value: '08'
    }, {
      label: '9 am / 09:',
      value: '09'
    }, {
      label: '10 am / 10:',
      value: '10'
    }, {
      label: '11 am / 11:',
      value: '11'
    }, {
      label: '12 noon / 12:',
      value: '12'
    }, {
      label: '1 pm / 13:',
      value: '13'
    }, {
      label: '2 pm / 14:',
      value: '14'
    }, {
      label: '3 pm / 15:',
      value: '15'
    }, {
      label: '4 pm / 16:',
      value: '16'
    }, {
      label: '5 pm / 17:',
      value: '17'
    }, {
      label: '6 pm / 18:',
      value: '18'
    }, {
      label: '7 pm / 19:',
      value: '19'
    }, {
      label: '8 pm / 20:',
      value: '20'
    }, {
      label: '9 pm / 21:',
      value: '21'
    }, {
      label: '10 pm / 22:',
      value: '22'
    }, {
      label: '11 pm / 23:',
      value: '23'
    }]
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaSelectControl, {
    label: __('Start Time (minutes)', 'rsvpmaker'),
    metaKey: "_sked_minutes",
    options: [{
      label: '00',
      value: '00'
    }, {
      label: '01',
      value: '01'
    }, {
      label: '02',
      value: '02'
    }, {
      label: '03',
      value: '03'
    }, {
      label: '04',
      value: '04'
    }, {
      label: '05',
      value: '05'
    }, {
      label: '06',
      value: '06'
    }, {
      label: '07',
      value: '07'
    }, {
      label: '08',
      value: '08'
    }, {
      label: '09',
      value: '09'
    }, {
      label: '10',
      value: '10'
    }, {
      label: '11',
      value: '11'
    }, {
      label: '12',
      value: '12'
    }, {
      label: '13',
      value: '13'
    }, {
      label: '14',
      value: '14'
    }, {
      label: '15',
      value: '15'
    }, {
      label: '16',
      value: '16'
    }, {
      label: '17',
      value: '17'
    }, {
      label: '18',
      value: '18'
    }, {
      label: '19',
      value: '19'
    }, {
      label: '20',
      value: '20'
    }, {
      label: '21',
      value: '21'
    }, {
      label: '22',
      value: '22'
    }, {
      label: '23',
      value: '23'
    }, {
      label: '24',
      value: '24'
    }, {
      label: '25',
      value: '25'
    }, {
      label: '26',
      value: '26'
    }, {
      label: '27',
      value: '27'
    }, {
      label: '28',
      value: '28'
    }, {
      label: '29',
      value: '29'
    }, {
      label: '30',
      value: '30'
    }, {
      label: '31',
      value: '31'
    }, {
      label: '32',
      value: '32'
    }, {
      label: '33',
      value: '33'
    }, {
      label: '34',
      value: '34'
    }, {
      label: '35',
      value: '35'
    }, {
      label: '36',
      value: '36'
    }, {
      label: '37',
      value: '37'
    }, {
      label: '38',
      value: '38'
    }, {
      label: '39',
      value: '39'
    }, {
      label: '40',
      value: '40'
    }, {
      label: '41',
      value: '41'
    }, {
      label: '42',
      value: '42'
    }, {
      label: '43',
      value: '43'
    }, {
      label: '44',
      value: '44'
    }, {
      label: '45',
      value: '45'
    }, {
      label: '46',
      value: '46'
    }, {
      label: '47',
      value: '47'
    }, {
      label: '48',
      value: '48'
    }, {
      label: '49',
      value: '49'
    }, {
      label: '50',
      value: '50'
    }, {
      label: '51',
      value: '51'
    }, {
      label: '52',
      value: '52'
    }, {
      label: '53',
      value: '53'
    }, {
      label: '54',
      value: '54'
    }, {
      label: '55',
      value: '55'
    }, {
      label: '56',
      value: '56'
    }, {
      label: '57',
      value: '57'
    }, {
      label: '58',
      value: '58'
    }, {
      label: '59',
      value: '59'
    }]
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaEndDateControl, {
    type: "template",
    statusKey: "_sked_duration",
    timeKey: "_sked_end"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: "Auto Add Dates",
    metaKey: "rsvpautorenew",
    help: "Automatically add dates according to this schedule"
  })));
}

/***/ }),

/***/ "./src/editor-sidebar/TemplateProjected.js":
/*!*************************************************!*\
  !*** ./src/editor-sidebar/TemplateProjected.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TemplateProjected)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Checkbox__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Checkbox */ "./src/editor-sidebar/Checkbox.js");
/* harmony import */ var _SanitizedHTML_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../SanitizedHTML.js */ "./src/SanitizedHTML.js");
/* harmony import */ var _http_common_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../http-common.js */ "./src/http-common.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");


const {
  Modal
} = wp.components;


const {
  subscribe
} = wp.data;


function TemplateProjected(props) {
  const [isOpen, setOpen] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isCheckAll, setIsCheckAll] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isCheck, setIsCheck] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  function close() {
    setOpen(false);
    if (props.setOpenModal) props.setOpenModal(false);
  }
  function open() {
    setOpen(true);
  }
  let wasSavingPost = wp.data.select('core/editor').isSavingPost();
  let wasAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
  let wasPreviewingPost = wp.data.select('core/editor').isPreviewingPost();
  // determine whether to show notice
  subscribe(() => {
    const isSavingPost = wp.data.select('core/editor').isSavingPost();
    const isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
    const isPreviewingPost = wp.data.select('core/editor').isPreviewingPost();
    const hasActiveMetaBoxes = wp.data.select('core/edit-post').hasMetaBoxes();

    // Save metaboxes on save completion, except for autosaves that are not a post preview.
    const shouldTriggerTemplateNotice = wasSavingPost && !isSavingPost && !wasAutosavingPost || wasAutosavingPost && wasPreviewingPost && !isPreviewingPost;

    // Save current state for next inspection.
    wasSavingPost = isSavingPost;
    wasAutosavingPost = isAutosavingPost;
    wasPreviewingPost = isPreviewingPost;
    if (shouldTriggerTemplateNotice) {
      setOpen(true);
    }
  });
  function fetchProjected() {
    const parts = window.location.href.split('?');
    return _http_common_js__WEBPACK_IMPORTED_MODULE_4__["default"].get('template_projected?' + parts[1]);
  }
  const {
    data,
    isLoading,
    isError
  } = (0,react_query__WEBPACK_IMPORTED_MODULE_5__.useQuery)(['template_projected'], fetchProjected, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      console.log('template_projected', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving template_projected', err);
    },
    refetchInterval: false
  });
  if (isError) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Error loading projected dates.");
  const handleSelectAll = e => {
    setIsCheckAll(!isCheckAll);
    const mod = tdata.dates.map(li => li.id);
    console.log('selectall dates', tdata.dates);
    console.log('selectall mod is check', mod);
    setIsCheck(mod);
    if (isCheckAll) {
      setIsCheck([]);
    }
  };
  const handleClick = e => {
    const {
      id,
      checked
    } = e.target;
    console.log('handleclick id', id);
    setIsCheck([...isCheck, id]);
    if (!checked) {
      setIsCheck(isCheck.filter(item => item !== id));
    }
  };
  const tdata = data ? data.data : null;
  let catalog = [];
  if (!isLoading && tdata) {
    console.log('tdata loaded', tdata);
    console.log('tdata dates', tdata.dates);
    catalog = tdata.dates.map((item, i) => {
      if ('existing' == item.type) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Checkbox__WEBPACK_IMPORTED_MODULE_2__["default"], {
        id: item.id,
        isChecked: isCheck.includes(item.id),
        handleClick: handleClick,
        name: 'update_from_template[' + i + ']',
        type: "checkbox",
        value: item.event
      }), " ", item.prettydate + ' ' + item.note, item.dups.sametime.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "alertnotice"
      }, "Scheduled for the same time: ", item.dups.sametime.map(d => {
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, d.post_title, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          href: d.permalink
        }, "View"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          href: d.edit
        }, "Edit"), " ");
      })), item.dups.sameday.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "alertnotice"
      }, "Scheduled for the same day: ", item.dups.sameday.map(d => {
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, d.post_title, " ", d.prettydate, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          href: d.permalink
        }, "View"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          href: d.edit
        }, "Edit"), " ");
      })));else return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Checkbox__WEBPACK_IMPORTED_MODULE_2__["default"], {
        id: item.id,
        isChecked: isCheck.includes(item.id),
        handleClick: handleClick,
        name: 'recur_check[' + i + ']',
        type: "checkbox",
        value: i
      }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        size: "4",
        type: "text",
        name: 'recur_year[' + i + ']',
        defaultValue: item.year
      }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        size: "2",
        type: "text",
        name: 'recur_month[' + i + ']',
        defaultValue: item.month
      }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        size: "2",
        type: "text",
        name: 'recur_day[' + i + ']',
        defaultValue: item.day
      }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        type: "text",
        name: 'recur_title[' + i + ']',
        defaultValue: tdata.title
      }), " ", item.note && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
        className: "alertnotice"
      }, item.note), item.dups.sametime.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Scheduled for the same time: ", item.dups.sametime.map(d => {
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, d.post_title, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          target: "_blank",
          href: d.permalink
        }, "View"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          target: "_blank",
          href: d.edit
        }, "Edit"), " ");
      })), item.dups.sameday.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Scheduled for the same day: ", item.dups.sameday.map(d => {
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, d.post_title, " ", d.prettydate, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          target: "_blank",
          href: d.permalink
        }, "View"), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          target: "_blank",
          href: d.edit
        }, "Edit"), " ");
      })));
    });
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-calendar-check",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"
  })), !isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: open
  }, "Create/Update"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Click to see more event options")), isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Modal, {
    className: "rsvpmaker-create-update",
    title: "Create/Update from Template",
    onRequestClose: close
  }, isLoading && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Loading ..."), !isLoading && tdata && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    id: "create-update-form",
    style: {
      'width': '800px',
      'paddingBottom': '100px'
    },
    method: "post",
    action: tdata.action
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "create-update-controls",
    style: {
      'width': '150px',
      'position': 'sticky',
      'float': 'right',
      'backgroundColor': '#ffffff'
    }
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "New post status", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "newstatus",
    value: "publish",
    checked: "checked"
  }), " Published ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "newstatus",
    value: "draft"
  }), " Draft"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "hidden",
    name: "timelord",
    value: rsvpmaker_rest.timelord
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", null, "Create/Update")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Checkbox__WEBPACK_IMPORTED_MODULE_2__["default"], {
    type: "checkbox",
    name: "selectAll",
    id: "selectAll",
    handleClick: handleSelectAll,
    isChecked: isCheckAll,
    value: 1
  }), " Check all", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dates"
  }, catalog))));
}

/***/ }),

/***/ "./src/editor-sidebar/metadata_components.js":
/*!***************************************************!*\
  !*** ./src/editor-sidebar/metadata_components.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "MetaDateControl": () => (/* binding */ MetaDateControl),
/* harmony export */   "MetaEndDateControl": () => (/* binding */ MetaEndDateControl),
/* harmony export */   "MetaEndDateTimeControl": () => (/* binding */ MetaEndDateTimeControl),
/* harmony export */   "MetaFormToggle": () => (/* binding */ MetaFormToggle),
/* harmony export */   "MetaRadioControl": () => (/* binding */ MetaRadioControl),
/* harmony export */   "MetaSelectControl": () => (/* binding */ MetaSelectControl),
/* harmony export */   "MetaTextControl": () => (/* binding */ MetaTextControl),
/* harmony export */   "MetaTextareaControl": () => (/* binding */ MetaTextareaControl),
/* harmony export */   "MetaTimestampControl": () => (/* binding */ MetaTimestampControl)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/utils */ "@wordpress/utils");
/* harmony import */ var _wordpress_utils__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_utils__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/date */ "@wordpress/date");
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_date__WEBPACK_IMPORTED_MODULE_4__);


const {
  __
} = wp.i18n; // Import __() from wp.i18n
//const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const el = wp.element.createElement;
const {
  DateTimePicker,
  TimePicker,
  RadioControl,
  SelectControl,
  TextControl,
  TextareaControl,
  FormToggle
} = wp.components;
const {
  withSelect,
  withDispatch
} = wp.data;
const {
  Fragment
} = wp.element;



const settings = (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_4__.__experimentalGetSettings)();
//experimental deprecated after WordPress 6.1 , switch to getSettings() : 

const is12HourTime = /a(?!\\)/i.test(settings.formats.time.toLowerCase() // Test only the lower case a
.replace(/\\\\/g, '') // Replace "//" with empty strings
.split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash
);

function HourOptions() {
  var hourarray = [];
  for (var i = 0; i < 24; i++) hourarray.push(i);
  return hourarray.map(function (hour) {
    var displayhour = '';
    var valuehour = '';
    var ampm = '';
    if (hour < 10) valuehour = displayhour = '0' + hour.toString();else valuehour = displayhour = hour.toString();
    if (is12HourTime) {
      if (hour > 12) {
        displayhour = (hour - 12).toString();
        ampm = 'pm';
      } else if (hour == 12) {
        displayhour = hour.toString();
        ampm = 'pm';
      } else if (hour == 0) {
        displayhour = __('midnight', 'rsvpmaker');
      } else {
        displayhour = hour.toString();
        ampm = 'am';
      }
    }
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: valuehour
    }, displayhour, " ", ampm);
  });
}
function MinutesOptions() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "00"
  }, "00"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "15"
  }, "15"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "30"
  }, "30"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "45"
  }, "45"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "01"
  }, "01"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "02"
  }, "02"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "03"
  }, "03"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "04"
  }, "04"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "05"
  }, "05"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "06"
  }, "06"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "07"
  }, "07"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "08"
  }, "08"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "09"
  }, "09"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "10"
  }, "10"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "11"
  }, "11"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "12"
  }, "12"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "13"
  }, "13"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "14"
  }, "14"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "15"
  }, "15"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "16"
  }, "16"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "17"
  }, "17"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "18"
  }, "18"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "19"
  }, "19"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "20"
  }, "20"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "21"
  }, "21"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "22"
  }, "22"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "23"
  }, "23"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "24"
  }, "24"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "25"
  }, "25"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "26"
  }, "26"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "27"
  }, "27"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "28"
  }, "28"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "29"
  }, "29"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "30"
  }, "30"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "31"
  }, "31"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "32"
  }, "32"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "33"
  }, "33"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "34"
  }, "34"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "35"
  }, "35"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "36"
  }, "36"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "37"
  }, "37"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "38"
  }, "38"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "39"
  }, "39"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "40"
  }, "40"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "41"
  }, "41"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "42"
  }, "42"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "43"
  }, "43"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "44"
  }, "44"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "45"
  }, "45"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "46"
  }, "46"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "47"
  }, "47"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "48"
  }, "48"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "49"
  }, "49"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "50"
  }, "50"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "51"
  }, "51"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "52"
  }, "52"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "53"
  }, "53"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "54"
  }, "54"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "55"
  }, "55"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "56"
  }, "56"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "57"
  }, "57"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "58"
  }, "58"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "59"
  }, "59"));
}
var MetaTextControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  return el(TextControl, {
    label: props.label,
    value: props.metaValue,
    onChange: function (content) {
      props.setMetaValue(content);
    }
  });
});
var MetaRadioControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      console.log('onchange setMetaValue', metaValue);
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  return el(RadioControl, {
    label: props.label,
    selected: props.metaValue,
    options: props.options,
    onChange: function (content) {
      console.log('onchange to', content);
      props.setMetaValue(content);
    }
  });
});
var MetaSelectControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  return el(SelectControl, {
    label: props.label,
    value: props.metaValue,
    options: props.options,
    onChange: function (content) {
      props.setMetaValue(content);
    }
  });
});
var MetaEndDateControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      dispatch('core/editor').editPost({
        meta: {
          [props.timeKey]: metaValue
        }
      } //'_endfirsttime'
      );
    },

    setDisplay: function (value) {
      dispatch('core/editor').editPost({
        meta: {
          [props.statusKey]: value
        }
      } //'_firsttime'
      );
    }
  };
}), withSelect(function (select, props) {
  let metaValue = select('core/editor').getEditedPostAttribute('meta')[props.timeKey];
  var hour = '';
  var minutes = '';
  var parts;
  if (metaValue == 'Array') metaValue = '12:00';
  if (typeof metaValue === 'string' && metaValue.indexOf(':') > 0) parts = metaValue.split(':');else {
    parts = ['12', '00'];
    if (props.type == 'date') {
      var time = select('core/editor').getEditedPostAttribute('meta')['_rsvp_date'];
      var p = time.split('/ :/');
      var h = parseInt(p[1]) + 1;
      if (h < 10) hour = '0' + h.toString();
      hour = h.toString();
      parts = [hour, p[2]];
    } else {
      hour = select('core/editor').getEditedPostAttribute('meta')['_sked_hour'];
      minutes = select('core/editor').getEditedPostAttribute('meta')['_sked_minutes'];
      var h = parseInt(hour) + 1;
      if (h < 10) hour = '0' + h.toString();
      hour = h.toString();
      parts = [hour, minutes];
    }
  }
  let display = select('core/editor').getEditedPostAttribute('meta')[props.statusKey];
  return {
    parts: parts,
    display: display
    //metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ],
  };
}))(function (props) {
  //inner function to handle change
  function getTimeValues() {
    var hour = document.querySelector('#endhour option:checked');
    var minutes = document.querySelector('#endminutes option:checked');
    if (typeof hour === 'undefined' || !hour) hour = '12';
    if (typeof minutes === 'undefined' || !minutes) minutes = '00';
    var newend = hour.value + ':' + minutes.value;
    return newend;
  }
  function handleChange() {
    props.setMetaValue(getTimeValues());
  }
  if (typeof props.display != 'undefined' && props.display != 'set' && props.display.search('ulti') < 0) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: "Time Display",
    value: props.display,
    options: [{
      label: 'End Time Not Displayed',
      value: ''
    }, {
      label: 'Show End Time',
      value: 'set'
    }, {
      label: 'All Day / Time Not Shown',
      value: 'allday'
    }, {
      label: '2 Days / Time Not Shown',
      value: 'multi|2'
    }, {
      label: '3 Days / Time Not Shown',
      value: 'multi|3'
    }, {
      label: '4 Days / Time Not Shown',
      value: 'multi|4'
    }, {
      label: '5 Days / Time Not Shown',
      value: 'multi|5'
    }, {
      label: '6 Days / Time Not Shown',
      value: 'multi|6'
    }, {
      label: '7 Days / Time Not Shown',
      value: 'multi|7'
    }],
    onChange: function (content) {
      props.setDisplay(content);
    }
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: "Time Display",
    value: props.display,
    options: [{
      label: 'End Time Not Displayed',
      value: ''
    }, {
      label: 'Show End Time',
      value: 'set'
    }, {
      label: 'All Day / Time Not Shown',
      value: 'allday'
    }, {
      label: '2 Days / Time Not Shown',
      value: 'multi|2'
    }, {
      label: '3 Days / Time Not Shown',
      value: 'multi|3'
    }, {
      label: '4 Days / Time Not Shown',
      value: 'multi|4'
    }, {
      label: '5 Days / Time Not Shown',
      value: 'multi|5'
    }, {
      label: '6 Days / Time Not Shown',
      value: 'multi|6'
    }, {
      label: '7 Days / Time Not Shown',
      value: 'multi|7'
    }],
    onChange: function (content) {
      props.setDisplay(content);
    }
  }), "End Time", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "endhour",
    value: props.parts[0],
    onChange: handleChange
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(HourOptions, null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "endminutes",
    value: props.parts[1],
    onChange: handleChange
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(MinutesOptions, null)));
});
var MetaTemplateStartTimeControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setHour: function (metaValue) {
      dispatch('core/editor').editPost({
        meta: {
          '_sked_hour': metaValue
        }
      });
    },
    setMinutes: function (value) {
      dispatch('core/editor').editPost({
        meta: {
          '_sked_minutes': value
        }
      });
    }
  };
}), withSelect(function (select, props) {
  let hour = select('core/editor').getEditedPostAttribute('meta')['_sked_hour'];
  let minutes = select('core/editor').getEditedPostAttribute('meta')['_sked_minutes'];
  return {
    hour: hour,
    minutes: minutes
  };
}))(function (props) {
  //inner function to handle change

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Start Time:", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "starthour",
    value: props.hour,
    onChange: hour => {
      setHour(hour);
    }
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(HourOptions, null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "startminutes",
    value: props.minutes,
    onChange: minutes => {
      setMinutes(minutes);
    }
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(MinutesOptions, null)));
});
var MetaDateControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      metaValue = metaValue.replace('T', ' ');
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: 'rsvpmaker/v1/clearcache/' + rsvpmaker_ajax.event_id
      });
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  const settings = (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_4__.__experimentalGetSettings)();
  // To know if the current timezone is a 12 hour time with look for "a" in the time format
  // We also make sure this a is not escaped by a "/"
  const is12HourTime = /a(?!\\)/i.test(settings.formats.time.toLowerCase() // Test only the lower case a
  .replace(/\\\\/g, '') // Replace "//" with empty strings
  .split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash
  );

  return el(DateTimePicker, {
    label: props.label,
    is12Hour: is12HourTime,
    currentDate: props.metaValue,
    options: props.options,
    onChange: function (content) {
      props.setMetaValue(content);
    }
  });
});
var MetaTextareaControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  return el(TextareaControl, {
    label: props.label,
    value: props.metaValue,
    onChange: function (content) {
      props.setMetaValue(content);
    }
  });
});
var MetaFormToggle = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      if (metaValue == null) {
        metaValue = false; //never submit a null value
      }

      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
      //todo trigger change in week components for template
    }
  };
}), withSelect(function (select, props) {
  let value = select('core/editor').getEditedPostAttribute('meta')[props.metaKey]; //boolvalue,
  if (value == null) value = false;
  return {
    metaValue: value
  };
}))(function (props) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    class: "rsvpmaker_toggles"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(FormToggle, {
    checked: props.metaValue,
    onChange: function () {
      props.setMetaValue(!props.metaValue);
    }
  }), "\xA0", props.label, " ");
});
var MetaPrices = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue, index) {
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey[index]]: metaValue
        }
      });
      //todo trigger change in week components for template
    }
  };
}), withSelect(function (select, props) {
  let value = select('core/editor').getEditedPostAttribute('meta')[props.metaKey]; //boolvalue,
  return {
    metaValue: value
  };
}))(function (props) {
  return props.metaValue.forEach(function (value, index) {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
      value: value,
      onChange: function (value) {
        props.setMetaValue(value, index);
      }
    });
  });
});
var MetaEndDateTimeControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setDisplay: function (value) {
      dispatch('core/editor').editPost({
        meta: {
          ['_firsttime']: value
        }
      } //'_firsttime'
      );
    }
  };
}), withSelect(function (select, props) {
  let display = select('core/editor').getEditedPostAttribute('meta')['_firsttime'];
  return {
    display: display
    //endtime: metaValue,
    //metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ],
  };
}))(function (props) {
  //inner function to handle change

  if (typeof props.display != 'undefined' && props.display != 'set' && props.display.search('ulti') < 0) return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: "Time Display",
    value: props.display,
    options: [{
      label: 'End Time Not Displayed',
      value: ''
    }, {
      label: 'Show End Time',
      value: 'set'
    }, {
      label: 'All Day / Time Not Shown',
      value: 'allday'
    }, {
      label: '2 Days / Time Not Shown',
      value: 'multi|2'
    }, {
      label: '3 Days / Time Not Shown',
      value: 'multi|3'
    }, {
      label: '4 Days / Time Not Shown',
      value: 'multi|4'
    }, {
      label: '5 Days / Time Not Shown',
      value: 'multi|5'
    }, {
      label: '6 Days / Time Not Shown',
      value: 'multi|6'
    }, {
      label: '7 Days / Time Not Shown',
      value: 'multi|7'
    }],
    onChange: function (content) {
      props.setDisplay(content);
    }
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: "Time Display",
    value: props.display,
    options: [{
      label: 'End Time Not Displayed',
      value: ''
    }, {
      label: 'Show End Time',
      value: 'set'
    }, {
      label: 'All Day / Time Not Shown',
      value: 'allday'
    }],
    onChange: function (content) {
      props.setDisplay(content);
    }
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    class: "endtime",
    style: {
      backgroundColor: 'lightgray',
      padding: 5
    }
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "End Time"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(RSVPEndDateControl, {
    metaKey: "_rsvp_end_date"
  })));
});
var RSVPEndDateControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      metaValue = metaValue.replace('T', ' ');
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: 'rsvpmaker/v1/clearcache/' + rsvpmaker_ajax.event_id
      });
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  let current = select('core/editor').getEditedPostAttribute('meta')[props.metaKey];
  if (!current) {
    let datestring = select('core/editor').getEditedPostAttribute('meta')['_rsvp_dates'];
    let timestring = select('core/editor').getEditedPostAttribute('meta')['_endfirsttime'];
    if (timestring) {
      let parts = datestring.split(' ');
      current = parts[0] + ' ' + timestring;
    } else {
      let startdate = new Date(datestring);
      startdate.setTime(startdate.getTime() + 60 * 1000);
      current = startdate.getHours();
      if (current < 10) current = '0' + current;
      current = current + ':' + startdate.getMinutes() + ':00';
    }
  }
  return {
    metaValue: current
  };
}))(function (props) {
  const settings = (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_4__.__experimentalGetSettings)();
  // To know if the current timezone is a 12 hour time with look for "a" in the time format
  // We also make sure this a is not escaped by a "/"
  const is12HourTime = /a(?!\\)/i.test(settings.formats.time.toLowerCase() // Test only the lower case a
  .replace(/\\\\/g, '') // Replace "//" with empty strings
  .split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash
  );

  return el(DateTimePicker, {
    label: props.label,
    is12Hour: is12HourTime,
    currentDate: props.metaValue,
    options: props.options,
    onChange: function (content) {
      props.setMetaValue(content);
    }
  });
});
const MetaTimestampControl = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue) {
      console.log('metats dispatch', metaValue);
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey]: metaValue
        }
      });
    }
  };
}), withSelect(function (select, props) {
  console.log('withselect props', props);
  return {
    metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
  };
}))(function (props) {
  function pad(n) {
    if (n < 10) return '0' + n;else return n;
  }
  const sdate = new Date(rsvpmaker_ajax.eventdata.date);
  //subtract from js calculated dates / 1000 to get server timestamp
  console.log('meta ts props', props);
  const correction = sdate.getTime() - rsvpmaker_ajax.eventdata.ts_start * 1000;
  const metadate = new Date();
  if (props.metaValue) metadate.setTime(props.metaValue * 1000 + correction);
  const [date, setDate] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.metaValue ? metadate.getFullYear() + '-' + pad(metadate.getMonth() + 1) + '-' + pad(metadate.getDate()) : '');
  const [time, setTime] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(props.metaValue ? pad(metadate.getHours()) + ':' + pad(metadate.getMinutes()) : '');
  const [message, setMessage] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  function save() {
    const sdate = new Date(date + ' ' + time);
    props.setMetaValue((sdate.getTime() - correction) / 1000);
    setMessage('New date will be recorded when you save/publish/update');
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, props.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "date",
    value: date,
    onChange: e => {
      setDate(e.target.value);
    }
  }), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "time",
    value: time,
    onChange: e => {
      setTime(e.target.value);
    }
  }), " ", date && time && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: save
  }, "Set")), (date && !time || time && !date) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, "Enter both date and time")), message);
});
var MetaPrices = wp.compose.compose(withDispatch(function (dispatch, props) {
  return {
    setMetaValue: function (metaValue, index) {
      dispatch('core/editor').editPost({
        meta: {
          [props.metaKey[index]]: metaValue
        }
      });
      //todo trigger change in week components for template
    }
  };
}), withSelect(function (select, props) {
  let value = select('core/editor').getEditedPostAttribute('meta')[props.metaKey]; //boolvalue,
  return {
    metaValue: value
  };
}))(function (props) {
  return props.metaValue.forEach(function (value, index) {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
      value: value,
      onChange: function (value) {
        props.setMetaValue(value, index);
      }
    });
  });
});


/***/ }),

/***/ "./src/editor-sidebar/rsvpmaker-sidebar-extra.js":
/*!*******************************************************!*\
  !*** ./src/editor-sidebar/rsvpmaker-sidebar-extra.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _metadata_components_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./metadata_components.js */ "./src/editor-sidebar/metadata_components.js");
/* harmony import */ var _Setup_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Setup.js */ "./src/editor-sidebar/Setup.js");
/* harmony import */ var _DateOrTemplate_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./DateOrTemplate.js */ "./src/editor-sidebar/DateOrTemplate.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");

/**
 * BLOCK: blocknewrsvp
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

const {
  __
} = wp.i18n; // Import __() from wp.i18n
const el = wp.element.createElement;
const {
  Fragment
} = wp.element;
const {
  registerPlugin
} = wp.plugins;
const {
  PluginSidebar,
  PluginSidebarMoreMenuItem
} = wp.editPost;
const {
  Panel,
  PanelBody,
  PanelRow
} = wp.components;




const queryClient = new react_query__WEBPACK_IMPORTED_MODULE_4__.QueryClient();
const PluginRSVPMaker2023 = () => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PluginSidebarMoreMenuItem, {
    target: "plugin-rsvpmaker-extra",
    icon: "calendar-alt"
  }, "RSVPMaker"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PluginSidebar, {
    name: "plugin-rsvpmaker-extra-2023",
    title: "RSVPMaker 2023",
    icon: "calendar"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Panel, {
    header: __('RSVPMaker Event Options', 'rsvpmaker')
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __("Set Basic Options", 'rsvpmaker'),
    icon: "calendar-alt",
    initialOpen: true
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_query__WEBPACK_IMPORTED_MODULE_4__.QueryClientProvider, {
    client: queryClient
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DateOrTemplate_js__WEBPACK_IMPORTED_MODULE_3__["default"], null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: "Collect RSVPs",
    metaKey: "_rsvp_on"
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: "Related",
    icon: "admin-links",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: wp?.data?.select('core/editor').getPermalink()
  }, __('View Event', 'rsvpmaker'))), rsvpmaker_ajax.related_document_links.map(function (x) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
      class: x.class
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: x.href
    }, x.title));
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: "Display",
    icon: "admin-settings",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __('"Show Add to Google/Outlook Calendar Icons" ', 'rsvpmaker'),
    metaKey: "_calendar_icons"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Add Timezone to Date", 'rsvpmaker'),
    metaKey: "_add_timezone"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Show Timezone Conversion Button", 'rsvpmaker'),
    metaKey: "_convert_timezone"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Show RSVP Count", 'rsvpmaker'),
    metaKey: "_rsvp_count"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaSelectControl, {
    label: __("Display attendee names / RSVP note field", 'rsvpmaker'),
    metaKey: "_rsvp_show_attendees",
    options: [{
      label: 'No',
      value: '0'
    }, {
      label: 'Yes',
      value: '1'
    }, {
      label: 'Only for Logged In Users',
      value: '2'
    }]
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __("Notifications / Reminders", 'rsvpmaker'),
    icon: "email",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaTextControl, {
    title: __("Send notifications to:", 'rsvpmaker'),
    metaKey: "_rsvp_to"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Send Confirmation Email", 'rsvpmaker'),
    metaKey: "_rsvp_rsvpmaker_send_confirmation_email"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Confirm AFTER Payment", 'rsvpmaker'),
    metaKey: "_rsvp_confirmation_after_payment"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __('"Include Event Content with Confirmation"', 'rsvpmaker'),
    metaKey: "_rsvp_confirmation_include_event"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, __('Confirmation Message (exerpt)', 'rsvpmaker'), ": ", rsvpmaker_ajax.confirmation_excerpt), rsvpmaker_ajax.confirmation_links.map(function (x) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: x.href
    }, x.title));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: rsvpmaker_ajax.reminders
  }, __('Create / Edit Reminders'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaSelectControl, {
    label: __("Email Template for Confirmations", 'rsvpmaker'),
    metaKey: "rsvp_tx_template",
    options: rsvpmaker_ajax.rsvp_tx_template_choices
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Venue:", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaTextControl, {
    title: __("Venue", 'rsvpmaker'),
    metaKey: "venue"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, __('A street address or web address to include on the calendar invite attachment included with confirmations. If not specifed, RSVPMaker includes a link to the event post.', 'rsvpmaker')))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __("RSVP Form", 'rsvpmaker'),
    icon: "yes-alt",
    initialOpen: false
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, rsvpmaker_ajax.form_fields), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("em", null, rsvpmaker_ajax.form_type)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Login required to RSVP", 'rsvpmaker'),
    metaKey: "_rsvp_login_required"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Captcha security challenge", 'rsvpmaker'),
    metaKey: "_rsvp_captcha"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Show Yes/No Options on Registration Form", 'rsvpmaker'),
    metaKey: "_rsvp_yesno"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaFormToggle, {
    label: __("Show Date and Time on Form", 'rsvpmaker'),
    metaKey: "_rsvp_form_show_date"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaTextControl, {
    label: __('Maximum number of participants (0 for no limit)', 'rsvpmaker'),
    metaKey: "_rsvp_max"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_1__.MetaTextareaControl, {
    label: __('Form Instructions for User', 'rsvpmaker'),
    metaKey: "_rsvp_instructions"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Use the Show Setup Guide button, above - to edit the form without leaving this screen. Or use the link(s) below to open a form in the WordPress editor."), rsvpmaker_ajax.form_links.map(function (x) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelRow, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: x.href
    }, x.title));
  })))));
};
if ((rsvpmaker.post_type == 'rsvpmaker' || rsvpmaker.post_type == 'rsvpmaker_template') && !rsvpmaker_ajax.special) registerPlugin('plugin-rsvpmaker-2023', {
  render: PluginRSVPMaker2023
});

/***/ }),

/***/ "./src/editor-sidebar/rsvpmaker-sidebar.js":
/*!*************************************************!*\
  !*** ./src/editor-sidebar/rsvpmaker-sidebar.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _metadata_components_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./metadata_components.js */ "./src/editor-sidebar/metadata_components.js");
/* harmony import */ var _DateOrTemplate_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./DateOrTemplate.js */ "./src/editor-sidebar/DateOrTemplate.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");
/* harmony import */ var _TemplateProjected_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./TemplateProjected.js */ "./src/editor-sidebar/TemplateProjected.js");


//import './state.js';
//const { withState } = wp.compose;
const {
  subscribe
} = wp.data;
const {
  DateTimePicker
} = wp.components;
const {
  Panel,
  PanelBody,
  PanelRow
} = wp.components;



const queryClient = new react_query__WEBPACK_IMPORTED_MODULE_4__.QueryClient();

var el = wp.element.createElement;
const {
  __
} = wp.i18n; // Import __() from wp.i18n

const RSVPMakerSidebarPlugin = function () {
  if (typeof rsvpmaker_ajax === 'undefined') return null; //not an rsvpmaker post
  const initialPostStatus = wp?.data?.select('core/editor').getEditedPostAttribute('status');
  const url = window.location.href;
  const tabarg = url.match(/tab=([^&]+)/);
  const tab = tabarg ? tabarg[1] : 'basics';
  const [openModal, setOpenModal] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)('draft' == initialPostStatus || 'auto-draft' == initialPostStatus || tabarg && tabarg[1]);
  const end_times = Array();
  const end_times_display = Array();
  var datecount = '';
  var first_date = '';
  var additional_dates = '';
  var time_display = '';
  const rsvpdates = Array();
  var post_id = wp.data.select('core/editor').getEditedPostAttribute('id');
  if (rsvpmaker_ajax.special) {
    return el(wp.editPost.PluginPostStatusInfo, {}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "RSVPMaker ", __('Special Document', 'rsvpmaker')), rsvpmaker_ajax.top_message, rsvpmaker_ajax.special == 'RSVP Form' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "https://rsvpmaker.com/knowledge-base/customize-the-rsvp-form/",
      target: "_blank"
    }, __('Documentation', 'rsvpmaker'))), rsvpmaker_ajax.bottom_message));
  }
  return el(wp.editPost.PluginPostStatusInfo, {}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_query__WEBPACK_IMPORTED_MODULE_4__.QueryClientProvider, {
    client: queryClient
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DateOrTemplate_js__WEBPACK_IMPORTED_MODULE_3__["default"], null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_metadata_components_js__WEBPACK_IMPORTED_MODULE_2__.MetaFormToggle, {
    label: __('Collect RSVPs', 'rsvpmaker'),
    metaKey: "_rsvp_on"
  }), rsvpmaker_ajax.bottom_message)));
};
if (typeof rsvpmaker_ajax !== 'undefined') wp.plugins.registerPlugin('rsvpmaker-sidebar-plugin', {
  render: RSVPMakerSidebarPlugin
});
var PluginPostPublishPanel = wp.editPost.PluginPostPublishPanel;
function RSVPPluginPostPublishPanel() {
  return el(PluginPostPublishPanel, {
    className: 'rsvpmaker-post-publish-panel',
    title: __('RSVPMaker Post Published'),
    initialOpen: true
  });
}
if (rsvpmaker.post_type == 'rsvpmaker' || rsvpmaker.post_type == 'rsvpmaker_template') wp.plugins.registerPlugin('rsvpmaker-sidebar-postpublish', {
  render: RSVPPluginPostPublishPanel
});
if ("undefined" !== typeof rsvpmaker_ajax && rsvpmaker_ajax.template_url) {
  wp.data.dispatch('core/notices').createNotice('info',
  // Can be one of: success, info, warning, error.
  __('You are editing one event in a series defined by a template. To make changes you can apply to the whole series of events, switch to editing the template.'),
  // Text string to display.
  {
    id: 'rsvptemplateedit',
    //assigning an ID prevents the notice from being added repeatedly
    isDismissible: true,
    // Whether the user can dismiss the notice.
    // Any actions the user can perform.
    actions: [{
      url: rsvpmaker_ajax.template_url,
      label: rsvpmaker_ajax.template_label
    }]
  });
}
if (rsvpmaker.post_type == 'rsvpmaker' || rsvpmaker.post_type == 'rsvpmaker_template') {
  const isEditorSidebarOpened = wp.data.select('core/edit-post').isEditorSidebarOpened();
  if (!isEditorSidebarOpened) {
    wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/document');
  }
}

/***/ }),

/***/ "./src/http-common.js":
/*!****************************!*\
  !*** ./src/http-common.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (axios__WEBPACK_IMPORTED_MODULE_0___default().create({
  baseURL: '/wp-json/rsvpmaker/v1/',
  headers: {
    "Content-type": "application/json",
    'X-WP-Nonce': rsvpmaker_rest.nonce
  },
  validateStatus: function (status) {
    return status < 400; // Resolve only if the status code is less than 400
  }
}));

/***/ }),

/***/ "./src/icons.js":
/*!**********************!*\
  !*** ./src/icons.js ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Close": () => (/* binding */ Close),
/* harmony export */   "Delete": () => (/* binding */ Delete),
/* harmony export */   "Down": () => (/* binding */ Down),
/* harmony export */   "DownUp": () => (/* binding */ DownUp),
/* harmony export */   "Move": () => (/* binding */ Move),
/* harmony export */   "Plus": () => (/* binding */ Plus),
/* harmony export */   "Top": () => (/* binding */ Top),
/* harmony export */   "Up": () => (/* binding */ Up)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

function Up(props) {
  const {
    type
  } = props;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-arrow-up-circle",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Move Up ", type), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-7.5 3.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V11.5z"
  }));
}
function Top(props) {
  const {
    type
  } = props;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-arrow-up-circle-fill",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Move to Top ", type), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M16 8A8 8 0 1 0 0 8a8 8 0 0 0 16 0zm-7.5 3.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V11.5z"
  }));
}
function Down(props) {
  const {
    type
  } = props;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-arrow-down-circle",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Move Down ", type), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"
  }));
}
function Plus(props) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-plus-circle",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Insert"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"
  }));
}
function Delete(props) {
  const {
    type
  } = props;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-trash",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Delete ", type), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"
  }));
}
function Close() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-chevron-bar-contract",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("title", null, "Close Gaps (Unassigned)"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M3.646 14.854a.5.5 0 0 0 .708 0L8 11.207l3.646 3.647a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 0 0 0 .708zm0-13.708a.5.5 0 0 1 .708 0L8 4.793l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708zM1 8a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 0 1h-13A.5.5 0 0 1 1 8z"
  }));
}
function Move() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-arrows-move",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708l2-2zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10zM.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708l-2-2zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8z"
  }));
}
function DownUp() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "16",
    height: "16",
    fill: "currentColor",
    class: "bi bi-arrow-down-up",
    viewBox: "0 0 16 16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    "fill-rule": "evenodd",
    d: "M11.5 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L11 2.707V14.5a.5.5 0 0 0 .5.5zm-7-14a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L4 13.293V1.5a.5.5 0 0 1 .5-.5z"
  }));
}

/***/ }),

/***/ "./src/queries.js":
/*!************************!*\
  !*** ./src/queries.js ***!
  \************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useOptions": () => (/* binding */ useOptions),
/* harmony export */   "useOptionsMutation": () => (/* binding */ useOptionsMutation),
/* harmony export */   "useRSVPDate": () => (/* binding */ useRSVPDate),
/* harmony export */   "useRSVPDateMutation": () => (/* binding */ useRSVPDateMutation)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _http_common_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./http-common.js */ "./src/http-common.js");
/* harmony import */ var react_query__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react-query */ "./node_modules/react-query/es/index.js");



function useOptions() {
  let tab = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  function fetchOptions(queryobj) {
    return _http_common_js__WEBPACK_IMPORTED_MODULE_1__["default"].get('rsvp_options?tab=' + tab);
  }
  return (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useQuery)(['rsvp_options'], fetchOptions, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      console.log('rsvp options query', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving rsvp options', err);
    },
    refetchInterval: false
  });
}
function useOptionsMutation(setChanges, makeNotification) {
  const queryClient = (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useQueryClient)();
  async function updateOption(option) {
    return await _http_common_js__WEBPACK_IMPORTED_MODULE_1__["default"].post('rsvp_options', option);
  }
  return (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useMutation)(updateOption, {
    onMutate: async options => {
      console.log('optimistic update option', options);
      await queryClient.cancelQueries(['rsvp_options']);
      const previousValue = queryClient.getQueryData(['rsvp_options']);
      queryClient.setQueryData(['rsvp_options'], oldQueryData => {
        //function passed to setQueryData
        const {
          data
        } = oldQueryData;
        options.forEach(o => {
          if ('rsvp_options' == o.type) data.rsvp_options[o.key] = o.value;
        });
        const newdata = {
          ...oldQueryData,
          data: data
        };
        console.log('newdata optimistic update', newdata);
        return newdata;
      });
      //makeNotification('Updating ...');
      console.log('updating options');
      return {
        previousValue
      };
    },
    onSettled: (data, error, variables, context) => {
      queryClient.invalidateQueries(['rsvp_options']);
    },
    onSuccess: (data, error, variables, context) => {
      console.log('updated');
      setChanges([]);
    },
    onError: (err, variables, context) => {
      //makeNotification('Error '+err.message);
      console.log('update options error', err);
      queryClient.setQueryData("rsvp_options", context.previousValue);
    }
  });
}
function useRSVPDate(eventID) {
  function fetchRSVPDate(queryobj) {
    return _http_common_js__WEBPACK_IMPORTED_MODULE_1__["default"].get('rsvp_event_date?event_id=' + eventID);
  }
  return (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useQuery)(['rsvp_event_date'], fetchRSVPDate, {
    enabled: true,
    retry: 2,
    onSuccess: (data, error, variables, context) => {
      console.log('rsvp_event_date query', data);
    },
    onError: (err, variables, context) => {
      console.log('error retrieving rsvp_event_date', err);
    },
    refetchInterval: false
  });
}
function useRSVPDateMutation(eventID, setError) {
  const queryClient = (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useQueryClient)();
  console.log('useRSVPDateMutation called with');
  console.log('useRSVPDateMutation queryClient', queryClient);
  async function updateDate(update) {
    return await _http_common_js__WEBPACK_IMPORTED_MODULE_1__["default"].post('rsvp_event_date?event_id=' + eventID, update);
  }
  return (0,react_query__WEBPACK_IMPORTED_MODULE_2__.useMutation)(updateDate, {
    onMutate: async update => {
      console.log('optimistic update event', update);
      await queryClient.cancelQueries(['rsvp_event_date']);
      const previousValue = queryClient.getQueryData(['rsvp_event_date']);
      console.log('previousValue', previousValue);
      queryClient.setQueryData(['rsvp_event_date'], oldQueryData => {
        const {
          data
        } = oldQueryData;
        if (update.date) data.date = update.date;
        if (update.enddate) data.enddate = update.enddate;
        if (update.display_type && update.display_type != null) data.display_type = update.display_type;
        if (update.timezone) data.timezone = update.timezone;
        const newdata = {
          data: data
        };
        console.log('newdata optimistic update', newdata);
        return newdata;
      });
      console.log('updating options');
      return {
        previousValue
      };
    },
    onSettled: (data, error, variables, context) => {
      queryClient.invalidateQueries(['rsvp_event_date']);
    },
    onSuccess: (data, error, variables, context) => {
      //queryClient.setQueryData(['rsvp_event_date'],data.data);
      console.log('updated', data);
      queryClient.setQueryData(['rsvp_event_date'], data);
      queryClient.invalidateQueries(['rsvp_event_date']);
      setError('');
    },
    onError: (err, variables, context) => {
      alert('Error updating event date data on server');
      setError('Error updating event date data on server');
      //makeNotification('Error '+err.message);
      console.log('update dates error', err);
      //queryClient.setQueryData(['rsvp_event_date'], context.previousValue);
    }
  });
}

/***/ }),

/***/ "./node_modules/dompurify/dist/purify.js":
/*!***********************************************!*\
  !*** ./node_modules/dompurify/dist/purify.js ***!
  \***********************************************/
/***/ (function(module) {

/*! @license DOMPurify 3.0.2 | (c) Cure53 and other contributors | Released under the Apache license 2.0 and Mozilla Public License 2.0 | github.com/cure53/DOMPurify/blob/3.0.2/LICENSE */

(function (global, factory) {
   true ? module.exports = factory() :
  0;
})(this, (function () { 'use strict';

  const {
    entries,
    setPrototypeOf,
    isFrozen,
    getPrototypeOf,
    getOwnPropertyDescriptor
  } = Object;
  let {
    freeze,
    seal,
    create
  } = Object; // eslint-disable-line import/no-mutable-exports

  let {
    apply,
    construct
  } = typeof Reflect !== 'undefined' && Reflect;

  if (!apply) {
    apply = function apply(fun, thisValue, args) {
      return fun.apply(thisValue, args);
    };
  }

  if (!freeze) {
    freeze = function freeze(x) {
      return x;
    };
  }

  if (!seal) {
    seal = function seal(x) {
      return x;
    };
  }

  if (!construct) {
    construct = function construct(Func, args) {
      return new Func(...args);
    };
  }

  const arrayForEach = unapply(Array.prototype.forEach);
  const arrayPop = unapply(Array.prototype.pop);
  const arrayPush = unapply(Array.prototype.push);
  const stringToLowerCase = unapply(String.prototype.toLowerCase);
  const stringToString = unapply(String.prototype.toString);
  const stringMatch = unapply(String.prototype.match);
  const stringReplace = unapply(String.prototype.replace);
  const stringIndexOf = unapply(String.prototype.indexOf);
  const stringTrim = unapply(String.prototype.trim);
  const regExpTest = unapply(RegExp.prototype.test);
  const typeErrorCreate = unconstruct(TypeError);
  function unapply(func) {
    return function (thisArg) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      return apply(func, thisArg, args);
    };
  }
  function unconstruct(func) {
    return function () {
      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return construct(func, args);
    };
  }
  /* Add properties to a lookup table */

  function addToSet(set, array, transformCaseFunc) {
    transformCaseFunc = transformCaseFunc ? transformCaseFunc : stringToLowerCase;

    if (setPrototypeOf) {
      // Make 'in' and truthy checks like Boolean(set.constructor)
      // independent of any properties defined on Object.prototype.
      // Prevent prototype setters from intercepting set as a this value.
      setPrototypeOf(set, null);
    }

    let l = array.length;

    while (l--) {
      let element = array[l];

      if (typeof element === 'string') {
        const lcElement = transformCaseFunc(element);

        if (lcElement !== element) {
          // Config presets (e.g. tags.js, attrs.js) are immutable.
          if (!isFrozen(array)) {
            array[l] = lcElement;
          }

          element = lcElement;
        }
      }

      set[element] = true;
    }

    return set;
  }
  /* Shallow clone an object */

  function clone(object) {
    const newObject = create(null);

    for (const [property, value] of entries(object)) {
      newObject[property] = value;
    }

    return newObject;
  }
  /* This method automatically checks if the prop is function
   * or getter and behaves accordingly. */

  function lookupGetter(object, prop) {
    while (object !== null) {
      const desc = getOwnPropertyDescriptor(object, prop);

      if (desc) {
        if (desc.get) {
          return unapply(desc.get);
        }

        if (typeof desc.value === 'function') {
          return unapply(desc.value);
        }
      }

      object = getPrototypeOf(object);
    }

    function fallbackValue(element) {
      console.warn('fallback value for', element);
      return null;
    }

    return fallbackValue;
  }

  const html$1 = freeze(['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'big', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'content', 'data', 'datalist', 'dd', 'decorator', 'del', 'details', 'dfn', 'dialog', 'dir', 'div', 'dl', 'dt', 'element', 'em', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'marquee', 'menu', 'menuitem', 'meter', 'nav', 'nobr', 'ol', 'optgroup', 'option', 'output', 'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'select', 'shadow', 'small', 'source', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'track', 'tt', 'u', 'ul', 'var', 'video', 'wbr']); // SVG

  const svg$1 = freeze(['svg', 'a', 'altglyph', 'altglyphdef', 'altglyphitem', 'animatecolor', 'animatemotion', 'animatetransform', 'circle', 'clippath', 'defs', 'desc', 'ellipse', 'filter', 'font', 'g', 'glyph', 'glyphref', 'hkern', 'image', 'line', 'lineargradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialgradient', 'rect', 'stop', 'style', 'switch', 'symbol', 'text', 'textpath', 'title', 'tref', 'tspan', 'view', 'vkern']);
  const svgFilters = freeze(['feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence']); // List of SVG elements that are disallowed by default.
  // We still need to know them so that we can do namespace
  // checks properly in case one wants to add them to
  // allow-list.

  const svgDisallowed = freeze(['animate', 'color-profile', 'cursor', 'discard', 'fedropshadow', 'font-face', 'font-face-format', 'font-face-name', 'font-face-src', 'font-face-uri', 'foreignobject', 'hatch', 'hatchpath', 'mesh', 'meshgradient', 'meshpatch', 'meshrow', 'missing-glyph', 'script', 'set', 'solidcolor', 'unknown', 'use']);
  const mathMl$1 = freeze(['math', 'menclose', 'merror', 'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover', 'mprescripts']); // Similarly to SVG, we want to know all MathML elements,
  // even those that we disallow by default.

  const mathMlDisallowed = freeze(['maction', 'maligngroup', 'malignmark', 'mlongdiv', 'mscarries', 'mscarry', 'msgroup', 'mstack', 'msline', 'msrow', 'semantics', 'annotation', 'annotation-xml', 'mprescripts', 'none']);
  const text = freeze(['#text']);

  const html = freeze(['accept', 'action', 'align', 'alt', 'autocapitalize', 'autocomplete', 'autopictureinpicture', 'autoplay', 'background', 'bgcolor', 'border', 'capture', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'clear', 'color', 'cols', 'colspan', 'controls', 'controlslist', 'coords', 'crossorigin', 'datetime', 'decoding', 'default', 'dir', 'disabled', 'disablepictureinpicture', 'disableremoteplayback', 'download', 'draggable', 'enctype', 'enterkeyhint', 'face', 'for', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'id', 'inputmode', 'integrity', 'ismap', 'kind', 'label', 'lang', 'list', 'loading', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'minlength', 'multiple', 'muted', 'name', 'nonce', 'noshade', 'novalidate', 'nowrap', 'open', 'optimum', 'pattern', 'placeholder', 'playsinline', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'rev', 'reversed', 'role', 'rows', 'rowspan', 'spellcheck', 'scope', 'selected', 'shape', 'size', 'sizes', 'span', 'srclang', 'start', 'src', 'srcset', 'step', 'style', 'summary', 'tabindex', 'title', 'translate', 'type', 'usemap', 'valign', 'value', 'width', 'xmlns', 'slot']);
  const svg = freeze(['accent-height', 'accumulate', 'additive', 'alignment-baseline', 'ascent', 'attributename', 'attributetype', 'azimuth', 'basefrequency', 'baseline-shift', 'begin', 'bias', 'by', 'class', 'clip', 'clippathunits', 'clip-path', 'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cx', 'cy', 'd', 'dx', 'dy', 'diffuseconstant', 'direction', 'display', 'divisor', 'dur', 'edgemode', 'elevation', 'end', 'fill', 'fill-opacity', 'fill-rule', 'filter', 'filterunits', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'fx', 'fy', 'g1', 'g2', 'glyph-name', 'glyphref', 'gradientunits', 'gradienttransform', 'height', 'href', 'id', 'image-rendering', 'in', 'in2', 'k', 'k1', 'k2', 'k3', 'k4', 'kerning', 'keypoints', 'keysplines', 'keytimes', 'lang', 'lengthadjust', 'letter-spacing', 'kernelmatrix', 'kernelunitlength', 'lighting-color', 'local', 'marker-end', 'marker-mid', 'marker-start', 'markerheight', 'markerunits', 'markerwidth', 'maskcontentunits', 'maskunits', 'max', 'mask', 'media', 'method', 'mode', 'min', 'name', 'numoctaves', 'offset', 'operator', 'opacity', 'order', 'orient', 'orientation', 'origin', 'overflow', 'paint-order', 'path', 'pathlength', 'patterncontentunits', 'patterntransform', 'patternunits', 'points', 'preservealpha', 'preserveaspectratio', 'primitiveunits', 'r', 'rx', 'ry', 'radius', 'refx', 'refy', 'repeatcount', 'repeatdur', 'restart', 'result', 'rotate', 'scale', 'seed', 'shape-rendering', 'specularconstant', 'specularexponent', 'spreadmethod', 'startoffset', 'stddeviation', 'stitchtiles', 'stop-color', 'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke', 'stroke-width', 'style', 'surfacescale', 'systemlanguage', 'tabindex', 'targetx', 'targety', 'transform', 'transform-origin', 'text-anchor', 'text-decoration', 'text-rendering', 'textlength', 'type', 'u1', 'u2', 'unicode', 'values', 'viewbox', 'visibility', 'version', 'vert-adv-y', 'vert-origin-x', 'vert-origin-y', 'width', 'word-spacing', 'wrap', 'writing-mode', 'xchannelselector', 'ychannelselector', 'x', 'x1', 'x2', 'xmlns', 'y', 'y1', 'y2', 'z', 'zoomandpan']);
  const mathMl = freeze(['accent', 'accentunder', 'align', 'bevelled', 'close', 'columnsalign', 'columnlines', 'columnspan', 'denomalign', 'depth', 'dir', 'display', 'displaystyle', 'encoding', 'fence', 'frame', 'height', 'href', 'id', 'largeop', 'length', 'linethickness', 'lspace', 'lquote', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'movablelimits', 'notation', 'numalign', 'open', 'rowalign', 'rowlines', 'rowspacing', 'rowspan', 'rspace', 'rquote', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier', 'selection', 'separator', 'separators', 'stretchy', 'subscriptshift', 'supscriptshift', 'symmetric', 'voffset', 'width', 'xmlns']);
  const xml = freeze(['xlink:href', 'xml:id', 'xlink:title', 'xml:space', 'xmlns:xlink']);

  const MUSTACHE_EXPR = seal(/\{\{[\w\W]*|[\w\W]*\}\}/gm); // Specify template detection regex for SAFE_FOR_TEMPLATES mode

  const ERB_EXPR = seal(/<%[\w\W]*|[\w\W]*%>/gm);
  const TMPLIT_EXPR = seal(/\${[\w\W]*}/gm);
  const DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]/); // eslint-disable-line no-useless-escape

  const ARIA_ATTR = seal(/^aria-[\-\w]+$/); // eslint-disable-line no-useless-escape

  const IS_ALLOWED_URI = seal(/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i // eslint-disable-line no-useless-escape
  );
  const IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
  const ATTR_WHITESPACE = seal(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g // eslint-disable-line no-control-regex
  );
  const DOCTYPE_NAME = seal(/^html$/i);

  var EXPRESSIONS = /*#__PURE__*/Object.freeze({
    __proto__: null,
    MUSTACHE_EXPR: MUSTACHE_EXPR,
    ERB_EXPR: ERB_EXPR,
    TMPLIT_EXPR: TMPLIT_EXPR,
    DATA_ATTR: DATA_ATTR,
    ARIA_ATTR: ARIA_ATTR,
    IS_ALLOWED_URI: IS_ALLOWED_URI,
    IS_SCRIPT_OR_DATA: IS_SCRIPT_OR_DATA,
    ATTR_WHITESPACE: ATTR_WHITESPACE,
    DOCTYPE_NAME: DOCTYPE_NAME
  });

  const getGlobal = () => typeof window === 'undefined' ? null : window;
  /**
   * Creates a no-op policy for internal use only.
   * Don't export this function outside this module!
   * @param {?TrustedTypePolicyFactory} trustedTypes The policy factory.
   * @param {Document} document The document object (to determine policy name suffix)
   * @return {?TrustedTypePolicy} The policy created (or null, if Trusted Types
   * are not supported).
   */


  const _createTrustedTypesPolicy = function _createTrustedTypesPolicy(trustedTypes, document) {
    if (typeof trustedTypes !== 'object' || typeof trustedTypes.createPolicy !== 'function') {
      return null;
    } // Allow the callers to control the unique policy name
    // by adding a data-tt-policy-suffix to the script element with the DOMPurify.
    // Policy creation with duplicate names throws in Trusted Types.


    let suffix = null;
    const ATTR_NAME = 'data-tt-policy-suffix';

    if (document.currentScript && document.currentScript.hasAttribute(ATTR_NAME)) {
      suffix = document.currentScript.getAttribute(ATTR_NAME);
    }

    const policyName = 'dompurify' + (suffix ? '#' + suffix : '');

    try {
      return trustedTypes.createPolicy(policyName, {
        createHTML(html) {
          return html;
        },

        createScriptURL(scriptUrl) {
          return scriptUrl;
        }

      });
    } catch (_) {
      // Policy creation failed (most likely another DOMPurify script has
      // already run). Skip creating the policy, as this will only cause errors
      // if TT are enforced.
      console.warn('TrustedTypes policy ' + policyName + ' could not be created.');
      return null;
    }
  };

  function createDOMPurify() {
    let window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getGlobal();

    const DOMPurify = root => createDOMPurify(root);
    /**
     * Version label, exposed for easier checks
     * if DOMPurify is up to date or not
     */


    DOMPurify.version = '3.0.2';
    /**
     * Array of elements that DOMPurify removed during sanitation.
     * Empty if nothing was removed.
     */

    DOMPurify.removed = [];

    if (!window || !window.document || window.document.nodeType !== 9) {
      // Not running in a browser, provide a factory function
      // so that you can pass your own Window
      DOMPurify.isSupported = false;
      return DOMPurify;
    }

    const originalDocument = window.document;
    let {
      document
    } = window;
    const {
      DocumentFragment,
      HTMLTemplateElement,
      Node,
      Element,
      NodeFilter,
      NamedNodeMap = window.NamedNodeMap || window.MozNamedAttrMap,
      HTMLFormElement,
      DOMParser,
      trustedTypes
    } = window;
    const ElementPrototype = Element.prototype;
    const cloneNode = lookupGetter(ElementPrototype, 'cloneNode');
    const getNextSibling = lookupGetter(ElementPrototype, 'nextSibling');
    const getChildNodes = lookupGetter(ElementPrototype, 'childNodes');
    const getParentNode = lookupGetter(ElementPrototype, 'parentNode'); // As per issue #47, the web-components registry is inherited by a
    // new document created via createHTMLDocument. As per the spec
    // (http://w3c.github.io/webcomponents/spec/custom/#creating-and-passing-registries)
    // a new empty registry is used when creating a template contents owner
    // document, so we use that as our parent document to ensure nothing
    // is inherited.

    if (typeof HTMLTemplateElement === 'function') {
      const template = document.createElement('template');

      if (template.content && template.content.ownerDocument) {
        document = template.content.ownerDocument;
      }
    }

    const trustedTypesPolicy = _createTrustedTypesPolicy(trustedTypes, originalDocument);

    const emptyHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML('') : '';
    const {
      implementation,
      createNodeIterator,
      createDocumentFragment,
      getElementsByTagName
    } = document;
    const {
      importNode
    } = originalDocument;
    let hooks = {};
    /**
     * Expose whether this browser supports running the full DOMPurify.
     */

    DOMPurify.isSupported = typeof entries === 'function' && typeof getParentNode === 'function' && implementation && typeof implementation.createHTMLDocument !== 'undefined';
    const {
      MUSTACHE_EXPR,
      ERB_EXPR,
      TMPLIT_EXPR,
      DATA_ATTR,
      ARIA_ATTR,
      IS_SCRIPT_OR_DATA,
      ATTR_WHITESPACE
    } = EXPRESSIONS;
    let {
      IS_ALLOWED_URI: IS_ALLOWED_URI$1
    } = EXPRESSIONS;
    /**
     * We consider the elements and attributes below to be safe. Ideally
     * don't add any new ones but feel free to remove unwanted ones.
     */

    /* allowed element names */

    let ALLOWED_TAGS = null;
    const DEFAULT_ALLOWED_TAGS = addToSet({}, [...html$1, ...svg$1, ...svgFilters, ...mathMl$1, ...text]);
    /* Allowed attribute names */

    let ALLOWED_ATTR = null;
    const DEFAULT_ALLOWED_ATTR = addToSet({}, [...html, ...svg, ...mathMl, ...xml]);
    /*
     * Configure how DOMPUrify should handle custom elements and their attributes as well as customized built-in elements.
     * @property {RegExp|Function|null} tagNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any custom elements)
     * @property {RegExp|Function|null} attributeNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any attributes not on the allow list)
     * @property {boolean} allowCustomizedBuiltInElements allow custom elements derived from built-ins if they pass CUSTOM_ELEMENT_HANDLING.tagNameCheck. Default: `false`.
     */

    let CUSTOM_ELEMENT_HANDLING = Object.seal(Object.create(null, {
      tagNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      attributeNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      allowCustomizedBuiltInElements: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: false
      }
    }));
    /* Explicitly forbidden tags (overrides ALLOWED_TAGS/ADD_TAGS) */

    let FORBID_TAGS = null;
    /* Explicitly forbidden attributes (overrides ALLOWED_ATTR/ADD_ATTR) */

    let FORBID_ATTR = null;
    /* Decide if ARIA attributes are okay */

    let ALLOW_ARIA_ATTR = true;
    /* Decide if custom data attributes are okay */

    let ALLOW_DATA_ATTR = true;
    /* Decide if unknown protocols are okay */

    let ALLOW_UNKNOWN_PROTOCOLS = false;
    /* Decide if self-closing tags in attributes are allowed.
     * Usually removed due to a mXSS issue in jQuery 3.0 */

    let ALLOW_SELF_CLOSE_IN_ATTR = true;
    /* Output should be safe for common template engines.
     * This means, DOMPurify removes data attributes, mustaches and ERB
     */

    let SAFE_FOR_TEMPLATES = false;
    /* Decide if document with <html>... should be returned */

    let WHOLE_DOCUMENT = false;
    /* Track whether config is already set on this instance of DOMPurify. */

    let SET_CONFIG = false;
    /* Decide if all elements (e.g. style, script) must be children of
     * document.body. By default, browsers might move them to document.head */

    let FORCE_BODY = false;
    /* Decide if a DOM `HTMLBodyElement` should be returned, instead of a html
     * string (or a TrustedHTML object if Trusted Types are supported).
     * If `WHOLE_DOCUMENT` is enabled a `HTMLHtmlElement` will be returned instead
     */

    let RETURN_DOM = false;
    /* Decide if a DOM `DocumentFragment` should be returned, instead of a html
     * string  (or a TrustedHTML object if Trusted Types are supported) */

    let RETURN_DOM_FRAGMENT = false;
    /* Try to return a Trusted Type object instead of a string, return a string in
     * case Trusted Types are not supported  */

    let RETURN_TRUSTED_TYPE = false;
    /* Output should be free from DOM clobbering attacks?
     * This sanitizes markups named with colliding, clobberable built-in DOM APIs.
     */

    let SANITIZE_DOM = true;
    /* Achieve full DOM Clobbering protection by isolating the namespace of named
     * properties and JS variables, mitigating attacks that abuse the HTML/DOM spec rules.
     *
     * HTML/DOM spec rules that enable DOM Clobbering:
     *   - Named Access on Window (§7.3.3)
     *   - DOM Tree Accessors (§3.1.5)
     *   - Form Element Parent-Child Relations (§4.10.3)
     *   - Iframe srcdoc / Nested WindowProxies (§4.8.5)
     *   - HTMLCollection (§4.2.10.2)
     *
     * Namespace isolation is implemented by prefixing `id` and `name` attributes
     * with a constant string, i.e., `user-content-`
     */

    let SANITIZE_NAMED_PROPS = false;
    const SANITIZE_NAMED_PROPS_PREFIX = 'user-content-';
    /* Keep element content when removing element? */

    let KEEP_CONTENT = true;
    /* If a `Node` is passed to sanitize(), then performs sanitization in-place instead
     * of importing it into a new Document and returning a sanitized copy */

    let IN_PLACE = false;
    /* Allow usage of profiles like html, svg and mathMl */

    let USE_PROFILES = {};
    /* Tags to ignore content of when KEEP_CONTENT is true */

    let FORBID_CONTENTS = null;
    const DEFAULT_FORBID_CONTENTS = addToSet({}, ['annotation-xml', 'audio', 'colgroup', 'desc', 'foreignobject', 'head', 'iframe', 'math', 'mi', 'mn', 'mo', 'ms', 'mtext', 'noembed', 'noframes', 'noscript', 'plaintext', 'script', 'style', 'svg', 'template', 'thead', 'title', 'video', 'xmp']);
    /* Tags that are safe for data: URIs */

    let DATA_URI_TAGS = null;
    const DEFAULT_DATA_URI_TAGS = addToSet({}, ['audio', 'video', 'img', 'source', 'image', 'track']);
    /* Attributes safe for values like "javascript:" */

    let URI_SAFE_ATTRIBUTES = null;
    const DEFAULT_URI_SAFE_ATTRIBUTES = addToSet({}, ['alt', 'class', 'for', 'id', 'label', 'name', 'pattern', 'placeholder', 'role', 'summary', 'title', 'value', 'style', 'xmlns']);
    const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    /* Document namespace */

    let NAMESPACE = HTML_NAMESPACE;
    let IS_EMPTY_INPUT = false;
    /* Allowed XHTML+XML namespaces */

    let ALLOWED_NAMESPACES = null;
    const DEFAULT_ALLOWED_NAMESPACES = addToSet({}, [MATHML_NAMESPACE, SVG_NAMESPACE, HTML_NAMESPACE], stringToString);
    /* Parsing of strict XHTML documents */

    let PARSER_MEDIA_TYPE;
    const SUPPORTED_PARSER_MEDIA_TYPES = ['application/xhtml+xml', 'text/html'];
    const DEFAULT_PARSER_MEDIA_TYPE = 'text/html';
    let transformCaseFunc;
    /* Keep a reference to config to pass to hooks */

    let CONFIG = null;
    /* Ideally, do not touch anything below this line */

    /* ______________________________________________ */

    const formElement = document.createElement('form');

    const isRegexOrFunction = function isRegexOrFunction(testValue) {
      return testValue instanceof RegExp || testValue instanceof Function;
    };
    /**
     * _parseConfig
     *
     * @param  {Object} cfg optional config literal
     */
    // eslint-disable-next-line complexity


    const _parseConfig = function _parseConfig(cfg) {
      if (CONFIG && CONFIG === cfg) {
        return;
      }
      /* Shield configuration object from tampering */


      if (!cfg || typeof cfg !== 'object') {
        cfg = {};
      }
      /* Shield configuration object from prototype pollution */


      cfg = clone(cfg);
      PARSER_MEDIA_TYPE = // eslint-disable-next-line unicorn/prefer-includes
      SUPPORTED_PARSER_MEDIA_TYPES.indexOf(cfg.PARSER_MEDIA_TYPE) === -1 ? PARSER_MEDIA_TYPE = DEFAULT_PARSER_MEDIA_TYPE : PARSER_MEDIA_TYPE = cfg.PARSER_MEDIA_TYPE; // HTML tags and attributes are not case-sensitive, converting to lowercase. Keeping XHTML as is.

      transformCaseFunc = PARSER_MEDIA_TYPE === 'application/xhtml+xml' ? stringToString : stringToLowerCase;
      /* Set configuration parameters */

      ALLOWED_TAGS = 'ALLOWED_TAGS' in cfg ? addToSet({}, cfg.ALLOWED_TAGS, transformCaseFunc) : DEFAULT_ALLOWED_TAGS;
      ALLOWED_ATTR = 'ALLOWED_ATTR' in cfg ? addToSet({}, cfg.ALLOWED_ATTR, transformCaseFunc) : DEFAULT_ALLOWED_ATTR;
      ALLOWED_NAMESPACES = 'ALLOWED_NAMESPACES' in cfg ? addToSet({}, cfg.ALLOWED_NAMESPACES, stringToString) : DEFAULT_ALLOWED_NAMESPACES;
      URI_SAFE_ATTRIBUTES = 'ADD_URI_SAFE_ATTR' in cfg ? addToSet(clone(DEFAULT_URI_SAFE_ATTRIBUTES), // eslint-disable-line indent
      cfg.ADD_URI_SAFE_ATTR, // eslint-disable-line indent
      transformCaseFunc // eslint-disable-line indent
      ) // eslint-disable-line indent
      : DEFAULT_URI_SAFE_ATTRIBUTES;
      DATA_URI_TAGS = 'ADD_DATA_URI_TAGS' in cfg ? addToSet(clone(DEFAULT_DATA_URI_TAGS), // eslint-disable-line indent
      cfg.ADD_DATA_URI_TAGS, // eslint-disable-line indent
      transformCaseFunc // eslint-disable-line indent
      ) // eslint-disable-line indent
      : DEFAULT_DATA_URI_TAGS;
      FORBID_CONTENTS = 'FORBID_CONTENTS' in cfg ? addToSet({}, cfg.FORBID_CONTENTS, transformCaseFunc) : DEFAULT_FORBID_CONTENTS;
      FORBID_TAGS = 'FORBID_TAGS' in cfg ? addToSet({}, cfg.FORBID_TAGS, transformCaseFunc) : {};
      FORBID_ATTR = 'FORBID_ATTR' in cfg ? addToSet({}, cfg.FORBID_ATTR, transformCaseFunc) : {};
      USE_PROFILES = 'USE_PROFILES' in cfg ? cfg.USE_PROFILES : false;
      ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false; // Default true

      ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false; // Default true

      ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false; // Default false

      ALLOW_SELF_CLOSE_IN_ATTR = cfg.ALLOW_SELF_CLOSE_IN_ATTR !== false; // Default true

      SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false; // Default false

      WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false; // Default false

      RETURN_DOM = cfg.RETURN_DOM || false; // Default false

      RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false; // Default false

      RETURN_TRUSTED_TYPE = cfg.RETURN_TRUSTED_TYPE || false; // Default false

      FORCE_BODY = cfg.FORCE_BODY || false; // Default false

      SANITIZE_DOM = cfg.SANITIZE_DOM !== false; // Default true

      SANITIZE_NAMED_PROPS = cfg.SANITIZE_NAMED_PROPS || false; // Default false

      KEEP_CONTENT = cfg.KEEP_CONTENT !== false; // Default true

      IN_PLACE = cfg.IN_PLACE || false; // Default false

      IS_ALLOWED_URI$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI;
      NAMESPACE = cfg.NAMESPACE || HTML_NAMESPACE;
      CUSTOM_ELEMENT_HANDLING = cfg.CUSTOM_ELEMENT_HANDLING || {};

      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.tagNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.attributeNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && typeof cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements === 'boolean') {
        CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements = cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements;
      }

      if (SAFE_FOR_TEMPLATES) {
        ALLOW_DATA_ATTR = false;
      }

      if (RETURN_DOM_FRAGMENT) {
        RETURN_DOM = true;
      }
      /* Parse profile info */


      if (USE_PROFILES) {
        ALLOWED_TAGS = addToSet({}, [...text]);
        ALLOWED_ATTR = [];

        if (USE_PROFILES.html === true) {
          addToSet(ALLOWED_TAGS, html$1);
          addToSet(ALLOWED_ATTR, html);
        }

        if (USE_PROFILES.svg === true) {
          addToSet(ALLOWED_TAGS, svg$1);
          addToSet(ALLOWED_ATTR, svg);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.svgFilters === true) {
          addToSet(ALLOWED_TAGS, svgFilters);
          addToSet(ALLOWED_ATTR, svg);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.mathMl === true) {
          addToSet(ALLOWED_TAGS, mathMl$1);
          addToSet(ALLOWED_ATTR, mathMl);
          addToSet(ALLOWED_ATTR, xml);
        }
      }
      /* Merge configuration parameters */


      if (cfg.ADD_TAGS) {
        if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
          ALLOWED_TAGS = clone(ALLOWED_TAGS);
        }

        addToSet(ALLOWED_TAGS, cfg.ADD_TAGS, transformCaseFunc);
      }

      if (cfg.ADD_ATTR) {
        if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
          ALLOWED_ATTR = clone(ALLOWED_ATTR);
        }

        addToSet(ALLOWED_ATTR, cfg.ADD_ATTR, transformCaseFunc);
      }

      if (cfg.ADD_URI_SAFE_ATTR) {
        addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR, transformCaseFunc);
      }

      if (cfg.FORBID_CONTENTS) {
        if (FORBID_CONTENTS === DEFAULT_FORBID_CONTENTS) {
          FORBID_CONTENTS = clone(FORBID_CONTENTS);
        }

        addToSet(FORBID_CONTENTS, cfg.FORBID_CONTENTS, transformCaseFunc);
      }
      /* Add #text in case KEEP_CONTENT is set to true */


      if (KEEP_CONTENT) {
        ALLOWED_TAGS['#text'] = true;
      }
      /* Add html, head and body to ALLOWED_TAGS in case WHOLE_DOCUMENT is true */


      if (WHOLE_DOCUMENT) {
        addToSet(ALLOWED_TAGS, ['html', 'head', 'body']);
      }
      /* Add tbody to ALLOWED_TAGS in case tables are permitted, see #286, #365 */


      if (ALLOWED_TAGS.table) {
        addToSet(ALLOWED_TAGS, ['tbody']);
        delete FORBID_TAGS.tbody;
      } // Prevent further manipulation of configuration.
      // Not available in IE8, Safari 5, etc.


      if (freeze) {
        freeze(cfg);
      }

      CONFIG = cfg;
    };

    const MATHML_TEXT_INTEGRATION_POINTS = addToSet({}, ['mi', 'mo', 'mn', 'ms', 'mtext']);
    const HTML_INTEGRATION_POINTS = addToSet({}, ['foreignobject', 'desc', 'title', 'annotation-xml']); // Certain elements are allowed in both SVG and HTML
    // namespace. We need to specify them explicitly
    // so that they don't get erroneously deleted from
    // HTML namespace.

    const COMMON_SVG_AND_HTML_ELEMENTS = addToSet({}, ['title', 'style', 'font', 'a', 'script']);
    /* Keep track of all possible SVG and MathML tags
     * so that we can perform the namespace checks
     * correctly. */

    const ALL_SVG_TAGS = addToSet({}, svg$1);
    addToSet(ALL_SVG_TAGS, svgFilters);
    addToSet(ALL_SVG_TAGS, svgDisallowed);
    const ALL_MATHML_TAGS = addToSet({}, mathMl$1);
    addToSet(ALL_MATHML_TAGS, mathMlDisallowed);
    /**
     *
     *
     * @param  {Element} element a DOM element whose namespace is being checked
     * @returns {boolean} Return false if the element has a
     *  namespace that a spec-compliant parser would never
     *  return. Return true otherwise.
     */

    const _checkValidNamespace = function _checkValidNamespace(element) {
      let parent = getParentNode(element); // In JSDOM, if we're inside shadow DOM, then parentNode
      // can be null. We just simulate parent in this case.

      if (!parent || !parent.tagName) {
        parent = {
          namespaceURI: NAMESPACE,
          tagName: 'template'
        };
      }

      const tagName = stringToLowerCase(element.tagName);
      const parentTagName = stringToLowerCase(parent.tagName);

      if (!ALLOWED_NAMESPACES[element.namespaceURI]) {
        return false;
      }

      if (element.namespaceURI === SVG_NAMESPACE) {
        // The only way to switch from HTML namespace to SVG
        // is via <svg>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'svg';
        } // The only way to switch from MathML to SVG is via`
        // svg if parent is either <annotation-xml> or MathML
        // text integration points.


        if (parent.namespaceURI === MATHML_NAMESPACE) {
          return tagName === 'svg' && (parentTagName === 'annotation-xml' || MATHML_TEXT_INTEGRATION_POINTS[parentTagName]);
        } // We only allow elements that are defined in SVG
        // spec. All others are disallowed in SVG namespace.


        return Boolean(ALL_SVG_TAGS[tagName]);
      }

      if (element.namespaceURI === MATHML_NAMESPACE) {
        // The only way to switch from HTML namespace to MathML
        // is via <math>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'math';
        } // The only way to switch from SVG to MathML is via
        // <math> and HTML integration points


        if (parent.namespaceURI === SVG_NAMESPACE) {
          return tagName === 'math' && HTML_INTEGRATION_POINTS[parentTagName];
        } // We only allow elements that are defined in MathML
        // spec. All others are disallowed in MathML namespace.


        return Boolean(ALL_MATHML_TAGS[tagName]);
      }

      if (element.namespaceURI === HTML_NAMESPACE) {
        // The only way to switch from SVG to HTML is via
        // HTML integration points, and from MathML to HTML
        // is via MathML text integration points
        if (parent.namespaceURI === SVG_NAMESPACE && !HTML_INTEGRATION_POINTS[parentTagName]) {
          return false;
        }

        if (parent.namespaceURI === MATHML_NAMESPACE && !MATHML_TEXT_INTEGRATION_POINTS[parentTagName]) {
          return false;
        } // We disallow tags that are specific for MathML
        // or SVG and should never appear in HTML namespace


        return !ALL_MATHML_TAGS[tagName] && (COMMON_SVG_AND_HTML_ELEMENTS[tagName] || !ALL_SVG_TAGS[tagName]);
      } // For XHTML and XML documents that support custom namespaces


      if (PARSER_MEDIA_TYPE === 'application/xhtml+xml' && ALLOWED_NAMESPACES[element.namespaceURI]) {
        return true;
      } // The code should never reach this place (this means
      // that the element somehow got namespace that is not
      // HTML, SVG, MathML or allowed via ALLOWED_NAMESPACES).
      // Return false just in case.


      return false;
    };
    /**
     * _forceRemove
     *
     * @param  {Node} node a DOM node
     */


    const _forceRemove = function _forceRemove(node) {
      arrayPush(DOMPurify.removed, {
        element: node
      });

      try {
        // eslint-disable-next-line unicorn/prefer-dom-node-remove
        node.parentNode.removeChild(node);
      } catch (_) {
        node.remove();
      }
    };
    /**
     * _removeAttribute
     *
     * @param  {String} name an Attribute name
     * @param  {Node} node a DOM node
     */


    const _removeAttribute = function _removeAttribute(name, node) {
      try {
        arrayPush(DOMPurify.removed, {
          attribute: node.getAttributeNode(name),
          from: node
        });
      } catch (_) {
        arrayPush(DOMPurify.removed, {
          attribute: null,
          from: node
        });
      }

      node.removeAttribute(name); // We void attribute values for unremovable "is"" attributes

      if (name === 'is' && !ALLOWED_ATTR[name]) {
        if (RETURN_DOM || RETURN_DOM_FRAGMENT) {
          try {
            _forceRemove(node);
          } catch (_) {}
        } else {
          try {
            node.setAttribute(name, '');
          } catch (_) {}
        }
      }
    };
    /**
     * _initDocument
     *
     * @param  {String} dirty a string of dirty markup
     * @return {Document} a DOM, filled with the dirty markup
     */


    const _initDocument = function _initDocument(dirty) {
      /* Create a HTML document */
      let doc;
      let leadingWhitespace;

      if (FORCE_BODY) {
        dirty = '<remove></remove>' + dirty;
      } else {
        /* If FORCE_BODY isn't used, leading whitespace needs to be preserved manually */
        const matches = stringMatch(dirty, /^[\r\n\t ]+/);
        leadingWhitespace = matches && matches[0];
      }

      if (PARSER_MEDIA_TYPE === 'application/xhtml+xml' && NAMESPACE === HTML_NAMESPACE) {
        // Root of XHTML doc must contain xmlns declaration (see https://www.w3.org/TR/xhtml1/normative.html#strict)
        dirty = '<html xmlns="http://www.w3.org/1999/xhtml"><head></head><body>' + dirty + '</body></html>';
      }

      const dirtyPayload = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
      /*
       * Use the DOMParser API by default, fallback later if needs be
       * DOMParser not work for svg when has multiple root element.
       */

      if (NAMESPACE === HTML_NAMESPACE) {
        try {
          doc = new DOMParser().parseFromString(dirtyPayload, PARSER_MEDIA_TYPE);
        } catch (_) {}
      }
      /* Use createHTMLDocument in case DOMParser is not available */


      if (!doc || !doc.documentElement) {
        doc = implementation.createDocument(NAMESPACE, 'template', null);

        try {
          doc.documentElement.innerHTML = IS_EMPTY_INPUT ? emptyHTML : dirtyPayload;
        } catch (_) {// Syntax error if dirtyPayload is invalid xml
        }
      }

      const body = doc.body || doc.documentElement;

      if (dirty && leadingWhitespace) {
        body.insertBefore(document.createTextNode(leadingWhitespace), body.childNodes[0] || null);
      }
      /* Work on whole document or just its body */


      if (NAMESPACE === HTML_NAMESPACE) {
        return getElementsByTagName.call(doc, WHOLE_DOCUMENT ? 'html' : 'body')[0];
      }

      return WHOLE_DOCUMENT ? doc.documentElement : body;
    };
    /**
     * _createIterator
     *
     * @param  {Document} root document/fragment to create iterator for
     * @return {Iterator} iterator instance
     */


    const _createIterator = function _createIterator(root) {
      return createNodeIterator.call(root.ownerDocument || root, root, // eslint-disable-next-line no-bitwise
      NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT, null, false);
    };
    /**
     * _isClobbered
     *
     * @param  {Node} elm element to check for clobbering attacks
     * @return {Boolean} true if clobbered, false if safe
     */


    const _isClobbered = function _isClobbered(elm) {
      return elm instanceof HTMLFormElement && (typeof elm.nodeName !== 'string' || typeof elm.textContent !== 'string' || typeof elm.removeChild !== 'function' || !(elm.attributes instanceof NamedNodeMap) || typeof elm.removeAttribute !== 'function' || typeof elm.setAttribute !== 'function' || typeof elm.namespaceURI !== 'string' || typeof elm.insertBefore !== 'function' || typeof elm.hasChildNodes !== 'function');
    };
    /**
     * _isNode
     *
     * @param  {Node} obj object to check whether it's a DOM node
     * @return {Boolean} true is object is a DOM node
     */


    const _isNode = function _isNode(object) {
      return typeof Node === 'object' ? object instanceof Node : object && typeof object === 'object' && typeof object.nodeType === 'number' && typeof object.nodeName === 'string';
    };
    /**
     * _executeHook
     * Execute user configurable hooks
     *
     * @param  {String} entryPoint  Name of the hook's entry point
     * @param  {Node} currentNode node to work on with the hook
     * @param  {Object} data additional hook parameters
     */


    const _executeHook = function _executeHook(entryPoint, currentNode, data) {
      if (!hooks[entryPoint]) {
        return;
      }

      arrayForEach(hooks[entryPoint], hook => {
        hook.call(DOMPurify, currentNode, data, CONFIG);
      });
    };
    /**
     * _sanitizeElements
     *
     * @protect nodeName
     * @protect textContent
     * @protect removeChild
     *
     * @param   {Node} currentNode to check for permission to exist
     * @return  {Boolean} true if node was killed, false if left alive
     */


    const _sanitizeElements = function _sanitizeElements(currentNode) {
      let content;
      /* Execute a hook if present */

      _executeHook('beforeSanitizeElements', currentNode, null);
      /* Check if element is clobbered or can clobber */


      if (_isClobbered(currentNode)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Now let's check the element's type and name */


      const tagName = transformCaseFunc(currentNode.nodeName);
      /* Execute a hook if present */

      _executeHook('uponSanitizeElement', currentNode, {
        tagName,
        allowedTags: ALLOWED_TAGS
      });
      /* Detect mXSS attempts abusing namespace confusion */


      if (currentNode.hasChildNodes() && !_isNode(currentNode.firstElementChild) && (!_isNode(currentNode.content) || !_isNode(currentNode.content.firstElementChild)) && regExpTest(/<[/\w]/g, currentNode.innerHTML) && regExpTest(/<[/\w]/g, currentNode.textContent)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Remove element if anything forbids its presence */


      if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
        /* Check if we have a custom element to handle */
        if (!FORBID_TAGS[tagName] && _basicCustomElementTest(tagName)) {
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, tagName)) return false;
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(tagName)) return false;
        }
        /* Keep content except for bad-listed elements */


        if (KEEP_CONTENT && !FORBID_CONTENTS[tagName]) {
          const parentNode = getParentNode(currentNode) || currentNode.parentNode;
          const childNodes = getChildNodes(currentNode) || currentNode.childNodes;

          if (childNodes && parentNode) {
            const childCount = childNodes.length;

            for (let i = childCount - 1; i >= 0; --i) {
              parentNode.insertBefore(cloneNode(childNodes[i], true), getNextSibling(currentNode));
            }
          }
        }

        _forceRemove(currentNode);

        return true;
      }
      /* Check whether element has a valid namespace */


      if (currentNode instanceof Element && !_checkValidNamespace(currentNode)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Make sure that older browsers don't get noscript mXSS */


      if ((tagName === 'noscript' || tagName === 'noembed') && regExpTest(/<\/no(script|embed)/i, currentNode.innerHTML)) {
        _forceRemove(currentNode);

        return true;
      }
      /* Sanitize element content to be template-safe */


      if (SAFE_FOR_TEMPLATES && currentNode.nodeType === 3) {
        /* Get the element's text content */
        content = currentNode.textContent;
        content = stringReplace(content, MUSTACHE_EXPR, ' ');
        content = stringReplace(content, ERB_EXPR, ' ');
        content = stringReplace(content, TMPLIT_EXPR, ' ');

        if (currentNode.textContent !== content) {
          arrayPush(DOMPurify.removed, {
            element: currentNode.cloneNode()
          });
          currentNode.textContent = content;
        }
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeElements', currentNode, null);

      return false;
    };
    /**
     * _isValidAttribute
     *
     * @param  {string} lcTag Lowercase tag name of containing element.
     * @param  {string} lcName Lowercase attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid, otherwise false.
     */
    // eslint-disable-next-line complexity


    const _isValidAttribute = function _isValidAttribute(lcTag, lcName, value) {
      /* Make sure attribute cannot clobber */
      if (SANITIZE_DOM && (lcName === 'id' || lcName === 'name') && (value in document || value in formElement)) {
        return false;
      }
      /* Allow valid data-* attributes: At least one character after "-"
          (https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes)
          XML-compatible (https://html.spec.whatwg.org/multipage/infrastructure.html#xml-compatible and http://www.w3.org/TR/xml/#d0e804)
          We don't need to check the value; it's always URI safe. */


      if (ALLOW_DATA_ATTR && !FORBID_ATTR[lcName] && regExpTest(DATA_ATTR, lcName)) ; else if (ALLOW_ARIA_ATTR && regExpTest(ARIA_ATTR, lcName)) ; else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
        if ( // First condition does a very basic check if a) it's basically a valid custom element tagname AND
        // b) if the tagName passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        // and c) if the attribute name passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.attributeNameCheck
        _basicCustomElementTest(lcTag) && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, lcTag) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(lcTag)) && (CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.attributeNameCheck, lcName) || CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.attributeNameCheck(lcName)) || // Alternative, second condition checks if it's an `is`-attribute, AND
        // the value passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        lcName === 'is' && CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, value) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(value))) ; else {
          return false;
        }
        /* Check value is safe. First, is attr inert? If so, is safe */

      } else if (URI_SAFE_ATTRIBUTES[lcName]) ; else if (regExpTest(IS_ALLOWED_URI$1, stringReplace(value, ATTR_WHITESPACE, ''))) ; else if ((lcName === 'src' || lcName === 'xlink:href' || lcName === 'href') && lcTag !== 'script' && stringIndexOf(value, 'data:') === 0 && DATA_URI_TAGS[lcTag]) ; else if (ALLOW_UNKNOWN_PROTOCOLS && !regExpTest(IS_SCRIPT_OR_DATA, stringReplace(value, ATTR_WHITESPACE, ''))) ; else if (!value) ; else {
        return false;
      }

      return true;
    };
    /**
     * _basicCustomElementCheck
     * checks if at least one dash is included in tagName, and it's not the first char
     * for more sophisticated checking see https://github.com/sindresorhus/validate-element-name
     * @param {string} tagName name of the tag of the node to sanitize
     */


    const _basicCustomElementTest = function _basicCustomElementTest(tagName) {
      return tagName.indexOf('-') > 0;
    };
    /**
     * _sanitizeAttributes
     *
     * @protect attributes
     * @protect nodeName
     * @protect removeAttribute
     * @protect setAttribute
     *
     * @param  {Node} currentNode to sanitize
     */


    const _sanitizeAttributes = function _sanitizeAttributes(currentNode) {
      let attr;
      let value;
      let lcName;
      let l;
      /* Execute a hook if present */

      _executeHook('beforeSanitizeAttributes', currentNode, null);

      const {
        attributes
      } = currentNode;
      /* Check if we have attributes; if not we might have a text node */

      if (!attributes) {
        return;
      }

      const hookEvent = {
        attrName: '',
        attrValue: '',
        keepAttr: true,
        allowedAttributes: ALLOWED_ATTR
      };
      l = attributes.length;
      /* Go backwards over all attributes; safely remove bad ones */

      while (l--) {
        attr = attributes[l];
        const {
          name,
          namespaceURI
        } = attr;
        value = name === 'value' ? attr.value : stringTrim(attr.value);
        lcName = transformCaseFunc(name);
        /* Execute a hook if present */

        hookEvent.attrName = lcName;
        hookEvent.attrValue = value;
        hookEvent.keepAttr = true;
        hookEvent.forceKeepAttr = undefined; // Allows developers to see this is a property they can set

        _executeHook('uponSanitizeAttribute', currentNode, hookEvent);

        value = hookEvent.attrValue;
        /* Did the hooks approve of the attribute? */

        if (hookEvent.forceKeepAttr) {
          continue;
        }
        /* Remove attribute */


        _removeAttribute(name, currentNode);
        /* Did the hooks approve of the attribute? */


        if (!hookEvent.keepAttr) {
          continue;
        }
        /* Work around a security issue in jQuery 3.0 */


        if (!ALLOW_SELF_CLOSE_IN_ATTR && regExpTest(/\/>/i, value)) {
          _removeAttribute(name, currentNode);

          continue;
        }
        /* Sanitize attribute content to be template-safe */


        if (SAFE_FOR_TEMPLATES) {
          value = stringReplace(value, MUSTACHE_EXPR, ' ');
          value = stringReplace(value, ERB_EXPR, ' ');
          value = stringReplace(value, TMPLIT_EXPR, ' ');
        }
        /* Is `value` valid for this attribute? */


        const lcTag = transformCaseFunc(currentNode.nodeName);

        if (!_isValidAttribute(lcTag, lcName, value)) {
          continue;
        }
        /* Full DOM Clobbering protection via namespace isolation,
         * Prefix id and name attributes with `user-content-`
         */


        if (SANITIZE_NAMED_PROPS && (lcName === 'id' || lcName === 'name')) {
          // Remove the attribute with this value
          _removeAttribute(name, currentNode); // Prefix the value and later re-create the attribute with the sanitized value


          value = SANITIZE_NAMED_PROPS_PREFIX + value;
        }
        /* Handle attributes that require Trusted Types */


        if (trustedTypesPolicy && typeof trustedTypes === 'object' && typeof trustedTypes.getAttributeType === 'function') {
          if (namespaceURI) ; else {
            switch (trustedTypes.getAttributeType(lcTag, lcName)) {
              case 'TrustedHTML':
                value = trustedTypesPolicy.createHTML(value);
                break;

              case 'TrustedScriptURL':
                value = trustedTypesPolicy.createScriptURL(value);
                break;
            }
          }
        }
        /* Handle invalid data-* attribute set by try-catching it */


        try {
          if (namespaceURI) {
            currentNode.setAttributeNS(namespaceURI, name, value);
          } else {
            /* Fallback to setAttribute() for browser-unrecognized namespaces e.g. "x-schema". */
            currentNode.setAttribute(name, value);
          }

          arrayPop(DOMPurify.removed);
        } catch (_) {}
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeAttributes', currentNode, null);
    };
    /**
     * _sanitizeShadowDOM
     *
     * @param  {DocumentFragment} fragment to iterate over recursively
     */


    const _sanitizeShadowDOM = function _sanitizeShadowDOM(fragment) {
      let shadowNode;

      const shadowIterator = _createIterator(fragment);
      /* Execute a hook if present */


      _executeHook('beforeSanitizeShadowDOM', fragment, null);

      while (shadowNode = shadowIterator.nextNode()) {
        /* Execute a hook if present */
        _executeHook('uponSanitizeShadowNode', shadowNode, null);
        /* Sanitize tags and elements */


        if (_sanitizeElements(shadowNode)) {
          continue;
        }
        /* Deep shadow DOM detected */


        if (shadowNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(shadowNode.content);
        }
        /* Check attributes, sanitize if necessary */


        _sanitizeAttributes(shadowNode);
      }
      /* Execute a hook if present */


      _executeHook('afterSanitizeShadowDOM', fragment, null);
    };
    /**
     * Sanitize
     * Public method providing core sanitation functionality
     *
     * @param {String|Node} dirty string or DOM node
     * @param {Object} configuration object
     */
    // eslint-disable-next-line complexity


    DOMPurify.sanitize = function (dirty) {
      let cfg = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      let body;
      let importedNode;
      let currentNode;
      let returnNode;
      /* Make sure we have a string to sanitize.
        DO NOT return early, as this will return the wrong type if
        the user has requested a DOM object rather than a string */

      IS_EMPTY_INPUT = !dirty;

      if (IS_EMPTY_INPUT) {
        dirty = '<!-->';
      }
      /* Stringify, in case dirty is an object */


      if (typeof dirty !== 'string' && !_isNode(dirty)) {
        // eslint-disable-next-line no-negated-condition
        if (typeof dirty.toString !== 'function') {
          throw typeErrorCreate('toString is not a function');
        } else {
          dirty = dirty.toString();

          if (typeof dirty !== 'string') {
            throw typeErrorCreate('dirty is not a string, aborting');
          }
        }
      }
      /* Return dirty HTML if DOMPurify cannot run */


      if (!DOMPurify.isSupported) {
        return dirty;
      }
      /* Assign config vars */


      if (!SET_CONFIG) {
        _parseConfig(cfg);
      }
      /* Clean up removed elements */


      DOMPurify.removed = [];
      /* Check if dirty is correctly typed for IN_PLACE */

      if (typeof dirty === 'string') {
        IN_PLACE = false;
      }

      if (IN_PLACE) {
        /* Do some early pre-sanitization to avoid unsafe root nodes */
        if (dirty.nodeName) {
          const tagName = transformCaseFunc(dirty.nodeName);

          if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
            throw typeErrorCreate('root node is forbidden and cannot be sanitized in-place');
          }
        }
      } else if (dirty instanceof Node) {
        /* If dirty is a DOM element, append to an empty document to avoid
           elements being stripped by the parser */
        body = _initDocument('<!---->');
        importedNode = body.ownerDocument.importNode(dirty, true);

        if (importedNode.nodeType === 1 && importedNode.nodeName === 'BODY') {
          /* Node is already a body, use as is */
          body = importedNode;
        } else if (importedNode.nodeName === 'HTML') {
          body = importedNode;
        } else {
          // eslint-disable-next-line unicorn/prefer-dom-node-append
          body.appendChild(importedNode);
        }
      } else {
        /* Exit directly if we have nothing to do */
        if (!RETURN_DOM && !SAFE_FOR_TEMPLATES && !WHOLE_DOCUMENT && // eslint-disable-next-line unicorn/prefer-includes
        dirty.indexOf('<') === -1) {
          return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(dirty) : dirty;
        }
        /* Initialize the document to work on */


        body = _initDocument(dirty);
        /* Check we have a DOM node from the data */

        if (!body) {
          return RETURN_DOM ? null : RETURN_TRUSTED_TYPE ? emptyHTML : '';
        }
      }
      /* Remove first element node (ours) if FORCE_BODY is set */


      if (body && FORCE_BODY) {
        _forceRemove(body.firstChild);
      }
      /* Get node iterator */


      const nodeIterator = _createIterator(IN_PLACE ? dirty : body);
      /* Now start iterating over the created document */


      while (currentNode = nodeIterator.nextNode()) {
        /* Sanitize tags and elements */
        if (_sanitizeElements(currentNode)) {
          continue;
        }
        /* Shadow DOM detected, sanitize it */


        if (currentNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(currentNode.content);
        }
        /* Check attributes, sanitize if necessary */


        _sanitizeAttributes(currentNode);
      }
      /* If we sanitized `dirty` in-place, return it. */


      if (IN_PLACE) {
        return dirty;
      }
      /* Return sanitized string or DOM */


      if (RETURN_DOM) {
        if (RETURN_DOM_FRAGMENT) {
          returnNode = createDocumentFragment.call(body.ownerDocument);

          while (body.firstChild) {
            // eslint-disable-next-line unicorn/prefer-dom-node-append
            returnNode.appendChild(body.firstChild);
          }
        } else {
          returnNode = body;
        }

        if (ALLOWED_ATTR.shadowroot || ALLOWED_ATTR.shadowrootmod) {
          /*
            AdoptNode() is not used because internal state is not reset
            (e.g. the past names map of a HTMLFormElement), this is safe
            in theory but we would rather not risk another attack vector.
            The state that is cloned by importNode() is explicitly defined
            by the specs.
          */
          returnNode = importNode.call(originalDocument, returnNode, true);
        }

        return returnNode;
      }

      let serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;
      /* Serialize doctype if allowed */

      if (WHOLE_DOCUMENT && ALLOWED_TAGS['!doctype'] && body.ownerDocument && body.ownerDocument.doctype && body.ownerDocument.doctype.name && regExpTest(DOCTYPE_NAME, body.ownerDocument.doctype.name)) {
        serializedHTML = '<!DOCTYPE ' + body.ownerDocument.doctype.name + '>\n' + serializedHTML;
      }
      /* Sanitize final string template-safe */


      if (SAFE_FOR_TEMPLATES) {
        serializedHTML = stringReplace(serializedHTML, MUSTACHE_EXPR, ' ');
        serializedHTML = stringReplace(serializedHTML, ERB_EXPR, ' ');
        serializedHTML = stringReplace(serializedHTML, TMPLIT_EXPR, ' ');
      }

      return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
    };
    /**
     * Public method to set the configuration once
     * setConfig
     *
     * @param {Object} cfg configuration object
     */


    DOMPurify.setConfig = function (cfg) {
      _parseConfig(cfg);

      SET_CONFIG = true;
    };
    /**
     * Public method to remove the configuration
     * clearConfig
     *
     */


    DOMPurify.clearConfig = function () {
      CONFIG = null;
      SET_CONFIG = false;
    };
    /**
     * Public method to check if an attribute value is valid.
     * Uses last set config, if any. Otherwise, uses config defaults.
     * isValidAttribute
     *
     * @param  {string} tag Tag name of containing element.
     * @param  {string} attr Attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid. Otherwise, returns false.
     */


    DOMPurify.isValidAttribute = function (tag, attr, value) {
      /* Initialize shared config vars if necessary. */
      if (!CONFIG) {
        _parseConfig({});
      }

      const lcTag = transformCaseFunc(tag);
      const lcName = transformCaseFunc(attr);
      return _isValidAttribute(lcTag, lcName, value);
    };
    /**
     * AddHook
     * Public method to add DOMPurify hooks
     *
     * @param {String} entryPoint entry point for the hook to add
     * @param {Function} hookFunction function to execute
     */


    DOMPurify.addHook = function (entryPoint, hookFunction) {
      if (typeof hookFunction !== 'function') {
        return;
      }

      hooks[entryPoint] = hooks[entryPoint] || [];
      arrayPush(hooks[entryPoint], hookFunction);
    };
    /**
     * RemoveHook
     * Public method to remove a DOMPurify hook at a given entryPoint
     * (pops it from the stack of hooks if more are present)
     *
     * @param {String} entryPoint entry point for the hook to remove
     * @return {Function} removed(popped) hook
     */


    DOMPurify.removeHook = function (entryPoint) {
      if (hooks[entryPoint]) {
        return arrayPop(hooks[entryPoint]);
      }
    };
    /**
     * RemoveHooks
     * Public method to remove all DOMPurify hooks at a given entryPoint
     *
     * @param  {String} entryPoint entry point for the hooks to remove
     */


    DOMPurify.removeHooks = function (entryPoint) {
      if (hooks[entryPoint]) {
        hooks[entryPoint] = [];
      }
    };
    /**
     * RemoveAllHooks
     * Public method to remove all DOMPurify hooks
     *
     */


    DOMPurify.removeAllHooks = function () {
      hooks = {};
    };

    return DOMPurify;
  }

  var purify = createDOMPurify();

  return purify;

}));
//# sourceMappingURL=purify.js.map


/***/ }),

/***/ "./node_modules/react-query/es/core/focusManager.js":
/*!**********************************************************!*\
  !*** ./node_modules/react-query/es/core/focusManager.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FocusManager": () => (/* binding */ FocusManager),
/* harmony export */   "focusManager": () => (/* binding */ focusManager)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");



var FocusManager = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(FocusManager, _Subscribable);

  function FocusManager() {
    var _this;

    _this = _Subscribable.call(this) || this;

    _this.setup = function (onFocus) {
      var _window;

      if (!_utils__WEBPACK_IMPORTED_MODULE_1__.isServer && ((_window = window) == null ? void 0 : _window.addEventListener)) {
        var listener = function listener() {
          return onFocus();
        }; // Listen to visibillitychange and focus


        window.addEventListener('visibilitychange', listener, false);
        window.addEventListener('focus', listener, false);
        return function () {
          // Be sure to unsubscribe if a new handler is set
          window.removeEventListener('visibilitychange', listener);
          window.removeEventListener('focus', listener);
        };
      }
    };

    return _this;
  }

  var _proto = FocusManager.prototype;

  _proto.onSubscribe = function onSubscribe() {
    if (!this.cleanup) {
      this.setEventListener(this.setup);
    }
  };

  _proto.onUnsubscribe = function onUnsubscribe() {
    if (!this.hasListeners()) {
      var _this$cleanup;

      (_this$cleanup = this.cleanup) == null ? void 0 : _this$cleanup.call(this);
      this.cleanup = undefined;
    }
  };

  _proto.setEventListener = function setEventListener(setup) {
    var _this$cleanup2,
        _this2 = this;

    this.setup = setup;
    (_this$cleanup2 = this.cleanup) == null ? void 0 : _this$cleanup2.call(this);
    this.cleanup = setup(function (focused) {
      if (typeof focused === 'boolean') {
        _this2.setFocused(focused);
      } else {
        _this2.onFocus();
      }
    });
  };

  _proto.setFocused = function setFocused(focused) {
    this.focused = focused;

    if (focused) {
      this.onFocus();
    }
  };

  _proto.onFocus = function onFocus() {
    this.listeners.forEach(function (listener) {
      listener();
    });
  };

  _proto.isFocused = function isFocused() {
    if (typeof this.focused === 'boolean') {
      return this.focused;
    } // document global can be unavailable in react native


    if (typeof document === 'undefined') {
      return true;
    }

    return [undefined, 'visible', 'prerender'].includes(document.visibilityState);
  };

  return FocusManager;
}(_subscribable__WEBPACK_IMPORTED_MODULE_2__.Subscribable);
var focusManager = new FocusManager();

/***/ }),

/***/ "./node_modules/react-query/es/core/hydration.js":
/*!*******************************************************!*\
  !*** ./node_modules/react-query/es/core/hydration.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "dehydrate": () => (/* binding */ dehydrate),
/* harmony export */   "hydrate": () => (/* binding */ hydrate)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


// TYPES
// FUNCTIONS
function dehydrateMutation(mutation) {
  return {
    mutationKey: mutation.options.mutationKey,
    state: mutation.state
  };
} // Most config is not dehydrated but instead meant to configure again when
// consuming the de/rehydrated data, typically with useQuery on the client.
// Sometimes it might make sense to prefetch data on the server and include
// in the html-payload, but not consume it on the initial render.


function dehydrateQuery(query) {
  return {
    state: query.state,
    queryKey: query.queryKey,
    queryHash: query.queryHash
  };
}

function defaultShouldDehydrateMutation(mutation) {
  return mutation.state.isPaused;
}

function defaultShouldDehydrateQuery(query) {
  return query.state.status === 'success';
}

function dehydrate(client, options) {
  var _options, _options2;

  options = options || {};
  var mutations = [];
  var queries = [];

  if (((_options = options) == null ? void 0 : _options.dehydrateMutations) !== false) {
    var shouldDehydrateMutation = options.shouldDehydrateMutation || defaultShouldDehydrateMutation;
    client.getMutationCache().getAll().forEach(function (mutation) {
      if (shouldDehydrateMutation(mutation)) {
        mutations.push(dehydrateMutation(mutation));
      }
    });
  }

  if (((_options2 = options) == null ? void 0 : _options2.dehydrateQueries) !== false) {
    var shouldDehydrateQuery = options.shouldDehydrateQuery || defaultShouldDehydrateQuery;
    client.getQueryCache().getAll().forEach(function (query) {
      if (shouldDehydrateQuery(query)) {
        queries.push(dehydrateQuery(query));
      }
    });
  }

  return {
    mutations: mutations,
    queries: queries
  };
}
function hydrate(client, dehydratedState, options) {
  if (typeof dehydratedState !== 'object' || dehydratedState === null) {
    return;
  }

  var mutationCache = client.getMutationCache();
  var queryCache = client.getQueryCache();
  var mutations = dehydratedState.mutations || [];
  var queries = dehydratedState.queries || [];
  mutations.forEach(function (dehydratedMutation) {
    var _options$defaultOptio;

    mutationCache.build(client, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options == null ? void 0 : (_options$defaultOptio = options.defaultOptions) == null ? void 0 : _options$defaultOptio.mutations, {
      mutationKey: dehydratedMutation.mutationKey
    }), dehydratedMutation.state);
  });
  queries.forEach(function (dehydratedQuery) {
    var _options$defaultOptio2;

    var query = queryCache.get(dehydratedQuery.queryHash); // Do not hydrate if an existing query exists with newer data

    if (query) {
      if (query.state.dataUpdatedAt < dehydratedQuery.state.dataUpdatedAt) {
        query.setState(dehydratedQuery.state);
      }

      return;
    } // Restore query


    queryCache.build(client, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options == null ? void 0 : (_options$defaultOptio2 = options.defaultOptions) == null ? void 0 : _options$defaultOptio2.queries, {
      queryKey: dehydratedQuery.queryKey,
      queryHash: dehydratedQuery.queryHash
    }), dehydratedQuery.state);
  });
}

/***/ }),

/***/ "./node_modules/react-query/es/core/index.js":
/*!***************************************************!*\
  !*** ./node_modules/react-query/es/core/index.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "CancelledError": () => (/* reexport safe */ _retryer__WEBPACK_IMPORTED_MODULE_0__.CancelledError),
/* harmony export */   "InfiniteQueryObserver": () => (/* reexport safe */ _infiniteQueryObserver__WEBPACK_IMPORTED_MODULE_5__.InfiniteQueryObserver),
/* harmony export */   "MutationCache": () => (/* reexport safe */ _mutationCache__WEBPACK_IMPORTED_MODULE_6__.MutationCache),
/* harmony export */   "MutationObserver": () => (/* reexport safe */ _mutationObserver__WEBPACK_IMPORTED_MODULE_7__.MutationObserver),
/* harmony export */   "QueriesObserver": () => (/* reexport safe */ _queriesObserver__WEBPACK_IMPORTED_MODULE_4__.QueriesObserver),
/* harmony export */   "QueryCache": () => (/* reexport safe */ _queryCache__WEBPACK_IMPORTED_MODULE_1__.QueryCache),
/* harmony export */   "QueryClient": () => (/* reexport safe */ _queryClient__WEBPACK_IMPORTED_MODULE_2__.QueryClient),
/* harmony export */   "QueryObserver": () => (/* reexport safe */ _queryObserver__WEBPACK_IMPORTED_MODULE_3__.QueryObserver),
/* harmony export */   "dehydrate": () => (/* reexport safe */ _hydration__WEBPACK_IMPORTED_MODULE_13__.dehydrate),
/* harmony export */   "focusManager": () => (/* reexport safe */ _focusManager__WEBPACK_IMPORTED_MODULE_10__.focusManager),
/* harmony export */   "hashQueryKey": () => (/* reexport safe */ _utils__WEBPACK_IMPORTED_MODULE_12__.hashQueryKey),
/* harmony export */   "hydrate": () => (/* reexport safe */ _hydration__WEBPACK_IMPORTED_MODULE_13__.hydrate),
/* harmony export */   "isCancelledError": () => (/* reexport safe */ _retryer__WEBPACK_IMPORTED_MODULE_0__.isCancelledError),
/* harmony export */   "isError": () => (/* reexport safe */ _utils__WEBPACK_IMPORTED_MODULE_12__.isError),
/* harmony export */   "notifyManager": () => (/* reexport safe */ _notifyManager__WEBPACK_IMPORTED_MODULE_9__.notifyManager),
/* harmony export */   "onlineManager": () => (/* reexport safe */ _onlineManager__WEBPACK_IMPORTED_MODULE_11__.onlineManager),
/* harmony export */   "setLogger": () => (/* reexport safe */ _logger__WEBPACK_IMPORTED_MODULE_8__.setLogger)
/* harmony export */ });
/* harmony import */ var _retryer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./retryer */ "./node_modules/react-query/es/core/retryer.js");
/* harmony import */ var _queryCache__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./queryCache */ "./node_modules/react-query/es/core/queryCache.js");
/* harmony import */ var _queryClient__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./queryClient */ "./node_modules/react-query/es/core/queryClient.js");
/* harmony import */ var _queryObserver__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./queryObserver */ "./node_modules/react-query/es/core/queryObserver.js");
/* harmony import */ var _queriesObserver__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./queriesObserver */ "./node_modules/react-query/es/core/queriesObserver.js");
/* harmony import */ var _infiniteQueryObserver__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./infiniteQueryObserver */ "./node_modules/react-query/es/core/infiniteQueryObserver.js");
/* harmony import */ var _mutationCache__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./mutationCache */ "./node_modules/react-query/es/core/mutationCache.js");
/* harmony import */ var _mutationObserver__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./mutationObserver */ "./node_modules/react-query/es/core/mutationObserver.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./logger */ "./node_modules/react-query/es/core/logger.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _focusManager__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./focusManager */ "./node_modules/react-query/es/core/focusManager.js");
/* harmony import */ var _onlineManager__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./onlineManager */ "./node_modules/react-query/es/core/onlineManager.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _hydration__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./hydration */ "./node_modules/react-query/es/core/hydration.js");
/* harmony import */ var _types__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./types */ "./node_modules/react-query/es/core/types.js");
/* harmony import */ var _types__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_types__WEBPACK_IMPORTED_MODULE_14__);
/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};
/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _types__WEBPACK_IMPORTED_MODULE_14__) if(["default","CancelledError","QueryCache","QueryClient","QueryObserver","QueriesObserver","InfiniteQueryObserver","MutationCache","MutationObserver","setLogger","notifyManager","focusManager","onlineManager","hashQueryKey","isError","isCancelledError","dehydrate","hydrate"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _types__WEBPACK_IMPORTED_MODULE_14__[__WEBPACK_IMPORT_KEY__]
/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);














 // Types



/***/ }),

/***/ "./node_modules/react-query/es/core/infiniteQueryBehavior.js":
/*!*******************************************************************!*\
  !*** ./node_modules/react-query/es/core/infiniteQueryBehavior.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getNextPageParam": () => (/* binding */ getNextPageParam),
/* harmony export */   "getPreviousPageParam": () => (/* binding */ getPreviousPageParam),
/* harmony export */   "hasNextPage": () => (/* binding */ hasNextPage),
/* harmony export */   "hasPreviousPage": () => (/* binding */ hasPreviousPage),
/* harmony export */   "infiniteQueryBehavior": () => (/* binding */ infiniteQueryBehavior)
/* harmony export */ });
/* harmony import */ var _retryer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./retryer */ "./node_modules/react-query/es/core/retryer.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");


function infiniteQueryBehavior() {
  return {
    onFetch: function onFetch(context) {
      context.fetchFn = function () {
        var _context$fetchOptions, _context$fetchOptions2, _context$fetchOptions3, _context$fetchOptions4, _context$state$data, _context$state$data2;

        var refetchPage = (_context$fetchOptions = context.fetchOptions) == null ? void 0 : (_context$fetchOptions2 = _context$fetchOptions.meta) == null ? void 0 : _context$fetchOptions2.refetchPage;
        var fetchMore = (_context$fetchOptions3 = context.fetchOptions) == null ? void 0 : (_context$fetchOptions4 = _context$fetchOptions3.meta) == null ? void 0 : _context$fetchOptions4.fetchMore;
        var pageParam = fetchMore == null ? void 0 : fetchMore.pageParam;
        var isFetchingNextPage = (fetchMore == null ? void 0 : fetchMore.direction) === 'forward';
        var isFetchingPreviousPage = (fetchMore == null ? void 0 : fetchMore.direction) === 'backward';
        var oldPages = ((_context$state$data = context.state.data) == null ? void 0 : _context$state$data.pages) || [];
        var oldPageParams = ((_context$state$data2 = context.state.data) == null ? void 0 : _context$state$data2.pageParams) || [];
        var abortController = (0,_utils__WEBPACK_IMPORTED_MODULE_0__.getAbortController)();
        var abortSignal = abortController == null ? void 0 : abortController.signal;
        var newPageParams = oldPageParams;
        var cancelled = false; // Get query function

        var queryFn = context.options.queryFn || function () {
          return Promise.reject('Missing queryFn');
        };

        var buildNewPages = function buildNewPages(pages, param, page, previous) {
          newPageParams = previous ? [param].concat(newPageParams) : [].concat(newPageParams, [param]);
          return previous ? [page].concat(pages) : [].concat(pages, [page]);
        }; // Create function to fetch a page


        var fetchPage = function fetchPage(pages, manual, param, previous) {
          if (cancelled) {
            return Promise.reject('Cancelled');
          }

          if (typeof param === 'undefined' && !manual && pages.length) {
            return Promise.resolve(pages);
          }

          var queryFnContext = {
            queryKey: context.queryKey,
            signal: abortSignal,
            pageParam: param,
            meta: context.meta
          };
          var queryFnResult = queryFn(queryFnContext);
          var promise = Promise.resolve(queryFnResult).then(function (page) {
            return buildNewPages(pages, param, page, previous);
          });

          if ((0,_retryer__WEBPACK_IMPORTED_MODULE_1__.isCancelable)(queryFnResult)) {
            var promiseAsAny = promise;
            promiseAsAny.cancel = queryFnResult.cancel;
          }

          return promise;
        };

        var promise; // Fetch first page?

        if (!oldPages.length) {
          promise = fetchPage([]);
        } // Fetch next page?
        else if (isFetchingNextPage) {
            var manual = typeof pageParam !== 'undefined';
            var param = manual ? pageParam : getNextPageParam(context.options, oldPages);
            promise = fetchPage(oldPages, manual, param);
          } // Fetch previous page?
          else if (isFetchingPreviousPage) {
              var _manual = typeof pageParam !== 'undefined';

              var _param = _manual ? pageParam : getPreviousPageParam(context.options, oldPages);

              promise = fetchPage(oldPages, _manual, _param, true);
            } // Refetch pages
            else {
                (function () {
                  newPageParams = [];
                  var manual = typeof context.options.getNextPageParam === 'undefined';
                  var shouldFetchFirstPage = refetchPage && oldPages[0] ? refetchPage(oldPages[0], 0, oldPages) : true; // Fetch first page

                  promise = shouldFetchFirstPage ? fetchPage([], manual, oldPageParams[0]) : Promise.resolve(buildNewPages([], oldPageParams[0], oldPages[0])); // Fetch remaining pages

                  var _loop = function _loop(i) {
                    promise = promise.then(function (pages) {
                      var shouldFetchNextPage = refetchPage && oldPages[i] ? refetchPage(oldPages[i], i, oldPages) : true;

                      if (shouldFetchNextPage) {
                        var _param2 = manual ? oldPageParams[i] : getNextPageParam(context.options, pages);

                        return fetchPage(pages, manual, _param2);
                      }

                      return Promise.resolve(buildNewPages(pages, oldPageParams[i], oldPages[i]));
                    });
                  };

                  for (var i = 1; i < oldPages.length; i++) {
                    _loop(i);
                  }
                })();
              }

        var finalPromise = promise.then(function (pages) {
          return {
            pages: pages,
            pageParams: newPageParams
          };
        });
        var finalPromiseAsAny = finalPromise;

        finalPromiseAsAny.cancel = function () {
          cancelled = true;
          abortController == null ? void 0 : abortController.abort();

          if ((0,_retryer__WEBPACK_IMPORTED_MODULE_1__.isCancelable)(promise)) {
            promise.cancel();
          }
        };

        return finalPromise;
      };
    }
  };
}
function getNextPageParam(options, pages) {
  return options.getNextPageParam == null ? void 0 : options.getNextPageParam(pages[pages.length - 1], pages);
}
function getPreviousPageParam(options, pages) {
  return options.getPreviousPageParam == null ? void 0 : options.getPreviousPageParam(pages[0], pages);
}
/**
 * Checks if there is a next page.
 * Returns `undefined` if it cannot be determined.
 */

function hasNextPage(options, pages) {
  if (options.getNextPageParam && Array.isArray(pages)) {
    var nextPageParam = getNextPageParam(options, pages);
    return typeof nextPageParam !== 'undefined' && nextPageParam !== null && nextPageParam !== false;
  }
}
/**
 * Checks if there is a previous page.
 * Returns `undefined` if it cannot be determined.
 */

function hasPreviousPage(options, pages) {
  if (options.getPreviousPageParam && Array.isArray(pages)) {
    var previousPageParam = getPreviousPageParam(options, pages);
    return typeof previousPageParam !== 'undefined' && previousPageParam !== null && previousPageParam !== false;
  }
}

/***/ }),

/***/ "./node_modules/react-query/es/core/infiniteQueryObserver.js":
/*!*******************************************************************!*\
  !*** ./node_modules/react-query/es/core/infiniteQueryObserver.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "InfiniteQueryObserver": () => (/* binding */ InfiniteQueryObserver)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _queryObserver__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./queryObserver */ "./node_modules/react-query/es/core/queryObserver.js");
/* harmony import */ var _infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./infiniteQueryBehavior */ "./node_modules/react-query/es/core/infiniteQueryBehavior.js");




var InfiniteQueryObserver = /*#__PURE__*/function (_QueryObserver) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__["default"])(InfiniteQueryObserver, _QueryObserver);

  // Type override
  // Type override
  // Type override
  // eslint-disable-next-line @typescript-eslint/no-useless-constructor
  function InfiniteQueryObserver(client, options) {
    return _QueryObserver.call(this, client, options) || this;
  }

  var _proto = InfiniteQueryObserver.prototype;

  _proto.bindMethods = function bindMethods() {
    _QueryObserver.prototype.bindMethods.call(this);

    this.fetchNextPage = this.fetchNextPage.bind(this);
    this.fetchPreviousPage = this.fetchPreviousPage.bind(this);
  };

  _proto.setOptions = function setOptions(options, notifyOptions) {
    _QueryObserver.prototype.setOptions.call(this, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options, {
      behavior: (0,_infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_2__.infiniteQueryBehavior)()
    }), notifyOptions);
  };

  _proto.getOptimisticResult = function getOptimisticResult(options) {
    options.behavior = (0,_infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_2__.infiniteQueryBehavior)();
    return _QueryObserver.prototype.getOptimisticResult.call(this, options);
  };

  _proto.fetchNextPage = function fetchNextPage(options) {
    var _options$cancelRefetc;

    return this.fetch({
      // TODO consider removing `?? true` in future breaking change, to be consistent with `refetch` API (see https://github.com/tannerlinsley/react-query/issues/2617)
      cancelRefetch: (_options$cancelRefetc = options == null ? void 0 : options.cancelRefetch) != null ? _options$cancelRefetc : true,
      throwOnError: options == null ? void 0 : options.throwOnError,
      meta: {
        fetchMore: {
          direction: 'forward',
          pageParam: options == null ? void 0 : options.pageParam
        }
      }
    });
  };

  _proto.fetchPreviousPage = function fetchPreviousPage(options) {
    var _options$cancelRefetc2;

    return this.fetch({
      // TODO consider removing `?? true` in future breaking change, to be consistent with `refetch` API (see https://github.com/tannerlinsley/react-query/issues/2617)
      cancelRefetch: (_options$cancelRefetc2 = options == null ? void 0 : options.cancelRefetch) != null ? _options$cancelRefetc2 : true,
      throwOnError: options == null ? void 0 : options.throwOnError,
      meta: {
        fetchMore: {
          direction: 'backward',
          pageParam: options == null ? void 0 : options.pageParam
        }
      }
    });
  };

  _proto.createResult = function createResult(query, options) {
    var _state$data, _state$data2, _state$fetchMeta, _state$fetchMeta$fetc, _state$fetchMeta2, _state$fetchMeta2$fet;

    var state = query.state;

    var result = _QueryObserver.prototype.createResult.call(this, query, options);

    return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, result, {
      fetchNextPage: this.fetchNextPage,
      fetchPreviousPage: this.fetchPreviousPage,
      hasNextPage: (0,_infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_2__.hasNextPage)(options, (_state$data = state.data) == null ? void 0 : _state$data.pages),
      hasPreviousPage: (0,_infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_2__.hasPreviousPage)(options, (_state$data2 = state.data) == null ? void 0 : _state$data2.pages),
      isFetchingNextPage: state.isFetching && ((_state$fetchMeta = state.fetchMeta) == null ? void 0 : (_state$fetchMeta$fetc = _state$fetchMeta.fetchMore) == null ? void 0 : _state$fetchMeta$fetc.direction) === 'forward',
      isFetchingPreviousPage: state.isFetching && ((_state$fetchMeta2 = state.fetchMeta) == null ? void 0 : (_state$fetchMeta2$fet = _state$fetchMeta2.fetchMore) == null ? void 0 : _state$fetchMeta2$fet.direction) === 'backward'
    });
  };

  return InfiniteQueryObserver;
}(_queryObserver__WEBPACK_IMPORTED_MODULE_3__.QueryObserver);

/***/ }),

/***/ "./node_modules/react-query/es/core/logger.js":
/*!****************************************************!*\
  !*** ./node_modules/react-query/es/core/logger.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getLogger": () => (/* binding */ getLogger),
/* harmony export */   "setLogger": () => (/* binding */ setLogger)
/* harmony export */ });
// TYPES
// FUNCTIONS
var logger = console;
function getLogger() {
  return logger;
}
function setLogger(newLogger) {
  logger = newLogger;
}

/***/ }),

/***/ "./node_modules/react-query/es/core/mutation.js":
/*!******************************************************!*\
  !*** ./node_modules/react-query/es/core/mutation.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Mutation": () => (/* binding */ Mutation),
/* harmony export */   "getDefaultState": () => (/* binding */ getDefaultState)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./logger */ "./node_modules/react-query/es/core/logger.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _retryer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./retryer */ "./node_modules/react-query/es/core/retryer.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");




 // TYPES

// CLASS
var Mutation = /*#__PURE__*/function () {
  function Mutation(config) {
    this.options = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, config.defaultOptions, config.options);
    this.mutationId = config.mutationId;
    this.mutationCache = config.mutationCache;
    this.observers = [];
    this.state = config.state || getDefaultState();
    this.meta = config.meta;
  }

  var _proto = Mutation.prototype;

  _proto.setState = function setState(state) {
    this.dispatch({
      type: 'setState',
      state: state
    });
  };

  _proto.addObserver = function addObserver(observer) {
    if (this.observers.indexOf(observer) === -1) {
      this.observers.push(observer);
    }
  };

  _proto.removeObserver = function removeObserver(observer) {
    this.observers = this.observers.filter(function (x) {
      return x !== observer;
    });
  };

  _proto.cancel = function cancel() {
    if (this.retryer) {
      this.retryer.cancel();
      return this.retryer.promise.then(_utils__WEBPACK_IMPORTED_MODULE_1__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_1__.noop);
    }

    return Promise.resolve();
  };

  _proto.continue = function _continue() {
    if (this.retryer) {
      this.retryer.continue();
      return this.retryer.promise;
    }

    return this.execute();
  };

  _proto.execute = function execute() {
    var _this = this;

    var data;
    var restored = this.state.status === 'loading';
    var promise = Promise.resolve();

    if (!restored) {
      this.dispatch({
        type: 'loading',
        variables: this.options.variables
      });
      promise = promise.then(function () {
        // Notify cache callback
        _this.mutationCache.config.onMutate == null ? void 0 : _this.mutationCache.config.onMutate(_this.state.variables, _this);
      }).then(function () {
        return _this.options.onMutate == null ? void 0 : _this.options.onMutate(_this.state.variables);
      }).then(function (context) {
        if (context !== _this.state.context) {
          _this.dispatch({
            type: 'loading',
            context: context,
            variables: _this.state.variables
          });
        }
      });
    }

    return promise.then(function () {
      return _this.executeMutation();
    }).then(function (result) {
      data = result; // Notify cache callback

      _this.mutationCache.config.onSuccess == null ? void 0 : _this.mutationCache.config.onSuccess(data, _this.state.variables, _this.state.context, _this);
    }).then(function () {
      return _this.options.onSuccess == null ? void 0 : _this.options.onSuccess(data, _this.state.variables, _this.state.context);
    }).then(function () {
      return _this.options.onSettled == null ? void 0 : _this.options.onSettled(data, null, _this.state.variables, _this.state.context);
    }).then(function () {
      _this.dispatch({
        type: 'success',
        data: data
      });

      return data;
    }).catch(function (error) {
      // Notify cache callback
      _this.mutationCache.config.onError == null ? void 0 : _this.mutationCache.config.onError(error, _this.state.variables, _this.state.context, _this); // Log error

      (0,_logger__WEBPACK_IMPORTED_MODULE_2__.getLogger)().error(error);
      return Promise.resolve().then(function () {
        return _this.options.onError == null ? void 0 : _this.options.onError(error, _this.state.variables, _this.state.context);
      }).then(function () {
        return _this.options.onSettled == null ? void 0 : _this.options.onSettled(undefined, error, _this.state.variables, _this.state.context);
      }).then(function () {
        _this.dispatch({
          type: 'error',
          error: error
        });

        throw error;
      });
    });
  };

  _proto.executeMutation = function executeMutation() {
    var _this2 = this,
        _this$options$retry;

    this.retryer = new _retryer__WEBPACK_IMPORTED_MODULE_3__.Retryer({
      fn: function fn() {
        if (!_this2.options.mutationFn) {
          return Promise.reject('No mutationFn found');
        }

        return _this2.options.mutationFn(_this2.state.variables);
      },
      onFail: function onFail() {
        _this2.dispatch({
          type: 'failed'
        });
      },
      onPause: function onPause() {
        _this2.dispatch({
          type: 'pause'
        });
      },
      onContinue: function onContinue() {
        _this2.dispatch({
          type: 'continue'
        });
      },
      retry: (_this$options$retry = this.options.retry) != null ? _this$options$retry : 0,
      retryDelay: this.options.retryDelay
    });
    return this.retryer.promise;
  };

  _proto.dispatch = function dispatch(action) {
    var _this3 = this;

    this.state = reducer(this.state, action);
    _notifyManager__WEBPACK_IMPORTED_MODULE_4__.notifyManager.batch(function () {
      _this3.observers.forEach(function (observer) {
        observer.onMutationUpdate(action);
      });

      _this3.mutationCache.notify(_this3);
    });
  };

  return Mutation;
}();
function getDefaultState() {
  return {
    context: undefined,
    data: undefined,
    error: null,
    failureCount: 0,
    isPaused: false,
    status: 'idle',
    variables: undefined
  };
}

function reducer(state, action) {
  switch (action.type) {
    case 'failed':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        failureCount: state.failureCount + 1
      });

    case 'pause':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        isPaused: true
      });

    case 'continue':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        isPaused: false
      });

    case 'loading':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        context: action.context,
        data: undefined,
        error: null,
        isPaused: false,
        status: 'loading',
        variables: action.variables
      });

    case 'success':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        data: action.data,
        error: null,
        status: 'success',
        isPaused: false
      });

    case 'error':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
        data: undefined,
        error: action.error,
        failureCount: state.failureCount + 1,
        isPaused: false,
        status: 'error'
      });

    case 'setState':
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, action.state);

    default:
      return state;
  }
}

/***/ }),

/***/ "./node_modules/react-query/es/core/mutationCache.js":
/*!***********************************************************!*\
  !*** ./node_modules/react-query/es/core/mutationCache.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "MutationCache": () => (/* binding */ MutationCache)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _mutation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./mutation */ "./node_modules/react-query/es/core/mutation.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");




 // TYPES

// CLASS
var MutationCache = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(MutationCache, _Subscribable);

  function MutationCache(config) {
    var _this;

    _this = _Subscribable.call(this) || this;
    _this.config = config || {};
    _this.mutations = [];
    _this.mutationId = 0;
    return _this;
  }

  var _proto = MutationCache.prototype;

  _proto.build = function build(client, options, state) {
    var mutation = new _mutation__WEBPACK_IMPORTED_MODULE_1__.Mutation({
      mutationCache: this,
      mutationId: ++this.mutationId,
      options: client.defaultMutationOptions(options),
      state: state,
      defaultOptions: options.mutationKey ? client.getMutationDefaults(options.mutationKey) : undefined,
      meta: options.meta
    });
    this.add(mutation);
    return mutation;
  };

  _proto.add = function add(mutation) {
    this.mutations.push(mutation);
    this.notify(mutation);
  };

  _proto.remove = function remove(mutation) {
    this.mutations = this.mutations.filter(function (x) {
      return x !== mutation;
    });
    mutation.cancel();
    this.notify(mutation);
  };

  _proto.clear = function clear() {
    var _this2 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_2__.notifyManager.batch(function () {
      _this2.mutations.forEach(function (mutation) {
        _this2.remove(mutation);
      });
    });
  };

  _proto.getAll = function getAll() {
    return this.mutations;
  };

  _proto.find = function find(filters) {
    if (typeof filters.exact === 'undefined') {
      filters.exact = true;
    }

    return this.mutations.find(function (mutation) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_3__.matchMutation)(filters, mutation);
    });
  };

  _proto.findAll = function findAll(filters) {
    return this.mutations.filter(function (mutation) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_3__.matchMutation)(filters, mutation);
    });
  };

  _proto.notify = function notify(mutation) {
    var _this3 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_2__.notifyManager.batch(function () {
      _this3.listeners.forEach(function (listener) {
        listener(mutation);
      });
    });
  };

  _proto.onFocus = function onFocus() {
    this.resumePausedMutations();
  };

  _proto.onOnline = function onOnline() {
    this.resumePausedMutations();
  };

  _proto.resumePausedMutations = function resumePausedMutations() {
    var pausedMutations = this.mutations.filter(function (x) {
      return x.state.isPaused;
    });
    return _notifyManager__WEBPACK_IMPORTED_MODULE_2__.notifyManager.batch(function () {
      return pausedMutations.reduce(function (promise, mutation) {
        return promise.then(function () {
          return mutation.continue().catch(_utils__WEBPACK_IMPORTED_MODULE_3__.noop);
        });
      }, Promise.resolve());
    });
  };

  return MutationCache;
}(_subscribable__WEBPACK_IMPORTED_MODULE_4__.Subscribable);

/***/ }),

/***/ "./node_modules/react-query/es/core/mutationObserver.js":
/*!**************************************************************!*\
  !*** ./node_modules/react-query/es/core/mutationObserver.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "MutationObserver": () => (/* binding */ MutationObserver)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _mutation__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./mutation */ "./node_modules/react-query/es/core/mutation.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");





// CLASS
var MutationObserver = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__["default"])(MutationObserver, _Subscribable);

  function MutationObserver(client, options) {
    var _this;

    _this = _Subscribable.call(this) || this;
    _this.client = client;

    _this.setOptions(options);

    _this.bindMethods();

    _this.updateResult();

    return _this;
  }

  var _proto = MutationObserver.prototype;

  _proto.bindMethods = function bindMethods() {
    this.mutate = this.mutate.bind(this);
    this.reset = this.reset.bind(this);
  };

  _proto.setOptions = function setOptions(options) {
    this.options = this.client.defaultMutationOptions(options);
  };

  _proto.onUnsubscribe = function onUnsubscribe() {
    if (!this.listeners.length) {
      var _this$currentMutation;

      (_this$currentMutation = this.currentMutation) == null ? void 0 : _this$currentMutation.removeObserver(this);
    }
  };

  _proto.onMutationUpdate = function onMutationUpdate(action) {
    this.updateResult(); // Determine which callbacks to trigger

    var notifyOptions = {
      listeners: true
    };

    if (action.type === 'success') {
      notifyOptions.onSuccess = true;
    } else if (action.type === 'error') {
      notifyOptions.onError = true;
    }

    this.notify(notifyOptions);
  };

  _proto.getCurrentResult = function getCurrentResult() {
    return this.currentResult;
  };

  _proto.reset = function reset() {
    this.currentMutation = undefined;
    this.updateResult();
    this.notify({
      listeners: true
    });
  };

  _proto.mutate = function mutate(variables, options) {
    this.mutateOptions = options;

    if (this.currentMutation) {
      this.currentMutation.removeObserver(this);
    }

    this.currentMutation = this.client.getMutationCache().build(this.client, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.options, {
      variables: typeof variables !== 'undefined' ? variables : this.options.variables
    }));
    this.currentMutation.addObserver(this);
    return this.currentMutation.execute();
  };

  _proto.updateResult = function updateResult() {
    var state = this.currentMutation ? this.currentMutation.state : (0,_mutation__WEBPACK_IMPORTED_MODULE_2__.getDefaultState)();

    var result = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
      isLoading: state.status === 'loading',
      isSuccess: state.status === 'success',
      isError: state.status === 'error',
      isIdle: state.status === 'idle',
      mutate: this.mutate,
      reset: this.reset
    });

    this.currentResult = result;
  };

  _proto.notify = function notify(options) {
    var _this2 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batch(function () {
      // First trigger the mutate callbacks
      if (_this2.mutateOptions) {
        if (options.onSuccess) {
          _this2.mutateOptions.onSuccess == null ? void 0 : _this2.mutateOptions.onSuccess(_this2.currentResult.data, _this2.currentResult.variables, _this2.currentResult.context);
          _this2.mutateOptions.onSettled == null ? void 0 : _this2.mutateOptions.onSettled(_this2.currentResult.data, null, _this2.currentResult.variables, _this2.currentResult.context);
        } else if (options.onError) {
          _this2.mutateOptions.onError == null ? void 0 : _this2.mutateOptions.onError(_this2.currentResult.error, _this2.currentResult.variables, _this2.currentResult.context);
          _this2.mutateOptions.onSettled == null ? void 0 : _this2.mutateOptions.onSettled(undefined, _this2.currentResult.error, _this2.currentResult.variables, _this2.currentResult.context);
        }
      } // Then trigger the listeners


      if (options.listeners) {
        _this2.listeners.forEach(function (listener) {
          listener(_this2.currentResult);
        });
      }
    });
  };

  return MutationObserver;
}(_subscribable__WEBPACK_IMPORTED_MODULE_4__.Subscribable);

/***/ }),

/***/ "./node_modules/react-query/es/core/notifyManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/react-query/es/core/notifyManager.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "NotifyManager": () => (/* binding */ NotifyManager),
/* harmony export */   "notifyManager": () => (/* binding */ notifyManager)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
 // TYPES

// CLASS
var NotifyManager = /*#__PURE__*/function () {
  function NotifyManager() {
    this.queue = [];
    this.transactions = 0;

    this.notifyFn = function (callback) {
      callback();
    };

    this.batchNotifyFn = function (callback) {
      callback();
    };
  }

  var _proto = NotifyManager.prototype;

  _proto.batch = function batch(callback) {
    var result;
    this.transactions++;

    try {
      result = callback();
    } finally {
      this.transactions--;

      if (!this.transactions) {
        this.flush();
      }
    }

    return result;
  };

  _proto.schedule = function schedule(callback) {
    var _this = this;

    if (this.transactions) {
      this.queue.push(callback);
    } else {
      (0,_utils__WEBPACK_IMPORTED_MODULE_0__.scheduleMicrotask)(function () {
        _this.notifyFn(callback);
      });
    }
  }
  /**
   * All calls to the wrapped function will be batched.
   */
  ;

  _proto.batchCalls = function batchCalls(callback) {
    var _this2 = this;

    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      _this2.schedule(function () {
        callback.apply(void 0, args);
      });
    };
  };

  _proto.flush = function flush() {
    var _this3 = this;

    var queue = this.queue;
    this.queue = [];

    if (queue.length) {
      (0,_utils__WEBPACK_IMPORTED_MODULE_0__.scheduleMicrotask)(function () {
        _this3.batchNotifyFn(function () {
          queue.forEach(function (callback) {
            _this3.notifyFn(callback);
          });
        });
      });
    }
  }
  /**
   * Use this method to set a custom notify function.
   * This can be used to for example wrap notifications with `React.act` while running tests.
   */
  ;

  _proto.setNotifyFunction = function setNotifyFunction(fn) {
    this.notifyFn = fn;
  }
  /**
   * Use this method to set a custom function to batch notifications together into a single tick.
   * By default React Query will use the batch function provided by ReactDOM or React Native.
   */
  ;

  _proto.setBatchNotifyFunction = function setBatchNotifyFunction(fn) {
    this.batchNotifyFn = fn;
  };

  return NotifyManager;
}(); // SINGLETON

var notifyManager = new NotifyManager();

/***/ }),

/***/ "./node_modules/react-query/es/core/onlineManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/react-query/es/core/onlineManager.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "OnlineManager": () => (/* binding */ OnlineManager),
/* harmony export */   "onlineManager": () => (/* binding */ onlineManager)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");



var OnlineManager = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(OnlineManager, _Subscribable);

  function OnlineManager() {
    var _this;

    _this = _Subscribable.call(this) || this;

    _this.setup = function (onOnline) {
      var _window;

      if (!_utils__WEBPACK_IMPORTED_MODULE_1__.isServer && ((_window = window) == null ? void 0 : _window.addEventListener)) {
        var listener = function listener() {
          return onOnline();
        }; // Listen to online


        window.addEventListener('online', listener, false);
        window.addEventListener('offline', listener, false);
        return function () {
          // Be sure to unsubscribe if a new handler is set
          window.removeEventListener('online', listener);
          window.removeEventListener('offline', listener);
        };
      }
    };

    return _this;
  }

  var _proto = OnlineManager.prototype;

  _proto.onSubscribe = function onSubscribe() {
    if (!this.cleanup) {
      this.setEventListener(this.setup);
    }
  };

  _proto.onUnsubscribe = function onUnsubscribe() {
    if (!this.hasListeners()) {
      var _this$cleanup;

      (_this$cleanup = this.cleanup) == null ? void 0 : _this$cleanup.call(this);
      this.cleanup = undefined;
    }
  };

  _proto.setEventListener = function setEventListener(setup) {
    var _this$cleanup2,
        _this2 = this;

    this.setup = setup;
    (_this$cleanup2 = this.cleanup) == null ? void 0 : _this$cleanup2.call(this);
    this.cleanup = setup(function (online) {
      if (typeof online === 'boolean') {
        _this2.setOnline(online);
      } else {
        _this2.onOnline();
      }
    });
  };

  _proto.setOnline = function setOnline(online) {
    this.online = online;

    if (online) {
      this.onOnline();
    }
  };

  _proto.onOnline = function onOnline() {
    this.listeners.forEach(function (listener) {
      listener();
    });
  };

  _proto.isOnline = function isOnline() {
    if (typeof this.online === 'boolean') {
      return this.online;
    }

    if (typeof navigator === 'undefined' || typeof navigator.onLine === 'undefined') {
      return true;
    }

    return navigator.onLine;
  };

  return OnlineManager;
}(_subscribable__WEBPACK_IMPORTED_MODULE_2__.Subscribable);
var onlineManager = new OnlineManager();

/***/ }),

/***/ "./node_modules/react-query/es/core/queriesObserver.js":
/*!*************************************************************!*\
  !*** ./node_modules/react-query/es/core/queriesObserver.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueriesObserver": () => (/* binding */ QueriesObserver)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _queryObserver__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./queryObserver */ "./node_modules/react-query/es/core/queryObserver.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");





var QueriesObserver = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(QueriesObserver, _Subscribable);

  function QueriesObserver(client, queries) {
    var _this;

    _this = _Subscribable.call(this) || this;
    _this.client = client;
    _this.queries = [];
    _this.result = [];
    _this.observers = [];
    _this.observersMap = {};

    if (queries) {
      _this.setQueries(queries);
    }

    return _this;
  }

  var _proto = QueriesObserver.prototype;

  _proto.onSubscribe = function onSubscribe() {
    var _this2 = this;

    if (this.listeners.length === 1) {
      this.observers.forEach(function (observer) {
        observer.subscribe(function (result) {
          _this2.onUpdate(observer, result);
        });
      });
    }
  };

  _proto.onUnsubscribe = function onUnsubscribe() {
    if (!this.listeners.length) {
      this.destroy();
    }
  };

  _proto.destroy = function destroy() {
    this.listeners = [];
    this.observers.forEach(function (observer) {
      observer.destroy();
    });
  };

  _proto.setQueries = function setQueries(queries, notifyOptions) {
    this.queries = queries;
    this.updateObservers(notifyOptions);
  };

  _proto.getCurrentResult = function getCurrentResult() {
    return this.result;
  };

  _proto.getOptimisticResult = function getOptimisticResult(queries) {
    return this.findMatchingObservers(queries).map(function (match) {
      return match.observer.getOptimisticResult(match.defaultedQueryOptions);
    });
  };

  _proto.findMatchingObservers = function findMatchingObservers(queries) {
    var _this3 = this;

    var prevObservers = this.observers;
    var defaultedQueryOptions = queries.map(function (options) {
      return _this3.client.defaultQueryObserverOptions(options);
    });
    var matchingObservers = defaultedQueryOptions.flatMap(function (defaultedOptions) {
      var match = prevObservers.find(function (observer) {
        return observer.options.queryHash === defaultedOptions.queryHash;
      });

      if (match != null) {
        return [{
          defaultedQueryOptions: defaultedOptions,
          observer: match
        }];
      }

      return [];
    });
    var matchedQueryHashes = matchingObservers.map(function (match) {
      return match.defaultedQueryOptions.queryHash;
    });
    var unmatchedQueries = defaultedQueryOptions.filter(function (defaultedOptions) {
      return !matchedQueryHashes.includes(defaultedOptions.queryHash);
    });
    var unmatchedObservers = prevObservers.filter(function (prevObserver) {
      return !matchingObservers.some(function (match) {
        return match.observer === prevObserver;
      });
    });
    var newOrReusedObservers = unmatchedQueries.map(function (options, index) {
      if (options.keepPreviousData) {
        // return previous data from one of the observers that no longer match
        var previouslyUsedObserver = unmatchedObservers[index];

        if (previouslyUsedObserver !== undefined) {
          return {
            defaultedQueryOptions: options,
            observer: previouslyUsedObserver
          };
        }
      }

      return {
        defaultedQueryOptions: options,
        observer: _this3.getObserver(options)
      };
    });

    var sortMatchesByOrderOfQueries = function sortMatchesByOrderOfQueries(a, b) {
      return defaultedQueryOptions.indexOf(a.defaultedQueryOptions) - defaultedQueryOptions.indexOf(b.defaultedQueryOptions);
    };

    return matchingObservers.concat(newOrReusedObservers).sort(sortMatchesByOrderOfQueries);
  };

  _proto.getObserver = function getObserver(options) {
    var defaultedOptions = this.client.defaultQueryObserverOptions(options);
    var currentObserver = this.observersMap[defaultedOptions.queryHash];
    return currentObserver != null ? currentObserver : new _queryObserver__WEBPACK_IMPORTED_MODULE_1__.QueryObserver(this.client, defaultedOptions);
  };

  _proto.updateObservers = function updateObservers(notifyOptions) {
    var _this4 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_2__.notifyManager.batch(function () {
      var prevObservers = _this4.observers;

      var newObserverMatches = _this4.findMatchingObservers(_this4.queries); // set options for the new observers to notify of changes


      newObserverMatches.forEach(function (match) {
        return match.observer.setOptions(match.defaultedQueryOptions, notifyOptions);
      });
      var newObservers = newObserverMatches.map(function (match) {
        return match.observer;
      });
      var newObserversMap = Object.fromEntries(newObservers.map(function (observer) {
        return [observer.options.queryHash, observer];
      }));
      var newResult = newObservers.map(function (observer) {
        return observer.getCurrentResult();
      });
      var hasIndexChange = newObservers.some(function (observer, index) {
        return observer !== prevObservers[index];
      });

      if (prevObservers.length === newObservers.length && !hasIndexChange) {
        return;
      }

      _this4.observers = newObservers;
      _this4.observersMap = newObserversMap;
      _this4.result = newResult;

      if (!_this4.hasListeners()) {
        return;
      }

      (0,_utils__WEBPACK_IMPORTED_MODULE_3__.difference)(prevObservers, newObservers).forEach(function (observer) {
        observer.destroy();
      });
      (0,_utils__WEBPACK_IMPORTED_MODULE_3__.difference)(newObservers, prevObservers).forEach(function (observer) {
        observer.subscribe(function (result) {
          _this4.onUpdate(observer, result);
        });
      });

      _this4.notify();
    });
  };

  _proto.onUpdate = function onUpdate(observer, result) {
    var index = this.observers.indexOf(observer);

    if (index !== -1) {
      this.result = (0,_utils__WEBPACK_IMPORTED_MODULE_3__.replaceAt)(this.result, index, result);
      this.notify();
    }
  };

  _proto.notify = function notify() {
    var _this5 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_2__.notifyManager.batch(function () {
      _this5.listeners.forEach(function (listener) {
        listener(_this5.result);
      });
    });
  };

  return QueriesObserver;
}(_subscribable__WEBPACK_IMPORTED_MODULE_4__.Subscribable);

/***/ }),

/***/ "./node_modules/react-query/es/core/query.js":
/*!***************************************************!*\
  !*** ./node_modules/react-query/es/core/query.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Query": () => (/* binding */ Query)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./logger */ "./node_modules/react-query/es/core/logger.js");
/* harmony import */ var _retryer__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./retryer */ "./node_modules/react-query/es/core/retryer.js");




 // TYPES

// CLASS
var Query = /*#__PURE__*/function () {
  function Query(config) {
    this.abortSignalConsumed = false;
    this.hadObservers = false;
    this.defaultOptions = config.defaultOptions;
    this.setOptions(config.options);
    this.observers = [];
    this.cache = config.cache;
    this.queryKey = config.queryKey;
    this.queryHash = config.queryHash;
    this.initialState = config.state || this.getDefaultState(this.options);
    this.state = this.initialState;
    this.meta = config.meta;
    this.scheduleGc();
  }

  var _proto = Query.prototype;

  _proto.setOptions = function setOptions(options) {
    var _this$options$cacheTi;

    this.options = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.defaultOptions, options);
    this.meta = options == null ? void 0 : options.meta; // Default to 5 minutes if not cache time is set

    this.cacheTime = Math.max(this.cacheTime || 0, (_this$options$cacheTi = this.options.cacheTime) != null ? _this$options$cacheTi : 5 * 60 * 1000);
  };

  _proto.setDefaultOptions = function setDefaultOptions(options) {
    this.defaultOptions = options;
  };

  _proto.scheduleGc = function scheduleGc() {
    var _this = this;

    this.clearGcTimeout();

    if ((0,_utils__WEBPACK_IMPORTED_MODULE_1__.isValidTimeout)(this.cacheTime)) {
      this.gcTimeout = setTimeout(function () {
        _this.optionalRemove();
      }, this.cacheTime);
    }
  };

  _proto.clearGcTimeout = function clearGcTimeout() {
    if (this.gcTimeout) {
      clearTimeout(this.gcTimeout);
      this.gcTimeout = undefined;
    }
  };

  _proto.optionalRemove = function optionalRemove() {
    if (!this.observers.length) {
      if (this.state.isFetching) {
        if (this.hadObservers) {
          this.scheduleGc();
        }
      } else {
        this.cache.remove(this);
      }
    }
  };

  _proto.setData = function setData(updater, options) {
    var _this$options$isDataE, _this$options;

    var prevData = this.state.data; // Get the new data

    var data = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.functionalUpdate)(updater, prevData); // Use prev data if an isDataEqual function is defined and returns `true`

    if ((_this$options$isDataE = (_this$options = this.options).isDataEqual) == null ? void 0 : _this$options$isDataE.call(_this$options, prevData, data)) {
      data = prevData;
    } else if (this.options.structuralSharing !== false) {
      // Structurally share data between prev and new data if needed
      data = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.replaceEqualDeep)(prevData, data);
    } // Set data and mark it as cached


    this.dispatch({
      data: data,
      type: 'success',
      dataUpdatedAt: options == null ? void 0 : options.updatedAt
    });
    return data;
  };

  _proto.setState = function setState(state, setStateOptions) {
    this.dispatch({
      type: 'setState',
      state: state,
      setStateOptions: setStateOptions
    });
  };

  _proto.cancel = function cancel(options) {
    var _this$retryer;

    var promise = this.promise;
    (_this$retryer = this.retryer) == null ? void 0 : _this$retryer.cancel(options);
    return promise ? promise.then(_utils__WEBPACK_IMPORTED_MODULE_1__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_1__.noop) : Promise.resolve();
  };

  _proto.destroy = function destroy() {
    this.clearGcTimeout();
    this.cancel({
      silent: true
    });
  };

  _proto.reset = function reset() {
    this.destroy();
    this.setState(this.initialState);
  };

  _proto.isActive = function isActive() {
    return this.observers.some(function (observer) {
      return observer.options.enabled !== false;
    });
  };

  _proto.isFetching = function isFetching() {
    return this.state.isFetching;
  };

  _proto.isStale = function isStale() {
    return this.state.isInvalidated || !this.state.dataUpdatedAt || this.observers.some(function (observer) {
      return observer.getCurrentResult().isStale;
    });
  };

  _proto.isStaleByTime = function isStaleByTime(staleTime) {
    if (staleTime === void 0) {
      staleTime = 0;
    }

    return this.state.isInvalidated || !this.state.dataUpdatedAt || !(0,_utils__WEBPACK_IMPORTED_MODULE_1__.timeUntilStale)(this.state.dataUpdatedAt, staleTime);
  };

  _proto.onFocus = function onFocus() {
    var _this$retryer2;

    var observer = this.observers.find(function (x) {
      return x.shouldFetchOnWindowFocus();
    });

    if (observer) {
      observer.refetch();
    } // Continue fetch if currently paused


    (_this$retryer2 = this.retryer) == null ? void 0 : _this$retryer2.continue();
  };

  _proto.onOnline = function onOnline() {
    var _this$retryer3;

    var observer = this.observers.find(function (x) {
      return x.shouldFetchOnReconnect();
    });

    if (observer) {
      observer.refetch();
    } // Continue fetch if currently paused


    (_this$retryer3 = this.retryer) == null ? void 0 : _this$retryer3.continue();
  };

  _proto.addObserver = function addObserver(observer) {
    if (this.observers.indexOf(observer) === -1) {
      this.observers.push(observer);
      this.hadObservers = true; // Stop the query from being garbage collected

      this.clearGcTimeout();
      this.cache.notify({
        type: 'observerAdded',
        query: this,
        observer: observer
      });
    }
  };

  _proto.removeObserver = function removeObserver(observer) {
    if (this.observers.indexOf(observer) !== -1) {
      this.observers = this.observers.filter(function (x) {
        return x !== observer;
      });

      if (!this.observers.length) {
        // If the transport layer does not support cancellation
        // we'll let the query continue so the result can be cached
        if (this.retryer) {
          if (this.retryer.isTransportCancelable || this.abortSignalConsumed) {
            this.retryer.cancel({
              revert: true
            });
          } else {
            this.retryer.cancelRetry();
          }
        }

        if (this.cacheTime) {
          this.scheduleGc();
        } else {
          this.cache.remove(this);
        }
      }

      this.cache.notify({
        type: 'observerRemoved',
        query: this,
        observer: observer
      });
    }
  };

  _proto.getObserversCount = function getObserversCount() {
    return this.observers.length;
  };

  _proto.invalidate = function invalidate() {
    if (!this.state.isInvalidated) {
      this.dispatch({
        type: 'invalidate'
      });
    }
  };

  _proto.fetch = function fetch(options, fetchOptions) {
    var _this2 = this,
        _this$options$behavio,
        _context$fetchOptions,
        _abortController$abor;

    if (this.state.isFetching) {
      if (this.state.dataUpdatedAt && (fetchOptions == null ? void 0 : fetchOptions.cancelRefetch)) {
        // Silently cancel current fetch if the user wants to cancel refetches
        this.cancel({
          silent: true
        });
      } else if (this.promise) {
        var _this$retryer4;

        // make sure that retries that were potentially cancelled due to unmounts can continue
        (_this$retryer4 = this.retryer) == null ? void 0 : _this$retryer4.continueRetry(); // Return current promise if we are already fetching

        return this.promise;
      }
    } // Update config if passed, otherwise the config from the last execution is used


    if (options) {
      this.setOptions(options);
    } // Use the options from the first observer with a query function if no function is found.
    // This can happen when the query is hydrated or created with setQueryData.


    if (!this.options.queryFn) {
      var observer = this.observers.find(function (x) {
        return x.options.queryFn;
      });

      if (observer) {
        this.setOptions(observer.options);
      }
    }

    var queryKey = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.ensureQueryKeyArray)(this.queryKey);
    var abortController = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.getAbortController)(); // Create query function context

    var queryFnContext = {
      queryKey: queryKey,
      pageParam: undefined,
      meta: this.meta
    };
    Object.defineProperty(queryFnContext, 'signal', {
      enumerable: true,
      get: function get() {
        if (abortController) {
          _this2.abortSignalConsumed = true;
          return abortController.signal;
        }

        return undefined;
      }
    }); // Create fetch function

    var fetchFn = function fetchFn() {
      if (!_this2.options.queryFn) {
        return Promise.reject('Missing queryFn');
      }

      _this2.abortSignalConsumed = false;
      return _this2.options.queryFn(queryFnContext);
    }; // Trigger behavior hook


    var context = {
      fetchOptions: fetchOptions,
      options: this.options,
      queryKey: queryKey,
      state: this.state,
      fetchFn: fetchFn,
      meta: this.meta
    };

    if ((_this$options$behavio = this.options.behavior) == null ? void 0 : _this$options$behavio.onFetch) {
      var _this$options$behavio2;

      (_this$options$behavio2 = this.options.behavior) == null ? void 0 : _this$options$behavio2.onFetch(context);
    } // Store state in case the current fetch needs to be reverted


    this.revertState = this.state; // Set to fetching state if not already in it

    if (!this.state.isFetching || this.state.fetchMeta !== ((_context$fetchOptions = context.fetchOptions) == null ? void 0 : _context$fetchOptions.meta)) {
      var _context$fetchOptions2;

      this.dispatch({
        type: 'fetch',
        meta: (_context$fetchOptions2 = context.fetchOptions) == null ? void 0 : _context$fetchOptions2.meta
      });
    } // Try to fetch the data


    this.retryer = new _retryer__WEBPACK_IMPORTED_MODULE_2__.Retryer({
      fn: context.fetchFn,
      abort: abortController == null ? void 0 : (_abortController$abor = abortController.abort) == null ? void 0 : _abortController$abor.bind(abortController),
      onSuccess: function onSuccess(data) {
        _this2.setData(data); // Notify cache callback


        _this2.cache.config.onSuccess == null ? void 0 : _this2.cache.config.onSuccess(data, _this2); // Remove query after fetching if cache time is 0

        if (_this2.cacheTime === 0) {
          _this2.optionalRemove();
        }
      },
      onError: function onError(error) {
        // Optimistically update state if needed
        if (!((0,_retryer__WEBPACK_IMPORTED_MODULE_2__.isCancelledError)(error) && error.silent)) {
          _this2.dispatch({
            type: 'error',
            error: error
          });
        }

        if (!(0,_retryer__WEBPACK_IMPORTED_MODULE_2__.isCancelledError)(error)) {
          // Notify cache callback
          _this2.cache.config.onError == null ? void 0 : _this2.cache.config.onError(error, _this2); // Log error

          (0,_logger__WEBPACK_IMPORTED_MODULE_3__.getLogger)().error(error);
        } // Remove query after fetching if cache time is 0


        if (_this2.cacheTime === 0) {
          _this2.optionalRemove();
        }
      },
      onFail: function onFail() {
        _this2.dispatch({
          type: 'failed'
        });
      },
      onPause: function onPause() {
        _this2.dispatch({
          type: 'pause'
        });
      },
      onContinue: function onContinue() {
        _this2.dispatch({
          type: 'continue'
        });
      },
      retry: context.options.retry,
      retryDelay: context.options.retryDelay
    });
    this.promise = this.retryer.promise;
    return this.promise;
  };

  _proto.dispatch = function dispatch(action) {
    var _this3 = this;

    this.state = this.reducer(this.state, action);
    _notifyManager__WEBPACK_IMPORTED_MODULE_4__.notifyManager.batch(function () {
      _this3.observers.forEach(function (observer) {
        observer.onQueryUpdate(action);
      });

      _this3.cache.notify({
        query: _this3,
        type: 'queryUpdated',
        action: action
      });
    });
  };

  _proto.getDefaultState = function getDefaultState(options) {
    var data = typeof options.initialData === 'function' ? options.initialData() : options.initialData;
    var hasInitialData = typeof options.initialData !== 'undefined';
    var initialDataUpdatedAt = hasInitialData ? typeof options.initialDataUpdatedAt === 'function' ? options.initialDataUpdatedAt() : options.initialDataUpdatedAt : 0;
    var hasData = typeof data !== 'undefined';
    return {
      data: data,
      dataUpdateCount: 0,
      dataUpdatedAt: hasData ? initialDataUpdatedAt != null ? initialDataUpdatedAt : Date.now() : 0,
      error: null,
      errorUpdateCount: 0,
      errorUpdatedAt: 0,
      fetchFailureCount: 0,
      fetchMeta: null,
      isFetching: false,
      isInvalidated: false,
      isPaused: false,
      status: hasData ? 'success' : 'idle'
    };
  };

  _proto.reducer = function reducer(state, action) {
    var _action$meta, _action$dataUpdatedAt;

    switch (action.type) {
      case 'failed':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          fetchFailureCount: state.fetchFailureCount + 1
        });

      case 'pause':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          isPaused: true
        });

      case 'continue':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          isPaused: false
        });

      case 'fetch':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          fetchFailureCount: 0,
          fetchMeta: (_action$meta = action.meta) != null ? _action$meta : null,
          isFetching: true,
          isPaused: false
        }, !state.dataUpdatedAt && {
          error: null,
          status: 'loading'
        });

      case 'success':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          data: action.data,
          dataUpdateCount: state.dataUpdateCount + 1,
          dataUpdatedAt: (_action$dataUpdatedAt = action.dataUpdatedAt) != null ? _action$dataUpdatedAt : Date.now(),
          error: null,
          fetchFailureCount: 0,
          isFetching: false,
          isInvalidated: false,
          isPaused: false,
          status: 'success'
        });

      case 'error':
        var error = action.error;

        if ((0,_retryer__WEBPACK_IMPORTED_MODULE_2__.isCancelledError)(error) && error.revert && this.revertState) {
          return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.revertState);
        }

        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          error: error,
          errorUpdateCount: state.errorUpdateCount + 1,
          errorUpdatedAt: Date.now(),
          fetchFailureCount: state.fetchFailureCount + 1,
          isFetching: false,
          isPaused: false,
          status: 'error'
        });

      case 'invalidate':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, {
          isInvalidated: true
        });

      case 'setState':
        return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, state, action.state);

      default:
        return state;
    }
  };

  return Query;
}();

/***/ }),

/***/ "./node_modules/react-query/es/core/queryCache.js":
/*!********************************************************!*\
  !*** ./node_modules/react-query/es/core/queryCache.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueryCache": () => (/* binding */ QueryCache)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _query__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./query */ "./node_modules/react-query/es/core/query.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");





// CLASS
var QueryCache = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(QueryCache, _Subscribable);

  function QueryCache(config) {
    var _this;

    _this = _Subscribable.call(this) || this;
    _this.config = config || {};
    _this.queries = [];
    _this.queriesMap = {};
    return _this;
  }

  var _proto = QueryCache.prototype;

  _proto.build = function build(client, options, state) {
    var _options$queryHash;

    var queryKey = options.queryKey;
    var queryHash = (_options$queryHash = options.queryHash) != null ? _options$queryHash : (0,_utils__WEBPACK_IMPORTED_MODULE_1__.hashQueryKeyByOptions)(queryKey, options);
    var query = this.get(queryHash);

    if (!query) {
      query = new _query__WEBPACK_IMPORTED_MODULE_2__.Query({
        cache: this,
        queryKey: queryKey,
        queryHash: queryHash,
        options: client.defaultQueryOptions(options),
        state: state,
        defaultOptions: client.getQueryDefaults(queryKey),
        meta: options.meta
      });
      this.add(query);
    }

    return query;
  };

  _proto.add = function add(query) {
    if (!this.queriesMap[query.queryHash]) {
      this.queriesMap[query.queryHash] = query;
      this.queries.push(query);
      this.notify({
        type: 'queryAdded',
        query: query
      });
    }
  };

  _proto.remove = function remove(query) {
    var queryInMap = this.queriesMap[query.queryHash];

    if (queryInMap) {
      query.destroy();
      this.queries = this.queries.filter(function (x) {
        return x !== query;
      });

      if (queryInMap === query) {
        delete this.queriesMap[query.queryHash];
      }

      this.notify({
        type: 'queryRemoved',
        query: query
      });
    }
  };

  _proto.clear = function clear() {
    var _this2 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batch(function () {
      _this2.queries.forEach(function (query) {
        _this2.remove(query);
      });
    });
  };

  _proto.get = function get(queryHash) {
    return this.queriesMap[queryHash];
  };

  _proto.getAll = function getAll() {
    return this.queries;
  };

  _proto.find = function find(arg1, arg2) {
    var _parseFilterArgs = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.parseFilterArgs)(arg1, arg2),
        filters = _parseFilterArgs[0];

    if (typeof filters.exact === 'undefined') {
      filters.exact = true;
    }

    return this.queries.find(function (query) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_1__.matchQuery)(filters, query);
    });
  };

  _proto.findAll = function findAll(arg1, arg2) {
    var _parseFilterArgs2 = (0,_utils__WEBPACK_IMPORTED_MODULE_1__.parseFilterArgs)(arg1, arg2),
        filters = _parseFilterArgs2[0];

    return Object.keys(filters).length > 0 ? this.queries.filter(function (query) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_1__.matchQuery)(filters, query);
    }) : this.queries;
  };

  _proto.notify = function notify(event) {
    var _this3 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batch(function () {
      _this3.listeners.forEach(function (listener) {
        listener(event);
      });
    });
  };

  _proto.onFocus = function onFocus() {
    var _this4 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batch(function () {
      _this4.queries.forEach(function (query) {
        query.onFocus();
      });
    });
  };

  _proto.onOnline = function onOnline() {
    var _this5 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batch(function () {
      _this5.queries.forEach(function (query) {
        query.onOnline();
      });
    });
  };

  return QueryCache;
}(_subscribable__WEBPACK_IMPORTED_MODULE_4__.Subscribable);

/***/ }),

/***/ "./node_modules/react-query/es/core/queryClient.js":
/*!*********************************************************!*\
  !*** ./node_modules/react-query/es/core/queryClient.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueryClient": () => (/* binding */ QueryClient)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _queryCache__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./queryCache */ "./node_modules/react-query/es/core/queryCache.js");
/* harmony import */ var _mutationCache__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./mutationCache */ "./node_modules/react-query/es/core/mutationCache.js");
/* harmony import */ var _focusManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./focusManager */ "./node_modules/react-query/es/core/focusManager.js");
/* harmony import */ var _onlineManager__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./onlineManager */ "./node_modules/react-query/es/core/onlineManager.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./infiniteQueryBehavior */ "./node_modules/react-query/es/core/infiniteQueryBehavior.js");








// CLASS
var QueryClient = /*#__PURE__*/function () {
  function QueryClient(config) {
    if (config === void 0) {
      config = {};
    }

    this.queryCache = config.queryCache || new _queryCache__WEBPACK_IMPORTED_MODULE_1__.QueryCache();
    this.mutationCache = config.mutationCache || new _mutationCache__WEBPACK_IMPORTED_MODULE_2__.MutationCache();
    this.defaultOptions = config.defaultOptions || {};
    this.queryDefaults = [];
    this.mutationDefaults = [];
  }

  var _proto = QueryClient.prototype;

  _proto.mount = function mount() {
    var _this = this;

    this.unsubscribeFocus = _focusManager__WEBPACK_IMPORTED_MODULE_3__.focusManager.subscribe(function () {
      if (_focusManager__WEBPACK_IMPORTED_MODULE_3__.focusManager.isFocused() && _onlineManager__WEBPACK_IMPORTED_MODULE_4__.onlineManager.isOnline()) {
        _this.mutationCache.onFocus();

        _this.queryCache.onFocus();
      }
    });
    this.unsubscribeOnline = _onlineManager__WEBPACK_IMPORTED_MODULE_4__.onlineManager.subscribe(function () {
      if (_focusManager__WEBPACK_IMPORTED_MODULE_3__.focusManager.isFocused() && _onlineManager__WEBPACK_IMPORTED_MODULE_4__.onlineManager.isOnline()) {
        _this.mutationCache.onOnline();

        _this.queryCache.onOnline();
      }
    });
  };

  _proto.unmount = function unmount() {
    var _this$unsubscribeFocu, _this$unsubscribeOnli;

    (_this$unsubscribeFocu = this.unsubscribeFocus) == null ? void 0 : _this$unsubscribeFocu.call(this);
    (_this$unsubscribeOnli = this.unsubscribeOnline) == null ? void 0 : _this$unsubscribeOnli.call(this);
  };

  _proto.isFetching = function isFetching(arg1, arg2) {
    var _parseFilterArgs = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2),
        filters = _parseFilterArgs[0];

    filters.fetching = true;
    return this.queryCache.findAll(filters).length;
  };

  _proto.isMutating = function isMutating(filters) {
    return this.mutationCache.findAll((0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, filters, {
      fetching: true
    })).length;
  };

  _proto.getQueryData = function getQueryData(queryKey, filters) {
    var _this$queryCache$find;

    return (_this$queryCache$find = this.queryCache.find(queryKey, filters)) == null ? void 0 : _this$queryCache$find.state.data;
  };

  _proto.getQueriesData = function getQueriesData(queryKeyOrFilters) {
    return this.getQueryCache().findAll(queryKeyOrFilters).map(function (_ref) {
      var queryKey = _ref.queryKey,
          state = _ref.state;
      var data = state.data;
      return [queryKey, data];
    });
  };

  _proto.setQueryData = function setQueryData(queryKey, updater, options) {
    var parsedOptions = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseQueryArgs)(queryKey);
    var defaultedOptions = this.defaultQueryOptions(parsedOptions);
    return this.queryCache.build(this, defaultedOptions).setData(updater, options);
  };

  _proto.setQueriesData = function setQueriesData(queryKeyOrFilters, updater, options) {
    var _this2 = this;

    return _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      return _this2.getQueryCache().findAll(queryKeyOrFilters).map(function (_ref2) {
        var queryKey = _ref2.queryKey;
        return [queryKey, _this2.setQueryData(queryKey, updater, options)];
      });
    });
  };

  _proto.getQueryState = function getQueryState(queryKey, filters) {
    var _this$queryCache$find2;

    return (_this$queryCache$find2 = this.queryCache.find(queryKey, filters)) == null ? void 0 : _this$queryCache$find2.state;
  };

  _proto.removeQueries = function removeQueries(arg1, arg2) {
    var _parseFilterArgs2 = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2),
        filters = _parseFilterArgs2[0];

    var queryCache = this.queryCache;
    _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      queryCache.findAll(filters).forEach(function (query) {
        queryCache.remove(query);
      });
    });
  };

  _proto.resetQueries = function resetQueries(arg1, arg2, arg3) {
    var _this3 = this;

    var _parseFilterArgs3 = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2, arg3),
        filters = _parseFilterArgs3[0],
        options = _parseFilterArgs3[1];

    var queryCache = this.queryCache;

    var refetchFilters = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, filters, {
      active: true
    });

    return _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      queryCache.findAll(filters).forEach(function (query) {
        query.reset();
      });
      return _this3.refetchQueries(refetchFilters, options);
    });
  };

  _proto.cancelQueries = function cancelQueries(arg1, arg2, arg3) {
    var _this4 = this;

    var _parseFilterArgs4 = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2, arg3),
        filters = _parseFilterArgs4[0],
        _parseFilterArgs4$ = _parseFilterArgs4[1],
        cancelOptions = _parseFilterArgs4$ === void 0 ? {} : _parseFilterArgs4$;

    if (typeof cancelOptions.revert === 'undefined') {
      cancelOptions.revert = true;
    }

    var promises = _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      return _this4.queryCache.findAll(filters).map(function (query) {
        return query.cancel(cancelOptions);
      });
    });
    return Promise.all(promises).then(_utils__WEBPACK_IMPORTED_MODULE_5__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);
  };

  _proto.invalidateQueries = function invalidateQueries(arg1, arg2, arg3) {
    var _ref3,
        _filters$refetchActiv,
        _filters$refetchInact,
        _this5 = this;

    var _parseFilterArgs5 = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2, arg3),
        filters = _parseFilterArgs5[0],
        options = _parseFilterArgs5[1];

    var refetchFilters = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, filters, {
      // if filters.refetchActive is not provided and filters.active is explicitly false,
      // e.g. invalidateQueries({ active: false }), we don't want to refetch active queries
      active: (_ref3 = (_filters$refetchActiv = filters.refetchActive) != null ? _filters$refetchActiv : filters.active) != null ? _ref3 : true,
      inactive: (_filters$refetchInact = filters.refetchInactive) != null ? _filters$refetchInact : false
    });

    return _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      _this5.queryCache.findAll(filters).forEach(function (query) {
        query.invalidate();
      });

      return _this5.refetchQueries(refetchFilters, options);
    });
  };

  _proto.refetchQueries = function refetchQueries(arg1, arg2, arg3) {
    var _this6 = this;

    var _parseFilterArgs6 = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseFilterArgs)(arg1, arg2, arg3),
        filters = _parseFilterArgs6[0],
        options = _parseFilterArgs6[1];

    var promises = _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      return _this6.queryCache.findAll(filters).map(function (query) {
        return query.fetch(undefined, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options, {
          meta: {
            refetchPage: filters == null ? void 0 : filters.refetchPage
          }
        }));
      });
    });
    var promise = Promise.all(promises).then(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);

    if (!(options == null ? void 0 : options.throwOnError)) {
      promise = promise.catch(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);
    }

    return promise;
  };

  _proto.fetchQuery = function fetchQuery(arg1, arg2, arg3) {
    var parsedOptions = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseQueryArgs)(arg1, arg2, arg3);
    var defaultedOptions = this.defaultQueryOptions(parsedOptions); // https://github.com/tannerlinsley/react-query/issues/652

    if (typeof defaultedOptions.retry === 'undefined') {
      defaultedOptions.retry = false;
    }

    var query = this.queryCache.build(this, defaultedOptions);
    return query.isStaleByTime(defaultedOptions.staleTime) ? query.fetch(defaultedOptions) : Promise.resolve(query.state.data);
  };

  _proto.prefetchQuery = function prefetchQuery(arg1, arg2, arg3) {
    return this.fetchQuery(arg1, arg2, arg3).then(_utils__WEBPACK_IMPORTED_MODULE_5__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);
  };

  _proto.fetchInfiniteQuery = function fetchInfiniteQuery(arg1, arg2, arg3) {
    var parsedOptions = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.parseQueryArgs)(arg1, arg2, arg3);
    parsedOptions.behavior = (0,_infiniteQueryBehavior__WEBPACK_IMPORTED_MODULE_7__.infiniteQueryBehavior)();
    return this.fetchQuery(parsedOptions);
  };

  _proto.prefetchInfiniteQuery = function prefetchInfiniteQuery(arg1, arg2, arg3) {
    return this.fetchInfiniteQuery(arg1, arg2, arg3).then(_utils__WEBPACK_IMPORTED_MODULE_5__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);
  };

  _proto.cancelMutations = function cancelMutations() {
    var _this7 = this;

    var promises = _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      return _this7.mutationCache.getAll().map(function (mutation) {
        return mutation.cancel();
      });
    });
    return Promise.all(promises).then(_utils__WEBPACK_IMPORTED_MODULE_5__.noop).catch(_utils__WEBPACK_IMPORTED_MODULE_5__.noop);
  };

  _proto.resumePausedMutations = function resumePausedMutations() {
    return this.getMutationCache().resumePausedMutations();
  };

  _proto.executeMutation = function executeMutation(options) {
    return this.mutationCache.build(this, options).execute();
  };

  _proto.getQueryCache = function getQueryCache() {
    return this.queryCache;
  };

  _proto.getMutationCache = function getMutationCache() {
    return this.mutationCache;
  };

  _proto.getDefaultOptions = function getDefaultOptions() {
    return this.defaultOptions;
  };

  _proto.setDefaultOptions = function setDefaultOptions(options) {
    this.defaultOptions = options;
  };

  _proto.setQueryDefaults = function setQueryDefaults(queryKey, options) {
    var result = this.queryDefaults.find(function (x) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_5__.hashQueryKey)(queryKey) === (0,_utils__WEBPACK_IMPORTED_MODULE_5__.hashQueryKey)(x.queryKey);
    });

    if (result) {
      result.defaultOptions = options;
    } else {
      this.queryDefaults.push({
        queryKey: queryKey,
        defaultOptions: options
      });
    }
  };

  _proto.getQueryDefaults = function getQueryDefaults(queryKey) {
    var _this$queryDefaults$f;

    return queryKey ? (_this$queryDefaults$f = this.queryDefaults.find(function (x) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_5__.partialMatchKey)(queryKey, x.queryKey);
    })) == null ? void 0 : _this$queryDefaults$f.defaultOptions : undefined;
  };

  _proto.setMutationDefaults = function setMutationDefaults(mutationKey, options) {
    var result = this.mutationDefaults.find(function (x) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_5__.hashQueryKey)(mutationKey) === (0,_utils__WEBPACK_IMPORTED_MODULE_5__.hashQueryKey)(x.mutationKey);
    });

    if (result) {
      result.defaultOptions = options;
    } else {
      this.mutationDefaults.push({
        mutationKey: mutationKey,
        defaultOptions: options
      });
    }
  };

  _proto.getMutationDefaults = function getMutationDefaults(mutationKey) {
    var _this$mutationDefault;

    return mutationKey ? (_this$mutationDefault = this.mutationDefaults.find(function (x) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_5__.partialMatchKey)(mutationKey, x.mutationKey);
    })) == null ? void 0 : _this$mutationDefault.defaultOptions : undefined;
  };

  _proto.defaultQueryOptions = function defaultQueryOptions(options) {
    if (options == null ? void 0 : options._defaulted) {
      return options;
    }

    var defaultedOptions = (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.defaultOptions.queries, this.getQueryDefaults(options == null ? void 0 : options.queryKey), options, {
      _defaulted: true
    });

    if (!defaultedOptions.queryHash && defaultedOptions.queryKey) {
      defaultedOptions.queryHash = (0,_utils__WEBPACK_IMPORTED_MODULE_5__.hashQueryKeyByOptions)(defaultedOptions.queryKey, defaultedOptions);
    }

    return defaultedOptions;
  };

  _proto.defaultQueryObserverOptions = function defaultQueryObserverOptions(options) {
    return this.defaultQueryOptions(options);
  };

  _proto.defaultMutationOptions = function defaultMutationOptions(options) {
    if (options == null ? void 0 : options._defaulted) {
      return options;
    }

    return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.defaultOptions.mutations, this.getMutationDefaults(options == null ? void 0 : options.mutationKey), options, {
      _defaulted: true
    });
  };

  _proto.clear = function clear() {
    this.queryCache.clear();
    this.mutationCache.clear();
  };

  return QueryClient;
}();

/***/ }),

/***/ "./node_modules/react-query/es/core/queryObserver.js":
/*!***********************************************************!*\
  !*** ./node_modules/react-query/es/core/queryObserver.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueryObserver": () => (/* binding */ QueryObserver)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _notifyManager__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _focusManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./focusManager */ "./node_modules/react-query/es/core/focusManager.js");
/* harmony import */ var _subscribable__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./subscribable */ "./node_modules/react-query/es/core/subscribable.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./logger */ "./node_modules/react-query/es/core/logger.js");
/* harmony import */ var _retryer__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./retryer */ "./node_modules/react-query/es/core/retryer.js");








var QueryObserver = /*#__PURE__*/function (_Subscribable) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__["default"])(QueryObserver, _Subscribable);

  function QueryObserver(client, options) {
    var _this;

    _this = _Subscribable.call(this) || this;
    _this.client = client;
    _this.options = options;
    _this.trackedProps = [];
    _this.selectError = null;

    _this.bindMethods();

    _this.setOptions(options);

    return _this;
  }

  var _proto = QueryObserver.prototype;

  _proto.bindMethods = function bindMethods() {
    this.remove = this.remove.bind(this);
    this.refetch = this.refetch.bind(this);
  };

  _proto.onSubscribe = function onSubscribe() {
    if (this.listeners.length === 1) {
      this.currentQuery.addObserver(this);

      if (shouldFetchOnMount(this.currentQuery, this.options)) {
        this.executeFetch();
      }

      this.updateTimers();
    }
  };

  _proto.onUnsubscribe = function onUnsubscribe() {
    if (!this.listeners.length) {
      this.destroy();
    }
  };

  _proto.shouldFetchOnReconnect = function shouldFetchOnReconnect() {
    return shouldFetchOn(this.currentQuery, this.options, this.options.refetchOnReconnect);
  };

  _proto.shouldFetchOnWindowFocus = function shouldFetchOnWindowFocus() {
    return shouldFetchOn(this.currentQuery, this.options, this.options.refetchOnWindowFocus);
  };

  _proto.destroy = function destroy() {
    this.listeners = [];
    this.clearTimers();
    this.currentQuery.removeObserver(this);
  };

  _proto.setOptions = function setOptions(options, notifyOptions) {
    var prevOptions = this.options;
    var prevQuery = this.currentQuery;
    this.options = this.client.defaultQueryObserverOptions(options);

    if (typeof this.options.enabled !== 'undefined' && typeof this.options.enabled !== 'boolean') {
      throw new Error('Expected enabled to be a boolean');
    } // Keep previous query key if the user does not supply one


    if (!this.options.queryKey) {
      this.options.queryKey = prevOptions.queryKey;
    }

    this.updateQuery();
    var mounted = this.hasListeners(); // Fetch if there are subscribers

    if (mounted && shouldFetchOptionally(this.currentQuery, prevQuery, this.options, prevOptions)) {
      this.executeFetch();
    } // Update result


    this.updateResult(notifyOptions); // Update stale interval if needed

    if (mounted && (this.currentQuery !== prevQuery || this.options.enabled !== prevOptions.enabled || this.options.staleTime !== prevOptions.staleTime)) {
      this.updateStaleTimeout();
    }

    var nextRefetchInterval = this.computeRefetchInterval(); // Update refetch interval if needed

    if (mounted && (this.currentQuery !== prevQuery || this.options.enabled !== prevOptions.enabled || nextRefetchInterval !== this.currentRefetchInterval)) {
      this.updateRefetchInterval(nextRefetchInterval);
    }
  };

  _proto.getOptimisticResult = function getOptimisticResult(options) {
    var defaultedOptions = this.client.defaultQueryObserverOptions(options);
    var query = this.client.getQueryCache().build(this.client, defaultedOptions);
    return this.createResult(query, defaultedOptions);
  };

  _proto.getCurrentResult = function getCurrentResult() {
    return this.currentResult;
  };

  _proto.trackResult = function trackResult(result, defaultedOptions) {
    var _this2 = this;

    var trackedResult = {};

    var trackProp = function trackProp(key) {
      if (!_this2.trackedProps.includes(key)) {
        _this2.trackedProps.push(key);
      }
    };

    Object.keys(result).forEach(function (key) {
      Object.defineProperty(trackedResult, key, {
        configurable: false,
        enumerable: true,
        get: function get() {
          trackProp(key);
          return result[key];
        }
      });
    });

    if (defaultedOptions.useErrorBoundary || defaultedOptions.suspense) {
      trackProp('error');
    }

    return trackedResult;
  };

  _proto.getNextResult = function getNextResult(options) {
    var _this3 = this;

    return new Promise(function (resolve, reject) {
      var unsubscribe = _this3.subscribe(function (result) {
        if (!result.isFetching) {
          unsubscribe();

          if (result.isError && (options == null ? void 0 : options.throwOnError)) {
            reject(result.error);
          } else {
            resolve(result);
          }
        }
      });
    });
  };

  _proto.getCurrentQuery = function getCurrentQuery() {
    return this.currentQuery;
  };

  _proto.remove = function remove() {
    this.client.getQueryCache().remove(this.currentQuery);
  };

  _proto.refetch = function refetch(options) {
    return this.fetch((0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options, {
      meta: {
        refetchPage: options == null ? void 0 : options.refetchPage
      }
    }));
  };

  _proto.fetchOptimistic = function fetchOptimistic(options) {
    var _this4 = this;

    var defaultedOptions = this.client.defaultQueryObserverOptions(options);
    var query = this.client.getQueryCache().build(this.client, defaultedOptions);
    return query.fetch().then(function () {
      return _this4.createResult(query, defaultedOptions);
    });
  };

  _proto.fetch = function fetch(fetchOptions) {
    var _this5 = this;

    return this.executeFetch(fetchOptions).then(function () {
      _this5.updateResult();

      return _this5.currentResult;
    });
  };

  _proto.executeFetch = function executeFetch(fetchOptions) {
    // Make sure we reference the latest query as the current one might have been removed
    this.updateQuery(); // Fetch

    var promise = this.currentQuery.fetch(this.options, fetchOptions);

    if (!(fetchOptions == null ? void 0 : fetchOptions.throwOnError)) {
      promise = promise.catch(_utils__WEBPACK_IMPORTED_MODULE_2__.noop);
    }

    return promise;
  };

  _proto.updateStaleTimeout = function updateStaleTimeout() {
    var _this6 = this;

    this.clearStaleTimeout();

    if (_utils__WEBPACK_IMPORTED_MODULE_2__.isServer || this.currentResult.isStale || !(0,_utils__WEBPACK_IMPORTED_MODULE_2__.isValidTimeout)(this.options.staleTime)) {
      return;
    }

    var time = (0,_utils__WEBPACK_IMPORTED_MODULE_2__.timeUntilStale)(this.currentResult.dataUpdatedAt, this.options.staleTime); // The timeout is sometimes triggered 1 ms before the stale time expiration.
    // To mitigate this issue we always add 1 ms to the timeout.

    var timeout = time + 1;
    this.staleTimeoutId = setTimeout(function () {
      if (!_this6.currentResult.isStale) {
        _this6.updateResult();
      }
    }, timeout);
  };

  _proto.computeRefetchInterval = function computeRefetchInterval() {
    var _this$options$refetch;

    return typeof this.options.refetchInterval === 'function' ? this.options.refetchInterval(this.currentResult.data, this.currentQuery) : (_this$options$refetch = this.options.refetchInterval) != null ? _this$options$refetch : false;
  };

  _proto.updateRefetchInterval = function updateRefetchInterval(nextInterval) {
    var _this7 = this;

    this.clearRefetchInterval();
    this.currentRefetchInterval = nextInterval;

    if (_utils__WEBPACK_IMPORTED_MODULE_2__.isServer || this.options.enabled === false || !(0,_utils__WEBPACK_IMPORTED_MODULE_2__.isValidTimeout)(this.currentRefetchInterval) || this.currentRefetchInterval === 0) {
      return;
    }

    this.refetchIntervalId = setInterval(function () {
      if (_this7.options.refetchIntervalInBackground || _focusManager__WEBPACK_IMPORTED_MODULE_3__.focusManager.isFocused()) {
        _this7.executeFetch();
      }
    }, this.currentRefetchInterval);
  };

  _proto.updateTimers = function updateTimers() {
    this.updateStaleTimeout();
    this.updateRefetchInterval(this.computeRefetchInterval());
  };

  _proto.clearTimers = function clearTimers() {
    this.clearStaleTimeout();
    this.clearRefetchInterval();
  };

  _proto.clearStaleTimeout = function clearStaleTimeout() {
    if (this.staleTimeoutId) {
      clearTimeout(this.staleTimeoutId);
      this.staleTimeoutId = undefined;
    }
  };

  _proto.clearRefetchInterval = function clearRefetchInterval() {
    if (this.refetchIntervalId) {
      clearInterval(this.refetchIntervalId);
      this.refetchIntervalId = undefined;
    }
  };

  _proto.createResult = function createResult(query, options) {
    var prevQuery = this.currentQuery;
    var prevOptions = this.options;
    var prevResult = this.currentResult;
    var prevResultState = this.currentResultState;
    var prevResultOptions = this.currentResultOptions;
    var queryChange = query !== prevQuery;
    var queryInitialState = queryChange ? query.state : this.currentQueryInitialState;
    var prevQueryResult = queryChange ? this.currentResult : this.previousQueryResult;
    var state = query.state;
    var dataUpdatedAt = state.dataUpdatedAt,
        error = state.error,
        errorUpdatedAt = state.errorUpdatedAt,
        isFetching = state.isFetching,
        status = state.status;
    var isPreviousData = false;
    var isPlaceholderData = false;
    var data; // Optimistically set result in fetching state if needed

    if (options.optimisticResults) {
      var mounted = this.hasListeners();
      var fetchOnMount = !mounted && shouldFetchOnMount(query, options);
      var fetchOptionally = mounted && shouldFetchOptionally(query, prevQuery, options, prevOptions);

      if (fetchOnMount || fetchOptionally) {
        isFetching = true;

        if (!dataUpdatedAt) {
          status = 'loading';
        }
      }
    } // Keep previous data if needed


    if (options.keepPreviousData && !state.dataUpdateCount && (prevQueryResult == null ? void 0 : prevQueryResult.isSuccess) && status !== 'error') {
      data = prevQueryResult.data;
      dataUpdatedAt = prevQueryResult.dataUpdatedAt;
      status = prevQueryResult.status;
      isPreviousData = true;
    } // Select data if needed
    else if (options.select && typeof state.data !== 'undefined') {
        // Memoize select result
        if (prevResult && state.data === (prevResultState == null ? void 0 : prevResultState.data) && options.select === this.selectFn) {
          data = this.selectResult;
        } else {
          try {
            this.selectFn = options.select;
            data = options.select(state.data);

            if (options.structuralSharing !== false) {
              data = (0,_utils__WEBPACK_IMPORTED_MODULE_2__.replaceEqualDeep)(prevResult == null ? void 0 : prevResult.data, data);
            }

            this.selectResult = data;
            this.selectError = null;
          } catch (selectError) {
            (0,_logger__WEBPACK_IMPORTED_MODULE_4__.getLogger)().error(selectError);
            this.selectError = selectError;
          }
        }
      } // Use query data
      else {
          data = state.data;
        } // Show placeholder data if needed


    if (typeof options.placeholderData !== 'undefined' && typeof data === 'undefined' && (status === 'loading' || status === 'idle')) {
      var placeholderData; // Memoize placeholder data

      if ((prevResult == null ? void 0 : prevResult.isPlaceholderData) && options.placeholderData === (prevResultOptions == null ? void 0 : prevResultOptions.placeholderData)) {
        placeholderData = prevResult.data;
      } else {
        placeholderData = typeof options.placeholderData === 'function' ? options.placeholderData() : options.placeholderData;

        if (options.select && typeof placeholderData !== 'undefined') {
          try {
            placeholderData = options.select(placeholderData);

            if (options.structuralSharing !== false) {
              placeholderData = (0,_utils__WEBPACK_IMPORTED_MODULE_2__.replaceEqualDeep)(prevResult == null ? void 0 : prevResult.data, placeholderData);
            }

            this.selectError = null;
          } catch (selectError) {
            (0,_logger__WEBPACK_IMPORTED_MODULE_4__.getLogger)().error(selectError);
            this.selectError = selectError;
          }
        }
      }

      if (typeof placeholderData !== 'undefined') {
        status = 'success';
        data = placeholderData;
        isPlaceholderData = true;
      }
    }

    if (this.selectError) {
      error = this.selectError;
      data = this.selectResult;
      errorUpdatedAt = Date.now();
      status = 'error';
    }

    var result = {
      status: status,
      isLoading: status === 'loading',
      isSuccess: status === 'success',
      isError: status === 'error',
      isIdle: status === 'idle',
      data: data,
      dataUpdatedAt: dataUpdatedAt,
      error: error,
      errorUpdatedAt: errorUpdatedAt,
      failureCount: state.fetchFailureCount,
      errorUpdateCount: state.errorUpdateCount,
      isFetched: state.dataUpdateCount > 0 || state.errorUpdateCount > 0,
      isFetchedAfterMount: state.dataUpdateCount > queryInitialState.dataUpdateCount || state.errorUpdateCount > queryInitialState.errorUpdateCount,
      isFetching: isFetching,
      isRefetching: isFetching && status !== 'loading',
      isLoadingError: status === 'error' && state.dataUpdatedAt === 0,
      isPlaceholderData: isPlaceholderData,
      isPreviousData: isPreviousData,
      isRefetchError: status === 'error' && state.dataUpdatedAt !== 0,
      isStale: isStale(query, options),
      refetch: this.refetch,
      remove: this.remove
    };
    return result;
  };

  _proto.shouldNotifyListeners = function shouldNotifyListeners(result, prevResult) {
    if (!prevResult) {
      return true;
    }

    var _this$options = this.options,
        notifyOnChangeProps = _this$options.notifyOnChangeProps,
        notifyOnChangePropsExclusions = _this$options.notifyOnChangePropsExclusions;

    if (!notifyOnChangeProps && !notifyOnChangePropsExclusions) {
      return true;
    }

    if (notifyOnChangeProps === 'tracked' && !this.trackedProps.length) {
      return true;
    }

    var includedProps = notifyOnChangeProps === 'tracked' ? this.trackedProps : notifyOnChangeProps;
    return Object.keys(result).some(function (key) {
      var typedKey = key;
      var changed = result[typedKey] !== prevResult[typedKey];
      var isIncluded = includedProps == null ? void 0 : includedProps.some(function (x) {
        return x === key;
      });
      var isExcluded = notifyOnChangePropsExclusions == null ? void 0 : notifyOnChangePropsExclusions.some(function (x) {
        return x === key;
      });
      return changed && !isExcluded && (!includedProps || isIncluded);
    });
  };

  _proto.updateResult = function updateResult(notifyOptions) {
    var prevResult = this.currentResult;
    this.currentResult = this.createResult(this.currentQuery, this.options);
    this.currentResultState = this.currentQuery.state;
    this.currentResultOptions = this.options; // Only notify if something has changed

    if ((0,_utils__WEBPACK_IMPORTED_MODULE_2__.shallowEqualObjects)(this.currentResult, prevResult)) {
      return;
    } // Determine which callbacks to trigger


    var defaultNotifyOptions = {
      cache: true
    };

    if ((notifyOptions == null ? void 0 : notifyOptions.listeners) !== false && this.shouldNotifyListeners(this.currentResult, prevResult)) {
      defaultNotifyOptions.listeners = true;
    }

    this.notify((0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, defaultNotifyOptions, notifyOptions));
  };

  _proto.updateQuery = function updateQuery() {
    var query = this.client.getQueryCache().build(this.client, this.options);

    if (query === this.currentQuery) {
      return;
    }

    var prevQuery = this.currentQuery;
    this.currentQuery = query;
    this.currentQueryInitialState = query.state;
    this.previousQueryResult = this.currentResult;

    if (this.hasListeners()) {
      prevQuery == null ? void 0 : prevQuery.removeObserver(this);
      query.addObserver(this);
    }
  };

  _proto.onQueryUpdate = function onQueryUpdate(action) {
    var notifyOptions = {};

    if (action.type === 'success') {
      notifyOptions.onSuccess = true;
    } else if (action.type === 'error' && !(0,_retryer__WEBPACK_IMPORTED_MODULE_5__.isCancelledError)(action.error)) {
      notifyOptions.onError = true;
    }

    this.updateResult(notifyOptions);

    if (this.hasListeners()) {
      this.updateTimers();
    }
  };

  _proto.notify = function notify(notifyOptions) {
    var _this8 = this;

    _notifyManager__WEBPACK_IMPORTED_MODULE_6__.notifyManager.batch(function () {
      // First trigger the configuration callbacks
      if (notifyOptions.onSuccess) {
        _this8.options.onSuccess == null ? void 0 : _this8.options.onSuccess(_this8.currentResult.data);
        _this8.options.onSettled == null ? void 0 : _this8.options.onSettled(_this8.currentResult.data, null);
      } else if (notifyOptions.onError) {
        _this8.options.onError == null ? void 0 : _this8.options.onError(_this8.currentResult.error);
        _this8.options.onSettled == null ? void 0 : _this8.options.onSettled(undefined, _this8.currentResult.error);
      } // Then trigger the listeners


      if (notifyOptions.listeners) {
        _this8.listeners.forEach(function (listener) {
          listener(_this8.currentResult);
        });
      } // Then the cache listeners


      if (notifyOptions.cache) {
        _this8.client.getQueryCache().notify({
          query: _this8.currentQuery,
          type: 'observerResultsUpdated'
        });
      }
    });
  };

  return QueryObserver;
}(_subscribable__WEBPACK_IMPORTED_MODULE_7__.Subscribable);

function shouldLoadOnMount(query, options) {
  return options.enabled !== false && !query.state.dataUpdatedAt && !(query.state.status === 'error' && options.retryOnMount === false);
}

function shouldFetchOnMount(query, options) {
  return shouldLoadOnMount(query, options) || query.state.dataUpdatedAt > 0 && shouldFetchOn(query, options, options.refetchOnMount);
}

function shouldFetchOn(query, options, field) {
  if (options.enabled !== false) {
    var value = typeof field === 'function' ? field(query) : field;
    return value === 'always' || value !== false && isStale(query, options);
  }

  return false;
}

function shouldFetchOptionally(query, prevQuery, options, prevOptions) {
  return options.enabled !== false && (query !== prevQuery || prevOptions.enabled === false) && (!options.suspense || query.state.status !== 'error') && isStale(query, options);
}

function isStale(query, options) {
  return query.isStaleByTime(options.staleTime);
}

/***/ }),

/***/ "./node_modules/react-query/es/core/retryer.js":
/*!*****************************************************!*\
  !*** ./node_modules/react-query/es/core/retryer.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "CancelledError": () => (/* binding */ CancelledError),
/* harmony export */   "Retryer": () => (/* binding */ Retryer),
/* harmony export */   "isCancelable": () => (/* binding */ isCancelable),
/* harmony export */   "isCancelledError": () => (/* binding */ isCancelledError)
/* harmony export */ });
/* harmony import */ var _focusManager__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./focusManager */ "./node_modules/react-query/es/core/focusManager.js");
/* harmony import */ var _onlineManager__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./onlineManager */ "./node_modules/react-query/es/core/onlineManager.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/core/utils.js");




function defaultRetryDelay(failureCount) {
  return Math.min(1000 * Math.pow(2, failureCount), 30000);
}

function isCancelable(value) {
  return typeof (value == null ? void 0 : value.cancel) === 'function';
}
var CancelledError = function CancelledError(options) {
  this.revert = options == null ? void 0 : options.revert;
  this.silent = options == null ? void 0 : options.silent;
};
function isCancelledError(value) {
  return value instanceof CancelledError;
} // CLASS

var Retryer = function Retryer(config) {
  var _this = this;

  var cancelRetry = false;
  var cancelFn;
  var continueFn;
  var promiseResolve;
  var promiseReject;
  this.abort = config.abort;

  this.cancel = function (cancelOptions) {
    return cancelFn == null ? void 0 : cancelFn(cancelOptions);
  };

  this.cancelRetry = function () {
    cancelRetry = true;
  };

  this.continueRetry = function () {
    cancelRetry = false;
  };

  this.continue = function () {
    return continueFn == null ? void 0 : continueFn();
  };

  this.failureCount = 0;
  this.isPaused = false;
  this.isResolved = false;
  this.isTransportCancelable = false;
  this.promise = new Promise(function (outerResolve, outerReject) {
    promiseResolve = outerResolve;
    promiseReject = outerReject;
  });

  var resolve = function resolve(value) {
    if (!_this.isResolved) {
      _this.isResolved = true;
      config.onSuccess == null ? void 0 : config.onSuccess(value);
      continueFn == null ? void 0 : continueFn();
      promiseResolve(value);
    }
  };

  var reject = function reject(value) {
    if (!_this.isResolved) {
      _this.isResolved = true;
      config.onError == null ? void 0 : config.onError(value);
      continueFn == null ? void 0 : continueFn();
      promiseReject(value);
    }
  };

  var pause = function pause() {
    return new Promise(function (continueResolve) {
      continueFn = continueResolve;
      _this.isPaused = true;
      config.onPause == null ? void 0 : config.onPause();
    }).then(function () {
      continueFn = undefined;
      _this.isPaused = false;
      config.onContinue == null ? void 0 : config.onContinue();
    });
  }; // Create loop function


  var run = function run() {
    // Do nothing if already resolved
    if (_this.isResolved) {
      return;
    }

    var promiseOrValue; // Execute query

    try {
      promiseOrValue = config.fn();
    } catch (error) {
      promiseOrValue = Promise.reject(error);
    } // Create callback to cancel this fetch


    cancelFn = function cancelFn(cancelOptions) {
      if (!_this.isResolved) {
        reject(new CancelledError(cancelOptions));
        _this.abort == null ? void 0 : _this.abort(); // Cancel transport if supported

        if (isCancelable(promiseOrValue)) {
          try {
            promiseOrValue.cancel();
          } catch (_unused) {}
        }
      }
    }; // Check if the transport layer support cancellation


    _this.isTransportCancelable = isCancelable(promiseOrValue);
    Promise.resolve(promiseOrValue).then(resolve).catch(function (error) {
      var _config$retry, _config$retryDelay;

      // Stop if the fetch is already resolved
      if (_this.isResolved) {
        return;
      } // Do we need to retry the request?


      var retry = (_config$retry = config.retry) != null ? _config$retry : 3;
      var retryDelay = (_config$retryDelay = config.retryDelay) != null ? _config$retryDelay : defaultRetryDelay;
      var delay = typeof retryDelay === 'function' ? retryDelay(_this.failureCount, error) : retryDelay;
      var shouldRetry = retry === true || typeof retry === 'number' && _this.failureCount < retry || typeof retry === 'function' && retry(_this.failureCount, error);

      if (cancelRetry || !shouldRetry) {
        // We are done if the query does not need to be retried
        reject(error);
        return;
      }

      _this.failureCount++; // Notify on fail

      config.onFail == null ? void 0 : config.onFail(_this.failureCount, error); // Delay

      (0,_utils__WEBPACK_IMPORTED_MODULE_0__.sleep)(delay) // Pause if the document is not visible or when the device is offline
      .then(function () {
        if (!_focusManager__WEBPACK_IMPORTED_MODULE_1__.focusManager.isFocused() || !_onlineManager__WEBPACK_IMPORTED_MODULE_2__.onlineManager.isOnline()) {
          return pause();
        }
      }).then(function () {
        if (cancelRetry) {
          reject(error);
        } else {
          run();
        }
      });
    });
  }; // Start loop


  run();
};

/***/ }),

/***/ "./node_modules/react-query/es/core/subscribable.js":
/*!**********************************************************!*\
  !*** ./node_modules/react-query/es/core/subscribable.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Subscribable": () => (/* binding */ Subscribable)
/* harmony export */ });
var Subscribable = /*#__PURE__*/function () {
  function Subscribable() {
    this.listeners = [];
  }

  var _proto = Subscribable.prototype;

  _proto.subscribe = function subscribe(listener) {
    var _this = this;

    var callback = listener || function () {
      return undefined;
    };

    this.listeners.push(callback);
    this.onSubscribe();
    return function () {
      _this.listeners = _this.listeners.filter(function (x) {
        return x !== callback;
      });

      _this.onUnsubscribe();
    };
  };

  _proto.hasListeners = function hasListeners() {
    return this.listeners.length > 0;
  };

  _proto.onSubscribe = function onSubscribe() {// Do nothing
  };

  _proto.onUnsubscribe = function onUnsubscribe() {// Do nothing
  };

  return Subscribable;
}();

/***/ }),

/***/ "./node_modules/react-query/es/core/types.js":
/*!***************************************************!*\
  !*** ./node_modules/react-query/es/core/types.js ***!
  \***************************************************/
/***/ (() => {



/***/ }),

/***/ "./node_modules/react-query/es/core/utils.js":
/*!***************************************************!*\
  !*** ./node_modules/react-query/es/core/utils.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "difference": () => (/* binding */ difference),
/* harmony export */   "ensureQueryKeyArray": () => (/* binding */ ensureQueryKeyArray),
/* harmony export */   "functionalUpdate": () => (/* binding */ functionalUpdate),
/* harmony export */   "getAbortController": () => (/* binding */ getAbortController),
/* harmony export */   "hashQueryKey": () => (/* binding */ hashQueryKey),
/* harmony export */   "hashQueryKeyByOptions": () => (/* binding */ hashQueryKeyByOptions),
/* harmony export */   "isError": () => (/* binding */ isError),
/* harmony export */   "isPlainObject": () => (/* binding */ isPlainObject),
/* harmony export */   "isQueryKey": () => (/* binding */ isQueryKey),
/* harmony export */   "isServer": () => (/* binding */ isServer),
/* harmony export */   "isValidTimeout": () => (/* binding */ isValidTimeout),
/* harmony export */   "mapQueryStatusFilter": () => (/* binding */ mapQueryStatusFilter),
/* harmony export */   "matchMutation": () => (/* binding */ matchMutation),
/* harmony export */   "matchQuery": () => (/* binding */ matchQuery),
/* harmony export */   "noop": () => (/* binding */ noop),
/* harmony export */   "parseFilterArgs": () => (/* binding */ parseFilterArgs),
/* harmony export */   "parseMutationArgs": () => (/* binding */ parseMutationArgs),
/* harmony export */   "parseMutationFilterArgs": () => (/* binding */ parseMutationFilterArgs),
/* harmony export */   "parseQueryArgs": () => (/* binding */ parseQueryArgs),
/* harmony export */   "partialDeepEqual": () => (/* binding */ partialDeepEqual),
/* harmony export */   "partialMatchKey": () => (/* binding */ partialMatchKey),
/* harmony export */   "replaceAt": () => (/* binding */ replaceAt),
/* harmony export */   "replaceEqualDeep": () => (/* binding */ replaceEqualDeep),
/* harmony export */   "scheduleMicrotask": () => (/* binding */ scheduleMicrotask),
/* harmony export */   "shallowEqualObjects": () => (/* binding */ shallowEqualObjects),
/* harmony export */   "sleep": () => (/* binding */ sleep),
/* harmony export */   "stableValueHash": () => (/* binding */ stableValueHash),
/* harmony export */   "timeUntilStale": () => (/* binding */ timeUntilStale)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");

// TYPES
// UTILS
var isServer = typeof window === 'undefined';
function noop() {
  return undefined;
}
function functionalUpdate(updater, input) {
  return typeof updater === 'function' ? updater(input) : updater;
}
function isValidTimeout(value) {
  return typeof value === 'number' && value >= 0 && value !== Infinity;
}
function ensureQueryKeyArray(value) {
  return Array.isArray(value) ? value : [value];
}
function difference(array1, array2) {
  return array1.filter(function (x) {
    return array2.indexOf(x) === -1;
  });
}
function replaceAt(array, index, value) {
  var copy = array.slice(0);
  copy[index] = value;
  return copy;
}
function timeUntilStale(updatedAt, staleTime) {
  return Math.max(updatedAt + (staleTime || 0) - Date.now(), 0);
}
function parseQueryArgs(arg1, arg2, arg3) {
  if (!isQueryKey(arg1)) {
    return arg1;
  }

  if (typeof arg2 === 'function') {
    return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg3, {
      queryKey: arg1,
      queryFn: arg2
    });
  }

  return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg2, {
    queryKey: arg1
  });
}
function parseMutationArgs(arg1, arg2, arg3) {
  if (isQueryKey(arg1)) {
    if (typeof arg2 === 'function') {
      return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg3, {
        mutationKey: arg1,
        mutationFn: arg2
      });
    }

    return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg2, {
      mutationKey: arg1
    });
  }

  if (typeof arg1 === 'function') {
    return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg2, {
      mutationFn: arg1
    });
  }

  return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg1);
}
function parseFilterArgs(arg1, arg2, arg3) {
  return isQueryKey(arg1) ? [(0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg2, {
    queryKey: arg1
  }), arg3] : [arg1 || {}, arg2];
}
function parseMutationFilterArgs(arg1, arg2) {
  return isQueryKey(arg1) ? (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, arg2, {
    mutationKey: arg1
  }) : arg1;
}
function mapQueryStatusFilter(active, inactive) {
  if (active === true && inactive === true || active == null && inactive == null) {
    return 'all';
  } else if (active === false && inactive === false) {
    return 'none';
  } else {
    // At this point, active|inactive can only be true|false or false|true
    // so, when only one value is provided, the missing one has to be the negated value
    var isActive = active != null ? active : !inactive;
    return isActive ? 'active' : 'inactive';
  }
}
function matchQuery(filters, query) {
  var active = filters.active,
      exact = filters.exact,
      fetching = filters.fetching,
      inactive = filters.inactive,
      predicate = filters.predicate,
      queryKey = filters.queryKey,
      stale = filters.stale;

  if (isQueryKey(queryKey)) {
    if (exact) {
      if (query.queryHash !== hashQueryKeyByOptions(queryKey, query.options)) {
        return false;
      }
    } else if (!partialMatchKey(query.queryKey, queryKey)) {
      return false;
    }
  }

  var queryStatusFilter = mapQueryStatusFilter(active, inactive);

  if (queryStatusFilter === 'none') {
    return false;
  } else if (queryStatusFilter !== 'all') {
    var isActive = query.isActive();

    if (queryStatusFilter === 'active' && !isActive) {
      return false;
    }

    if (queryStatusFilter === 'inactive' && isActive) {
      return false;
    }
  }

  if (typeof stale === 'boolean' && query.isStale() !== stale) {
    return false;
  }

  if (typeof fetching === 'boolean' && query.isFetching() !== fetching) {
    return false;
  }

  if (predicate && !predicate(query)) {
    return false;
  }

  return true;
}
function matchMutation(filters, mutation) {
  var exact = filters.exact,
      fetching = filters.fetching,
      predicate = filters.predicate,
      mutationKey = filters.mutationKey;

  if (isQueryKey(mutationKey)) {
    if (!mutation.options.mutationKey) {
      return false;
    }

    if (exact) {
      if (hashQueryKey(mutation.options.mutationKey) !== hashQueryKey(mutationKey)) {
        return false;
      }
    } else if (!partialMatchKey(mutation.options.mutationKey, mutationKey)) {
      return false;
    }
  }

  if (typeof fetching === 'boolean' && mutation.state.status === 'loading' !== fetching) {
    return false;
  }

  if (predicate && !predicate(mutation)) {
    return false;
  }

  return true;
}
function hashQueryKeyByOptions(queryKey, options) {
  var hashFn = (options == null ? void 0 : options.queryKeyHashFn) || hashQueryKey;
  return hashFn(queryKey);
}
/**
 * Default query keys hash function.
 */

function hashQueryKey(queryKey) {
  var asArray = ensureQueryKeyArray(queryKey);
  return stableValueHash(asArray);
}
/**
 * Hashes the value into a stable hash.
 */

function stableValueHash(value) {
  return JSON.stringify(value, function (_, val) {
    return isPlainObject(val) ? Object.keys(val).sort().reduce(function (result, key) {
      result[key] = val[key];
      return result;
    }, {}) : val;
  });
}
/**
 * Checks if key `b` partially matches with key `a`.
 */

function partialMatchKey(a, b) {
  return partialDeepEqual(ensureQueryKeyArray(a), ensureQueryKeyArray(b));
}
/**
 * Checks if `b` partially matches with `a`.
 */

function partialDeepEqual(a, b) {
  if (a === b) {
    return true;
  }

  if (typeof a !== typeof b) {
    return false;
  }

  if (a && b && typeof a === 'object' && typeof b === 'object') {
    return !Object.keys(b).some(function (key) {
      return !partialDeepEqual(a[key], b[key]);
    });
  }

  return false;
}
/**
 * This function returns `a` if `b` is deeply equal.
 * If not, it will replace any deeply equal children of `b` with those of `a`.
 * This can be used for structural sharing between JSON values for example.
 */

function replaceEqualDeep(a, b) {
  if (a === b) {
    return a;
  }

  var array = Array.isArray(a) && Array.isArray(b);

  if (array || isPlainObject(a) && isPlainObject(b)) {
    var aSize = array ? a.length : Object.keys(a).length;
    var bItems = array ? b : Object.keys(b);
    var bSize = bItems.length;
    var copy = array ? [] : {};
    var equalItems = 0;

    for (var i = 0; i < bSize; i++) {
      var key = array ? i : bItems[i];
      copy[key] = replaceEqualDeep(a[key], b[key]);

      if (copy[key] === a[key]) {
        equalItems++;
      }
    }

    return aSize === bSize && equalItems === aSize ? a : copy;
  }

  return b;
}
/**
 * Shallow compare objects. Only works with objects that always have the same properties.
 */

function shallowEqualObjects(a, b) {
  if (a && !b || b && !a) {
    return false;
  }

  for (var key in a) {
    if (a[key] !== b[key]) {
      return false;
    }
  }

  return true;
} // Copied from: https://github.com/jonschlinkert/is-plain-object

function isPlainObject(o) {
  if (!hasObjectPrototype(o)) {
    return false;
  } // If has modified constructor


  var ctor = o.constructor;

  if (typeof ctor === 'undefined') {
    return true;
  } // If has modified prototype


  var prot = ctor.prototype;

  if (!hasObjectPrototype(prot)) {
    return false;
  } // If constructor does not have an Object-specific method


  if (!prot.hasOwnProperty('isPrototypeOf')) {
    return false;
  } // Most likely a plain Object


  return true;
}

function hasObjectPrototype(o) {
  return Object.prototype.toString.call(o) === '[object Object]';
}

function isQueryKey(value) {
  return typeof value === 'string' || Array.isArray(value);
}
function isError(value) {
  return value instanceof Error;
}
function sleep(timeout) {
  return new Promise(function (resolve) {
    setTimeout(resolve, timeout);
  });
}
/**
 * Schedules a microtask.
 * This can be useful to schedule state updates after rendering.
 */

function scheduleMicrotask(callback) {
  Promise.resolve().then(callback).catch(function (error) {
    return setTimeout(function () {
      throw error;
    });
  });
}
function getAbortController() {
  if (typeof AbortController === 'function') {
    return new AbortController();
  }
}

/***/ }),

/***/ "./node_modules/react-query/es/index.js":
/*!**********************************************!*\
  !*** ./node_modules/react-query/es/index.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./core */ "./node_modules/react-query/es/core/index.js");
/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};
/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _core__WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== "default") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _core__WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]
/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);
/* harmony import */ var _react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./react */ "./node_modules/react-query/es/react/index.js");
/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};
/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _react__WEBPACK_IMPORTED_MODULE_1__) if(["default","CancelledError","QueryCache","QueryClient","QueryObserver","QueriesObserver","InfiniteQueryObserver","MutationCache","MutationObserver","setLogger","notifyManager","focusManager","onlineManager","hashQueryKey","isError","isCancelledError","dehydrate","hydrate"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _react__WEBPACK_IMPORTED_MODULE_1__[__WEBPACK_IMPORT_KEY__]
/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);



/***/ }),

/***/ "./node_modules/react-query/es/react/Hydrate.js":
/*!******************************************************!*\
  !*** ./node_modules/react-query/es/react/Hydrate.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Hydrate": () => (/* binding */ Hydrate),
/* harmony export */   "useHydrate": () => (/* binding */ useHydrate)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _core__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core */ "./node_modules/react-query/es/core/hydration.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");



function useHydrate(state, options) {
  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__.useQueryClient)();
  var optionsRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(options);
  optionsRef.current = options; // Running hydrate again with the same queries is safe,
  // it wont overwrite or initialize existing queries,
  // relying on useMemo here is only a performance optimization.
  // hydrate can and should be run *during* render here for SSR to work properly

  react__WEBPACK_IMPORTED_MODULE_0___default().useMemo(function () {
    if (state) {
      (0,_core__WEBPACK_IMPORTED_MODULE_2__.hydrate)(queryClient, state, optionsRef.current);
    }
  }, [queryClient, state]);
}
var Hydrate = function Hydrate(_ref) {
  var children = _ref.children,
      options = _ref.options,
      state = _ref.state;
  useHydrate(state, options);
  return children;
};

/***/ }),

/***/ "./node_modules/react-query/es/react/QueryClientProvider.js":
/*!******************************************************************!*\
  !*** ./node_modules/react-query/es/react/QueryClientProvider.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueryClientProvider": () => (/* binding */ QueryClientProvider),
/* harmony export */   "useQueryClient": () => (/* binding */ useQueryClient)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

var defaultContext = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createContext(undefined);
var QueryClientSharingContext = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createContext(false); // if contextSharing is on, we share the first and at least one
// instance of the context across the window
// to ensure that if React Query is used across
// different bundles or microfrontends they will
// all use the same **instance** of context, regardless
// of module scoping.

function getQueryClientContext(contextSharing) {
  if (contextSharing && typeof window !== 'undefined') {
    if (!window.ReactQueryClientContext) {
      window.ReactQueryClientContext = defaultContext;
    }

    return window.ReactQueryClientContext;
  }

  return defaultContext;
}

var useQueryClient = function useQueryClient() {
  var queryClient = react__WEBPACK_IMPORTED_MODULE_0___default().useContext(getQueryClientContext(react__WEBPACK_IMPORTED_MODULE_0___default().useContext(QueryClientSharingContext)));

  if (!queryClient) {
    throw new Error('No QueryClient set, use QueryClientProvider to set one');
  }

  return queryClient;
};
var QueryClientProvider = function QueryClientProvider(_ref) {
  var client = _ref.client,
      _ref$contextSharing = _ref.contextSharing,
      contextSharing = _ref$contextSharing === void 0 ? false : _ref$contextSharing,
      children = _ref.children;
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    client.mount();
    return function () {
      client.unmount();
    };
  }, [client]);
  var Context = getQueryClientContext(contextSharing);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(QueryClientSharingContext.Provider, {
    value: contextSharing
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(Context.Provider, {
    value: client
  }, children));
};

/***/ }),

/***/ "./node_modules/react-query/es/react/QueryErrorResetBoundary.js":
/*!**********************************************************************!*\
  !*** ./node_modules/react-query/es/react/QueryErrorResetBoundary.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "QueryErrorResetBoundary": () => (/* binding */ QueryErrorResetBoundary),
/* harmony export */   "useQueryErrorResetBoundary": () => (/* binding */ useQueryErrorResetBoundary)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
 // CONTEXT

function createValue() {
  var _isReset = false;
  return {
    clearReset: function clearReset() {
      _isReset = false;
    },
    reset: function reset() {
      _isReset = true;
    },
    isReset: function isReset() {
      return _isReset;
    }
  };
}

var QueryErrorResetBoundaryContext = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createContext(createValue()); // HOOK

var useQueryErrorResetBoundary = function useQueryErrorResetBoundary() {
  return react__WEBPACK_IMPORTED_MODULE_0___default().useContext(QueryErrorResetBoundaryContext);
}; // COMPONENT

var QueryErrorResetBoundary = function QueryErrorResetBoundary(_ref) {
  var children = _ref.children;
  var value = react__WEBPACK_IMPORTED_MODULE_0___default().useMemo(function () {
    return createValue();
  }, []);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(QueryErrorResetBoundaryContext.Provider, {
    value: value
  }, typeof children === 'function' ? children(value) : children);
};

/***/ }),

/***/ "./node_modules/react-query/es/react/index.js":
/*!****************************************************!*\
  !*** ./node_modules/react-query/es/react/index.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Hydrate": () => (/* reexport safe */ _Hydrate__WEBPACK_IMPORTED_MODULE_10__.Hydrate),
/* harmony export */   "QueryClientProvider": () => (/* reexport safe */ _QueryClientProvider__WEBPACK_IMPORTED_MODULE_2__.QueryClientProvider),
/* harmony export */   "QueryErrorResetBoundary": () => (/* reexport safe */ _QueryErrorResetBoundary__WEBPACK_IMPORTED_MODULE_3__.QueryErrorResetBoundary),
/* harmony export */   "useHydrate": () => (/* reexport safe */ _Hydrate__WEBPACK_IMPORTED_MODULE_10__.useHydrate),
/* harmony export */   "useInfiniteQuery": () => (/* reexport safe */ _useInfiniteQuery__WEBPACK_IMPORTED_MODULE_9__.useInfiniteQuery),
/* harmony export */   "useIsFetching": () => (/* reexport safe */ _useIsFetching__WEBPACK_IMPORTED_MODULE_4__.useIsFetching),
/* harmony export */   "useIsMutating": () => (/* reexport safe */ _useIsMutating__WEBPACK_IMPORTED_MODULE_5__.useIsMutating),
/* harmony export */   "useMutation": () => (/* reexport safe */ _useMutation__WEBPACK_IMPORTED_MODULE_6__.useMutation),
/* harmony export */   "useQueries": () => (/* reexport safe */ _useQueries__WEBPACK_IMPORTED_MODULE_8__.useQueries),
/* harmony export */   "useQuery": () => (/* reexport safe */ _useQuery__WEBPACK_IMPORTED_MODULE_7__.useQuery),
/* harmony export */   "useQueryClient": () => (/* reexport safe */ _QueryClientProvider__WEBPACK_IMPORTED_MODULE_2__.useQueryClient),
/* harmony export */   "useQueryErrorResetBoundary": () => (/* reexport safe */ _QueryErrorResetBoundary__WEBPACK_IMPORTED_MODULE_3__.useQueryErrorResetBoundary)
/* harmony export */ });
/* harmony import */ var _setBatchUpdatesFn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setBatchUpdatesFn */ "./node_modules/react-query/es/react/setBatchUpdatesFn.js");
/* harmony import */ var _setLogger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./setLogger */ "./node_modules/react-query/es/react/setLogger.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");
/* harmony import */ var _QueryErrorResetBoundary__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./QueryErrorResetBoundary */ "./node_modules/react-query/es/react/QueryErrorResetBoundary.js");
/* harmony import */ var _useIsFetching__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./useIsFetching */ "./node_modules/react-query/es/react/useIsFetching.js");
/* harmony import */ var _useIsMutating__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./useIsMutating */ "./node_modules/react-query/es/react/useIsMutating.js");
/* harmony import */ var _useMutation__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./useMutation */ "./node_modules/react-query/es/react/useMutation.js");
/* harmony import */ var _useQuery__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./useQuery */ "./node_modules/react-query/es/react/useQuery.js");
/* harmony import */ var _useQueries__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./useQueries */ "./node_modules/react-query/es/react/useQueries.js");
/* harmony import */ var _useInfiniteQuery__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./useInfiniteQuery */ "./node_modules/react-query/es/react/useInfiniteQuery.js");
/* harmony import */ var _Hydrate__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Hydrate */ "./node_modules/react-query/es/react/Hydrate.js");
/* harmony import */ var _types__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./types */ "./node_modules/react-query/es/react/types.js");
/* harmony import */ var _types__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_types__WEBPACK_IMPORTED_MODULE_11__);
/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};
/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _types__WEBPACK_IMPORTED_MODULE_11__) if(["default","QueryClientProvider","useQueryClient","QueryErrorResetBoundary","useQueryErrorResetBoundary","useIsFetching","useIsMutating","useMutation","useQuery","useQueries","useInfiniteQuery","useHydrate","Hydrate"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _types__WEBPACK_IMPORTED_MODULE_11__[__WEBPACK_IMPORT_KEY__]
/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);
// Side effects










 // Types



/***/ }),

/***/ "./node_modules/react-query/es/react/logger.js":
/*!*****************************************************!*\
  !*** ./node_modules/react-query/es/react/logger.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "logger": () => (/* binding */ logger)
/* harmony export */ });
var logger = console;

/***/ }),

/***/ "./node_modules/react-query/es/react/reactBatchedUpdates.js":
/*!******************************************************************!*\
  !*** ./node_modules/react-query/es/react/reactBatchedUpdates.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "unstable_batchedUpdates": () => (/* binding */ unstable_batchedUpdates)
/* harmony export */ });
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_0__);

var unstable_batchedUpdates = (react_dom__WEBPACK_IMPORTED_MODULE_0___default().unstable_batchedUpdates);

/***/ }),

/***/ "./node_modules/react-query/es/react/setBatchUpdatesFn.js":
/*!****************************************************************!*\
  !*** ./node_modules/react-query/es/react/setBatchUpdatesFn.js ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../core */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _reactBatchedUpdates__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./reactBatchedUpdates */ "./node_modules/react-query/es/react/reactBatchedUpdates.js");


_core__WEBPACK_IMPORTED_MODULE_0__.notifyManager.setBatchNotifyFunction(_reactBatchedUpdates__WEBPACK_IMPORTED_MODULE_1__.unstable_batchedUpdates);

/***/ }),

/***/ "./node_modules/react-query/es/react/setLogger.js":
/*!********************************************************!*\
  !*** ./node_modules/react-query/es/react/setLogger.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../core */ "./node_modules/react-query/es/core/logger.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./logger */ "./node_modules/react-query/es/react/logger.js");


(0,_core__WEBPACK_IMPORTED_MODULE_0__.setLogger)(_logger__WEBPACK_IMPORTED_MODULE_1__.logger);

/***/ }),

/***/ "./node_modules/react-query/es/react/types.js":
/*!****************************************************!*\
  !*** ./node_modules/react-query/es/react/types.js ***!
  \****************************************************/
/***/ (() => {



/***/ }),

/***/ "./node_modules/react-query/es/react/useBaseQuery.js":
/*!***********************************************************!*\
  !*** ./node_modules/react-query/es/react/useBaseQuery.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useBaseQuery": () => (/* binding */ useBaseQuery)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../core/notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _QueryErrorResetBoundary__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./QueryErrorResetBoundary */ "./node_modules/react-query/es/react/QueryErrorResetBoundary.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/react/utils.js");





function useBaseQuery(options, Observer) {
  var mountedRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(false);

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_0___default().useState(0),
      forceUpdate = _React$useState[1];

  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__.useQueryClient)();
  var errorResetBoundary = (0,_QueryErrorResetBoundary__WEBPACK_IMPORTED_MODULE_2__.useQueryErrorResetBoundary)();
  var defaultedOptions = queryClient.defaultQueryObserverOptions(options); // Make sure results are optimistically set in fetching state before subscribing or updating options

  defaultedOptions.optimisticResults = true; // Include callbacks in batch renders

  if (defaultedOptions.onError) {
    defaultedOptions.onError = _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(defaultedOptions.onError);
  }

  if (defaultedOptions.onSuccess) {
    defaultedOptions.onSuccess = _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(defaultedOptions.onSuccess);
  }

  if (defaultedOptions.onSettled) {
    defaultedOptions.onSettled = _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(defaultedOptions.onSettled);
  }

  if (defaultedOptions.suspense) {
    // Always set stale time when using suspense to prevent
    // fetching again when directly mounting after suspending
    if (typeof defaultedOptions.staleTime !== 'number') {
      defaultedOptions.staleTime = 1000;
    } // Set cache time to 1 if the option has been set to 0
    // when using suspense to prevent infinite loop of fetches


    if (defaultedOptions.cacheTime === 0) {
      defaultedOptions.cacheTime = 1;
    }
  }

  if (defaultedOptions.suspense || defaultedOptions.useErrorBoundary) {
    // Prevent retrying failed query if the error boundary has not been reset yet
    if (!errorResetBoundary.isReset()) {
      defaultedOptions.retryOnMount = false;
    }
  }

  var _React$useState2 = react__WEBPACK_IMPORTED_MODULE_0___default().useState(function () {
    return new Observer(queryClient, defaultedOptions);
  }),
      observer = _React$useState2[0];

  var result = observer.getOptimisticResult(defaultedOptions);
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    mountedRef.current = true;
    errorResetBoundary.clearReset();
    var unsubscribe = observer.subscribe(_core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(function () {
      if (mountedRef.current) {
        forceUpdate(function (x) {
          return x + 1;
        });
      }
    })); // Update result to make sure we did not miss any query updates
    // between creating the observer and subscribing to it.

    observer.updateResult();
    return function () {
      mountedRef.current = false;
      unsubscribe();
    };
  }, [errorResetBoundary, observer]);
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    // Do not notify on updates because of changes in the options because
    // these changes should already be reflected in the optimistic result.
    observer.setOptions(defaultedOptions, {
      listeners: false
    });
  }, [defaultedOptions, observer]); // Handle suspense

  if (defaultedOptions.suspense && result.isLoading) {
    throw observer.fetchOptimistic(defaultedOptions).then(function (_ref) {
      var data = _ref.data;
      defaultedOptions.onSuccess == null ? void 0 : defaultedOptions.onSuccess(data);
      defaultedOptions.onSettled == null ? void 0 : defaultedOptions.onSettled(data, null);
    }).catch(function (error) {
      errorResetBoundary.clearReset();
      defaultedOptions.onError == null ? void 0 : defaultedOptions.onError(error);
      defaultedOptions.onSettled == null ? void 0 : defaultedOptions.onSettled(undefined, error);
    });
  } // Handle error boundary


  if (result.isError && !errorResetBoundary.isReset() && !result.isFetching && (0,_utils__WEBPACK_IMPORTED_MODULE_4__.shouldThrowError)(defaultedOptions.suspense, defaultedOptions.useErrorBoundary, [result.error, observer.getCurrentQuery()])) {
    throw result.error;
  } // Handle result property usage tracking


  if (defaultedOptions.notifyOnChangeProps === 'tracked') {
    result = observer.trackResult(result, defaultedOptions);
  }

  return result;
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useInfiniteQuery.js":
/*!***************************************************************!*\
  !*** ./node_modules/react-query/es/react/useInfiniteQuery.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useInfiniteQuery": () => (/* binding */ useInfiniteQuery)
/* harmony export */ });
/* harmony import */ var _core_infiniteQueryObserver__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core/infiniteQueryObserver */ "./node_modules/react-query/es/core/infiniteQueryObserver.js");
/* harmony import */ var _core_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../core/utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _useBaseQuery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./useBaseQuery */ "./node_modules/react-query/es/react/useBaseQuery.js");


 // HOOK

function useInfiniteQuery(arg1, arg2, arg3) {
  var options = (0,_core_utils__WEBPACK_IMPORTED_MODULE_0__.parseQueryArgs)(arg1, arg2, arg3);
  return (0,_useBaseQuery__WEBPACK_IMPORTED_MODULE_1__.useBaseQuery)(options, _core_infiniteQueryObserver__WEBPACK_IMPORTED_MODULE_2__.InfiniteQueryObserver);
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useIsFetching.js":
/*!************************************************************!*\
  !*** ./node_modules/react-query/es/react/useIsFetching.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useIsFetching": () => (/* binding */ useIsFetching)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../core/notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _core_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core/utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");





var checkIsFetching = function checkIsFetching(queryClient, filters, isFetching, setIsFetching) {
  var newIsFetching = queryClient.isFetching(filters);

  if (isFetching !== newIsFetching) {
    setIsFetching(newIsFetching);
  }
};

function useIsFetching(arg1, arg2) {
  var mountedRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(false);
  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__.useQueryClient)();

  var _parseFilterArgs = (0,_core_utils__WEBPACK_IMPORTED_MODULE_2__.parseFilterArgs)(arg1, arg2),
      filters = _parseFilterArgs[0];

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_0___default().useState(queryClient.isFetching(filters)),
      isFetching = _React$useState[0],
      setIsFetching = _React$useState[1];

  var filtersRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(filters);
  filtersRef.current = filters;
  var isFetchingRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(isFetching);
  isFetchingRef.current = isFetching;
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    mountedRef.current = true;
    checkIsFetching(queryClient, filtersRef.current, isFetchingRef.current, setIsFetching);
    var unsubscribe = queryClient.getQueryCache().subscribe(_core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(function () {
      if (mountedRef.current) {
        checkIsFetching(queryClient, filtersRef.current, isFetchingRef.current, setIsFetching);
      }
    }));
    return function () {
      mountedRef.current = false;
      unsubscribe();
    };
  }, [queryClient]);
  return isFetching;
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useIsMutating.js":
/*!************************************************************!*\
  !*** ./node_modules/react-query/es/react/useIsMutating.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useIsMutating": () => (/* binding */ useIsMutating)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../core/notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _core_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../core/utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");




function useIsMutating(arg1, arg2) {
  var mountedRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(false);
  var filters = (0,_core_utils__WEBPACK_IMPORTED_MODULE_1__.parseMutationFilterArgs)(arg1, arg2);
  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_2__.useQueryClient)();

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_0___default().useState(queryClient.isMutating(filters)),
      isMutating = _React$useState[0],
      setIsMutating = _React$useState[1];

  var filtersRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(filters);
  filtersRef.current = filters;
  var isMutatingRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(isMutating);
  isMutatingRef.current = isMutating;
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    mountedRef.current = true;
    var unsubscribe = queryClient.getMutationCache().subscribe(_core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(function () {
      if (mountedRef.current) {
        var newIsMutating = queryClient.isMutating(filtersRef.current);

        if (isMutatingRef.current !== newIsMutating) {
          setIsMutating(newIsMutating);
        }
      }
    }));
    return function () {
      mountedRef.current = false;
      unsubscribe();
    };
  }, [queryClient]);
  return isMutating;
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useMutation.js":
/*!**********************************************************!*\
  !*** ./node_modules/react-query/es/react/useMutation.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useMutation": () => (/* binding */ useMutation)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _core_notifyManager__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../core/notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _core_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core/utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _core_mutationObserver__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../core/mutationObserver */ "./node_modules/react-query/es/core/mutationObserver.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./utils */ "./node_modules/react-query/es/react/utils.js");






 // HOOK

function useMutation(arg1, arg2, arg3) {
  var mountedRef = react__WEBPACK_IMPORTED_MODULE_1___default().useRef(false);

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_1___default().useState(0),
      forceUpdate = _React$useState[1];

  var options = (0,_core_utils__WEBPACK_IMPORTED_MODULE_2__.parseMutationArgs)(arg1, arg2, arg3);
  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_3__.useQueryClient)();
  var obsRef = react__WEBPACK_IMPORTED_MODULE_1___default().useRef();

  if (!obsRef.current) {
    obsRef.current = new _core_mutationObserver__WEBPACK_IMPORTED_MODULE_4__.MutationObserver(queryClient, options);
  } else {
    obsRef.current.setOptions(options);
  }

  var currentResult = obsRef.current.getCurrentResult();
  react__WEBPACK_IMPORTED_MODULE_1___default().useEffect(function () {
    mountedRef.current = true;
    var unsubscribe = obsRef.current.subscribe(_core_notifyManager__WEBPACK_IMPORTED_MODULE_5__.notifyManager.batchCalls(function () {
      if (mountedRef.current) {
        forceUpdate(function (x) {
          return x + 1;
        });
      }
    }));
    return function () {
      mountedRef.current = false;
      unsubscribe();
    };
  }, []);
  var mutate = react__WEBPACK_IMPORTED_MODULE_1___default().useCallback(function (variables, mutateOptions) {
    obsRef.current.mutate(variables, mutateOptions).catch(_core_utils__WEBPACK_IMPORTED_MODULE_2__.noop);
  }, []);

  if (currentResult.error && (0,_utils__WEBPACK_IMPORTED_MODULE_6__.shouldThrowError)(undefined, obsRef.current.options.useErrorBoundary, [currentResult.error])) {
    throw currentResult.error;
  }

  return (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, currentResult, {
    mutate: mutate,
    mutateAsync: currentResult.mutate
  });
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useQueries.js":
/*!*********************************************************!*\
  !*** ./node_modules/react-query/es/react/useQueries.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useQueries": () => (/* binding */ useQueries)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _core_notifyManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../core/notifyManager */ "./node_modules/react-query/es/core/notifyManager.js");
/* harmony import */ var _core_queriesObserver__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core/queriesObserver */ "./node_modules/react-query/es/core/queriesObserver.js");
/* harmony import */ var _QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./QueryClientProvider */ "./node_modules/react-query/es/react/QueryClientProvider.js");




function useQueries(queries) {
  var mountedRef = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(false);

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_0___default().useState(0),
      forceUpdate = _React$useState[1];

  var queryClient = (0,_QueryClientProvider__WEBPACK_IMPORTED_MODULE_1__.useQueryClient)();
  var defaultedQueries = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    return queries.map(function (options) {
      var defaultedOptions = queryClient.defaultQueryObserverOptions(options); // Make sure the results are already in fetching state before subscribing or updating options

      defaultedOptions.optimisticResults = true;
      return defaultedOptions;
    });
  }, [queries, queryClient]);

  var _React$useState2 = react__WEBPACK_IMPORTED_MODULE_0___default().useState(function () {
    return new _core_queriesObserver__WEBPACK_IMPORTED_MODULE_2__.QueriesObserver(queryClient, defaultedQueries);
  }),
      observer = _React$useState2[0];

  var result = observer.getOptimisticResult(defaultedQueries);
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    mountedRef.current = true;
    var unsubscribe = observer.subscribe(_core_notifyManager__WEBPACK_IMPORTED_MODULE_3__.notifyManager.batchCalls(function () {
      if (mountedRef.current) {
        forceUpdate(function (x) {
          return x + 1;
        });
      }
    }));
    return function () {
      mountedRef.current = false;
      unsubscribe();
    };
  }, [observer]);
  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    // Do not notify on updates because of changes in the options because
    // these changes should already be reflected in the optimistic result.
    observer.setQueries(defaultedQueries, {
      listeners: false
    });
  }, [defaultedQueries, observer]);
  return result;
}

/***/ }),

/***/ "./node_modules/react-query/es/react/useQuery.js":
/*!*******************************************************!*\
  !*** ./node_modules/react-query/es/react/useQuery.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useQuery": () => (/* binding */ useQuery)
/* harmony export */ });
/* harmony import */ var _core__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../core */ "./node_modules/react-query/es/core/queryObserver.js");
/* harmony import */ var _core_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../core/utils */ "./node_modules/react-query/es/core/utils.js");
/* harmony import */ var _useBaseQuery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./useBaseQuery */ "./node_modules/react-query/es/react/useBaseQuery.js");


 // HOOK

function useQuery(arg1, arg2, arg3) {
  var parsedOptions = (0,_core_utils__WEBPACK_IMPORTED_MODULE_0__.parseQueryArgs)(arg1, arg2, arg3);
  return (0,_useBaseQuery__WEBPACK_IMPORTED_MODULE_1__.useBaseQuery)(parsedOptions, _core__WEBPACK_IMPORTED_MODULE_2__.QueryObserver);
}

/***/ }),

/***/ "./node_modules/react-query/es/react/utils.js":
/*!****************************************************!*\
  !*** ./node_modules/react-query/es/react/utils.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shouldThrowError": () => (/* binding */ shouldThrowError)
/* harmony export */ });
function shouldThrowError(suspense, _useErrorBoundary, params) {
  // Allow useErrorBoundary function to override throwing behavior on a per-error basis
  if (typeof _useErrorBoundary === 'function') {
    return _useErrorBoundary.apply(void 0, params);
  } // Allow useErrorBoundary to override suspense's throwing behavior


  if (typeof _useErrorBoundary === 'boolean') return _useErrorBoundary; // If suspense is enabled default to throwing errors

  return !!suspense;
}

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

"use strict";
module.exports = window["React"];

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ ((module) => {

"use strict";
module.exports = window["ReactDOM"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/date":
/*!******************************!*\
  !*** external ["wp","date"] ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["date"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/utils":
/*!*******************************!*\
  !*** external ["wp","utils"] ***!
  \*******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["utils"];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  _extends = Object.assign ? Object.assign.bind() : function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];
      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }
    return target;
  };
  return _extends.apply(this, arguments);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _inheritsLoose)
/* harmony export */ });
/* harmony import */ var _setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setPrototypeOf.js */ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js");

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  (0,_setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__["default"])(subClass, superClass);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _setPrototypeOf)
/* harmony export */ });
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };
  return _setPrototypeOf(o, p);
}

/***/ })

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
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!****************************************!*\
  !*** ./src/editor-sidebar/sidebars.js ***!
  \****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _rsvpmaker_sidebar_extra_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./rsvpmaker-sidebar-extra.js */ "./src/editor-sidebar/rsvpmaker-sidebar-extra.js");
/* harmony import */ var _rsvpmaker_sidebar_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./rsvpmaker-sidebar.js */ "./src/editor-sidebar/rsvpmaker-sidebar.js");


})();

/******/ })()
;
//# sourceMappingURL=sidebars.js.map