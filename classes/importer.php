<?php
/**
 * SC_Importer class
 *
 * @package wp-softcatala
 */

class SC_Importer
{
    protected $link;
    const csvfile = 'http://softcatala.local/result.csv';
    protected static $baseurl = "http://www.softcatala.org/w/api.php";
    const DB_User = 'rrebost';
    const DB_Pass = 'mypasswd';
    const DB_Name = 'rebost';

    public function __construct()
    {
        $this->link = mysqli_connect('localhost', self::DB_User, self::DB_Pass, self::DB_Name);
    }

    public function run( $i, $j, $step )
    {
        $download_info = $this->open_csv_file( $i, $j, $step );
        $download_info['step'] = $step;

        return $download_info;
    }

    /**
     * Opens the csv file containing all the information to be exported
     *
     * @return array
     */
    private function open_csv_file( $i, $j, $step )
    {
        $row = 1;
        $return['text'] = '';
        if ( ( $handle = fopen( self::csvfile , "r" ) ) !== FALSE ) {
            while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
                if ( $row > $i && $row <= $j ) {
                    $post_name = str_replace( 'Rebost:', '', $data[0] );
                    $slug = sanitize_title($post_name);
                    if ( ! $page = get_page_by_path( $slug , OBJECT, 'programa' ) ) {
                        $download_info[$row] = $this->import_data( $data );
                        $return['text'] .= $this->create_program($download_info[$row]).'<br/>';
                    } elseif ( strpos( get_permalink( $page ), 'programes/' ) == false ) {
                        $download_info[$row] = $this->import_data( $data );
                        $return['text'] .= $this->create_program($download_info[$row]).'<br/>';
                    } else {
                        $return['text'] .= $row . ' - Not imported: '. get_permalink( $page ).'<br/>';
                    }
                }
                $row++;
            }
            fclose($handle);
        }
        $return['i_value'] = $j;
        $return['j_value'] = $j + $step;

        return $return;
    }

    /**
     * Generates an array with all the data ready for the import
     *
     * @param $data
     * @return mixed
     */
    private function import_data( $data )
    {
        $value['post_name'] = str_replace( 'Rebost:', '', $data[0] );
        $value['autor_programa'] = $data[1];
        $value['url_rebost'] = 'https://www.softcatala.org/wiki/'.str_replace( ' ', '_', $data[0] );
        $value['post_content'] = $this->get_wiki_article_content( $value['url_rebost'] );
        $value['imatge_destacada_1'] = $this->get_image_url( $data[2] );
        $value['logotip_programa'] = $this->get_image_url( $data[7] );
        $value['llicencia'] = $this->get_taxonomy_id( $data[6], 'llicencia' );
        $value['vots'] = $data[9];
        $value['valoracio'] = $this->get_final_valoracio( $data[14] );
        $value['categoria-programa'] = $this->get_taxonomy_id( $data[17], 'categoria-programa' );
        $value['preu'] = $data[12];
        $value['lloc_web_programa'] = $data[18];
        $value['hosted_in_sc'] = ( $data[23] = 'fals' ? '0' : '1' );
        $value['slug'] = sanitize_title($value['post_name']);
        $value['idrebost'] = $this->get_idrebost_for_page_namepage( $value['post_name'] );
        $value['arxivat'] = '0';

        $downloads = explode( ',', $data[4] );
        $data[3] = false; //Calculation is not accurate
        if ( $data[3] ) {
            $download_size = str_replace( ',', '', $data[3]);
            $sizes = str_split($download_size, strlen( $download_size ) / count( $downloads ) );
        }

        $os = explode( ',', $data[10] );
        foreach ( $downloads as $key => $download ) {
            $value['program'][$key]['url_baixada'] = $download;
            if ( isset ( $os[$key] ) ) {
                $os_name = $this->generate_os_name($os[$key]);
                $value['program'][$key]['download_os'] = $this->get_taxonomy_id( $os_name, 'sistema-operatiu-programa' );
                $value['program'][$key]['arquitectura_baixada'] = ( strpos($os[$key], '64') ? 2 : 1 );
            }

            $value['program'][$key]['versio_baixada'] = $data[19];
            $value['program'][$key]['versio_estesa_baixada'] = $data[11];

            if ( $data[3] ) {
                $value['program'][$key]['download_size'] = $sizes[$key];
            }
        }

        return $value;
    }

    private function get_wiki_article_content( $url )
    {
        $wikipage = file_get_contents( $url );
        $first_step = explode( '<h2><span class="mw-headline" id="Descripci.C3.B3">Descripció</span></h2>' , $wikipage );
        if ( isset ( $first_step[1] ) ) {
            $second_step = explode("<div id=\"contact_warning\">" , $first_step[1] );
        } else {
            var_dump($url);
            $second_step = explode("<div id=\"contact_warning\">" , $first_step[1] );
        }


        return $second_step[0];
    }

    private function get_idrebost_for_page_namepage( $program_name )
    {
        try {
            $program_name = addslashes( str_replace( ' ', '_', $program_name ) );
            $query = utf8_decode( "SELECT
                       b.idrebost
                  FROM wikidb.page w, rebost.baixades b
                  WHERE w.page_id = b.idrebost
                  AND w.page_title = '$program_name'
                  group by b.idrebost
                  LIMIT 1" );

            $query_result = $this->link->query($query);
            while ($row = $query_result->fetch_object()){
                $result[] = $row;
            }

            return $result[0]->idrebost;
        } catch (Exception $e ) {
            error_log($e, 3, "/var/log/nginx/wordpress.log");
        }

    }

    /**
     * Should import the image from the old website
     *
     * @param $filename
     * @return mixed
     */
    private function get_image_url ( $filename )
    {
        if ( $filename != '' ) {
            $base_sc_url = 'https://www.softcatala.org/';
            $base_scwiki_url = 'https://www.softcatala.org/wiki/';
            $contents = file_get_contents($base_scwiki_url.$filename);
            $first_step = explode( '<div class="fullImageLink" id="file">' , $contents );
            $second_step = explode("</div>" , $first_step[1] );
            preg_match('/<a href=\"([^\"]*)\">(.*)<\/a>/iU', $second_step[0], $match);
            $image_url = $base_sc_url.$match[1];
        } else {
            $image_url = '';
        }

        return $image_url;
    }

    private function create_program( $value )
    {
        $metadata = array(
            'autor_programa' => $value['autor_programa'],
            'lloc_web_programa' => $value['lloc_web_programa'],
            'url_rebost' => $value['url_rebost'],
            'vots' => $value['vots'],
            'valoracio' => $value['valoracio'],
            'preu' => $value['preu'],
            'hosted_in_sc' => $value['hosted_in_sc'],
            'idrebost' => $value['idrebost'],
            'arxivat' => $value['arxivat']
        );

        $terms = array(
            'categoria-programa' => array($value['categoria-programa']),
            'llicencia' => array($value['llicencia'])
        );

        $return = $this->sc_add_draft_content(
            'programa',
            $value['post_name'],
            $value['post_content'],
            $value['slug'],
            $terms,
            $metadata
        );

        if( $return['status'] == 1 ) {
            //Logo and screenshot file upload
            $logo_attach_id = $this->sc_upload_file($value['logotip_programa'], $return['post_id']);
            $screenshot_attach_id = $this->sc_upload_file($value['imatge_destacada_1'], $return['post_id']);
            $metadata = array(
                'logotip_programa' => wp_get_attachment_url($logo_attach_id),
                'imatge_destacada_1' => wp_get_attachment_url($screenshot_attach_id)
            );

            $this->sc_update_metadata($return['post_id'], $metadata);

            foreach ($value['program'] as $baixada) {
                if( isset( $baixada['download_os'] ) ) {
                    $terms_baixada = array(
                        'sistema-operatiu-programa' => array($baixada['download_os'])
                    );

                    $metadata_baixada = array(
                        'url_baixada' => $baixada['url_baixada'],
                        'versio_baixada' => $baixada['versio_baixada'],
                        'arquitectura_baixada' => $baixada['arquitectura_baixada'],
                        'post_id' => $return['post_id']
                    );

                    if ( $baixada['url_baixada'] != '' ) {
                        $return_baixada = $this->sc_add_draft_content('baixada', $value['post_name'], '', $value['slug'], $terms_baixada, $metadata_baixada);
                    }
                }
            }
        }

        return 'Imported program: '.$value['post_name'];
    }

    /**
     * Returns the rating number with '.' in case the one from the csv doesn't contains it
     *
     * @param $valoracio
     * @return string
     */
    private function get_final_valoracio( $valoracio )
    {
        if ( strlen($valoracio) > '3' && ! strpos($valoracio, '.') ) {
            $splited = str_split($valoracio, 1);
            $splited[0] = $splited[0].'.';
            $valoracio = implode('', $splited);
        }

        return $valoracio;
    }

    /**
     * Obtains the taxonomy id for a given name. If it doesn't exist, it creates it
     *
     * @param $taxonomy_name
     * @param $taxonomy
     * @return mixed
     */
    private function get_taxonomy_id ( $taxonomy_name, $taxonomy )
    {
        if( ! empty ( $taxonomy_name ) ) {
            $id = term_exists($taxonomy_name, $taxonomy);
            if ( ! $id ) {
                $id = wp_insert_term(
                    $taxonomy_name,
                    $taxonomy
                );
            }
            $result = $id['term_id'];
        } else {
            $result = '';
        }


        return $result;
    }

    private function sc_add_draft_content ( $type, $nom, $descripcio, $slug, $allTerms, $metadata ) {

        $return = array();
        if( isset( $metadata['post_id'] ) ){
            $parent_id = $metadata['post_id'];
            unset($metadata['post_id']);
            $post_status = 'publish';
        } else {
            $post_status = 'publish';
        }

        //Generate array data
        $post_data = array (
            'post_type'         =>  $type,
            'post_status'		=>	$post_status,
            'comment_status'	=>	'open',
            'ping_status'		=>	'closed',
            'post_author'		=>	get_current_user_id(),
            'post_name'		    =>	$slug,
            'post_title'		=>	$nom,
            'post_content'      =>  $descripcio,
            'post_date'         => date('Y-m-d H:i:s')
        );

        $post_id = wp_insert_post( $post_data );
        if( $post_id ) {
            foreach( $allTerms as $taxonomy => $terms ) {
                wp_set_post_terms( $post_id, $terms, $taxonomy );
            }

            $this->sc_update_metadata( $post_id, $metadata );

            if ( $type == 'aparell' ) {
                $featured_image_attach_id = $this->sc_upload_file( 'file', $post_id );
                $return = $this->sc_set_featured_image( $post_id, $featured_image_attach_id );
            } elseif ( $type == 'baixada' ) {
                $return = $this->sc_set_baixada_post_relationship( $post_id, $parent_id );
            } else {
                $return['status'] = 1;
            }

        } else {
            $return['status'] = 0;
            $return['text'] = "S'ha produït un error en enviar les dades. Proveu de nou.";
        }

        if( $return['status'] == 1 ) {
            $return['post_id'] = $post_id;
            $return['text'] = 'Gràcies per enviar aquesta informació. La publicarem el més aviat possible.';
        }

        return $return;
    }

    function sc_set_baixada_post_relationship( $baixada_id, $program_id ) {
        update_post_meta( $baixada_id, '_wpcf_belongs_programa_id', $program_id );
    }

    /**
     * This function updates an array of given post metadata
     *
     * @param int $post_id
     * @param array $metadata
     * @return boolean
     */
    function sc_update_metadata( $post_id, $metadata ) {
        $result = false;
        if( $post_id ) {
            global $wpcf;

            foreach ($metadata as $meta_key => $meta_value) {
                $wpcf->field->set( $post_id, $meta_key );
                $wpcf->field->save( $meta_value );
            }
            $result = true;
        }
        return $result;
    }

    function sc_upload_file( $value, $post_id ) {

        if ( $value ) {
            if ( !function_exists('media_handle_upload') ) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                require_once(ABSPATH . "wp-admin" . '/includes/media.php');
            }

            $url = $value;
            $tmp = download_url( $url );
            if( is_wp_error( $tmp ) ){
                var_dump('Error 1');
                var_dump($tmp);
            }
            $desc = "";
            $file_array = array();

            // Set variables for storage
            // fix file filename for query strings
            preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png|svg)/i', $url, $matches);
            $file_array['name'] = basename($matches[0]);
            $file_array['tmp_name'] = $tmp;

            // If error storing temporarily, unlink
            if ( is_wp_error( $tmp ) ) {
                @unlink($file_array['tmp_name']);
                $file_array['tmp_name'] = '';
                var_dump('Error 2');
                var_dump($tmp);
            }

            // do the validation and storage stuff
            $id = media_handle_sideload( $file_array, $post_id, $desc );

            // If error storing permanently, unlink
            if ( is_wp_error($id) ) {
                @unlink($file_array['tmp_name']);
                var_dump('Error 3');
                var_dump($id);
            }

            return $id;
        }
    }

    function generate_os_name( $name )
    {
        switch ($name ) {
            case 'Linux':
            case 'Linux32':
            case 'Linux64':
                $value = 'Linux';
                break;
            case 'Windows':
            case 'Windows32':
            case 'Windows64':
                $value = 'Windows';
                break;
            default:
                $value = $name;
                break;
        }

        return $value;
    }
}