jQuery(function() {
    document.getElementById("defaultOpen").click();
    console.log(jQuery("#always_display_talkm").prop("checked"));
});
function talkmopentab(evt, tabName) {
    /* Declare all variables*/
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("talkmtabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tawktablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function talkm_setWidget(){
				var talkm_comapny = jQuery("input[name*='talkm_Company_Name']").val();
				var talkm_user = jQuery("input[name*='talkm_username']").val();
				var talkm_pass = jQuery("input[name*='talkm_password']").val();
				jQuery('.talkm-connection').text('Connecting...');
				 jQuery.ajax({
					 url: ajaxurl,
					 type: "POST",
					 data: {'action':'talkm_setwidget','talkm_Company_Name':talkm_comapny,'talkm_username':talkm_user,'talkm_password':talkm_pass},
					 async : false,
					 success: function(data){
						/*Code, that need to be executed when data arrives after
						  successful AJAX request execution*/
						 if (data) {
							 if(data['error_description']){
								 jQuery('.talkm-connection').text('Error');
								 	alert(data['error_description']);
							 }else{
							 jQuery('.talkm-connection').text('Connected'); 
							 alert(data['status']);}
						 }
					 }
				});
			}
			
function talkm_removeWidget(){
				 jQuery('.talkm-connection').text('Disconnecting...');
				 jQuery.ajax({
					 url: ajaxurl,
					 type: "POST",
					 data: {'action':'talkm_removewidget'},
					 async : false,
					 success: function(data){
						/*Code, that need to be executed when data arrives after
						 successful AJAX request execution*/
						 if (data) {
							 jQuery('.talkm-connection').text('Disconnected');
								alert(data['Status']);
								
								}
					 }
				});
			}			