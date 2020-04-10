jQuery.noConflict();

jQuery(document).on('nebula_event_tracking', function(){
	nvFormRealTime();
});

/*==========================
 DOM Ready (After nebula.js is loaded)
 ===========================*/

jQuery(function(){
	nvQueryParameters();
}); //End Document Ready

/*==========================
 Window Load
 ===========================*/

jQuery(window).on('load', function(){

});


/*==========================
 Companion Functions
 ===========================*/

//Easily send data to nebula.nv() via URL query parameters
//Use the nv-* format in the URL to pass data to this function. Ex: ?nv-firstname=Chris (can be encoded, too)
function nvQueryParameters(){
	if ( nebula.has(nebula, 'site.options.advanced_form_identification') && nebula.site.options.advanced_form_identification ){ //If the option exists and is enabled
		var queryParameters = nebula.getQueryStrings();
		var nvData = {};
		var nvRemove = [];

		jQuery.each(queryParameters, function(index, value){
			index = decodeURIComponent(index);
			value = decodeURIComponent(value).replace('+', ' ');

			if ( index.substring(0, 3) === 'nv-' ){
				var parameter = index.substring(3, index.length);
				nvData[parameter] = value;
				nvRemove.push(index);
			}

			if ( index.substring(0, 4) === 'utm_' ){
				var parameter = index.substring(4, index.length);
				nvData[parameter] = value;
			}
		});

		//Send to CRM
		if ( Object.keys(nvData).length ){
			nebula.nv('identify', nvData);
		}

		//Remove the nv-* query parameters
		if ( nvRemove.length > 0 && !get('persistent') && window.history.replaceState ){ //IE10+
			window.history.replaceState({}, document.title, removeQueryParameter(nvRemove, window.location.href));
		}
	}
}

//Listen to form inputs and identify in real-time
//Add a class to the input field with the category to use. Ex: nv-firstname
function nvFormRealTime(){
	if ( nebula.has(nebula, 'site.options.advanced_form_identification') && nebula.site.options.advanced_form_identification ){ //If the option exists and is enabled
		jQuery('form [class*="nv-"]').on('blur', function(){
			var thisVal = jQuery.trim(jQuery(this).val());

			if ( thisVal.length > 0 ){
				var cat = /nv-([a-z\_]+)/g.exec(jQuery(this).attr('class'));

				if ( cat ){
					data = {};
					data[cat[1]] = thisVal;
					nebula.nv('identify', data);
				}
			}
		});
	}
}