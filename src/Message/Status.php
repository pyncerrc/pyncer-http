<?php
namespace Pyncer\Http\Message;

enum Status: int
{
    // Informational 1xx
    case INFORMATIONAL_100_CONTINUE = 100;
    case INFORMATIONAL_101_SWITCHING_PROTOCOLS = 101;
    case INFORMATIONAL_102_PROCESSING = 102;

    // Success 2xx
    case SUCCESS_200_OK = 200;
    case SUCCESS_201_CREATED = 201;
    case SUCCESS_202_ACCEPTED = 202;
    case SUCCESS_203_NON_AUTHORITATIVE_INFORMATION = 203;
    case SUCCESS_204_NO_CONTENT = 204;
    case SUCCESS_205_RESET_CONTENT = 205;
    case SUCCESS_206_PARTIAL_CONTENT = 206;
    case SUCCESS_207_MULTI_STATUS = 207;
    case SUCCESS_208_ALREADY_REPORTED = 208;
    case SUCCESS_226_IM_USED = 226;

    // Redirection 3xx
    case REDIRECTION_300_MULTIPLE_CHOICE = 300;
    case REDIRECTION_301_MOVED_PERMANENTLY = 301;
    case REDIRECTION_302_FOUND = 302;
    case REDIRECTION_303_SEE_OTHER = 303;
    case REDIRECTION_304_NOT_MODIFIED = 304;
    case REDIRECTION_305_USE_PROXY = 305;
    case REDIRECTION_306_SWITCH_PROXY = 306;
    case REDIRECTION_307_TEMPORARY_REDIRECT = 307;
    case REDIRECTION_308_PERMANENT_REDIRECT = 308;

    // Client Error 4xx
    case CLIENT_ERROR_400_BAD_REQUEST = 400;
    case CLIENT_ERROR_401_UNAUTHORIZED = 401;
    case CLIENT_ERROR_402_PAYMENT_REQUIRED = 402;
    case CLIENT_ERROR_403_FORBIDDEN = 403;
    case CLIENT_ERROR_404_NOT_FOUND = 404;
    case CLIENT_ERROR_405_METHOD_NOT_ALLOWED = 405;
    case CLIENT_ERROR_406_NOT_ACCEPTABLE = 406;
    case CLIENT_ERROR_407_PROXY_AUTHENTICATION_REQUIRED = 407;
    case CLIENT_ERROR_408_REQUEST_TIMEOUT = 408;
    case CLIENT_ERROR_409_CONFLICT = 409;
    case CLIENT_ERROR_410_GONE = 410;
    case CLIENT_ERROR_411_LENGTH_REQUIRED = 411;
    case CLIENT_ERROR_412_PRECONDITION_FAILED = 412;
    case CLIENT_ERROR_413_REQUEST_ENTITY_TOO_LARGE = 413;
    case CLIENT_ERROR_414_REQUEST_URI_TOO_LONG = 414;
    case CLIENT_ERROR_415_UNSUxpED_MEDIA_TYPE = 415;
    case CLIENT_ERROR_416_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    case CLIENT_ERROR_417_EXPECTATION_FAILED = 417;
    case CLIENT_ERROR_418_IM_A_TEAPOT = 418;
    case CLIENT_ERROR_421_MISDIRECTED_REQUEST = 421;
    case CLIENT_ERROR_422_UNPROCESSABLE_ENTITY = 422;
    case CLIENT_ERROR_423_LOCKED = 423;
    case CLIENT_ERROR_424_FAILED_DEPENDENCY = 424;
    case CLIENT_ERROR_426_UPGRADE_REQUIRED = 426;
    case CLIENT_ERROR_428_PRECONDITION_REQUIRED = 428;
    case CLIENT_ERROR_429_TOO_MANY_REQUESTS = 429;
    case CLIENT_ERROR_431_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    case CLIENT_ERROR_451_UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // Server Error 5xx
    case SERVER_ERROR_500_INTERNAL_SERVER_ERROR = 500;
    case SERVER_ERROR_501_NOT_IMPLEMENTED = 501;
    case SERVER_ERROR_502_BAD_GATEWAY = 502;
    case SERVER_ERROR_503_SERVICE_UNAVAILABLE = 503;
    case SERVER_ERROR_504_GATEWAY_TIMEOUT = 504;
    case SERVER_ERROR_505_HTTP_VERSION_NOT_SUxpED = 505;
    case SERVER_ERROR_506_VARIANT_ALSO_NEGOTIATES = 506;
    case SERVER_ERROR_507_INSUFFIECIENT_STORAGE = 507;
    case SERVER_ERROR_508_LOOP_DETECTED = 508;
    case SERVER_ERROR_510_NOT_EXTENDED = 510;
    case SERVER_ERROR_511_NETWORK_AUTHENTICATION_REQUIRED = 511;

    const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsuxped Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request ',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not suxped',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    public function getStatusCode(): int
    {
        return $this->value;
    }
    public function getReasonPhrase(): string
    {
        return self::REASON_PHRASES[$this->value];
    }

    public function isInformational(): bool
    {
        return ($this->value >= 100 && $this->value < 200);
    }
    public function isSuccess(): bool
    {
        return ($this->value >= 200 && $this->value < 300);
    }
    public function isRedirection(): bool
    {
        return ($this->value >= 300 && $this->value < 400);
    }
    public function isClientError(): bool
    {
        return ($this->value >= 400 && $this->value < 500);
    }
    public function isServerError(): bool
    {
        return ($this->value >= 500 && $this->value < 600);
    }
}
