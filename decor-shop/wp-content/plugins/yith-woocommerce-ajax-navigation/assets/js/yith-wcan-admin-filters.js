/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// CONCATENATED MODULE: ./assets/js/shortcodes/globals.js


/* global globalThis, jQuery, yith_wcan_shortcodes, accounting */
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
var $ = jQuery,
  // we can do this as WebPack will compact all together inside a closure.
  $body = $('body'),
  block = function block($el) {
    var _yith_wcan_shortcodes;
    if (typeof $.fn.block === 'undefined') {
      return;
    }
    var background = '#fff center center no-repeat';
    if ('undefined' !== typeof yith_wcan_shortcodes && (_yith_wcan_shortcodes = yith_wcan_shortcodes) !== null && _yith_wcan_shortcodes !== void 0 && _yith_wcan_shortcodes.loader) {
      background = "url('".concat(yith_wcan_shortcodes.loader, "') ").concat(background);
    }
    $el.block({
      message: null,
      overlayCSS: {
        background: background,
        opacity: 0.7
      }
    });
  },
  unblock = function unblock($el) {
    if (typeof $.fn.unblock === 'undefined') {
      return;
    }
    $el.unblock();
  },
  serialize = function serialize($el, _ref) {
    var formatName = _ref.formatName,
      filterItems = _ref.filterItems;
    var result = {},
      inputs = $el.find(':input').not('[disabled]');
    if (typeof filterItems === 'function') {
      inputs = inputs.filter(filterItems);
    }
    inputs.each(function () {
      var t = $(this),
        name = t.attr('name'),
        value;
      if (!name) {
        return;
      }

      // removes ending brackets, since are not needed
      name = name.replace(/^(.*)\[]$/, '$1');

      // offers additional name formatting from invoker
      if (typeof formatName === 'function') {
        name = formatName(name);
      }

      // retrieve value, depending on input type
      if (t.is('[type="radio"]') && !t.is(':checked')) {
        return;
      }
      value = t.val();

      // if name is composite, try to recreate missing structure
      if (-1 !== name.indexOf('[')) {
        var components = name.split('[').map(function (c) {
            return c.replace(/[\[, \]]/g, '');
          }),
          firstComponent = components.shift(),
          newItem = components.reverse().reduce(function (res, key) {
            return _defineProperty({}, key, res);
          }, value);
        if (typeof result[firstComponent] === 'undefined') {
          result[firstComponent] = newItem;
        } else {
          result[firstComponent] = $.extend(true, result[firstComponent], newItem);
        }
      }
      // else simply append value to result object
      else {
        result[name] = value;
      }
    });
    return result;
  },
  removeHierarchyFromString = function removeHierarchyFromString(value) {
    return value.replace(/^(.*>)([^>]+)$/, '$2').replace('&amp;', '&').trim();
  },
  keys = {
    esc: 27,
    enter: 32,
    space: 13,
    up: 38,
    down: 40
  };

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/ajax.js


/* global ajaxurl */
function ajax_typeof(obj) { "@babel/helpers - typeof"; return ajax_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, ajax_typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { ajax_defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function ajax_defineProperty(obj, key, value) { key = ajax_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function ajax_toPropertyKey(arg) { var key = ajax_toPrimitive(arg, "string"); return ajax_typeof(key) === "symbol" ? key : String(key); }
function ajax_toPrimitive(input, hint) { if (ajax_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (ajax_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }

var request = function request(method, action, params, args) {
    // retrieve wrapper as current context.
    var $wrapper = $(this);
    if (params instanceof FormData) {
      params.append('action', "yith_wcan_".concat(action));
    } else {
      params = _objectSpread({
        action: "yith_wcan_".concat(action)
      }, params);
    }
    var ajaxArgs = _objectSpread({
      url: ajaxurl,
      data: params,
      dataType: 'json',
      method: method,
      beforeSend: function beforeSend() {
        return $wrapper.length && block($wrapper);
      },
      complete: function complete() {
        return $wrapper.length && unblock($wrapper);
      }
    }, args);
    return $.ajax(ajaxArgs);
  },
  get = function get() {
    for (var _len = arguments.length, params = new Array(_len), _key = 0; _key < _len; _key++) {
      params[_key] = arguments[_key];
    }
    return request.call.apply(request, [this, 'get'].concat(params));
  },
  post = function post() {
    for (var _len2 = arguments.length, params = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      params[_key2] = arguments[_key2];
    }
    return request.call.apply(request, [this, 'post'].concat(params));
  };
/* harmony default export */ const ajax = ({
  request: request,
  get: get,
  post: post
});
;// CONCATENATED MODULE: ./assets/js/admin-filters/conf.js


/* global jQuery */
var filterFieldsDependencies = {
  taxonomy: {
    type: 'tax'
  },
  use_all_terms: {
    type: 'tax'
  },
  term_ids: {
    type: 'tax',
    use_all_terms: '!:checked'
  },
  filter_design: {
    type: ['tax', 'review', 'price_range']
  },
  customize_terms: {
    type: 'tax',
    use_all_terms: '!:checked'
  },
  terms_options: {
    term_ids: function term_ids(v) {
      return !!v;
    },
    customize_terms: ':checked',
    __show: function __show(filter) {
      return filter.afterTermsSelected();
    }
  },
  label_position: {
    filter_design: ['color', 'label']
  },
  column_number: {
    filter_design: ['label', 'color'],
    label_position: ['below', 'hide']
  },
  show_search: {
    type: 'tax',
    filter_design: 'select'
  },
  price_ranges: {
    type: 'price_range'
  },
  price_slider_adaptive_limits: {
    type: 'price_slider'
  },
  price_slider_design: {
    type: 'price_slider'
  },
  price_slider_min: {
    type: 'price_slider',
    price_slider_adaptive_limits: '!:checked'
  },
  price_slider_max: {
    type: 'price_slider',
    price_slider_adaptive_limits: '!:checked'
  },
  price_slider_step: {
    type: 'price_slider'
  },
  order_options: {
    type: 'orderby'
  },
  show_stock_filter: {
    type: 'stock_sale'
  },
  show_sale_filter: {
    type: 'stock_sale'
  },
  show_featured_filter: {
    type: 'stock_sale'
  },
  toggle_style: {
    show_toggle: ':checked'
  },
  order_by: {
    type: 'tax'
  },
  order: {
    type: 'tax'
  },
  show_count: {
    type: ['tax', 'price_range', 'review', 'stock_sale']
  },
  hierarchical: {
    type: 'tax',
    filter_design: ['checkbox', 'radio', 'text']
  },
  multiple: {
    type: ['tax', 'review', 'price_range'],
    filter_design: '!radio'
  },
  relation: {
    type: 'tax',
    multiple: ':checked'
  },
  adoptive: {
    type: ['tax', 'price_range', 'review', 'stock_sale']
  }
};

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/yith-wcan-dependencies-handler.js


/* global yith_wcan_admin, ajaxurl */
function yith_wcan_dependencies_handler_typeof(obj) { "@babel/helpers - typeof"; return yith_wcan_dependencies_handler_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, yith_wcan_dependencies_handler_typeof(obj); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i["return"] && (_r = _i["return"](), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, yith_wcan_dependencies_handler_toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function yith_wcan_dependencies_handler_defineProperty(obj, key, value) { key = yith_wcan_dependencies_handler_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function yith_wcan_dependencies_handler_toPropertyKey(arg) { var key = yith_wcan_dependencies_handler_toPrimitive(arg, "string"); return yith_wcan_dependencies_handler_typeof(key) === "symbol" ? key : String(key); }
function yith_wcan_dependencies_handler_toPrimitive(input, hint) { if (yith_wcan_dependencies_handler_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (yith_wcan_dependencies_handler_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }

var YITH_WCAN_Dependencies_Handler = /*#__PURE__*/function () {
  function YITH_WCAN_Dependencies_Handler($container, dependenciesTree, context) {
    var _this$$container, _this$$fields;
    _classCallCheck(this, YITH_WCAN_Dependencies_Handler);
    // container
    yith_wcan_dependencies_handler_defineProperty(this, "$container", void 0);
    // fields;
    yith_wcan_dependencies_handler_defineProperty(this, "$fields", void 0);
    // dependencies tree.
    yith_wcan_dependencies_handler_defineProperty(this, "dependencies", {});
    // context object.
    yith_wcan_dependencies_handler_defineProperty(this, "context", null);
    yith_wcan_dependencies_handler_defineProperty(this, "checkFieldConditions", function (conditions) {
      var result = true;
      for (var _i = 0, _Object$entries = Object.entries(conditions); _i < _Object$entries.length; _i++) {
        var _$field;
        var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
          field = _Object$entries$_i[0],
          condition = _Object$entries$_i[1];
        var $field = void 0,
          fieldValue = void 0;
        if (!result || ['__show', '__hide'].includes(field)) {
          continue;
        }
        $field = this.findField(field, false);
        if (!((_$field = $field) !== null && _$field !== void 0 && _$field.length)) {
          continue;
        }
        if ($field.first().is('input[type="radio"]')) {
          fieldValue = $field.filter(':checked').val().toString();
        } else {
          var _$field2, _$field2$val;
          fieldValue = (_$field2 = $field) === null || _$field2 === void 0 ? void 0 : (_$field2$val = _$field2.val()) === null || _$field2$val === void 0 ? void 0 : _$field2$val.toString();
        }
        if (Array.isArray(condition)) {
          result = condition.includes(fieldValue);
        } else if (typeof condition === 'function') {
          result = condition(fieldValue, $field, this.$container);
        } else if (0 === condition.indexOf(':')) {
          result = $field.is(condition);
        } else if (0 === condition.indexOf('!:')) {
          result = !$field.is(condition.toString().substring(1));
        } else if (0 === condition.indexOf('!')) {
          result = condition.toString().substring(1) !== fieldValue;
        } else {
          result = condition.toString() === fieldValue;
        }
        if (typeof this.dependencies[field] !== 'undefined') {
          result = result && this.checkFieldConditions(this.dependencies[field]);
        }
      }
      return result;
    });
    this.$container = $container;
    this.dependencies = dependenciesTree;
    this.context = context || {};
    if (!((_this$$container = this.$container) !== null && _this$$container !== void 0 && _this$$container.length)) {
      return;
    }
    this.initFields();
    if (!((_this$$fields = this.$fields) !== null && _this$$fields !== void 0 && _this$$fields.length)) {
      return;
    }
    this.initDependencies();
  }
  _createClass(YITH_WCAN_Dependencies_Handler, [{
    key: "initFields",
    value: function initFields() {
      this.$fields = this.$container.find(':input');
    }
  }, {
    key: "findField",
    value: function findField(field, returnContainer) {
      var $field;
      if ('function' === typeof this.context.findField) {
        return this.context.findField(field, returnContainer);
      }
      $field = this.$container.find(":input[name*=\"".concat(field, "\"]"));
      if (!$field.length) {
        return null;
      }
      if (returnContainer) {
        return $field.closest('.yith-toggle-content-row');
      }
      return $field;
    }
  }, {
    key: "initDependencies",
    value: function initDependencies() {
      if (!Object.keys(this.dependencies).length) {
        return;
      }
      this.handleDependencies();
    }
  }, {
    key: "handleDependencies",
    value: function handleDependencies() {
      var _this = this;
      this.$fields.on('change', function () {
        return _this.applyDependencies();
      });
      this.applyDependencies();
    }
  }, {
    key: "applyDependencies",
    value: function applyDependencies() {
      for (var _i2 = 0, _Object$entries2 = Object.entries(this.dependencies); _i2 < _Object$entries2.length; _i2++) {
        var _Object$entries2$_i = _slicedToArray(_Object$entries2[_i2], 2),
          field = _Object$entries2$_i[0],
          conditions = _Object$entries2$_i[1];
        var $field = this.findField(field, true),
          show = this.checkFieldConditions(conditions);
        if (show) {
          $field === null || $field === void 0 ? void 0 : $field.css({
            display: 'table'
          });
          if ('function' === typeof (conditions === null || conditions === void 0 ? void 0 : conditions.__show)) {
            conditions === null || conditions === void 0 ? void 0 : conditions.__show(this.context);
          }
        } else {
          $field === null || $field === void 0 ? void 0 : $field.hide();
          if ('function' === typeof (conditions === null || conditions === void 0 ? void 0 : conditions.__hide)) {
            conditions === null || conditions === void 0 ? void 0 : conditions.__hide(this.context);
          }
        }
      }
    }
  }]);
  return YITH_WCAN_Dependencies_Handler;
}();

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/yith-wcan-filter-term.js


/* global yith_wcan_admin, ajaxurl */
function yith_wcan_filter_term_typeof(obj) { "@babel/helpers - typeof"; return yith_wcan_filter_term_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, yith_wcan_filter_term_typeof(obj); }
function yith_wcan_filter_term_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function yith_wcan_filter_term_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, yith_wcan_filter_term_toPropertyKey(descriptor.key), descriptor); } }
function yith_wcan_filter_term_createClass(Constructor, protoProps, staticProps) { if (protoProps) yith_wcan_filter_term_defineProperties(Constructor.prototype, protoProps); if (staticProps) yith_wcan_filter_term_defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function yith_wcan_filter_term_defineProperty(obj, key, value) { key = yith_wcan_filter_term_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function yith_wcan_filter_term_toPropertyKey(arg) { var key = yith_wcan_filter_term_toPrimitive(arg, "string"); return yith_wcan_filter_term_typeof(key) === "symbol" ? key : String(key); }
function yith_wcan_filter_term_toPrimitive(input, hint) { if (yith_wcan_filter_term_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (yith_wcan_filter_term_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }

var YITH_WCAN_Filter_Term = /*#__PURE__*/function () {
  function YITH_WCAN_Filter_Term($term, filter) {
    yith_wcan_filter_term_classCallCheck(this, YITH_WCAN_Filter_Term);
    /**
     * Term id
     */
    yith_wcan_filter_term_defineProperty(this, "id", void 0);
    /**
     * Dom object for containing term box
     */
    yith_wcan_filter_term_defineProperty(this, "$term", void 0);
    /**
     * Filter object
     */
    yith_wcan_filter_term_defineProperty(this, "filter", void 0);
    if (!$term.length) {
      return;
    }
    this.$term = $term;
    this.filter = filter;
    if ($term.hasClass('initialized')) {
      return;
    }
    this.init();
  }

  // init object
  yith_wcan_filter_term_createClass(YITH_WCAN_Filter_Term, [{
    key: "init",
    value: function init() {
      this.initTabs();
      this.initImageSelector();
      this.initAdditionalColor();
      this.initFields();
      this.$term.addClass('initialized');
    }
  }, {
    key: "initTabs",
    value: function initTabs() {
      var _this = this;
      var headers = this.$term.find('.term-tab-header');
      headers.on('click', function (ev) {
        var t = $(ev.target),
          tab = t.data('tab');
        ev.preventDefault();
        _this.showTab(tab);
      });
      this.showTab(this.$term.find('.term-mode').val());
    }
  }, {
    key: "initImageSelector",
    value: function initImageSelector() {
      var $imageSelector = this.$term.find('.image-selector'),
        $placeholder = $imageSelector.find('.placeholder-image'),
        $selected = $imageSelector.find('.selected-image'),
        $selectedImg = $selected.find('img'),
        $input = $imageSelector.find('.term-image'),
        $clear = $selected.find('.clear-image'),
        media;
      $placeholder.off('click').on('click', function () {
        block($placeholder);
        if (media) {
          media.open();
          return;
        }

        // Create a new media frame
        media = wp.media({
          title: yith_wcan_admin.labels.upload_media,
          button: {
            text: yith_wcan_admin.labels.confirm_media
          },
          multiple: false
        });

        // When an image is selected in the media frame...
        media.on('select', function () {
          // Get media attachment details from the frame state
          var attachment = media.state().get('selection').first().toJSON();
          $selectedImg.remove();
          $selectedImg = $('<img/>', {
            src: attachment.url
          });
          $selected.prepend($selectedImg);
          $input.val(attachment.id).change();
          unblock($placeholder);
          $placeholder.hide();
          $selected.show();
        });
        media.on('close', function () {
          unblock($placeholder);
        });

        // Finally, open the modal on click
        media.open();
      });
      $clear.off('click').on('click', function () {
        $input.val('').change();
        $selected.hide();
        $placeholder.show();
        return false;
      });
      $input.val() || $clear.click();
    }
  }, {
    key: "initAdditionalColor",
    value: function initAdditionalColor() {
      var _this2 = this;
      var $addColor = this.$term.find('.term-add-second-color'),
        $hideColor = this.$term.find('.term-hide-second-color'),
        $color = this.$term.find('.additional-color').find('input'),
        color = $color.val();
      $addColor.on('click', function () {
        return _this2.showAdditionalColor(), false;
      });
      $hideColor.on('click', function () {
        return _this2.hideAdditionalColor(), false;
      });
      if (color && color !== $color.data('default-color')) {
        this.showAdditionalColor();
      } else {
        this.hideAdditionalColor();
      }
    }
  }, {
    key: "initFields",
    value: function initFields() {
      var _this3 = this;
      this.$term.trigger('yith_fields_init');
      this.$term.find('.yith-plugin-fw-colorpicker--initialized').wpColorPicker('option', 'change', function () {
        return _this3.filter.afterTermChanged(_this3);
      });
      this.$term.on('change', ':input', function () {
        return _this3.filter.afterTermChanged(_this3);
      });
    }

    // actions
  }, {
    key: "showTab",
    value: function showTab(tab, force) {
      var headers = this.$term.find('.term-tab-header'),
        tabs = this.$term.find('.tab'),
        selectedTab = tabs.filter('.tab-' + tab);
      if (!selectedTab.length || !headers.is(':visible') && !force) {
        return;
      }
      var $activeMode = this.$term.find('.term-mode'),
        prevMode = $activeMode.val();
      headers.removeClass('active').filter('[data-tab="' + tab + '"]').addClass('active');
      tabs.hide();
      selectedTab.show();
      $activeMode.val(tab);
      prevMode !== tab && $activeMode.change();
    }
  }, {
    key: "showAdditionalColor",
    value: function showAdditionalColor() {
      var trigger = this.$term.find('.term-add-second-color');
      trigger.parent().hide().next('.additional-color').show().find('.wp-color-picker').prop('disabled', false).change();
    }
  }, {
    key: "hideAdditionalColor",
    value: function hideAdditionalColor() {
      var trigger = this.$term.find('.term-hide-second-color');
      trigger.parent().find('.wp-color-picker').prop('disabled', true).val('').change().end().hide().prev('p').show();
    }
  }, {
    key: "updateFields",
    value: function updateFields(type) {
      var tabToShow = false;
      switch (type) {
        case 'complete':
          this.$term.find('.term-tab-headers').show().find('a[data-tab="color"], span').show();
          this.$term.find('.tab.tab-color').show();
          this.$term.find('.tab.tab-image').show();
          tabToShow = this.$term.find('.term-mode').val();
          break;
        case 'colors_only':
          this.$term.find('.term-tab-headers').show().find('a[data-tab="image"], span').hide();
          this.$term.find('.tab.tab-color').show();
          this.$term.find('.tab.tab-image').hide();
          tabToShow = 'color';
          break;
        case 'image_only':
          this.$term.find('.term-tab-headers').show().find('a[data-tab="color"], span').hide();
          this.$term.find('.tab.tab-color').hide();
          this.$term.find('.tab.tab-image').show();
          tabToShow = 'image';
          break;
        case 'labels_only':
        default:
          this.$term.find('.term-tab-headers').hide();
          this.$term.find('.tab.tab-color').hide();
          this.$term.find('.tab.tab-image').hide();
      }
      tabToShow && this.showTab(tabToShow, true);
    }

    // data handling
  }, {
    key: "getId",
    value: function getId() {
      return this.id || (this.id = this.$term.data('term_id'));
    }
  }, {
    key: "getData",
    value: function getData() {
      var termData = serialize(this.$term, {
        formatName: function formatName(v) {
          return v.replace(/filters\[[0-9]+]\[terms]\[[0-9]+]\[([a-z0-9_-]+)]/, '$1');
        }
      });
      termData.term_id = this.getId();
      return termData;
    }
  }]);
  return YITH_WCAN_Filter_Term;
}();

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/yith-wcan-filter-range.js


/* global yith_wcan_admin, ajaxurl */
function yith_wcan_filter_range_typeof(obj) { "@babel/helpers - typeof"; return yith_wcan_filter_range_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, yith_wcan_filter_range_typeof(obj); }
function yith_wcan_filter_range_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function yith_wcan_filter_range_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, yith_wcan_filter_range_toPropertyKey(descriptor.key), descriptor); } }
function yith_wcan_filter_range_createClass(Constructor, protoProps, staticProps) { if (protoProps) yith_wcan_filter_range_defineProperties(Constructor.prototype, protoProps); if (staticProps) yith_wcan_filter_range_defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function yith_wcan_filter_range_defineProperty(obj, key, value) { key = yith_wcan_filter_range_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function yith_wcan_filter_range_toPropertyKey(arg) { var key = yith_wcan_filter_range_toPrimitive(arg, "string"); return yith_wcan_filter_range_typeof(key) === "symbol" ? key : String(key); }
function yith_wcan_filter_range_toPrimitive(input, hint) { if (yith_wcan_filter_range_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (yith_wcan_filter_range_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }

var yith_wcan_filter_range_YITH_WCAN_Filter = /*#__PURE__*/function () {
  function YITH_WCAN_Filter($range, filter) {
    yith_wcan_filter_range_classCallCheck(this, YITH_WCAN_Filter);
    /**
     * ID of the range
     */
    yith_wcan_filter_range_defineProperty(this, "id", void 0);
    /**
     * Dom object for containing range box
     */
    yith_wcan_filter_range_defineProperty(this, "$range", void 0);
    /**
     * Filter object
     */
    yith_wcan_filter_range_defineProperty(this, "filter", void 0);
    if (!$range.length) {
      return;
    }
    this.$range = $range;
    this.filter = filter;
    if ($range.hasClass('initialized')) {
      return;
    }
    this.init();
  }

  // init objec
  yith_wcan_filter_range_createClass(YITH_WCAN_Filter, [{
    key: "init",
    value: function init() {
      this.initDependencies();
      this.initRemove();
      this.$range.addClass('initialized');
    }
  }, {
    key: "initRemove",
    value: function initRemove() {
      var _this = this;
      this.$range.find('a.range-remove').on('click', function (ev) {
        ev.preventDefault();
        _this.$range.remove();
        _this.filter.afterRangeDelete();
      });
    }
  }, {
    key: "initDependencies",
    value: function initDependencies() {
      var $unlimitedCheck = this.$range.find('[name*="unlimited"]');

      // manage unlimited check
      $unlimitedCheck.on('change', function () {
        var t = $(this),
          $max = t.closest('.range-box').find('.max');
        if (t.is(':checked')) {
          $max.hide();
        } else {
          $max.show();
        }
      }).change();
    }

    // actions
  }, {
    key: "populate",
    value: function populate(rangeData) {
      var min = rangeData.min,
        max = rangeData.max,
        unlimited = rangeData.unlimited;
      this.$range.find('.min').find(':input').val(min);
      this.$range.find('.max').find(':input').val(max);
      this.$range.find('.unlimited').find(':input').prop('checked', unlimited);
    }
  }, {
    key: "toggleUnlimited",
    value: function toggleUnlimited(show) {
      var $unlimitedContainer = this.$range.find('.unlimited'),
        $unlimitedCheck = $unlimitedContainer.find(':input');
      show && $unlimitedContainer.show();
      show || ($unlimitedCheck.prop('checked', false).change(), $unlimitedContainer.hide());
    }

    // data handling
  }, {
    key: "getId",
    value: function getId() {
      return this.id || (this.id = this.$range.data('range_id'));
    }
  }]);
  return YITH_WCAN_Filter;
}();

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/yith-wcan-filter.js


/* global yith_wcan_admin, ajaxurl */
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || yith_wcan_filter_unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return yith_wcan_filter_arrayLikeToArray(arr); }
function yith_wcan_filter_typeof(obj) { "@babel/helpers - typeof"; return yith_wcan_filter_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, yith_wcan_filter_typeof(obj); }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = yith_wcan_filter_unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e2) { throw _e2; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e3) { didErr = true; err = _e3; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function yith_wcan_filter_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function yith_wcan_filter_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? yith_wcan_filter_ownKeys(Object(source), !0).forEach(function (key) { yith_wcan_filter_defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : yith_wcan_filter_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function yith_wcan_filter_slicedToArray(arr, i) { return yith_wcan_filter_arrayWithHoles(arr) || yith_wcan_filter_iterableToArrayLimit(arr, i) || yith_wcan_filter_unsupportedIterableToArray(arr, i) || yith_wcan_filter_nonIterableRest(); }
function yith_wcan_filter_nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function yith_wcan_filter_unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return yith_wcan_filter_arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return yith_wcan_filter_arrayLikeToArray(o, minLen); }
function yith_wcan_filter_arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function yith_wcan_filter_iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i["return"] && (_r = _i["return"](), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function yith_wcan_filter_arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function yith_wcan_filter_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function yith_wcan_filter_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, yith_wcan_filter_toPropertyKey(descriptor.key), descriptor); } }
function yith_wcan_filter_createClass(Constructor, protoProps, staticProps) { if (protoProps) yith_wcan_filter_defineProperties(Constructor.prototype, protoProps); if (staticProps) yith_wcan_filter_defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function yith_wcan_filter_defineProperty(obj, key, value) { key = yith_wcan_filter_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function yith_wcan_filter_toPropertyKey(arg) { var key = yith_wcan_filter_toPrimitive(arg, "string"); return yith_wcan_filter_typeof(key) === "symbol" ? key : String(key); }
function yith_wcan_filter_toPrimitive(input, hint) { if (yith_wcan_filter_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (yith_wcan_filter_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }






var YITH_WCAN_Filter = /*#__PURE__*/function () {
  function YITH_WCAN_Filter($filter, preset) {
    yith_wcan_filter_classCallCheck(this, YITH_WCAN_Filter);
    /**
     * Unique ID of the filter
     */
    yith_wcan_filter_defineProperty(this, "id", void 0);
    /**
     * Dom object for containing filter form
     */
    yith_wcan_filter_defineProperty(this, "$filter", void 0);
    /**
     * Preset object
     */
    yith_wcan_filter_defineProperty(this, "preset", void 0);
    /**
     * Terms map
     */
    yith_wcan_filter_defineProperty(this, "terms", new Map());
    /**
     * Ranges map
     */
    yith_wcan_filter_defineProperty(this, "ranges", new Map());
    if (!$filter.length) {
      return;
    }
    this.$filter = $filter;
    this.$toggleTitle = $filter.find('.yith-toggle-title');
    this.$title = $filter.find('h3.title');
    this.preset = preset;
    if ($filter.hasClass('initialized')) {
      return;
    }
    this.init();
  }

  // init object
  yith_wcan_filter_createClass(YITH_WCAN_Filter, [{
    key: "init",
    value: function init() {
      this.initTitle();
      this.initToggle();
      this.initSave();
      this.initDelete();
      this.initClone();
      this.initTerms();
      this.initRanges();
      this.initDependencies();
      this.initFields();
      this.$filter.addClass('initialized');
    }
  }, {
    key: "initTitle",
    value: function initTitle() {
      var _this = this;
      var $field = this.$filter.find('.heading-field').first();
      if (this.$title.length && $field.length) {
        $field.on('keyup', function () {
          var v = $field.val();
          _this.$title.html(v || "<span class=\"no-title\">".concat(yith_wcan_admin.labels.no_title, "</span>"));
        });
      }
    }
  }, {
    key: "initToggle",
    value: function initToggle() {
      var _this2 = this;
      this.$toggleTitle.on('click', function (ev) {
        var $target = $(ev.target);
        if ($target.closest('.filter-actions').length) {
          return;
        }
        _this2.isOpen() ? _this2.close() : _this2.open();
      });
    }
  }, {
    key: "initDependencies",
    value: function initDependencies() {
      new YITH_WCAN_Dependencies_Handler(this.$filter, filterFieldsDependencies, this);
    }
  }, {
    key: "initFields",
    value: function initFields() {
      this.$filter.trigger('yith_fields_init');
      this.initTermSearch();
      this.initCustomizeTerms();
      this.initTaxonomy();
      this.initType();
      this.initDesign();
      this.initCurrencyFields();
      this.initErrorsHandling();
    }
  }, {
    key: "initTermSearch",
    value: function initTermSearch() {
      var _this3 = this;
      var $termSearch = this.$filter.find('.term-search').first(),
        $taxonomySelect = this.$filter.find('.taxonomy').first(),
        $container = $termSearch.closest('.yith-plugin-fw-field-wrapper'),
        getAjaxParams = function getAjaxParams(params) {
          return {
            term: params.term,
            all: typeof params.all !== 'undefined' ? params.all : 0,
            taxonomy: $taxonomySelect.val(),
            selected: $termSearch.val(),
            action: 'yith_wcan_search_term',
            security: yith_wcan_admin.nonce.search_term,
            lang: yith_wcan_admin.current_lang
          };
        },
        select2_args = {
          placeholder: $(this).data('placeholder'),
          minimumInputLength: '1',
          templateSelection: function templateSelection(option) {
            return removeHierarchyFromString(option.text);
          },
          templateResult: function templateResult(option) {
            return option.text.replace('&amp;', '&');
          },
          ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: getAjaxParams,
            processResults: function processResults(data) {
              var terms = [];
              if (data) {
                $.each(data, function (id, text) {
                  terms.push({
                    id: id,
                    text: text
                  });
                });
              }
              return {
                results: terms
              };
            },
            cache: true
          },
          sorter: function sorter(items) {
            return items;
          }
        };

      // init terms select
      $termSearch.selectWoo(select2_args);

      // on term changes redraw Customize terms section
      $termSearch.on('change', function (ev, ignoreVisibility) {
        return _this3.afterTermsSelected(ignoreVisibility);
      });

      // add all button
      $container.find('.yith-plugin-fw-select-all').on('click', function (ev) {
        ev.preventDefault();
        if (!_this3.confirmAddAllTerms($taxonomySelect)) {
          return false;
        }
        block($container);
        $.get(ajaxurl, getAjaxParams({
          term: '',
          all: 1
        })).then(function (data) {
          var selected = $termSearch.val();
          if (!selected) {
            selected = [];
          }
          $termSearch.find('option').not(':selected').remove();
          for (var _i = 0, _Object$entries = Object.entries(data); _i < _Object$entries.length; _i++) {
            var _Object$entries$_i = yith_wcan_filter_slicedToArray(_Object$entries[_i], 2),
              termId = _Object$entries$_i[0],
              termLabel = _Object$entries$_i[1];
            selected.push(termId);
            $termSearch.append($('<option/>', {
              value: termId,
              text: termLabel
            }));
          }
          $termSearch.val(selected).trigger('change', [true]);
          unblock($container);
        });
        return false;
      });

      // remove all button
      $container.find('.yith-plugin-fw-deselect-all').on('click', function (ev) {
        ev.preventDefault();
        $termSearch.find('option').remove().end().val('').change();
        return false;
      });
    }
  }, {
    key: "initCustomizeTerms",
    value: function initCustomizeTerms() {
      var _this4 = this;
      var $customizeTerms = this.$filter.find('.customize-terms').find('input'),
        $orderBy = this.$filter.find('.order-by');
      $customizeTerms.on('change', function () {
        $orderBy.find('[value="include"]').prop('disabled', !$customizeTerms.is(':checked'));
        $orderBy.removeClass('enhanced').trigger('wc-enhanced-select-init');
        !$orderBy.val() && $orderBy.val('name');
        _this4.afterTermsSelected();
      }).change();
    }
  }, {
    key: "initTaxonomy",
    value: function initTaxonomy() {
      var _this5 = this;
      var $taxonomySelect = this.$filter.find('.taxonomy').first(),
        $filterDesign = this.$filter.find('.filter-design').first();
      $filterDesign.on('change', function () {
        return _this5.customizeTermsNotice();
      });
      $taxonomySelect.on('change', function () {
        var prevValue = $taxonomySelect.data('taxonomy'),
          currentValue = $taxonomySelect.val();
        prevValue !== currentValue && _this5.afterTaxonomyChange();
      });
      setTimeout(function () {
        $taxonomySelect.select2();
      }, 1000);
    }
  }, {
    key: "afterTaxonomyChange",
    value: function afterTaxonomyChange() {
      var $termSearch = this.$filter.find('.term-search').first();

      // clear terms select when taxonomy is changed
      $termSearch.find('option').remove().end().change();

      // handle changes to Customize Terms description
      this.customizeTermsNotice();
    }
  }, {
    key: "customizeTermsNotice",
    value: function customizeTermsNotice() {
      var _taxonomies$taxonomy, _taxonomies$taxonomy2, _taxonomies$taxonomy3;
      var $taxonomySelect = this.$filter.find('.taxonomy').first(),
        $filterDesign = this.$filter.find('.filter-design').first(),
        $customizeTermsWrapper = this.$filter.find('.customize-terms').parent(),
        $customizeTermsRow = $customizeTermsWrapper.closest('.yith-toggle-content-row'),
        $customizeTermsDescription = $customizeTermsWrapper.next('.description'),
        $customizeTerms = $customizeTermsWrapper.find('input'),
        $wcclNotice = $customizeTermsDescription.find('.wccl-notice'),
        $imagesNotice = $customizeTermsDescription.find('.images-notice'),
        taxonomies = $taxonomySelect.data('taxonomies'),
        taxonomy = $taxonomySelect.val(),
        filterDesign = $filterDesign.val();

      // show Colors & Labels notice
      if (!yith_wcan_admin.yith_wccl_enabled || !((_taxonomies$taxonomy = taxonomies[taxonomy]) !== null && _taxonomies$taxonomy !== void 0 && _taxonomies$taxonomy.is_attribute)) {
        $wcclNotice.hide();
      } else {
        $wcclNotice.show();
      }

      // show images notice
      if (!((_taxonomies$taxonomy2 = taxonomies[taxonomy]) !== null && _taxonomies$taxonomy2 !== void 0 && _taxonomies$taxonomy2.supports_images) || 'label' !== filterDesign) {
        $imagesNotice.hide();
      } else {
        $imagesNotice.show();
      }

      // hide option if not needed
      if ('color' === filterDesign && (!yith_wcan_admin.yith_wccl_enabled || !((_taxonomies$taxonomy3 = taxonomies[taxonomy]) !== null && _taxonomies$taxonomy3 !== void 0 && _taxonomies$taxonomy3.is_attribute))) {
        $customizeTerms.prop('checked', true);
        $customizeTermsRow.addClass('disabled');
      } else {
        $customizeTermsRow.removeClass('disabled');
      }
    }
  }, {
    key: "initType",
    value: function initType() {
      var $filterType = this.$filter.find('.filter-type'),
        $filterDesign = this.$filter.find('.filter-design');
      $filterType.on('change', function () {
        var filterType = $filterType.val(),
          designs = Object.entries(yith_wcan_admin.supported_designs),
          unsupported = {
            review: ['color', 'label'],
            price_range: ['color', 'label']
          };
        for (var _i2 = 0, _designs = designs; _i2 < _designs.length; _i2++) {
          var _unsupported$filterTy;
          var _designs$_i = yith_wcan_filter_slicedToArray(_designs[_i2], 2),
            design = _designs$_i[0],
            designName = _designs$_i[1];
          var $opt = $filterDesign.find("[value=\"".concat(design, "\"]"));
          if (unsupported !== null && unsupported !== void 0 && (_unsupported$filterTy = unsupported[filterType]) !== null && _unsupported$filterTy !== void 0 && _unsupported$filterTy.includes(design)) {
            $opt.remove();
            continue;
          }
          if ($opt.length) {
            continue;
          }
          $filterDesign.append($('<option/>', {
            value: design,
            text: designName
          }));
        }
        $filterDesign.change();
      }).change();
    }
  }, {
    key: "initDesign",
    value: function initDesign() {
      var _this6 = this;
      var $filterType = this.$filter.find('.filter-design');
      $filterType.on('change', function () {
        return _this6.updateTermFields();
      }).change();
    }
  }, {
    key: "initCurrencyFields",
    value: function initCurrencyFields() {
      this.$filter.find('[data-currency]').each(function () {
        var $field = $(this),
          $currencySpan = $('<span/>', {
            text: $field.data('currency'),
            "class": 'currency'
          });
        $field.after($currencySpan);
      });
    }
  }, {
    key: "initErrorsHandling",
    value: function initErrorsHandling() {
      var _this7 = this;
      /**
       * Invalid event does not bubble, so we need to handle it for each filter added
       * https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/invalid_event
       */
      this.$filter.find(':input').on('invalid', function (ev) {
        var target = ev.target,
          $target = $(target);
        ev.preventDefault();
        _this7.preset.goToFilter(_this7.$filter, $target).done(function () {
          _this7.addInputValidationMessage($target, target.validationMessage);
        });
      });
      this.$filter.on('change keydown', ':input', function () {
        var $input = $(this);
        if ($input.hasClass('validation-error')) {
          // remove any validation class
          $input.removeClass('validation-error').removeClass('required-field-empty');

          // remove any error message
          $input.next('.validation-message').remove();
        }
      });
    }

    // init actions
  }, {
    key: "initSave",
    value: function initSave() {
      var _this8 = this;
      this.$filter.find('.save').on('click', function () {
        return _this8.maybeSave(), false;
      });
    }
  }, {
    key: "initClone",
    value: function initClone() {
      var _this9 = this;
      this.$filter.find('.clone').on('click', function () {
        return _this9.clone();
      });
    }
  }, {
    key: "initDelete",
    value: function initDelete() {
      var _this10 = this;
      this.$filter.find('.delete').on('click', function () {
        return _this10.maybeDelete();
      });
    }

    // actions
  }, {
    key: "maybeSave",
    value: function maybeSave() {
      var _this11 = this;
      if (!this.validate()) {
        return false;
      }
      this.save().done(function (data) {
        _this11.preset.maybeSetId(data === null || data === void 0 ? void 0 : data.id);
        _this11.close();
      });
    }
  }, {
    key: "save",
    value: function save() {
      var maybeBlock = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      var preset_id = this.preset.getId(),
        filter = this.getData(),
        filter_id = this.getId(),
        $prev_filter = this.$filter.prev('.filter-row'),
        termsPool = this.getTermsPool(),
        prev_filter_id = $prev_filter.length ? $prev_filter.attr('id').replace('filter_', '') : -1;

      // send terms.
      filter.terms = Object.fromEntries(termsPool.map(function (term) {
        return [term.term_id, term];
      }));
      filter.terms_order = termsPool.map(function (term) {
        return term.term_id;
      });
      return ajax.post.call(maybeBlock ? this.$filter : null, 'save_preset_filter', {
        preset: preset_id,
        filter: filter,
        filter_id: filter_id,
        prev_filter_id: prev_filter_id,
        _wpnonce: yith_wcan_admin.nonce.save_preset_filter
      });
    }
  }, {
    key: "clone",
    value: function clone() {
      this.preset.addFilter(this.getData());
    }
  }, {
    key: "maybeDelete",
    value: function maybeDelete() {
      var _this12 = this;
      if (confirm(yith_wcan_admin.messages.confirm_delete)) {
        this["delete"]().then(function () {
          _this12.$filter.remove();
          _this12.preset.afterFilterDelete(_this12);
        });
      }
    }
  }, {
    key: "delete",
    value: function _delete() {
      var preset_id = this.preset.getId();
      if (!preset_id) {
        return Promise.resolve();
      }
      var filter_id = this.getId();
      if (!filter_id) {
        return Promise.resolve();
      }
      return ajax.post.call(this.$filter, 'delete_preset_filter', {
        preset: preset_id,
        filter_id: filter_id,
        _wpnonce: yith_wcan_admin.nonce.delete_preset_filter
      });
    }
  }, {
    key: "validate",
    value: function validate() {
      var layout = this.preset.$layout.find(':checked').val(),
        $title = this.findField('title', false),
        title = $title.val();

      // horizontal layout needs title for each filter
      if ('horizontal' === layout && !title) {
        this.addInputValidationMessage($title, yith_wcan_admin.messages.filter_title_required);
        this.preset.goToFilter(this);
        return false;
      }

      // trigger default browser validation.
      return this.$filter.find(':input').get().reduce(function (valid, node) {
        return valid && node.reportValidity();
      }, true);
    }
  }, {
    key: "updateLayout",
    value: function updateLayout(layout) {
      var $showToggle = this.findField('show_toggle'),
        $tooltips = this.findField('tooltip', false);
      if ('horizontal' === layout) {
        $showToggle === null || $showToggle === void 0 ? void 0 : $showToggle.hide().find(':input').prop('checked', false).val('no').change();
        $tooltips === null || $tooltips === void 0 ? void 0 : $tooltips.parent().hide();
      } else {
        $showToggle === null || $showToggle === void 0 ? void 0 : $showToggle.show();
        $tooltips === null || $tooltips === void 0 ? void 0 : $tooltips.parent().show();
      }
    }

    // data handling
  }, {
    key: "getId",
    value: function getId() {
      return this.id || (this.id = this.$filter.attr('id').replace('filter_', ''));
    }
  }, {
    key: "getRowIndex",
    value: function getRowIndex() {
      return this.$filter.data('item_key');
    }
  }, {
    key: "getData",
    value: function getData() {
      return yith_wcan_filter_objectSpread(yith_wcan_filter_objectSpread({}, serialize(this.$filter, {
        formatName: function formatName(v) {
          return v.replace(/filters\[[0-9]+]\[([a-z_-]+)]/, '$1');
        },
        filterItems: function filterItems(i, v) {
          return !$(v).is('[name*="[terms]"]');
        }
      })), {}, {
        terms: this.getTermsPool()
      });
    }
  }, {
    key: "populate",
    value: function populate(filterData) {
      var row_id = this.getId();
      for (var i in filterData) {
        var value = filterData[i];
        var nameId = 'terms' === i ? "filters_".concat(row_id, "_term_ids") : "filters_".concat(row_id, "_").concat(i),
          $input = this.$filter.find("#".concat(nameId));
        if (!$input.length && 'price_ranges' !== i) {
          continue;
        }
        if ('terms' === i) {
          var _iterator = _createForOfIteratorHelper(value),
            _step;
          try {
            for (_iterator.s(); !(_step = _iterator.n()).done;) {
              var term = _step.value;
              if (!(term !== null && term !== void 0 && term.label)) {
                continue;
              }
              var newOption = $('<option/>', {
                value: term.term_id,
                text: term.label,
                selected: true
              });
              $input.append(newOption);
            }
          } catch (err) {
            _iterator.e(err);
          } finally {
            _iterator.f();
          }
          this.$filter.find('.terms-wrapper').data('terms', value);
          $input.val(value.map(function (term) {
            return term.term_id;
          })).change();
          this.updateTerms(true);
        } else if ('price_ranges' === i) {
          var ranges = value;
          if ('object' !== yith_wcan_filter_typeof(ranges)) {
            continue;
          }
          for (var j in ranges) {
            var range = ranges[j];
            this.addRange(range);
          }
        } else if ($input.is(':checkbox')) {
          $input.prop('checked', value === 'yes').val(value).change();
        } else if ($input.is('[data-type="radio"]')) {
          $input.find(':input').prop('checked', false).filter('[value="' + value + '"]').prop('checked', true).change();
        } else if ('title' === i) {
          $input.val(filterData[i]).keyup();
        } else if ('taxonomy' === i) {
          $input.data('taxonomy', value).val(value);
        } else {
          $input.val(filterData[i]).change();
        }
      }
    }

    // toggle accordions.
  }, {
    key: "isOpen",
    value: function isOpen() {
      return this.$filter.hasClass('filter-row-opened');
    }
  }, {
    key: "open",
    value: function open() {
      // fix title
      this.$toggleTitle.find('.title-arrow').removeClass('yith-icon-arrow-right-alt').addClass('yith-icon-arrow-down-alt');

      // animate content and return promise
      return this.$filter.addClass('filter-row-opened').find('.yith-toggle-content').slideDown().promise();
    }
  }, {
    key: "isClosed",
    value: function isClosed() {
      return !this.isOpen();
    }
  }, {
    key: "close",
    value: function close() {
      var _this13 = this;
      // fix title
      this.$filter.find('.yith-toggle-title').find('.title-arrow').addClass('yith-icon-arrow-right-alt').removeClass('yith-icon-arrow-down-alt');

      // animate content and return promise
      return this.$filter.find('.yith-toggle-content').slideUp(400, function () {
        _this13.$filter.removeClass('filter-row-opened');
      }).promise();
    }

    // term handling
  }, {
    key: "getTermsPool",
    value: function getTermsPool() {
      return _toConsumableArray(this.$filter.find('.terms-wrapper').data('terms').values()).filter(function (term) {
        return !!term;
      });
    }
  }, {
    key: "getTermsToShow",
    value: function getTermsToShow() {
      var termsPool = this.getTermsPool(),
        perPage = parseInt(yith_wcan_admin.terms_per_page);
      if (termsPool && this.termsPaginated && perPage && Object.keys(termsPool).length > perPage) {
        termsPool = termsPool.slice(0, perPage);
      } else {
        this.$filter.find('.show-more-terms').hide();
      }
      return termsPool;
    }
  }, {
    key: "getTermsType",
    value: function getTermsType() {
      var $filterType = this.$filter.find('.filter-design'),
        filterType = $filterType === null || $filterType === void 0 ? void 0 : $filterType.val();
      if ('label' !== filterType && 'color' !== filterType) {
        return 'labels_only';
      } else if ('color' === filterType) {
        return 'complete';
      }
      return 'image_only';
    }
  }, {
    key: "initTerms",
    value: function initTerms() {
      var _this14 = this;
      var $terms = this.$filter.find('.term-box'),
        $orderBy = this.$filter.find('.order-by'),
        $showMore = this.$filter.find('.show-more-terms');
      this.termsPaginated = !!this.$filter.find('.show-more-terms').length;
      var _iterator2 = _createForOfIteratorHelper($terms.get()),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var termNode = _step2.value;
          var $term = $(termNode),
            term = new YITH_WCAN_Filter_Term($term, this);
          this.terms.set(term.getId(), term);
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
      $showMore.on('click', function () {
        return _this14.showMoreTerms();
      });
      $orderBy.on('change', function () {
        var v = $orderBy.val(),
          methodToRun = 'include' === v ? 'initTermsDragDrop' : 'destroyTermsDragDrop';
        _this14[methodToRun]();
      }).change();
      this.updateTermFields();
    }
  }, {
    key: "initTermsDragDrop",
    value: function initTermsDragDrop() {
      var _this15 = this;
      try {
        this.$filter.find('.terms-wrapper').sortable({
          cursor: 'move',
          scrollSensitivity: 40,
          forcePlaceholderSize: true,
          helper: 'clone',
          stop: function stop() {
            var termsPool = _this15.getTermsPool(),
              newPool = [],
              shownOrder = _this15.$filter.find('.term-box').get().map(function (termNode) {
                return parseInt(termNode.dataset.term_id);
              });
            shownOrder.forEach(function (termId) {
              var termIndex = termsPool.findIndex(function (term) {
                return term.term_id === termId;
              });
              newPool.push(termsPool[termIndex]);
            });
            if (_this15.termsPaginated) {
              newPool = newPool.concat(termsPool.slice(yith_wcan_admin.terms_per_page));
            }
            _this15.$filter.find('.terms-wrapper').data('terms', newPool);
          }
        });
      } catch (e) {
        // do nothing.
      }
    }
  }, {
    key: "destroyTermsDragDrop",
    value: function destroyTermsDragDrop() {
      try {
        this.$filter.find('.terms-wrapper').sortable('destroy');
      } catch (e) {
        // do nothing.
      }
    }

    // terms actions.
  }, {
    key: "afterTermsSelected",
    value: function afterTermsSelected() {
      var _this16 = this;
      var ignoreVisibility = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      var $termSearch = this.$filter.find('.term-search'),
        selectedTerms = $termSearch.val().map(function (id) {
          return parseInt(id);
        }),
        //
        termsPool = this.getTermsPool(),
        termType = this.getTermsType(),
        existingTerms = termsPool.map(function (term) {
          return term.term_id;
        }),
        toAdd = selectedTerms.filter(function (termId) {
          return !existingTerms.includes(termId);
        }),
        toRemove = existingTerms.filter(function (termId) {
          return !selectedTerms.includes(termId);
        });
      toRemove.forEach(function (termId) {
        delete termsPool[termsPool.findIndex(function (term) {
          return termId === (term === null || term === void 0 ? void 0 : term.term_id);
        })];
      });
      toAdd.forEach(function (termId) {
        var termLabel = $termSearch.find("[value=\"".concat(termId, "\"]")).text();
        termsPool.push({
          id: _this16.getId(),
          term_id: termId,
          label: termLabel,
          name: termLabel,
          color_1: '#007694',
          color_2: '',
          mode: 'images_only' === termType ? 'image' : 'color'
        });
      });
      if (toRemove.length || toAdd.length) {
        toAdd.length && (this.termsPaginated = false);
        this.$filter.find('.terms-wrapper').data('terms', termsPool);
      }
      this.updateTerms(ignoreVisibility);
    }
  }, {
    key: "afterTermChanged",
    value: function afterTermChanged(term) {
      var termsPool = this.getTermsPool(),
        termId = term.getId(),
        termIndex = termsPool.findIndex(function (_term) {
          return termId === (_term === null || _term === void 0 ? void 0 : _term.term_id);
        });
      termsPool[termIndex] = term.getData();
      this.$filter.find('.terms-wrapper').data('terms', termsPool);
    }
  }, {
    key: "updateTerms",
    value: function updateTerms() {
      var ignoreVisibility = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      var $termsContainer = this.$filter.find('.terms-wrapper');
      if (!ignoreVisibility && !$termsContainer.is(':visible')) {
        return;
      }
      var selectedTerms = this.getTermsToShow(),
        newTerms = new Map(),
        newTermTemplate = wp.template('yith-wcan-filter-term'),
        $existingTerms = $termsContainer.find('.term-box');
      $existingTerms.detach();
      if (selectedTerms) {
        var _iterator3 = _createForOfIteratorHelper(selectedTerms),
          _step3;
        try {
          for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
            var selectedTerm = _step3.value;
            var term = void 0,
              $term = void 0;
            if (this.terms.has(selectedTerm.term_id)) {
              term = this.terms.get(selectedTerm.term_id);
              $term = term.$term;
            } else {
              $term = $(newTermTemplate(selectedTerm));
            }
            $term.length && $termsContainer.append($term);
            term || (term = new YITH_WCAN_Filter_Term($term, this));
            term && newTerms.set(selectedTerm.term_id, term);
          }
        } catch (err) {
          _iterator3.e(err);
        } finally {
          _iterator3.f();
        }
      }
      this.terms = newTerms;
      this.updateTermFields();
    }
  }, {
    key: "showMoreTerms",
    value: function showMoreTerms() {
      this.termsPaginated = false;
      this.updateTerms();
    }
  }, {
    key: "updateTermFields",
    value: function updateTermFields() {
      var type = this.getTermsType();
      _toConsumableArray(this.terms.values()).map(function (term) {
        return term.updateFields(type);
      });
    }
  }, {
    key: "confirmAddAllTerms",
    value: function confirmAddAllTerms() {
      var _details$v, _details$v2;
      var $taxonomySelect = this.$filter.find('.taxonomy').first(),
        v = $taxonomySelect.val(),
        details = $taxonomySelect.data('taxonomies'),
        message = yith_wcan_admin.messages.confirm_add_all_terms;
      if ((_details$v = details[v]) !== null && _details$v !== void 0 && _details$v.terms_count && ((_details$v2 = details[v]) === null || _details$v2 === void 0 ? void 0 : _details$v2.terms_count) > 1) {
        var _details$v3;
        return confirm(message.replace('%s', (_details$v3 = details[v]) === null || _details$v3 === void 0 ? void 0 : _details$v3.terms_count));
      }
      return true;
    }

    // ranges handling.
  }, {
    key: "initRanges",
    value: function initRanges() {
      var $ranges = this.$filter.find('.range-box');
      this.initAddRange();
      this.initRangesDragDrop();
      var _iterator4 = _createForOfIteratorHelper($ranges.get()),
        _step4;
      try {
        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
          var rangeNode = _step4.value;
          var $range = $(rangeNode),
            range = new yith_wcan_filter_range_YITH_WCAN_Filter($range, this);
          this.ranges.set(range.getId(), range);
        }
      } catch (err) {
        _iterator4.e(err);
      } finally {
        _iterator4.f();
      }
      this.initRangesPosition();
    }
  }, {
    key: "initAddRange",
    value: function initAddRange() {
      var _this17 = this;
      var $addRange = this.$filter.find('.add-price-range');
      $addRange.on('click', function (ev) {
        ev.preventDefault();
        _this17.addRange();
        _this17.initRangesPosition();
      });
    }
  }, {
    key: "initRangesPosition",
    value: function initRangesPosition() {
      var _iterator5 = _createForOfIteratorHelper(this.ranges.values()),
        _step5;
      try {
        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
          var range = _step5.value;
          range.toggleUnlimited(range.$range.is(':last-child'));
        }
      } catch (err) {
        _iterator5.e(err);
      } finally {
        _iterator5.f();
      }
    }
  }, {
    key: "initRangesDragDrop",
    value: function initRangesDragDrop() {
      var _this18 = this;
      var $rangesWrapper = this.$filter.find('.ranges-wrapper');
      $rangesWrapper.sortable({
        cursor: 'move',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: 'clone',
        stop: function stop() {
          var replaceIndex = function replaceIndex(prevIndex, currentIndex) {
            return function (i, attr) {
              return attr.replace(prevIndex, currentIndex);
            };
          };
          var _iterator6 = _createForOfIteratorHelper(_this18.ranges.values()),
            _step6;
          try {
            for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
              var range = _step6.value;
              var $range = range.$range,
                currentIndex = $range.index(),
                prevIndex = $range.data('range_id'),
                replacer = replaceIndex(prevIndex, currentIndex);
              $range.data('range_id', currentIndex).find(':input').attr('name', replacer).attr('id', replacer);
            }
          } catch (err) {
            _iterator6.e(err);
          } finally {
            _iterator6.f();
          }
          _this18.initRangesPosition();
        }
      });
    }

    // ranges actions.
  }, {
    key: "addRange",
    value: function addRange(data, index) {
      var newRangeTemplate = wp.template('yith-wcan-filter-range'),
        newRange = newRangeTemplate({
          id: this.getRowIndex(),
          range_id: index || this.getNextRangeIndex(),
          min: 0,
          max: 0
        }),
        $newRange = $(newRange),
        range = new yith_wcan_filter_range_YITH_WCAN_Filter($newRange, this);
      data && range.populate(data);
      this.ranges.set(range.getId(), range);
      this.$filter.find('.ranges-wrapper').append($newRange);
      return $newRange;
    }
  }, {
    key: "afterRangeDelete",
    value: function afterRangeDelete(range) {
      this.ranges["delete"](range.getId());
      this.initRangesPosition();
    }

    // utils
  }, {
    key: "findField",
    value: function findField(field) {
      var returnContainer = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      var $field;
      switch (field) {
        case 'terms_options':
          $field = this.$filter.find('.terms-wrapper');
          break;
        case 'price_ranges':
          $field = this.$filter.find('.ranges-wrapper');
          break;
        default:
          $field = this.$filter.find(':input[name*="[' + field + ']"]');
          break;
      }
      if (!$field.length) {
        return null;
      }
      if (returnContainer) {
        return $field.closest('.yith-toggle-content-row');
      }
      return $field;
    }
  }, {
    key: "getNextRangeIndex",
    value: function getNextRangeIndex() {
      var $rangeWrapper = this.$filter.find('.ranges-wrapper');
      var currentIndex = $rangeWrapper.data('index'),
        nextIndex = 0;
      if (!currentIndex) {
        currentIndex = _toConsumableArray(this.ranges.values()).reduce(function (a, range) {
          return Math.max(a, range.getId());
        }, 0);
      }
      nextIndex = ++currentIndex;
      $rangeWrapper.data('index', nextIndex);
      return nextIndex;
    }
  }, {
    key: "addInputValidationMessage",
    value: function addInputValidationMessage($input, message) {
      var $message = $('<span/>', {
        "class": 'validation-message',
        text: message
      });
      $input.addClass('required-field-empty').addClass('validation-error').next('.validation-message').remove().end().after($message);
    }
  }]);
  return YITH_WCAN_Filter;
}();

;// CONCATENATED MODULE: ./assets/js/admin-filters/modules/yith-wcan-preset.js


/* global yith_wcan_admin, ajaxurl */
function yith_wcan_preset_typeof(obj) { "@babel/helpers - typeof"; return yith_wcan_preset_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, yith_wcan_preset_typeof(obj); }
function yith_wcan_preset_createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = yith_wcan_preset_unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function yith_wcan_preset_toConsumableArray(arr) { return yith_wcan_preset_arrayWithoutHoles(arr) || yith_wcan_preset_iterableToArray(arr) || yith_wcan_preset_unsupportedIterableToArray(arr) || yith_wcan_preset_nonIterableSpread(); }
function yith_wcan_preset_nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function yith_wcan_preset_unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return yith_wcan_preset_arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return yith_wcan_preset_arrayLikeToArray(o, minLen); }
function yith_wcan_preset_iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function yith_wcan_preset_arrayWithoutHoles(arr) { if (Array.isArray(arr)) return yith_wcan_preset_arrayLikeToArray(arr); }
function yith_wcan_preset_arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function yith_wcan_preset_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function yith_wcan_preset_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, yith_wcan_preset_toPropertyKey(descriptor.key), descriptor); } }
function yith_wcan_preset_createClass(Constructor, protoProps, staticProps) { if (protoProps) yith_wcan_preset_defineProperties(Constructor.prototype, protoProps); if (staticProps) yith_wcan_preset_defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function yith_wcan_preset_defineProperty(obj, key, value) { key = yith_wcan_preset_toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function yith_wcan_preset_toPropertyKey(arg) { var key = yith_wcan_preset_toPrimitive(arg, "string"); return yith_wcan_preset_typeof(key) === "symbol" ? key : String(key); }
function yith_wcan_preset_toPrimitive(input, hint) { if (yith_wcan_preset_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (yith_wcan_preset_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var YITH_WCAN_Preset = /*#__PURE__*/function () {
  function YITH_WCAN_Preset($wrapper) {
    yith_wcan_preset_classCallCheck(this, YITH_WCAN_Preset);
    // status
    yith_wcan_preset_defineProperty(this, "rowIndex", 0);
    // dom objects
    yith_wcan_preset_defineProperty(this, "$wrapper", void 0);
    yith_wcan_preset_defineProperty(this, "$mainAddNewFilterButton", void 0);
    yith_wcan_preset_defineProperty(this, "$loadMoreFiltersButtons", void 0);
    yith_wcan_preset_defineProperty(this, "$filtersContainer", void 0);
    yith_wcan_preset_defineProperty(this, "$layout", void 0);
    yith_wcan_preset_defineProperty(this, "$page", void 0);
    // filters
    yith_wcan_preset_defineProperty(this, "filters", new Map());
    if (!$wrapper.length) {
      return;
    }
    this.$wrapper = $wrapper;
    this.$mainAddNewFilterButton = this.$wrapper.find('#add_new_filter');
    this.$filtersContainer = this.$wrapper.find('.preset-filters');
    this.$layout = this.$wrapper.find('#preset_layout');
    this.$page = this.$wrapper.find('#paged');
    this.$loadMoreFiltersButtons = this.$wrapper.find('.load-more-filters');
    this.init();
  }

  // init object
  yith_wcan_preset_createClass(YITH_WCAN_Preset, [{
    key: "init",
    value: function init() {
      this.initFilters();
      this.initAddFilter();
      this.initLoadMoreFilters();
      this.initLayout();
      this.initSubmit();
    }

    // general init
  }, {
    key: "initAddFilter",
    value: function initAddFilter() {
      var _this = this;
      this.$wrapper.find('.add-new-filter').on('click', function () {
        _this.addFilter();
        return false;
      });
    }
  }, {
    key: "initLoadMoreFilters",
    value: function initLoadMoreFilters() {
      var _this2 = this;
      this.$loadMoreFiltersButtons.on('click', function () {
        _this2.loadMoreFilters();
        return false;
      });
    }
  }, {
    key: "initSubmit",
    value: function initSubmit() {
      var _this3 = this;
      this.$wrapper.find('#submit').on('click', function (ev) {
        if (!_this3.$wrapper.find('form').get(0).reportValidity()) {
          return false;
        }
        if (!_this3.validateFilters()) {
          return false;
        }
        ev.preventDefault();
        block(_this3.$wrapper);
        return ajax.post.call(null, 'save_preset', _this3.getData()).then(function (data) {
          _this3.maybeSetId(data === null || data === void 0 ? void 0 : data.id);
          var promises = yith_wcan_preset_toConsumableArray(_this3.filters.values()).map(function (filter) {
            return filter.save(false);
          });
          return Promise.all(promises);
        }).then(function () {
          return window.location = _this3.getUrl();
        });
      });
    }
  }, {
    key: "initLayout",
    value: function initLayout() {
      var _this4 = this;
      this.$layout.on('change', 'input', function () {
        return _this4.afterLayoutChange();
      }).find('input').first().change();
    }
  }, {
    key: "initFilters",
    value: function initFilters() {
      // init filter drag & drop
      this.initFiltersDragDrop();
      var $filters = this.$filtersContainer.find('.filter-row');

      // filter specific init
      var _iterator = yith_wcan_preset_createForOfIteratorHelper($filters.get()),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var filterNode = _step.value;
          var $filter = $(filterNode),
            filter = new YITH_WCAN_Filter($filter, this);
          this.filters.set(filter.getId(), filter);
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
    }
  }, {
    key: "initFiltersDragDrop",
    value: function initFiltersDragDrop() {
      this.$filtersContainer.sortable({
        cursor: 'move',
        handle: '.yith-toggle-title',
        axis: 'y',
        scrollSensitivity: 40,
        forcePlaceholderSize: true
      });
    }
  }, {
    key: "afterLayoutChange",
    value: function afterLayoutChange() {
      var layout = this.$layout.find(':checked').val();
      var _iterator2 = yith_wcan_preset_createForOfIteratorHelper(this.filters.values()),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var filter = _step2.value;
          filter.updateLayout(layout);
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
    }

    // filter actions
  }, {
    key: "filterExists",
    value: function filterExists(filterId) {
      return this.filters.has(filterId);
    }
  }, {
    key: "addFilter",
    value: function addFilter(data, index) {
      var newFilterTemplate = wp.template('yith-wcan-filter'),
        newFilter = newFilterTemplate({
          id: index || this.nextRowIndex(),
          key: this.filters.size
        }),
        $newFilter = $(newFilter);
      this.$filtersContainer.append($newFilter);
      var filter = new YITH_WCAN_Filter($newFilter, this);
      data && filter.populate(data);
      this.afterFilterAdd(filter);
      return $newFilter;
    }
  }, {
    key: "afterFilterAdd",
    value: function afterFilterAdd(filter) {
      var _this5 = this;
      this.filters.set(filter.getId(), filter);
      this.closeAllFilters().then(function () {
        return _this5.goToFilter(filter);
      });
      this.updateRowIndex();
      this.maybeHideEmptyBox();
      this.$mainAddNewFilterButton.show();
    }
  }, {
    key: "afterFilterDelete",
    value: function afterFilterDelete(filter) {
      this.filters["delete"](filter.getId());
      this.maybeShowEmptyBox();
      if (!this.filters.size) {
        this.$mainAddNewFilterButton.hide();
      }
    }
  }, {
    key: "closeAllFilters",
    value: function closeAllFilters() {
      var promises = yith_wcan_preset_toConsumableArray(this.filters.values()).map(function (filter) {
        return filter.close();
      });
      return Promise.all(promises);
    }
  }, {
    key: "loadMoreFilters",
    value: function loadMoreFilters() {
      var _this6 = this;
      var page = this.$page.val();
      ajax.get.call(this.$loadMoreFiltersButtons, 'load_more_filters', {
        preset: this.getId(),
        page: ++page,
        _wpnonce: yith_wcan_admin.nonce.load_more_filters
      }).done(function (data) {
        if (!data) {
          return;
        }
        if (data.filters) {
          var _iterator3 = yith_wcan_preset_createForOfIteratorHelper(data.filters),
            _step3;
          try {
            for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
              var filterData = _step3.value;
              var filterId = filterData.id;
              if (_this6.filterExists(filterId)) {
                continue;
              }
              _this6.addFilter(filterData, filterId);
            }
          } catch (err) {
            _iterator3.e(err);
          } finally {
            _iterator3.f();
          }
        }
        if (!data.has_more) {
          _this6.$loadMoreFiltersButtons.remove();
          _this6.$page.remove();
          _this6.$page = null;
        } else {
          _this6.$page.val(page);
        }
      });
    }
  }, {
    key: "validateFilters",
    value: function validateFilters() {
      return yith_wcan_preset_toConsumableArray(this.filters.values()).reduce(function (valid, filter) {
        return valid && filter.validate();
      }, true);
    }
  }, {
    key: "goToFilter",
    value: function goToFilter(filter, $target) {
      if (filter.isOpen()) {
        $target = $target || filter.$filter;
        return $('html, body').stop(true).animate({
          scrollTop: $target.offset().top - 100
        }).promise();
      }
      return $('html, body').stop(true).animate({
        scrollTop: filter.$filter.offset().top - 100
      }).promise().done(function () {
        filter.open();
        if (!$target || !$target.length) {
          return;
        }
        $('html, body').animate({
          scrollTop: $target.offset().top - 100
        });
      });
    }

    // indexes
  }, {
    key: "getId",
    value: function getId() {
      return this.$wrapper.find('#preset_id').val();
    }
  }, {
    key: "getUrl",
    value: function getUrl() {
      var currentUrl = new URL(window.location),
        id = this.getId();
      if (id) {
        currentUrl.searchParams.set('action', 'edit');
        currentUrl.searchParams.set('preset', id);
      }
      return currentUrl.toString();
    }
  }, {
    key: "maybeSetId",
    value: function maybeSetId(newId) {
      if (this.getId() || !newId) {
        return;
      }
      this.$wrapper.find('#preset_id').val(newId);
    }
  }, {
    key: "updateRowIndex",
    value: function updateRowIndex() {
      var maxIndex = this.$filtersContainer.data('max-filter-id'),
        maxKey = Math.max.apply(Math, yith_wcan_preset_toConsumableArray(this.filters.keys()));
      this.rowIndex = Math.max(maxIndex, maxKey);
    }
  }, {
    key: "nextRowIndex",
    value: function nextRowIndex() {
      if (!this.rowIndex) {
        this.updateRowIndex();
      }
      return ++this.rowIndex;
    }
  }, {
    key: "currentRowIndex",
    value: function currentRowIndex() {
      if (!this.rowIndex) {
        this.updateRowIndex();
      }
      return this.rowIndex;
    }

    // utils
  }, {
    key: "getData",
    value: function getData() {
      return serialize(this.$wrapper, {
        filterItems: function filterItems(i, v) {
          return !$(v).is('[name^="filters"]');
        }
      });
    }
  }, {
    key: "maybeShowEmptyBox",
    value: function maybeShowEmptyBox() {
      var emptyBox = this.$filtersContainer.children('.yith-wcan-admin-no-post');
      if (emptyBox.length && !emptyBox.is(':visible') && !this.filters.size) {
        emptyBox.show();
      }
    }
  }, {
    key: "maybeHideEmptyBox",
    value: function maybeHideEmptyBox() {
      var emptyBox = this.$filtersContainer.children('.yith-wcan-admin-no-post');
      if (emptyBox.length && emptyBox.is(':visible') && this.filters.size) {
        emptyBox.hide();
      }
    }
  }], [{
    key: "getRowIndex",
    value: function getRowIndex($row) {
      var index = $row.data('item_key');
      return index ? parseInt(index) : 0;
    }
  }]);
  return YITH_WCAN_Preset;
}();

;// CONCATENATED MODULE: ./assets/js/admin-filters/index.js


/* global jQuery */

jQuery(function ($) {
  var $wrapper = $('#yith_wcan_panel_filter-preset-edit');
  if (!$wrapper.length) {
    return;
  }

  // Init filters handling
  $(document).on('yith_wcan_filters_init', function () {
    new YITH_WCAN_Preset($wrapper);
  }).trigger('yith_wcan_filters_init');
});
/******/ })()
;
//# sourceMappingURL=yith-wcan-admin-filters.js.map