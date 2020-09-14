<pre><?php

/*

1. specify the regular expression
2. place the file to your wordpress root directory (next to wp-config.php file)

*/

/* the example:
input:
    24-Stunden-Pflege
    24 Stunden Pflege
    24H Stunden Pflege
    24H-Pflege
    24-Stunden-Betreuung
    24 Stunden Betreuung
    24H Betreuung
    24H-Betreuung
    ”24-Stunden-Betreuung„
    ”24-Stunden„ Pflege
    a few more similar variants
output:
    sogenannte 24-Stunden-Pflege
    sogenannte 24 Stunden Pflege
    ...
*/

$pflege = new PregMatchReplace( // when making regexp, make sure it doesn't break everything on second attempt
    '/(?:sogenannte )*([”„]*24[- H]+(?:Stunden[”]?[- ]+)?(?:Pflege|Betreuung))/i', // replace what
    'sogenannte $1' // replace with
);
/*
$content = new TargetDatabase( 'posts', 'post_content', 'ID', $pflege, true,
    '`post_status` = "publish" AND ( `post_type` = "post" OR `post_type` = "page" )',
    '`post_type` ASC, `id` ASC',
    '100000'
);
//*/
//*
$content = new TargetFiles(
    __DIR__ . '/' . 'wp-content/themes',
    ['php'], $pflege, true
);
//*/

$content->test();
//$content->run();


class TargetDatabase {

    private $db, $t, $reg, $hideEmpty;

    public function __construct( $table, $contCol, $IDCol, $PMR, $hideEmpty = false, $where = '', $order = '', $limit = '' ) {
    
        include( 'wp-config.php' );
        $this->db = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
        if (!$this->db) {
            exit;
        }
        $this->db->set_charset( DB_CHARSET );

        if ( strpos( $table, $table_prefix ) === 0 )
            $table = substr( $table, 0, count( $table_prefix ) );
        
        $this->t = (object) [
            'table'   => $table,
            'contCol' => $contCol,
            'IDCol'   => $IDCol,
            'prefix'  => $table_prefix,
            'where'   => $where,
            'order'   => $order,
            'limit'   => $limit
        ];
        
        $this->reg = $PMR;
        $this->hideEmpty = $hideEmpty;
        
    }
    
    public function test() {
        
        if ( !$data = $this->select() )
            return false;
        
        foreach( $data as $v ) {

            $match = $this->reg->match( $v[ $this->t->contCol ] );
            if ( !$this->hideEmpty || $match ) {
                echo $this->report( $v[ $this->t->IDCol ], $match['count'], $match['matches'] );
            }
        }

    }
    
    public function run() {

        if ( !$data = $this->select() )
            return false;
        
        foreach( $data as $v ) {

            $match = $this->reg->replace( $v[ $this->t->contCol ] );
            if ( !$this->hideEmpty || $match ) {
                $this->update( $v[ $this->t->IDCol ], $match['content'] );
                echo $this->report( $v[ $this->t->IDCol ], $match['count'], $match['matches'] );
            }
        }

    }
    
    private function select() {
    
        $posts = 'SELECT

        `'.$this->t->IDCol.'`,
        `'.$this->t->contCol.'`

        FROM `'.$this->t->prefix.$this->t->table.'`
        '.( $where ? 'WHERE '.$this->t->where : '' ).'
        '.( $order ? 'ORDER BY '.$this->t->order : '' ).'
        '.( $limit ? 'LIMIT '.$this->t->limit : '' )
        ;

        return $this->queryToArray( $posts );
    }
    
    private function update( $id, $content ) {

        if ( !$id )
            return false;

        $result = $this->db->query( '
            UPDATE `'.$this->t->prefix.$this->t->table.'`
            SET `'.$this->t->contCol.'` = "'.$this->db->real_escape_string( $content ).'"
            WHERE `'.$this->t->IDCol.'` = "'.$id.'"
        ');
        
        return $result;
    }
    
    private function report( $id, $count = 0, $matches = [] ) {

        return  'ID: '.$id."\n".
                ( $count ? '' : 'NOT ' ).'found or effected: '.
                '<font color="#'.( $count ? '00ff00' : 'ff0000' ).'">'.( $count ? $count : '0' ).'</font> '.
                ( $this->t->table == 'posts' ? '<a href="\?p='.$id.'">&gt;&gt;</a>' : '' ).
                ( $matches[0] ? "\n".'<font color="#999999">'.implode( "\n", $matches ) : '' ).'</font>'.
                "\n";
    
    }

    private function queryToArray( $sql, $id = '' ) {

        $result = $this->db->query( $sql );
        if ( $result->num_rows > 0 ) {
            while( $row = $result->fetch_assoc() ) {
                if ( $id )
                    $return[ $row[$id] ] = $row;
                else
                    $return[] = $row;
            }
            return $return;
        }
        return false;
    }
    
}


class TargetFiles {

    private $dir = '', $types = [], $files = [];

    public function __construct( $dir = __DIR__, $types = ['php'], $PMR, $hideEmpty = false ) {
    
        $this->dir = $dir;
        $this->types = $types;
        
        $this->reg = $PMR;
        $this->hideEmpty = $hideEmpty;
        
    }
    
    public function test() {
        
        $this->scan();
        
        foreach( $this->files as $v ) {

            $content = $this->read( $v );
            $match = $this->reg->match( $content );
            if ( !$this->hideEmpty || $match ) {
                echo $this->report( $v, $match['count'], $match['matches'] );
            }
        }

    }
    
    public function run() {

        $this->scan();
        
        foreach( $this->files as $v ) {

            $content = $this->read( $v );
            $match = $this->reg->replace( $content );
            if ( !$this->hideEmpty || $match ) {
                $this->write( $v, $match['content'] );
                echo $this->report( $v, $match['count'], $match['matches'] );
            }
        }

    }
    
    private function scan( $dir = '' ){
    
        if ( !$dir ) {
            $dir = $this->dir;
            $this->files = [];
        }

        if ( !is_dir( $dir ) ) {
            echo $dir . ' <font color="#ff0000">dir not found</font>';
            return;
        }

        $opened = opendir( $dir );
        while( $read = readdir( $opened ) ){

            if( $read == '.' || $read == '..' )
                continue;

            $read = $dir.'/'.$read;
            if ( is_dir( $read ) ) {
                $this->scan( $read );
                continue;
            }

            if ( is_file( $read ) && in_array( pathinfo( $read, PATHINFO_EXTENSION ), $this->types ) ) {
                $this->files[] = $read;
            }
        }
        
        closedir( $opened );
    }
    
    private function read( $file ) {
        if ( $file_r = fopen( $file, 'r' ) ) {
            $content = fread( $file_r, filesize( $file ) );
            fclose( $file_r );
            return $content;
        }
    }
    
    private function write( $file, $content ) {
        if ( $file_w = fopen( $file, 'w' ) ) {
            fwrite( $file_w, $content );
            fclose( $file_w );
        }
    }

    private function report( $file, $count = 0, $matches = [] ) {

        return  $file."\n".
                ( $count ? '' : ' NOT' ).'found or effected: '.
                '<font color="#'.( $count ? '00ff00' : 'ff0000' ).'">'.( $count ? $count : '0' ).'</font> '.
                ( $matches[0] ? "\n".'<font color="#999999">'.implode( "\n", $matches ) : '' ).'</font>'.
                "\n";
    
    }

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
                return [
                    'matches' => $matches[0],
                    'count'   => count( $matches[0] )
                ];
            else
                return false;
        }
    }
    
    public function replace( $text ) {
        $result = preg_replace( $this->from, $this->to, $text, -1, $count );
        if ( $count ) {
            return [
                'matches' => [],
                'count'   => $count,
                'content' => $result
            ];
        } else {
            return false;
        }
    }

}

?>
</pre>
