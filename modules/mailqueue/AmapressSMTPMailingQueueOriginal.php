<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressSMTPMailingQueueOriginal {
	/**
	 * Send mail, similar to PHP's mail
	 *
	 * A true return value does not automatically mean that the user received the
	 * email successfully. It just only means that the method used was able to
	 * process the request without any errors.
	 *
	 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
	 * creating a from address like 'Name <email@address.com>' when both are set. If
	 * just 'wp_mail_from' is set, then just the email address will be used with no
	 * name.
	 *
	 * The default content type is 'text/plain' which does not allow using HTML.
	 * However, you can set the content type of the email by using the
	 * 'wp_mail_content_type' filter.
	 *
	 * The default charset is based on the charset used on the blog. The charset can
	 * be set using the 'wp_mail_charset' filter.
	 *
	 * @since 1.2.1
	 *
	 * @global PHPMailer $phpmailer
	 *
	 * @param string|array $to Array or comma-separated list of email addresses to send message.
	 * @param string $subject Email subject
	 * @param string $message Message contents
	 * @param string|array $headers Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return array if empty, no error
	 */
	public static function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$errors = array();
		// Compact the input, apply the filters, and extract them back out

		/**
		 * Filter the wp_mail() arguments.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
		 *                    subject, message, headers, and attachments values.
		 */
		$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

		if ( isset( $atts['to'] ) ) {
			$to = $atts['to'];
		}

		if ( isset( $atts['subject'] ) ) {
			$subject = $atts['subject'];
		}

		if ( isset( $atts['message'] ) ) {
			$message = $atts['message'];
		}

		if ( isset( $atts['headers'] ) ) {
			$headers = $atts['headers'];
		}

		if ( isset( $atts['attachments'] ) ) {
			$attachments = $atts['attachments'];
		}

		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}
		global $phpmailer;

		// (Re)create it, if it's gone missing
		if ( ! ( $phpmailer instanceof PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			$phpmailer = new PHPMailer( true );
		}

		$reply_to      = '';
		$reply_to_name = '';
		// Headers
		if ( empty( $headers ) ) {
			$headers = array();
		} else {
			if ( ! is_array( $headers ) ) {
				// Explode the headers out, so this function can take both
				// string headers and an array of headers.
				$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
			} else {
				$tempheaders = $headers;
			}
			$headers = array();
			$cc      = array();
			$bcc     = array();

			// If it's actually got contents
			if ( ! empty( $tempheaders ) ) {
				// Iterate through the raw headers
				foreach ( (array) $tempheaders as $header ) {
					if ( strpos( $header, ':' ) === false ) {
						if ( false !== stripos( $header, 'boundary=' ) ) {
							$parts    = preg_split( '/boundary=/i', trim( $header ) );
							$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
						}
						continue;
					}
					// Explode them out
					list( $name, $content ) = explode( ':', trim( $header ), 2 );

					// Cleanup crew
					$name    = trim( $name );
					$content = trim( $content );

					switch ( strtolower( $name ) ) {
						// Mainly for legacy -- process a From: header if it's there
						case 'from':
							$bracket_pos = strpos( $content, '<' );
							if ( $bracket_pos !== false ) {
								// Text before the bracketed email is the "From" name.
								if ( $bracket_pos > 0 ) {
									$from_name = substr( $content, 0, $bracket_pos - 1 );
									$from_name = str_replace( '"', '', $from_name );
									$from_name = trim( $from_name );
								}

								$from_email = substr( $content, $bracket_pos + 1 );
								$from_email = str_replace( '>', '', $from_email );
								$from_email = trim( $from_email );

								// Avoid setting an empty $from_email.
							} elseif ( '' !== trim( $content ) ) {
								$from_email = trim( $content );
							}
							break;
						case 'reply-to':
							$bracket_pos = strpos( $content, '<' );
							if ( $bracket_pos !== false ) {
								// Text before the bracketed email is the "From" name.
								if ( $bracket_pos > 0 ) {
									$reply_to_name = substr( $content, 0, $bracket_pos - 1 );
									$reply_to_name = str_replace( '"', '', $reply_to_name );
									$reply_to_name = trim( $reply_to_name );
								}

								$reply_to = substr( $content, $bracket_pos + 1 );
								$reply_to = str_replace( '>', '', $reply_to );
								$reply_to = trim( $reply_to );

								// Avoid setting an empty $from_email.
							} elseif ( '' !== trim( $content ) ) {
								$reply_to = trim( $content );
							}
							break;
						case 'content-type':
							if ( strpos( $content, ';' ) !== false ) {
								list( $type, $charset_content ) = explode( ';', $content );
								$content_type = trim( $type );
								if ( false !== stripos( $charset_content, 'charset=' ) ) {
									$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
								} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
									$boundary = trim( str_replace( array(
										'BOUNDARY=',
										'boundary=',
										'"'
									), '', $charset_content ) );
									$charset  = '';
								}

								// Avoid setting an empty $content_type.
							} elseif ( '' !== trim( $content ) ) {
								$content_type = trim( $content );
							}
							break;
						case 'cc':
							$cc = array_merge( (array) $cc, explode( ',', $content ) );
							break;
						case 'bcc':
							$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
							break;
						default:
							// Add it to our grand headers array
							$headers[ trim( $name ) ] = trim( $content );
							break;
					}
				}
			}
		}

		// Empty out the values that may be set
		$phpmailer->ClearAllRecipients();
		$phpmailer->ClearAttachments();
		$phpmailer->ClearCustomHeaders();
		$phpmailer->ClearReplyTos();

		// From email and name
		// If we don't have a name from the input headers
		if ( ! isset( $from_name ) ) {
			$from_name = 'WordPress';
		}

		/* If we don't have an email from the input headers default to wordpress@$sitename
		 * Some hosts will block outgoing mail from this address if it doesn't exist but
		 * there's no easy alternative. Defaulting to admin_email might appear to be another
		 * option but some hosts may refuse to relay mail from an unknown domain. See
		 * https://core.trac.wordpress.org/ticket/5007.
		 */

		if ( ! isset( $from_email ) ) {
			// Get the site domain and get rid of www.
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}

			$from_email = 'wordpress@' . $sitename;
		}

		/**
		 * Filter the email address to send from.
		 *
		 * @since 2.2.0
		 *
		 * @param string $from_email Email address to send from.
		 */
		$phpmailer->From = apply_filters( 'wp_mail_from', $from_email );

		/**
		 * Filter the name to associate with the "from" email address.
		 *
		 * @since 2.3.0
		 *
		 * @param string $from_name Name associated with the "from" email address.
		 */
		$phpmailer->FromName = apply_filters( 'wp_mail_from_name', $from_name );

		// Set destination addresses
		if ( ! is_array( $to ) ) {
			$to = explode( ',', $to );
		}

		foreach ( (array) $to as $recipient ) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';
				if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$recipient      = $matches[2];
					}
				}
				$phpmailer->AddAddress( $recipient, $recipient_name );
			} catch ( phpmailerException $e ) {
				$errors[] = $e->errorMessage();
				continue;
			}
		}

		// Set mail's subject and body
		$phpmailer->Subject = $subject;
		$phpmailer->Body    = $message;

		// Add any CC and BCC recipients
		if ( ! empty( $cc ) ) {
			foreach ( (array) $cc as $recipient ) {
				try {
					// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
					$recipient_name = '';
					if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
						if ( count( $matches ) == 3 ) {
							$recipient_name = $matches[1];
							$recipient      = $matches[2];
						}
					}
					$phpmailer->AddCc( $recipient, $recipient_name );
				} catch ( phpmailerException $e ) {
					$errors[] = $e->errorMessage();
					continue;
				}
			}
		}

		if ( ! empty( $bcc ) ) {
			foreach ( (array) $bcc as $recipient ) {
				try {
					// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
					$recipient_name = '';
					if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
						if ( count( $matches ) == 3 ) {
							$recipient_name = $matches[1];
							$recipient      = $matches[2];
						}
					}
					$phpmailer->AddBcc( $recipient, $recipient_name );
				} catch ( phpmailerException $e ) {
					$errors[] = $e->errorMessage();
					continue;
				}
			}
		}

		// Set to use PHP's mail()
		$phpmailer->IsMail();

		// Set Content-Type and charset
		// If we don't have a content-type from the input headers
		if ( ! isset( $content_type ) ) {
			$content_type = 'text/plain';
		}

		/**
		 * Filter the wp_mail() content type.
		 *
		 * @since 2.3.0
		 *
		 * @param string $content_type Default wp_mail() content type.
		 */
		$content_type = apply_filters( 'wp_mail_content_type', $content_type );

		$phpmailer->ContentType = $content_type;

		// Set whether it's plaintext, depending on $content_type
		if ( 'text/html' == $content_type ) {
			if ( defined( 'SEND_EMAILS_AS_PLAIN_TEXT' ) ) {
				$phpmailer->IsHTML( false );
				$phpmailer->Body    = $phpmailer->html2text( $phpmailer->Body );
				$phpmailer->AltBody = null;
				$content_type       = 'text/plain';
			} else {
				$phpmailer->IsHTML( true );
				$phpmailer->AltBody = $phpmailer->html2text( $phpmailer->Body );
			}
		}

		// If we don't have a charset from the input headers
		if ( ! isset( $charset ) ) {
			$charset = get_bloginfo( 'charset' );
		}

		// Set the content-type and charset

		/**
		 * Filter the default wp_mail() charset.
		 *
		 * @since 2.3.0
		 *
		 * @param string $charset Default email charset.
		 */
		$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

		if ( ! empty( $reply_to ) ) {
			$phpmailer->addReplyTo( $reply_to, $reply_to_name );
		}

		// Set custom headers
		if ( ! empty( $headers ) ) {
			foreach ( (array) $headers as $name => $content ) {
				$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
			}

			if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
				$phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
			}
		}

		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				try {
					$phpmailer->AddAttachment( $attachment );
				} catch ( phpmailerException $e ) {
					$errors[] = $e->errorMessage();
					continue;
				}
			}
		}

		/**
		 * Fires after PHPMailer is initialized.
		 *
		 * @since 2.2.0
		 *
		 * @param PHPMailer &$phpmailer The PHPMailer instance, passed by reference.
		 */
		do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

		// Send!
		try {
			if ( defined( 'FREE_PAGES_PERSO' ) ) {
				$start_time = time();
				if ( ! $phpmailer->Send() ) {
					$errors[] = $phpmailer->ErrorInfo;
				} else {
					$time = time() - $start_time;
					if ( $time < 1.5 ) {
						$errors[] = 'Free Page Perso mail send failed';
					}
				}
			} else {
				if ( ! $phpmailer->Send() ) {
					$errors[] = $phpmailer->ErrorInfo;
				}
			}
		} catch ( phpmailerException $e ) {
			$errors[] = $e->errorMessage();
		}

		return $errors;
	}
}