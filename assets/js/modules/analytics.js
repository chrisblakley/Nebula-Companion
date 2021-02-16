//Easily send data to nebula.crm() via URL query parameters
//Use the crm-* format in the URL to pass data to this function. Ex: ?crm-firstname=Chris (can be encoded, too)
nebula.crmQueryParameters = function(){
	if ( nebula?.site?.options?.advanced_form_identification ){ //If the option exists and is enabled
		if ( location.search.includes('crm-') ){ //If a "crm-*" query parameter exists
			var queryParameters = nebula.get();
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
			if ( crmRemove.length > 0 && !nebula.get('persistent') && window.history.replaceState ){ //IE10+
				window.history.replaceState({}, document.title, removeQueryParameter(crmRemove, window.location.href));
			}
		}
	}
}

//Listen to form inputs and identify in real-time
//Add a class to the input field with the category to use. Ex: crm-firstname
nebula.crmFormRealTime = function(){
	if ( nebula?.site?.options?.advanced_form_identification ){ //If the option exists and is enabled.
		jQuery('form [class*="crm-" i]').on('blur', function(){
			var thisVal = jQuery(this).val().trim();

			if ( thisVal.length ){
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