<?php 
/*
Plugin Name: imedo Arztsuche
Plugin URI: http://developer.imedo.de/arztsuche-wordpress-plugin
Description: Das Wordpress Plugin dient dazu, die imedo Arztsuche bequem über WordPress in die eigene Webseite zu integrieren.
Version: 1.0.1
Author: Markus Drubba
Author URI: http://www.markusdrubba.de
Text Domain: imedo-arztsuche
Domain Path: /lang
*/

$opt                = array();
$opt['delete_data'] = 0;
$opt['api']         = '';
$opt['size']        = '';

add_option("drumba_arztsuche", $opt);

if ( ! class_exists( 'Drumba_Arztsuche_Admin' ) ) {

    require_once('drumba_plugin_admin.php');
    require_once('integration.php');

    class Drumba_Arztsuche_Admin extends Drumba_Plugin_Admin {
        
        var $hook                   = 'imedo-arztsuche';
        var $longname               = 'imedo Arztsuche';
        var $shortname              = 'Arztsuche';
        var $filename               = 'imedo-arztsuche/imedo-arztsuche.php';
        var $optionname             = 'drumba_arztsuche';
        var $option                 = '';
        var $integration_response   = '';
        
        function Drumba_Arztsuche_Admin()
        {
            parent::__construct();
            $this->option = get_option('drumba_arztsuche');
            add_action('init', array(&$this, 'init_doctors'));
            add_shortcode('arztsuche', array(&$this, 'generate_docsearch'));
            add_action('deactivate_'.plugin_basename(__FILE__), array(&$this,'deactive_plugin'));
        }
        
        function init_doctors()
        {
            if($this->option['api'] != '') 
                $this->integration_response = integrate_doctors($this->option['api']);
        }
        
        function config_page()
        {
            if ( isset($_POST['submit']) ) {
                if (!current_user_can('manage_options')) die(__('Du kannst die imedo Arztsuche Einstellungen nicht bearbeiten.', $this->hook));
                check_admin_referer('drumba-arztsuche-updatesettings');

                foreach (array('api', 'size') as $option_name) {
                    if (isset($_POST[$option_name])) {
                        $opt[$option_name] = htmlentities(html_entity_decode($_POST[$option_name]));
                    }
                }

                foreach (array('delete_data') as $option_name) {
                    if (isset($_POST[$option_name])) {
                        $opt[$option_name] = true;
                    } else {
                        $opt[$option_name] = false;
                    }
                }

                update_option('drumba_arztsuche', $opt);
            }
            
            $opt  = get_option('drumba_arztsuche');
            ?>
            <div class="wrap">
                <a href="http://imedo.de"><div id="imedo-icon" style="background: url(<?php bloginfo('url'); ?>/wp-content/plugins/<?php echo dirname($this->filename); ?>/icon.png) no-repeat;" class="icon32"><br /></div></a>
                <h2><?php _e('imedo Arztsuche Konfiguration', $this->hook); ?></h2>
                <div class="postbox-container" style="width:100%;">
                    <div class="metabox-holder">    
                        <form action="" method="post" id="drumba-arztsuche-conf">
                            <?php if (function_exists('wp_nonce_field'))
                            wp_nonce_field('drumba-arztsuche-updatesettings');
                            
                            $rows = array();
                            $rows[] = array(
                                "id" => "api",
                                "label" => __('API-Key', $this->hook),
                                "content" => $this->textinput('api'),
                                "desc" => __('Den API-Key erhalten Sie auf <a href="http://developer.imedo.de">http://developer.imedo.de</a>', $this->hook)
                            );
                            
                            $rows[] = array(
                                "id" => "size",
                                "label" => __('Gr&ouml;ße', $this->hook),
                                "content" => $this->select('size', array('small' => __('klein', $this->hook), 'medium' => __('mittel', $this->hook), 'large' => __('gro&szlig;', $this->hook))),
                                "desc" => __('Die Größe wurde beim generieren des API-Key festgelegt, Änderungen sind an dieser Stelle nicht möglich', $this->hook)
                            );
                            
                            $rows[] = array(
                                "id" => "delete_data",
                                "label" => __('Plugin Daten l&ouml;schen', $this->hook),
                                "content" => $this->checkbox('delete_data'),
                                "desc" => __('Plugin Daten beim deaktivieren des Plugins l&ouml;schen', $this->hook)
                            );
                            
                            $table = $this->form_table($rows);
                            
                            $this->postbox('arztsuchesettings',__('Einstellungen f&uuml;r imedo Arztsuche', $this->hook), $table.'<div class="submit"><input type="submit" class="button-primary" name="submit" value="'.__('Speichere Arztsuche Einstellungen', $this->hook).'" /></div>')
                            ?>
                        </form>
                    </div>
                </div>
                
                <div class="postbox-container" style="width:100%;">
                    <div class="metabox-holder">    
                            <?php
                            $string = __('Wenn Sie die Arztsuche in Ihrem Blog integrieren wollen, schicken Sie uns bitte eine Anfrage über den unten stehenden Link und erweitern Sie die Mail mit der genauen Seite auf der die Arztsuche integriert werden soll. Bspw.', $this->hook); get_bloginfo('url').'/arztsuche'; __('und die gewünschte Gr&ouml;ße der Integration.', $this->hook);
                            $string .= '<br/><br/><ol><li>small - 300px</li><li>medium - 380px</li><li>large - 680px</li></ol><br/><br/>';
                            $string .= sprintf(__('&raquo; <a href="mailto:%1$s?subject=WordPress API-Key Request from %2$s&amp;body=Hallo, bitte senden Sie mir einen API-Key für eine imedo Arztsuche Integration in meinem WordPress %3$s Blog auf %2$s.">Anfrage senden</a>', $this->hook), 'lizenz@imedo.de', get_bloginfo('url'), get_bloginfo('version'));
                            $this->postbox('arztsucheapirequest','API-Key Anfragen', $string);
                            ?>
                    </div>
                </div>
                
                <div class="postbox-container" style="width:49%;">
                    <div class="metabox-holder">    
                        <?php $this->plugin_like(); ?>
                    </div>
                </div>
                
                <div class="postbox-container" style="width:49%;">
                    <div class="metabox-holder">    
                        <?php $this->plugin_support(); ?>
                    </div>
                </div>
            </div>
        
<?php
        }
        
        function deactive_plugin()
        {
            if($this->option['delete_data']){
                delete_option('drumba_arztsuche');
            }
        }
        
        function generate_docsearch()
        {
            if($this->option['api'] != ''){
                add_action('wp_footer', array(&$this, 'generate_css'));
                return render_integration_response($this->integration_response);
            }
            return $this->api_is_empty();
        }
    
        function generate_css()
        {
            render_integration_stylesheets($this->option['size']);
        }
    
        function api_is_empty()
        {
            return sprintf(__('Geben Sie bitte erst einen API-Key in den <a href="%1$s">Einstellungen</a> zur Arztsuche ein.', $this->hook), $this->plugin_options_url());
        }
    }
    
    $daa = new Drumba_Arztsuche_Admin();
}