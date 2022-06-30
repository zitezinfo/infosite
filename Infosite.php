<?php
/*
Plugin Name: Infosite
Description: Questionnaire du forum https://wpfr.net/support/ à voir sur le tableau de bord
Version: 6
*/

namespace Infosite;

class Infosite
{
	
	function __construct()
	{
		
		add_action('wp_dashboard_setup', [$this, 'wp_dashboard_setup'], 8);
		
	}
	
	function wp_dashboard_setup()
	{
		
		$data = get_file_data(__FILE__, ["version" => "Version"]);
		$version_extension = $data["version"];
		
		$chemin_extension = plugins_url("", __FILE__);
		
		
		// for gettext
		$repertoire_extension = basename(__DIR__);
		load_plugin_textdomain('Infosite', false, "$repertoire_extension/languages");
		
		// for javascript
		wp_register_script(
			  "Infosite"
			, "$chemin_extension/liens/js/fwf.js"
			, ['jquery', 'clipboard']
			, $version_extension
			, TRUE // script en pied de page
		);
		wp_localize_script("Infosite", 'fwf_L10n', [
			'ko' => esc_js( __( 'S&eacute;lectionner le texte ci-dessous puis CTRL + C ou Pomme + C', 'Infosite' ) ) . "\n\n\n",
			'ok' => esc_js( __( 'Copi&eacute; dans le presse-papier', 'Infosite' ) ),
		]);
		wp_enqueue_script("Infosite");
		
		// for widget
		wp_add_dashboard_widget( 'Infosite', __( 'wpfr.net/support', 'Infosite' ), [$this, 'widget']);
		
	}
	
	function widget() 
	{
		// version wordpress
		
		$txt[] = sprintf( __( '<strong>- Version de WordPress :</strong> %1$s%2$s', 'Infosite' )
					, $GLOBALS["wp_version"], (is_multisite()) ? ' ' . __( 'multi-site', 'Infosite' ) : '' );
		
		// version php/mysql
		$php_ver = phpversion();
		$mysql_ver = $GLOBALS["wpdb"]->db_version();
		$txt[] = sprintf( __( '<strong>- Version de PHP / MySQL :</strong> %1$s / %2$s', 'Infosite' ), $php_ver, $mysql_ver );
		
		
		// theme
		
		$slug_theme = get_stylesheet();
		$wp_theme = wp_get_theme($slug_theme);
		
		$wp_theme_name = $wp_theme->display( 'Name', true, false );
		
		if (!is_child_theme()) {
			
			$wp_theme_url = $wp_theme->display( 'ThemeURI', true, false );
			
			$txt[] = sprintf(
				  __( '<strong>- Th&egrave;me utilis&eacute; :</strong> %s (slug&nbsp;: %s) pas de thème enfant', 'Infosite' )
				, $wp_theme_name
				, $slug_theme
			);
			
		} else {
			
			$theme_parent = $wp_theme->parent();
			
			$theme_parent_name = $theme_parent->display( 'Name', true, false );
			$wp_theme_url = $theme_parent->display( 'ThemeURI', true, false );
			
			$txt[] = sprintf(
				  __( '<strong>- Th&egrave;me utilis&eacute; :</strong> %s (slug&nbsp;: %s)', 'Infosite' )
				, $wp_theme_name
				, $slug_theme
			);
			
			$txt[] = sprintf(
				  __( '<strong>- Th&egrave;me parent :</strong> %s (slug&nbsp;: %s)', 'Infosite' )
				, $theme_parent_name
				, get_template()
			);
			
		}
		
		if ( !empty( $wp_theme_url ) ) {
			$txt[] = sprintf( __( '<strong>- Th&egrave;me URI :</strong> %s', 'Infosite' ), $wp_theme_url );
		}
		
		
		// extensions
		
		$ms_plugins = [];
		$wp_plugins = [];
		$extensions_desactivees = [];
		
		foreach ((array) get_plugins() as $plugin_file => $plugin_data)
		{
			
			$texte = $plugin_data['Name'] . ' (' . $plugin_data['Version'] . ')';
			
			if (is_plugin_active_for_network($plugin_file)) {
				$ms_plugins[] = $texte;
			} elseif (is_plugin_active($plugin_file)) {
				$wp_plugins[] = $texte;
			} else {
				$extensions_desactivees[] = $texte;
			}
			
		}
		
		if (!empty($wp_plugins)) {
			$txt[] = sprintf( __( '<strong>- Extensions activées :</strong> %s', 'Infosite' ), join( ', ', $wp_plugins ) );
		}
		
		if (!empty($ms_plugins)) {
			$txt[] = sprintf( __( '<strong>- Extensions activées pour le r&eacute;seau :</strong> %s', 'Infosite' ), join( ', ', $ms_plugins ) );
		}
		
		if (!empty($extensions_desactivees)) {
			$txt[] = sprintf( __( '<strong>- Extensions désactivées :</strong> %s', 'Infosite' ), join( ', ', $extensions_desactivees ) );
		}
		
		
		// site url
		$txt[] = sprintf( __( '<strong>- Adresse du site :</strong> %s', 'Infosite' ), $this->get_site_url());
		
		// host
		//$host = $_SERVER['SERVER_SOFTWARE'];
		$host = "";
		
		$txt[] = sprintf( __( '<strong>- H&eacute;bergeur :</strong> %s', 'Infosite' ), $host );
		
		// os
		//	$os = php_uname();
		//	$txt[] = sprintf( __( '<strong>- Nom de l\'o.s. :</strong> %s', 'Infosite' ), $os );
		
		$out  = '';
		$out .= '<div id="fwf_content"><strong>' . __( 'Ma configuration WP actuelle :', 'Infosite' ) . '</strong>';
		$out .= "\n";
		$out .= '<ul><li>' . join( "</li>\n<li>", $txt ) . '</li></ul>';
		$out .= '</div>';
		
		$out .= '<div style="position:relative;">';
			
			foreach( $txt as $k => $v ) $txt[$k] = strip_tags( $txt[$k] );
			
			$out .= '<div style="position:absolute;">';
			$out .= '<textarea id="fwf_copied" style="height:0; width:0; opacity:0;">' .  __( 'Ma configuration WP actuelle :', 'Infosite' ) . "\r\n" . join( "\r\n", $txt ) . '</textarea>';
			$out .= '</div>';
			
			$out .= '<div id="fwf_button">';
			$out .= '<input id="fwf_copy" class="fwf_copy button-primary" type="button" data-clipboard-target="#fwf_copied" value="' . esc_attr( __( 'Copier', 'Infosite' ) ) . '" />';
			$out .= '</div>';
			
		$out .= '</div>';
		
		$out .= '<div><em>' . __( 'Indiquez l\'hébergeur après avoir collé le texte.<br/>exemples&nbsp;: OVH, Ionos, o2switch, etc.', 'Infosite' ) . '</em></div>';
		
		echo $out;
		
	}
	
	function get_site_url()
	{
		return (defined('WP_SITEURL')) ? WP_SITEURL : home_url();
	}
	
}

new Infosite();

