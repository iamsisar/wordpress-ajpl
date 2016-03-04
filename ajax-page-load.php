<?php
/*
Plugin Name: Ajax page load
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Ajax the world, yolo!
Version:     0.0.1
Author:      Cesare Cocito
Author URI:  http://iamsisar.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: ajpl
*/

function asdotrack_skin_scripts() {

	wp_register_script('ajpl-ajax', plugin_dir_url(__FILE__) . 'js/ajax.js', array( 'jquery' ) );
	wp_localize_script( 'ajpl-ajax', 'ajpl', array(
	    'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
	    'ajplNonce'		=> wp_create_nonce( 'ajpl-nonce' ),
	    //'before'    	=> false,
	    'after'         => false
	    )
	);

	wp_enqueue_script('ajpl-ajax', false, array(), false, true);
}

if(!is_admin()) add_action( 'wp_enqueue_scripts', 'asdotrack_skin_scripts' );


add_action('wp_ajax_load_post', 'load_post_callback');
add_action('wp_ajax_nopriv_load_post', 'load_post_callback');


function load_post_callback() {


	// recupera l'id della pagina dal permalink
	if ( isset($_POST['id'] ) ) {
		$id = $_POST['id'];
	} elseif ($_POST['permalink']){
		$id = url_to_postid( $_POST['permalink'] );
	} else {
		wp_send_json_error('No page requested');
	}

	if ($id == 0){
		wp_send_json_error('No page requested');
	}


	// recupera il post
	$args = array(
		'post__in' => array( $id ),
		'post_type' => 'any'
	);

	$the_query = new WP_Query($args);

	if($the_query->have_posts()) : while ( $the_query->have_posts() ) : $the_query->the_post();
		global $more;
		global $post;

		$more = 1;

		// recupera il nome del template, se impostato, altrimenti
		// get_page_template_slug() ritorna stringa vuota...
		$template = get_page_template_slug( $post->ID );
		$pagename = $post->post_name;
		$id = $post->ID;
		$type = $post->post_type;

		// ...in tal caso ipotizza la gerarchia dei template da usare
		// (https://core.trac.wordpress.org/browser/tags/4.4.2/src/wp-includes/template.php#L323)
	    $templates = array();
	        if ( $template && 0 === validate_file( $template ) )
	            $templates[] = $template;
	        if ( $pagename )
	            $templates[] = "$type-$pagename.php";
	        if ( $id )
	            $templates[] = "$type-$id.php";
	    $templates[] = "$type.php";
	    $templates[] = "single.php";

	    $template_file = get_query_template( $type, $templates );

	endwhile; endif;

	// inizializza la query
	query_posts( $args );

	// recupera l'output del template
    ob_start();
    if (locate_template($templates)){
		include($template_file);
    } else {
    	echo 'meh';
    }
    $var = ob_get_contents();
    ob_end_clean();


    // recupera quali script sono in queue per la pagina richiesta
    global $wp_scripts;

    $registered_scripts = (array) $wp_scripts->registered;

    // confronta la queue con gli script registrati e estrae la stampa
    // del tag <script> per preservare le dipendenze
    //
    // TODO: check 'in_footer' e 'conditional'
    foreach ($wp_scripts->queue as $handle) {
    	ob_start();
    	$wp_scripts->print_scripts($handle);
   		$scripts[] = ob_get_contents();
    	ob_end_clean();
    }


    // compone la risposta
    $result['permalink'] = get_the_permalink();
    $result['title'] = get_the_title();
    $result['featured_img'] = wp_get_attachment_image_src( get_post_thumbnail_id() );
    $result['template'] = $template_file;
    $result['content'] = $var;
    $result['scripts'] = $scripts;

    $result['id'] = $id;
    $result['query'] = $the_query;
    $result['post'] = $post;


	wp_send_json_success($result);

	exit;

}


function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}


add_filter('script_loader_tag','add_id_to_script',10,2);
function add_id_to_script($tag, $handle){



	global $wp_scripts;

	$script = $wp_scripts->registered[$handle];

	if ( startsWith($script->src, get_template_directory_uri()) ){
		$tag = "<script class='wp-enqueued-js' id='$script->handle-script' type='text/javascript' src='$script->src'></script>";
	}

	return $tag;
}


// /** Step 2 (from text above). */
// add_action( 'admin_menu', 'my_plugin_menu' );

// /** Step 1. */
// function my_plugin_menu() {
// 	add_options_page( 'AJPL Options', 'Ajax Page Load', 'manage_options', 'ajpl-options', 'ajpl_options_screen' );
// }

// /** Step 3. */
// function ajpl_options_screen() {
// 	if ( !current_user_can( 'manage_options' ) )  {
// 		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
// 	}
// 	echo '<div class="wrap">';
// 	echo '<p>Here is where the form would go if I actually had options.</p>';
// 	echo '</div>';
// }

require 'plugin-update-checker/plugin-update-checker.php';
$className = PucFactory::getLatestClassVersion('PucGitHubChecker');
$myUpdateChecker = new $className(
    'https://github.com/user-name/plugin-repo-name/',
    __FILE__,
    'master'
);


?>