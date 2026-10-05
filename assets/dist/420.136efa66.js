"use strict";
/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
(self["webpackChunksupportbay_portal"] = self["webpackChunksupportbay_portal"] || []).push([[420],{

/***/ 40886
/*!***************************************!*\
  !*** ./assets/src/react/core/date.ts ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   formatDate: () => (/* binding */ formatDate),\n/* harmony export */   formatDateTime: () => (/* binding */ formatDateTime)\n/* harmony export */ });\nfunction formatDate(value) {\n    if (!value)\n        return 'Not available';\n    return new Intl.DateTimeFormat(undefined, {\n        month: 'short',\n        day: 'numeric',\n        year: 'numeric',\n    }).format(new Date(value.replace(' ', 'T')));\n}\nfunction formatDateTime(value) {\n    return new Intl.DateTimeFormat(undefined, {\n        month: 'short',\n        day: 'numeric',\n        hour: 'numeric',\n        minute: '2-digit',\n    }).format(new Date(value.replace(' ', 'T')));\n}\n\n\n//# sourceURL=webpack://supportbay-portal/./assets/src/react/core/date.ts?\n}");

/***/ },

/***/ 68420
/*!**************************************************************!*\
  !*** ./assets/src/react/modules/purchases/PurchasesPage.tsx ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   PurchasesPage: () => (/* binding */ PurchasesPage)\n/* harmony export */ });\n/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ 74848);\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ 96540);\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _api_portal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../api/portal */ 9599);\n/* harmony import */ var _core_date__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../core/date */ 40886);\n/* harmony import */ var _shared_components_Preloader__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../shared/components/Preloader */ 25787);\n\n\n\n\n\nfunction PurchasesPage() {\n    const [purchases, setPurchases] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);\n    (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {\n        _api_portal__WEBPACK_IMPORTED_MODULE_2__.portalApi.verifications().then(setPurchases);\n    }, []);\n    return ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"section\", { className: \"sbay-page\", children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"header\", { className: \"sbay-page__header\", children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"div\", { children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { className: \"sbay-kicker\", children: \"Product access\" }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"h1\", { children: \"Verified purchases\" }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"p\", { children: \"Your connected products, licenses, and support coverage.\" })] }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"span\", { className: \"sbay-page__total\", children: [purchases?.length ?? 0, \" connected\"] })] }), !purchases ? ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_shared_components_Preloader__WEBPACK_IMPORTED_MODULE_4__.Preloader, { label: \"Loading verified purchases\\u2026\" })) : purchases.length === 0 ? ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"p\", { className: \"sbay-empty\", children: \"No verified purchases are connected yet.\" })) : ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"div\", { className: \"sbay-purchase-grid\", children: purchases.map((purchase) => ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"article\", { children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"div\", { className: \"sbay-purchase-card__top\", children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { className: \"sbay-product-mark\", children: (purchase.product_name ?? 'P').charAt(0) }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { className: \"sbay-verified\", children: purchase.status })] }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { className: \"sbay-kicker\", children: purchase.provider }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"h2\", { children: purchase.product_name ?? 'Verified product' }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"p\", { children: purchase.license_type ?? 'License information unavailable' }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"dl\", { children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"div\", { children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"dt\", { children: \"Purchased\" }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"dd\", { children: (0,_core_date__WEBPACK_IMPORTED_MODULE_3__.formatDate)(purchase.purchased_at) })] }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"div\", { children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"dt\", { children: \"Support until\" }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"dd\", { children: (0,_core_date__WEBPACK_IMPORTED_MODULE_3__.formatDate)(purchase.support_expires_at) })] })] })] }, purchase.id))) }))] }));\n}\n\n\n//# sourceURL=webpack://supportbay-portal/./assets/src/react/modules/purchases/PurchasesPage.tsx?\n}");

/***/ },

/***/ 25787
/*!****************************************************!*\
  !*** ./assets/src/shared/components/Preloader.tsx ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   Preloader: () => (/* binding */ Preloader)\n/* harmony export */ });\n/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ 74848);\n\nfunction Preloader({ label = 'Loading…', compact = false }) {\n    return ((0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(\"div\", { className: `sbay-preloader${compact ? ' is-compact' : ''}`, role: \"status\", \"aria-live\": \"polite\", children: [(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { className: \"sbay-preloader__spinner\", \"aria-hidden\": \"true\" }), (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(\"span\", { children: label })] }));\n}\n\n\n//# sourceURL=webpack://supportbay-portal/./assets/src/shared/components/Preloader.tsx?\n}");

/***/ }

}]);