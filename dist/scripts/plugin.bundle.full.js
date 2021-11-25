/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "multi \\wp\\wp-content\\plugins\\rfs-acf-sync-notice\\src\\scripts\\plugin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "aphK":
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/*
  Project: RFS ACF JSON Sync Notice
  Author: rfswp
 */

/* global RFS_ACF_SYNC_NOTICE */
var RfsAcfJsonSyncNotice =
/*#__PURE__*/
function () {
  function RfsAcfJsonSyncNotice() {
    _classCallCheck(this, RfsAcfJsonSyncNotice);

    this.data = RFS_ACF_SYNC_NOTICE;
  }

  _createClass(RfsAcfJsonSyncNotice, [{
    key: "init",
    value: function init() {
      switch (this.data.mode) {
        case 'notice':
          this.noticeMode();
          break;

        default:
          this.autoMode();
          break;
      }
    }
  }, {
    key: "autoMode",
    value: function autoMode() {
      var _container$classList,
          _spinner$classList,
          _this = this;

      if (!this.data.doAutoSync) {
        return;
      }

      var container = document.createElement('div');
      var inner = document.createElement('div');

      (_container$classList = container.classList).add.apply(_container$classList, ['rfs-acf-sync-auto-container', 'show']);

      inner.classList.add('rfs-acf-sync-auto-inner');
      var spinner = document.createElement('span');

      (_spinner$classList = spinner.classList).add.apply(_spinner$classList, ['spinner', 'is-active']);

      var message = document.createElement('p');
      Object.assign(message, {
        className: 'rfs-acf-sync-auto-message',
        innerText: this.data.autoSync.syncing
      });
      inner.appendChild(spinner);
      inner.appendChild(message);
      container.appendChild(inner);
      document.body.appendChild(container);
      fetch(this.data.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Cache-Control': 'no-cache'
        },
        body: new URLSearchParams({
          action: 'rfs_acf_auto_sync',
          security: this.data.nonce,
          sync_data: JSON.stringify(this.data.syncData),
          files: JSON.stringify(this.data.files),
          url: this.data.acfPageUrl,
          auto_sync_mode: this.data.autoSyncMode
        })
      }).then(function (response) {
        return response.json();
      }).then(function (response) {
        if (response.ok) {
          if (response.redirectUrl === 'none') {
            spinner.remove();
            message.innerText = _this.data.autoSync.synced;
            message.classList.add('is-synced');
            setTimeout(function () {
              container.classList.remove('show');
            }, 500);
          } else {
            setTimeout(function () {
              window.location.replace(response.redirectUrl);
            }, 1000);
          }
        }
      }).catch(function (error) {
        console.log(error);
      });
    }
  }, {
    key: "noticeMode",
    value: function noticeMode() {
      if (!this.data.groupHasSync) {
        return;
      }

      var modal = document.createElement('div');
      modal.classList.add('rfs-acf-sync-modal');
      var modalInner = document.createElement('div');
      modalInner.classList.add('rfs-acf-sync-modal-inner');
      var modalHeading = document.createElement('h2');
      var modalText = document.createElement('p');
      var modalButtonsContainer = document.createElement('div');
      modalButtonsContainer.classList.add('rfs-acf-sync-modal-buttons');
      var modalButtonSync = document.createElement('a');
      var modalButtonIgnore = document.createElement('a');
      Object.assign(modalHeading, {
        className: 'rfs-acf-sync-modal-heading',
        innerText: this.data.modal.heading
      });
      Object.assign(modalText, {
        className: 'rfs-acf-sync-modal-text',
        innerText: this.data.modal.text
      });
      Object.assign(modalButtonSync, {
        href: this.data.modal.buttons.sync.url,
        className: 'rfs-acf-modal-button button button-primary button-large',
        innerText: this.data.modal.buttons.sync.label
      });
      Object.assign(modalButtonIgnore, {
        href: this.data.modal.buttons.ignore.url,
        className: 'rfs-acf-modal-button button page-title-action',
        role: 'button',
        innerText: this.data.modal.buttons.ignore.label
      });
      modalButtonsContainer.appendChild(modalButtonSync);
      modalButtonsContainer.appendChild(modalButtonIgnore);
      modalInner.appendChild(modalHeading);
      modalInner.appendChild(modalText);
      modalInner.appendChild(modalButtonsContainer);
      modal.appendChild(modalInner);
      document.body.appendChild(modal);
      modalButtonIgnore.addEventListener('click', function (e) {
        e.preventDefault();
        modal.classList.add('hide');
      });
    }
  }]);

  return RfsAcfJsonSyncNotice;
}();

var plugin = new RfsAcfJsonSyncNotice();
plugin.init();

/***/ }),

/***/ "multi \\wp\\wp-content\\plugins\\rfs-acf-sync-notice\\src\\scripts\\plugin.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("aphK");


/***/ })

/******/ });
