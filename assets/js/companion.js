jQuery.noConflict();

jQuery(document).on('nebula_event_tracking', function(){
	nvFormRealTime();
});

jQuery(document).on('wpcf7submit', function(e){
	if ( has(nebula, 'site.options.advanced_form_identification') ){
		nvForm(); //nvForm() here because it triggers after all others. No nv() here so it doesn't overwrite the other (more valuable) data.
	}
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
	facebookConnect();
	prefillFacebookFields();
});


/*==========================
 Companion Functions
 ===========================*/

//Facebook Connect functions
function facebookConnect(){
	nebula.user.flags.fbconnect = false;

	if ( nebula.site.options.facebook_app_id ){
		window.fbAsyncInit = function(){
			FB.init({
				appId: nebula.site.options.facebook_app_id,
				channelUrl: nebula.site.directory.template.uri + '/inc/channel.php',
				status: true,
				xfbml: true
			});

			nebula.dom.document.trigger('fbinit');
			checkFacebookStatus();
		};
	} else {
		jQuery('.facebook-connect').remove();
	}
}

//Check Facebook Status
function checkFacebookStatus(){
	FB.getLoginStatus(function(response){
		nebula.user.facebook = {'status': response.status}
		if ( nebula.user.facebook.status === 'connected' ){ //User is logged into Facebook and is connected to this app.
			FB.api('/me', {fields: 'id,name,first_name,last_name,cover,devices,gender,email,link,locale,timezone'}, function(response){ //Only publicly available fields
				nebula.user.facebook = {
					id: response.id,
					name: {
						first: response.first_name,
						last: response.last_name,
						full: response.name,
					},
					gender: response.gender,
					email: response.email,
					image: {
						base: 'https://graph.facebook.com/' + response.id + '/picture',
						thumbnail: 'https://graph.facebook.com/' + response.id + '/picture?width=100&height=100',
						large: 'https://graph.facebook.com/' + response.id + '/picture?width=1000&height=1000',
						cover: response.cover.source,
					},
					url: response.link,
					location: {
						locale: response.locale,
						timezone: response.timezone,
					},
					devices: response.devices
				}

				nebula.user.name = {
					first: response.first_name,
					last: response.last_name,
					full: response.name,
				};
				nebula.user.gender = response.gender;
				nebula.user.email = response.email;
				nebula.user.location = {
					locale: response.locale,
					timezone: response.timezone,
				}

				nv('identify', {
					firstname: response.first_name,
					lastname: response.last_name,
					full_name: response.name,
					email: response.email,
					facebook_id: response.id,
					profile_photo: 'https://graph.facebook.com/' + response.id + '/picture?width=1000&height=1000',
					image: response.cover.source,
					gender: response.gender,
				});

				ga('set', nebula.analytics.dimensions.fbID, nebula.user.facebook.id);
				if ( nebula.user.flags.fbconnect !== true ){
					ga('send', 'event', 'Social', 'Facebook Connect', nebula.user.facebook.id);
					nebula.user.flags.fbconnect = true;
				}

				nebula.dom.body.removeClass('fb-disconnected').addClass('fb-connected fb-' + nebula.user.facebook.id);
				jQuery(document).trigger('fbConnected', response);
			});
		} else if ( nebula.user.facebook.status === 'not_authorized' ){ //User is logged into Facebook, but has not connected to this app.
			nebula.dom.body.removeClass('fb-connected').addClass('fb-not_authorized');
			jQuery(document).trigger('fbNotAuthorized');
			nebula.user.flags.fbconnect = false;
		} else { //User is not logged into Facebook.
			nebula.dom.body.removeClass('fb-connected').addClass('fb-disconnected');
			jQuery(document).trigger('fbDisconnected');
			nebula.user.flags.fbconnect = false;
		}
	});
}

//Fill or clear form inputs with Facebook data
function prefillFacebookFields(){
	jQuery(document).on('fbConnected', function(){
		jQuery('.fb-name, .comment-form-author input, input.name').each(function(){
			if ( jQuery.trim(jQuery(this).val()) === '' ){
				jQuery(this).val(nebula.user.facebook.name.full).addClass('fb-filled').trigger('keyup');
			}
		});
		jQuery('.fb-first-name, input.first-name').each(function(){
			if ( jQuery.trim(jQuery(this).val()) === '' ){
				jQuery(this).val(nebula.user.facebook.name.first).addClass('fb-filled').trigger('keyup');
			}
		});
		jQuery('.fb-last-name, input.last-name').each(function(){
			if ( jQuery.trim(jQuery(this).val()) === '' ){
				jQuery(this).val(nebula.user.facebook.name.last).addClass('fb-filled').trigger('keyup');
			}
		});
		jQuery('.fb-email, .comment-form-email input, .wpcf7-email, input.email').each(function(){
			if ( jQuery.trim(jQuery(this).val()) === '' ){
				jQuery(this).val(nebula.user.facebook.email).addClass('fb-filled').trigger('keyup');
			}
		});
	});

	jQuery(document).on('fbNotAuthorized fbDisconnected', function(){
		jQuery('.fb-filled').each(function(){
			jQuery(this).val('').removeClass('fb-filled').trigger('keyup');
		});
	});
}

//Easily send data to nv() via URL query parameters
//Use the nv-* format in the URL to pass data to this function. Ex: ?nv-firstname=Chris (can be encoded, too)
function nvQueryParameters(){
	if ( has(nebula, 'site.options.advanced_form_identification') ){
		var queryParameters = getQueryStrings();
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
			nv('identify', nvData);
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
	if ( has(nebula, 'site.options.advanced_form_identification') ){
		jQuery('form [class*="nv-"]').on('blur', function(){
			var thisVal = jQuery.trim(jQuery(this).val());

			if ( thisVal.length > 0 ){
				var cat = /nv-([a-z\_]+)/g.exec(jQuery(this).attr('class'));

				if ( cat ){
					data = {};
					data[cat[1]] = thisVal;
					nv('identify', data);
				}
			}
		});
	}
}

//Easily send form data to nv() with nv-* classes
//Add a class to the input field with the category to use. Ex: nv-firstname
//Call this function before sending a ga() event because it sets dimensions too
function nvForm(){
	nvFormObj = {};
	jQuery('form [class*="nv-"]').each(function(){
		if ( jQuery.trim(jQuery(this).val()).length ){
			if ( jQuery(this).attr('class').indexOf('nv-notable_poi') >= 0 ){
				ga('set', nebula.analytics.dimensions.poi, jQuery('.notable-poi').val());
			}

			var cat = /nv-([a-z\_]+)/g.exec(jQuery(this).attr('class'));
			if ( cat ){
				var thisCat = cat[1];
				nvFormObj[thisCat] = jQuery(this).val();
			}
		}
	});

	if ( Object.keys(nvFormObj).length ){
		nv('identify', nvFormObj);
	}
}