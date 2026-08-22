<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ReCaptcha;

/**
 * The response returned from the service.
 */
class Response {

	/**
	 * Success or failure.
	 *
	 * @var boolean
	 */
	private $success = false;

	/**
	 * Error code strings.
	 *
	 * @var array
	 */
	private $errorCodes = array();

	/**
	 * The hostname of the site where the reCAPTCHA was solved.
	 *
	 * @var string
	 */
	private $hostname;

	/**
	 * Timestamp of the challenge load.
	 *
	 * @var string
	 */
	private $challengeTs = '';

	/**
	 * APK package name.
	 *
	 * @var string
	 */
	private $apkPackageName = '';

	/**
	 * Score assigned to the request (v3).
	 *
	 * @var float|null
	 */
	private $score = null;

	/**
	 * Action as specified by the page (v3).
	 *
	 * @var string
	 */
	private $action = '';

	/**
	 * Build the response from the expected JSON returned by the service.
	 *
	 * @param string $json
	 * @return \ReCaptcha\Response
	 */
	public static function fromJson( $json ) {
		$responseData = json_decode( $json, true );

		if ( ! $responseData ) {
			return new Response( false, array( 'invalid-json' ) );
		}

		$hostname = isset( $responseData['hostname'] ) ? $responseData['hostname'] : null;
		$challengeTs = isset( $responseData['challenge_ts'] ) ? $responseData['challenge_ts'] : '';
		$apkPackageName = isset( $responseData['apk_package_name'] ) ? $responseData['apk_package_name'] : '';
		$score = ( isset( $responseData['score'] ) && is_numeric( $responseData['score'] ) ) ? (float) $responseData['score'] : null;
		$action = isset( $responseData['action'] ) ? $responseData['action'] : '';

		if ( isset( $responseData['success'] ) && $responseData['success'] == true ) {
			return new Response( true, array(), $hostname, $challengeTs, $apkPackageName, $score, $action );
		}

		if ( isset( $responseData['error-codes'] ) && is_array( $responseData['error-codes'] ) ) {
			return new Response( false, $responseData['error-codes'], $hostname, $challengeTs, $apkPackageName, $score, $action );
		}

		return new Response( false, array(), $hostname, $challengeTs, $apkPackageName, $score, $action );
	}

	/**
	 * Constructor.
	 *
	 * @param boolean    $success
	 * @param array      $errorCodes
	 * @param string     $hostname
	 * @param string     $challengeTs
	 * @param string     $apkPackageName
	 * @param float|null $score
	 * @param string     $action
	 */
	public function __construct( $success, array $errorCodes = array(), $hostname = null, $challengeTs = '', $apkPackageName = '', $score = null, $action = '' ) {
		$this->success    = $success;
		$this->errorCodes = $errorCodes;
		$this->hostname   = $hostname;
		$this->challengeTs = $challengeTs;
		$this->apkPackageName = $apkPackageName;
		$this->score = $score;
		$this->action = $action;
	}

	/**
	 * Is success?
	 *
	 * @return boolean
	 */
	public function isSuccess() {
		return $this->success;
	}

	/**
	 * Get error codes.
	 *
	 * @return array
	 */
	public function getErrorCodes() {
		return $this->errorCodes;
	}

	/**
	 * Get hostname.
	 *
	 * @return string
	 */
	public function getHostname() {
		return $this->hostname;
	}

	/**
	 * Get challenge timestamp.
	 *
	 * @return string
	 */
	public function getChallengeTs() {
		return $this->challengeTs;
	}

	/**
	 * Get APK package name.
	 *
	 * @return string
	 */
	public function getApkPackageName() {
		return $this->apkPackageName;
	}

	/**
	 * Get score.
	 *
	 * @return float|null
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * Get action.
	 *
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Array representation.
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'success' => $this->isSuccess(),
			'hostname' => $this->getHostname(),
			'challenge_ts' => $this->getChallengeTs(),
			'apk_package_name' => $this->getApkPackageName(),
			'score' => $this->getScore(),
			'action' => $this->getAction(),
			'error-codes' => $this->getErrorCodes(),
		);
	}
}
