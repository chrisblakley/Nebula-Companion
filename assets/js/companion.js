jQuery.noConflict();

jQuery(document).on('nebula_event_tracking', function(){
	crmFormRealTime();
});

/*==========================
 DOM Ready (After nebula.js is loaded)
 ===========================*/

jQuery(function(){
	crmQueryParameters();
}); //End Document Ready

/*==========================
 Window Load
 ===========================*/

jQuery(window).on('load', function(){

});


/*==========================
 Companion Functions
 ===========================*/

//Easily send data to nebula.crm() via URL query parameters
//Use the crm-* format in the URL to pass data to this function. Ex: ?crm-firstname=Chris (can be encoded, too)
function crmQueryParameters(){
	if ( nebula.has(nebula, 'site.options.advanced_form_identification') && nebula.site.options.advanced_form_identification ){ //If the option exists and is enabled. Use optional chaining here.
		if ( window.location.href.indexOf('crm-') > 0 ){ //If a "crm-*" query parameter exists
			var queryParameters = nebula.getQueryStrings();
			var crmData = {};
			var crmRemove = [];

			jQuery.each(queryParameters, function(index, value){
				index = decodeURIComponent(index);
				value = decodeURIComponent(value).replace('+', ' ');

				if ( index.substring(0, 3) === 'crm-' ){
					var parameter = index.substring(3, index.length);
					crmData[parameter] = value;
					crmRemove.push(index);
				}

				if ( index.substring(0, 4) === 'utm_' ){
					var parameter = index.substring(4, index.length);
					crmData[parameter] = value;
				}
			});

			//Send to CRM
			if ( Object.keys(crmData).length ){
				nebula.crm('identify', crmData);
			}

			//Remove the crm-* query parameters
			if ( crmRemove.length > 0 && !get('persistent') && window.history.replaceState ){ //IE10+
				window.history.replaceState({}, document.title, removeQueryParameter(crmRemove, window.location.href));
			}
		}
	}
}

//Listen to form inputs and identify in real-time
//Add a class to the input field with the category to use. Ex: crm-firstname
function crmFormRealTime(){
	if ( nebula.has(nebula, 'site.options.advanced_form_identification') && nebula.site.options.advanced_form_identification ){ //If the option exists and is enabled. Use optional chaining here.
		jQuery('form [class*="crm-"]').on('blur', function(){
			var thisVal = jQuery.trim(jQuery(this).val());

			if ( thisVal.length > 0 ){
				var cat = /crm-([a-z\_]+)/g.exec(jQuery(this).attr('class'));

				if ( cat ){
					data = {};
					data[cat[1]] = thisVal;
					nebula.crm('identify', data);
				}
			}
		});
	}
}