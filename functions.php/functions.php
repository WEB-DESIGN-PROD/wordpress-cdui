<!--
    1) Au niveau des réglages, médias, mettre à zéro toutes les dimensions.

    2) Au réglages d'elementor, avancé, mettre 
    - Désactiver les typos Google font ( uploader une typo en woff2 )

    3) Pour la partie elementor -> fonctionnalités, voir les réglages dans le dosssier performance
-->

<!--  ajouter ce code au functions.php pour optimiser les performances avec ELEMENTOR  -->
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

//Ensure Webfont is Loaded
add_filter( 'elementor_pro/custom_fonts/font_display', function( $current_value, $font_family, $data ) {
	return 'swap';
}, 10, 3 );

//Stop Lazy Load
add_filter( 'wp_lazy_loading_enabled', '__return_false' );

//Remove Unused JS

/**
 * We will Dequeue the jQuery UI script as example.
 *
 * Hooked to the wp_print_scripts action, with a late priority (99),
 * so that it is after the script was enqueued.
 */
function wp_remove_scripts() {
// check if user is admina
 if (current_user_can( 'update_core' )) {
            return;
        } 
 else {
    // Check for the page you want to target
    if ( is_page( 'homepage' ) ) {
        // Remove Scripts
  wp_dequeue_style( 'jquery-ui-core' );
     }
 }
}
add_action( 'wp_enqueue_scripts', 'wp_remove_scripts', 99 );


//Explicit Fixed Width and Height

add_filter( 'the_content', 'add_image_dimensions' );

function add_image_dimensions( $content ) {

    preg_match_all( '/<img[^>]+>/i', $content, $images);

    if (count($images) < 1)
        return $content;

    foreach ($images[0] as $image) {
        preg_match_all( '/(alt|title|src|width|class|id|height)=("[^"]*")/i', $image, $img );

        if ( !in_array( 'src', $img[1] ) )
            continue;

        if ( !in_array( 'width', $img[1] ) || !in_array( 'height', $img[1] ) ) {
            $src = $img[2][ array_search('src', $img[1]) ];
            $alt = in_array( 'alt', $img[1] ) ? ' alt=' . $img[2][ array_search('alt', $img[1]) ] : '';
            $title = in_array( 'title', $img[1] ) ? ' title=' . $img[2][ array_search('title', $img[1]) ] : '';
            $class = in_array( 'class', $img[1] ) ? ' class=' . $img[2][ array_search('class', $img[1]) ] : '';
            $id = in_array( 'id', $img[1] ) ? ' id=' . $img[2][ array_search('id', $img[1]) ] : '';
            list( $width, $height, $type, $attr ) = getimagesize( str_replace( "\"", "" , $src ) );

            $image_tag = sprintf( '<img src=%s%s%s%s%s width="%d" height="%d" />', $src, $alt, $title, $class, $id, $width, $height );
            $content = str_replace($image, $image_tag, $content);
        }
    }

    return $content;
}

//Remove Gutenberg CSS
//Remove Gutenberg Block Library CSS from loading on the frontend
function smartwp_remove_wp_block_library_css(){
 wp_dequeue_style( 'wp-block-library' );
 wp_dequeue_style( 'wp-block-library-theme' );
}
add_action( 'wp_enqueue_scripts', 'smartwp_remove_wp_block_library_css' );


//Purge your Site
/*
Plugin Name: Purge Cache
Description: Adds a button to the WordPress dashboard to clear the object cache
*/

add_action( 'admin_bar_menu', 'add_purge_cache_button', 999 );

function add_purge_cache_button( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $args = array(
        'id'    => 'purge-cache',
        'title' => 'Purge Cache',
        'href'  => '#',
        'meta'  => array( 'class' => 'purge-cache' )
    );
    $wp_admin_bar->add_node( $args );
}

add_action( 'admin_footer', 'add_purge_cache_script' );

function add_purge_cache_script() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#wp-admin-bar-purge-cache').click(function() {
            if (confirm('Are you sure you want to purge the cache?')) {
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    data: {
                        action: 'purge_cache',
                    },
                    success: function() {
                        alert('Cache purged successfully!');
                    },
                    error: function() {
                        alert('An error occurred while purging the cache.');
                    }
                });
            }
        });
    });
    </script>
    <?php
}

add_action( 'wp_ajax_purge_cache', 'purge_cache_callback' );

function purge_cache_callback() {
    global $wp_object_cache;
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die();
    }

    wp_cache_flush();

    wp_die();
}




// DIVERSES FONCTIONS

// pouvoir ajouter nos propres css, du dossier nommé customcss il faut donc créer une fonction
function ajouter_css_theme_enfant() {
    // expliquer la fonction...
    wp_enqueue_style(
        // préciser le nom du fichier css à lier
        'main',
        // préciser le dossier où se trouve notre fichier
        get_stylesheet_directory_uri() . '/customcss/main.css',
        [
            // ici on pourrait venir indiquer des dossiers parents
        ],
        // version du fichier
        '1.0.0'
    );
    wp_enqueue_style(
        'header',
        get_stylesheet_directory_uri() . '/customcss/header.css',
        [
        ],
        '1.0.0'
    );
} 
// on vient de déclare à wp l'existence du dossier de css et l'existence d'un fichier main.css et header.css venir exécuter la fonction: add_action 1 argument wp_enqueue_scripts 2eme argument le nom de la fonction crée
// Il a attend en 3ème argument l'ordre de priorité, par défaut 10
add_action('wp_enqueue_scripts', 'ajouter_css_theme_enfant', 20);




// revoir les widgets sur le thème
function revoirWidgets() {
    // register_sidebar attend en paramètre un array, un tableau
    register_sidebar( array(
        'name'              => esc_html__('Barre Latérale'),
        'id'                => 'sidebar-1',
        'description'       => esc_html__('Ajouter des widgets à ma barre latérale'),
        'before_widget'     => '<section id="%1$s" class="widget %2$s">',
        'after_widget'      => '</section>',
        'before_title'      => '<h2 class="widget-title h5">',
        'after_title'       => '</h2>',
    ));
    // on peut ici dupliquer ce register_sidebar pour ajouter autant de zone de widgets que nécessaire
    register_sidebar( array(
        'name'              => esc_html__('Footer 1'),
        'id'                => 'sidebar-2',
        'description'       => esc_html__('Ajouter des widgets à mon footer zone 1'),
        'before_widget'     => '<section id="%1$s" class="widget %2$s">',
        'after_widget'      => '</section>',
        'before_title'      => '<h2 class="widget-title h5">',
        'after_title'       => '</h2>',
    ));
 
}
add_action('widgets_init', 'revoirWidgets');