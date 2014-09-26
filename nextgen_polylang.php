<?php
/*
Plugin Name: Multilang NextGen Gallery
Description: Make NextGen Gallery descriptions and titles multilang with Polylang
Version: 1.0
Author: Kamil Ryczek
*/

if(!class_exists('NGG_Polylang')) {
	class NGG_Polylang {
		public function __construct() {
			add_filter('ngg_manage_gallery_fields', array(&$this, 'addPolylangFields'), 11, 2);
			#	add_action("ngg_manage_image_custom_column", array(&$this, "nggcf_admin_col"), 10 ,2);
			#	NGG >= v2.0.57
			add_filter('ngg_manage_images_number_of_columns', array(&$this, 'nggcf_add_image_cols'));
			add_filter("ngg_manage_images_columns", array(&$this, "nggcf_manage_cols"));
			add_action("ngg_update_gallery", array(&$this, "nggcf_save_data"), 10, 2);
		}

		public function activate() {
			global $wpdb;
			global $nggpoly_db;
			$nggpoly_db = $wpdb->prefix . 'ngg_polylang';
			
			if($wpdb->get_var("show tables like '$nggpoly_db'") != $nggpoly_db) {
				$sql = "CREATE TABLE " . $nggpoly_db . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`fid` mediumint(9) NOT NULL,
				`type` tinytext NOT NULL,
				`value` text NOT NULL,
				`lang` tinytext NOT NULL,
				UNIQUE KEY id (id)
				);";
		 
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
		}

		public function deactivate() {
			#$this->clearAllPluginData();
		}
		
		public function clearAllPluginData() {
			#	USUŃ WSZYSTKIE DANE WTYCZKI
		}
		
		public function displayPolylangFields($gallery=array()) {
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_style('jquery-ui-tabs', plugins_url('nextgen_polylang/theme/jquery-ui.css'));
			global $wpdb;
			
			echo '<input type="hidden" name="nggcf_gallery[ngg_gallery_id]" value="'.$gallery->gid.'" />';
			
			if(function_exists('pll_languages_list')) {
				global $polylang;
				if (isset($polylang)) {
					$translations = $polylang->get_languages_list();
					$neededObjects = array_filter($translations,
						function ($e) {
							return $e->slug == pll_default_language();
						}						
					);
					$new_value = $translations[key($neededObjects)];
					unset($translations[key($neededObjects)]);
					array_unshift($translations, $new_value);
					
					?>
					<script>jQuery(function($) { $( ".polytabs" ).tabs({collapsible: true, active: false }); });</script>
					<div class="polytabs">
						<ul><?php
							foreach($translations as $lang) {
								echo '<li><a href="#tabs-'.$lang->slug.'">'.$lang->flag.$lang->name.'</a></li>';
							} ?>
						</ul>
						<?php
							foreach($translations as $lang) {
								echo '<div id="tabs-'.$lang->slug.'"><span class="field gallery_title_field" style="display: block; margin-bottom: 5px;"><label style="line-height: 30px;">Tytuł galerii</label><input type="text" name="'.(($lang->slug==pll_default_language())?"title":"plgal[{$gallery->gid}][{$lang->slug}][title]").'" style="width:100%;" value="'.(($lang->slug==pll_default_language())?esc_attr($gallery->title):$this->get_image_field("gallery", $gallery->gid, "title", $lang->slug)).'" /></span><br /><span class="field gallery_title_field" style="display: block; margin-bottom: 5px;"><label style="line-height: 30px;">Opis galerii</label><textarea name="'.(($lang->slug==pll_default_language())?"galdesc":"plgal[{$gallery->gid}][{$lang->slug}][description]").'" style="width:100%;">'.(($lang->slug==pll_default_language())?esc_attr($gallery->galdesc):$this->get_image_field("gallery", $gallery->gid, "description", $lang->slug)).'</textarea></span></div>';
						} ?>
						
					</div>
				<?php
				}
			}
		}
		
		function addPolylangFields($fields=array(), $gallery=NULL) {
			$fields['left']['ngg_cf_options'] = array(
				'callback'	=> array(&$this, 'displayPolylangFields'),
				'label'		=>	_('Tłumaczenia:'),
				'tooltip'   =>  NULL,
				'id'		=>	'ngg_cf_options'
				);
			unset($fields['left']['title']);
			unset($fields['left']['description']);
			return $fields;
		}
		
		
		function nggcf_add_image_cols($numCols) {
			global $wpdb, $nggcf_image_cols;
			$fields = array('jedno');
			/*
			foreach ($fields as $key=>$val) {
				$numCols++;
				$nggcf_image_cols[$numCols] = $val;
				//add_filter('ngg_manage_images_column_'.$numCols.'_header', array(&$this, 'nggcf_image_col_header'), $numCols);
				//add_filter('ngg_manage_images_column_'.$numCols.'_content', array(&$this, 'nggcf_image_col_field'), 10, 2);
			}
			*/
			add_filter('ngg_manage_images_column_5_header', array(&$this, 'nggcf_image_col_header'), $numCols);
			add_filter('ngg_manage_images_column_5_content', array(&$this, 'nggcf_image_col_field'), 10, 2);
			
			$numCols -= 1;
			return $numCols;
		}
		
		public function nggcf_image_col_header($name) {
			return 'Polylang';
		}
		
		function nggcf_image_col_field($output='', $picture=array()) {
			//$output 	= serialize($picture);
			$alttext	= esc_attr(stripslashes($picture->alttext));
			$desc		= esc_html(stripslashes($picture->description));
			$tags 		= wp_get_object_terms($picture->pid, 'ngg_tag', 'fields=names');
			if (is_array($tags)) $tags = implode(', ', $tags);
			$tags 		= esc_html($tags);	
			
			if(function_exists('pll_languages_list')) {
				global $polylang;
				if (isset($polylang)) {
					$translations = $polylang->get_languages_list();
					$neededObjects = array_filter($translations,
						function ($e) {
							return $e->slug == pll_default_language();
						}						
					);
					$new_value = $translations[key($neededObjects)];
					unset($translations[key($neededObjects)]);
					array_unshift($translations, $new_value);
					?>
					<div class="polytabs">
						<ul><?php
							foreach($translations as $lang) {
								echo '<li><a href="#tabsimg-'.$lang->slug.'">'.$lang->flag.'</a></li>';
							} ?>
						</ul>
						<?php
							foreach($translations as $lang) {
								echo '<div id="tabsimg-'.$lang->slug.'" style="overflow:hidden;"><div style="width:50%;float:left;padding:5px;box-sizing:border-box;"><span class="field gallery_title_field" style="display: block; margin-bottom: 5px;"><label style="line-height: 30px;">Tytuł obrazka</label><input type="text" name="'.(($lang->slug==pll_default_language())?"images[{$picture->pid}][alttext]":"plimg[{$picture->pid}][{$lang->slug}][alttext]").'" style="width:100%;" value="'.(($lang->slug==pll_default_language())?"{$alttext}":$this->get_image_field("image", $picture->pid, "alttext", $lang->slug)).'" /></span><br /><span class="field gallery_title_field" style="display: block; margin-bottom: 5px;"><label style="line-height: 30px;">Opis obrazka</label><textarea name="'.(($lang->slug==pll_default_language())?"images[{$picture->pid}][description]":"plimg[{$picture->pid}][{$lang->slug}][description]").'" style="width:100%;">'.(($lang->slug==pll_default_language())?"{$desc}":$this->get_image_field("image", $picture->pid, "description", $lang->slug)).'</textarea></span></div><div style="width:50%;float:left;padding:5px;box-sizing:border-box;"><span class="field gallery_title_field" style="display: block; margin-bottom: 5px;"><label style="line-height: 30px;">Tagi obrazka</label><textarea name="'.(($lang->slug==pll_default_language())?"images[{$picture->pid}][tags]":"plimg[{$picture->pid}][{$lang->slug}][tags]").'" style="width:100%;">'.(($lang->slug==pll_default_language())?"{$tags}":$this->get_image_field("image", $picture->pid, "tags", $lang->slug)).'</textarea></span></div></div>';
						} ?>
						
					</div>
				<?php
				}
			}
			//return $output;
		}
		
		function get_image_field($type, $pid, $field, $lang) {
			global $wpdb;
			$v = $wpdb->get_var( $wpdb->prepare( 
				"
					SELECT value 
					FROM ".$wpdb->prefix."ngg_polylang  
					WHERE fid = %s 
					AND type = %s 
					AND lang = %s 
					AND field = %s
				", 
				$pid, $type, $lang, $field
			) );
			return $v;
		}
		
		function nggcf_save_data($gid, $post) {
			global $wpdb;
			if ( is_array($post["plimg"]) ) {
				foreach (stripslashes_deep($post["plimg"]) as $pid=>$fields) {
					foreach($fields as $lang=>$polyfield) {
						foreach($polyfield as $field=>$value) {
						
							if($row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ngg_polylang WHERE fid = '$pid' AND type = 'image' AND field = '$field'")) {
								$wpdb->query("UPDATE ".$wpdb->prefix."ngg_polylang SET value = '".$wpdb->escape($value)."' WHERE field = '".$field."' AND type = 'image' AND fid = ".$pid);
							} else {
								if($wpdb->escape($value)) {
									$wpdb->query("INSERT INTO ".$wpdb->prefix."ngg_polylang (fid, type, field, value, lang) VALUES ('$pid', 'image', '".$field."', '".$value."', '".$lang."')");
								}
							}
							
						}
					}
				}
			}
			
			if ( is_array($post["plgal"]) ) {
				foreach (stripslashes_deep($post["plgal"]) as $pid=>$fields) {
					foreach($fields as $lang=>$polyfield) {
						foreach($polyfield as $field=>$value) {
							if($row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ngg_polylang WHERE fid = '$pid' AND type = 'gallery' AND field = '$field'")) {
								$wpdb->query("UPDATE ".$wpdb->prefix."ngg_polylang SET value = '".$wpdb->escape($value)."' WHERE field = '".$field."' AND type = 'gallery' AND fid = ".$pid);
							} else {
								if($wpdb->escape($value)) {
									$wpdb->query("INSERT INTO ".$wpdb->prefix."ngg_polylang (fid, type, field, value, lang) VALUES ('$pid', 'gallery', '".$field."', '".$value."', '".$lang."')");
								}
							}
						}
					}
				}
			}
		}
		
		/*	DODANIE DO "OPCJA EKRANU"	*/
		function nggcf_manage_cols($gallery_columns) {
			global $wpdb;
			$fields = array('jedno');
			
			foreach ($fields as $key=>$val) {
				$gallery_columns[htmlspecialchars($val)] = htmlspecialchars($val);
			}
			
			return $gallery_columns;
		}
	
		//the field for managing the images in a gallery
		function nggcf_admin_col($gallery_column_key, $pid) {
			global $wpdb, $ngg_edit_gallery;
			echo "Pole";
		}
		
	}
}

/*
 *	SPRAWDZ CZY PLUGIN UZYWANY W MODULE JEST ZAINSTALOWANY W SYSTEMIE
 *	JESLI JEST AKTYWUJ WSZYSTKIE OPCJE MODULU
 *	JESLI NIE JEST, WYLACZ MODUL
 */
function checkActiveNextGenGallery() {
	if ( !class_exists( 'nggGallery' ) ) {
		$opcje = get_option('active_plugins');
		$findPlug = array_search('nextgen_polylang/nextgen_polylang.php', $opcje);
		unset($opcje[$findPlug]);
		update_option('active_plugins', $opcje);
		add_action( 'admin_notices', 'installFirstWarning', 14 );
	} else {
		register_activation_hook(__FILE__, array('NGG_Polylang', 'activate'));
		register_deactivation_hook(__FILE__, array('NGG_Polylang', 'deactivate'));
		$NGG_Polylang = new NGG_Polylang();
	}
}

function installFirstWarning() {
	echo "<style>.updated { display: none; }</style><div class=\"error\"><p>Moduł NextGen Gallery nie jest zainstalowany!</p><p><a href=\"".admin_url('plugin-install.php?tab=search&s=NextGEN+Gallery')."\" class=\"button button-primary button-large\">Tak, zainstaluj NextGen Gallery</a></p></div>";
}

/*
 *	INICJACJA MODUŁU
 *	Z WCZESNIEJSZYM SPRAWDZANIEM WYMAGAŃ
 */
if(class_exists('NGG_Polylang')) {
	//add_action( 'init', 'checkActiveNextGenGallery' );
	if ( !class_exists( 'nggGallery' ) ) {
		$opcje = get_option('active_plugins');
		$findPlug = array_search('nextgen_polylang/nextgen_polylang.php', $opcje);
		unset($opcje[$findPlug]);
		update_option('active_plugins', $opcje);
		add_action( 'admin_notices', 'installFirstWarning', 14 );
	} else {
		register_activation_hook(__FILE__, array('NGG_Polylang', 'activate'));
		register_deactivation_hook(__FILE__, array('NGG_Polylang', 'deactivate'));
		$NGG_Polylang = new NGG_Polylang();
	}	
}
