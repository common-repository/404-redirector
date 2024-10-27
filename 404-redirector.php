<?php
/**
 * Plugin Name: 404 ReDirector
 * Plugin URI: www.brainfruit.com
 * Description: 
 * Version: 1.0.2
 * Author: brainfruit
 * Author URI: www.brainfruit.com
 * Text Domain: brainfruit_redirect
 * License: GPL2
 */

function bfr_redirector404_currentPageURL() {
    //$pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {
		//$pageURL .= "s";
	}
	$pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
  	} 
    else {
		$pageURL .= $_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}


/*##############################################################################                

                Hole alle Pages und Posts / Get Pages and posts
Parameter:
 * $SlugsRaw = Array to store all avaible Pages
*/##############################################################################
function bfr_redirector404_getPages($SlugsRaw) {
                    
    $args = array(
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'post_type' => 'page',
            'post_status' => 'publish'
    );
    $pages = get_pages( $args ); 

    foreach ( $pages as $key ) {
            $pageLink = get_page_link( $key->ID );
            array_push( $SlugsRaw, $pageLink );
    }
    
    return $SlugsRaw;
}

/*##############################################################################                

                    Hole alle Kategorien / Get Categories
Parameter:
 * $SlugsRaw = Array to store all avaible Categories
*/##############################################################################
function bfr_redirector404_getCats($SlugsRaw) {
                    
    $args = array(                                                            
        'orderby'                  => 'name',
        'order'                    => 'ASC',
        'hide_empty'               => 1,
        'hierarchical'             => 1,                                                            
        'taxonomy'                 => 'category',
        'pad_counts'               => false 
    ); 

    $categories = get_categories( $args );

    foreach ( $categories as $category ) {	            
        $pageLink = get_category_link( $category->term_id );                        
        array_push( $SlugsRaw, $pageLink );
    }
    
    return $SlugsRaw;
}


/*##############################################################################                

                Hole alle Tags / Get Tags
Parameter:
 * $SlugsRaw = Array to store all avaible Tags
*/##############################################################################
function bfr_redirector404_getTags($SlugsRaw) {
                    
    $args = array(                                                            
        'orderby'                  => 'name',
        'order'                    => 'ASC',
        'hide_empty'               => 'false',
        'get'                      => 'all'
    ); 

    $tags = get_tags( $args );

    foreach ( $tags as $tag ) {	            
        $pageLink = get_tag_link( $tag->term_id );                        
        array_push( $SlugsRaw, $pageLink );                                                                                                
    }            
    
    return $SlugsRaw;
}


/*##############################################################################                

                Kalkulieren �bereinstimmung / evaluate similitary 
Parameter:
 * $SlugsRaw = the Array with the avaible Sites,Tags and Categories
 * $removeMe = the Site url
 * $category_permalink = the Permalink / the Base of your Category Pages
 * $tag_permalink = the Permalink / the Base of your Tag Pages
 * $lastUrlQueryString = the URL Query String
*/##############################################################################
function bfr_redirector404_calc($SlugsRaw, $removeMe, $category_permalink, $tag_permalink, $lastUrlQueryString) {
    $closestValue = 1000;
    $closestLink = '';
    $mixin = array();    
    
    foreach( $SlugsRaw as $pageLink ) {
            $wholeLink = $pageLink;
            $trimedLink = str_replace( $removeMe, '', $pageLink );                        

            if (strpos($trimedLink ,$category_permalink) !== false)
            {
                $trimedLink = str_replace( $category_permalink, '', $trimedLink );                            
            }
            else if (strpos($trimedLink ,$tag_permalink) !== false)
            {
                $trimedLink = str_replace( $tag_permalink, '', $trimedLink );                            
            }

            $trimedLink = trim(str_replace( '/', '', $trimedLink ));
                    
            //Vergleich
            $similarityLevel = levenshtein( $trimedLink, $lastUrlQueryString );

            // echo 'TrimedLink= ' . $trimedLink . '<br>'; 
            // echo 'LastURLQueryString= ' .   $lastUrlQueryString . '<br>'; 
            // echo 'Level = ' .   $similarityLevel . '<br>_______________________________________<br>'; 

            array_push( $mixin, array( 'similar' => $similarityLevel, 'link' => $wholeLink ) );
    }

    for( $i = 0; $i < count($mixin); $i++ ){
            if( $closestValue > $mixin[$i]['similar'] ){
                    $closestValue = $mixin[$i]['similar'];
                    $closestLink = $mixin[$i]['link'];
            }
    }                
    
/*##############################################################################                
                Wenn nur ein Artikel in dieser Kategorie, dann zeige diesen.
 * F�r eine sp�tere Version
*/##############################################################################
/*                                
    if (strpos($closestLink ,$category_permalink) !== false)
    {
        $closestCategory = str_replace( $removeMe, '', $closestLink );
        $closestCategory = str_replace( $category_permalink, '', $closestCategory );     
        $closestCategory = trim(str_replace( '/', '', $closestCategory ));                    

        $posts = query_posts( 'category_name=' . $closestCategory );

        if (count($posts) == 1) {
            $closestLink = get_permalink($posts[0]->ID);                        
        }                                        
        wp_reset_query();
    }
                
*/              
/*##############################################################################                
                Wenn nur ein Artikel diesem Tag zugeordnet ist, dann zeige diesen.
 * F�r eine sp�tere Version
*/##############################################################################
/*                             
    if (strpos($closestLink ,$tag_permalink) !== false)
    {
        $closestTag = str_replace( $removeMe, '', $closestLink );                                                            
        $closestTag = str_replace( $tag_permalink, '', $closestTag );                                                                
        $closestTag = trim(str_replace( '/', '', $closestTag ));                                                                                                    

        $posts = query_posts( 'tag=' . $closestTag );                                                            

        if (count($posts) == 1) {
            $closestLink = get_permalink($posts[0]->ID);                        
        }          
        wp_reset_query();                                        
    }
*/   
    return $closestLink;
}


function bfr_redirector404_main() {

	if( is_404() ){

		$SlugsRaw = array();			
		$removeMe = site_url();
		$url = bfr_redirector404_currentPageURL();	
        
        // echo $url;
        	
        // $lastUrlQueryString = array_pop( explode( "/", $url ) );                
        // 
        // //Added to fix Bug with multiple Folders          
        // if (strlen($lastUrlQueryString) < 3)
        // {
        //     $UrlQueryArray = explode( "/", $url );
        //     $UrlQueryIndex = count($UrlQueryArray);
        //     
        //     // echo '<pre>';
        //     // print_r($UrlQueryArray);
        //     // echo '</pre>';
        //     // 
        //     // echo count($UrlQueryArray);
        //     // echo $UrlQueryArray[$UrlQueryIndex];
        //     
        //     $lastUrlQueryString = $UrlQueryArray[$UrlQueryIndex -2];
        // }                                   
                //Hole alle Seiten, Kategorien und Tags / get all Pages, Categories and Tags
                $SlugsRaw = bfr_redirector404_getPages($SlugsRaw);                
                $SlugsRaw = bfr_redirector404_getTags($SlugsRaw);                
                $SlugsRaw = bfr_redirector404_getCats($SlugsRaw);
                                
                
                //Hole Tag Permalink / get tag Permalink                 
                $tag_permalink = get_option( 'tag_base' );

                if ($tag_permalink == "")
                {
                    $tag_permalink = "tag";
                }
                
                $TagRemoved = false;
                $TagPermalinkPos = strpos($url, $tag_permalink);
                 
                if ($TagPermalinkPos !== false)
                {
                    $lastUrlQueryString = array_pop( explode( $tag_permalink, $url ) );
                    $TagRemoved = true;
                }
                              
                                    
                //Hole Kategorie Permalink / get category Permalink                              
                $category_permalink = get_option( 'category_base' );
                
                if ($category_permalink == "")
                {
                    $category_permalink = "category";
                }
                
                $categoryRemoved = false;
                $CategoryPermalinkPos = strpos($url, $category_permalink);
                 
                if ($CategoryPermalinkPos !== false)
                {
                    $lastUrlQueryString = array_pop( explode( $CategoryPermalinkPos, $url ) );
                    $categoryRemoved = true;
                }
                
                
                if (!$categoryRemoved && !$TagRemoved)
                {
                    $lastUrlQueryString = $url;
                }
                
                // echo 'LastURLQueryString = ' . $lastUrlQueryString . '<br>';
                               
                //Hole den nahe liegesten Link / get the closest Link
                $closestLink = bfr_redirector404_calc($SlugsRaw, $removeMe, $category_permalink, $tag_permalink, $lastUrlQueryString);
                
                //Weiterleiten / Redirect
		wp_redirect( $closestLink, 301 );
	}

}
add_action( 'template_redirect', 'bfr_redirector404_main' );
?>