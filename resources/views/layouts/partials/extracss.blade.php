
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style>
    body {transition: opacity ease-in 0.2s;}
    body[unresolved] {opacity: 0;display: block;overflow: hidden;position: relative;}
  </style>
  <style type="text/css">
    .swal-icon--error {border-color: #f27474;-webkit-animation: animateErrorIcon 0.5s;animation: animateErrorIcon 0.5s;}
    .swal-icon--error__x-mark {position: relative;display: block;-webkit-animation: animateXMark 0.5s;animation: animateXMark 0.5s;}
    .swal-icon--error__line {position: absolute;height: 5px;width: 47px;background-color: #f27474;display: block;top: 37px;border-radius: 2px;}
    .swal-icon--error__line--left {-webkit-transform: rotate(45deg);transform: rotate(45deg);left: 17px;}
    .swal-icon--error__line--right {-webkit-transform: rotate(-45deg);transform: rotate(-45deg);right: 16px;}
    @-webkit-keyframes animateErrorIcon {
      0% {
        -webkit-transform: rotateX(100deg);
        transform: rotateX(100deg);
        opacity: 0;
      }
      to {
        -webkit-transform: rotateX(0deg);
        transform: rotateX(0deg);
        opacity: 1;
      }
    }
    @keyframes animateErrorIcon {
      0% {
        -webkit-transform: rotateX(100deg);
        transform: rotateX(100deg);
        opacity: 0;
      }
      to {
        -webkit-transform: rotateX(0deg);
        transform: rotateX(0deg);
        opacity: 1;
      }
    }
    @-webkit-keyframes animateXMark {
      0% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      50% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      80% {
        -webkit-transform: scale(1.15);
        transform: scale(1.15);
        margin-top: -6px;
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
        margin-top: 0;
        opacity: 1;
      }
    }
    @keyframes animateXMark {
      0% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      50% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      80% {
        -webkit-transform: scale(1.15);
        transform: scale(1.15);
        margin-top: -6px;
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
        margin-top: 0;
        opacity: 1;
      }
    }
    .swal-icon--warning {border-color: #f8bb86;-webkit-animation: pulseWarning 0.75s infinite alternate;animation: pulseWarning 0.75s infinite alternate;}
    .swal-icon--warning__body {width: 5px;height: 47px;top: 10px;border-radius: 2px;margin-left: -2px;}
    .swal-icon--warning__body,
    .swal-icon--warning__dot {position: absolute;left: 50%;background-color: #f8bb86;}
    .swal-icon--warning__dot {width: 7px;height: 7px;border-radius: 50%;margin-left: -4px;bottom: -11px;}
    @-webkit-keyframes pulseWarning {
      0% {
        border-color: #f8d486;
      }
      to {
        border-color: #f8bb86;
      }
    }
    @keyframes pulseWarning {
      0% {
        border-color: #f8d486;
      }
      to {
        border-color: #f8bb86;
      }
    }
    .swal-icon--success {border-color: #a5dc86;}
    .swal-icon--success:after,
    .swal-icon--success:before {content: "";border-radius: 50%;position: absolute;width: 60px;height: 120px;background: #fff;-webkit-transform: rotate(45deg);transform: rotate(45deg);}
    .swal-icon--success:before {border-radius: 120px 0 0 120px;top: -7px;left: -33px;-webkit-transform: rotate(-45deg);transform: rotate(-45deg);-webkit-transform-origin: 60px 60px;transform-origin: 60px 60px;}
    .swal-icon--success:after {border-radius: 0 120px 120px 0;top: -11px;left: 30px;-webkit-transform: rotate(-45deg);transform: rotate(-45deg);-webkit-transform-origin: 0 60px;transform-origin: 0 60px;-webkit-animation: rotatePlaceholder 4.25s ease-in;animation: rotatePlaceholder 4.25s ease-in;}
    .swal-icon--success__ring {width: 80px;height: 80px;border: 4px solid hsla(98, 55%, 69%, 0.2);border-radius: 50%;box-sizing: content-box;position: absolute;left: -4px;top: -4px;z-index: 2;}
    .swal-icon--success__hide-corners {width: 5px;height: 90px;background-color: #fff;padding: 1px;position: absolute;left: 28px;top: 8px;z-index: 1;-webkit-transform: rotate(-45deg);transform: rotate(-45deg);}
    .swal-icon--success__line {height: 5px;background-color: #a5dc86;display: block;border-radius: 2px;position: absolute;z-index: 2;}
    .swal-icon--success__line--tip {width: 25px;left: 14px;top: 46px;-webkit-transform: rotate(45deg);transform: rotate(45deg);-webkit-animation: animateSuccessTip 0.75s;animation: animateSuccessTip 0.75s;}
    .swal-icon--success__line--long {width: 47px;right: 8px;top: 38px;-webkit-transform: rotate(-45deg);transform: rotate(-45deg);-webkit-animation: animateSuccessLong 0.75s;animation: animateSuccessLong 0.75s;}
    @-webkit-keyframes rotatePlaceholder {
      0% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      5% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      12% {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
      to {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
    }
    @keyframes rotatePlaceholder {
      0% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      5% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      12% {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
      to {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
    }
    @-webkit-keyframes animateSuccessTip {
      0% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      54% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      70% {
        width: 50px;
        left: -8px;
        top: 37px;
      }
      84% {
        width: 17px;
        left: 21px;
        top: 48px;
      }
      to {
        width: 25px;
        left: 14px;
        top: 45px;
      }
    }
    @keyframes animateSuccessTip {
      0% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      54% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      70% {
        width: 50px;
        left: -8px;
        top: 37px;
      }
      84% {
        width: 17px;
        left: 21px;
        top: 48px;
      }
      to {
        width: 25px;
        left: 14px;
        top: 45px;
      }
    }
    @-webkit-keyframes animateSuccessLong {
      0% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      65% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      84% {
        width: 55px;
        right: 0;
        top: 35px;
      }
      to {
        width: 47px;
        right: 8px;
        top: 38px;
      }
    }
    @keyframes animateSuccessLong {
      0% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      65% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      84% {
        width: 55px;
        right: 0;
        top: 35px;
      }
      to {
        width: 47px;
        right: 8px;
        top: 38px;
      }
    }
    .swal-icon--info {border-color: #c9dae1;}
    .swal-icon--info:before {width: 5px;height: 29px;bottom: 17px;border-radius: 2px;margin-left: -2px;}
    .swal-icon--info:after,
    .swal-icon--info:before {content: "";position: absolute;left: 50%;background-color: #c9dae1;}
    .swal-icon--info:after {width: 7px;height: 7px;border-radius: 50%;margin-left: -3px;top: 19px;}
    .swal-icon {width: 80px;height: 80px;border-width: 4px;border-style: solid;border-radius: 50%;padding: 0;position: relative;box-sizing: content-box;margin: 20px auto;}
    .swal-icon:first-child {margin-top: 32px;}
    .swal-icon--custom {width: auto;height: auto;max-width: 100%;border: none;border-radius: 0;}
    .swal-icon img {max-width: 100%;max-height: 100%;}
    .swal-title {font-weight: 600;text-transform: none;position: relative;display: block;padding: 13px 16px;font-size: 27px;line-height: normal;text-align: center;margin-bottom: 0;}html[data-vspos-theme="dark"] .swal-title{color: rgba(0, 0, 0, 0.65);}
    .swal-title:first-child {margin-top: 26px;}
    .swal-title:not(:first-child) {padding-bottom: 0;}
    .swal-title:not(:last-child) {margin-bottom: 13px;}
    .swal-text {font-size: 16px;position: relative;float: none;line-height: normal;vertical-align: top;text-align: left;display: inline-block;margin: 0;padding: 0 10px;font-weight: 400;max-width: calc(100% - 20px);overflow-wrap: break-word;box-sizing: border-box;}html[data-vspos-theme="dark"] .swal-text{color: rgba(0, 0, 0, 0.64);}
    .swal-text:first-child {margin-top: 45px;}
    .swal-text:last-child {margin-bottom: 45px;}
    .swal-footer {text-align: right;padding-top: 13px;margin-top: 13px;padding: 13px 16px;border-radius: inherit;border-top-left-radius: 0;border-top-right-radius: 0;}
    .swal-button-container {margin: 5px;display: inline-block;position: relative;}
    .swal-button {background-color: #7cd1f9;color: #fff;border: none;box-shadow: none;border-radius: 5px;font-weight: 600;font-size: 14px;padding: 10px 24px;margin: 0;cursor: pointer;}
    .swal-button[not:disabled]:hover {background-color: #78cbf2;}
    .swal-button:active {background-color: #70bce0;}
    .swal-button:focus {outline: none;box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(43, 114, 165, 0.29);}
    .swal-button[disabled] {opacity: 0.5;cursor: default;}
    .swal-button::-moz-focus-inner {border: 0;}
    .swal-button--cancel {color: #555;background-color: #efefef;}
    .swal-button--cancel[not:disabled]:hover {background-color: #e8e8e8;}
    .swal-button--cancel:active {background-color: #d7d7d7;}
    .swal-button--cancel:focus {box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(116, 136, 150, 0.29);}
    .swal-button--danger {background-color: #e64942;}
    .swal-button--danger[not:disabled]:hover {background-color: #df4740;}
    .swal-button--danger:active {background-color: #cf423b;}
    .swal-button--danger:focus {box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(165, 43, 43, 0.29);}
    .swal-content {padding: 0 20px;margin-top: 20px;font-size: medium;}
    .swal-content:last-child {margin-bottom: 20px;}
    .swal-content__input,
    .swal-content__textarea {-webkit-appearance: none;border: none;font-size: 14px;display: block;box-sizing: border-box;width: 100%;padding: 10px 13px;border-radius: 2px;transition: border-color 0.2s;}html[data-vspos-theme="dark"] .swal-content__input,
html[data-vspos-theme="dark"] .swal-content__textarea{background-color: #fff;border: 1px solid rgba(0, 0, 0, 0.14);}
    .swal-content__input:focus,
    .swal-content__textarea:focus {outline: none;border-color: #6db8ff;}
    .swal-content__textarea {resize: vertical;}
    .swal-button--loading {color: transparent;}
    .swal-button--loading ~ .swal-button__loader {opacity: 1;}
    .swal-button__loader {position: absolute;height: auto;width: 43px;z-index: 2;left: 50%;top: 50%;-webkit-transform: translateX(-50%) translateY(-50%);transform: translateX(-50%) translateY(-50%);text-align: center;pointer-events: none;opacity: 0;}
    .swal-button__loader div {display: inline-block;float: none;vertical-align: baseline;width: 9px;height: 9px;padding: 0;border: none;margin: 2px;opacity: 0.4;border-radius: 7px;background-color: hsla(0, 0%, 100%, 0.9);transition: background 0.2s;-webkit-animation: swal-loading-anim 1s infinite;animation: swal-loading-anim 1s infinite;}
    .swal-button__loader div:nth-child(3n + 2) {-webkit-animation-delay: 0.15s;animation-delay: 0.15s;}
    .swal-button__loader div:nth-child(3n + 3) {-webkit-animation-delay: 0.3s;animation-delay: 0.3s;}
    @-webkit-keyframes swal-loading-anim {
      0% {
        opacity: 0.4;
      }
      20% {
        opacity: 0.4;
      }
      50% {
        opacity: 1;
      }
      to {
        opacity: 0.4;
      }
    }
    @keyframes swal-loading-anim {
      0% {
        opacity: 0.4;
      }
      20% {
        opacity: 0.4;
      }
      50% {
        opacity: 1;
      }
      to {
        opacity: 0.4;
      }
    }
    .swal-overlay {position: fixed;top: 0;bottom: 0;left: 0;right: 0;text-align: center;font-size: 0;overflow-y: auto;z-index: 10000;pointer-events: none;opacity: 0;transition: opacity 0.3s;}html[data-vspos-theme="dark"] .swal-overlay{background-color: rgba(0, 0, 0, 0.4);}
    .swal-overlay:before {content: " ";display: inline-block;vertical-align: middle;height: 100%;}
    .swal-overlay--show-modal {opacity: 1;pointer-events: auto;}
    .swal-overlay--show-modal .swal-modal {opacity: 1;pointer-events: auto;box-sizing: border-box;-webkit-animation: showSweetAlert 0.3s;animation: showSweetAlert 0.3s;will-change: transform;}
    .swal-modal {width: 478px;opacity: 0;pointer-events: none;background-color: #fff;text-align: center;border-radius: 5px;position: static;margin: 20px auto;display: inline-block;vertical-align: middle;-webkit-transform: scale(1);transform: scale(1);-webkit-transform-origin: 50% 50%;transform-origin: 50% 50%;z-index: 10001;transition: opacity 0.2s, -webkit-transform 0.3s;transition: transform 0.3s, opacity 0.2s;transition: transform 0.3s, opacity 0.2s, -webkit-transform 0.3s;}
    @media (max-width: 500px) {
      .swal-modal {width: calc(100% - 20px);}
    }
    @-webkit-keyframes showSweetAlert {
      0% {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
      1% {
        -webkit-transform: scale(0.5);
        transform: scale(0.5);
      }
      45% {
        -webkit-transform: scale(1.05);
        transform: scale(1.05);
      }
      80% {
        -webkit-transform: scale(0.95);
        transform: scale(0.95);
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
    }
    @keyframes showSweetAlert {
      0% {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
      1% {
        -webkit-transform: scale(0.5);
        transform: scale(0.5);
      }
      45% {
        -webkit-transform: scale(1.05);
        transform: scale(1.05);
      }
      80% {
        -webkit-transform: scale(0.95);
        transform: scale(0.95);
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
    }
  </style>
  <style>
    .action-link[data-v-1552a5b6] {cursor: pointer;}
  </style>
  <style>
    .action-link[data-v-397d14ca] {cursor: pointer;}
  </style>
  <style>
    .action-link[data-v-49962cc0] {cursor: pointer;}
  </style>

  {{-- ====================================================
       VSOFT POS — Global Modern UI Override
       Transforms AdminLTE boxes/panels/tables to modern cards
       Mobile-first, clean, professional
  ====================================================== --}}
  <style>
    /* ── Base typography & spacing ── */
    body {font-family: var(--qpos-font, 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif) !important;}
    /* Page backgrounds: global dark theme in css.blade.php — POS uses transparent below */
    body.lockscreen .content-wrapper,
    body.lockscreen section.content {background: transparent !important;}
    section.content-header {padding: 18px 20px 10px !important;background: transparent !important;}
    section.content-header h1 {font-size: 1.35rem !important;font-weight: 700 !important;letter-spacing: -0.02em;margin: 0 0 2px 0 !important;display: flex;align-items: center;gap: 8px;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) section.content-header h1{color: #f8fafc !important;}
    section.content-header h1 small {font-size: 0.78rem !important;font-weight: 400 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) section.content-header h1 small{color: #94a3b8 !important;}
    section.content-header .breadcrumb {background: transparent !important;padding: 0 !important;margin: 0 !important;font-size: 0.78rem;color: #64748b;}

    /* ── Box → Card transformation (structure only; colors from dark theme) ── */
    .box {border: none !important;border-radius: 14px !important;margin-bottom: 20px !important;transition: box-shadow .2s ease, transform .2s ease !important;/* visible so Actions dropdowns are not clipped */
      overflow: visible !important;}html[data-vspos-theme="dark"] .box{box-shadow: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.05) !important;}
    html[data-vspos-theme="dark"] .box:hover{box-shadow: 0 4px 16px rgba(0,0,0,.09) !important;}
    .box.box-primary {border-top: 3px solid #3b82f6 !important;}
    .box.box-success {border-top: 3px solid #22c55e !important;}
    .box.box-warning {border-top: 3px solid #f59e0b !important;}
    .box.box-danger  {border-top: 3px solid #ef4444 !important;}
    .box.box-info    {border-top: 3px solid #06b6d4 !important;}

    .box-header {padding: 16px 20px !important;background: transparent !important;display: flex !important;align-items: center !important;justify-content: space-between !important;}
    body:not(.lockscreen) .box-header {border-bottom: 1px solid rgba(148, 163, 184, .22) !important;}
    .box-header.with-border {border-bottom: 1px solid rgba(148, 163, 184, .22) !important;}
    .box-title {font-size: 0.95rem !important;font-weight: 700 !important;letter-spacing: -0.01em;margin: 0 !important;display: flex !important;align-items: center !important;gap: 8px;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box-title{color: #f8fafc !important;}
    .box-tools {margin: 0 !important;}
    .box-body {padding: 16px 20px !important;}
    .box-footer {padding: 12px 20px !important;background: transparent !important;}
    body:not(.lockscreen) .box-footer {border-top: 1px solid rgba(148, 163, 184, .22) !important;}

    /* ── Info boxes (dashboard summary cards) ── */
    .info-box {border-radius: 14px !important;min-height: 70px !important;overflow: hidden !important;transition: box-shadow .2s, transform .2s !important;margin-bottom: 16px !important;}html[data-vspos-theme="dark"] .info-box{box-shadow: 0 1px 3px rgba(0,0,0,.07) !important;}
    .info-box:hover {transform: translateY(-2px);}html[data-vspos-theme="dark"] .info-box:hover{box-shadow: 0 6px 20px rgba(0,0,0,.1) !important;}
    .info-box-icon {border-radius: 14px 0 0 14px !important;width: 72px !important;font-size: 1.6rem !important;display: flex !important;align-items: center !important;justify-content: center !important;}
    .info-box-content {padding: 12px 16px !important;}
    .info-box-text {font-size: 0.78rem !important;font-weight: 600 !important;letter-spacing: .03em;text-transform: uppercase;}
    .info-box-number {font-size: 1.4rem !important;font-weight: 800 !important;}

    /* ── Tables (structure only on admin dark pages) ── */
    .table {border-collapse: separate !important;border-spacing: 0 !important;}
    .table thead tr th {font-size: 0.72rem !important;font-weight: 700 !important;letter-spacing: .06em;text-transform: uppercase !important;border-top: none !important;padding: 10px 14px !important;white-space: nowrap;}
    .table tbody tr td {padding: 10px 14px !important;vertical-align: middle !important;font-size: 0.875rem !important;border-top: none !important;}
    .table-bordered {border-radius: 10px !important;overflow: visible !important;}

    /* DataTables / table wrappers — keep Actions menus visible on box chrome.
       Do not force overflow:visible on DataTables scroll containers: that
       disables scrollX and lets wide tables expand the page. */
    body:not(.lockscreen) .box-body {overflow: visible !important;}
    body:not(.lockscreen) .dataTables_wrapper {max-width: 100%;}
    body:not(.lockscreen) .dataTables_wrapper .dataTables_scroll,
    body:not(.lockscreen) .dt-bootstrap .dataTables_scroll {max-width: 100%;overflow: hidden;}
    body:not(.lockscreen) .dataTables_scrollBody,
    body:not(.lockscreen) div.dataTables_wrapper div.dataTables_scrollBody {overflow-x: auto !important;overflow-y: auto !important;max-width: 100%;}
    /* Wide tables (incl. Nutmeg cost report): force horizontal scroll */
    body:not(.lockscreen) .table-responsive.vspos-table-scroll-x,
    body:not(.lockscreen) .vspos-purchase-table-wrap,
    body:not(.lockscreen) .vspos-sell-table-wrap,
    body:not(.lockscreen) .table-responsive.pvs-table-scroll,
    body:not(.lockscreen) .pvs-table-scroll {max-width: 100% !important;overflow: hidden !important;}
    body:not(.lockscreen) .vspos-table-scroll-x div.dataTables_wrapper,
    body:not(.lockscreen) .vspos-purchase-table-wrap div.dataTables_wrapper,
    body:not(.lockscreen) .vspos-sell-table-wrap div.dataTables_wrapper,
    body:not(.lockscreen) .pvs-table-scroll div.dataTables_wrapper,
    body:not(.lockscreen) .pvs-table-scroll .dataTables_wrapper {overflow: hidden !important;max-width: 100% !important;}
    body:not(.lockscreen) .vspos-table-scroll-x div.dataTables_wrapper div.dataTables_scroll,
    body:not(.lockscreen) .vspos-purchase-table-wrap div.dataTables_wrapper div.dataTables_scroll,
    body:not(.lockscreen) .vspos-sell-table-wrap div.dataTables_wrapper div.dataTables_scroll,
    body:not(.lockscreen) .pvs-table-scroll div.dataTables_wrapper div.dataTables_scroll {overflow: hidden !important;max-width: 100% !important;}
    body:not(.lockscreen) .vspos-table-scroll-x div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-purchase-table-wrap div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-sell-table-wrap div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .pvs-table-scroll div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-table-scroll-x .dataTables_scrollBody,
    body:not(.lockscreen) .pvs-table-scroll .dataTables_scrollBody {overflow-x: auto !important;overflow-y: auto !important;max-width: 100% !important;}
    body:not(.lockscreen) .btn-group.open {position: relative;z-index: 1060 !important;}
    body:not(.lockscreen) .btn-group .dropdown-menu,
    body:not(.lockscreen) .dropdown-menu.vspos-dt-dropdown {z-index: 1070 !important;}
    .of-visible {overflow: visible !important;}

    /* ── Buttons ── */
    .btn {border-radius: 8px !important;font-size: 0.8rem !important;font-weight: 600 !important;padding: 7px 16px !important;transition: all .15s ease !important;letter-spacing: .01em;border-width: 1px !important;}
    .btn-xs {padding: 4px 10px !important;font-size: 0.72rem !important;border-radius: 6px !important;}
    .btn-sm {padding: 6px 12px !important;font-size: 0.78rem !important;}
    .btn-lg {padding: 10px 22px !important;font-size: 0.95rem !important;}
    .btn-primary {background: #3b82f6 !important;border-color: #3b82f6 !important;color: #fff !important;}
    .btn-primary:hover {background: #2563eb !important;border-color: #2563eb !important;transform: translateY(-1px);box-shadow: 0 4px 12px rgba(59,130,246,.35) !important;}
    .btn-success {background: #22c55e !important;border-color: #22c55e !important;color: #fff !important;}
    .btn-success:hover {background: #16a34a !important;border-color: #16a34a !important;transform: translateY(-1px);}
    .btn-danger  {background: #ef4444 !important;border-color: #ef4444 !important;color: #fff !important;}
    .btn-danger:hover  {background: #dc2626 !important;border-color: #dc2626 !important;transform: translateY(-1px);}
    .btn-warning {background: #f59e0b !important;border-color: #f59e0b !important;color: #fff !important;}
    .btn-info    {background: #06b6d4 !important;border-color: #06b6d4 !important;color: #fff !important;}
    .btn-default {border-width: 1px !important;}
    body:not(.lockscreen) .btn-default {border-color: rgba(148, 163, 184, .3) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-default{background: #0f172a !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-default:hover{background: #17233a !important;}

    /* ── Labels / Badges ── */
    .label {border-radius: 6px !important;font-size: 0.7rem !important;font-weight: 700 !important;padding: 3px 8px !important;letter-spacing: .04em;}
    .label-warning {background: #fef3c7 !important;color: #b45309 !important;}
    .label-success {background: #dcfce7 !important;color: #15803d !important;}
    .label-danger  {background: #fee2e2 !important;color: #b91c1c !important;}
    .label-info    {background: #e0f2fe !important;color: #0369a1 !important;}
    .label-primary {background: #dbeafe !important;color: #1d4ed8 !important;}

    /* ── Form controls (structure; dark colors in css.blade.php) ── */
    .form-control {border-radius: 8px !important;font-size: 0.875rem !important;padding: 8px 12px !important;transition: border-color .15s, box-shadow .15s !important;}
    .form-control:focus {box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;outline: none !important;}
    .form-group label {font-weight: 600 !important;font-size: 0.8rem !important;margin-bottom: 5px !important;}
    .input-group-addon {border-radius: 8px 0 0 8px !important;}

    /* ── Modal (structure only) ── */
    .modal-content {border: none !important;border-radius: 16px !important;overflow: hidden !important;}
    .modal-header {padding: 16px 20px !important;}
    .modal-title {font-weight: 700 !important;font-size: 1rem !important;}
    .modal-body {padding: 20px !important;}
    .modal-footer {padding: 14px 20px !important;}

    /* ── DataTables controls ── */
    .dataTables_wrapper .dataTables_filter input {border-radius: 8px !important;border: 1px solid #d1d5db !important;padding: 6px 10px !important;font-size: 0.82rem !important;}
    .dataTables_wrapper .dataTables_length select {border-radius: 8px !important;border: 1px solid #d1d5db !important;padding: 4px 8px !important;}
    .dataTables_wrapper .dataTables_info {font-size: 0.78rem !important;color: #64748b !important;}

    /*
     * DataTables Bootstrap renderer puts class "paginate_button" on the <li>,
     * while Bootstrap floats the inner <a>. Styling the <li> as a button
     * leaves empty square boxes after Previous / page / Next.
     */
    .dataTables_wrapper .dataTables_paginate ul.pagination {display: inline-flex !important;flex-wrap: wrap;align-items: center;justify-content: flex-end;gap: 4px;margin: 0;padding: 0;white-space: normal;}
    .dataTables_wrapper .dataTables_paginate ul.pagination > li,
    .dataTables_wrapper .dataTables_paginate ul.pagination > li.paginate_button {display: inline-flex !important;float: none !important;padding: 0 !important;margin: 0 !important;min-width: 0 !important;min-height: 0 !important;border: 0 !important;border-radius: 0 !important;background: transparent !important;box-shadow: none !important;}
    .pagination > li > a,
    .pagination > li > span,
    .dataTables_wrapper .dataTables_paginate ul.pagination > li > a,
    .dataTables_wrapper .dataTables_paginate ul.pagination > li > span {float: none !important;display: inline-flex !important;align-items: center;justify-content: center;min-width: 34px;height: 32px;padding: 4px 12px !important;margin: 0 !important;font-size: 0.78rem !important;font-weight: 600;line-height: 1.2 !important;border-radius: 6px !important;box-sizing: border-box;text-decoration: none !important;}
    /* Fallback when DataTables default renderer uses <a class="paginate_button"> */
    .dataTables_wrapper .dataTables_paginate > a.paginate_button,
    .dataTables_wrapper .dataTables_paginate > span > a.paginate_button {display: inline-flex !important;align-items: center;justify-content: center;min-width: 34px;height: 32px;padding: 4px 12px !important;margin: 0 2px !important;font-size: 0.78rem !important;font-weight: 600;border-radius: 6px !important;box-sizing: border-box;}

    /* ── Nav tabs ── */
    .nav-tabs {border-bottom: 2px solid #e2e8f0 !important;}
    .nav-tabs > li > a {border-radius: 8px 8px 0 0 !important;font-size: 0.82rem !important;font-weight: 600 !important;color: #64748b !important;border: none !important;padding: 9px 16px !important;}
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:focus,
    .nav-tabs > li.active > a:hover {color: #3b82f6 !important;border: none !important;border-bottom: 2px solid #3b82f6 !important;background: transparent !important;}

    /* ── Select2 (structure only — colors in css.blade.php / app.blade.php) ── */
    .select2-container--default .select2-selection--single {border-radius: 8px !important;height: 36px !important;}
    .select2-container--default .select2-selection--single .select2-selection__rendered {line-height: 34px !important;font-size: 0.875rem !important;padding-left: 10px !important;}
    .select2-container--default .select2-selection--single .select2-selection__arrow {height: 34px !important;}
    .select2-dropdown {border-radius: 10px !important;}html[data-vspos-theme="dark"] .select2-dropdown{box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;}

    /* ── Alerts ── */
    .alert {border-radius: 10px !important;border: none !important;font-size: 0.875rem !important;padding: 12px 16px !important;}
    .alert-success {background: #f0fdf4 !important;color: #15803d !important;border-left: 4px solid #22c55e !important;}
    .alert-danger  {background: #fef2f2 !important;color: #b91c1c !important;border-left: 4px solid #ef4444 !important;}
    .alert-warning {background: #fffbeb !important;color: #b45309 !important;border-left: 4px solid #f59e0b !important;}
    .alert-info    {background: #f0f9ff !important;color: #0369a1 !important;border-left: 4px solid #06b6d4 !important;}

    /* ── Scrollbars (webkit) ── */
    ::-webkit-scrollbar {width: 5px;height: 5px;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) ::-webkit-scrollbar-track{background: #020617;}
    body:not(.lockscreen) ::-webkit-scrollbar-thumb {background: #334155;border-radius: 4px;}
    body:not(.lockscreen) ::-webkit-scrollbar-thumb:hover {background: #475569;}

    /* ── Mobile responsive tweaks ── */
    @media (max-width: 767px) {
      section.content {padding: 8px !important;}
      .box-body {padding: 12px !important;}
      .box-header {padding: 12px 14px !important;flex-wrap: wrap !important;gap: 8px;}
      body:not(.lockscreen) .table thead tr th,
      body:not(.lockscreen) .table tbody tr td {padding: 8px 10px !important;font-size: 0.78rem !important;}
      .btn {padding: 6px 12px !important;}
      section.content-header h1 {font-size: 1.1rem !important;flex-wrap: wrap;row-gap: 2px;}
      body:not(.lockscreen) #sell_list_selected_range {display: block;width: 100%;font-size: 0.85rem !important;}
    }

    /*
     * DataTables draws the sort glyph with :after at right:8px and expects
     * padding-right:30px. Later padding shorthands used !important and painted
     * the icon on top of the header label.
     */
    body:not(.lockscreen) table.dataTable thead > tr > th.sorting,
    body:not(.lockscreen) table.dataTable thead > tr > th.sorting_asc,
    body:not(.lockscreen) table.dataTable thead > tr > th.sorting_desc,
    body:not(.lockscreen) table.dataTable thead > tr > th.sorting_asc_disabled,
    body:not(.lockscreen) table.dataTable thead > tr > th.sorting_desc_disabled,
    body:not(.lockscreen) table.dataTable thead > tr > td.sorting,
    body:not(.lockscreen) table.dataTable thead > tr > td.sorting_asc,
    body:not(.lockscreen) table.dataTable thead > tr > td.sorting_desc {position: relative;padding-right: 28px !important;}
    body:not(.lockscreen) table.dataTable thead .sorting:after,
    body:not(.lockscreen) table.dataTable thead .sorting_asc:after,
    body:not(.lockscreen) table.dataTable thead .sorting_desc:after,
    body:not(.lockscreen) table.dataTable thead .sorting_asc_disabled:after,
    body:not(.lockscreen) table.dataTable thead .sorting_desc_disabled:after {right: 8px;bottom: auto;top: 50%;transform: translateY(-50%);line-height: 1;}

    /* Wide lists keep one line and scroll. Other tables wrap instead of overlapping. */
    body:not(.lockscreen) .dataTables_scrollBody table.dataTable tbody td,
    body:not(.lockscreen) .vspos-table-scroll-x table.dataTable tbody td,
    body:not(.lockscreen) .vspos-purchase-table-wrap table.dataTable tbody td,
    body:not(.lockscreen) .vspos-sell-table-wrap table.dataTable tbody td,
    body:not(.lockscreen) .pvs-table-scroll table.dataTable tbody td {white-space: nowrap !important;overflow-wrap: normal;}

    /* Bootstrap .row negative margins inside DataTables push the page sideways. */
    body:not(.lockscreen) .dataTables_wrapper > .row,
    body:not(.lockscreen) .dt-buttons.btn-group {margin-left: 0;margin-right: 0;}
    body:not(.lockscreen) .dataTables_wrapper .dt-buttons {display: inline-flex;flex-wrap: wrap;gap: 4px;margin-bottom: 8px;}
    body:not(.lockscreen) .dataTables_wrapper .dataTables_filter,
    body:not(.lockscreen) .dataTables_wrapper .dataTables_length,
    body:not(.lockscreen) .dataTables_wrapper .dataTables_info,
    body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate {padding-top: 6px;}
    body:not(.lockscreen) .thetop,
    body:not(.lockscreen) main {min-width: 0;max-width: 100%;}
    .nv-inner,
    .nv-right {min-width: 0;}

    @media (max-width: 991px) {
      .modal-dialog {width: calc(100vw - 16px) !important;margin: 8px auto !important;}
      .modal-body {max-height: calc(100vh - 150px);overflow-y: auto;}
      .nv-right {gap: 4px;}

      /* Report and list tabs wrap as even rows instead of uneven floats. */
      body:not(.lockscreen) .nav-tabs-custom > .nav-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
      }
      body:not(.lockscreen) .nav-tabs-custom > .nav-tabs > li {
        float: none !important;
        flex: 1 1 46%;
        margin: 0 !important;
      }
      body:not(.lockscreen) .nav-tabs-custom > .nav-tabs > li > a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-align: center;
        white-space: normal !important;
        line-height: 1.25;
        min-height: 40px;
        padding: 8px 8px !important;
      }

      /* DataTables toolbar stacks inside the card. */
      body:not(.lockscreen) .dataTables_wrapper > .row {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-left: 0 !important;
        margin-right: 0 !important;
      }
      body:not(.lockscreen) .dataTables_wrapper > .row > [class*="col-"] {
        width: 100% !important;
        max-width: 100% !important;
        float: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        text-align: left !important;
      }
      body:not(.lockscreen) .dataTables_wrapper .dataTables_filter,
      body:not(.lockscreen) .dataTables_wrapper .dataTables_filter label {
        display: block;
        width: 100% !important;
        margin: 0 !important;
        text-align: left !important;
        white-space: normal !important;
      }
      body:not(.lockscreen) .dataTables_wrapper .dataTables_filter input {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 6px 0 0 0 !important;
        min-height: 36px;
        box-sizing: border-box;
      }
      body:not(.lockscreen) .dataTables_wrapper .dataTables_length label,
      body:not(.lockscreen) .dataTables_wrapper .dataTables_info {
        white-space: normal !important;
      }
      body:not(.lockscreen) .dataTables_wrapper .dataTables_length select {
        min-height: 36px;
      }
      body:not(.lockscreen) .dataTables_wrapper .dt-buttons {
        display: flex !important;
        flex-wrap: wrap;
        gap: 6px;
        float: none !important;
        width: 100%;
      }
      body:not(.lockscreen) .dataTables_wrapper .dt-buttons .dt-button,
      body:not(.lockscreen) .dataTables_wrapper .dt-buttons .btn {
        float: none !important;
        margin: 0 !important;
        min-height: 36px;
      }
      body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate {
        float: none !important;
        text-align: left !important;
        white-space: normal !important;
      }
      body:not(.lockscreen) .dataTables_scrollBody {
        -webkit-overflow-scrolling: touch;
      }
      body:not(.lockscreen) .dataTables_scrollBody::-webkit-scrollbar {height: 10px;width: 8px;}

      /* Profit / Loss filters and money columns. */
      body:not(.lockscreen) #profit_loss_date_filter,
      body:not(.lockscreen) #print_pl_80mm {
        width: 100%;
        margin-top: 8px;
        justify-content: center;
      }
      body:not(.lockscreen) .content > .row.no-print > .tw-flex {
        flex-wrap: wrap !important;
        justify-content: stretch !important;
      }
      body:not(.lockscreen) .content > .row.no-print > [class*="col-"] {margin-bottom: 8px;}
    }

    /* Financial report labels wrap. Amounts stay on one line and align right. */
    body:not(.lockscreen) #pl_data_div table {width: 100%;}
    body:not(.lockscreen) #pl_data_div table th {
      white-space: normal !important;
      vertical-align: top;
    }
    body:not(.lockscreen) #pl_data_div table td {
      white-space: nowrap !important;
      text-align: right;
      vertical-align: top;
      width: 1%;
    }
    body:not(.lockscreen) #pl_data_div .display_currency {white-space: nowrap !important;}
    body:not(.lockscreen) #pl_data_div h2,
    body:not(.lockscreen) #pl_data_div h3,
    body:not(.lockscreen) #pl_data_div h4 {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: baseline;
      gap: 6px 12px;
    }
    body:not(.lockscreen) [id^="profit_by_"] table.dataTable th:last-child,
    body:not(.lockscreen) [id^="profit_by_"] table.dataTable td:last-child {
      white-space: nowrap;
      text-align: right;
    }
    body:not(.lockscreen) [id^="profit_by_"] table.dataTable th:first-child,
    body:not(.lockscreen) [id^="profit_by_"] table.dataTable td:first-child {
      text-align: left;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) #pl_data_div tr[style*="fff8e1"] th,
    html[data-vspos-theme="dark"] body:not(.lockscreen) #pl_data_div tr[style*="fff8e1"] td {
      background: #2a2416 !important;
      color: #f8fafc !important;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) #pl_data_div th[style*="f4f4f4"] {
      background: #141414 !important;
      color: #f8fafc !important;
      border-color: #2a2a2a !important;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) #pl_data_div div[style*="f8fff4"] {
      background: #142016 !important;
      border-color: #23402a !important;
      color: #f8fafc !important;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) #pl_data_div div[style*="fafafa"] {
      background: #141414 !important;
      border-color: #2a2a2a !important;
      color: #f8fafc !important;
    }
  </style>