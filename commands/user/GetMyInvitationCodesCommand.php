<?php

namespace Telegram\Bot\Commands;

use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class GetMyInvitationCodesCommand.
 */
class GetMyInvitationCodesCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "my_invitation_codes";
	
	/**
	 * @var string Command Description
	 */
	protected $description = "Description_My invitation codes";
	
	/**
	 * {@inheritdoc}
	 */
	public function handle()
	{
		$update = $this->getUpdate();
		
		$telegram_id = $update->getMessage()->chat->id;
		
		$user = user_is_verified($telegram_id);
		
		if(!$user['status']) {
			return false;
		}

		$lcApi = new \LCAPPAPI();
		$data = $lcApi->makeRequest('get-my-invitation-codes', ['telegram_id' => $telegram_id]);

		$options = [
			'chat_id' => $telegram_id,
		];

		if($data['status'] === 'error' || empty($data)) {
			$options['text'] = __("Error! Try again later.", $user['user']['language']);
			$this->telegram->sendMessage($options);
		} else {
			if(empty($data['codes'])) {
				$options['text'] = __('You have no invitation codes available', $user['user']['language']);
				$this->telegram->sendMessage($options);
			} else {
				foreach ($data['codes'] as $key => $code) {
					$options['text'] = $code['code'] . (empty($code['user']) ? '' : ', ' . __('Used by', $user['user']['language']) . ' @' . $code['user']['publicAlias'] . ' ' . __('on', $user['user']['language']) . ' ' . date('m/d/Y', $code['signup_date']));
					$this->telegram->sendMessage($options);
				}
				$options['text'] = __("You can forward any code which is not used to any of your telegram contacts along with the message below", $user['user']['language']);
				$this->telegram->sendMessage($options);

				$options['text'] = "\xF0\x9F\x94\xA5 " . __("This is an invitation to Zeya — a chatbot-based community of people where you can find people based on shared interests and emotional resonance. Open bot: @zeya_community_bot and paste your unique code when asked. You cannot proceed with registration without a code which you can only get from existing community members.", $user['user']['language']);
				$this->telegram->sendMessage($options);
			}

		}
	}
}
