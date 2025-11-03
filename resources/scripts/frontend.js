/**
 * LEXO Forms - Frontend JavaScript
 *
 * Initializes LEXO Captcha for all forms on the frontend
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof LEXO_Captcha !== 'undefined') {
            LEXO_Captcha.initialise_all_forms();            
        }
    });
})();
