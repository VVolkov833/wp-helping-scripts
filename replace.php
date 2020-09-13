<pre><?php

/*
NOT READY!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
1. specify the regular expression
2. place the file to your wordpress root directory (next to wp-config.php file)
3. running the file will do the dry running
4. ?replace query in url will replace all found phrases

*/


include_once( 'wp-config.php' );

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (!$mysqli) {
    exit;
}
$mysqli->set_charset( 'utf8' );

//-------____________--___---____________________--------

/*
24-Stunden-Pflege
24 Stunden Pflege
24H Stunden Pflege
24H-Pflege
24-Stunden-Betreuung
24 Stunden Betreuung
24H Betreuung
24H-Betreuung
and same within quotes ”„
//*/

$pflege = new PregMatchReplace( // when making regexp, make sure it doesn't break everything on second attempt
    '/(?:sogenannte )*([”„]*24[- H]+(?:Stunden[”]?[- ]+)?(?:Pflege|Betreuung)[”]*)/i',
    'sogenannte $1'
);



$posts = 'SELECT

`ID`,
`post_content`,
`post_title`,
`post_type`

FROM `'.$table_prefix.'posts`
WHERE `post_status` = "publish"
ORDER BY `post_type` ASC, `id` ASC
LIMIT 100000';

$posts = queryToArray( $posts );

$total = 0;

foreach ( $posts as $v ) {

    if ( isset( $_GET['replace'] ) ) {
/*
        $result = $mysqli->query( '
            UPDATE `'.$table_prefix.'posts`
            SET `post_content` = "'.$mysqli->real_escape_string( $content ).'"
            WHERE `ID` = "'.$v['ID'].'"
        ');
        
        if ( $result === true ) {
            echo ' <font color="#00ff00">success</font>'."\n";
        } else {
            echo ' <font color="#ff0000">update failed</font> '.$mysqli->error."\n";
        }
//*/
    } else {

/*
        $inTitle = $pflege->match( $v['post_title'] );
        if ( $inTitle ) { // to hide not interesting
            echo formatMatchOutput( array_merge( $v, [
                'text_type' => 'title',
                'found' => $inTitle
            ] ) );
            $total += $inTitle[1];
        }
//*/

        $inContent = $pflege->match( $v['post_content'] );
        if ( $inContent ) { // to hide not interesting
            echo formatMatchOutput( array_merge( $v, [
                'text_type' => 'content',
                'found' => $inContent
            ] ) );
            $total += $inContent[1];
        }
            
    }

}

echo "\n".'Total: '.$total;
if ( isset( $_GET['replace'] ) ) {
    echo "\n".'<a href="'.basename( $_SERVER['PHP_SELF'] ).'">&lt;&lt; Check again</a>';
} else {
    echo "\n".'<a href="?replace">Replace all &gt;&gt;</a>';
}


//____________---__----_____________________-------

function queryToArray( $sql, $id = '' ) {
    global $mysqli;

    $result = $mysqli->query( $sql );

    if ( $result->num_rows > 0 ) {
        
        while( $row = $result->fetch_assoc() ) {
            if ( $id )
                $return[$row[$id]] = $row;
            else
                $return[] = $row;
        }

        return $return;
    }
    
    return false;
}

class PregMatchReplace {

    private $from, $to;

    public function __construct( $from = '', $to = '' ) {
        $this->from = $from;
        $this->to = $to;
    }

    public function match( $text ) {
        if ( preg_match_all( $this->from, $text, $matches, PREG_PATTERN_ORDER ) ) {
            if ( is_array( $matches[0] ) )
                return [ $matches[0], count( $matches[0] ) ];
            else
                return false;
        }
    }
    
    public function replace( $text ) {
        return [ preg_replace( $this->from, $this->to, $text, -1, $count ), $count ];
    }

}

function formatMatchOutput($v) {
    return  $v['post_type'].' ID '.$v['ID'].' '.
            '<font color="#'.( $v['found'] ? '00ff00' : 'ff0000' ).'"> '.( $v['found'] ? $v['found'][1].' found' : 'not found' ).' in '.$v['text_type'].'</font> '.
            '<font color="#888888">'.$v['post_title'].'</font> '.
            '<a href="\?p='.$v['ID'].'">&gt;&gt;</a>'.
            ( $v['found'] ? "\n".implode( "\n", $v['found'][0] ) : '' ).'
';
}

?>
</pre>
