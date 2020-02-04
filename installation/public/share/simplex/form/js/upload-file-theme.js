/*!
 * File input Simplex theme that uses fontello icons
 * bootstrap-fileinput v5.0.3
 * http://plugins.krajee.com/file-input
 *
 * Font Awesome 5 icon theme configuration for bootstrap-fileinput. Requires font awesome 5 assets to be loaded.
 *
 * Author: Kartik Visweswaran
 * Copyright: 2014 - 2019, Kartik Visweswaran, Krajee.com
 *
 * Licensed under the BSD-3-Clause
 * https://github.com/kartik-v/bootstrap-fileinput/blob/master/LICENSE.md
 */
(function ($) {
    "use strict";

    $.fn.fileinputThemes.simplex = {
        fileActionSettings: {
            removeIcon: '<i class="icon-form-trash"></i>',
            uploadIcon: '<i class="icon-form-upload"></i>',
            uploadRetryIcon: '<i class="icon-form-reload"></i>',
            downloadIcon: '<i class="icon-form-download"></i>',
            zoomIcon: '<i class="icon-form-zoom-in"></i>',
            dragIcon: '<i class="icon-form-move"></i>',
            indicatorNew: '<i class="icon-form-plus-circled text-warning"></i>',
            indicatorSuccess: '<i class="icon-form-checked-circled text-success"></i>',
            indicatorError: '<i class="icon-form-exclamation-circled text-danger"></i>',
            indicatorLoading: '<i class="icon-form-hourglass text-muted"></i>',
            indicatorPaused: '<i class="icon-form-pause text-info"></i>'
        },
        layoutTemplates: {
            fileIcon: '<i class="icon-form-file"></i> '
        },
        previewZoomButtonIcons: {
            prev: '<i class="icon-form-angle-left"></i>',
            next: '<i class="icon-form-angle-right"></i>',
            toggleheader: '<i class="icon-form-resize-vertical"></i>',
            fullscreen: '<i class="icon-form-resize-full"></i>',
            borderless: '<i class="icon-form-external-link"></i>',
            close: '<i class="icon-form-close"></i>'
        },
        previewFileIcon: '<i class="icon-form-file"></i>',
        browseIcon: '<i class="icon-form-folder-open"></i>',
        removeIcon: '<i class="icon-form-trash"></i>',
        cancelIcon: '<i class="icon-form-block"></i>',
        pauseIcon: '<i class="icon-form-pause"></i>',
        uploadIcon: '<i class="icon-form-upload"></i>',
        msgValidationErrorIcon: '<i class="icon-form-exclamation-circled"></i> '
    };
})(window.jQuery);
