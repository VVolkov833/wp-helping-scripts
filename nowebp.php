<pre><?php
/*

removeWEPBs(); removes all *.webp files from current and inheritor directories

1. prints all *.webp files on dry run
2. deletes all *.webp files with ?unlink query

*/

// run in the root WP directory to remove all *.webp files from the 'uploads' directory
removeWEPBs( __DIR__ . '/' . 'wp-content/uploads' );


function removeWEPBs( $dir = __DIR__ ) {

    if ( !is_dir( $dir ) ) {
        echo $dir.' <font color="#ff0000">dir not found</font>';
        return;
    }

    $opened = opendir( $dir );
    while( $read = readdir( $opened ) ) {

        if( $read == '.' || $read == '..' )
            continue;

        $read = $dir.'/'.$read;
        if ( is_dir($read) ) {
            removeWEPBs( $read );
            continue;
        }
            
        if ( is_file( $read ) && substr( $read, -5 ) == '.webp' ) {
            echo "\n".$read;

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
