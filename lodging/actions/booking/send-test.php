<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\email\Email;
use equal\email\EmailAttachment;

use communication\TemplateAttachment;
use documents\Document;
use lodging\sale\booking\Booking;
use core\setting\Setting;
use core\Mail;
use core\Lang;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking quote as attachment.",
    'params' 		=>	[
    ],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context', 'cron']
]);


// init local vars with inputs
list($context, $cron) = [ $providers['context'], $providers['cron'] ];



// create message
$message = new Email();
$message->setTo('cedricfrancoys@gmail.com')
        ->setReplyTo('reception@kaleo-asbl.be')
        ->setSubject('test')
        ->setContentType("text/html")
        ->setBody('<html><body>test lorem ipsum</body></html>');


// queue message
Mail::queue($message);



$context->httpResponse()
        ->status(204)
        ->send();