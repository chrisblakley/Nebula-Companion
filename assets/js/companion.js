'use strict';

window.performance.mark('(Nebula Companion) Inside companion.js');
jQuery.noConflict();

/*==========================
 Import Modules
 ===========================*/

import './modules/analytics.js';

/*==========================
 DOM Ready (After nebula.js is loaded)
 ===========================*/

jQuery(async function(){
    await import(nebula.site.directory.template.uri + '/assets/js/modules/optimization.js'); //Await parent theme module
    nebula.cacheSelectors();
});

/*==========================
 Window Load
 ===========================*/

jQuery(window).on('load', function(){
	nebula.crmQueryParameters();
});

/*==========================
 Nebula Hooks
 ===========================*/

jQuery(document).on('nebula_event_tracking', function(){
	nebula.crmFormRealTime();
});