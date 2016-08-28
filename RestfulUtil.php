<?php

    namespace Dansnet\Webservice;
    
    class RestfulUtil {
        
        public static function ArrayExtract( array &$array, $keys ) {
            if( !is_array($keys) ) {
                $keys = [$keys];
            }
            $intersection = [];
            foreach( array_keys($array) as $key ) {
                if( in_array($key, $keys) ) {
                    $intersection[$key] = $array[$key];
                    unset($array[$key]);
                }
            }
            return $intersection;
        }

        public static function IsAssoc( array $array ) {
            return array_keys($array) !== range(0, count($array) - 1);
        }
        
    }