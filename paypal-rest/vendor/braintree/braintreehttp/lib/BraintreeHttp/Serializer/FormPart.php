<?php

namespace BraintreeHttp\Serializer;

class FormPart {

	private $value;
	private $headers;

	public function __construct( $value, $headers ) {
		$this->value   = $value;
		$this->headers = array_merge( array(), $headers );
	}

	public function getValue() {
		return $this->value;
	}

	public function getHeaders() {
		return $this->headers;
	}
}
