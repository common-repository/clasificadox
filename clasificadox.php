<?php
/*
Plugin Name: ClasificadoX
Plugin URI: http://clasificadox.com/plugin-anuncios-clasificados-para-WordPress
Description: Muestra los anuncios de ClasificadoX. Puedes configurarlo para que muestre tus propios anuncios u otros anuncios.
Version: 1.0
Author: ClasificadoX
Author URI: http://clasificadox.com
*/

/*
 * Falta: 
 * - Idiomas
 * - CSS
 */

// Cuando se inicializa el widget llamaremos al metodo register de la clase Widget_anunciosCX
add_action( "widgets_init", array( "Widget_anunciosCX", "register" ) );

// Cuando se active el plugin se llamara al metodo activate de la clase Widget_anunciosCX
// donde añadiremos los argumentos por defecto para que funcione el plugin
register_activation_hook( __FILE__, array( "Widget_anunciosCX", "activate" ) );

// Cuando se desactive el plugin se llamara al metodo desactivate de la clase Widget_anunciosCX
// donde se eliminaran los argumentos anteriormente guardados, para tener una DB limpia
register_deactivation_hook( __FILE__, array( "Widget_anunciosCX", "deactivate" ) );

function load_css_cx(){
    $pluginDirComplete = plugin_basename(dirname(__FILE__));
    $pluginsWPDirComplete = basename(dirname(dirname(__FILE__)));

    $urlSite = get_settings('siteurl');
    $urlCSS = $urlSite . '/wp-content/'.$pluginsWPDirComplete.'/'.$pluginDirComplete.'/clasificadox.css';

    wp_enqueue_style('on__traffsend', $urlCSS);
}

add_action('wp_print_styles', 'load_css_cx');


// Clase Widget_anunciosCX
class Widget_anunciosCX
{
    // Metodo que se llamara cuando se visualize el Widget en pantalla
    function widget($args)
    {
        
	// Variables
	$aData     = get_option( "anunciosCX" );
        
                echo $args["before_widget"];
                echo $args["before_title"] . " " .$aData['TITLE'] ." ". $args["after_title"];
                
                $host='http://clasificadox.com/ad/widget/get-ads.php';

                if ( function_exists('curl_init') ) {
                    $fields = array(
                                            'num_ads' => urlencode($aData['NUM_ADS']),
                                            'email' => urlencode($aData['EMAIL']),
                                            'source' => urlencode($_SERVER['HTTP_HOST'])
                                            );

                    //url-ify the data for the POST
                    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
                    rtrim($fields_string, '&');

                    //use cURL to fetch data
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $host);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch,CURLOPT_POST, count($fields));
                    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                    $response = curl_exec($ch);
                    curl_close ($ch);
                } else if ( ini_get('allow_url_fopen') ) {
                    $response = file_get_contents($host, 'r');
                }
                
                $n=0;
                
                if ($response){
                    
                    echo '<div style="width:100%;text-align:center">';
                    $ads=  json_decode($response);
                    if ($ads->premium_ads){
                        foreach ($ads->premium_ads as $ad=>$a){
                            $n++;if ($n>$aData['NUM_ADS']) break;
                            $sPrice='';if (!empty($a->price)) $sPrice='<span class="list-prices">'.$a->price.' '.$a->currency.'</span>';
                            echo '<div class="small-list-item premium" style="padding-bottom:20px;">
                                        <div class="small-list-img" style="width:100%;max-width:170px;text-align:center">
                                            <a target="_blank" id="link" title="'.$a->title.'" href="'.str_replace('develop.','',$a->ad_url).'?utm_source=widget">
                                                <img style="width:100%" align="left" title="'.$a->title.'" alt="'.$a->title.'" src="'.$a->img_url .'">
                                            </a>
                                        </div>
                                        <div class="small-list-title" style="float:left;width:90%;max-width:170px;">
                                            <p>'.$a->phone1 .'</p>
                                            <a target="_blank" id="link" title="'.$a->title.'" href="'.str_replace('develop.','',$a->ad_url).'?utm_source=widget">
                                                <h3>'.$a->title .'</h3>
                                            </a>
                                            <span class="list-prices">'.$sPrice .'</span>
                                        </div>
                                    </div>';
                        }
                    }
                    
                    if ($ads->ads){
                        foreach ($ads->ads as $ad=>$a){
                            $n++;if ($n>$aData['NUM_ADS']) break;
                            $sPrice='';if (!empty($a->price)) $sPrice='<span class="list-prices">'.$a->price.' '.$a->currency.'</span>';
                            echo '<div class="small-list-item" style="padding-bottom:20px;">
                                        <div class="small-list-img" style="width:100%;max-width:170px;text-align:center">
                                            <a target="_blank" id="link" title="'.$a->title.'" href="'.str_replace('develop.','',$a->ad_url).'">
                                                <img style="width:100%" align="left" title="'.$a->title.'" alt="'.$a->title.'" src="'.$a->medium_img_url .'">
                                            </a>
                                        </div>
                                        <div class="small-list-title" style="float:left;width:90%;max-width:170px;">
                                            <p>'.$a->phone1 .'</p>
                                            <a target="_blank" id="link" title="'.$a->title.'" href="'.str_replace('develop.','',$a->ad_url).'">
                                                <h3>'.$a->title .'</h3>
                                            </a>
                                            <span class="list-prices">'.$sPrice .'</span>
                                        </div>
                                    </div>';
                        }
                    }
                    echo '</div>';
                }
                
                
                echo $args["after_widget"];
    }

    // Meotodo que se llamara cuando se inicialice el Widget
    function register()
    {
        // Incluimos el widget en el panel control de Widgets
        register_sidebar_widget( "Últimos anuncios ClasificadoX", array( "Widget_anunciosCX", "widget" ) );

        // Formulario para editar las propiedades de nuestro Widget
        register_widget_control( "Últimos anuncios ClasificadoX", array( "Widget_anunciosCX", "control" ) );
    }
    
    function activate()
    {
        // Argumentos y sus valores por defecto
        $aData = array( "EMAIL" => '', "NUM_ADS" => 5, 'TITLE'=>'Últimos anuncios de ClasificadoX' );

        // Comprobamos si existe opciones para este Widget, si no existe las creamos por el contrario actualizamos
        if( ! get_option( "anunciosCX" ) )
            add_option( "anunciosCX" , $aData );
        else
            update_option( "anunciosCX" , $aData);
    }

    function deactivate()
    {
        // Cuando se desactive el plugin se eliminaran todas las filas de la DB que le sirven a este plugin
        delete_option( "anunciosCX" );
    }
    
    // Panel de control que se mostrara abajo de nuestro Widget en el panel de configuración de Widgets
    // Panel de control que se mostrara abajo de nuestro Widget en el panel de configuración de Widgets
    function control(){
        $aData = get_option( "anunciosCX" );

        // Mostraremos un formulario en HTML para modificar los valores del Widget
        ?>
            <p>
                <label>Título:</label>
                <input name="anunciosCX_TITLE" type="text" value="<?php echo $aData["TITLE"] ?>" class="widefat" />
            </p>
            <p>
                <label>Anuncios a mostrar:</label>
                <input name="anunciosCX_NUM_ADS" type="text" value="<?php echo $aData["NUM_ADS"] ?>" />
            </p>
            <p>
                <label>Correo electrónico (Mostrará los anuncios del usuario con este correo) [Opcional]:</label>
                <input name="anunciosCX_EMAIL" type="text" value="<?php echo $aData["EMAIL"]?>" class="widefat" />
            </p>
        <?php

        // Si se ha enviado uno de los valores del formulario por POST actualizaremos los datos
        if( isset( $_POST["anunciosCX_SIZE_AVATAR"] ) )
        {
            $aData["TITLE"] = attribute_escape( $_POST["anunciosCX_TITLE"] );
            $aData["NUM_ADS"] = attribute_escape( $_POST["anunciosCX_NUM_ADS"] );
            $aData["EMAIL"] = attribute_escape( $_POST["anunciosCX_EMAIL"] );

            update_option( "anunciosCX", $aData );
        }
    }
}
?>