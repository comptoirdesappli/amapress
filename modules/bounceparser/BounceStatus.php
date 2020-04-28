<?php

namespace rambomst\PHPBounceHandler;

class BounceStatus {

	protected static $status_code_classes = array(
		# [RFC3463] (Standards track)
		'2' => array(
			'title'       => 'Success',
			'description' => 'Success specifies that the DSN is reporting a positive delivery action. Detail sub-codes may provide notification of transformations required for delivery.'
		),
		'4' => array(
			'title'       => 'Persistent Transient Failure',
			'description' => 'A persistent transient failure is one in which the message as sent is valid, but persistence of some temporary condition has caused abandonment or delay of attempts to send the message. If this code accompanies a delivery failure report, sending in the future may be successful.'
		),
		'5' => array(
			'title'       => 'Permanent Failure',
			'description' => 'A permanent failure is one which is not likely to be resolved by resending the message in the current form. Some change to the message or the destination must be made for successful delivery.'
		)
	);
	protected static $status_code_sub_classes = array(
		# [RFC3463] (Standards Track)
		'0.0'  => array(
			'title'       => 'Other undefined Status',
			'description' => 'Other undefined status is the only undefined error code. It should be used for all errors for which only the class of the error is known.',
		),
		'1.0'  => array(
			'title'       => 'Other address status',
			'description' => 'Something about the address specified in the message caused this DSN.',
		),
		'1.1'  => array(
			'title'       => 'Bad destination mailbox address',
			'description' => 'The mailbox specified in the address does not exist. For Internet mail names, this means the address portion to the left of the "@" sign is invalid. This code is only useful for permanent failures.',
		),
		'1.2'  => array(
			'title'       => 'Bad destination system address',
			'description' => 'The destination system specified in the address does not exist or is incapable of accepting mail. For Internet mail names, this means the address portion to the right of the "@" is invalid for mail. This code is only useful for permanent failures.',
		),
		'1.3'  => array(
			'title'       => 'Bad destination mailbox address syntax',
			'description' => 'The destination address was syntactically invalid. This can apply to any field in the address. This code is only useful for permanent failures.',
		),
		'1.4'  => array(
			'title'       => 'Destination mailbox address ambiguous',
			'description' => 'The mailbox address as specified matches one or more recipients on the destination system. This may result if a heuristic address mapping algorithm is used to map the specified address to a local mailbox name.',
		),
		'1.5'  => array(
			'title'       => 'Destination address valid',
			'description' => 'This mailbox address as specified was valid. This status code should be used for positive delivery reports.',
		),
		'1.6'  => array(
			'title'       => 'Destination mailbox has moved, No forwarding address',
			'description' => 'The mailbox address provided was at one time valid, but mail is no longer being accepted for that address. This code is only useful for permanent failures.',
		),
		'1.7'  => array(
			'title'       => 'Bad sender\'s mailbox address syntax',
			'description' => 'The sender\'s address was syntactically invalid. This can apply to any field in the address.',
		),
		'1.8'  => array(
			'title'       => 'Bad sender\'s system address',
			'description' => 'The sender\'s system specified in the address does not exist or is incapable of accepting return mail. For domain names, this means the address portion to the right of the "@" is invalid for mail.',
		),
		# [RFC3886] (Standards Track)
		'1.9'  => array(
			'title'       => 'Message relayed to non-compliant mailer',
			'description' => 'The mailbox address specified was valid, but the message has been relayed to a system that does not speak this protocol; no further information can be provided.',
		),
		# [RFC7505] (Standards Track); [RFC7504] (Standards Track)
		'1.10' => array(
			'title'       => 'Recipient address has null MX',
			'description' => 'This status code is returned when the associated address is marked as invalid using a null MX.',
		),
		# [RFC3463] (Standards Track)
		'2.0'  => array(
			'title'       => 'Other or undefined mailbox status',
			'description' => 'The mailbox exists, but something about the destination mailbox has caused the sending of this DSN.',
		),
		'2.1'  => array(
			'title'       => 'Mailbox disabled, not accepting messages',
			'description' => 'The mailbox exists, but is not accepting messages. This may be a permanent error if the mailbox will never be re-enabled or a transient error if the mailbox is only temporarily disabled.',
		),
		'2.2'  => array(
			'title'       => 'Mailbox full',
			'description' => 'The mailbox is full because the user has exceeded a per-mailbox administrative quota or physical capacity. The general semantics implies that the recipient can delete messages to make more space available. This code should be used as a persistent transient failure.',
		),
		'2.3'  => array(
			'title'       => 'Message length exceeds administrative limit',
			'description' => 'A per-mailbox administrative message length limit has been exceeded. This status code should be used when the per-mailbox message length limit is less than the general system limit. This code should be used as a permanent failure.',
		),
		'2.4'  => array(
			'title'       => 'Mailing list expansion problem',
			'description' => 'The mailbox is a mailing list address and the mailing list was unable to be expanded. This code may represent a permanent failure or a persistent transient failure.',
		),
		'3.0'  => array(
			'title'       => 'Other or undefined mail system status',
			'description' => 'The destination system exists and normally accepts mail, but something about the system has caused the generation of this DSN.',
		),
		'3.1'  => array(
			'title'       => 'Mail system full',
			'description' => 'Mail system storage has been exceeded. The general semantics imply that the individual recipient may not be able to delete material to make room for additional messages. This is useful only as a persistent transient error.',
		),
		# [RFC3463] (Standards Track); [RFC7504] (Standards Track)
		'3.2'  => array(
			'title'       => 'System not accepting network messages',
			'description' => 'The host on which the mailbox is resident is not accepting messages. Examples of such conditions include an imminent shutdown, excessive load, or system maintenance. This is useful for both permanent and persistent transient errors.',
		),
		# [RFC3463] (Standards Track)
		'3.3'  => array(
			'title'       => 'System not capable of selected features',
			'description' => 'Selected features specified for the message are not supported by the destination system. This can occur in gateways when features from one domain cannot be mapped onto the supported feature in another.',
		),
		'3.4'  => array(
			'title'       => 'Message too big for system',
			'description' => 'The message is larger than per-message size limit. This limit may either be for physical or administrative reasons. This is useful only as a permanent error.',
		),
		'3.5'  => array(
			'title'       => 'System incorrectly configured',
			'description' => 'The system is not configured in a manner that will permit it to accept this message.',
		),
		# [RFC6710] (Standards Track)
		'3.6'  => array(
			'title'       => 'Requested priority was changed',
			'description' => 'The message was accepted for relay/delivery, but the requested priority (possibly the implied default) was not honoured. The human readable text after the status code contains the new priority, followed by SP (space) and explanatory human readable text.',
		),
		# [RFC3463] (Standards Track)
		'4.0'  => array(
			'title'       => 'Other or undefined network or routing status',
			'description' => 'Something went wrong with the networking, but it is not clear what the problem is, or the problem cannot be well expressed with any of the other provided detail codes.',
		),
		'4.1'  => array(
			'title'       => 'No answer from host',
			'description' => 'The outbound connection attempt was not answered, because either the remote system was busy, or was unable to take a call. This is useful only as a persistent transient error.',
		),
		'4.2'  => array(
			'title'       => 'Bad connection',
			'description' => 'The outbound connection was established, but was unable to complete the message transaction, either because of time-out, or inadequate connection quality. This is useful only as a persistent transient error.',
		),
		'4.3'  => array(
			'title'       => 'Directory server failure',
			'description' => 'The network system was unable to forward the message, because a directory server was unavailable. This is useful only as a persistent transient error. The inability to connect to an Internet DNS server is one example of the directory server failure error.',
		),
		'4.4'  => array(
			'title'       => 'Unable to route',
			'description' => 'The mail system was unable to determine the next hop for the message because the necessary routing information was unavailable from the directory server. This is useful for both permanent and persistent transient errors. A DNS lookup returning only an SOA (Start of Administration) record for a domain name is one example of the unable to route error.',
		),
		'4.5'  => array(
			'title'       => 'Mail system congestion',
			'description' => 'The mail system was unable to deliver the message because the mail system was congested. This is useful only as a persistent transient error.',
		),
		'4.6'  => array(
			'title'       => 'Routing loop detected',
			'description' => 'A routing loop caused the message to be forwarded too many times, either because of incorrect routing tables or a user- forwarding loop. This is useful only as a persistent transient error.',
		),
		'4.7'  => array(
			'title'       => 'Delivery time expired',
			'description' => 'The message was considered too old by the rejecting system, either because it remained on that host too long or because the time-to-live value specified by the sender of the message was exceeded. If possible, the code for the actual problem found when delivery was attempted should be returned rather than this code.',
		),
		'5.0'  => array(
			'title'       => 'Other or undefined protocol status',
			'description' => 'Something was wrong with the protocol necessary to deliver the message to the next hop and the problem cannot be well expressed with any of the other provided detail codes.',
		),
		'5.1'  => array(
			'title'       => 'Invalid command',
			'description' => 'A mail transaction protocol command was issued which was either out of sequence or unsupported. This is useful only as a permanent error.',
		),
		'5.2'  => array(
			'title'       => 'Syntax error',
			'description' => 'A mail transaction protocol command was issued which could not be interpreted, either because the syntax was wrong or the command is unrecognized. This is useful only as a permanent error.',
		),
		'5.3'  => array(
			'title'       => 'Too many recipients',
			'description' => 'More recipients were specified for the message than could have been delivered by the protocol. This error should normally result in the segmentation of the message into two, the remainder of the recipients to be delivered on a subsequent delivery attempt. It is included in this list in the event that such segmentation is not possible.',
		),
		'5.4'  => array(
			'title'       => 'Invalid command arguments',
			'description' => 'A valid mail transaction protocol command was issued with invalid arguments, either because the arguments were out of range or represented unrecognized features. This is useful only as a permanent error.',
		),
		'5.5'  => array(
			'title'       => 'Wrong protocol version',
			'description' => 'A protocol version mis-match existed which could not be automatically resolved by the communicating parties.',
		),
		# [RFC4954] (Standards Track)
		'5.6'  => array(
			'title'       => 'Authentication Exchange line is too long',
			'description' => 'This enhanced status code SHOULD be returned when the server fails the AUTH command due to the client sending a [BASE64] response which is longer than the maximum buffer size available for the currently selected SASL mechanism. This is useful for both permanent and persistent transient errors.',
		),
		# [RFC3463] (Standards Track)
		'6.0'  => array(
			'title'       => 'Other or undefined media error',
			'description' => 'Something about the content of a message caused it to be considered undeliverable and the problem cannot be well expressed with any of the other provided detail codes.',
		),
		'6.1'  => array(
			'title'       => 'Media not supported',
			'description' => 'The media of the message is not supported by either the delivery protocol or the next system in the forwarding path. This is useful only as a permanent error.',
		),
		'6.2'  => array(
			'title'       => 'Conversion required and prohibited',
			'description' => 'The content of the message must be converted before it can be delivered and such conversion is not permitted. Such prohibitions may be the expression of the sender in the message itself or the policy of the sending host.',
		),
		'6.3'  => array(
			'title'       => 'Conversion required but not supported',
			'description' => 'The message content must be converted in order to be forwarded but such conversion is not possible or is not practical by a host in the forwarding path. This condition may result when an ESMTP gateway supports 8bit transport but is not able to downgrade the message to 7 bit as required for the next hop.',
		),
		'6.4'  => array(
			'title'       => 'Conversion with loss performed',
			'description' => 'This is a warning sent to the sender when message delivery was successfully but when the delivery required a conversion in which some data was lost. This may also be a permanent error if the sender has indicated that conversion with loss is prohibited for the message.',
		),
		'6.5'  => array(
			'title'       => 'Conversion Failed',
			'description' => 'A conversion was required but was unsuccessful. This may be useful as a permanent or persistent temporary notification.',
		),
		# [RFC4468] (Standards Track)
		'6.6'  => array(
			'title'       => 'Message content not available',
			'description' => 'The message content could not be fetched from a remote system. This may be useful as a permanent or persistent temporary notification.',
		),
		# [RFC6531] (Standards track)
		'6.7'  => array(
			'title'       => 'Non-ASCII addresses not permitted for that sender/recipient',
			'description' => 'This indicates the reception of a MAIL or RCPT command that non-ASCII addresses are not permitted',
		),
		# [RFC6531] (Standards track)
		'6.8'  => array(
			'title'       => 'UTF-8 string reply is required, but not permitted by the SMTP client',
			'description' => 'This indicates that a reply containing a UTF-8 string is required to show the mailbox name, but that form of response is not permitted by the SMTP client.',
		),
		# [RFC6531] (Standards track)
		'6.9'  => array(
			'title'       => 'UTF-8 header message cannot be transferred to one or more recipients, so the message must be rejected',
			'description' => 'This indicates that transaction failed after the final "." of the DATA command.',
		),
		# [RFC6531] (Standards track)
		'6.10' => array(
			'title'       => '',
			'description' => 'This is a duplicate of X.6.8 and is thus deprecated.',
		),
		# [RFC3463] (Standards Track)
		'7.0'  => array(
			'title'       => 'Other or undefined security status',
			'description' => 'Something related to security caused the message to be returned, and the problem cannot be well expressed with any of the other provided detail codes. This status code may also be used when the condition cannot be further described because of security policies in force.',
		),
		'7.1'  => array(
			'title'       => 'Delivery not authorized, message refused',
			'description' => 'The sender is not authorized to send to the destination. This can be the result of per-host or per-recipient filtering. This memo does not discuss the merits of any such filtering, but provides a mechanism to report such. This is useful only as a permanent error.',
		),
		'7.2'  => array(
			'title'       => 'Mailing list expansion prohibited',
			'description' => 'The sender is not authorized to send a message to the intended mailing list. This is useful only as a permanent error.',
		),
		'7.3'  => array(
			'title'       => 'Security conversion required but not possible',
			'description' => 'A conversion from one secure messaging protocol to another was required for delivery and such conversion was not possible. This is useful only as a permanent error.',
		),
		'7.4'  => array(
			'title'       => 'Security features not supported',
			'description' => 'A message contained security features such as secure authentication that could not be supported on the delivery protocol. This is useful only as a permanent error.',
		),
		'7.5'  => array(
			'title'       => 'Cryptographic failure',
			'description' => 'A transport system otherwise authorized to validate or decrypt a message in transport was unable to do so because necessary information such as key was not available or such information was invalid.',
		),
		'7.6'  => array(
			'title'       => 'Cryptographic algorithm not supported',
			'description' => 'A transport system otherwise authorized to validate or decrypt a message was unable to do so because the necessary algorithm was not supported.',
		),
		'7.7'  => array(
			'title'       => 'Message integrity failure',
			'description' => 'A transport system otherwise authorized to validate a message was unable to do so because the message was corrupted or altered. This may be useful as a permanent, transient persistent, or successful delivery code.',
		),
		# [RFC4954] (Standards Track)
		'7.8'  => array(
			'title'       => 'Authentication credentials invalid',
			'description' => 'This response to the AUTH command indicates that the authentication failed due to invalid or insufficient authentication credentials. In this case, the client SHOULD ask the user to supply new credentials (such as by presenting a password dialog box).',
		),
		'7.9'  => array(
			'title'       => 'Authentication mechanism is too weak',
			'description' => 'This response to the AUTH command indicates that the selected authentication mechanism is weaker than server policy permits for that user. The client SHOULD retry with a new authentication mechanism.',
		),
		# [RFC5248] (Best current practice)
		'7.10' => array(
			'title'       => 'Encryption Needed',
			'description' => 'This indicates that external strong privacy layer is needed in order to use the requested authentication mechanism. This is primarily intended for use with clear text authentication mechanisms. A client which receives this may activate a security layer such as TLS prior to authenticating, or attempt to use a stronger mechanism.',
		),
		# [RFC4954] (Standards Track)
		'7.11' => array(
			'title'       => 'Encryption required for requested authentication mechanism',
			'description' => 'This response to the AUTH command indicates that the selected authentication mechanism may only be used when the underlying SMTP connection is encrypted. Note that this response code is documented here for historical purposes only. Modern implementations SHOULD NOT advertise mechanisms that are not permitted due to lack of encryption, unless an encryption layer of sufficient strength is currently being employed.',
		),
		'7.12' => array(
			'title'       => 'A password transition is needed',
			'description' => 'This response to the AUTH command indicates that the user needs to transition to the selected authentication mechanism. This is typically done by authenticating once using the [PLAIN] authentication mechanism. The selected mechanism SHOULD then work for authentications in subsequent sessions.',
		),
		# [RFC5248] (Best current practice)
		'7.13' => array(
			'title'       => 'User Account Disabled',
			'description' => 'Sometimes a system administrator will have to disable a user\'s account (e.g., due to lack of payment, abuse, evidence of a break-in attempt, etc). This error code occurs after a successful authentication to a disabled account. This informs the client that the failure is permanent until the user contacts their system administrator to get the account re-enabled. It differs from a generic authentication failure where the client\'s best option is to present the passphrase entry dialog in case the user simply mistyped their passphrase.',
		),
		'7.14' => array(
			'title'       => 'Trust relationship required',
			'description' => 'The submission server requires a configured trust relationship with a third-party server in order to access the message content. This value replaces the prior use of X.7.8 for this error condition. thereby updating [RFC4468].',
		),
		# [RFC6710] (Standards Track)
		'7.15' => array(
			'title'       => 'Priority Level is too low',
			'description' => 'The specified priority level is below the lowest priority acceptable for the receiving SMTP server. This condition might be temporary, for example the server is operating in a mode where only higher priority messages are accepted for transfer and delivery, while lower priority messages are rejected.',
		),
		'7.16' => array(
			'title'       => 'Message is too big for the specified priority',
			'description' => 'The message is too big for the specified priority. This condition might be temporary, for example the server is operating in a mode where only higher priority messages below certain size are accepted for transfer and delivery.',
		),
		# [RFC7293] (Standards Track)
		'7.17' => array(
			'title'       => 'Mailbox owner has changed',
			'description' => 'This status code is returned when a message is received with a Require-Recipient-Valid-Since field or RRVS extension and the receiving system is able to determine that the intended recipient mailbox has not been under continuous ownership since the specified date-time.',
		),
		'7.18' => array(
			'title'       => 'Domain owner has changed',
			'description' => 'This status code is returned when a message is received with a Require-Recipient-Valid-Since field or RRVS extension and the receiving system wishes to disclose that the owner of the domain name of the recipient has changed since the specified date-time.',
		),
		'7.19' => array(
			'title'       => 'RRVS test cannot be completed',
			'description' => 'This status code is returned when a message is received with a Require-Recipient-Valid-Since field or RRVS extension and the receiving system cannot complete the requested evaluation because the required timestamp was not recorded. The message originator needs to decide whether to reissue the message without RRVS protection.',
		),
		# [RFC7372] (Standards Track); [RFC6376] (Standards Track)
		'7.20' => array(
			'title'       => 'No passing DKIM signature found',
			'description' => 'This status code is returned when a message did not contain any passing DKIM signatures. (This violates the advice of Section 6.1 of [RFC6376].)',
		),
		'7.21' => array(
			'title'       => 'No acceptable DKIM signature found',
			'description' => 'This status code is returned when a message contains one or more passing DKIM signatures, but none are acceptable. (This violates the advice of Section 6.1 of [RFC6376].)',
		),
		'7.22' => array(
			'title'       => 'No valid author-matched DKIM signature found',
			'description' => 'This status code is returned when a message contains one or more passing DKIM signatures, but none are acceptable because none have an identifier(s) that matches the author address(es) found in the From header field. This is a special case of X.7.21. (This violates the advice of Section 6.1 of [RFC6376].)',
		),
		# [RFC7372] (Standards Track); [RFC7208] (Standards Track)
		'7.23' => array(
			'title'       => 'SPF validation failed',
			'description' => 'This status code is returned when a message completed an SPF check that produced a \"fail\" result, contrary to local policy requirements. Used in place of 5.7.1 as described in Section 8.4 of [RFC7208].',
		),

		'7.24' => array(
			'title'       => 'SPF validation error',
			'description' => 'This status code is returned when evaluation of SPF relative to an arriving message resulted in an error. Used in place of 4.4.3 or 5.5.2 as described in Sections 8.6 and 8.7 of [RFC7208].',
		),
		# [RFC7372] (Standards Track); [RFC7601] (Standards Track)
		'7.25' => array(
			'title'       => 'Reverse DNS validation failed',
			'description' => 'This status code is returned when an SMTP client\'s IP address failed a reverse DNS validation check, contrary to local policy requirements.',
		),
		# [RFC7372] (Standards Track)
		'7.26' => array(
			'title'       => 'Multiple authentication checks failed',
			'description' => 'This status code is returned when a message failed more than one message authentication check, contrary to local policy requirements. The particular mechanisms that failed are not specified.',
		),
		'7.27' => array(
			'title'       => 'Sender address has null MX',
			'description' => 'This status code is returned when the associated sender address has a null MX, and the SMTP receiver is configured to reject mail from such sender (e.g., because it could not return a DSN).',
		),
	);

	protected static $bounce_list = array(
		'[45]\d\d[- ]#?([45]\.\d\.\d{1,2})'                              => 'x',
		# use the code from the regex
		'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d{1,2})'        => 'x',
		# use the code from the regex
		'Status: ([45]\.\d\.\d{1,2})'                                    => 'x',
		# use the code from the regex
		'not yet been delivered'                                         => '4.2.0',
		'Message will be retried for'                                    => '4.2.0',
		'Connection frequency limited\. http:\/\/service\.mail\.qq\.com' => '4.2.0',
		'Benutzer hat zuviele Mails auf dem Server'                      => '4.2.2',
		#.DE "mailbox full"
		'exceeded storage allocation'                                    => '4.2.2',
		'Mailbox full'                                                   => '4.2.2',
		'mailbox is full'                                                => '4.2.2',
		#BH
		'Mailbox quota usage exceeded'                                   => '4.2.2',
		#BH
		'Mailbox size limit exceeded'                                    => '4.2.2',
		'over ?quota'                                                    => '4.2.2',
		'quota exceeded'                                                 => '4.2.2',
		'Quota violation'                                                => '4.2.2',
		'User has exhausted allowed storage space'                       => '4.2.2',
		'User has too many messages on the server'                       => '4.2.2',
		'User mailbox exceeds allowed size'                              => '4.2.2',
		'mailfolder is full'                                             => '4.2.2',
		'user has Exceeded'                                              => '4.2.2',
		'not enough storage space'                                       => '4.2.2',
		'Delivery attempts will continue to be made for'                 => '4.3.2',
		#SB: 4.3.2 is a more generic 'defer'; Kanon added. From Symantec_AntiVirus_for_SMTP_Gateways@uqam.ca Im not sure why Symantec delayed this message, but x.2.x means something to do with the mailbox, which seemed appropriate. x.5.x (protocol) or x.7.x (security) also seem possibly appropriate. It seems a lot of times its x.5.x when it seems to me it should be x.7.x, so maybe x.5.x is standard when mail is rejected due to spam-like characteristics instead of x.7.x like I think it should be.
		'delivery temporarily suspended'                                 => '4.3.2',
		'Greylisted for 5 minutes'                                       => '4.3.2',
		'Greylisting in action'                                          => '4.3.2',
		'Server busy'                                                    => '4.3.2',
		'server too busy'                                                => '4.3.2',
		'system load is too high'                                        => '4.3.2',
		'temporarily deferred'                                           => '4.3.2',
		'temporarily unavailable'                                        => '4.3.2',
		'Throttling'                                                     => '4.3.2',
		'too busy to accept mail'                                        => '4.3.2',
		'too many connections'                                           => '4.3.2',
		'too many sessions'                                              => '4.3.2',
		'Too much load'                                                  => '4.3.2',
		'try again later'                                                => '4.3.2',
		'Try later'                                                      => '4.3.2',
		'retry timeout exceeded'                                         => '4.4.7',
		'queue too long'                                                 => '4.4.7',
		'554 delivery error:'                                            => '5.1.1',
		#SB: Yahoo/rogers.com generic delivery failure (see also OU-00)
		'account has been disabled'                                      => '5.1.1',
		'account is unavailable'                                         => '5.1.1',
		'Account not found'                                              => '5.1.1',
		'Address invalid'                                                => '5.1.1',
		'Address is unknown'                                             => '5.1.1',
		'Address unknown'                                                => '5.1.1',
		'Addressee unknown'                                              => '5.1.1',
		'ADDRESS_NOT_FOUND'                                              => '5.1.1',
		'bad address'                                                    => '5.1.1',
		'Bad destination mailbox address'                                => '5.1.1',
		'destin. Sconosciuto'                                            => '5.1.1',
		#.IT "user unknown"
		'Destinatario errato'                                            => '5.1.1',
		#.IT "invalid"
		'Destinatario sconosciuto o mailbox disatttivata'                => '5.1.1',
		#.IT "unknown /disabled"
		'does not exist'                                                 => '5.1.1',
		'Email Address was not found'                                    => '5.1.1',
		'Excessive userid unknowns'                                      => '5.1.1',
		'Indirizzo inesistente'                                          => '5.1.1',
		#.IT "no user"
		'Invalid account'                                                => '5.1.1',
		'invalid address'                                                => '5.1.1',
		'Invalid or unknown virtual user'                                => '5.1.1',
		'Invalid mailbox'                                                => '5.1.1',
		'Invalid recipient'                                              => '5.1.1',
		'Mailbox not found'                                              => '5.1.1',
		'mailbox unavailable'                                            => '5.1.1',
		'nie istnieje'                                                   => '5.1.1',
		#.PL "does not exist"
		'Nie ma takiego konta'                                           => '5.1.1',
		#.PL "no such account"
		'No mail box available for this user'                            => '5.1.1',
		'no mailbox here'                                                => '5.1.1',
		'No one with that email address here'                            => '5.1.1',
		'no such address'                                                => '5.1.1',
		'no such email address'                                          => '5.1.1',
		'No such mail drop defined'                                      => '5.1.1',
		'No such mailbox'                                                => '5.1.1',
		'No such person at this address'                                 => '5.1.1',
		'no such recipient'                                              => '5.1.1',
		'No such user'                                                   => '5.1.1',
		'not a known user'                                               => '5.1.1',
		'not a valid mailbox'                                            => '5.1.1',
		'not a valid user'                                               => '5.1.1',
		'not available'                                                  => '5.1.1',
		'not exists'                                                     => '5.1.1',
		'Recipient address rejected'                                     => '5.1.1',
		'Recipient not allowed'                                          => '5.1.1',
		'Recipient not found'                                            => '5.1.1',
		'recipient rejected'                                             => '5.1.1',
		'Recipient unknown'                                              => '5.1.1',
		"server doesn't handle mail for that user"                       => '5.1.1',
		'This account is disabled'                                       => '5.1.1',
		'This address no longer accepts mail'                            => '5.1.1',
		'This email address is not known to this system'                 => '5.1.1',
		'Unknown account'                                                => '5.1.1',
		'unknown address or alias'                                       => '5.1.1',
		'Unknown email address'                                          => '5.1.1',
		'Unknown local part'                                             => '5.1.1',
		'unknown or illegal alias'                                       => '5.1.1',
		'unknown or illegal user'                                        => '5.1.1',
		'Unknown recipient'                                              => '5.1.1',
		'unknown user'                                                   => '5.1.1',
		'user disabled'                                                  => '5.1.1',
		"User doesn't exist in this server"                              => '5.1.1',
		'user invalid'                                                   => '5.1.1',
		'User is suspended'                                              => '5.1.1',
		'User is unknown'                                                => '5.1.1',
		'User not found'                                                 => '5.1.1',
		'User not known'                                                 => '5.1.1',
		'User unknown'                                                   => '5.1.1',
		'valid RCPT command must precede DATA'                           => '5.1.1',
		'was not found in LDAP server'                                   => '5.1.1',
		'We are sorry but the address is invalid'                        => '5.1.1',
		'Unable to find alias user'                                      => '5.1.1',
		"domain isn't in my list of allowed rcpthosts"                   => '5.1.2',
		'Esta casilla ha expirado por falta de uso'                      => '5.1.2',
		#BH ES:expired
		'host ?name is unknown'                                          => '5.1.2',
		'no relaying allowed'                                            => '5.1.2',
		'no such domain'                                                 => '5.1.2',
		'not our customer'                                               => '5.1.2',
		'relay not permitted'                                            => '5.1.2',
		'Relay access denied'                                            => '5.1.2',
		'relaying denied'                                                => '5.1.2',
		'Relaying not allowed'                                           => '5.1.2',
		'This system is not configured to relay mail'                    => '5.1.2',
		'Unable to relay'                                                => '5.1.2',
		'unrouteable mail domain'                                        => '5.1.2',
		#BH
		'we do not relay'                                                => '5.1.2',
		'Old address no longer valid'                                    => '5.1.6',
		'recipient no longer on server'                                  => '5.1.6',
		'Sender address rejected'                                        => '5.1.8',
		'exceeded the rate limit'                                        => '5.2.0',
		'Local Policy Violation'                                         => '5.2.0',
		'Mailbox currently suspended'                                    => '5.2.0',
		'mailbox unavailable'                                            => '5.2.0',
		'mail can not be delivered'                                      => '5.2.0',
		'Delivery failed'                                                => '5.2.0',
		'mail couldn\'t be delivered'                                    => '5.2.0',
		'The account or domain may not exist'                            => '5.2.0',
		#I guess.... seems like 5.1.1, 5.1.2, or 5.4.4 would fit too, but 5.2.0 seemed most generic
		'Account disabled'                                               => '5.2.1',
		'account has been disabled'                                      => '5.2.1',
		'Account Inactive'                                               => '5.2.1',
		'Adressat unbekannt oder Mailbox deaktiviert'                    => '5.2.1',
		'Destinataire inconnu ou boite aux lettres desactivee'           => '5.2.1',
		#.FR disabled
		'mail is not currently being accepted for this mailbox'          => '5.2.1',
		'El usuario esta en estado: inactivo'                            => '5.2.1',
		#.IT inactive
		'email account that you tried to reach is disabled'              => '5.2.1',
		'inactive user'                                                  => '5.2.1',
		'Mailbox disabled for this recipient'                            => '5.2.1',
		'mailbox has been blocked due to inactivity'                     => '5.2.1',
		'mailbox is currently unavailable'                               => '5.2.1',
		'Mailbox is disabled'                                            => '5.2.1',
		'Mailbox is inactive'                                            => '5.2.1',
		'Mailbox Locked or Suspended'                                    => '5.2.1',
		'mailbox temporarily disabled'                                   => '5.2.1',
		'Podane konto jest zablokowane administracyjnie lub nieaktywne'  => '5.2.1',
		#.PL locked or inactive
		"Questo indirizzo e' bloccato per inutilizzo"                    => '5.2.1',
		#.IT blocked/expired
		'Recipient mailbox was disabled'                                 => '5.2.1',
		'Domain name not found'                                          => '5.2.1',
		'couldn\'t find any host named'                                  => '5.4.4',
		'couldn\'t find any host by that name'                           => '5.4.4',
		'PERM_FAILURE: DNS Error'                                        => '5.4.4',
		#SB: Routing failure
		'Temporary lookup failure'                                       => '5.4.4',
		'unrouteable address'                                            => '5.4.4',
		"can't connect to"                                               => '5.4.4',
		'Too many hops'                                                  => '5.4.6',
		'Requested action aborted'                                       => '5.5.0',
		'rejecting password protected file attachment'                   => '5.6.2',
		#RFC "Conversion required and prohibited"
		'550 OU-00'                                                      => '5.7.1',
		#SB hotmail returns a OU-001 if you're on their blocklist
		'550 SC-00'                                                      => '5.7.1',
		#SB hotmail returns a SC-00x if you're on their blocklist
		'550 DY-00'                                                      => '5.7.1',
		#SB hotmail returns a DY-00x if you're a dynamic IP
		'554 denied'                                                     => '5.7.1',
		'You have been blocked by the recipient'                         => '5.7.1',
		'requires that you verify'                                       => '5.7.1',
		'Access denied'                                                  => '5.7.1',
		'Administrative prohibition - unable to validate recipient'      => '5.7.1',
		'Blacklisted'                                                    => '5.7.1',
		'blocke?d? for spam'                                             => '5.7.1',
		'conection refused'                                              => '5.7.1',
		'Connection refused due to abuse'                                => '5.7.1',
		'dial-up or dynamic-ip denied'                                   => '5.7.1',
		'Domain has received too many bounces'                           => '5.7.1',
		'failed several antispam checks'                                 => '5.7.1',
		'found in a DNS blacklist'                                       => '5.7.1',
		'IPs blocked'                                                    => '5.7.1',
		'is blocked by'                                                  => '5.7.1',
		'Mail Refused'                                                   => '5.7.1',
		'Message does not pass DomainKeys'                               => '5.7.1',
		'Message looks like spam'                                        => '5.7.1',
		'Message refused by'                                             => '5.7.1',
		'not allowed access from your location'                          => '5.7.1',
		'permanently deferred'                                           => '5.7.1',
		'Rejected by policy'                                             => '5.7.1',
		'rejected by Windows Live Hotmail for policy reasons'            => '5.7.1',
		'Rejected for policy reasons'                                    => '5.7.1',
		'Rejecting banned content'                                       => '5.7.1',
		'Sorry, looks like spam'                                         => '5.7.1',
		'spam message discarded'                                         => '5.7.1',
		'Too many spams from your IP'                                    => '5.7.1',
		'TRANSACTION FAILED'                                             => '5.7.1',
		'Transaction rejected'                                           => '5.7.1',
		'Wiadomosc zostala odrzucona przez system antyspamowy'           => '5.7.1',
		#.PL rejected as spam
		'Your message was declared Spam'                                 => '5.7.1'
	);

	# triggers for auto responders
	protected static $auto_respond_list = array(
		'^\[?auto.{0,20}reply\]?',
		'^auto[ -]?response',
		'^Yahoo! auto response',
		'^Thank you for your email\.',
		'^Vacation.{0,20}(reply|respon)',
		'^out.?of (the )?office',
		'^(I am|I\'m).{0,20}\s(away|on vacation|on leave|out of office|out of the office)',
		"\350\207\252\345\212\250\345\233\236\345\244\215"   #sino.com,  163.com  UTF8 encoded
	);

	protected static $bounce_subj = array(
		'deletver reports about your e?mail',
		'delivery errors',
		'delivery failure',
		'delivery has failed',
		'delivery notification',
		'delivery problem',
		'delivery reports about your email',
		// 'delivery status notif',
		'delivery status',
		'failure delivery',
		'failure notice',
		'mail delivery fail',            #catches failure and failed
		'mail delivery system',
		'mailserver notification',
		'mail status report',
		'mail system error',
		'mail transaction failed',
		'mdaemon notification',
		'message delayed',
		'nondeliverable mail',
		'Non[_ ]remis[_ ]',            #fr
		'No[_ ]se[_ ]puede[_ ]entregar',    #es
		'Onbestelbaar',                #nl
		'returned e?mail',
		'returned to sender',
		'returning message to sender',
		'spam eater',
		'undeliverable',
		'undelivered mail',
		'warning: message',
	);

	public static function getAutoRespondList() {
		return self::$auto_respond_list;
	}

	public static function getBounceList() {
		return self::$bounce_list;
	}

	public static function getBounceSubj() {
		return self::$bounce_subj;
	}

	public static function getStatusCodeClasses() {
		return self::$status_code_classes;
	}

	public static function getStatusCodeSubClasses() {
		return self::$status_code_sub_classes;
	}
}