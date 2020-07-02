<?php
/**
 * First Data
 * used to perform the actual api calls
 * @since 1.0
 * @author Vincent Gabriel
 */

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 - Vincent Gabriel & Jason Gill
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */
namespace Nmc9\FirstDataApi;

use Nmc9\FirstDataApi\Firstdata_Error;
use Nmc9\FirstDataApi\FirstdataHttp_Error;

class FirstData
{
	const LIVE_API_URL = 'https://api.globalgatewaye4.firstdata.com/transaction/';
	const TEST_API_URL = 'https://api.demo.globalgatewaye4.firstdata.com/transaction/';
	/**
	 * @var string - the api gatewayId
	 */
	protected $gatewayId = null;
	/**
	 * @var string - the api password
	 */
	protected $password = null;
	/**
	 * @var string - the api hmac key
	 */
	protected $hmacKey = null;
	/**
	 * @var datetime - the date for X-GGe4-Date header
	 */
	protected $gge4Date = '';
	/**
	 * @var string - the api key id
	 */
	protected $keyId = null;
	/**
	 * @var int - api transaction type
	 */
	protected $transactionType = '00';
	/**
	 *  the error code if one exists
	 * @var integer
	 */
	protected $errorCode = 0;
	/**
	 * the error message if one exists
	 * @var string
	 */
	protected $errorMessage = '';
	/**
	 *  the response message
	 * @var string
	 */
	protected $response = '';
	/**
	 *  the headers returned from the call made
	 * @var array
	 */
	protected $headers = '';
	/**
	 * The response represented as an array
	 * @var array
	 */
	protected $arrayResponse = array();
	/**
	 * All the post fields we will add to the call
	 * @var array
	 */
	protected $postFields = array();
	/**
	 * The api version we are about to call
	 * @var string
	 */
	public static $apiVersion = 'v13';
	/**
	 * The api uri we are about to call
	 * @var string
	 */
	public static $apiUri = '/transaction/v13';
	/**
	 * @var boolean - set whether we are in a test mode or not
	 */
	public static $testMode = false;
	/**
	 * @var string - content type
	 */
	public static $contentType = 'application/json; charset=UTF-8;';
	/**
	 * @var string - method
	 */
	public static $method = 'POST';
	/**
	 * Default options for curl.
     */
	public static $CURL_OPTS = array(
		CURLOPT_VERBOSE        => 0,
		CURLOPT_POST           => 1,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_NOPROGRESS     => 1,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_TIMEOUT        => 60,
		CURLOPT_FRESH_CONNECT  => 1,
		CURLOPT_PORT		   => 443,
		CURLOPT_USERAGENT      => 'curl-php',
		CURLOPT_FOLLOWLOCATION => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_CUSTOMREQUEST  => 'POST',
		CURLOPT_HTTPHEADER	   => array('Content-Type: application/json; charset=UTF-8;','Accept: application/json'),
	);

	/**
	 * Transaction types
	 */
	const TRAN_PURCHASE = '00';
	const TRAN_PREAUTH = '01';
	const TRAN_PREAUTHCOMPLETE = '02';
	const TRAN_FORCEDPOST = '03';
	const TRAN_REFUND = '04';
	const TRAN_PREAUTHONLY = '05';
	const TRAN_PAYPALORDER = '07';
	const TRAN_VOID = '13';
	const TRAN_TAGGEDPREAUTHCOMPLETE = '32';
	const TRAN_TAGGEDVOID = '33';
	const TRAN_TAGGEDREFUND = '34';
	const TRAN_CASHOUT = '83';
	const TRAN_ACTIVATION = '85';
	const TRAN_BALANCEINQUIRY = '86';
	const TRAN_RELOAD = '88';
	const TRAN_DEACTIVATION = '89';

	/**
	 * Constructor
	 * @param string $gatewayId - gatewayId
	 * @param string $password - password
	 * @param string $hmacKey - hmacKey
	 * @param string $keyId - keyId
	 * @param bool $testMode - test mode
	 */
	public function __construct($gatewayId, $password, $hmacKey, $keyId, $testMode=false) {
		$this->gatewayId = $gatewayId;
		$this->password = $password;
		$this->hmacKey = $hmacKey;
		$this->keyId = $keyId;
		$this->setTestMode((bool) $testMode);
	}
	/**
	 * set the api gatewayId we are going to use
	 * @param string $gatewayId - the api gatewayId
	 */
	public function setGatewayId($gatewayId) {
		$this->gatewayId = $gatewayId;
	}
	/**
	 * set the api password we are going to use
	 * @param string $password - the api password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}
	/**
	 * set the api hmacKey we are going to use
	 * @param string $hmacKey - the api hmacKey
	 */
	public function setHmacKey($hmacKey) {
		$this->hmacKey = $hmacKey;
	}
	/**
	 * set the api keyId we are going to use
	 * @param string $keyId - the api keyId
	 */
	public function setKeyId($keyId) {
		$this->keyId = $keyId;
	}
	/**
	 * Return the post data fields as an array
	 * @return array
	 */
	public function getPostData() {
		return $this->postFields;
	}
	/**
	 * Set post fields
	 * @param mixed $key
	 * @param mixed $value
	 * @return object
	 */
	public function setPostData($key, $value=null) {
		if(is_array($key) && !$value) {
			foreach($key as $k => $v) {
				$this->postFields[$k] = $v;
			}
		} else {
			$this->postFields[$key] = $value;
		}

		return $this;
	}
	/**
	 * Set the api version we are going to use
	 * @param string $version the new api version
	 * @return void
	 */
	public function setApiVersion($version) {
		$this->apiVersion = $version;
	}
	/**
	 * Set whether we are in a test mode or not
	 * @param boolean $value
	 * @return void
	 */
	public function setTestMode($value) {
		self::$testMode = (bool) $value;
	}
	/**
	 * Set transaction type
	 * @param int $transactionType
	 * @return object
	 */
	public function setTransactionType($transactionType) {
		$this->transactionType = $transactionType;
		return $this;
	}
	/**
	 * Return transaction type
	 * @return int
	 */
	public function getTransactionType() {
		return $this->transactionType;
	}
	/**
	 * set ecommerce flag (ECI Indicator)
     * 1 – MOTO Indicator – Single Transaction mail/telephone order: designates a transaction where the cardholder is not present at a merchant location and consummates the sale via the phone or through the mail. The transaction is not for recurring services or product and does not include sales that are processed via an installment plan.
     * 2 – MOTO Indicator – Recurring Transaction: designates a transaction that represents an arrangement between a cardholder and the merchant where transactions are going to occur on a periodic basis.
     * 3 – MOTO Indicator – Installment Payment: designates a group of transactions that originated from a single purchase where the merchant agrees to bill the cardholder in installments.
     * 4 – MOTO Indicator – Deferred Transaction: designates a transaction that represents an order with a delayed payment for a specified amount of time.
     * 5 – ECI Indicator – Secure Electronic Commerce Transaction: designates a transaction between a cardholder and a merchant consummated via the Internet where the transaction was successfully authenticated and includes the management of a cardholder certificate. (e.g. 3-D Secure Transactions)
     * 6 – ECI Indicator – Non-Authenticated Electronic Commerce Transaction: designates a transaction consummated via the Internet at a 3-D Secure capable merchant that attempted to authenticate the cardholder using 3-D Secure. (e.g. 3-D Secure includes Verified by Visa and MasterCard SecureCode) Attempts occur with Verified by Visa and MasterCard SecureCode transactions in the event of:
     *     a. A non-participating Issuer
     *     b. A non-participating cardholder of a participating Issuer
     *     c. A participating Issuer, but the authentication server is not available
     * 7 – ECI Indicator – Channel Encrypted Transaction: designates a transaction between a cardholder and a merchant consummated via the Internet where the transaction includes the use of transaction encryption such as SSL, but authentication was not performed. The cardholder payment data was protected with a form of Internet security, such as SSL, but authentication was not performed.
     * 8 – ECI Indicator – Non-Secure Electronic Commerce Transaction: designates a transaction between a cardholder and a merchant consummated via the Internet where the transaction does not include the use of any transaction encryption such as SSL, no authentication performed, no management of a cardholder certificate.
     * R – Retail Indicator – designates a transaction where the cardholder was present at a merchant location.
     *     If an "R" is sent for a transaction with a MOTO Merchant Category Code (MCC) the transaction will downgrade.
     *
	 * @param string $number
	 * @return object
	 */
	public function setEcommerceFlag($number) {
		$this->setPostData('ecommerce_flag', $number);
		return $this;
	}
	/**
	 * set credit card number
	 * @param string $number
	 * @return object
	 */
	public function setCreditCardNumber($number) {
		$this->setPostData('cc_number', $number);
		return $this;
	}
	/**
	 * set credit card type
	 * @param string $type
	 * @return object
	 */
	public function setCreditCardType($type) {
		$this->setPostData('credit_card_type', $type);
		return $this;
	}
	/**
	 * set credit card/check holder name
	 * @param string $name
	 * @return object
	 */
	public function setCardHolderName($name) {
		$this->setPostData('cardholder_name', $name);
		return $this;
	}
	/**
	 * set credit card/check holder name
	 * @param string $name
	 * @return object
	 */
	public function setCustomerName($name) {
		$this->setPostData('cardholder_name', $name);
		return $this;
	}
	/**
	 * set check number
	 * @param string $number
	 * @return object
	 */
	public function setCheckNumber($number) {
		$this->setPostData('check_number', $number);
		return $this;
	}
	/**
	 * set check type
	 * @param string $type
	 * @return object
	 */
	public function setCheckType($type) {
		$this->setPostData('check_type', $type);
		return $this;
	}
	/**
	 * set bank account number
	 * @param string $number
	 * @return object
	 */
	public function setBankAccountNumber($number) {
		$this->setPostData('account_number', $number);
		return $this;
	}
	/**
	 * set bank routing number
	 * @param string $number
	 * @return object
	 */
	public function setBankRoutingNumber($number) {
		$this->setPostData('bank_id', $number);
		return $this;
	}
	/**
	 * set customer id number
	 * @param string $number
	 * @return object
	 */
	public function setCustomerId($number) {
		$this->setPostData('customer_id_number', $number);
		return $this;
	}
	/**
	 * set customer id type
	 * @param string $type
	 * @return object
	 */
	public function setCustomerIdType($type) {
		$this->setPostData('customer_id_type', $type);
		return $this;
	}
	/**
	 * set customer email
	 * @param string $email
	 * @return object
	 */
	public function setCustomerEmail($email) {
		$this->setPostData('client_email', $email);
		return $this;
	}
	/**
	 * set credit card expiration date
	 * @param date $date
	 * @return object
	 */
	public function setCreditCardExpiration($date) {
		$this->setPostData('cc_expiry', $date);
		return $this;
	}
	/**
	 * set amount
	 * @param int $amount
	 * @return object
	 */
	public function setAmount($amount) {
		$this->setPostData('amount', $amount);
		return $this;
	}
	/**
	 * set trans armor token
	 * @param string $token
	 * @return object
	 */
	public function setTransArmorToken($token) {
		$this->setPostData('transarmor_token', $token);
		return $this;
	}
	/**
	 * set auth number
	 * @param string $number
	 * @return object
	 */
	public function setAuthNumber($number) {
		$this->setPostData('authorization_num', $number);
		return $this;
	}
	/**
	 * set credit card address
	 * VerificationStr1 is comprised of the following constituent address values: Street, Zip/Postal Code, City, State/Provence, Country.
	 * They are separted by the Pipe Character "|".
	 * Street Address|Zip/Postal|City|State/Prov|Country
	 *
	 * used for verification
	 * @param string $address
	 * @return object
	 */
	public function setCustomerAddress($address) {
		$this->setPostData('cc_verification_str1', $address);
		return $this;
	}
	/**
	 * set credit card cvv code
	 * This is the 0, 3, or 4-digit code on the back of the credit card sometimes called the CVV2 or CVD value.
	 *
	 * used for verification
	 * @param string $cvv
	 * @return object
	 */
	public function setCreditCardVerification($cvv) {
		$this->setPostData('cc_verification_str2', $cvv);
		$this->setPostData('cvd_presence_ind', '1');
		return $this;
	}
	/**
	 * set credit card cavv code
	 * This is the 0, 3, or 4-digit code on the back of the credit card sometimes called the CVV2 or CVD value.
	 *
	 * used for 3-D Secure/Verified by Visa value returded by Cardinal Commerce
	 * @param string $cavv
	 * @return object
	 */
	public function setCreditCardCAVV($cavv) {
		$this->setPostData('cavv', $cavv);
		return $this;
	}
	/**
	 * set credit card cavv code
	 * This is the 0, 3, or 4-digit code on the back of the credit card sometimes called the CVV2 or CVD value.
	 *
	 * used for 3-D Secure/Verified by Visa value returded by Cardinal Commerce
	 * @param string $xid
	 * @return object
	 */
	public function setCreditCardXID($xid) {
		$this->setPostData('xid', $xid);
		return $this;
	}
	/**
	 * set credit card zip code
	 *
	 * used for verification
	 * @param int $zip
	 * @return object
	 */
	public function setCreditCardZipCode($zip) {
		$this->setPostData('zip_code', $zip);
		return $this;
	}
	/**
	 * set currency code
	 *
	 * @param string $code
	 * @return object
	 */
	public function setCurrency($code) {
		$this->setPostData('currency_code', $code);
		return $this;
	}
	/**
	 * set reference number
	 *
	 * @param int $number
	 * @return object
	 */
	public function setReferenceNumber($number) {
		$this->setPostData('reference_no', $number);
		return $this;
	}
	/**
	 * set customer reference number
	 *
	 * @param int $number
	 * @return object
	 */
	public function setCustomerReference($number) {
		$this->setPostData('customer_ref', $number);
		return $this;
	}
	/**
	 * set soft descriptors
	 *
	 * @param string $dba_name
	 * @param string $street
	 * @param string $city
	 * @param string $region
	 * @param string $postal_code
	 * @param string $country_code
	 * @param string $mid
	 * @param string $mcc
	 * @param string $merchant_contact_info
	 * @return object
	 */
	public function setSoftDescriptors($dba_name='', $street='', $city='', $region='', $postal_code='', $country_code='US', $mid='', $mcc='', $merchant_contact_info='') {
        $_params = array(
			'soft_descriptor' => array(
            	'dba_name' => $dba_name,
	            'street' => $street,
	            'city' => $city,
	            'region' => $region,
				'postal_code' => $postal_code,
				'country_code' => $country_code,
				'mid' => $mid,
				'mcc' => $mcc,
				'merchant_contact_info' => $merchant_contact_info,
			)
        );

		$this->setPostData('soft_descriptor', $_params);
		return $this;
	}
	/**
	 * Perform the API call
	 * @return string
	 */
	public function process() {
		return $this->doRequest();
	}
	/**
   	* Makes an HTTP request. This method can be overriden by subclasses if
   	* developers want to do fancier things or use something other than curl to
   	* make the request.
   	*
   	* @param curl_handler  - optional initialized curl handle
   	* @return String - the response text
   	*/
  	protected function doRequest($curl_handler = null) {
    	if (!$curl_handler) {
      		$curl_handler = curl_init();
    	}

		$this->gge4Date = gmdate("c");

		$content_digest = sha1(json_encode(array_merge(array('gateway_id' => $this->gatewayId, 'password' => $this->password, 'transaction_type' => $this->transactionType), $this->getPostData())));

		$haststr = self::$method . "\n" . self::$contentType . "\n" . $content_digest . "\n" . $this->gge4Date . "\n" . self::$apiUri;

		$authstr = base64_encode(hash_hmac("sha1", $haststr, $this->hmacKey, TRUE));

		$curl_headers = array('Content-Type: application/json; charset=UTF-8;', 'Accept: application/json');
		$curl_headers[] = 'X-GGe4-Content-SHA1: ' . $content_digest;
		$curl_headers[] = 'X-GGe4-Date: ' . $this->gge4Date;
		$curl_headers[] = 'Authorization: GGE4_API ' . $this->keyId . ':' . $authstr;

    	$opts = self::$CURL_OPTS;
    	$opts[CURLOPT_POSTFIELDS] = json_encode(array_merge(array('gateway_id' => $this->gatewayId, 'password' => $this->password, 'transaction_type' => $this->transactionType), $this->getPostData()));
    	$opts[CURLOPT_URL] = self::$testMode ? self::TEST_API_URL . self::$apiVersion : self::LIVE_API_URL . self::$apiVersion;
		$opts[CURLOPT_HTTPHEADER] = $curl_headers;

		// set options
		curl_setopt_array($curl_handler, $opts);

		// execute
		$this->setResponse( curl_exec($curl_handler) );
		$this->setHeaders( curl_getinfo($curl_handler) );

		// fetch errors
		$this->setErrorCode( curl_errno($curl_handler) );
		$this->setErrorMessage( curl_error($curl_handler) );

		// Convert response to array
		$this->convertResponseToArray();

		// We need to make sure we do not have any errors
		if($this->isError()) {
			// check Response Codes
			if(!$this->getArrayResponse()) {
				// We have an error
				$returnedMessage = $this->getResponse();

				// Pull out the error code from the message
				preg_match('/\(([0-9]+)\)/', $returnedMessage, $matches);

				$errorCodes = $this->getResponseCodes();

				if(isset($matches[1])) {
					// If it's not 00, 70, 73, 76, 77, 79 array[1:42] then there was an error
					$this->setErrorCode( isset($errorCodes[$matches[1]]) ? $matches[1] : 42 );
					$this->setErrorMessage( isset($errorCodes[$matches[1]]) ? $errorCodes[$matches[1]] : $errorCodes[42] );

					throw new Firstdata_Error($this->getErrorMessage(), $this->getErrorCode());
				} else {
					$headers = $this->getHeaders();
					$this->setErrorCode($headers['http_code']);
					$this->setErrorMessage($returnedMessage);

					throw new Firstdata_HttpError($this->getErrorMessage(), $this->getErrorCode());
				}
			/*} elseif ($this->isAVSError()) {
				// set AVS filter codes
				$avsCodes = $this->getAVSResponseCodes();

				if(isset($avsCodes[$this->getAVSResponse()])) {
					$this->setErrorMessage($avsCodes[$this->getAVSResponse()]);
					$this->setErrorCode($this->getExactResponseCode());
				}

				throw new FirstData_Error($this->getErrorMessage(), $this->getErrorCode());*/
			/*} elseif ($this->isCVV2Error()) {
				// set CVV2 filter codes
				$cvv2Codes = $this->getCVV2ResponseCodes();

				if(isset($cvv2Codes[$this->getCVV2Response()])) {
					$this->setErrorMessage($cvv2Codes[$this->getCVV2Response()]);
					$this->setErrorCode($this->getExactResponseCode());
				}

				throw new FirstData_Error($this->getErrorMessage(), $this->getErrorCode());*/
			} else {
				$this->setErrorMessage($this->getBankResponseName());
				$this->setErrorCode($this->getBankResponseCode());

				throw new Firstdata_HttpError($this->getErrorMessage(), $this->getErrorCode());
			}
		} else {
			// We have a json string, empty error message
			$this->setErrorMessage('');
			$this->setErrorCode(0);
		}

		// close
		curl_close($curl_handler);

		// Reset
		$this->postFields = array();

		return $this->getResponse();
    }
	/**
	 * Did we encounter an AVS Filter error?
	 * @return boolean
	 */
	public function isAVSError() {
		// AVS Filter response
		// website requires 2, phone requires Z, check returns ''
		if($this->getAVSResponse() && $this->getAVSResponse() != '2' && $this->getAVSResponse() != 'Z') {
			return true;
		}

		// No error
		return false;
	}
	/**
	 * Did we encounter an AVS Filter error?
	 * @return boolean
	 */
	public function isCVV2Error() {
		// CVV2 Filter response
		// check returns ''
		if($this->getCVV2Response() && $this->getCVV2Response() != 'M') {
			return true;
		}

		// No error
		return false;
	}
	/**
	 * Did we encounter an http error?
	 * @return boolean
	 */
	public function isHttpError() {
		$headers = $this->getHeaders();
		// First make sure we got a valid response
		if(!in_array($headers['http_code'], array(200, 201, 202))) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Did we encounter an error?
	 * @return boolean
	 */
	public function isError() {
		$headers = $this->getHeaders();
		$response = $this->getArrayResponse();
		// First make sure we got a valid response
		if ($this->isHttpError()) {
			return true;
		}

		// Make sure the response does not have error in it
		if(!$response || !count($response)) {
			return true;
		}

		// Do we have an error code
		if($this->getErrorCode() > 0) {
			return true;
		}

		// bank response type
		if($this->getBankResponseType() && $this->getBankResponseType() != 'S') {
			return true;
		}

		// exact response type
		if($this->getExactResponseCode() != '00') {
			return true;
		}

		// AVS Filter response
		/*if($this->isAVSError()) {
			return true;
		}*/

		// CVV2 Filter response
		/*if($this->isCVV2Error()) {
			return true;
		}*/

		// No error
		return false;
	}
	/**
	 * Was the last call successful
	 * @return boolean
	 */
	public function isSuccess() {
		return !$this->isError() ? true : false;
	}

	/**
	 * Check if transaction was approved
	 * @return int
	 */
	public function isApproved() {
		return $this->getValueByKey($this->getArrayResponse(), 'transaction_approved');
	}
	/**
	 * Check for transaction transaction error
	 * @return int
	 */
	public function isTransactionError() {
		return $this->getValueByKey($this->getArrayResponse(), 'transaction_error');
	}
	/**
	 * Get AVS filter response
	 * @return string
	 */
	public function getAVSResponse() {
		return $this->getValueByKey($this->getArrayResponse(), 'avs');
	}
	/**
	 * Get CVV2 filter response
	 * @return string
	 */
	public function getCVV2Response() {
		return $this->getValueByKey($this->getArrayResponse(), 'cvv2');
	}
	/**
	 * Get transaction record/receipt
	 * @return string
	 */
	public function getTransactionRecord() {
		return $this->getValueByKey($this->getArrayResponse(), 'ctr');
	}
	/**
	 * Get transaction auth number
	 * @return string
	 */
	public function getAuthNumber() {
		return $this->getValueByKey($this->getArrayResponse(), 'authorization_num');
	}
	/**
	 * Get transaction reference number
	 * @return string
	 */
	public function getReferenceNumber() {
		return $this->getValueByKey($this->getArrayResponse(), 'retrieval_ref_no');
	}
	/**
	 * Get transaction auth number
	 * @return string
	 */
	public function getSequenceNumber() {
		return $this->getValueByKey($this->getArrayResponse(), 'sequence_no');
	}
	/**
	 * Get transaction transarmor token
	 * @return string
	 */
	public function getTransArmorToken() {
		return $this->getValueByKey($this->getArrayResponse(), 'transarmor_token');
	}
	/**
	 * Get transaction bank response code
	 * @return int
	 */
	public function getBankResponseCode() {
		return $this->getValueByKey($this->getArrayResponse(), 'bank_resp_code');
	}
	/**
	 * Get transaction bank response message
	 * @return string
	 */
	public function getBankResponseMessage() {
		return $this->getValueByKey($this->getArrayResponse(), 'bank_message');
	}
	/**
	 * Get transaction Exact response code
	 * @return int
	 */
	public function getExactResponseCode() {
		return $this->getValueByKey($this->getArrayResponse(), 'exact_resp_code');
	}
	/**
	 * Get transaction Exact response message
	 * @return string
	 */
	public function getExactResponseMessage() {
		return $this->getValueByKey($this->getArrayResponse(), 'exact_message');
	}

	/**
	 * Get transaction bank response comment
	 * @return string
	 */
	public function getBankResponseComments() {
		$code = $this->getBankResponseCode();
		$codes = $this->getBankResponseCodes();
		return isset($codes[$code]) ? $codes[$code]['comments'] : null;
	}
	/**
	 * Get transaction bank response type
	 *  S = Successful Response Codes
	 *	R = Reject Response Codes
	 *	D = Decline Response Codes
	 *
	 * @return string
	 */
	public function getBankResponseType() {
		$code = $this->getBankResponseCode();
		$codes = $this->getBankResponseCodes();
		return isset($codes[$code]) ? $codes[$code]['response'] : null;
	}
	/**
	 * Get transaction bank response required action
	 *
	 * @return string
	 */
	public function getBankResponseAction() {
		$code = $this->getBankResponseCode();
		$codes = $this->getBankResponseCodes();
		return isset($codes[$code]) ? $codes[$code]['action'] : null;
	}
	/**
	 * Get transaction bank response name
	 *
	 * @return string
	 */
	public function getBankResponseName() {
		$code = $this->getBankResponseCode();
		$codes = $this->getBankResponseCodes();
		return isset($codes[$code]) ? $codes[$code]['name'] : null;
	}
	/**
	 * Set the array response value
	 * @param array $value
	 * @return void
	 */
	public function setArrayResponse($value) {
		$this->arrayResponse = $value;
	}
	/**
	 * Return the array representation of the last response
	 * @return array
	 */
	public function getArrayResponse() {
		return $this->arrayResponse;
	}
	/**
	 * Return the response represented as string
	 * @return array
	 */
	protected function convertResponseToArray() {
		if($this->getResponse()) {
			$this->setArrayResponse(json_decode($this->getResponse()));
		}

		return $this->getArrayResponse();
	}
	/**
	 * Set the response
	 *
	 * @param mixed the response returned from the call
	 * @return object
	 */
	protected function setResponse( $response='' ) {
		$this->response = $response;
		return $this;
	}
	/**
	 * Get the response data
	 *
	 * @return mixed the response data
	 */
	public function getResponse() {
		return $this->response;
	}
	/**
	 * Set the headers
	 *
	 * @param the $headers returned from the call
	 * @return object
	 */
	protected function setHeaders( $headers='' ) {
		$this->headers = $headers;
		return $this;
	}
	/**
	 * Get the headers
	 *
	 * @return array the headers returned from the call
	 */
	protected function getHeaders() {
		return $this->headers;
	}
	/**
	 * Set the error code number
	 *
	 * @param integer the error code number
	 * @return object
	 */
	public function setErrorCode($code=0) {
		$this->errorCode = $code;
		return $this;
	}
	/**
	 * Get the error code number
	 *
	 * @return integer error code number
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}
	/**
	 * Set the error message
	 *
	 * @param string the error message
	 * @return object
	 */
	public function setErrorMessage($message='') {
		$this->errorMessage = $message;
		return $this;
	}
	/**
	 * Get the error code message
	 *
	 * @return string error code message
	 */
	public function getErrorMessage() {
		return $this->errorMessage;
	}
	/**
	 * Find a key inside a multi dim. array
	 * @param array/object $data
	 * @param string $key
	 * @return mixed
	 */
	protected function getValueByKey($data, $key) {
		if(count($data)) {
			foreach($data as $k=>$each) {
		  		if($k==$key) {
		   			return $each;
		  		}

		  		if(is_array($each)) {
		   			if($return = $this->getValueByKey($each, $key)) {
		    			return $return;
		   			}
		  		}
		 	}
		}

		// Nothing matched
		return null;
	}

	/**
	 * Return api response codes
	 * @return array
	 */
	protected function getResponseCodes() {
		return array(
			// This response code indicates that the transaction was processed normally. Please refer to the bank and approval response information for bank approval Status.
			'00' => 'Transaction Normal',
			// The following response codes indicate invalid data in the transaction. In these cases, the data should be changed before attempting to resend the transaction. These response codes are generated by the remote Plug-In. They will not appear on the First Data Payeezy Gateway website.
			'22' => 'Invalid Credit Card Number',
			'25' => 'Invalid Expiry Date',
			'26' => 'Invalid Amount',
			'27' => 'Invalid Card Holder',
			'28' => 'Invalid Authorization No',
			'31' => 'Invalid Verification String',
			'32' => 'Invalid Transaction Code',
			'57' => 'Invalid Reference No',
			'58' => 'Invalid AVS String, The length of the AVS String has exceeded the max. 40 characters',
			'60' => 'Invalid Customer Reference Number',
			'63' => 'Invalid Duplicate',
			'64' => 'Invalid Refund',
			'68' => 'Restricted Card Number',
			'69' => 'Inavlid Transaction Tag',
			'72' => 'Data within the transaction is incorrect',
			'93' => 'Invalid authorization number entered on a pre-auth completion',
			// The following response codes indicate a problem with the merchant configuration at the financial institution. Please contact First Data for further investigation.
			'11' => 'Invalid Sequence No',
			'12' => 'Message Timed-out at Host',
			'21' => 'BCE Function Error',
			'23' => 'Invalid Response from First Data',
			'30' => 'Invalid Date From Host',
			// The following response codes indicate a problem with the Payeezy Gateway host or an error in the merchant configuration. Please contact First Data for further investigation.
			'10' => 'Invalid Transaction Description',
			'14' => 'Invalid Gateway ID',
			'15' => 'Invalid Transaction Number',
			'16' => 'Connection Inactive',
			'17' => 'Unmatched Transaction',
			'18' => 'Invalid Reversal Response',
			'19' => 'Unable to Send Socket Transaction',
			'20' => 'Unable to Write Transaction to File',
			'24' => 'Unable to Void Transaction',
			'37' => 'Payment Type Not Supported By Merchant',
			'40' => 'Unable to Connect',
			'41' => 'Unable to Send Logon',
			'42' => 'Unable to Send Trans',
			'43' => 'Invalid Logon',
			'52' => 'Terminal not Activated',
			'53' => 'Terminal/Gateway Mismatch',
			'54' => 'Invalid Processing Center',
			'55' => 'No Processors Available',
			'56' => 'Database Unavailable',
			'61' => 'Socket Error',
			'62' => 'Host not Ready',
			// The following response codes indicate the final state of a transaction. In the event of one of these codes being returned, please contact First Data for further investigation.
			'08' => 'CVV2/CID/CVC2 Data not verified',
			'44' => 'Address not Verified',
			'70' => 'Transaction Placed in Queue',
			'73' => 'Transaction Received from Bank',
			'76' => 'Reversal Pending',
			'77' => 'Reversal Complete',
			'79' => 'Reversal Sent to Bank',
			// The following response codes indicate the final state of a transaction due to custom Fraud Filters created by the Merchant.
			'F1' => 'Address check failed - Fraud suspected',
			'F2' => 'Card/Check Number check failed - Fraud suspected',
			'F3' => 'Country Check Failed - Fraud Suspected',
			'F4' => 'Customer Reference Check Failed - Fraud Suspected',
			'F5' => 'Email Address check failed - Fraud suspected',
			'F6' => 'IP Address check failed - Fraud suspected',
		);
	}

	/**
	 * Return api CVV2 response codes
	 * @return array
	 */
	protected function getCVV2ResponseCodes() {
		return array(
			'M' => 'Card is Authentic',
			'N' => 'CVV2 does not match',
			'P' => 'Card expiration not provided or card does not have valid CVD code',
			'S' => 'Merchant indicated that CVV2 is not present on card',
			'U' => 'Card issuer is not certified and/or has not provided visa encryption keys',
			'I' => 'CVV2 code is invalid or empty',
		);
	}

	/**
	 * Return api AVS response codes
	 * @return array
	 */
	protected function getAVSResponseCodes() {
		return array(
			'X' => 'Exact match, 9 digit zip - Street Address, and 9 digit ZIP Code match',
			'Y' => 'Exact match, 5 digit zip - Street Address, and 5 digit ZIP Code match',
			'A' => 'Partial match - Street Address matches, ZIP Code does not',
			'W' => 'Partial match - ZIP Code matches, Street Address does not',
			'Z' => 'Partial match - 5 digit ZIP Code match only',
			'N' => 'No match - No Address or ZIP Code match',
			'U' => 'Unavailable - Address information is unavailable for that account number, or the card issuer does not support',
			'G' => 'Service Not supported, non-US Issuer does not participate',
			'R' => 'Retry - Issuer system unavailable, retry later',
			'E' => 'Not a mail or phone order',
			'S' => 'Service not supported',
			'Q' => 'Bill to address did not pass edit checks/Card Association can\'t verify the authentication of an address',
			'D' => 'International street address and postal code match',
			'B' => 'International street address match, postal code not verified due to incompatable formats',
			'C' => 'International street address and postal code not verified due to incompatable formats',
			'P' => 'International postal code match, street address not verified due to incompatable format',
			'1' => 'Cardholder name matches',
			'2' => 'Cardholder name, billing address, and postal code match',
			'3' => 'Cardholder name and billing postal code match',
			'4' => 'Cardholder name and billing address match',
			'5' => 'Cardholder name incorrect, billing address and postal code match',
			'6' => 'Cardholder name incorrect, billing postal code matches',
			'7' => 'Cardholder name incorrect, billing address matches',
			'8' => 'Cardholder name, billing address, and postal code are all incorrect',
		);
	}

	/**
	 * Return api International AVS response codes
	 * @return array
	 */
	protected function getIntlAVSResponseCodes() {
		return array(
			'G' => 'Global non-AVS participant',
			'B' => 'Address matches only',
			'C' => 'Address and Postal Code do not match',
			'D' => 'Address and Postal Code match',
			'F' => 'Address and Postal Code match (UK only)',
			'I' => 'Address information not verified for international transaction',
			'M' => 'Address and Postal Code match',
			'P' => 'Postal Code matches only',
		);
	}

	/**
	 * API Bank Response Code, type, name, comments and action required
	 * @return array
	 */
	protected function getBankResponseCodes() {
		return array(
			000 => array(
				'response' => 'D',
				'code' => '000',
				'name' => 'No Answer',
				'action' => 'Resend',
				'comments' => 'First Data received no answer from auth network',
			),
			100 => array(
				'response' => 'S',
				'code' => '100',
				'name' => 'Approved',
				'action' => 'N/A',
				'comments' => 'Successfully approved',
			),
			101 => array(
				'response' => 'S',
				'code' => '101',
				'name' => 'Validated',
				'action' => 'N/A',
				'comments' => 'Account Passed edit checks',
			),
			102 => array(
				'response' => 'S',
				'code' => '102',
				'name' => 'Verified',
				'action' => 'N/A',
				'comments' => 'Account Passed external negative file',
			),
			103 => array(
				'response' => 'S',
				'code' => '103',
				'name' => 'Pre-Noted',
				'action' => 'N/A',
				'comments' => 'Passed Pre-Note',
			),
			104 => array(
				'response' => 'S',
				'code' => '104',
				'name' => 'No Reason to Decline',
				'action' => 'N/A',
				'comments' => 'Successfully approved',
			),
			105 => array(
				'response' => 'S',
				'code' => '105',
				'name' => 'Received and Stored',
				'action' => 'N/A',
				'comments' => 'Successfully approved',
			),
			106 => array(
				'response' => 'S',
				'code' => '106',
				'name' => 'Provided Auth',
				'action' => 'N/A',
				'comments' => 'Successfully approved Note: Indicates customized code was used in processing',
			),
			107 => array(
				'response' => 'S',
				'code' => '107',
				'name' => 'Request Received',
				'action' => 'N/A',
				'comments' => 'Successfully approved Note: Indicates customized code was used in processing',
			),
			108 => array(
				'response' => 'S',
				'code' => '108',
				'name' => 'Approved for Activation',
				'action' => 'N/A',
				'comments' => 'Successfully Activated',
			),
			109 => array(
				'response' => 'S',
				'code' => '109',
				'name' => 'Previously&nbsp;Processed Transaction',
				'action' => 'N/A',
				'comments' => 'Transaction was not re-authorized with the Debit Network because it was previously processed',
			),
			110 => array(
				'response' => 'S',
				'code' => '110',
				'name' => 'BIN Alert',
				'action' => 'N/A',
				'comments' => 'Successfully approved Note: Indicates customized code was used in processing',
			),
			111 => array(
				'response' => 'S',
				'code' => '111',
				'name' => 'Approved for Partial',
				'action' => 'N/A',
				'comments' => 'Successfully approved Note: Indicates customized code was used in processing',
			),
			164 => array(
				'response' => 'S',
				'code' => '164',
				'name' => 'Conditional Approval',
				'action' => 'Wait',
				'comments' => 'Conditional Approval - Hold shipping for 24 hours',
			),
			201 => array(
				'response' => 'R',
				'code' => '201',
				'name' => 'Invalid CC Number',
				'action' => 'Fix',
				'comments' => 'Bad check digit, length, or other credit card problem',
			),
			202 => array(
				'response' => 'R',
				'code' => '202',
				'name' => 'Bad Amount Nonnumeric Amount',
				'action' => 'If',
				'comments' => 'Amount sent was zero, unreadable, over ceiling limit, or exceeds maximum allowable amount.',
			),
			203 => array(
				'response' => 'R',
				'code' => '203',
				'name' => 'Zero Amount',
				'action' => 'Fix',
				'comments' => 'Amount sent was zero',
			),
			204 => array(
				'response' => 'R',
				'code' => '204',
				'name' => 'Other Error',
				'action' => 'Fix',
				'comments' => 'Unidentifiable error',
			),
			205 => array(
				'response' => 'R',
				'code' => '205',
				'name' => 'Bad Total Auth Amount',
				'action' => 'Fix',
				'comments' => 'The sum of the authorization amount from extended data information does not equal detail record authorization Amount. Amount sent was zero, unreadable, over ceiling limit, or exceeds Maximum allowable amount.',
			),
			218 => array(
				'response' => 'R',
				'code' => '218',
				'name' => 'Invalid SKU Number',
				'action' => 'Fix',
				'comments' => 'Non‐numeric value was sent',
			),
			219 => array(
				'response' => 'R',
				'code' => '219',
				'name' => 'Invalid Credit Plan',
				'action' => 'Fix',
				'comments' => 'Non‐numeric value was sent',
			),
			220 => array(
				'response' => 'R',
				'code' => '220',
				'name' => 'Invalid Store Number',
				'action' => 'Fix',
				'comments' => 'Non‐numeric value was sent',
			),
			225 => array(
				'response' => 'R',
				'code' => '225',
				'name' => 'Invalid Field Data',
				'action' => 'Fix',
				'comments' => 'Data within transaction is incorrect',
			),
			227 => array(
				'response' => 'R',
				'code' => '227',
				'name' => 'Missing Companion Data',
				'action' => 'Fix',
				'comments' => 'Specific and relevant data within transaction is absent',
			),
			229 => array(
				'response' => 'R',
				'code' => '229',
				'name' => 'Percents do not total 100',
				'action' => 'Fix',
				'comments' => 'FPO monthly payments do not total 100 Note: FPO only',
			),
			230 => array(
				'response' => 'R',
				'code' => '230',
				'name' => 'Payments do not total 100',
				'action' => 'Fix',
				'comments' => 'FPO monthly payments do not total 100 Note: FPO only',
			),
			231 => array(
				'response' => 'R',
				'code' => '231',
				'name' => 'Invalid Division Number',
				'action' => 'Fix',
				'comments' => 'Division number incorrect',
			),
			233 => array(
				'response' => 'R',
				'code' => '233',
				'name' => 'Does not match MOP',
				'action' => 'Fix',
				'comments' => 'Credit card number does not match method of payment type or invalid BIN',
			),
			234 => array(
				'response' => 'R',
				'code' => '234',
				'name' => 'Duplicate Order Number',
				'action' => 'Fix',
				'comments' => 'Unique to authorization recycle transactions. Order number already exists in system Note: Auth Recycle only',
			),
			235 => array(
				'response' => 'R',
				'code' => '235',
				'name' => 'FPO Locked',
				'action' => 'Resend',
				'comments' => 'FPO change not allowed Note: FPO only',
			),
			236 => array(
				'response' => 'R',
				'code' => '236',
				'name' => 'Auth Recycle Host System Down',
				'action' => 'Resend',
				'comments' => 'Authorization recycle host system temporarily unavailable Note: Auth Recycle only',
			),
			237 => array(
				'response' => 'R',
				'code' => '237',
				'name' => 'FPO Not Approved',
				'action' => 'Call',
				'comments' => 'Division does not participate in FPO. Contact your First Data Representative for information on getting set up for FPO Note: FPO only',
			),
			238 => array(
				'response' => 'R',
				'code' => '238',
				'name' => 'Invalid Currency',
				'action' => 'Fix',
				'comments' => 'Currency does not match First Data merchant setup for division',
			),
			239 => array(
				'response' => 'R',
				'code' => '239',
				'name' => 'Invalid MOP for Division',
				'action' => 'Fix',
				'comments' => 'Method of payment is invalid for the division',
			),
			240 => array(
				'response' => 'R',
				'code' => '240',
				'name' => 'Auth Amount for Division',
				'action' => 'Fix',
				'comments' => 'Used by FPO',
			),
			241 => array(
				'response' => 'R',
				'code' => '241',
				'name' => 'Illegal Action',
				'action' => 'Fix',
				'comments' => 'Invalid action attempted',
			),
			243 => array(
				'response' => 'R',
				'code' => '243',
				'name' => 'Invalid Purchase Level 3',
				'action' => 'Fix',
				'comments' => 'Data is inaccurate or missing, or the BIN is ineligible for P‐card',
			),
			244 => array(
				'response' => 'R',
				'code' => '244',
				'name' => 'Invalid Encryption Format',
				'action' => 'Fix',
				'comments' => 'Invalid encryption flag. Data is Inaccurate.',
			),
			245 => array(
				'response' => 'R',
				'code' => '245',
				'name' => 'Missing or Invalid Secure Payment Data',
				'action' => 'Fix',
				'comments' => 'Visa or MasterCard authentication data not in appropriate Base 64 encoding format or data provided on A non‐e‐Commerce transaction.',
			),
			246 => array(
				'response' => 'R',
				'code' => '246',
				'name' => 'Merchant not MasterCard Secure code Enabled',
				'action' => 'Call',
				'comments' => 'Division does not participate in MasterCard Secure Code. Contact your First Data Representative for information on getting setup for MasterCard SecureCode.',
			),
			247 => array(
				'response' => 'R',
				'code' => '247',
				'name' => 'Check conversion Data Error',
				'action' => 'Fix',
				'comments' => 'Proper data elements were not sent',
			),
			248 => array(
				'response' => 'R',
				'code' => '248',
				'name' => 'Blanks not passed in reserved field',
				'action' => 'Fix',
				'comments' => 'Blanks not passed in Reserved Field',
			),
			249 => array(
				'response' => 'R',
				'code' => '249',
				'name' => 'Invalid (MCC)',
				'action' => 'Fix',
				'comments' => 'Invalid Merchant Category (MCC) sent',
			),
			251 => array(
				'response' => 'R',
				'code' => '251',
				'name' => 'Invalid Start Date',
				'action' => 'Fix',
				'comments' => 'Incorrect start date or card may require an issue number, but a start date was submitted.',
			),
			252 => array(
				'response' => 'R',
				'code' => '252',
				'name' => 'Invalid Issue Number',
				'action' => 'Fix',
				'comments' => 'Issue number invalid for this BIN.',
			),
			253 => array(
				'response' => 'R',
				'code' => '253',
				'name' => 'Invalid Tran. Type',
				'action' => 'Fix',
				'comments' => 'If an “R” (Retail Indicator) is sent for a transaction with a MOTO Merchant Category Code (MCC)',
			),
			257 => array(
				'response' => 'R',
				'code' => '257',
				'name' => 'Missing Cust Service Phone',
				'action' => 'Fix',
				'comments' => 'Card was authorized, but AVS did not match. The 100 was overwritten with a 260 per the merchant’s request Note: Conditional deposits only',
			),
			258 => array(
				'response' => 'R',
				'code' => '258',
				'name' => 'Not Authorized to Send Record',
				'action' => 'Call',
				'comments' => 'Division does not participate in Soft Merchant Descriptor. Contact your First Data Representative for information on getting set up for Soft Merchant Descriptor.',
			),
			260 => array(
				'response' => 'D',
				'code' => '260',
				'name' => 'Soft AVS',
				'action' => 'Cust',
				'comments' => 'Authorization network could not reach the bank which issued the card',
			),
			261 => array(
				'response' => 'R',
				'code' => '261',
				'name' => 'Account Not Eligible For Division’s Setup',
				'action' => 'N/A',
				'comments' => 'Account number not eligible for division’s Account Updater program setup',
			),
			262 => array(
				'response' => 'R',
				'code' => '262',
				'name' => 'Authorization Code Response Date Invalid',
				'action' => 'Fix',
				'comments' => 'Authorization code and/or response date are invalid. Note: MOP = MC, MD, VI only',
			),
			263 => array(
				'response' => 'R',
				'code' => '263',
				'name' => 'Partial Authorization Not Allowed or Partial Authorization Request Note Valid',
				'action' => 'Fix',
				'comments' => 'Action code or division does not allow partial authorizations or partial authorization request is not valid.',
			),
			264 => array(
				'response' => 'R',
				'code' => '264',
				'name' => 'Duplicate Deposit Transaction',
				'action' => 'N/A',
				'comments' => 'Transaction is a duplicate of a previously deposited transaction. Transaction will not be processed.',
			),
			265 => array(
				'response' => 'R',
				'code' => '265',
				'name' => 'Missing QHP Amount',
				'action' => 'Fix',
				'comments' => 'Missing QHP Amount',
			),
			266 => array(
				'response' => 'R',
				'code' => '266',
				'name' => 'Invalid QHP Amount',
				'action' => 'Fix',
				'comments' => 'QHP amount greater than transaction amount',
			),
			274 => array(
				'response' => 'R',
				'code' => '274',
				'name' => 'Transaction Not Supported',
				'action' => 'N/A',
				'comments' => 'The requested transaction type is blocked from being used with this card. Note:&nbsp; This may be the result of either an association rule, or a merchant boarding option.',
			),
			301 => array(
				'response' => 'D',
				'code' => '301',
				'name' => 'Issuer unavailable',
				'action' => 'Resend',
				'comments' => 'Authorization network could not reach the bank which issued the card',
			),
			302 => array(
				'response' => 'D',
				'code' => '302',
				'name' => 'Credit Floor',
				'action' => 'Cust',
				'comments' => 'Insufficient funds',
			),
			303 => array(
				'response' => 'D',
				'code' => '303',
				'name' => 'Processor Decline',
				'action' => 'Cust',
				'comments' => 'Generic decline – No other information is being provided by the Issuer',
			),
			304 => array(
				'response' => 'D',
				'code' => '304',
				'name' => 'Not On File',
				'action' => 'Cust',
				'comments' => 'No card record, or invalid/nonexistent to account specified',
			),
			305 => array(
				'response' => 'D',
				'code' => '305',
				'name' => 'Already Reversed',
				'action' => 'N/A',
				'comments' => 'Transaction previously reversed. Note: MOP = any Debit MOP, SV, MC, MD, VI only',
			),
			306 => array(
				'response' => 'D',
				'code' => '306',
				'name' => 'Amount Mismatch',
				'action' => 'Fix',
				'comments' => 'Requested reversal amount does not match original approved authorization amount. Note: MOP = MC, MD, VI only',
			),
			307 => array(
				'response' => 'D',
				'code' => '307',
				'name' => 'Authorization Not Found',
				'action' => 'Fix',
				'comments' => 'Transaction cannot be matched to an authorization that was stored in the database. Note: MOP = MC, MD, VI only',
			),
			351 => array(
				'response' => 'R',
				'code' => '351',
				'name' => 'TransArmor Service Unavailable',
				'action' => 'Resend',
				'comments' => 'TransArmor Service temporarily unavailable.',
			),
			352 => array(
				'response' => 'D',
				'code' => '352',
				'name' => 'Expired Lock',
				'action' => 'Cust',
				'comments' => 'ValueLink - Lock on funds has expired.',
			),
			353 => array(
				'response' => 'R',
				'code' => '353',
				'name' => 'TransArmor Invalid Token or PAN',
				'action' => 'Fix',
				'comments' => 'TransArmor Service encountered a problem converting the given Token or PAN with the given Token Type.',
			),
			354 => array(
				'response' => 'R',
				'code' => '354',
				'name' => 'TransArmor Invalid Result',
				'action' => 'Cust',
				'comments' => 'TransArmor Service encountered a problem with the resulting Token/PAN.',
			),
			401 => array(
				'response' => 'D',
				'code' => '401',
				'name' => 'Call',
				'action' => 'Voice',
				'comments' => 'Issuer wants voice contact with cardholder',
			),
			402 => array(
				'response' => 'D',
				'code' => '402',
				'name' => 'Default Call',
				'action' => 'Voice',
				'comments' => 'Decline',
			),
			501 => array(
				'response' => 'D',
				'code' => '501',
				'name' => 'Pickup',
				'action' => 'Cust',
				'comments' => 'Card Issuer wants card returned',
			),
			502 => array(
				'response' => 'D',
				'code' => '502',
				'name' => 'Lost/Stolen',
				'action' => 'Cust',
				'comments' => 'Card reported as lost/stolen Note: Does not apply to American Express',
			),
			503 => array(
				'response' => 'D',
				'code' => '503',
				'name' => 'Fraud/ Security Violation',
				'action' => 'Cust',
				'comments' => 'CID did not match Note: Discover only',
			),
			505 => array(
				'response' => 'D',
				'code' => '505',
				'name' => 'Negative File',
				'action' => 'Cust',
				'comments' => 'On negative file',
			),
			508 => array(
				'response' => 'D',
				'code' => '508',
				'name' => 'Excessive PIN try',
				'action' => 'Cust',
				'comments' => 'Allowable number of PIN tries exceeded',
			),
			509 => array(
				'response' => 'D',
				'code' => '509',
				'name' => 'Over the limit',
				'action' => 'Cust',
				'comments' => 'Exceeds withdrawal or activity amount limit',
			),
			510 => array(
				'response' => 'D',
				'code' => '510',
				'name' => 'Over Limit Frequency',
				'action' => 'Cust',
				'comments' => 'Exceeds withdrawal or activity count limit',
			),
			519 => array(
				'response' => 'D',
				'code' => '519',
				'name' => 'On negative file',
				'action' => 'Cust',
				'comments' => 'Account number appears on negative file',
			),
			521 => array(
				'response' => 'D',
				'code' => '521',
				'name' => 'Insufficient funds',
				'action' => 'Cust',
				'comments' => 'Insufficient funds/over credit limit',
			),
			522 => array(
				'response' => 'D',
				'code' => '522',
				'name' => 'Card is expired',
				'action' => 'Cust',
				'comments' => 'Card has expired',
			),
			524 => array(
				'response' => 'D',
				'code' => '524',
				'name' => 'Altered Data',
				'action' => 'Fix',
				'comments' => 'Altered Data\Magnetic stripe incorrect',
			),
			525 => array(
				'response' => 'R',
				'code' => '525',
				'name' => 'Server unavailable',
				'action' => 'Wait',
				'comments' => 'Re-submit the transaction in 30 seconds',
			),
			530 => array(
				'response' => 'D',
				'code' => '530',
				'name' => 'Do Not Honor',
				'action' => 'Cust',
				'comments' => 'Generic Decline – No other information is being provided by the issuer. Note: This is a hard decline for BML (will never pass with recycle attempts)',
			),
			531 => array(
				'response' => 'D',
				'code' => '531',
				'name' => 'CVV2/VAK Failure',
				'action' => 'Cust',
				'comments' => 'Issuer has declined auth request because CVV2 or VAK failed',
			),
			534 => array(
				'response' => 'D',
				'code' => '534',
				'name' => 'Do Not Honor - High Fraud',
				'action' => 'Cust',
				'comments' => 'The transaction has failed PayPal or Google Checkout risk models',
			),
			570  => array(
				'response' => 'D ',
				'code' => '570 ',
				'name' => 'Stop payment order one time recurring/ installment',
				'action' => 'Fix',
				'comments' => 'Cardholder has requested this one recurring/installment payment be stopped.',
			),
			571 => array(
				'response' => 'D',
				'code' => '571',
				'name' => 'Revocation of Authorization for All Recurring / Installments',
				'action' => 'Cust',
				'comments' => 'Cardholder has requested all recurring/installment payments be stopped',
			),
			572 => array(
				'response' => 'D',
				'code' => '572',
				'name' => 'Revocation of All Authorizations – Closed Account',
				'action' => 'Cust',
				'comments' => 'Cardholder has requested that all authorizations be stopped for this account due to closed account. Note: Visa only',
			),
			580 => array(
				'response' => 'D',
				'code' => '580',
				'name' => 'Account previously activated',
				'action' => 'Cust',
				'comments' => 'Account previously activated',
			),
			581 => array(
				'response' => 'D',
				'code' => '581',
				'name' => 'Unable to void',
				'action' => 'Fix',
				'comments' => 'Unable to void',
			),
			582 => array(
				'response' => 'D',
				'code' => '582',
				'name' => 'Block activation failed',
				'action' => 'Fix',
				'comments' => 'Reserved for Future Use',
			),
			583 => array(
				'response' => 'D',
				'code' => '583',
				'name' => 'Block Activation Failed',
				'action' => 'Fix',
				'comments' => 'Reserved for Future Use',
			),
			584 => array(
				'response' => 'D',
				'code' => '584',
				'name' => 'Issuance Does Not Meet Minimum Amount',
				'action' => 'Fix',
				'comments' => 'Issuance does not meet minimum amount',
			),
			585 => array(
				'response' => 'D',
				'code' => '585',
				'name' => 'No Original Authorization Found',
				'action' => 'N/A',
				'comments' => 'No original authorization found',
			),
			586 => array(
				'response' => 'D',
				'code' => '586',
				'name' => 'Outstanding Authorization, Funds on Hold',
				'action' => 'N/A',
				'comments' => 'Outstanding Authorization, funds on hold',
			),
			587 => array(
				'response' => 'D',
				'code' => '587',
				'name' => 'Activation Amount Incorrect',
				'action' => 'Fix',
				'comments' => 'Activation amount incorrect',
			),
			588 => array(
				'response' => 'D',
				'code' => '588',
				'name' => 'Block Activation Failed',
				'action' => 'Fix',
				'comments' => 'Reserved for Future Use',
			),
			589 => array(
				'response' => 'D',
				'code' => '589',
				'name' => 'CVD Value Failure',
				'action' => 'Cust',
				'comments' => 'Magnetic stripe CVD value failure',
			),
			590 => array(
				'response' => 'D',
				'code' => '590',
				'name' => 'Maximum Redemption Limit Met',
				'action' => 'Cust',
				'comments' => 'Maximum redemption limit met',
			),
			591 => array(
				'response' => 'D',
				'code' => '591',
				'name' => 'Invalid CC Number',
				'action' => 'Cust',
				'comments' => 'Bad check digit, length or other credit card problem. Issuer generated',
			),
			592 => array(
				'response' => 'D',
				'code' => '592',
				'name' => 'Bad Amount',
				'action' => 'Fix',
				'comments' => 'Amount sent was zero or unreadable. Issuer generated',
			),
			594 => array(
				'response' => 'D',
				'code' => '594',
				'name' => 'Other Error',
				'action' => 'Fix',
				//'comments' => 'Unidentifiable error. Issuer generated',
				'comments' => 'We are unable to verify your checking account or identity information. Please review the information you entered to ensure that all information is correct.',
			),
			595 => array(
				'response' => 'D',
				'code' => '595',
				'name' => 'New Card Issued',
				'action' => 'Cust',
				'comments' => 'New Card Issued',
			),
			596 => array(
				'response' => 'D',
				'code' => '596',
				'name' => 'Suspected Fraud',
				'action' => 'Cust',
				'comments' => 'Issuer has flagged account as suspected fraud',
			),
			599 => array(
				'response' => 'D',
				'code' => '599',
				'name' => 'Refund Not Allowed',
				'action' => 'N/A',
				'comments' => 'Refund Not Allowed',
			),
			602 => array(
				'response' => 'D',
				'code' => '602',
				'name' => 'Invalid Institution Code',
				'action' => 'Fix',
				'comments' => 'Card is bad, but passes MOD 10 check digit routine, wrong BIN',
			),
			603 => array(
				'response' => 'D',
				'code' => '603',
				'name' => 'Invalid Institution',
				'action' => 'Cust',
				'comments' => 'Institution not valid (i.e. possible merger)',
			),
			605 => array(
				'response' => 'D',
				'code' => '605',
				'name' => 'Invalid Expiration Date',
				'action' => 'Cust',
				'comments' => 'Card has expired or bad date sent. Confirm proper date',
			),
			// possible fee not allowed
			606 => array(
				'response' => 'D',
				'code' => '606',
				'name' => 'Invalid Transaction Type',
				'action' => 'Cust',
				'comments' => 'Issuer does not allow this type of transaction',
			),
			607 => array(
				'response' => 'D',
				'code' => '607',
				'name' => 'Invalid Amount',
				'action' => 'Fix',
				'comments' => 'Amount not accepted by network',
			),
			610 => array(
				'response' => 'D',
				'code' => '610',
				'name' => 'BIN Block',
				'action' => 'Cust',
				'comments' => 'Merchant has requested First Data not process credit cards with this BIN',
			),
			704 => array(
				'response' => 'S',
				'code' => '704',
				'name' => 'FPO Accepted',
				'action' => 'N/A',
				'comments' => 'Stored in FPO database',
			),
			740 => array(
				'response' => 'R',
				'code' => '740',
				'name' => 'Match Failed',
				'action' => 'Fix',
				'comments' => 'Unable to validate the debit. Authorization Record - based on amount, action code, and MOP (Batch response reason code for Debit Only)',
			),
			741 => array(
				'response' => 'R/D',
				'code' => '741',
				'name' => 'Validation Failed',
				'action' => 'Fix',
				'comments' => 'Unable to validate the Debit Authorization Record - based on amount, action code, and MOP (Batch response reason code for Debit Only)',
			),
			750 => array(
				'response' => 'R/D',
				'code' => '750',
				'name' => 'Invalid Transit Routing Number',
				'action' => 'Fix',
				'comments' => 'EC - ABA transit routing number is invalid, failed check digit',
			),
			751 => array(
				'response' => 'R/D',
				'code' => '751',
				'name' => 'Transit Routing Number Unknown',
				'action' => 'Fix',
				'comments' => 'Transit routing number not on list of current acceptable numbers.',
			),
			752 => array(
				'response' => 'R',
				'code' => '752',
				'name' => 'Missing Name',
				'action' => 'Fix',
				'comments' => 'Pertains to deposit transactions only',
			),
			753 => array(
				'response' => 'R',
				'code' => '753',
				'name' => 'Invalid Account Type',
				'action' => 'Fix',
				'comments' => 'Pertains to deposit transactions only',
			),
			754 => array(
				'response' => 'R/D',
				'code' => '754',
				'name' => 'Account Closed',
				'action' => 'Cust',
				'comments' => 'Bank account has been closed For PayPal and GoogleCheckout – the customer’s account was closed / restricted',
			),
			755 => array(
				'response' => 'R',
				'code' => '755',
				'name' => 'Subscriber Number Does Not Exist',
				'action' => 'Cust',
				'comments' => 'No Account/Unable to Locate',
			),
			758 => array(
				'response' => 'R',
				'code' => '758',
				'name' => 'Subscriber Number Not Active',
				'action' => 'Cust',
				'comments' => 'Account Frozen',
			),
			760 => array(
				'response' => 'R/D',
				'code' => '760',
				'name' => 'ACH Non-Participant',
				'action' => 'Cust',
				'comments' => 'EC – Banking Institution does not accept ACH transactions',
			),
			776 => array(
				'response' => 'D',
				'code' => '776',
				'name' => 'Duplicate Check',
				'action' => 'Cust',
				'comments' => 'Duplicate Transaction',
			),
			777 => array(
				'response' => 'D',
				'code' => '777',
				'name' => 'Original transaction was not approved',
				'action' => 'Cust',
				'comments' => 'Original not approved',
			),
			787 => array(
				'response' => 'D',
				'code' => '787',
				'name' => 'Rejected Code 3 (Risk)',
				'action' => 'Cust',
				'comments' => 'Decline High Risk',
			),
			788 => array(
				'response' => 'D',
				'code' => '788',
				'name' => 'Refund or partial amount is > original sale amount',
				'action' => 'Fix',
				'comments' => 'Refund Greater than original sale',
			),
			802 => array(
				'response' => 'D',
				'code' => '802',
				'name' => 'Positive ID',
				'action' => 'Voice',
				'comments' => 'Issuer requires further information',
			),
			806 => array(
				'response' => 'D',
				'code' => '806',
				'name' => 'Restraint',
				'action' => 'Cust',
				'comments' => 'Card has been restricted',
			),
			811 => array(
				'response' => 'D',
				'code' => '811',
				'name' => 'Invalid Security Code',
				'action' => 'Fix',
				'comments' => 'American Express CID is incorrect',
			),
			813 => array(
				'response' => 'D',
				'code' => '813',
				'name' => 'Invalid PIN',
				'action' => 'Cust',
				'comments' => 'PIN for online debit transactions is incorrect',
			),
			825 => array(
				'response' => 'D',
				'code' => '825',
				'name' => 'No Account',
				'action' => 'Cust',
				'comments' => 'Account does not exist',
			),
			833 => array(
				'response' => 'D',
				'code' => '833',
				'name' => 'Invalid Merchant',
				'action' => 'Fix',
				'comments' => 'Service Established (SE) number is incorrect, closed or Issuer does not allow this type of transaction',
			),
			834 => array(
				'response' => 'R',
				'code' => '834',
				'name' => 'Unauthorized User',
				'action' => 'Fix',
				'comments' => 'Method of payment is invalid for the division',
			),
			902 => array(
				'response' => 'D',
				'code' => '902',
				'name' => 'Process Unavailable',
				'action' => 'Resend/ Call/ Cust.',
				'comments' => 'System error/malfunction with Issuer For Debit – The link is down or setup issue; contact your First Data Representative.',
			),
			903 => array(
				'response' => 'D',
				'code' => '903',
				'name' => 'Invalid Expiration',
				'action' => 'Cust',
				'comments' => 'Invalid or expired expiration date',
			),
			904 => array(
				'response' => 'D',
				'code' => '904',
				'name' => 'Invalid Effective',
				'action' => 'Cust./ Resend',
				'comments' => 'Card not active',
			),
			997 => array(
				'response' => 'D',
				'code' => '997',
				'name' => 'Acquirer Error',
				'action' => 'Call',
				'comments' => 'Acquiring bank configuration problem. Contact your First Data representative.',
			),
		);
	}
}
