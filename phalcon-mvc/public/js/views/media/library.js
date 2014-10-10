// JavaScript Document specific to Exam create
// @Author Ahsan Shahzad [MedfarmDoIT]


		
	/*----------------------------------------------*/ 
	/*	Media library related event bindings		*/
	/*----------------------------------------------*/
		
	$(function () {
		'use strict';
		
		// initialize tabs
		$('#media_types').tabs();
		
		// Change this to the location of your server-side upload handler:
		var url = 'plugins/jquery-file-upload/server/php/';
		$('#fileupload').fileupload({
			url: url,
			dataType: 'json',
			done: function (e, data) {
				$.each(data.result.files, function (index, file) {
					$('<li/>').append($('<img/>').css('width', '100%').attr('src', file.url)).prependTo( "#selected-lib-img" );
					$( "#selected-lib-img" ).sortable();
				});
			}/*,
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#progress .progress-bar').css(
					'width',
					progress + '%'
				);
			}*/
		}).prop('disabled', !$.support.fileInput)
			.parent().addClass($.support.fileInput ? undefined : 'disabled');
			
			
		$('.select-lib-img').click(function() {
			if($(this).hasClass('select-lib-img')) {
				var mediaId = $(this).parent().attr('id');
				var title = $(this).parent().find('.lib-item-title').html();
				
				$('#lib-default-msg').hide();
				$('#lib-items-added > ul').append('\
								<li media-id="'+mediaId+'">\
									<img src="'+$(this).find('img').attr('src')+'">\
									<div class="title-box" file-path="'+$(this).find('img').attr('file-path')+'">'+title+'</div>\
									<i class="fa fa-close"></i>\
								</li>');
				$(this).find('span').html('<i class="fa fa-check-square-o"></i> Selected');
				$(this).removeClass('select-lib-img').addClass('selected-lib-img').removeAttr('href');
				$( "#selected-lib-img" ).sortable();
			}
			return false;
		});
		
		$( "#selected-lib-img" ).sortable();
		
		// attach settings tooltip on all library items
		$(".lib-item-settings > i").each(function(i, tagElement) {
			
			var title 	= $(tagElement).attr('item-title');
			var shared 	= $(tagElement).attr('item-share');
			var mediaId = $(tagElement).attr('media-id');
			
			new Opentip(tagElement,
					'<div>\
						<div>Title</div>\
						<div><input type="text" class="update-lib-item-title" value="'+title+'"></div>\
						\
						<br style="clear:both">\
						\
						<div>This can be reused </div>\
						<div>\
							<select class="update-lib-item-shared" style="width:145px; height:25px">\
								<option value="1" '+(shared == 1 ? 'selected' :'')+'>only for my exams</option>\
								<option value="2" '+(shared == 2 ? 'selected' :'')+'>for all exams in my department</option>\
								<option value="3" '+(shared == 3 ? 'selected' :'')+'>for any exam in Uppsala University</option>\
							</select>\
						</div>\
						<br />\
						<span class="btn btn-success update-lib-item-details" media-id="'+mediaId+'"> Save</span>\
					</div>',
					{style: "drops", tipJoint: "top left"});
		});
		
		/** update title and share settings **/
		$(document).on('click', '.update-lib-item-details', function() {
			// grab data
			var title 	= $(this).parent().find('.update-lib-item-title').val();
			var shared 	= $(this).parent().find('.update-lib-item-shared').val();
			var mediaId = $(this).attr('media-id');
			
			// send ajax request to save data
			
			// update dom as per the changes
			$('#lib-item-'+mediaId).find('.lib-item-title').html(title);
			$('#lib-items-added > ul').find('li[media-id="lib-item-'+mediaId+'"] > .title-box').html(title);
			// close tooltip
			close_tooltips();
		});
		
		$(document).on('click', '#selected-lib-img li i', function() {
			
			$('#'+$(this).parent().attr('media-id')).find('.selected-lib-img').removeClass('selected-lib-img').addClass('select-lib-img');
			$(this).parent().hide(300).remove();
			if(!$('#lib-items-added > ul > li').length)
				$('#lib-default-msg').show(500);
			
		});
		
	});
			
