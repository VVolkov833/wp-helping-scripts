<pre><?php

/*

removeThumbs(); removes all wordpress thumbnail images from current and inheritor directories, keeping the originals
use a WP plugin, like 'Regenerate Thumbnails' to restore registered sizes

1. prints all thumbnails on dry run
2. deletes all thumbnails with ?unlink query

*/


$types = [ 'jpg', 'jpeg', 'png', 'gif' ];


// run in the root WP directory to remove all thumbnails from the 'uploads' directory
removeThumbs( __DIR__ . '/' . 'wp-content/uploads' );


function removeThumbs( $dir = __DIR__ ){
    global $types;

    if ( !is_dir( $dir ) ) {
        echo $dir.' <font color="#ff0000">dir not found</font>';
        return;
    }

    $opened = opendir( $dir );
    while( $read = readdir( $opened ) ){

        if( $read == '.' || $read == '..' )
            continue;

        $read = $dir.'/'.$read;
        if ( is_dir($read) ) {
            removeThumbs( $read );
            continue;
        }

        $regexp = [ '/^(.*)?\-\d+x\d+\.('.implode( '|', $types ).')$/i', '$1.$2' ];
        
        if ( is_file( $read ) && preg_match( $regexp[0], $read ) ) {

            echo "\n".$read;
        
            $original = preg_replace( $regexp[0], $regexp[1], $read );
            if ( !is_file( $original ) ) {
                echo ' <font color="#ff0000">has no original</font>';
                continue;
            }
        
            if ( isset( $_GET['unlink'] ) ) {
                if ( unlink( $read ) )
                    echo ' <font color="#00ff00">removed</font>';
                else
                    echo ' <font color="#ff0000">error</font>';
                continue;
            }

            echo ' <font color="#0000ff">found</font>';
        }
    }
    
    closedir( $opened );
}

?></pre>
