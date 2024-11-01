<?php
/**
 * @package TalkM Widget for Wordpress
 * @author TalkM
 * @copyright (C) 2018- TalkM
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/
if ( ! defined( 'ABSPATH' ) ) { 
    exit; /* Exit if accessed directly */
}
	 $teenant_key =  get_option(self::TALKM_WIDGET_TEENANT_VARIABLE);
?>
<div class="talkmheader">
  <div class="talkmheadtext">
    <?php _e('TalkM Plugin Settings','talkm-to-live-chat'); ?>
  </div>
</div>
<div class="talkmsettingsbody">
	<div class="talkmtabs">
	  <button class="tawktablinks" onclick="talkmopentab(event, 'account')" id="defaultOpen"><?php _e('Account','talkm-to-live-chat'); ?></button>
	  
	<?php if(!empty($teenant_key))
			 {?>
		   <button class="tawktablinks" onclick="talkmopentab(event, 'visibility')"><?php _e('Visibility','talkm-to-live-chat'); ?></button>
	<?php  }else{?>
		  <button class="tawktablinks" style="cursor:not-allowed;"><?php _e('Visibility','talkm-to-live-chat'); ?></button>
	<?php  } ?>
	</div>

	<div id="account" class="talkmtabcontent">
  <?php

   $display_widgetsettings = false;
   if(($teenant_key == NULL)){
        $display_widgetsettings = true;
    }
  if ($display_widgetsettings == TRUE){
  ?>
  
<form name="talkm_connection" id="talkm_login" method="POST">
    <p> <span><?php _e('Company Name','talkm-to-live-chat'); ?></span>  <input type="text" name="talkm_Company_Name" placeholder="Company Name" id="talkm_Company_Name" class="text"/><?php _e('.talkm.com','talkm-to-live-chat'); ?></p>
      <p><span><?php _e('Username','talkm-to-live-chat'); ?></span>  <input type="text" name="talkm_username" placeholder="Username" id="talkm_username" class="text"/></p>
      <p><span><?php _e('Password','talkm-to-live-chat'); ?></span> <input type="password" name="talkm_password" placeholder="Password" id="talkm_password" class="text"/></p>
	   <p class='talkm_con'><button class='talkm-connection button button-primary' onclick="talkm_setWidget();"><?php _e('Connect','talkm-to-live-chat'); ?></button>  </p>
</form>
 <div id="talkmvisibilitysettings">
		<h4><?php _e('Please sign in with your TalkM account.','talkm-to-live-chat'); ?></h4>
		<h4>
		<?php _e('No account yet? Sign up for a new account at <a class="talkmlink" href="https://www.talkm.com/pricing.html" target="_blank">https://www.talkm.com/pricing.html</a> ','talkm-to-live-chat'); ?>
		</h4>
 </div>

      <?php
   }else{ ?>
   <div id="talkmvisibilitysettings">
		<h2><?php _e('The following account is connected:','talkm-to-live-chat'); ?></h2>
		<div class='talkm_content'>
		<h4>
		<?php _e('Username','talkm-to-live-chat'); ?> : <?php echo  $username = get_option('talkm-embed-widget-username-id'); ?>
		</h4>
		<form name="talkm_disconnect" id="talkm_disconnect button button-primary" method="POST">
			  <button class='talkm-connection button button-primary' onclick="talkm_removeWidget();"><?php _e('Disconnect','talkm-to-live-chat'); ?></button> 
		</form>
		</div>
		<p class='talkmnotice'>
		<?php _e('Disconnecting the account will remove live chat on all the pages.','talkm-to-live-chat'); ?>
		</p>
		 <h4>
		<?php _e('Status','talkm-to-live-chat'); ?> : <?php echo $status = get_option('talkm-embed-widget-status-id'); ?>
		</h4>
		<p class='talkmnotice'>
		<?php _e('If the account is inactive, please login to  <a class="talkmlink" href="https://www.talkm.com/pricing.html" target="_blank">https://www.talkm.com/pricing.html</a> to extend your subscription.','talkm-to-live-chat'); ?> 
		</p>
   </div>
   <?php } ?>
 </div>
<form method="post" action="options.php">
   <?php
   settings_fields( 'talkm_options' );
   do_settings_sections( 'talkm_options' );

   $visibility = get_option( 'talkm-visibility-options',FALSE );
   if($visibility == FALSE){
   		$visibility = array (
				'always_display_talkm' => 1,
				'exclude_url_talkm' => 1,
				'excluded_url_list_talkm' => '',
			);
   }
   ?>
	<div id="visibility" class="talkmtabcontent visibilitycontent">
 <div id="talkmvisibilitysettings">
    <table class="form-table">
	<!--Visible on All pages -->
      <tr valign="top">
      <th class="talkmsetting"  colspan='0' scope="row"><?php _e('Visible on all pages?','talkm-to-live-chat'); ?></th>
      <td class="talkm-all-page">
	  <div class="talkM-check-block">
		<label class="control control--radio">
			<input type="radio" name="talkm-visibility-options[always_display_talkm]" value='1' <?php echo checked( 1, $visibility['always_display_talkm'], false ); ?>><?php _e('Yes','talkm-to-live-chat'); ?></input>
			<div class="talkM-control__indicator"></div>
		</label>
	  </div>
	  <div class="talkM-check-block">
		<label class="control control--radio">
			<input type="radio" name="talkm-visibility-options[always_display_talkm]" value='0' <?php echo checked(0, $visibility['always_display_talkm'], false ); ?>><?php _e('No','talkm-to-live-chat'); ?></input>
			<div class="talkM-control__indicator"></div>
		</label>
	  </div>
      </td>
      </tr>
	  <!-- End of visible on all pages-->
	  <tr valign="top" >
	  <th colspan='0'>
		 <h4><?php _e('List of page(s) that exclude TalkM Chat Widget','talkm-to-live-chat'); ?></h4>
		  <p class='talkmnotice'>
		<?php _e("Add a page by entering the page's URL in a new row below.",'talkm-to-live-chat'); ?>
		<BR>
		<?php _e("Remove the page from the list by deleting the page's URL from the row.",'talkm-to-live-chat'); ?>
		</p>
		 </th>
     </tr>
	 
	  <!--Exclude By Page URL -->
      <tr valign="top">
      <th colspan='0'>
      <input type="hidden" id="exclude_url_talkm" name="talkm-visibility-options[exclude_url_talkm]" value="1" <?php echo checked( 1, $visibility['exclude_url_talkm'], false ); ?> />
	  
      	<div id="exlucded_urls_container">
		<?php if(empty($visibility['excluded_url_list_talkm'])){ ?>
		<textarea id="excluded_url_list_talkm" name="talkm-visibility-options[excluded_url_list_talkm]" cols="53" rows="5"><?php echo $visibility['excluded_url_list_talkm']; ?></textarea>
		<?php } else{ 
		$newList_talkm = explode(" ",$visibility['excluded_url_list_talkm']);
		?>
		<textarea id="excluded_url_list_talkm" name="talkm-visibility-options[excluded_url_list_talkm]" cols="53" rows="5"><?php echo implode("&#13;&#10;",$newList_talkm); ?></textarea>
		<?php } ?>
      	</div>
      </th>
      </tr>
	  <!--End Of Exclude Url -->
    </table>
			<div class="talkmfootaction">
				<?php submit_button(); ?>
			</div>

		</div>
	</div>
	</form>
</div>