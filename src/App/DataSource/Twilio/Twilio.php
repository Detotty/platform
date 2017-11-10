<?php

namespace Ushahidi\App\DataSource\Twilio;

/**
 * Twilio Data Provider
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    DataSource\Twilio
 * @copyright  2013 Ushahidi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

use Ushahidi\App\DataSource\CallbackDataSource;
use Ushahidi\App\DataSource\OutgoingAPIDataSource;
use Ushahidi\App\DataSource\Message\Type as MessageType;
use Ushahidi\Core\Entity\Contact;
use Services_Twilio;
use Services_Twilio_RestException;
use Log;

class Twilio implements CallbackDataSource, OutgoingAPIDataSource
{

	protected $config;

	/**
	 * Constructor function for DataSource
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function getName()
    {
		return 'Twilio';
	}

	public function getId()
	{
		return strtolower($this->getName());
	}

	public function getServices()
	{
		return [MessageType::SMS];
	}

	public function getOptions()
	{
		return array(
			'from' => array(
				'label' => 'Phone Number',
				'input' => 'text',
				'description' => 'The from phone number.
					A Twilio phone number enabled for the type of message you wish to send. ',
				'rules' => array('required')
			),
			'account_sid' => array(
				'label' => 'Account SID',
				'input' => 'text',
				'description' => 'The unique id of the Account that sent this message.',
				'rules' => array('required')
			),
			'auth_token' => array(
				'label' => 'Auth Token',
				'input' => 'text',
				'description' => '',
				'rules' => array('required')
			),
			'sms_auto_response' => array(
				'label' => 'SMS Auto response',
				'input' => 'text',
				'description' => '',
				'rules' => array('required')
			)
		);
	}

	/**
	 * Client to talk to the Twilio API
	 *
	 * @var Services_Twilio
	 */
	private $client;

	/**
	 * @return mixed
	 */
	public function send($to, $message, $title = "")
	{
		if (! isset($this->client)) {
			$this->client = new Services_Twilio($this->config['account_sid'], $this->config['auth_token']);
		}

		// Send!
		try {
			$message = $this->client->account->messages->sendMessage($this->config['from'], '+'.$to, $message);
			return array(DataSource\Message\Status::SENT, $message->sid);
		} catch (Services_Twilio_RestException $e) {
			Log::error($e->getMessage());
		}

		return array(DataSource\Message\Status::FAILED, false);
	}

	public function registerRoutes(\Laravel\Lumen\Routing\Router $router)
	{
		$router->post('sms/twilio[/]', 'Ushahidi\App\DataSource\Twilio\TwilioController@handleRequest');
	}

	public function verifySid($sid)
	{
        if (isset($this->config['account_sid']) and $sid === $this->config['account_sid']) {
            return true;
        }

        return false;
	}

	public function getSmsAutoResponse()
	{
		return isset($this->config['sms_auto_response']) ? $this->config['sms_auto_response'] : false;
	}
}
