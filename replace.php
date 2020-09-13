<pre><?php

/*
++ add table prefix protection
++ simple navigation
++ on update error
NOT READY!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
1. specify the regular expression
2. place the file to your wordpress root directory (next to wp-config.php file)
3. running the file will do the dry running
4. ?replace query in url will replace all found phrases

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
    '/(?:sogenannte )*([”„]*24[- H]+(?:Stunden[”]?[- ]+)?(?:Pflege|Betreuung)[”]*)/i',
    'sogenannte $1'
);

$content = new TargetDatabase( 'posts', 'post_content', 'ID', $pflege, false,
    '`post_status` = "publish"',
    '`post_type` ASC, `id` ASC',
    '100000'
);

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
        $this->db->set_charset( 'utf8' );

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
        
        $data = $this->select();
        
        foreach( $data as $v ) {

            $match = $this->reg->match( $v[ $this->t->contCol ] );
            if ( !$this->hideEmpty || $match ) {
                echo $this->report( $v[ $this->t->IDCol ], $match['count'], $match['matches'] );
            }
        }

    }
    
    public function run() {
    
    }
    
    private function select() {
    
        $posts = 'SELECT

        `'.$this->t->IDCol.'`,
        `'.$this->t->contCol.'`,

        FROM `'.$this->t->prefix.$this->t->table.'`
        '.( $where ? 'WHERE '.$this->t->where : '' ).'
        '.( $order ? 'ORDER BY '.$this->t->order : '' ).'
        '.( $limit ? 'LIMIT '.$this->t->limit : '' )

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

        return  'ID: '.$id."\t\t".''.( $count ? '' : ' NOT' ).'found or effected: '.$count.
                '<font color="#'.( $count ? '00ff00' : 'ff0000' ).'">'.$count.'</font> '.
                ( $this->t->table == 'posts' ? '<a href="\?p='.$id.'">&gt;&gt;</a>' : '' ).
                ( $matches[0] ? "\n".implode( "\n", $matches ) : '' ).
                "\n".'
';
    
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
                return [ 'matches' => false, 'count' => 0 ];
        }
    }
    
    public function replace( $text ) {
        $result = preg_replace( $this->from, $this->to, $text, -1, $count );
        if ( $count ) {
            return [
                'matches' => [],
                'count' => $count
            ];
        } else {
            return [ 'matches' => false, 'count' => 0 ];
        }
    }

}

?>
</pre>
