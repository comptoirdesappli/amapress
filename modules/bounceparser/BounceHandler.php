<?php

namespace rambomst\PHPBounceHandler;

class BounceHandler {

	// Properties
	public $head_hash = array();
	public $fbl_hash = array();
	public $body_hash = array();
	public $first_body_hash = array();

	public $looks_like_a_bounce = false;
	public $looks_like_an_FBL = false;
	public $looks_like_an_auto_response = false;
	public $is_hotmail_fbl = false;

	// these are for feedback reports, so you can extract uids from the emails
	// eg X-my-custom-header: userId12345
	// eg <img src="http://mysite.com/track.php?u=userId12345">
	public $web_beacon_preg_1 = '';
	public $web_beacon_preg_2 = '';
	public $x_header_search_1 = '';
	public $x_header_search_2 = '';

	// accessors
	public $type = '';
	public $web_beacon_1 = '';
	public $web_beacon_2 = '';
	public $feedback_type = '';
	public $x_header_beacon_1 = '';
	public $x_header_beacon_2 = '';

	// these accessors are useful only for FBL's
	// or if the output array has only one index
	public $action = '';
	public $status = '';
	public $subject = '';
	public $recipient = '';

	// the raw data set, a multiArray
	public $output = array();

	public function __construct() {
		$this->output[0]['action']    = '';
		$this->output[0]['status']    = '';
		$this->output[0]['recipient'] = '';
	}

	public function parseEmail( $email ) {
		// fluff up the email
		$bounce = $this->initBounceHandler( $email );

		if ( strpos( $bounce, "\r\n\r\n" ) !== false ) {
			list( $head, $body ) = preg_split( "/\r\n\r\n/", $bounce, 2 );
		} else {
			$head = $bounce;
			$body = '';
		}

		$this->head_hash = $this->parseHead( $head );

		// parse the email into data structures
		$boundary = null;
		if ( isset( $this->head_hash['Content-type'] ) ) {
			if ( array_key_exists( 'boundary', $this->head_hash['Content-type'] ) ) {
				$boundary = $this->head_hash['Content-type']['boundary'];
			}
		}

		$mime_sections   = $this->parseBodyIntoMimeSections( $body, $boundary );
		$this->body_hash = explode( "\r\n", $body );

		if ( $mime_sections ) {
			$this->first_body_hash = $this->parseHead( $mime_sections['first_body_part'] );
		}

		$this->looks_like_a_bounce         = $this->isBounce();
		$this->looks_like_an_FBL           = $this->isAnARF();
		$this->looks_like_an_auto_response = ! $this->looks_like_a_bounce && ! $this->looks_like_an_FBL && $this->isAnAutoResponse();

		// is it a Feedback Loop, in Abuse Feedback Reporting Format (ARF)?
		// http://en.wikipedia.org/wiki/Abuse_Reporting_Format#Abuse_Feedback_Reporting_Format_.28ARF.29
		if ( $this->looks_like_an_FBL ) {
			$this->output[0]['action'] = 'failed';
			$this->output[0]['status'] = '5.7.1';
			$this->subject             = trim( str_ireplace( 'Fw:', '', $this->head_hash['Subject'] ) );
			if ( $this->is_hotmail_fbl === true ) {
				// fill in the fbl_hash with sensible values
				$this->fbl_hash['Content-disposition'] = 'inline';
				$this->fbl_hash['Content-type']        = 'message/feedback-report';
				$this->fbl_hash['Feedback-type']       = 'abuse';
				$this->fbl_hash['User-agent']          = 'Hotmail FBL';

				if ( isset( $this->first_body_hash['Date'] ) ) {
					$this->fbl_hash['Received-date'] = $this->first_body_hash['Date'];
				}

				if ( ! empty( $this->recipient ) ) {
					$this->fbl_hash['Original-rcpt-to'] = $this->recipient;
				}

				if ( isset( $this->first_body_hash['X-sid-pra'] ) ) {
					$this->fbl_hash['Original-mail-from'] = $this->first_body_hash['X-sid-pra'];
				}
			} else {
				$this->fbl_hash = $this->standardParser( $mime_sections['machine_parsable_body_part'] );
				$returned_hash  = $this->standardParser( $mime_sections['returned_message_body_part'] );

				if ( ! empty( $returned_hash['Return-path'] ) ) {
					$this->fbl_hash['Original-mail-from'] = $returned_hash['Return-path'];
				} elseif ( empty( $this->fbl_hash['Original-mail-from'] ) && ! empty( $returned_hash['From'] ) ) {
					$this->fbl_hash['Original-mail-from'] = $returned_hash['From'];
				}

				if ( empty( $this->fbl_hash['Original-rcpt-to'] ) && ! empty( $this->fbl_hash['Removal-recipient'] ) ) {
					$this->fbl_hash['Original-rcpt-to'] = $this->fbl_hash['Removal-recipient'];
				} elseif ( ! empty( $returned_hash['To'] ) ) {
					$this->fbl_hash['Original-rcpt-to'] = $returned_hash['To'];
				}
			}

			// warning, some servers will remove the name of the original intended recipient from the FBL report,
			// replacing it with redacted@rcpt-hostname.com, making it utterly useless, of course (unless you used a web-beacon).
			// here we try our best to give you the actual intended recipient, if possible.
			if ( isset( $this->fbl_hash['Removal-recipient'] ) && preg_match( '/Undisclosed|redacted/i', $this->fbl_hash['Original-rcpt-to'] ) ) {
				$this->fbl_hash['Original-rcpt-to'] = $this->fbl_hash['Removal-recipient'];
			}

			if ( empty( $this->fbl_hash['Received-date'] ) && ! empty( $this->fbl_hash['Arrival-date'] ) ) {
				$this->fbl_hash['Received-date'] = $this->fbl_hash['Arrival-date'];
			}

			if ( isset( $this->fbl_hash['Original-mail-from'] ) ) {
				$this->fbl_hash['Original-mail-from'] = $this->stripAngleBrackets( $this->fbl_hash['Original-mail-from'] );
			}

			$this->fbl_hash['Original-rcpt-to'] = $this->stripAngleBrackets( $this->fbl_hash['Original-rcpt-to'] );
			$this->output[0]['recipient']       = $this->fbl_hash['Original-rcpt-to'];
		} elseif ( preg_match( '/auto.{0,20}reply|vacation|(out|away|on holiday).*office/i', $this->head_hash['Subject'] ) ) {
			// looks like a vacation autoreply, ignoring
			$this->output[0]['action'] = 'autoreply';
		} elseif ( $this->looks_like_an_auto_response ) {
			// is this an auto response ?
			$this->output[0]['action'] = 'transient';
			$this->output[0]['status'] = '4.3.2';
			// grab the first recipient and break
			$this->output[0]['recipient'] = isset( $this->head_hash['Return-path'] ) ? $this->stripAngleBrackets( $this->head_hash['Return-path'] ) : '';
			if ( empty( $this->output[0]['recipient'] ) ) {
				foreach ( $this->findEmailAddresses( $body ) as $key => $email_address ) {
					$this->output[ $key ]['recipient'] = trim( $email_address );
				}
			}
		} elseif ( $this->isRFC1892MultipartReport( $mime_sections ) === true ) {
			// TODO: this should probably be cached.  It's already parsed in
			//       isRFC1982MultipartReport
			$rpt_hash = $this->parseMachineParsableBodyPart( $mime_sections['machine_parsable_body_part'] );
			foreach ( $rpt_hash['per_recipient'] as $key => $hash ) {
				$this->output[ $key ]['recipient'] = $this->findRecipient( $hash );
				$my_code                           = $this->formatStatusCode( $hash['Status'] );
				$this->output[ $key ]['status']    = $my_code['code'];
				$this->output[ $key ]['action']    = $hash['Action'];
				$this->output[ $key ]['message']   = $hash['Diagnostic-code']['text'];
			}
		} elseif ( isset( $this->head_hash['X-failed-recipients'] ) ) {
			//  Busted Exim MTA
			//  Up to 50 email addresses can be listed on each header.
			//  There can be multiple X-Failed-Recipients: headers. - (not supported)
			$email_addresses = explode( ',', $this->head_hash['X-failed-recipients'] );
			foreach ( $email_addresses as $key => $email_address ) {
				$this->output[ $key ]['recipient'] = trim( $email_address );
				$this->output[ $key ]['status']    = $this->getStatusCodeFromText( $this->output[ $key ]['recipient'], 0 );
				$this->output[ $key ]['action']    = $this->getActionFromStatusCode( $this->output[ $key ]['status'] );
			}
		} elseif ( ! empty( $boundary ) && $this->looks_like_a_bounce ) {
			// oh god it could be anything, but at least it has mime parts, so let's try anyway
			foreach ( $this->findEmailAddresses( $mime_sections['first_body_part'] ) as $key => $email_address ) {
				$this->output[ $key ]['recipient'] = trim( $email_address );
				$this->output[ $key ]['status']    = $this->getStatusCodeFromText( $this->output[ $key ]['recipient'], 0 );
				$this->output[ $key ]['action']    = $this->getActionFromStatusCode( $this->output[ $key ]['status'] );
			}
		} elseif ( $this->looks_like_a_bounce ) {
			// last ditch attempt
			// could possibly produce erroneous output, or be very resource consuming,
			// so be careful.  You should comment out this section if you are very concerned
			// about 100% accuracy or if you want very fast performance.
			// Leave it turned on if you know that all messages to be analyzed are bounces.
			foreach ( $this->findEmailAddresses( $body ) as $key => $email_address ) {
				$this->output[ $key ]['recipient'] = trim( $email_address );
				$this->output[ $key ]['status']    = $this->getStatusCodeFromText( $this->output[ $key ]['recipient'], 0 );
				$this->output[ $key ]['action']    = $this->getActionFromStatusCode( $this->output[ $key ]['status'] );
			}
		}

		// remove empty array indices
		$tmp = array();
		foreach ( $this->output as $arr ) {
			if ( empty( $arr['recipient'] ) && empty( $arr['status'] ) && empty( $arr['action'] ) ) {
				continue;
			}
			if ( ! empty( $arr['status'] ) ) {
				$status_details        = $this->fetchStatusMessages( $arr['status'] );
				$arr['status_details'] = $status_details;
				if ( $status_details ) {
					if ( empty( $arr['message'] ) ) {
						$arr['message'] = $status_details['status_code_info']['title'] . ' / ' . $status_details['status_code_sub_info']['title'];
					}
				}
			}

			$tmp[] = $arr;
		}

		$this->output = $tmp;

		// accessors
		/*if it is an FBL, you could use the class variables to access the
		data (Unlike Multipart-reports, FBL's report only one bounce)
		*/
		$this->type          = $this->findType();
		$this->action        = isset( $this->output[0]['action'] ) ? $this->output[0]['action'] : '';
		$this->status        = isset( $this->output[0]['status'] ) ? $this->output[0]['status'] : '';
		$this->subject       = $this->subject ?: $this->head_hash['Subject'];
		$this->recipient     = isset( $this->output[0]['recipient'] ) ? $this->output[0]['recipient'] : '';
		$this->feedback_type = isset( $this->fbl_hash['Feedback-type'] ) ? $this->fbl_hash['Feedback-type'] : '';

		// sniff out any web beacons
		if ( $this->web_beacon_preg_1 ) {
			$this->web_beacon_1 = $this->findWebBeacon( $body, $this->web_beacon_preg_1 );
		}

		if ( $this->web_beacon_preg_2 ) {
			$this->web_beacon_2 = $this->findWebBeacon( $body, $this->web_beacon_preg_2 );
		}

		if ( $this->x_header_search_1 ) {
			$this->x_header_beacon_1 = $this->findXHeader( $this->x_header_search_1 );
		}

		if ( $this->x_header_search_2 ) {
			$this->x_header_beacon_2 = $this->findXHeader( $this->x_header_search_2 );
		}

		return $this->output;
	}


	public function initBounceHandler( $blob ) {
		$this->head_hash           = array();
		$this->fbl_hash            = array();
		$this->body_hash           = array();
		$this->looks_like_a_bounce = false;
		$this->looks_like_an_FBL   = false;
		$this->is_hotmail_fbl      = false;
		$this->type                = '';
		$this->feedback_type       = '';
		$this->action              = '';
		$this->status              = '';
		$this->subject             = '';
		$this->recipient           = '';
		$this->output              = array();

		$strEmail = preg_replace( "/(?<!\r)\n/", "\r\n", $blob );

		return str_replace( array( "=\r\n", '=3D', '=09' ), array( '', '=', '  ' ), $strEmail );
	}

	// general purpose recursive heuristic function
	// to try to extract useful info from the bounces produced by busted MTAs
	public function getStatusCodeFromText( $recipient, $index ) {
		for ( $i = $index; $i < count( $this->body_hash ); $i ++ ) {
			$line = trim( $this->body_hash[ $i ] );

			/******** recurse into the email if you find the recipient ********/
			if ( stripos( $line, $recipient ) !== false ) {
				// the status code MIGHT be in the next few lines after the recipient line,
				// depending on the message from the foreign host... What a laugh riot!
				$status_code = $this->getStatusCodeFromText( $recipient, $i + 1 );
				if ( $status_code ) {
					return $status_code;
				}
			}

			/******** exit conditions ********/
			// if it's the end of the human readable part in this stupid bounce
			if ( stripos( $line, '------ This is a copy of the message' ) !== false ) {
				break;
			}

			//if we see an email address other than our current recipient's,
			if ( stripos( $line, $recipient ) === false && strpos( $line, 'FROM:<' ) === false && count( $this->findEmailAddresses( $line ) ) >= 1 ) {
				// Kanon added this line because Hotmail puts the e-mail address too soon and there actually is error message stuff after it.
				break;
			}

			/******** pattern matching ********/
			foreach ( BounceStatus::getBounceList() as $bounce_text => $bounce_code ) {
				if ( preg_match( "/$bounce_text/i", $line, $matches ) ) {
					return isset( $matches[1] ) ? $matches[1] : $bounce_code;
				}
			}

			// rfc1893 return code
			if ( preg_match( '/\W([245]\.[01234567]\.[0-9]{1,2})\W/', $line, $matches ) ) {
				return $matches[1];
			}

			// search for RFC2821 return code
			// thanks to mark.tolman@gmail.com
			// Maybe at some point it should have it's own place within the main parsing scheme (at line 88)
			if ( preg_match( '/\]?: ([45][01257][012345]) /', $line, $matches ) || preg_match( '/^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+/i', $line, $matches ) ) {
				$my_code = $matches[1];
				// map RFC2821 -> RFC3463 codes
				if ( $my_code === '550' || $my_code === '551' || $my_code === '553' || $my_code === '554' ) {
					return '5.1.1';
				} elseif ( $my_code === '452' || $my_code === '552' ) {
					return '4.2.2';
				} elseif ( $my_code === '450' || $my_code === '421' ) {
					return '4.3.2';
				}
			}
		}

		return '5.5.0';  #other or unknown status
	}

	public function isRFC1892MultipartReport( $mime_sections ) {
		// Some mail servers dont follow the RFC.  There can still be
		// a delivery status report in multipart/mixed messages
		if ( ! isset( $this->head_hash['Content-type'] )
		     || ! isset( $this->head_hash['Content-type']['boundary'] )
		     || ( $this->head_hash['Content-type']['type'] !== 'multipart/report' && $this->head_hash['Content-type']['type'] !== 'multipart/mixed' )
		     || ( $this->head_hash['Content-type']['type'] === 'multipart/report' && $this->head_hash['Content-type']['report-type'] !== 'delivery-status' ) ) {
			return false;
		}
		if ( ! array_key_exists( 'machine_parsable_body_part', $mime_sections ) ) {
			return false;
		}
		$rpt_hash = $this->parseMachineParsableBodyPart( $mime_sections['machine_parsable_body_part'] );
		if ( isset( $rpt_hash['mime_header']['Content-type'] ) && $rpt_hash['mime_header']['Content-type'] === 'message/delivery-status' ) {
			return true;
		}

		return false;
	}

	public function parseHead( $headers ) {
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\r\n", $headers );
		}

		$hash = $this->standardParser( $headers );
		if ( ! empty( $hash['Content-type'] ) ) {
			$multipart_report             = explode( ';', $hash['Content-type'] );
			$hash['Content-type']         = array();
			$hash['Content-type']['type'] = strtolower( $multipart_report[0] );

			foreach ( $multipart_report as $mr ) {
				if ( preg_match( '/([^=.]*?)=(.*)/i', $mr, $matches ) ) {
					// didn't work when the content-type boundary ID contained an equal sign,
					// that exists in bounces from many Exchange servers
					$hash['Content-type'][ strtolower( trim( $matches[1] ) ) ] = str_replace( '"', '', $matches[2] );
				}
			}
		}

		return $hash;
	}

	public function parseBodyIntoMimeSections( $body, $boundary ) {
		if ( ! $boundary ) {
			return array();
		}

		if ( is_array( $body ) ) {
			$body = implode( "\r\n", $body );
		}


		$body                                        = explode( $boundary, $body );
		$mime_sections['first_body_part']            = $body[1];
		$mime_sections['machine_parsable_body_part'] = $body[2];

		if ( isset( $body[3] ) ) {
			$mime_sections['returned_message_body_part'] = $body[3];
		}

		return $mime_sections;
	}

	/**
	 * @param string|array $content
	 *
	 * @return array
	 */
	public function standardParser( $content ) {
		// receives email head as array of lines
		// simple parse (Entity: value\n)
		$hash = array( 'Received' => '' );
		if ( ! is_array( $content ) ) {
			$content = explode( "\r\n", $content );
		}

		foreach ( $content as $line ) {
			if ( preg_match( '/^([^\s.]*):\s*(.*)\s*/', $line, $array ) ) {
				$entity = ucfirst( strtolower( $array[1] ) );

				// decode MIME Header encoding (subject lines etc)
				if ( isset( $array[2] ) && strpos( $array[2], '=?' ) !== false ) {
					$array[2] = iconv_mime_decode( $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8' );
				}

				if ( empty( $hash[ $entity ] ) ) {
					$hash[ $entity ] = trim( $array[2] );
				} elseif ( $hash['Received'] ) {
					// grab extra Received headers :(
					// pile it on with pipe delimiters,
					// oh well, SMTP is broken in this way
					if ( $entity && $array[2] && $array[2] !== $hash[ $entity ] ) {
						$hash[ $entity ] .= '|' . trim( $array[2] );
					}
				}
			} elseif ( ! empty( $entity ) && preg_match( '/^\s+(.+)\s*/', $line ) ) {
				$line = trim( $line );
				if ( isset( $array[2] ) && strpos( $array[2], '=?' ) !== false ) {
					$line = iconv_mime_decode( $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8' );
				}

				$hash[ $entity ] .= ' ' . $line;
			}
		}

		// special formatting
		$hash['Received'] = explode( '|', $hash['Received'] );
		$hash['Subject']  = isset( $hash['Subject'] ) ? $hash['Subject'] : '';

		return $hash;
	}

	public function parseMachineParsableBodyPart( $str ) {
		//Per-Message DSN fields
		$hash                = $this->parseDsnFields( $str );
		$hash['mime_header'] = $this->standardParser( $hash['mime_header'] );
		$hash['per_message'] = $this->standardParser( $hash['per_message'] );
		if ( ! empty( $hash['per_message']['X-postfix-sender'] ) ) {
			$arr                                             = explode( ';', $hash['per_message']['X-postfix-sender'] );
			$hash['per_message']['X-postfix-sender']         = array();
			$hash['per_message']['X-postfix-sender']['type'] = trim( $arr[0] );
			$hash['per_message']['X-postfix-sender']['addr'] = trim( $arr[1] );
		}

		if ( ! empty( $hash['per_message']['Reporting-mta'] ) ) {
			$arr                                          = explode( ';', $hash['per_message']['Reporting-mta'] );
			$hash['per_message']['Reporting-mta']         = array();
			$hash['per_message']['Reporting-mta']['type'] = trim( $arr[0] );
			$hash['per_message']['Reporting-mta']['addr'] = trim( $arr[1] );
		}

		if ( isset( $hash['per_recipient'] ) ) {
			//Per-Recipient DSN fields
			foreach ( $hash['per_recipient'] as $key => $recipient ) {
				$temp = $this->standardParser( explode( "\r\n", $recipient ) );
				if ( isset( $temp['Final-recipient'] ) ) {
					$arr                     = explode( ';', $temp['Final-recipient'] );
					$temp['Final-recipient'] = $this->formatFinalRecipientArray( $arr );
				}

				if ( isset( $temp['Original-recipient'] ) ) {
					$arr                        = explode( ';', $temp['Original-recipient'] );
					$temp['Original-recipient'] = array();
					if ( count( $arr ) > 1 ) {
						$temp['Original-recipient']['type'] = trim( $arr[0] );
						$temp['Original-recipient']['addr'] = trim( $arr[1] );
					} else {
						$temp['Original-recipient']['type'] = "";
						// May be in <>
						$temp['Original-recipient']['addr'] = trim( $arr[0] );
					}
				}

				if ( isset( $temp['Diagnostic-code'] ) ) {
					$arr                             = explode( ';', $temp['Diagnostic-code'] );
					$temp['Diagnostic-code']         = array();
					$temp['Diagnostic-code']['type'] = trim( $arr[0] );
					$temp['Diagnostic-code']['text'] = trim( $arr[1] );
				}

				// now this is weird: plenty of times you see the status code is a permanent failure,
				// but the diagnostic code is a temporary failure.  So we will assert the most general
				// temporary failure in this case.
				if ( isset( $temp['Diagnostic-code'] ) ) {
					$ddc       = $this->decodeDiagnosticCode( $temp['Diagnostic-code']['text'] );
					$judgement = $this->getActionFromStatusCode( $ddc );
					if ( $judgement === 'transient' && stripos( $temp['Action'], 'failed' ) !== false ) {
						$temp['Action'] = 'transient';
						$temp['Status'] = '4.3.0';
					}
				}

				$hash['per_recipient'][ $key ] = $temp;
			}
		}


		return $hash;
	}

	public function getHeadFromReturnedMessageBodyPart( $mime_sections ) {
		$temp         = explode( "\r\n\r\n", $mime_sections['returned_message_body_part'] );
		$head         = $this->standardParser( $temp[1] );
		$head['From'] = $this->extractAddress( $head['From'] );
		$head['To']   = $this->extractAddress( $head['To'] );

		return $head;
	}

	public function extractAddress( $str ) {
		$from       = null;
		$from_stuff = preg_split( '/[ \"\'\<\>:\(\)\[\]]/', $str );
		foreach ( $from_stuff as $things ) {
			if ( strpos( $things, '@' ) !== false ) {
				$from = $things;
			}
		}

		return $from;
	}

	public function findRecipient( $per_rcpt ) {
		$recipient = '';
		if ( isset( $per_rcpt['Original-recipient'] ) && $per_rcpt['Original-recipient']['addr'] !== '' ) {
			$recipient = $per_rcpt['Original-recipient']['addr'];
		} elseif ( isset( $per_rcpt['Final-recipient'] ) && $per_rcpt['Final-recipient']['addr'] !== '' ) {
			$recipient = $per_rcpt['Final-recipient']['addr'];
		}

		$recipient = $this->stripAngleBrackets( $recipient );

		return $recipient;
	}

	public function findType() {
		if ( $this->looks_like_a_bounce ) {
			return 'bounce';
		} elseif ( $this->looks_like_an_FBL ) {
			return 'fbl';
		} else {
			return false;
		}
	}

	public function parseDsnFields( $dsn_fields ) {
		if ( ! is_array( $dsn_fields ) ) {
			$dsn_fields = explode( "\r\n\r\n", $dsn_fields );
		}

		$j = 0;
		reset( $dsn_fields );
		$hash = array(
			'mime_header'   => array(),
			'per_message'   => array(),
			'per_recipient' => array(),
		);
		for ( $i = 0; $i < count( $dsn_fields ); $i ++ ) {
			$dsn_fields[ $i ] = trim( $dsn_fields[ $i ] );
			if ( $i === 0 ) {
				$hash['mime_header'] = $dsn_fields[0];
			} elseif ( $i === 1 && ! preg_match( '/(Final|Original)-Recipient/', $dsn_fields[1] ) ) {
				// some mta's don't output the per_message part, which means
				// the second element in the array should really be
				// per_recipient - test with Final-Recipient - which should always
				// indicate that the part is a per_recipient part
				$hash['per_message'] = $dsn_fields[1];
			} else {
				if ( $dsn_fields[ $i ] === '--' ) {
					continue;
				}

				$hash['per_recipient'][ $j ] = $dsn_fields[ $i ];
				$j ++;
			}
		}

		return $hash;
	}

	public function formatStatusCode( $code ) {
		$ret = array();
		if ( preg_match( '/([245]\.[01234567]\.\d{1,2})\s*(.*)/', $code, $matches ) ) {
			$ret['code'] = $matches[1];
			$ret['text'] = $matches[2];
		} elseif ( preg_match( '/([245])([01234567])(\d{1,2})[\s\-]*(.*)/', $code, $matches ) ) {
			$ret['code'] = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
			$ret['text'] = $matches[4];
		}

		return $ret;
	}

	public function fetchStatusMessages( $code ) {
		$ret = $this->formatStatusCode( $code );
		$arr = explode( '.', $ret['code'] );

		$return_array                         = array();
		$return_array['status_code_info']     = BounceStatus::getStatusCodeClasses()[ $arr[0] ];
		$return_array['status_code_sub_info'] = BounceStatus::getStatusCodeSubClasses()[ $arr[1] . '.' . $arr[2] ];

		return $return_array;
	}

	public function getActionFromStatusCode( $code ) {
		if ( ! $code ) {
			return '';
		}

		$ret = $this->formatStatusCode( $code );
		if ( $ret ) {
			$stat = $ret['code'][0];
		} else {
			$stat = 1;
		}
		switch ( $stat ) {
			case( 2 ):
				return 'success';
				break;
			case( 4 ):
				return 'transient';
				break;
			case( 5 ):
				return 'failed';
				break;
			default:
				return '';
				break;
		}
	}

	public function decodeDiagnosticCode( $diagnostic_code ) {
		if ( preg_match( "/(\d\.\d\.\d)\s/", $diagnostic_code, $array ) ) {
			return $array[1];
		} elseif ( preg_match( "/(\d\d\d)[\s\-]/", $diagnostic_code, $array ) ) {
			return $array[1];
		}

		return false;
	}

	public function isBounce() {
		foreach ( BounceStatus::getBounceSubj() as $s ) {
			if ( preg_match( "/^$s/i", $this->head_hash['Subject'] ) ) {
				return true;
			}
		}

		if ( isset( $this->head_hash['Precedence'] ) && strpos( $this->head_hash['Precedence'], 'auto_reply' ) !== false ) {
			return true;
		}

		$from = $this->findEmailAddresses( $this->head_hash['From'] );
		if ( count( $from ) ) {
			if ( preg_match( "/^(postmaster|mailer-daemon)\@?/i", $from[0] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $string String to find email addresses in
	 *
	 * @return array Returns an array of email addresses
	 */
	public function findEmailAddresses( $string ) {
		$regex = '/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/i';
		preg_match_all( $regex, $string, $matches );
		if ( $matches && ! empty( $matches[0] ) ) {
			return $matches[0];
		}

		return array();
	}


	// these functions are for feedback loops
	public function isAnARF() {
		if ( isset( $this->head_hash['Content-type']['report-type'] ) && strpos( $this->head_hash['Content-type']['report-type'], 'feedback-report' ) !== false ) {
			return true;
		}

		if ( isset( $this->head_hash['X-loop'] ) && strpos( $this->head_hash['X-loop'], 'scomp' ) !== false ) {
			return true;
		}

		if ( isset( $this->head_hash['X-hmxmroriginalrecipient'] ) ) {
			$this->is_hotmail_fbl = true;
			$this->recipient      = $this->head_hash['X-hmxmroriginalrecipient'];

			return true;
		}

		if ( isset( $this->first_body_hash['X-hmxmroriginalrecipient'] ) ) {
			$this->is_hotmail_fbl = true;
			$this->recipient      = $this->first_body_hash['X-hmxmroriginalrecipient'];

			return true;
		}

		return false;
	}

	// look for common auto-responders
	public function isAnAutoResponse() {
		$subj = isset( $this->head_hash['Subject'] ) ? $this->head_hash['Subject'] : '';

		foreach ( BounceStatus::getAutoRespondList() as $a ) {
			if ( preg_match( "/$a/i", $subj ) ) {
				return true;
			}
		}

		// AOL & gmail autoreply, maybe others.  RFC3834 Sectoin 5.1 suggests
		// other potential matches.
		// https://tools.ietf.org/html/rfc3834#section-5
		if ( isset( $this->head_hash['Auto-submitted'] ) && stripos( $this->head_hash['Auto-submitted'], 'auto-replied' ) !== false ) {
			return true;
		}

		if ( isset( $this->head_hash['Delivered-to'] ) && stripos( $this->head_hash['Delivered-to'], 'autoresponder' ) !== false ) {
			return true;
		}

		return false;
	}

	// use a perl regular expression to find the web beacon
	public function findWebBeacon( $body, $preg ) {
		if ( ! $preg ) {
			return '';
		}

		if ( preg_match( $preg, $body, $matches ) ) {
			return $matches[1];
		}

		return false;
	}

	public function findXHeader( $x_header ) {
		$x_header = ucfirst( strtolower( $x_header ) );
		// check the header
		if ( isset( $this->head_hash[ $x_header ] ) ) {
			return $this->head_hash[ $x_header ];
		}

		// check the body too
		$tmp_body_hash = $this->standardParser( $this->body_hash );
		if ( isset( $tmp_body_hash[ $x_header ] ) ) {
			return $tmp_body_hash[ $x_header ];
		}

		return '';
	}

	private function stripAngleBrackets( $recipient ) {
		return str_replace( array( '<', '>' ), '', $recipient );
	}

	/*The syntax of the final-recipient field is as follows:
	"Final-Recipient" ":" address-type ";" generic-address
	*/
	private function formatFinalRecipientArray( $arr ) {
		$output = array( 'addr' => '', 'type' => '' );
		if ( strpos( $arr[0], '@' ) !== false ) {
			$output['addr'] = $this->stripAngleBrackets( $arr[0] );
			$output['type'] = ( ! empty( $arr[1] ) ) ? trim( $arr[1] ) : 'unknown';
		} else {
			$output['type'] = trim( $arr[0] );
			$output['addr'] = $this->stripAngleBrackets( $arr[1] );
		}

		return $output;
	}
}
