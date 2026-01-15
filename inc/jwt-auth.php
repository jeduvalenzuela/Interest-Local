<?php
if (!defined('ABSPATH')) exit;

class GeoInterest_JWT {
    private static $secret_key = null;
    
    private static function get_secret_key() {
        if (self::$secret_key === null) {
            self::$secret_key = defined('JWT_AUTH_SECRET_KEY') 
                ? JWT_AUTH_SECRET_KEY 
                : wp_salt('auth');
        }
        return self::$secret_key;
    }
    
    public static function generate_token($user_id) {
        $issued_at = time();
        $expiration = $issued_at + (7 * DAY_IN_SECONDS); // 7 días
        
        $payload = [
            'iss' => get_site_url(),
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user_id
        ];
        
        $token = self::encode($payload);
        
        // Guardar hash del token en DB
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'user_tokens',
            [
                'user_id' => $user_id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', $expiration)
            ]
        );
        
        return $token;
    }
    
    public static function validate_token($token) {
        try {
            $payload = self::decode($token);
            
            if (!isset($payload['user_id']) || !isset($payload['exp'])) {
                return false;
            }
            
            if ($payload['exp'] < time()) {
                return false;
            }
            
            // NO verificar en DB, solo validar la firma
            return $payload['user_id'];
        } catch (Exception $e) {
            error_log('JWT Validation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    private static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64_header = self::base64url_encode($header);
        $base64_payload = self::base64url_encode($payload);
        
        $signature = hash_hmac(
            'sha256',
            $base64_header . '.' . $base64_payload,
            self::get_secret_key(),
            true
        );
        $base64_signature = self::base64url_encode($signature);
        
        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }
    
    private static function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($base64_header, $base64_payload, $base64_signature) = $parts;
        
        $signature = self::base64url_decode($base64_signature);
        $expected_signature = hash_hmac(
            'sha256',
            $base64_header . '.' . $base64_payload,
            self::get_secret_key(),
            true
        );
        
        if (!hash_equals($signature, $expected_signature)) {
            throw new Exception('Invalid signature');
        }
        
        $payload = json_decode(self::base64url_decode($base64_payload), true);
        
        return $payload;
    }
    
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    public static function get_current_user_id() {
        // Intentar obtener el Authorization header de varias formas
        $auth_header = '';
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
            }
        }
        
        if (empty($auth_header)) {
            error_log('GeoInterest: No Authorization header found');
            return false;
        }
        
        // Extraer el token del header "Bearer {token}"
        if (preg_match('/Bearer\s+(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        } else {
            error_log('GeoInterest: Invalid Authorization header format');
            return false;
        }
        
        $user_id = self::validate_token($token);
        
        if (!$user_id) {
            error_log('GeoInterest: Token validation failed for token: ' . substr($token, 0, 20) . '...');
        }
        
        return $user_id;
    }
}

// Middleware para proteger endpoints
function geointerest_auth_middleware($request) {
    $user_id = GeoInterest_JWT::get_current_user_id();
    
    if (!$user_id) {
        return new WP_Error(
            'unauthorized',
            'Token inválido o expirado',
            ['status' => 401]
        );
    }
    
    // Agregar user_id al request
    $request->set_param('authenticated_user_id', $user_id);
    
    return true;
}