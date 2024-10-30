<?php
/**
* Backend Class
* inspired by Joost de Valk's Admin Class.
* Version 1.0
*/

if (!class_exists('Drumba_Plugin_Admin')) 
{
    class Drumba_Plugin_Admin {

        var $hook = '';
        var $filename = '';
        var $longname = '';
        var $shortname = '';
        var $optionname = '';
        var $homepage = '';
        var $accesslvl = 'manage_options';
        var $options = array();

        function Drumba_Plugin_Admin() 
        {
            add_action('init', array(&$this, 'load_my_plugin_textdomain'));
            add_action('admin_menu', array(&$this, 'register_settings_page'));
            add_filter('plugin_action_links', array(&$this, 'add_action_link'), 10, 2);

            add_action('admin_print_scripts', array(&$this,'config_page_scripts'));
            add_action('admin_print_styles', array(&$this,'config_page_styles'));
        }

        function config_page_styles()
        {
            if (isset($_GET['page']) && $_GET['page'] == $this->hook) {
                wp_enqueue_style('dashboard');
                wp_enqueue_style('thickbox');
                wp_enqueue_style('global');
                wp_enqueue_style('wp-admin');
            } 
        }
        
        function config_page_scripts()
        {
            if (isset($_GET['page']) && $_GET['page'] == $this->hook) {
                wp_enqueue_script('postbox');
                wp_enqueue_script('dashboard');
                wp_enqueue_script('thickbox');
                wp_enqueue_script('media-upload');
            }
        }

        function register_settings_page()
        {
            add_options_page($this->longname, $this->shortname, $this->accesslvl, $this->hook, array(&$this,'config_page'));
        }
        
        function load_my_plugin_textdomain()
        {
            load_plugin_textdomain( $this->hook, null, dirname($this->filename).'/lang' );
        }

        function plugin_options_url() {
            return admin_url( 'options-general.php?page='.$this->hook );
        }

        function add_action_link( $links, $file )
        {
            static $this_plugin;
            if( empty($this_plugin) ) $this_plugin = $this->filename;
            if ( $file == $this_plugin ) {
                $settings_link = '<a href="' . $this->plugin_options_url() . '">' . __('Einstellungen', $this->hook) . '</a>';
                array_unshift( $links, $settings_link );
            }
            return $links;
        }

        function config_page() 
        { 
        }

        /**
        * Create a Checkbox input field
        */
        function checkbox($id)
        {
            $options = get_option($this->optionname);
            return '<input type="checkbox" id="'.$id.'" name="'.$id.'"'. checked($options[$id],true,false).'/>';
        }

        /**
        * Create a Text input field
        */
        function textinput($id)
        {
            $options = get_option($this->optionname);
            return '<input size="45" type="text" id="'.$id.'" name="'.$id.'" value="'.htmlentities($options[$id]).'"/>';
        }
        
        /**
        * Create a select box
        */
        function select($id, $opt)
        {
            $options = get_option($this->optionname);
            $select = '<select name="'.$id.'" id="'.$id.'">';
                foreach($opt as $key => $value) {
                    $selected = ($options[$id] == $key) ? 'selected="selected"' : '';
                    $select .= '<option value="'.$key.'" '. $selected . '>' . $value . '</option>';
                }
            $select .= '</select>';
            return $select;
        }

        /**
        * Create a postbox widget
        */
        function postbox($id, $title, $content, $closed = '')
        {
            ?>
            <div id="<?php echo $id; ?>" class="postbox <?php echo $closed; ?>">
                <div class="handlediv" title="Click to toggle"><br /></div>
                <h3 class="hndle"><span><?php echo $title; ?></span></h3>
                <div class="inside">
                    <?php echo $content; ?>
                </div>
            </div>
            <?php
        }


        /**
        * Create a form table from an array of rows
        */
        function form_table($rows)
        {
            $content = '<table class="form-table">';
            foreach ($rows as $row) {
                $content .= '<tr valign="top"><th scope="row">';
                if (isset($row['id']) && $row['id'] != '')
                    $content .= '<label for="'.$row['id'].'">'.$row['label'].':</label>';
                else
                    $content .= $row['label'];
                $content .= '</th><td valign="top">';
                $content .= $row['content'];
                if (isset($row['desc']) && $row['desc'] != '')
                    $content .= '<br/>'.$row['desc'].'';
                $content .= '</td></tr>'; 
            }
            $content .= '</table>';
            return $content;
        }

        /**
        * Create a "plugin like" box.
        */
        function plugin_like()
        {
            $content = '<p>'.__('Dann mach doch eines oder alle der folgenden Dinge:',$this->hook).'</p>';
            $content .= '<ul>';
            $content .= '<li><a href="'.$this->homepage.'">'.__('Verlinke es, dass auch andere freude daran haben können.',$this->hook).'</a></li>';
            $content .= '<li><a href="http://wordpress.org/extend/plugins/'.$this->hook.'/">'.__('Vergib eine gute Bewertung auf WordPress.org.',$this->hook).'</a></li>';
            $content .= '<li><a href="http://bit.ly/donate4me">'.__('Spende einen beliebigen Betrag als Zeichen der Anerkennung meiner Arbeit.',$this->hook).'</a></li>';
            $content .= '</ul>';
            $this->postbox($this->hook.'like', 'Dir gefällt das Plugin?', $content, 'closed');
        }

        /**
        * Info box with link to the support forums.
        */
        function plugin_support()
        {
            $content = '<p>'.__('Wenn du Probleme oder gute Ideen für neue Features hast, dann sprich doch darüber im',$this->hook).' <a href="http://wordpress.org/tags/'.$this->hook.'">'.__("Support forum",$this->hook).'</a>.</p>';
            $this->postbox($this->hook.'support', 'Du benötigst Support?', $content, 'closed');
        }

        function text_limit( $text, $limit, $finish = ' [&hellip;]')
        {
            if( strlen( $text ) > $limit ) {
                $text = substr( $text, 0, $limit );
                $text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
                $text .= $finish;
            }
            return $text;
        }
    }
}

?>