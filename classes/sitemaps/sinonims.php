<?php

namespace Softcatala\Sitemaps;

class Sinonims {
    private $rest_client;

	public function __construct( $client = null ) {
		if ( null != $client ) {
			$this->rest_client = $client;
		} else {
			$this->rest_client = new \SC_RestClient();
		}
	}

    public function sitemap_index() {
        $sitemap_custom_items = '';

        $domain = home_url();
        $time = date('c', time());

        foreach (range('A', 'Z') as $lletra) {

            $sitemap_custom_items .= "
                <sitemap>
                <loc>$domain/sitemaps/sinonims-$lletra.xml</loc>
                <lastmod>$time</lastmod>
                </sitemap>";
        }

        return $sitemap_custom_items;
    }

    public function query_vars() {
        return ['sc_sinonims_lletra'];
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^sitemaps/sinonims-([A-Z]).xml$', 'index.php?sc_sitemaps=sinonims&sc_sinonims_lletra=$matches[1]', 'top');
    }

    public function maybe_render() {
        if ( get_query_var('sc_sitemaps') && get_query_var('sc_sitemaps') == 'sinonims' && get_query_var( 'sc_sinonims_lletra' ) ) {


            $lletra = strtolower( get_query_var( 'sc_sinonims_lletra' ) );

            $url_api = get_option( 'api_diccionari_sinonims' );
            $url     = $url_api . 'index/' . $lletra;

            $result = $this->rest_client->get( $url );

            if ( $result['error'] ) {
                return $this->return500();exit;
            }

            if ( 200 == $result['code'] && isset($result['result'])) {
                $api_result   = json_decode( $result['result'] );

                $domain = home_url();
                $time = date('c', time());

                $paraules = $api_result->words;
                echo '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="//www.softcatala.org/main-sitemap.xsl"?>';
                echo "\n";
                echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                echo "\n";

                foreach($paraules as $paraula) {
                    $u = urlencode($paraula);
                    echo "<url><loc>$domain/diccionari-de-sinonims/paraula/$u/</loc><lastmod>2023-03-07T20:03:30+00:00</lastmod></url>\n";
                }

                echo '</urlset>';
            }
        }
    }
}