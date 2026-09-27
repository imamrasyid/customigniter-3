<?php
declare(strict_types=1);

namespace Customigniter\Core;

/**
 * HTTP Status Codes as PHP 8.1 Enum
 *
 * @package	Customigniter3
 */
enum HttpStatus: int
{
	// 2xx Success
	case OK                    = 200;
	case Created               = 201;
	case Accepted              = 202;
	case NonAuthoritativeInfo  = 203;
	case NoContent             = 204;
	case ResetContent          = 205;
	case PartialContent        = 206;

	// 3xx Redirection
	case MultipleChoices       = 300;
	case MovedPermanently      = 301;
	case Found                 = 302;
	case SeeOther              = 303;
	case NotModified           = 304;
	case TemporaryRedirect     = 307;
	case PermanentRedirect     = 308;

	// 4xx Client Errors
	case BadRequest            = 400;
	case Unauthorized          = 401;
	case PaymentRequired       = 402;
	case Forbidden             = 403;
	case NotFound              = 404;
	case MethodNotAllowed      = 405;
	case NotAcceptable         = 406;
	case RequestTimeout        = 408;
	case Conflict              = 409;
	case Gone                  = 410;
	case UnprocessableEntity   = 422;
	case TooManyRequests       = 429;

	// 5xx Server Errors
	case InternalServerError   = 500;
	case NotImplemented        = 501;
	case BadGateway            = 502;
	case ServiceUnavailable    = 503;
	case GatewayTimeout        = 504;

	/**
	 * Get the standard HTTP status phrase
	 *
	 * @return	string
	 */
	public function phrase(): string
	{
		$phrases = [
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			422 => 'Unprocessable Entity',
			429 => 'Too Many Requests',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
		];

		return $phrases[$this->value];
	}

	/**
	 * Check if the status code represents a successful response (2xx)
	 */
	public function isSuccess(): bool
	{
		return $this->value < 300;
	}

	/**
	 * Check if the status code represents a redirection (3xx)
	 */
	public function isRedirect(): bool
	{
		return $this->value >= 300 && $this->value < 400;
	}

	/**
	 * Check if the status code represents a client error (4xx)
	 */
	public function isClientError(): bool
	{
		return $this->value >= 400 && $this->value < 500;
	}

	/**
	 * Check if the status code represents a server error (5xx)
	 */
	public function isServerError(): bool
	{
		return $this->value >= 500;
	}

	/**
	 * Check if the status code represents an error (4xx or 5xx)
	 */
	public function isError(): bool
	{
		return $this->value >= 400;
	}

	/**
	 * Create from integer code, returns null if invalid
	 */
	public static function fromCode(int $code): ?static
	{
		return self::tryFrom($code);
	}
}
