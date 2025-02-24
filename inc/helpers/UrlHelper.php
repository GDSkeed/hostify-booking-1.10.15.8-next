<?php

class UrlHelper
{
    public static function buildLogoUrl($logo)
    {
        return '';
        // return //params['imageUrl'] . $logo . '?t=' . time();
    }

    public static function buildCityImageUrl($city)
    {
        return '';
        // return $city->id && $city->image ? //params['imageUrl'] . $city->image . '?t=' . time() : '';
    }

    public static function listing($prm = [])
    {
        $prm = is_array($prm) ? $prm : ['id' => $prm];

        // remove default values

        if (isset($prm['long_term_mode'])) if ($prm['long_term_mode'] < 1) unset($prm['long_term_mode']);
        // todo
        // if (isset($prm['long_term_mode'])) {
        //     if ($prm['long_term_mode'] < 1 || $prm['long_term_mode'] == HFY_LONG_TERM_DEFAULT) {
        //         unset($prm['long_term_mode']);
        //     }
        // }

        if (isset($prm['guests'])) if ($prm['guests'] < 2) unset($prm['guests']);
        if (isset($prm['adults'])) if ($prm['adults'] < 2) unset($prm['adults']);
        if (isset($prm['children'])) if ($prm['children'] < 2) unset($prm['children']);
        if (isset($prm['infants'])) if ($prm['infants'] < 2) unset($prm['infants']);
        if (isset($prm['pets'])) if ($prm['pets'] < 2) unset($prm['pets']);

        $id = $prm['id'] ?? '';
        $query = http_build_query($prm);

        $url = HFY_PAGE_LISTING_URL . (strpos(HFY_PAGE_LISTING_URL, '?') ? '&' : '?') . $query;
        return HFY_SEO_LISTINGS ? self::get_listing_human_url($id, $query) : $url;
    }

    public static function listings($prm = [], $samepage = false)
    {
        if ($samepage) {
            return '?' . http_build_query($prm);
        }
        return HFY_PAGE_LISTINGS_URL . (strpos(HFY_PAGE_LISTINGS_URL, '?') ? '&' : '?') . http_build_query($prm);
    }

    public static function get_listing_human_url($id = null, $qs = null)
    {
        if (is_null($qs)) {
            $qs = explode('?', $_SERVER['REQUEST_URI'])[1] ?? '';
            if ($id) $qs .= '&id=' . $id;
        }
        $slug = self::get_listing_human_slug($id);
        if ($slug) {
            // return rtrim(get_bloginfo('url'), '/') . '/' . $slug . '/' . (empty($qs) ? '' : '?' . $qs);
            $s = '/' . $slug . '/' . (empty($qs) ? '' : '?' . $qs);
            return preg_replace('/\/[^\/]+\/?$/', $s, HFY_PAGE_LISTING_URL);
        }
        return HFY_PAGE_LISTING_URL . '?' . $qs;
    }

    public static function get_listing_human_url_single()
    {
        $qs = trim(explode('?', $_SERVER['REQUEST_URI'])[1] ?? '');
        if (empty($qs)) {
            $rq = preg_replace('/^\/.{2}\//', '', $_SERVER['REQUEST_URI'], 1);
            return rtrim(get_bloginfo('url'), '/') . '/' . ltrim($rq, '/');
        } else {
            $id = intval($_GET['id'] ?? null);
            if ($id) {
                $slug = self::get_listing_human_slug($id);
                if ($slug) {
                    // return rtrim(get_bloginfo('url'), '/') . '/' . $slug . '/';
                    return preg_replace('/\/[^\/]+\/?$/', '/' . $slug . '/', HFY_PAGE_LISTING_URL);
                }
            }
        }
        return HFY_PAGE_LISTING_URL . '?id=' . $id;
    }

    public static function get_listing_human_slug($id = null)
    {
        // todo
        // $lang = 'en';
        // if (class_exists('TRP_Translation_Render')) {
        //     global $TRP_LANGUAGE;
        //     $lang = substr($TRP_LANGUAGE ?? 'en', 0, 2);
        // }

        $id = $id ? $id : intval($_GET['id'] ?? 0);
        if ($id > 0) {
            global $wpdb;
            $tname = $wpdb->prefix . 'hfy_listing_permalink';
            $sql = $wpdb->prepare("select permalink from {$tname} where listing_id = %d limit 1", [ $id ]);
            $res = $wpdb->get_row($sql);
            if ($res) {
                return $res->permalink;
            }
        } else {

            // $rules = get_option( 'rewrite_rules' );
            // var_dump($rules);die;
            // foreach

            $parsed = self::parse_listings_url();
            global $wpdb;
            $tname = $wpdb->prefix . 'hfy_listing_permalink';
            $sql = $wpdb->prepare("select permalink from {$tname} where permalink = %s limit 1", [ $parsed['city'] ]);
            $res = $wpdb->get_row($sql);
            if ($res) {
                return $res->permalink;
            }
        }
        return false;
    }

    public static function parse_listings_url()
    {
        // $_SERVER['PATH_INFO'] -- miss on live server
        // $_SERVER['QUERY_STRING']

        $i = $_SERVER['PATH_INFO'] ?? null;
        $qs = $_SERVER['QUERY_STRING'] ?? null;
        if (!$i) {
            $ii = explode('?', $_SERVER['REQUEST_URI']);
            $i = $ii[0] ?? '';
            $qs = $ii[1] ?? '';
        }

        $path = explode('/', $i);

        # search: /lang/city/district/type/amenity/?prms=...&...
        #   ex: /en/barcelona/gracia/with-terrace/?from=&till=&
        #   ex: /en/barcelona/gracia/with-terrace/?from=&till=&
        # single listing:
        #   ex: /en/sky-blue-attic-penthouse-gothic-quarter/?...

        $lang = trim($path[1] ?? '');

        if (strlen($lang) > 2) {
            return [
                'full' => $i . '?' . $qs,
                'path' => $i,
                'qs' => $qs,
                'lang' => '',
                'city' => $path[1] ?? '',
                'district' => $path[2] ?? '',
                'type' => $path[3] ?? '',
                'amenity' => $path[4] ?? '',
            ];
        }

        return [
            'full' => $i . '?' . $qs,
            'path' => $i,
            'qs' => $qs,
            'lang' => $lang,
            'city' => $path[2] ?? '',
            'district' => $path[3] ?? '',
            'type' => $path[4] ?? '',
            'amenity' => $path[5] ?? '',
        ];
    }

}
