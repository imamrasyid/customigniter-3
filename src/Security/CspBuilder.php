<?php
declare(strict_types=1);

namespace Customigniter\Security;

/**
 * Content Security Policy (CSP) Header Builder
 *
 * Generates CSP headers using PHP 8.1+ enums for policy directives.
 *
 * @package	Customigniter3
 */
class CspBuilder
{
	/**
	 * CSP Directives
	 *
	 * @var array<string, array<string>>
	 */
	private array $directives = [];

	/**
	 * Whether to use report-only mode
	 *
	 * @var bool
	 */
	private bool $reportOnly = false;

	/**
	 * Report URI for violation reports
	 *
	 * @var ?string
	 */
	private ?string $reportUri = null;

	/**
	 * Add a source to a directive
	 *
	 * @param	string	$directive
	 * @param	string	$source
	 * @return	static
	 */
	public function addSource(string $directive, string $source): static
	{
		self::assertValidDirective($directive);
		self::assertValidSource($source);

		$this->directives[$directive][] = $source;
		return $this;
	}

	/**
	 * Set the default-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function defaultSrc(string ...$sources): static
	{
		return $this->setDirective('default-src', ...$sources);
	}

	/**
	 * Set the script-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function scriptSrc(string ...$sources): static
	{
		return $this->setDirective('script-src', ...$sources);
	}

	/**
	 * Set the style-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function styleSrc(string ...$sources): static
	{
		return $this->setDirective('style-src', ...$sources);
	}

	/**
	 * Set the img-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function imgSrc(string ...$sources): static
	{
		return $this->setDirective('img-src', ...$sources);
	}

	/**
	 * Set the font-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function fontSrc(string ...$sources): static
	{
		return $this->setDirective('font-src', ...$sources);
	}

	/**
	 * Set the connect-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function connectSrc(string ...$sources): static
	{
		return $this->setDirective('connect-src', ...$sources);
	}

	/**
	 * Set the frame-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function frameSrc(string ...$sources): static
	{
		return $this->setDirective('frame-src', ...$sources);
	}

	/**
	 * Set the object-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function objectSrc(string ...$sources): static
	{
		return $this->setDirective('object-src', ...$sources);
	}

	/**
	 * Set the media-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function mediaSrc(string ...$sources): static
	{
		return $this->setDirective('media-src', ...$sources);
	}

	/**
	 * Set the worker-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function workerSrc(string ...$sources): static
	{
		return $this->setDirective('worker-src', ...$sources);
	}

	/**
	 * Set the manifest-src directive
	 *
	 * @param	string	...$sources
	 * @return	static
	 */
	public function manifestSrc(string ...$sources): static
	{
		return $this->setDirective('manifest-src', ...$sources);
	}

	/**
	 * Enable nonce for script-src
	 *
	 * @param	string	$nonce
	 * @return	static
	 */
	public function scriptNonce(string $nonce): static
	{
		self::assertValidToken($nonce, 'nonce');

		$this->directives['script-src'][] = "'nonce-{$nonce}'";
		return $this;
	}

	/**
	 * Enable nonce for style-src
	 *
	 * @param	string	$nonce
	 * @return	static
	 */
	public function styleNonce(string $nonce): static
	{
		self::assertValidToken($nonce, 'nonce');

		$this->directives['style-src'][] = "'nonce-{$nonce}'";
		return $this;
	}

	/**
	 * Enable hash for script-src
	 *
	 * @param	string	$hash
	 * @return	static
	 */
	public function scriptHash(string $hash): static
	{
		self::assertValidToken($hash, 'hash');

		$this->directives['script-src'][] = "'{$hash}'";
		return $this;
	}

	/**
	 * Enable hash for style-src
	 *
	 * @param	string	$hash
	 * @return	static
	 */
	public function styleHash(string $hash): static
	{
		self::assertValidToken($hash, 'hash');

		$this->directives['style-src'][] = "'{$hash}'";
		return $this;
	}

	/**
	 * Set report-only mode
	 *
	 * @param	bool	$reportOnly
	 * @return	static
	 */
	public function setReportOnly(bool $reportOnly = true): static
	{
		$this->reportOnly = $reportOnly;
		return $this;
	}

	/**
	 * Set the report URI
	 *
	 * @param	string	$uri
	 * @return	static
	 */
	public function setReportUri(string $uri): static
	{
		if (trim($uri) === '' || preg_match('/[\x00-\x1F\x7F;]/', $uri) === 1) {
			throw new \InvalidArgumentException('report-uri must be a non-empty URL without control characters or ";".');
		}

		$this->reportUri = $uri;
		return $this;
	}

	/**
	 * Build the CSP header string
	 *
	 * @return	string
	 */
	public function build(): string
	{
		$parts = [];

		foreach ($this->directives as $directive => $sources) {
			$uniqueSources = array_values(array_unique($sources));
			$parts[] = $directive . ' ' . implode(' ', $uniqueSources);
		}

		if ($this->reportUri !== null) {
			$parts[] = "report-uri {$this->reportUri}";
		}

		return implode('; ', $parts);
	}

	/**
	 * Send the CSP header
	 *
	 * @return	void
	 */
	public function sendHeader(): void
	{
		$value = $this->build();

		if ($value === '') {
			return;
		}

		$headerName = $this->reportOnly
			? 'Content-Security-Policy-Report-Only'
			: 'Content-Security-Policy';

		header("{$headerName}: {$value}");
	}

	/**
	 * Set a generic directive
	 *
	 * @param	string	$directive
	 * @param	string	...$sources
	 * @return	static
	 */
	private function setDirective(string $directive, string ...$sources): static
	{
		self::assertValidDirective($directive);

		foreach ($sources as $source) {
			self::assertValidSource($source);
		}

		$this->directives[$directive] = array_merge(
			$this->directives[$directive] ?? [],
			$sources
		);
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate a directive name (e.g. "script-src")
	 *
	 * @throws	\InvalidArgumentException
	 */
	private static function assertValidDirective(string $directive): void
	{
		if (preg_match('/^[a-z][a-z0-9-]*$/', $directive) !== 1) {
			throw new \InvalidArgumentException("Invalid CSP directive name: '{$directive}'");
		}
	}

	/**
	 * Validate a source expression
	 *
	 * Sources must not contain whitespace, ";" or control characters —
	 * those would allow injecting additional directives or sources into
	 * the header value.
	 *
	 * @throws	\InvalidArgumentException
	 */
	private static function assertValidSource(string $source): void
	{
		if ($source === '' || preg_match('/[\x00-\x1F\x7F\s;]/', $source) === 1) {
			throw new \InvalidArgumentException(
				"Invalid CSP source '{$source}': must be non-empty and contain no whitespace, ';' or control characters."
			);
		}
	}

	/**
	 * Validate a nonce or hash token
	 *
	 * @throws	\InvalidArgumentException
	 */
	private static function assertValidToken(string $token, string $label): void
	{
		if (preg_match('/^[A-Za-z0-9+\/=_-]+$/', $token) !== 1) {
			throw new \InvalidArgumentException("Invalid CSP {$label}: '{$token}'");
		}
	}
}
